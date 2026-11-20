<?php
declare(strict_types=1);
require_once __DIR__.'/waste_parser.php';
function wasteAutoReady(PDO $pdo):bool {return (bool)$pdo->query("SELECT COUNT(*) FROM app_migrations WHERE version='360_waste_connections'")->fetchColumn();}
function wasteSource(PDO $pdo,int $id):array {$s=$pdo->prepare('SELECT s.*,p.address current_address,p.seed_key FROM waste_sources s JOIN properties p ON p.id=s.property_id WHERE s.id=?');$s->execute([$id]);$r=$s->fetch();if(!$r)throw new RuntimeException('Quelle nicht gefunden.');return $r;}
function wasteSnapshot(PDO $pdo):string {
 $data=[];foreach(['properties','waste_sources','waste_events','waste_origins','waste_task_links','tasks','recurring_jobs'] as $t){$rows=$pdo->query('SELECT * FROM '.$t.' ORDER BY 1,2,3 LIMIT 5001')->fetchAll();if(count($rows)>5000)throw new RuntimeException('Importgrenze erreicht.');$data[$t]=$rows;}if(wasteAutoReady($pdo))$data['waste_connections']=$pdo->query('SELECT property_id,enabled,address_hash,source_id,bins_json,out_time,in_time FROM waste_connections ORDER BY property_id LIMIT 5001')->fetchAll();return hash('sha256',json_encode($data,JSON_THROW_ON_ERROR));
}
function wasteHistory(PDO $pdo,int $admin,string $type,array $detail):void {$pdo->prepare('INSERT INTO waste_history(admin_id,event_type,detail) VALUES(?,?,?)')->execute([$admin,$type,json_encode($detail,JSON_UNESCAPED_UNICODE|JSON_THROW_ON_ERROR)]);}
function wastePreview(PDO $pdo,int $sourceId,array $rows,array $mapping,?array $coverage=null):array {
 $source=wasteSource($pdo,$sourceId);$out=[];$conflicts=[];$rules=[];$outOffset=-1;$inOffset=0;if((int)$pdo->query("SELECT COUNT(*) FROM app_migrations WHERE version='350_final'")->fetchColumn()){$q=$pdo->prepare("SELECT id,title,next_due_date,rule_source FROM recurring_jobs WHERE property_id=? AND rule_source IN ('routine_unverified','special_weekly') AND frequency='weekly' AND next_due_date IS NOT NULL");$q->execute([$source['property_id']]);$rules=$q->fetchAll();$q=$pdo->prepare('SELECT waste_out_offset,waste_in_offset FROM properties WHERE id=?');$q->execute([$source['property_id']]);$offsets=$q->fetch();$outOffset=(int)$offsets['waste_out_offset'];$inOffset=(int)$offsets['waste_in_offset'];}
 $s=$pdo->prepare('SELECT e.*,o.external_id FROM waste_events e JOIN waste_origins o ON o.event_id=e.id WHERE o.source_id=?');$s->execute([$sourceId]);$old=$s->fetchAll();$uids=[];
 foreach($rows as $r){$fraction=(string)($mapping[$r['label']]??'');if($fraction!==''&&!in_array($fraction,WASTE_FRACTIONS,true))throw new InvalidArgumentException('Fraktionszuordnung ungültig.');$r['fraction']=$fraction;$out[]=$r;$uids[$r['uid']]=$r;
  foreach($rules as $rule){if($rule['rule_source']==='special_weekly'&&$fraction!=='Gelb')continue;if($fraction==='Gelb'&&$source['seed_key']==='fontaenen29'&&$rule['rule_source']==='routine_unverified')continue;$isOut=str_contains(mb_strtolower($rule['title']),'raus');$actual=planDate($r['date'],$isOut?$outOffset:$inOffset);if((new DateTimeImmutable($actual))->format('N')!==(new DateTimeImmutable($rule['next_due_date']))->format('N'))$conflicts[]='Abweichung zur bisherigen Objektregel '.$rule['title'].' (#'.$rule['id'].') am '.$actual.'. Quelle prüfen; alte Regel bleibt erhalten.';}
  if($r['address']!==''&&mb_strtolower(trim($r['address']))!==mb_strtolower(trim($source['address'])))$conflicts[]='Abholadresse abweichend für '.$r['label'].' am '.$r['date'];
  if($r['date']>$source['valid_until'])$conflicts[]='Termin außerhalb der Quellengültigkeit: '.$r['date'];
  foreach($old as $o)if($o['external_id']===$r['uid']&&($o['collection_date']!==$r['date']||$o['fraction']!==($fraction?:'Unklar-'.substr(hash('sha256',$r['label']),0,10))))$conflicts[]='Geänderte Quellen-ID '.$r['uid'].': bisher '.$o['collection_date'].' / '.$o['fraction'].'; alte Aufgaben bleiben erhalten.';
 }
 if($source['address']!==$source['current_address'])$conflicts[]='Objektadresse seit Quelleneinrichtung geändert. Quelle neu anlegen bzw. Stammdaten prüfen.';
 if($rows){$min=min(array_column($rows,'date'));$max=max(array_column($rows,'date'));foreach($old as $o)if((!$coverage||($o['collection_date']>=$coverage[0]&&$o['collection_date']<=$coverage[1]))&&!isset($uids[$o['external_id']]))$conflicts[]='Vorheriger Termin fehlt im neuen Dateiausschnitt: #'.$o['id'].'; keine Löschung.';}
 return ['source'=>$source,'rows'=>$out,'conflicts'=>array_values(array_unique($conflicts))];
}
function wasteSavePreview(PDO $pdo,int $admin,int $sourceId,string $raw,string $format,array $csvMap,string $delimiter,array $mapping):string {
 $rows=$format==='ics'?wasteParseIcs($raw):wasteParseCsv($raw,$csvMap,$delimiter);$preview=wastePreview($pdo,$sourceId,$rows,$mapping);$token=bin2hex(random_bytes(32));
 $pdo->prepare('INSERT INTO waste_imports(token,admin_id,source_id,file_hash,fingerprint,payload) VALUES(?,?,?,?,?,?)')->execute([$token,$admin,$sourceId,hash('sha256',$raw),wasteSnapshot($pdo),json_encode($preview,JSON_THROW_ON_ERROR)]);return $token;
}
/** Always called inside the import/review transaction. Reuses special weekly identity. */
function wasteCreateTasks(PDO $pdo,array $event,bool $bundle=false):void {
 if($event['status']!=='geprueft')return;
 if(wasteAutoReady($pdo)){$q=$pdo->prepare('SELECT c.*,p.address current_address FROM waste_connections c JOIN properties p ON p.id=c.property_id JOIN waste_sources source ON source.property_id=c.property_id AND source.auto_managed=1 JOIN waste_origins o ON o.source_id=source.id WHERE o.event_id=?');$q->execute([$event['id']]);foreach($q->fetchAll() as $c){$a=json_decode($c['address_json'],true);if(!$c['enabled']||!hash_equals($c['address_hash'],hash('sha256',$c['current_address']))||!in_array($event['fraction'],array_column(json_decode($c['bins_json'],true),'canonical'),true))return;}}
 $out=-1;$in=0;if(gszV1Ready($pdo)){$policy=$pdo->prepare('SELECT waste_out_offset,waste_in_offset FROM properties WHERE id=?');$policy->execute([$event['property_id']]);$days=$policy->fetch();$out=(int)$days['waste_out_offset'];$in=(int)$days['waste_in_offset'];}
 foreach(['raus'=>planDate($event['collection_date'],$out),'rein'=>planDate($event['collection_date'],$in)] as $direction=>$date){
  $s=$pdo->prepare('SELECT task_id FROM waste_task_links WHERE event_id=? AND direction=?');$s->execute([$event['id'],$direction]);if($s->fetchColumn())continue;
  $s=$pdo->prepare("SELECT r.* FROM recurring_jobs r JOIN properties p ON p.id=r.property_id WHERE r.property_id=? AND p.seed_key='fontaenen29' AND r.rule_source='special_weekly' AND r.active=1 AND r.title=? ORDER BY r.id");$s->execute([$event['property_id'],'Gelbe Tonne '.($direction==='raus'?'rausstellen':'reinstellen')]);$special=$s->fetch();
  $weekday=(int)(new DateTimeImmutable($date))->format('N');
  if($event['fraction']==='Gelb'&&$special&&$weekday===($direction==='raus'?3:4)){
   zgsInsertOccurrence($pdo,$special,$date);$s=$pdo->prepare('SELECT id FROM tasks WHERE recurring_job_id=? AND scheduled_for=?');$s->execute([$special['id'],$date]);$task=(int)$s->fetchColumn();
  }else{
   $key=$bundle?'waste:group:'.$event['property_id'].':'.$date.':'.$direction:'waste:'.$event['id'].':'.$direction;
   $s=$pdo->prepare('SELECT * FROM tasks WHERE waste_key=? FOR UPDATE');$s->execute([$key]);$existing=$s->fetch();
   if($existing){
    // Never append a new fraction to a completed/edited existing visit.
    if($existing['done']||$existing['plan_locked']||$existing['start_time']||(int)$existing['sort_order']>0||$existing['due_date']!==$date)throw new RuntimeException('Bündelaufgabe wurde erledigt/manuell geändert. Einzeltermine verwenden.');
    $task=(int)$existing['id'];$title=$existing['title'];if(!str_contains($title,$event['fraction']))$pdo->prepare('UPDATE tasks SET title=? WHERE id=?')->execute([mb_substr($title.' + '.$event['fraction'],0,190),$task]);
   }else{
    $pdo->prepare('INSERT INTO tasks(property_id,title,due_date,duration_minutes,waste_key,window_start,window_end) VALUES(?,?,?,15,?,?,?)')->execute([$event['property_id'],'Tonnen '.$direction.'stellen: '.$event['fraction'],$date,$key,$date,$date]);$task=(int)$pdo->lastInsertId();
   }
  }
  $pdo->prepare('INSERT INTO waste_task_links(event_id,direction,task_id) VALUES(?,?,?)')->execute([$event['id'],$direction,$task]);
  if(wasteAutoReady($pdo)){$timing=$pdo->prepare('SELECT c.out_time,c.in_time FROM waste_connections c JOIN waste_sources source ON source.property_id=c.property_id AND source.auto_managed=1 JOIN waste_origins o ON o.source_id=source.id WHERE o.event_id=? AND c.enabled=1 LIMIT 1');$timing->execute([$event['id']]);$times=$timing->fetch();$time=$times[$direction==='raus'?'out_time':'in_time']??null;if($time)$pdo->prepare('UPDATE tasks SET start_time=?,plan_locked=1 WHERE id=? AND done=0 AND plan_locked=0 AND start_time IS NULL AND recurring_job_id IS NULL')->execute([$time,$task]);}
 }
}
function wasteApply(PDO $pdo,int $admin,string $token,bool $checked,bool $acknowledge,bool $bundle,bool $ackRoutineRules=false):void {
 if((int)$pdo->query("SELECT GET_LOCK('gsz_waste_import',5)")->fetchColumn()!==1)throw new RuntimeException('Import läuft bereits.');
 $pdo->beginTransaction();try{
  $s=$pdo->prepare('SELECT * FROM waste_imports WHERE token=? AND admin_id=? FOR UPDATE');$s->execute([$token,$admin]);$import=$s->fetch();if(!$import)throw new RuntimeException('Import nicht gefunden.');if($import['applied_at']){$pdo->commit();return;}
  if(strtotime($import['created_at'].' UTC')<time()-7200)throw new RuntimeException('Vorschau abgelaufen.');
  foreach(['properties','waste_sources','waste_events','waste_origins','waste_task_links','tasks','recurring_jobs'] as $t)$pdo->query('SELECT * FROM '.$t.' FOR UPDATE')->fetchAll();
  if(!hash_equals($import['fingerprint'],wasteSnapshot($pdo)))throw new RuntimeException('Bestand verändert. Vorschau neu erzeugen.');$p=json_decode($import['payload'],true,512,JSON_THROW_ON_ERROR);$source=wasteSource($pdo,(int)$import['source_id']);
  $blocking=$p['conflicts'];if($ackRoutineRules&&$acknowledge)$blocking=array_values(array_filter($blocking,fn($v)=>!str_starts_with($v,'Abweichung zur bisherigen Objektregel ')));
  if($blocking&&!$acknowledge)throw new RuntimeException('Konflikte prüfen und Erhalt alter Daten ausdrücklich bestätigen.');
  if($checked&&($source['valid_until']<date('Y-m-d')||$source['address']!==$source['current_address']||$blocking))throw new RuntimeException('Konflikt/veraltete Quelle: nur ungeprüft importieren, danach Einzeltermine prüfen.');
  foreach($p['rows'] as $r){$fraction=$r['fraction']?:'Unklar-'.substr(hash('sha256',$r['label']),0,10);$status=$r['fraction']===''?'unklar':($checked?'geprueft':'importiert');
   $hash=hash('sha256',json_encode([$source['property_id'],$r['date'],$fraction,$source['address']]));
   $s=$pdo->prepare('SELECT * FROM waste_events WHERE property_id=? AND collection_date=? AND fraction=?');$s->execute([$source['property_id'],$r['date'],$fraction]);$event=$s->fetch();
   if(!$event){$pdo->prepare('INSERT INTO waste_events(property_id,collection_date,fraction,address,status,event_hash) VALUES(?,?,?,?,?,?)')->execute([$source['property_id'],$r['date'],$fraction,$source['address'],$status,$hash]);$id=(int)$pdo->lastInsertId();}
   else{$id=(int)$event['id'];if($event['status']==='importiert'&&$checked)$pdo->prepare("UPDATE waste_events SET status='geprueft' WHERE id=?")->execute([$id]);}
   $pdo->prepare('INSERT INTO waste_origins(event_id,source_id,external_id,original_title,original_start,record_hash) VALUES(?,?,?,?,?,?) ON DUPLICATE KEY UPDATE event_id=VALUES(event_id)')->execute([$id,$source['id'],$r['uid'],$r['label'],$r['original_start'],hash('sha256',json_encode($r))]);
   $s=$pdo->prepare('SELECT * FROM waste_events WHERE id=?');$s->execute([$id]);wasteCreateTasks($pdo,$s->fetch(),$bundle);
  }
  $pdo->prepare('UPDATE waste_sources SET last_import_at=NOW(),checked_at=IF(?,NOW(),checked_at),last_error=\'\' WHERE id=?')->execute([$checked?1:0,$source['id']]);
  $pdo->prepare('UPDATE waste_imports SET applied_at=NOW() WHERE token=?')->execute([$token]);wasteHistory($pdo,$admin,'import',['source'=>$source['id'],'hash'=>$import['file_hash'],'checked'=>$checked,'bundle'=>$bundle,'conflicts'=>$p['conflicts']]);$pdo->commit();
 }catch(Throwable $e){if($pdo->inTransaction())$pdo->rollBack();throw $e;}finally{$pdo->query("SELECT RELEASE_LOCK('gsz_waste_import')");}
}
function wasteReview(PDO $pdo,int $admin,int $id,string $status):void {
 if(!in_array($status,['geprueft','veraltet'],true))throw new InvalidArgumentException('Prüfstatus ungültig.');
 $pdo->beginTransaction();try{$s=$pdo->prepare('SELECT * FROM waste_events WHERE id=? FOR UPDATE');$s->execute([$id]);$event=$s->fetch();if(!$event)throw new RuntimeException('Termin nicht gefunden.');
  if($status==='geprueft'){
   if(!in_array($event['fraction'],WASTE_FRACTIONS,true))throw new RuntimeException('Unklare Fraktion zuerst in korrigierter Datei zuordnen.');
   $s=$pdo->prepare('SELECT s.*,p.address current_address FROM waste_sources s JOIN waste_origins o ON o.source_id=s.id JOIN properties p ON p.id=s.property_id WHERE o.event_id=?');$s->execute([$id]);$valid=false;foreach($s->fetchAll() as $src)if($src['valid_until']>=max(date('Y-m-d'),$event['collection_date'])&&$src['address']===$src['current_address'])$valid=true;
   if(!$valid)throw new RuntimeException('Quelle/Adresse/Gültigkeit zuerst prüfen.');
  }
  $pdo->prepare('UPDATE waste_events SET status=? WHERE id=?')->execute([$status,$id]);$event['status']=$status;wasteCreateTasks($pdo,$event);wasteHistory($pdo,$admin,'review',['event'=>$id,'status'=>$status,'old_tasks_preserved'=>true]);$pdo->commit();
 }catch(Throwable $e){if($pdo->inTransaction())$pdo->rollBack();throw $e;}
}
