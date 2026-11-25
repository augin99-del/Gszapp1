<?php
declare(strict_types=1);
/**
 * Schema 430: Helfer-Zugang. Ein Helfer sieht nur seine eigenen, ihm zugeteilten Aufgaben, tippt „Start“ und „Fertig“
 * und lädt Vorher-/Nachher-Fotos hoch. Kein GPS, keine Standortdaten – gespeichert werden nur die getippten Zeiten.
 * Helfer haben keinen Zugriff auf die übrige Zentrale (eigene Sitzungsvariable worker_id, kein admin_id).
 */
require_once __DIR__.'/operations.php';require_once __DIR__.'/recurrence_policy.php';require_once __DIR__.'/reports.php';require_once __DIR__.'/leads.php';
const GSZ_WORKER_MAX_FAILS=5;const GSZ_WORKER_LOCK_MIN=15;

function gszWorkerAccount(PDO $pdo,int $worker):?array {$q=$pdo->prepare('SELECT a.*,w.name,w.active worker_active FROM worker_accounts a JOIN operations_workers w ON w.id=a.worker_id WHERE a.worker_id=?');$q->execute([$worker]);return $q->fetch(PDO::FETCH_ASSOC)?:null;}
/** Zugang anlegen/ändern. Passwort leer = unverändert (bei neuem Zugang Pflicht). */
function gszWorkerAccountSave(PDO $pdo,int $admin,int $worker,array $in):void {
 $q=$pdo->prepare('SELECT id FROM operations_workers WHERE id=?');$q->execute([$worker]);if(!$q->fetchColumn())throw new RuntimeException('Mitarbeiter nicht gefunden.');
 $login=mb_strtolower(trim((string)($in['login']??'')));if(!preg_match('/^[a-z0-9._-]{3,60}$/D',$login))throw new RuntimeException('Benutzername: 3–60 Zeichen, nur a–z, 0–9, Punkt, Strich.');
 $pw=(string)($in['password']??'');$old=gszWorkerAccount($pdo,$worker);$active=!empty($in['active'])?1:0;
 if($pw===''&&!$old)throw new RuntimeException('Bitte ein Passwort für den neuen Zugang vergeben.');
 if($pw!==''&&(mb_strlen($pw)<10||mb_strlen($pw)>200))throw new RuntimeException('Passwort: mindestens 10 Zeichen.');
 $dup=$pdo->prepare('SELECT worker_id FROM worker_accounts WHERE login=? AND worker_id<>?');$dup->execute([$login,$worker]);if($dup->fetchColumn())throw new RuntimeException('Dieser Benutzername ist schon vergeben.');
 if($old)$pdo->prepare('UPDATE worker_accounts SET login=?,active=?,failed_logins=IF(?<>\'\',0,failed_logins),locked_until=IF(?<>\'\',NULL,locked_until)'.($pw!==''?',password_hash=?':'').' WHERE worker_id=?')->execute(array_merge([$login,$active,$pw,$pw],$pw!==''?[password_hash($pw,PASSWORD_DEFAULT)]:[],[$worker]));
 else $pdo->prepare('INSERT INTO worker_accounts(worker_id,login,password_hash,active) VALUES(?,?,?,?)')->execute([$worker,$login,password_hash($pw,PASSWORD_DEFAULT),$active]);
 gszAudit($pdo,$admin,'worker_account_saved',['worker'=>$worker,'active'=>$active,'password_changed'=>$pw!=='']);
}
/** Anmeldung. Nach 5 Fehlversuchen 15 Minuten gesperrt. Gleiche Antwortzeit für unbekannte Namen. */
function gszWorkerLogin(PDO $pdo,string $login,string $password,?int $now=null):?int {
 $now??=time();$login=mb_strtolower(trim($login));$q=$pdo->prepare('SELECT a.*,w.active worker_active FROM worker_accounts a JOIN operations_workers w ON w.id=a.worker_id WHERE a.login=?');$q->execute([$login]);$a=$q->fetch(PDO::FETCH_ASSOC);
 if(!$a){static $dummy=null;$dummy??=password_hash('gsz-dummy',PASSWORD_DEFAULT);password_verify($password,$dummy);return null;}
 if($a['locked_until']&&strtotime($a['locked_until'].' UTC')>$now)throw new RuntimeException('Zu viele Versuche. Bitte in '.GSZ_WORKER_LOCK_MIN.' Minuten erneut versuchen.');
 if(!password_verify($password,(string)$a['password_hash'])){$f=(int)$a['failed_logins']+1;$pdo->prepare('UPDATE worker_accounts SET failed_logins=?,locked_until=? WHERE worker_id=?')->execute([$f>=GSZ_WORKER_MAX_FAILS?0:$f,$f>=GSZ_WORKER_MAX_FAILS?gmdate('Y-m-d H:i:s',$now+GSZ_WORKER_LOCK_MIN*60):null,$a['worker_id']]);return null;}
 if(!(int)$a['active']||!(int)$a['worker_active'])return null;
 $pdo->prepare('UPDATE worker_accounts SET failed_logins=0,locked_until=NULL,last_login_at=UTC_TIMESTAMP() WHERE worker_id=?')->execute([$a['worker_id']]);
 if(password_needs_rehash((string)$a['password_hash'],PASSWORD_DEFAULT))$pdo->prepare('UPDATE worker_accounts SET password_hash=? WHERE worker_id=?')->execute([password_hash($password,PASSWORD_DEFAULT),$a['worker_id']]);
 gszAudit($pdo,null,'worker_login',['worker'=>(int)$a['worker_id']]);return (int)$a['worker_id'];
}
/** Gilt der Zugang (noch)? Wird bei jedem Aufruf geprüft, damit Abschalten sofort wirkt. */
function gszWorkerActive(PDO $pdo,int $worker):bool {$a=gszWorkerAccount($pdo,$worker);return $a&&(int)$a['active']&&(int)$a['worker_active'];}

/** Eigene Aufgaben: heute fällig plus offen gebliebene der letzten 7 Tage; erledigte von heute am Ende. */
function gszWorkerTasks(PDO $pdo,int $worker,string $day):array {
 $q=$pdo->prepare('SELECT t.id,t.title,t.notes,t.due_date,t.start_time,t.done,t.duration_minutes,p.id property_id,p.name property_name,p.address,p.notes property_notes,
  (SELECT w.id FROM work_logs w WHERE w.task_id=t.id AND w.worker_id=? ORDER BY w.id DESC LIMIT 1) log_id
  FROM tasks t JOIN properties p ON p.id=t.property_id WHERE t.assigned_worker_id=? AND ((t.done=0 AND t.due_date BETWEEN ? AND ?) OR (t.done=1 AND t.due_date=?))
  ORDER BY t.done,t.due_date,(t.sort_order=0),t.sort_order,t.planned_order,t.start_time IS NULL,t.start_time,t.id LIMIT 60');
 $q->execute([$worker,$worker,(new DateTimeImmutable($day))->modify('-7 days')->format('Y-m-d'),$day,$day]);$rows=$q->fetchAll(PDO::FETCH_ASSOC);
 foreach($rows as &$r){$r['log']=$r['log_id']?gszWorkerLog($pdo,$worker,(int)$r['log_id']):null;}return $rows;
}
function gszWorkerLog(PDO $pdo,int $worker,int $log):?array {
 $q=$pdo->prepare('SELECT w.*,t.assigned_worker_id FROM work_logs w LEFT JOIN tasks t ON t.id=w.task_id WHERE w.id=? AND w.worker_id=?');$q->execute([$log,$worker]);$r=$q->fetch(PDO::FETCH_ASSOC);if(!$r)return null;
 $p=$pdo->prepare('SELECT id,label FROM work_log_photos WHERE work_log_id=? ORDER BY id');$p->execute([$log]);$r['photos']=$p->fetchAll(PDO::FETCH_ASSOC);return $r;
}
function gszWorkerTask(PDO $pdo,int $worker,int $task):array {$q=$pdo->prepare('SELECT * FROM tasks WHERE id=? AND assigned_worker_id=?');$q->execute([$task,$worker]);$t=$q->fetch(PDO::FETCH_ASSOC);if(!$t)throw new RuntimeException('Diese Aufgabe ist dir nicht zugeteilt.');return $t;}
/** „Start“: legt den Arbeitsnachweis mit Startzeit an (einmal je Aufgabe und Helfer). */
function gszWorkerStart(PDO $pdo,int $worker,int $task,?string $today=null):int {
 $t=gszWorkerTask($pdo,$worker,$task);if((int)$t['done'])throw new RuntimeException('Diese Aufgabe ist schon erledigt.');
 $q=$pdo->prepare('SELECT id FROM work_logs WHERE task_id=? AND worker_id=? AND ended_at IS NULL ORDER BY id DESC LIMIT 1');$q->execute([$task,$worker]);if($id=$q->fetchColumn())return (int)$id;
 $today??=(new DateTimeImmutable('today',new DateTimeZone('Europe/Berlin')))->format('Y-m-d');
 $pdo->prepare('INSERT INTO work_logs(property_id,task_id,performed_on,description,duration_minutes,worker_id,started_at) VALUES(?,?,?,?,0,?,UTC_TIMESTAMP())')->execute([$t['property_id'],$task,$today,mb_substr((string)$t['title'],0,500),$worker]);
 $id=(int)$pdo->lastInsertId();gszAudit($pdo,null,'worker_started',['worker'=>$worker,'task'=>$task,'log'=>$id]);return $id;
}
/** Foto zum eigenen Nachweis (vorher/nachher), ohne Metadaten gespeichert. Max. 12 je Nachweis. */
function gszWorkerPhoto(PDO $pdo,int $worker,int $log,string $label,array $file):void {
 if(!in_array($label,['before','after'],true))throw new RuntimeException('Bitte „vorher“ oder „nachher“ wählen.');$l=gszWorkerLog($pdo,$worker,$log);if(!$l)throw new RuntimeException('Nachweis nicht gefunden.');
 if(count($l['photos'])>=12)throw new RuntimeException('Maximal 12 Fotos je Aufgabe.');
 require_once __DIR__.'/photo_storage.php';[$name,$mime]=gszStoreCleanPhoto($file,photoStorageDir());$path=photoStorageDir().'/'.$name;
 try{$pdo->prepare('INSERT INTO work_log_photos(work_log_id,stored_name,original_name,mime_type,size_bytes,label) VALUES(?,?,?,?,?,?)')->execute([$log,$name,$label==='before'?'vorher.jpg':'nachher.jpg',$mime,(int)filesize($path),$label]);}
 catch(Throwable $e){@unlink($path);throw $e;}
}
/** „Fertig“: Endzeit, Dauer, Notiz; die Aufgabe wird erledigt (regelmäßige Arbeiten planen den nächsten Termin wie beim Abhaken). */
function gszWorkerFinish(PDO $pdo,int $worker,int $log,string $note=''):void {
 $l=gszWorkerLog($pdo,$worker,$log);if(!$l)throw new RuntimeException('Nachweis nicht gefunden.');if($l['ended_at'])return;
 $note=mb_substr(trim(preg_replace('/[\x00-\x08\x0B\x0C\x0E-\x1F\x7F]/u','',$note)??''),0,2000);
 $pdo->beginTransaction();try{
  $pdo->prepare('UPDATE work_logs SET ended_at=UTC_TIMESTAMP(),duration_minutes=LEAST(1440,GREATEST(1,ROUND(TIMESTAMPDIFF(SECOND,started_at,UTC_TIMESTAMP())/60))),notes=IF(?=\'\',notes,?) WHERE id=? AND ended_at IS NULL')->execute([$note,$note,$log]);
  if($l['task_id']){$t=opsLock($pdo,'tasks',(int)$l['task_id']);if((int)$t['assigned_worker_id']===$worker&&!(int)$t['done']){
   $pdo->prepare('UPDATE tasks SET done=1,completed_at=UTC_TIMESTAMP(),row_version=row_version+1 WHERE id=?')->execute([$t['id']]);
   if(gszV1Ready($pdo)){$day=(new DateTimeImmutable('today',new DateTimeZone('Europe/Berlin')))->format('Y-m-d');$pdo->prepare('UPDATE tasks SET completed_on=? WHERE id=?')->execute([$day,$t['id']]);gszCompletionSchedule($pdo,$t,$day,(int)$t['row_version']+1);}}}
  gszAudit($pdo,null,'worker_finished',['worker'=>$worker,'log'=>$log,'task'=>$l['task_id']]);$pdo->commit();
 }catch(Throwable $e){if($pdo->inTransaction())$pdo->rollBack();throw $e;}
}
/** Übersicht für den Inhaber: Zeiten der Helfer im Monat. */
function gszWorkerMonth(PDO $pdo,string $month):array {
 $q=$pdo->prepare('SELECT w.worker_id,o.name,COUNT(*) jobs,SUM(w.duration_minutes) minutes FROM work_logs w JOIN operations_workers o ON o.id=w.worker_id WHERE w.performed_on BETWEEN ? AND LAST_DAY(?) AND w.ended_at IS NOT NULL GROUP BY w.worker_id,o.name ORDER BY o.name');$q->execute([$month.'-01',$month.'-01']);return $q->fetchAll(PDO::FETCH_ASSOC);
}
