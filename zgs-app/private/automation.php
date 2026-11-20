<?php
declare(strict_types=1);
require_once __DIR__.'/planning_store.php';
function gszAutomationJob(PDO $pdo,string $name,callable $job):array {
 $start=microtime(true);$pdo->prepare("INSERT INTO automation_state(job_key,last_started,status) VALUES(?,UTC_TIMESTAMP(),'running') ON DUPLICATE KEY UPDATE last_started=UTC_TIMESTAMP(),status='running'")->execute([$name]);
 try{$result=$job();$pdo->prepare("UPDATE automation_state SET last_finished=UTC_TIMESTAMP(),last_success=UTC_TIMESTAMP(),status='PASS',duration_ms=?,result_json=?,last_error_code='' WHERE job_key=?")->execute([(int)((microtime(true)-$start)*1000),json_encode($result,JSON_THROW_ON_ERROR),$name]);return ['status'=>'PASS','result'=>$result];}
 catch(Throwable $e){$code='GSZ_'.strtoupper($name).'_FAILED';$pdo->prepare("UPDATE automation_state SET last_finished=UTC_TIMESTAMP(),status='BLOCKED',duration_ms=?,last_error_code=? WHERE job_key=?")->execute([(int)((microtime(true)-$start)*1000),$code,$name]);return ['status'=>'BLOCKED','error'=>$code];}
}
function gszRunAutomation(PDO $pdo,array $config,bool $mail=false,bool $waste=false):array {
 if(!gszV1Ready($pdo))throw new RuntimeException('Migration340 erforderlich.');$database=$pdo->query('SELECT DATABASE()')->fetchColumn();$key='gsz_automation_'.substr(hash('sha256',$database),0,32);$lock=$pdo->prepare('SELECT GET_LOCK(?,0)');$lock->execute([$key]);if(!(int)$lock->fetchColumn())return ['status'=>'SKIPPED','reason'=>'already_running'];
 try{$pdo->exec("UPDATE automation_state SET status='BLOCKED',last_error_code='GSZ_PREVIOUS_RUN_INTERRUPTED',last_finished=UTC_TIMESTAMP() WHERE status='running'");$today=(new DateTimeImmutable('today',new DateTimeZone('Europe/Berlin')))->format('Y-m-d');$results=[];$results['tasks']=gszAutomationJob($pdo,'tasks',fn()=>zgsGenerateDueTasks($pdo,$today));$admin=(int)$pdo->query('SELECT MIN(id) FROM admins')->fetchColumn();$results['planning']=gszAutomationJob($pdo,'planning',function()use($pdo,$admin){if(!$admin)throw new RuntimeException();$token=gszSuggestWeek($pdo,$admin);if(!$token)throw new RuntimeException();return ['preview_available'=>true,'applied'=>false];});
 $results['bank']=gszAutomationJob($pdo,'bank',fn()=>['pending_business_imports'=>(int)$pdo->query('SELECT COUNT(*) FROM bank_imports WHERE applied_at IS NULL')->fetchColumn(),'pending_estate_imports'=>(int)$pdo->query("SELECT COUNT(*) FROM estate_proposals WHERE kind='bank' AND applied_at IS NULL")->fetchColumn(),'online_fetch'=>false]);
 $results['reminders']=gszAutomationJob($pdo,'reminders',fn()=>gszReminderCounts($pdo,$today));
 require_once __DIR__.'/waste_connections.php';$hasConnections=wasteAutoReady($pdo)&&(bool)$pdo->query('SELECT property_id FROM waste_connections WHERE enabled=1 AND provider_key<>\'\' LIMIT 1')->fetchColumn();
 if($waste||$hasConnections)$results['waste']=gszAutomationJob($pdo,'waste',function()use($pdo,$config,$admin,$hasConnections){$results=[];if($hasConnections)$results=wasteSyncConnections($pdo,$admin)['results'];if(!empty($config['waste_sync_config_json']))$results=[...$results,...wasteSyncConfigured($pdo,$admin,$config)['results']];if(!$hasConnections&&empty($config['waste_sync_config_json']))throw new RuntimeException('Keine Quelle eingerichtet.');if(array_filter($results,fn($r)=>$r['status']==='BLOCKED'))throw new RuntimeException();return ['sources'=>count($results),'results'=>$results];});

 if($mail)$results['mail']=gszAutomationJob($pdo,'mail',function()use($pdo,$config){require_once __DIR__.'/mail_service.php';require_once __DIR__.'/mail_engine.php';$remote=gszMailRemote($config);return gszMailSync($pdo,$remote,'INBOX',(int)$config['mail_sync_limit']);});
 return ['status'=>array_filter($results,fn($r)=>$r['status']!=='PASS')?'BLOCKED':'PASS','jobs'=>$results];
 }finally{$release=$pdo->prepare('SELECT RELEASE_LOCK(?)');$release->execute([$key]);}
}
/** Read-only status. A configured expected interval does not prove hosting cron execution. */
function gszAutomationStatus(PDO $pdo,array $config):array {
 $registry=['tasks'=>'Regelaufgaben','planning'=>'Wochenvorschläge','waste'=>'Abfallkalender','mail'=>'Posteingang','bank'=>'Bankimport-Prüfliste','reminders'=>'Erinnerungen','seo'=>'SEO-Onlineabruf'];
 $stored=[];try{$stored=array_column($pdo->query('SELECT * FROM automation_state')->fetchAll(),null,'job_key');}catch(PDOException $e){}
 $hasWasteConnections=false;try{$hasWasteConnections=(bool)$pdo->query('SELECT property_id FROM waste_connections WHERE enabled=1 LIMIT 1')->fetchColumn();}catch(PDOException $e){}
 $scheduled=filter_var($config['automation_schedule_enabled']??false,FILTER_VALIDATE_BOOLEAN);$minutes=max(5,min(1440,(int)($config['automation_interval_minutes']??30)));$scheduledJobs=array_intersect(array_keys($registry),array_map('trim',explode(',',(string)($config['automation_scheduled_jobs']??'tasks,planning,bank,reminders'))));$out=[];if($hasWasteConnections)$scheduledJobs[]='waste';
 foreach($registry as $key=>$label){$row=$stored[$key]??['job_key'=>$key,'status'=>'NOT TESTED','last_started'=>null,'last_finished'=>null,'last_success'=>null,'duration_ms'=>null,'last_error_code'=>''];if(!isset($stored[$key])&&(($key==='mail'&&!filter_var($config['mail_enabled']??false,FILTER_VALIDATE_BOOLEAN))||($key==='waste'&&empty($config['waste_sync_config_json'])&&!$hasWasteConnections)||$key==='seo'))$row['status']='BLOCKED';$row['label']=$label;$row['next_planned']=null;if($scheduled&&$row['last_finished']&&in_array($key,$scheduledJobs,true)&&$key!=='seo')$row['next_planned']=(new DateTimeImmutable($row['last_finished'],new DateTimeZone('UTC')))->modify('+'.$minutes.' minutes')->format('Y-m-d H:i:s');$out[]=$row;}
 return $out;
}

function gszReminderCounts(PDO $pdo,string $berlinDay):array {
 if(!zgsValidDate($berlinDay))throw new InvalidArgumentException('Geschäftstag ungültig.');$q=$pdo->prepare('SELECT COUNT(*) FROM tasks WHERE done=0 AND due_date<?');$q->execute([$berlinDay]);return ['overdue_tasks'=>(int)$q->fetchColumn(),'unanswered_important_mail'=>(int)$pdo->query("SELECT COUNT(*) FROM mail_messages WHERE active=1 AND important=1 AND answered=0 AND automatic_message=0 AND sent_at<UTC_TIMESTAMP()-INTERVAL 24 HOUR")->fetchColumn(),'external_notifications_sent'=>false];
}
