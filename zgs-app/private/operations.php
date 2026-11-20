<?php
declare(strict_types=1);
require_once __DIR__.'/planning_engine.php';
require_once __DIR__.'/platform.php';
function opsReady(PDO $pdo):bool {return (bool)$pdo->query("SELECT COUNT(*) FROM app_migrations WHERE version='270_operations'")->fetchColumn();}
function opsRequire(PDO $pdo):void {if(!opsReady($pdo))throw new RuntimeException('Betriebsverwaltung benötigt Migration v270.');}
function opsText(array $in,string $key,int $max,bool $required=false):string {$v=trim((string)($in[$key]??''));if(!mb_check_encoding($v,'UTF-8')||mb_strlen($v)>$max||($required&&$v==='')||str_contains($v,"\0"))throw new InvalidArgumentException('Eingabe '.$key.' ungültig.');return $v;}
function opsEmail(array $in,string $key='email'):string {$v=opsText($in,$key,190);if($v!==''&&!filter_var($v,FILTER_VALIDATE_EMAIL))throw new InvalidArgumentException('E-Mail-Adresse ungültig.');return $v;}
function opsVersion(array $in):?int {if(!array_key_exists('row_version',$in))return null;$v=filter_var($in['row_version'],FILTER_VALIDATE_INT);if($v===false||$v<0)throw new InvalidArgumentException('Versionsstand ungültig.');return $v;}
function opsLock(PDO $pdo,string $table,int $id,?int $version=null):array {
 if(!in_array($table,['customers','properties','tasks','planning_appointments','operations_workers'],true)||$id<1)throw new InvalidArgumentException('Datensatz ungültig.');
 $q=$pdo->prepare('SELECT * FROM '.$table.' WHERE id=? FOR UPDATE');$q->execute([$id]);$r=$q->fetch();if(!$r)throw new RuntimeException('Datensatz nicht gefunden.');if($version!==null&&(int)$r['row_version']!==$version)throw new RuntimeException('Datensatz inzwischen geändert. Neu laden; keine Änderung überschrieben.');return $r;
}
function opsTransaction(PDO $pdo,callable $work):mixed {opsRequire($pdo);$pdo->beginTransaction();try{$result=$work();$pdo->commit();return $result;}catch(Throwable $e){if($pdo->inTransaction())$pdo->rollBack();throw $e;}}
function opsEvent(PDO $pdo,int $admin,string $type,int $id,string $action,array $before,array $after):void {
 if($admin<1)throw new InvalidArgumentException('Inhaber-Anmeldung erforderlich.');$changes=[];foreach($after as $key=>$v)if(!array_key_exists($key,$before)||$before[$key]!==$v)$changes[$key]=['vorher'=>$before[$key]??null,'nachher'=>$v];
 $pdo->prepare('INSERT INTO operations_events(entity_type,entity_id,admin_id,action,changes_json) VALUES(?,?,?,?,?)')->execute([$type,$id,$admin,$action,json_encode($changes,JSON_UNESCAPED_UNICODE|JSON_THROW_ON_ERROR)]);
 gszAudit($pdo,$admin,'operations_'.$action,['entity'=>$type,'id'=>$id,'fields'=>array_keys($changes)]);
}
function opsCustomerData(array $in):array {return ['name'=>opsText($in,'name',190,true),'email'=>opsEmail($in),'phone'=>opsText($in,'phone',80),'notes'=>opsText($in,'notes',5000),'billing_address'=>opsText($in,'billing_address',2000)];}
function opsSaveCustomer(PDO $pdo,int $admin,array $in,int $id=0):int {
 $data=opsCustomerData($in);$version=opsVersion($in);return opsTransaction($pdo,function()use($pdo,$admin,$data,$id,$version){$old=$id?opsLock($pdo,'customers',$id,$version):[];if($id&&$old['archived_at']!==null)throw new RuntimeException('Archivierten Kunden erst wieder aktivieren.');
 if($id)$pdo->prepare('UPDATE customers SET name=?,email=?,phone=?,notes=?,billing_address=?,row_version=row_version+1 WHERE id=?')->execute([...array_values($data),$id]);
 else{$pdo->prepare('INSERT INTO customers(name,email,phone,notes,billing_address) VALUES(?,?,?,?,?)')->execute(array_values($data));$id=(int)$pdo->lastInsertId();}opsEvent($pdo,$admin,'customer',$id,$old?'updated':'created',$old,$data);return $id;});
}
function opsCustomerArchive(PDO $pdo,int $admin,int $id,bool $archive,?int $version):void {opsTransaction($pdo,function()use($pdo,$admin,$id,$archive,$version){$old=opsLock($pdo,'customers',$id,$version);if(($old['archived_at']!==null)===$archive)return;$pdo->prepare('UPDATE customers SET archived_at=?,row_version=row_version+1 WHERE id=?')->execute([$archive?gmdate('Y-m-d H:i:s'):null,$id]);opsEvent($pdo,$admin,'customer',$id,$archive?'archived':'reactivated',$old,['archived'=>$archive]);});}
function opsPropertyData(array $in):array {return ['name'=>opsText($in,'name',190,true),'address'=>opsText($in,'address',255),'contact_name'=>opsText($in,'contact_name',190),'contact_phone'=>opsText($in,'contact_phone',80),'contact_email'=>opsEmail($in,'contact_email'),'notes'=>opsText($in,'notes',5000),'customer_id'=>($v=filter_var($in['customer_id']??0,FILTER_VALIDATE_INT))!==false&&$v>=0?($v?:null):throw new InvalidArgumentException('Kundenzuordnung ungültig.')];}
function opsSaveProperty(PDO $pdo,int $admin,int $id,array $in):void {
 $data=opsPropertyData($in);$version=opsVersion($in);if(gszV1Ready($pdo))foreach(['waste_out_offset'=>[-3,0],'waste_in_offset'=>[0,3]] as $key=>[$lo,$hi])if(array_key_exists($key,$in)){$v=filter_var($in[$key],FILTER_VALIDATE_INT);if($v===false||$v<$lo||$v>$hi)throw new InvalidArgumentException('Tonnen-Tagesabstand ungültig.');$data[$key]=$v;}opsTransaction($pdo,function()use($pdo,$admin,$id,$data,$version){$old=opsLock($pdo,'properties',$id,$version);if($data['customer_id']){$c=opsLock($pdo,'customers',$data['customer_id']);if($c['archived_at']!==null&&(int)$old['customer_id']!==$data['customer_id'])throw new RuntimeException('Archivierten Kunden nicht neu zuordnen.');}
 $pdo->prepare('UPDATE properties SET '.implode(',',array_map(fn($key)=>$key.'=?',array_keys($data))).',row_version=row_version+1 WHERE id=?')->execute([...array_values($data),$id]);opsEvent($pdo,$admin,'property',$id,'updated',$old,$data);});
}
function opsSaveWorker(PDO $pdo,int $admin,array $in,int $id=0):int {
 $data=['name'=>opsText($in,'name',190,true),'kind'=>(string)($in['kind']??''),'email'=>opsEmail($in),'phone'=>opsText($in,'phone',80),'notes'=>opsText($in,'notes',5000),'active'=>empty($in['active'])?0:1];if(!in_array($data['kind'],['employee','subcontractor'],true))throw new InvalidArgumentException('Zuordnungstyp ungültig.');$version=opsVersion($in);
 return opsTransaction($pdo,function()use($pdo,$admin,$id,$data,$version){$old=$id?opsLock($pdo,'operations_workers',$id,$version):[];if($id)$pdo->prepare('UPDATE operations_workers SET name=?,kind=?,email=?,phone=?,notes=?,active=?,row_version=row_version+1 WHERE id=?')->execute([...array_values($data),$id]);else{$pdo->prepare('INSERT INTO operations_workers(name,kind,email,phone,notes,active) VALUES(?,?,?,?,?,?)')->execute(array_values($data));$id=(int)$pdo->lastInsertId();}opsEvent($pdo,$admin,'worker',$id,$old?'updated':'created',$old,$data);return $id;});
}
function opsSetDone(PDO $pdo,int $admin,int $id,bool $done,?int $version=null,?int $property=null):void {
 opsTransaction($pdo,function()use($pdo,$admin,$id,$done,$version,$property){$old=opsLock($pdo,'tasks',$id,$version);if($property!==null&&(int)$old['property_id']!==$property)throw new RuntimeException('Aufgabe gehört nicht zu diesem Objekt.');if((bool)$old['done']===$done)return;
 $at=$done?gmdate('Y-m-d H:i:s'):null;$pdo->prepare('UPDATE tasks SET done=?,completed_at=?,row_version=row_version+1 WHERE id=?')->execute([$done?1:0,$at,$id]);if(gszV1Ready($pdo)){$completed=$done?(new DateTimeImmutable('today',new DateTimeZone('Europe/Berlin')))->format('Y-m-d'):null;$pdo->prepare('UPDATE tasks SET completed_on=? WHERE id=?')->execute([$completed,$id]);if($done)gszCompletionSchedule($pdo,$old,$completed,(int)$old['row_version']+1);}opsEvent($pdo,$admin,'task',$id,$done?'completed':'reopened',$old,['done'=>$done?1:0,'completed_at'=>$at]);});
}
function opsTaskNote(PDO $pdo,int $admin,int $id,string $note,?int $version):void {$note=opsText(['note'=>$note],'note',5000);opsTransaction($pdo,function()use($pdo,$admin,$id,$note,$version){$old=opsLock($pdo,'tasks',$id,$version);$pdo->prepare('UPDATE tasks SET notes=?,row_version=row_version+1 WHERE id=?')->execute([$note,$id]);opsEvent($pdo,$admin,'task',$id,'note',$old,['notes'=>$note]);});}
function opsAssignTask(PDO $pdo,int $admin,int $id,int $worker,?int $version):void {
 if($worker<0)throw new InvalidArgumentException('Zuordnung ungültig.');opsTransaction($pdo,function()use($pdo,$admin,$id,$worker,$version){$old=opsLock($pdo,'tasks',$id,$version);if($worker){$w=opsLock($pdo,'operations_workers',$worker);if(!$w['active'])throw new RuntimeException('Zuordnung ist inaktiv.');}$pdo->prepare('UPDATE tasks SET assigned_worker_id=?,row_version=row_version+1 WHERE id=?')->execute([$worker?:null,$id]);opsEvent($pdo,$admin,'task',$id,'assigned',$old,['assigned_worker_id'=>$worker?:null]);});
}
function opsAppointmentData(array $in):array {
 $day=(string)($in['appointment_day']??'');$start=(string)($in['appointment_start']??'');$end=(string)($in['appointment_end']??'');if(!zgsValidDate($day)||planMinute($start)>=planMinute($end))throw new InvalidArgumentException('Termin: Datum und Ende nach Beginn erforderlich.');
 opsLocalAppointment($day,$start);opsLocalAppointment($day,$end);
 $minutes=filter_var($in['reminder_minutes']??30,FILTER_VALIDATE_INT);if($minutes===false||$minutes<0||$minutes>1440)throw new InvalidArgumentException('Erinnerung 0–1440 Minuten.');
 return ['day'=>$day,'start_time'=>substr($start,0,5).':00','end_time'=>substr($end,0,5).':00','title'=>opsText($in,'appointment_title',190,true),'address'=>opsText($in,'address',255),'notes'=>opsText($in,'appointment_notes',5000),'reminder_minutes'=>$minutes];
}
function opsSaveAppointment(PDO $pdo,int $admin,array $in,int $id=0):int {
 $data=opsAppointmentData($in);$version=opsVersion($in);return opsTransaction($pdo,function()use($pdo,$admin,$data,$id,$version){$old=$id?opsLock($pdo,'planning_appointments',$id,$version):[];if($old&&$old['cancelled_at']!==null)throw new RuntimeException('Stornierten Termin nicht überschreiben.');
 if($id)$pdo->prepare('UPDATE planning_appointments SET day=?,start_time=?,end_time=?,title=?,address=?,notes=?,reminder_minutes=?,row_version=row_version+1 WHERE id=?')->execute([...array_values($data),$id]);else{$pdo->prepare('INSERT INTO planning_appointments(day,start_time,end_time,title,address,notes,reminder_minutes) VALUES(?,?,?,?,?,?,?)')->execute(array_values($data));$id=(int)$pdo->lastInsertId();}opsEvent($pdo,$admin,'appointment',$id,$old?'updated':'created',$old,$data);return $id;});
}
function opsCancelAppointment(PDO $pdo,int $admin,int $id,?int $version):void {opsTransaction($pdo,function()use($pdo,$admin,$id,$version){$old=opsLock($pdo,'planning_appointments',$id,$version);if($old['cancelled_at']!==null)return;$pdo->prepare('UPDATE planning_appointments SET cancelled_at=UTC_TIMESTAMP(),row_version=row_version+1 WHERE id=?')->execute([$id]);opsEvent($pdo,$admin,'appointment',$id,'cancelled',$old,['cancelled'=>true]);});}
/** Local reminder only, never push/mail. Reject nonexistent or ambiguous DST times. */
function opsLocalAppointment(string $day,string $time):DateTimeImmutable {
 if(!zgsValidDate($day))throw new InvalidArgumentException('Datum ungültig.');$minute=planMinute($time);$wall=$day.' '.planTime($minute);$tz=new DateTimeZone('Europe/Berlin');$d=new DateTimeImmutable($wall,$tz);if($d->format('Y-m-d H:i')!==$wall)throw new InvalidArgumentException('Uhrzeit existiert wegen Zeitumstellung nicht.');
 // At fall-back, two UTC instants share a wall time. Demand a different time.
 foreach([-3600,3600] as $shift)if($d->setTimestamp($d->getTimestamp()+$shift)->format('Y-m-d H:i')===$wall)throw new InvalidArgumentException('Uhrzeit wegen Zeitumstellung mehrdeutig.');return $d;
}
function opsReminderState(array $a,DateTimeImmutable $now):string {
 if(!empty($a['cancelled_at']))return 'cancelled';$start=opsLocalAppointment($a['day'],$a['start_time']);$end=opsLocalAppointment($a['day'],$a['end_time']);$ts=$now->getTimestamp();if($ts>=$end->getTimestamp())return 'past';if($ts>=$start->getTimestamp())return 'ongoing';return $ts>=$start->getTimestamp()-(int)($a['reminder_minutes']??30)*60?'due':'upcoming';
}
function opsHistory(PDO $pdo,string $type,int $id):array {if(!in_array($type,['customer','property','task','appointment','worker'],true))throw new InvalidArgumentException('Historientyp ungültig.');$q=$pdo->prepare('SELECT id,action,changes_json,created_at FROM operations_events WHERE entity_type=? AND entity_id=? ORDER BY id DESC LIMIT 30');$q->execute([$type,$id]);return $q->fetchAll();}
function opsSaveTask(PDO $pdo,int $admin,array $args,int $property,int $id,bool $unlock,?int $version):int {
 return opsTransaction($pdo,function()use($pdo,$admin,$args,$property,$id,$unlock,$version){$old=$id?opsLock($pdo,'tasks',$id,$version):[];if($property)opsLock($pdo,'properties',$property);
 $keys=['title','due_date','notes','duration_minutes','start_time','priority','sort_order','window_start','window_end'];$data=array_combine($keys,$args);$data['property_id']=$property?:null;$data['plan_locked']=$unlock?0:1;
 if($id)$pdo->prepare('UPDATE tasks SET title=?,due_date=?,notes=?,duration_minutes=?,start_time=?,priority=?,sort_order=?,window_start=?,window_end=?,property_id=?,plan_locked=?,row_version=row_version+1 WHERE id=?')->execute([...array_values($data),$id]);
 else{$pdo->prepare('INSERT INTO tasks(title,due_date,notes,duration_minutes,start_time,priority,sort_order,window_start,window_end,property_id,plan_locked) VALUES(?,?,?,?,?,?,?,?,?,?,?)')->execute(array_values($data));$id=(int)$pdo->lastInsertId();}opsEvent($pdo,$admin,'task',$id,$old?'updated':'created',$old,$data);return $id;});
}
