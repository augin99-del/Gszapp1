<?php
declare(strict_types=1);
require_once __DIR__.'/planning_engine.php';
require_once __DIR__.'/operations.php';
function planLoad(PDO $pdo):array {
 $v1=gszV1Ready($pdo);$extra=$v1?',r.service_kind,r.allowed_weekdays_mask,r.recurrence_mode':'';
 $tasks=$pdo->query("SELECT t.*,p.name property_name,p.address,p.seed_key property_seed,r.rule_source,r.season_start_month,r.season_end_month".$extra." FROM tasks t LEFT JOIN properties p ON p.id=t.property_id LEFT JOIN recurring_jobs r ON r.id=t.recurring_job_id ORDER BY t.id LIMIT 5001")->fetchAll();
 $jobs=$pdo->query('SELECT r.*,p.name property_name,p.address,p.seed_key property_seed FROM recurring_jobs r LEFT JOIN properties p ON p.id=r.property_id ORDER BY r.id LIMIT 501')->fetchAll();
 $appointments=$pdo->query('SELECT * FROM planning_appointments'.(opsReady($pdo)?' WHERE cancelled_at IS NULL':'').' ORDER BY day,start_time,id LIMIT 2001')->fetchAll();
 if(count($tasks)>5000||count($jobs)>500||count($appointments)>2000)throw new RuntimeException('Planungsgrenze erreicht (5000 Aufgaben, 500 Regeln, 2000 Termine). Keine unvollständige Vorschau erzeugt.');
 $settings=$pdo->query('SELECT * FROM planning_settings WHERE id=1')->fetch();if($v1)$settings['planning_from']=(new DateTimeImmutable('today',new DateTimeZone('Europe/Berlin')))->format('Y-m-d');
 return ['tasks'=>$tasks,'jobs'=>$jobs,'appointments'=>$appointments,'settings'=>$settings,'starts'=>$pdo->query('SELECT * FROM planning_days ORDER BY day')->fetchAll()];
}
function planFingerprint(array $data):string{return hash('sha256',json_encode($data,JSON_THROW_ON_ERROR));}
function planCandidates(array $data,string $monday):array {
 $end=planDate($monday,6);$list=[];$occurrences=[];$openJobs=[];
 foreach($data['tasks'] as $t){$t['key']='t'.$t['id'];if(!$t['done']&&$t['recurring_job_id'])$openJobs[(int)$t['recurring_job_id']]=true;if($t['recurring_job_id']&&$t['scheduled_for'])$occurrences[$t['recurring_job_id'].':'.$t['scheduled_for']]=true;
 if(($t['due_date']!==null&&$t['due_date']<=$end&&(!$t['done']||$t['due_date']>=$monday))||$t['due_date']===null)$list[]=$t;}
 foreach($data['jobs'] as $j){if(gszRecurrenceMode($j)==='completion'&&isset($openJobs[(int)$j['id']]))continue;if(!$j['active']||$j['rule_source']==='routine_unverified'||$j['frequency']==='seasonal'||!$j['next_due_date'])continue;
  $d=$j['next_due_date'];$anchor=(int)($j['anchor_day']??substr($d,8,2));$steps=0;
  while($d<=$end){if(++$steps>104)throw new RuntimeException('Wiederholungsrückstand zu groß. Erst bestehenden Scheduler begrenzt aufarbeiten.');
   if(!isset($occurrences[$j['id'].':'.$d])&&zgsInSeason($d,$j['season_start_month']===null?null:(int)$j['season_start_month'],$j['season_end_month']===null?null:(int)$j['season_end_month'])){
    $list[]=['key'=>'r'.$j['id'].':'.$d,'id'=>null,'recurring_job_id'=>$j['id'],'scheduled_for'=>$d,'due_date'=>$d,'title'=>$j['title'],'property_id'=>$j['property_id'],'property_name'=>$j['property_name'],'address'=>$j['address'],'property_seed'=>$j['property_seed'],'duration_minutes'=>$j['duration_minutes'],'rule_source'=>$j['rule_source'],'season_start_month'=>$j['season_start_month'],'season_end_month'=>$j['season_end_month'],'done'=>0,'priority'=>'normal','sort_order'=>0,'start_time'=>null,'plan_locked'=>0,'notes'=>'','window_start'=>null,'window_end'=>null,'service_kind'=>$j['service_kind']??'auto','allowed_weekdays_mask'=>$j['allowed_weekdays_mask']??null];
   }if(gszRecurrenceMode($j)==='completion')break;$d=zgsAdvanceDate($d,$j['frequency'],$anchor);
  }
 }
 return $list;
}
function planBuild(array $data,string $monday,?string $onlyDay=null):array {
 return planWeek(planCandidates($data,$monday),$data['appointments'],$data['settings'],array_column($data['starts'],'start_time','day'),$monday,$onlyDay);
}
function planSaveProposal(PDO $pdo,int $admin,string $monday,?string $onlyDay):string {
 $data=planLoad($pdo);$plan=planBuild($data,$monday,$onlyDay);if(gszV1Ready($pdo)){$same=$pdo->prepare("SELECT token,payload FROM planning_proposals WHERE admin_id=? AND fingerprint=? AND applied_at IS NULL AND created_at>=UTC_TIMESTAMP()-INTERVAL 2 HOUR ORDER BY created_at DESC LIMIT 10");$same->execute([$admin,planFingerprint($data)]);foreach($same->fetchAll() as $row){$saved=json_decode($row['payload'],true);if(!isset($saved['kind'])&&($saved['monday']??null)===$monday&&($saved['onlyDay']??null)===$onlyDay)return $row['token'];}}$token=bin2hex(random_bytes(32));
 $pdo->prepare('INSERT INTO planning_proposals(token,admin_id,fingerprint,payload) VALUES(?,?,?,?)')->execute([$token,$admin,planFingerprint($data),json_encode(['monday'=>$monday,'onlyDay'=>$onlyDay,'plan'=>$plan],JSON_THROW_ON_ERROR)]);return $token;
}
function planApply(PDO $pdo,int $admin,string $token):void {
 $pdo->beginTransaction();try{
  $s=$pdo->prepare('SELECT * FROM planning_proposals WHERE token=? AND admin_id=? FOR UPDATE');$s->execute([$token,$admin]);$p=$s->fetch();if(!$p)throw new RuntimeException('Vorschlag nicht gefunden.');if($p['applied_at']){$pdo->commit();return;}
  if(strtotime($p['created_at'].' UTC')<time()-7200)throw new RuntimeException('Vorschlag abgelaufen. Neu berechnen.');
  foreach(['planning_settings','planning_days','planning_appointments','properties','recurring_jobs','tasks'] as $table)$pdo->query('SELECT * FROM '.$table.' FOR UPDATE')->fetchAll();
  $data=planLoad($pdo);if(!hash_equals($p['fingerprint'],planFingerprint($data)))throw new RuntimeException('Bestand seit Vorschau geändert. Bitte neu berechnen.');
  $payload=json_decode($p['payload'],true,512,JSON_THROW_ON_ERROR);if(isset($payload['kind']))throw new RuntimeException('Routenvorschlag bitte im Routenbereich übernehmen.');
  $versionSql=opsReady($pdo)?',row_version=row_version+1':'';
  foreach($payload['plan']['days'] as $day=>$daily)foreach($daily['rows'] as $position=>$row){$t=$row['task'];if($t['done']||($payload['onlyDay']!==null&&$day!==$payload['onlyDay']))continue;
   if($row['end']!==null&&$row['end']>1439)throw new RuntimeException('Plan über Mitternacht: Wochenvorschau verwenden oder Aufgaben/Dauer vor Übernahme bearbeiten.');
   if(!$t['id']){zgsInsertOccurrence($pdo,['id'=>$t['recurring_job_id'],'title'=>$t['title'],'property_id'=>$t['property_id'],'duration_minutes'=>$t['duration_minutes']],$t['scheduled_for']);$s=$pdo->prepare('SELECT id FROM tasks WHERE recurring_job_id=? AND scheduled_for=?');$s->execute([$t['recurring_job_id'],$t['scheduled_for']]);$id=(int)$s->fetchColumn();}else $id=(int)$t['id'];
   // Locked/manual tasks remain byte-for-byte unchanged. Computed times are displayed,
   // not written into start_time (which is reserved for explicitly fixed times).
   if(!planFixed($t)&&(($t['due_date']??null)!==$day||(int)($t['planned_order']??0)!==$position+1))$pdo->prepare('UPDATE tasks SET due_date=?,planned_order=?'.$versionSql.' WHERE id=? AND done=0')->execute([$day,$position+1,$id]);
  }
  $pdo->prepare('UPDATE planning_proposals SET applied_at=NOW() WHERE token=?')->execute([$token]);$pdo->commit();
 }catch(Throwable $e){if($pdo->inTransaction())$pdo->rollBack();throw $e;}
}
function planValidateTask(array $in):array {
 $title=trim((string)($in['title']??''));$date=(string)($in['due_date']??'');$notes=trim((string)($in['notes']??''));
 if($title===''||mb_strlen($title)>190||!zgsValidDate($date)||mb_strlen($notes)>5000)throw new InvalidArgumentException('Aufgabe, Datum oder Notiz ungültig.');
 $duration=(string)($in['duration_minutes']??'');if($duration!==''&&(filter_var($duration,FILTER_VALIDATE_INT)===false||(int)$duration<1||(int)$duration>1440))throw new InvalidArgumentException('Dauer 1–1440 Minuten oder leer.');
 $time=(string)($in['start_time']??'');if($time!=='')planMinute($time);
 $priority=(string)($in['priority']??'normal');if(!in_array($priority,['urgent','high','normal','low'],true))throw new InvalidArgumentException('Priorität ungültig.');
 $order=filter_var($in['sort_order']??'0',FILTER_VALIDATE_INT);if($order===false||$order<0||$order>9999)throw new InvalidArgumentException('Reihenfolge 0–9999.');
 $lo=(string)($in['window_start']??'');$hi=(string)($in['window_end']??'');if(($lo!==''&&!zgsValidDate($lo))||($hi!==''&&!zgsValidDate($hi))||($lo!==''&&$hi!==''&&$lo>$hi))throw new InvalidArgumentException('Fälligkeitsfenster ungültig.');
 return [$title,$date,$notes,$duration===''?null:(int)$duration,$time?:null,$priority,$order,$lo?:null,$hi?:null];
}

function gszSuggestWeek(PDO $pdo,int $admin):?string {
 if(!gszV1Ready($pdo))return null;
 try{$day=(new DateTimeImmutable('today',new DateTimeZone('Europe/Berlin')))->modify('monday this week')->format('Y-m-d');$token=planSaveProposal($pdo,$admin,$day,null);if(session_status()===PHP_SESSION_ACTIVE)$_SESSION['planning_proposal_token']=$token;return $token;}catch(Throwable $e){error_log('GSZ_REPLAN_PREVIEW_FAILED');return null;}
}
