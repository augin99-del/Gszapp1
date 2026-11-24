<?php
declare(strict_types=1);
/**
 * Bauprojekte (Schema 410), z. B. „Sanierung 4-Familienhaus“: Gewerke, Handwerker, Termine (→ Woche), Kosten,
 * Mängel/To-dos je Wohnung, Fotos/Dokumente, Gewährleistung. Kosten sind eine Übersicht – Buchhaltung bleibt in Lexware.
 */
require_once __DIR__.'/platform.php';require_once __DIR__.'/money.php';
const GSZ_PROJECT_STATUS=['planung'=>'In Planung','laeuft'=>'Läuft','fertig'=>'Fertig'];
const GSZ_TRADE_STATUS=['offen'=>'Offen','geplant'=>'Geplant','laeuft'=>'Läuft','fertig'=>'Fertig'];
const GSZ_TRADE_TEMPLATE=['Entkernung / Abbruch','Rohbau / Maurer','Dach','Fenster & Türen','Elektro','Sanitär','Heizung','Trockenbau','Estrich','Fliesen','Maler','Bodenbeläge','Fassade','Außenanlagen','Endreinigung'];
const GSZ_FILE_PHASES=[''=>'—','vorher'=>'Vorher','waehrend'=>'Während','nachher'=>'Nachher'];

function gszProjectsReady(PDO $pdo):bool {try{return (bool)$pdo->query("SELECT COUNT(*) FROM app_migrations WHERE version='410_projekte'")->fetchColumn();}catch(Throwable $e){return false;}}
function gszPText(mixed $v,int $max):string {return mb_substr(trim(preg_replace('/[\x00-\x08\x0B\x0C\x0E-\x1F\x7F]/u','',(string)$v)??''),0,$max);}
function gszPDate(mixed $v,bool $required=false):?string {
 $v=trim((string)$v);if($v===''){if($required)throw new RuntimeException('Bitte ein Datum angeben.');return null;}
 if(!preg_match('/^\d{4}-\d{2}-\d{2}$/D',$v)||!checkdate((int)substr($v,5,2),(int)substr($v,8,2),(int)substr($v,0,4)))throw new RuntimeException('Datum ungültig.');return $v;
}
function gszPTime(mixed $v):string {$v=trim((string)$v);if(!preg_match('/^([01]\d|2[0-3]):[0-5]\d$/D',$v))throw new RuntimeException('Uhrzeit ungültig.');return $v.':00';}
/** Betrag wie „1.234,56“, „1234,5“ oder „1234“. Leer = null. */
function gszPMoney(mixed $v,bool $required=false):?int {
 $v=str_replace([' ','€'],'',trim((string)$v));if($v===''){if($required)throw new RuntimeException('Bitte einen Betrag angeben.');return null;}
 if(preg_match('/^\d+\.\d{1,2}$/D',$v))$v=str_replace('.',',',$v);
 try{$c=financeCents($v,'de');}catch(Throwable $e){throw new RuntimeException('Betrag bitte so angeben: 1.234,56');}
 if($c<0)throw new RuntimeException('Betrag darf nicht negativ sein.');return $c;
}
function gszPId(PDO $pdo,string $table,mixed $id,?int $projectId=null):?int {
 $id=(int)$id;if($id<1)return null;$sql="SELECT id FROM $table WHERE id=?".($projectId!==null?' AND project_id=?':'');$q=$pdo->prepare($sql);$q->execute($projectId!==null?[$id,$projectId]:[$id]);
 if(!$q->fetchColumn())throw new RuntimeException('Auswahl nicht gefunden.');return $id;
}

/* ---------- Projekt ---------- */
function gszProjectSave(PDO $pdo,int $admin,array $in,?int $id=null):int {
 $title=gszPText($in['title']??'',190);if($title==='')throw new RuntimeException('Bitte einen Projektnamen angeben.');
 $status=(string)($in['status']??'planung');if(!isset(GSZ_PROJECT_STATUS[$status]))throw new RuntimeException('Status ungültig.');
 $building=gszPId($pdo,'estate_buildings',$in['building_id']??0);$start=gszPDate($in['start_date']??'');$target=gszPDate($in['target_date']??'');
 if($start&&$target&&$target<$start)throw new RuntimeException('Fertigstellung liegt vor dem Start.');
 $vals=[$title,$building,$status,$start,$target,gszPMoney($in['budget']??''),gszPText($in['notes']??'',5000)];
 if($id===null){$pdo->prepare('INSERT INTO projects(title,building_id,status,start_date,target_date,budget_cents,notes) VALUES(?,?,?,?,?,?,?)')->execute($vals);$id=(int)$pdo->lastInsertId();gszAudit($pdo,$admin,'project_created',['id'=>$id]);}
 else{$q=$pdo->prepare('UPDATE projects SET title=?,building_id=?,status=?,start_date=?,target_date=?,budget_cents=?,notes=?,row_version=row_version+1 WHERE id=?');$q->execute([...$vals,$id]);gszAudit($pdo,$admin,'project_updated',['id'=>$id]);}
 return $id;
}
function gszProjectGet(PDO $pdo,int $id):?array {$q=$pdo->prepare('SELECT p.*,b.name building_name,b.address building_address FROM projects p LEFT JOIN estate_buildings b ON b.id=p.building_id WHERE p.id=?');$q->execute([$id]);$r=$q->fetch(PDO::FETCH_ASSOC);return $r?:null;}
function gszProjectsList(PDO $pdo):array {
 $rows=$pdo->query("SELECT p.*,b.name building_name FROM projects p LEFT JOIN estate_buildings b ON b.id=p.building_id ORDER BY FIELD(p.status,'laeuft','planung','fertig'),p.created_at DESC")->fetchAll(PDO::FETCH_ASSOC);
 foreach($rows as &$r)$r['summary']=gszProjectSummary($pdo,(int)$r['id']);unset($r);return $rows;
}
function gszProjectDelete(PDO $pdo,int $admin,int $id):void {
 $files=$pdo->prepare('SELECT stored_name FROM project_files WHERE project_id=?');$files->execute([$id]);$names=$files->fetchAll(PDO::FETCH_COLUMN);
 $ev=$pdo->prepare('SELECT appointment_id FROM project_events WHERE project_id=? AND appointment_id IS NOT NULL');$ev->execute([$id]);foreach($ev->fetchAll(PDO::FETCH_COLUMN) as $a)gszPCancelAppointment($pdo,(int)$a);
 $pdo->prepare('DELETE FROM projects WHERE id=?')->execute([$id]);foreach($names as $n)gszPFileUnlink((string)$n);gszAudit($pdo,$admin,'project_deleted',['id'=>$id]);
}

/* ---------- Handwerker ---------- */
function gszContractorSave(PDO $pdo,int $admin,array $in,?int $id=null):int {
 $name=gszPText($in['name']??'',190);if($name==='')throw new RuntimeException('Bitte Name oder Firma angeben.');
 $email=gszPText($in['email']??'',190);if($email!==''&&!filter_var($email,FILTER_VALIDATE_EMAIL))throw new RuntimeException('E-Mail-Adresse ungültig.');
 $vals=[$name,gszPText($in['trade']??'',120),gszPText($in['phone']??'',80),$email,gszPText($in['notes']??'',500)];
 if($id===null){$pdo->prepare('INSERT INTO project_contractors(name,trade,phone,email,notes) VALUES(?,?,?,?,?)')->execute($vals);$id=(int)$pdo->lastInsertId();}
 else $pdo->prepare('UPDATE project_contractors SET name=?,trade=?,phone=?,email=?,notes=? WHERE id=?')->execute([...$vals,$id]);
 return $id;
}
function gszContractors(PDO $pdo):array {return $pdo->query('SELECT * FROM project_contractors ORDER BY name')->fetchAll(PDO::FETCH_ASSOC);}
function gszContractorDelete(PDO $pdo,int $id):void {$pdo->prepare('DELETE FROM project_contractors WHERE id=?')->execute([$id]);}

/* ---------- Gewerke ---------- */
function gszTradeSave(PDO $pdo,int $projectId,array $in,?int $id=null):int {
 $name=gszPText($in['name']??'',120);if($name==='')throw new RuntimeException('Bitte das Gewerk benennen.');
 $status=(string)($in['status']??'offen');if(!isset(GSZ_TRADE_STATUS[$status]))throw new RuntimeException('Status ungültig.');
 $start=gszPDate($in['start_date']??'');$end=gszPDate($in['end_date']??'');if($start&&$end&&$end<$start)throw new RuntimeException('Ende liegt vor dem Start.');
 $vals=[$name,gszPId($pdo,'project_contractors',$in['contractor_id']??0),$status,$start,$end,gszPMoney($in['budget']??''),gszPDate($in['warranty_until']??''),gszPText($in['notes']??'',1000)];
 if($id===null){$o=$pdo->prepare('SELECT COALESCE(MAX(sort_order),0)+10 FROM project_trades WHERE project_id=?');$o->execute([$projectId]);
  $pdo->prepare('INSERT INTO project_trades(project_id,name,contractor_id,status,start_date,end_date,budget_cents,warranty_until,notes,sort_order) VALUES(?,?,?,?,?,?,?,?,?,?)')->execute([$projectId,...$vals,(int)$o->fetchColumn()]);return (int)$pdo->lastInsertId();}
 gszPId($pdo,'project_trades',$id,$projectId);$pdo->prepare('UPDATE project_trades SET name=?,contractor_id=?,status=?,start_date=?,end_date=?,budget_cents=?,warranty_until=?,notes=?,row_version=row_version+1 WHERE id=? AND project_id=?')->execute([...$vals,$id,$projectId]);return $id;
}
function gszTradeTemplate(PDO $pdo,int $projectId):int {
 $have=$pdo->prepare('SELECT name FROM project_trades WHERE project_id=?');$have->execute([$projectId]);$h=array_map('mb_strtolower',$have->fetchAll(PDO::FETCH_COLUMN));$n=0;
 foreach(GSZ_TRADE_TEMPLATE as $t)if(!in_array(mb_strtolower($t),$h,true)){gszTradeSave($pdo,$projectId,['name'=>$t]);$n++;}return $n;
}
function gszTradeStatus(PDO $pdo,int $projectId,int $id,string $status):void {if(!isset(GSZ_TRADE_STATUS[$status]))throw new RuntimeException('Status ungültig.');$q=$pdo->prepare('UPDATE project_trades SET status=?,row_version=row_version+1 WHERE id=? AND project_id=?');$q->execute([$status,$id,$projectId]);if(!$q->rowCount()&&!gszPId($pdo,'project_trades',$id,$projectId))throw new RuntimeException('Gewerk nicht gefunden.');}
function gszTradeDelete(PDO $pdo,int $projectId,int $id):void {$pdo->prepare('DELETE FROM project_trades WHERE id=? AND project_id=?')->execute([$id,$projectId]);}
function gszTrades(PDO $pdo,int $projectId):array {
 $q=$pdo->prepare('SELECT t.*,c.name contractor_name,c.phone contractor_phone,(SELECT COALESCE(SUM(amount_cents),0) FROM project_costs k WHERE k.trade_id=t.id AND k.kind=\'rechnung\') invoiced_cents,(SELECT COUNT(*) FROM project_items i WHERE i.trade_id=t.id AND i.done_at IS NULL) open_items FROM project_trades t LEFT JOIN project_contractors c ON c.id=t.contractor_id WHERE t.project_id=? ORDER BY t.sort_order,t.id');$q->execute([$projectId]);return $q->fetchAll(PDO::FETCH_ASSOC);
}

/* ---------- Termine (erscheinen in der Woche) ---------- */
function gszPCancelAppointment(PDO $pdo,int $appointmentId):void {
 $cols=$pdo->query('SHOW COLUMNS FROM planning_appointments')->fetchAll(PDO::FETCH_COLUMN);
 if(in_array('cancelled_at',$cols,true))$pdo->prepare('UPDATE planning_appointments SET cancelled_at=NOW() WHERE id=?')->execute([$appointmentId]);else $pdo->prepare('DELETE FROM planning_appointments WHERE id=?')->execute([$appointmentId]);
}
function gszEventSave(PDO $pdo,int $admin,int $projectId,array $in):int {
 $p=gszProjectGet($pdo,$projectId);if(!$p)throw new RuntimeException('Projekt nicht gefunden.');
 $day=gszPDate($in['day']??'',true);$start=gszPTime($in['start_time']??'');$end=gszPTime($in['end_time']??'');if($end<=$start)throw new RuntimeException('Ende muss nach dem Beginn liegen.');
 $trade=gszPId($pdo,'project_trades',$in['trade_id']??0,$projectId);$contractor=gszPId($pdo,'project_contractors',$in['contractor_id']??0);
 $title=gszPText($in['title']??'',190);
 $tn='';if($trade){$q=$pdo->prepare('SELECT name FROM project_trades WHERE id=?');$q->execute([$trade]);$tn=(string)$q->fetchColumn();}
 $cn='';if($contractor){$q=$pdo->prepare('SELECT name FROM project_contractors WHERE id=?');$q->execute([$contractor]);$cn=(string)$q->fetchColumn();}
 if($title==='')$title=trim($tn.($cn!==''?' – '.$cn:''));if($title==='')throw new RuntimeException('Bitte angeben, worum es geht (oder Gewerk/Handwerker wählen).');
 $pdo->beginTransaction();
 try{
  $pdo->prepare('INSERT INTO planning_appointments(day,start_time,end_time,title,address) VALUES(?,?,?,?,?)')->execute([$day,$start,$end,mb_substr('Bau: '.$title.' · '.$p['title'],0,190),mb_substr((string)($p['building_address']??''),0,255)]);$app=(int)$pdo->lastInsertId();
  $pdo->prepare('INSERT INTO project_events(project_id,trade_id,contractor_id,appointment_id,day,start_time,end_time,title) VALUES(?,?,?,?,?,?,?,?)')->execute([$projectId,$trade,$contractor,$app,$day,$start,$end,$title]);$id=(int)$pdo->lastInsertId();
  $pdo->commit();
 }catch(Throwable $e){if($pdo->inTransaction())$pdo->rollBack();throw $e;}
 gszAudit($pdo,$admin,'project_event_added',['project'=>$projectId,'id'=>$id]);return $id;
}
function gszEventDelete(PDO $pdo,int $projectId,int $id):void {
 $q=$pdo->prepare('SELECT appointment_id FROM project_events WHERE id=? AND project_id=?');$q->execute([$id,$projectId]);$a=$q->fetchColumn();if($a===false)throw new RuntimeException('Termin nicht gefunden.');
 if($a)gszPCancelAppointment($pdo,(int)$a);$pdo->prepare('DELETE FROM project_events WHERE id=?')->execute([$id]);
}
function gszEvents(PDO $pdo,int $projectId,bool $upcomingOnly=false):array {
 $q=$pdo->prepare('SELECT e.*,t.name trade_name,c.name contractor_name,c.phone contractor_phone FROM project_events e LEFT JOIN project_trades t ON t.id=e.trade_id LEFT JOIN project_contractors c ON c.id=e.contractor_id WHERE e.project_id=?'.($upcomingOnly?' AND e.day>=CURDATE()':'').' ORDER BY e.day'.($upcomingOnly?'':' DESC').',e.start_time');$q->execute([$projectId]);return $q->fetchAll(PDO::FETCH_ASSOC);
}

/* ---------- Dateien (Fotos, PDFs) ---------- */
function gszPFileDir():string {$d=__DIR__.'/uploads/projects';if(!is_dir($d)&&!@mkdir($d,0700,true)&&!is_dir($d))throw new RuntimeException('Ablage nicht verfügbar.');$r=realpath($d);if(!$r)throw new RuntimeException('Ablage nicht verfügbar.');return $r;}
function gszPFileUnlink(string $name):void {if(preg_match('/^[a-f0-9]{32}\.(jpg|png|webp|pdf)$/D',$name)){$p=gszPFileDir().'/'.$name;if(is_file($p)&&!is_link($p))@unlink($p);}}
function gszPHasUpload(?array $f):bool {return is_array($f)&&($f['error']??UPLOAD_ERR_NO_FILE)!==UPLOAD_ERR_NO_FILE;}
/** Speichert Foto (JPEG/PNG/WebP) oder PDF bis 15 MB außerhalb des Webverzeichnisses. */
function gszPFileStore(PDO $pdo,int $projectId,array $file,array $meta=[]):int {
 if(($file['error']??UPLOAD_ERR_NO_FILE)!==UPLOAD_ERR_OK||!is_uploaded_file((string)($file['tmp_name']??'')))throw new RuntimeException('Upload fehlgeschlagen.');
 $size=(int)($file['size']??0);if($size<1||$size>15*1024*1024)throw new RuntimeException('Datei bitte kleiner als 15 MB.');$tmp=(string)$file['tmp_name'];
 $mime=(new finfo(FILEINFO_MIME_TYPE))->file($tmp);$ext=['image/jpeg'=>'jpg','image/png'=>'png','image/webp'=>'webp','application/pdf'=>'pdf'][$mime]??null;
 if(!$ext)throw new RuntimeException('Nur Fotos (JPEG, PNG, WebP) oder PDF.');
 if($ext==='pdf'&&file_get_contents($tmp,false,null,0,5)!=='%PDF-')throw new RuntimeException('Ungültige PDF-Datei.');
 if($ext!=='pdf'){$i=@getimagesize($tmp);if(!$i||($i['mime']??'')!==$mime||$i[0]>12000||$i[1]>12000)throw new RuntimeException('Ungültiges Bild.');}
 $name=bin2hex(random_bytes(16)).'.'.$ext;$path=gszPFileDir().'/'.$name;if(!move_uploaded_file($tmp,$path))throw new RuntimeException('Datei konnte nicht gespeichert werden.');@chmod($path,0600);
 $phase=(string)($meta['phase']??'');if(!isset(GSZ_FILE_PHASES[$phase]))$phase='';
 try{$pdo->prepare('INSERT INTO project_files(project_id,trade_id,kind,phase,unit_label,caption,stored_name,original_name,mime_type,size_bytes) VALUES(?,?,?,?,?,?,?,?,?,?)')->execute([$projectId,gszPId($pdo,'project_trades',$meta['trade_id']??0,$projectId),$ext==='pdf'?'dokument':'foto',$phase,gszPText($meta['unit_label']??'',80),gszPText($meta['caption']??'',190),$name,gszName((string)($file['name']??'Datei')),$mime,$size]);}
 catch(Throwable $e){gszPFileUnlink($name);throw $e;}
 return (int)$pdo->lastInsertId();
}
function gszPFileDelete(PDO $pdo,int $projectId,int $id):void {$q=$pdo->prepare('SELECT stored_name FROM project_files WHERE id=? AND project_id=?');$q->execute([$id,$projectId]);$n=$q->fetchColumn();if($n===false)throw new RuntimeException('Datei nicht gefunden.');$pdo->prepare('DELETE FROM project_files WHERE id=?')->execute([$id]);gszPFileUnlink((string)$n);}
function gszPFiles(PDO $pdo,int $projectId,?string $kind=null):array {$q=$pdo->prepare('SELECT f.*,t.name trade_name FROM project_files f LEFT JOIN project_trades t ON t.id=f.trade_id WHERE f.project_id=?'.($kind?' AND f.kind=?':'').' ORDER BY f.created_at DESC,f.id DESC');$q->execute($kind?[$projectId,$kind]:[$projectId]);return $q->fetchAll(PDO::FETCH_ASSOC);}

/* ---------- Kosten ---------- */
function gszCostSave(PDO $pdo,int $admin,int $projectId,array $in,?array $file=null):int {
 $kind=(string)($in['kind']??'rechnung');if(!in_array($kind,['angebot','rechnung'],true))throw new RuntimeException('Art ungültig.');
 $title=gszPText($in['title']??'',190);if($title==='')throw new RuntimeException('Bitte kurz beschreiben (z. B. „Elektro Abschlag 1“).');
 $amount=gszPMoney($in['amount']??'',true);$trade=gszPId($pdo,'project_trades',$in['trade_id']??0,$projectId);$contractor=gszPId($pdo,'project_contractors',$in['contractor_id']??0);
 $fileId=gszPHasUpload($file)?gszPFileStore($pdo,$projectId,$file,['trade_id'=>$trade,'caption'=>$title]):null;
 $pdo->prepare('INSERT INTO project_costs(project_id,trade_id,contractor_id,kind,title,amount_cents,doc_date,due_date,accepted,paid_on,file_id,notes) VALUES(?,?,?,?,?,?,?,?,?,?,?,?)')->execute([$projectId,$trade,$contractor,$kind,$title,$amount,gszPDate($in['doc_date']??''),$kind==='rechnung'?gszPDate($in['due_date']??''):null,$kind==='angebot'&&!empty($in['accepted'])?1:0,$kind==='rechnung'&&!empty($in['paid'])?(gszPDate($in['paid_on']??'')??date('Y-m-d')):null,$fileId,gszPText($in['notes']??'',500)]);
 $id=(int)$pdo->lastInsertId();gszAudit($pdo,$admin,'project_cost_added',['project'=>$projectId,'id'=>$id,'kind'=>$kind]);return $id;
}
function gszCostFlag(PDO $pdo,int $projectId,int $id,string $flag,bool $on):void {
 $sql=match($flag){'paid'=>'UPDATE project_costs SET paid_on='.($on?'COALESCE(paid_on,CURDATE())':'NULL').",row_version=row_version+1 WHERE id=? AND project_id=? AND kind='rechnung'",'accepted'=>'UPDATE project_costs SET accepted='.($on?1:0).",row_version=row_version+1 WHERE id=? AND project_id=? AND kind='angebot'",default=>throw new RuntimeException('Aktion unbekannt.')};
 $q=$pdo->prepare($sql);$q->execute([$id,$projectId]);if(!$q->rowCount())throw new RuntimeException('Eintrag nicht gefunden.');
}
function gszCostDelete(PDO $pdo,int $projectId,int $id):void {$q=$pdo->prepare('SELECT file_id FROM project_costs WHERE id=? AND project_id=?');$q->execute([$id,$projectId]);$f=$q->fetchColumn();if($f===false)throw new RuntimeException('Eintrag nicht gefunden.');$pdo->prepare('DELETE FROM project_costs WHERE id=?')->execute([$id]);if($f)gszPFileDelete($pdo,$projectId,(int)$f);}
function gszCosts(PDO $pdo,int $projectId):array {$q=$pdo->prepare('SELECT k.*,t.name trade_name,c.name contractor_name,f.original_name file_name FROM project_costs k LEFT JOIN project_trades t ON t.id=k.trade_id LEFT JOIN project_contractors c ON c.id=k.contractor_id LEFT JOIN project_files f ON f.id=k.file_id WHERE k.project_id=? ORDER BY k.kind DESC,(k.paid_on IS NOT NULL),COALESCE(k.due_date,k.doc_date,DATE(k.created_at)),k.id');$q->execute([$projectId]);return $q->fetchAll(PDO::FETCH_ASSOC);}

/* ---------- Mängel & To-dos ---------- */
function gszItemSave(PDO $pdo,int $projectId,array $in,?array $photo=null):int {
 $title=gszPText($in['title']??'',190);if($title==='')throw new RuntimeException('Bitte kurz beschreiben, was zu tun ist.');
 $kind=(string)($in['kind']??'todo');if(!in_array($kind,['todo','mangel'],true))throw new RuntimeException('Art ungültig.');
 $trade=gszPId($pdo,'project_trades',$in['trade_id']??0,$projectId);$unit=gszPText($in['unit_label']??'',80);
 $photoId=gszPHasUpload($photo)?gszPFileStore($pdo,$projectId,$photo,['trade_id'=>$trade,'unit_label'=>$unit,'caption'=>$title,'phase'=>'waehrend']):null;
 $pdo->prepare('INSERT INTO project_items(project_id,trade_id,contractor_id,kind,title,unit_label,due_date,photo_file_id) VALUES(?,?,?,?,?,?,?,?)')->execute([$projectId,$trade,gszPId($pdo,'project_contractors',$in['contractor_id']??0),$kind,$title,$unit,gszPDate($in['due_date']??''),$photoId]);
 return (int)$pdo->lastInsertId();
}
function gszItemDone(PDO $pdo,int $projectId,int $id,bool $done,mixed $version=null):array {
 $q=$pdo->prepare('SELECT * FROM project_items WHERE id=? AND project_id=?');$q->execute([$id,$projectId]);$r=$q->fetch(PDO::FETCH_ASSOC);if(!$r)throw new RuntimeException('Eintrag nicht gefunden.');
 if($version!==null&&$version!==''&&(int)$version!==(int)$r['row_version'])throw new RuntimeException('Der Eintrag wurde inzwischen geändert. Bitte Seite neu laden.');
 $u=$pdo->prepare('UPDATE project_items SET done_at='.($done?'COALESCE(done_at,NOW())':'NULL').',row_version=row_version+1 WHERE id=? AND row_version=?');$u->execute([$id,$r['row_version']]);
 if(!$u->rowCount())throw new RuntimeException('Der Eintrag wurde inzwischen geändert. Bitte Seite neu laden.');$q->execute([$id,$projectId]);return $q->fetch(PDO::FETCH_ASSOC);
}
function gszItemDelete(PDO $pdo,int $projectId,int $id):void {$q=$pdo->prepare('SELECT photo_file_id FROM project_items WHERE id=? AND project_id=?');$q->execute([$id,$projectId]);$f=$q->fetchColumn();if($f===false)throw new RuntimeException('Eintrag nicht gefunden.');$pdo->prepare('DELETE FROM project_items WHERE id=?')->execute([$id]);if($f)gszPFileDelete($pdo,$projectId,(int)$f);}
function gszItems(PDO $pdo,int $projectId,bool $open=true):array {$q=$pdo->prepare('SELECT i.*,t.name trade_name,c.name contractor_name,c.phone contractor_phone FROM project_items i LEFT JOIN project_trades t ON t.id=i.trade_id LEFT JOIN project_contractors c ON c.id=i.contractor_id WHERE i.project_id=? AND i.done_at IS '.($open?'NULL':'NOT NULL').' ORDER BY '.($open?"i.kind='mangel' DESC,i.due_date IS NULL,i.due_date,i.unit_label,i.id":'i.done_at DESC').' LIMIT 500');$q->execute([$projectId]);return $q->fetchAll(PDO::FETCH_ASSOC);}

/* ---------- Übersicht ---------- */
function gszProjectSummary(PDO $pdo,int $id,?string $today=null):array {
 $today??=date('Y-m-d');$p=gszProjectGet($pdo,$id);
 $t=$pdo->prepare('SELECT status,COUNT(*) n FROM project_trades WHERE project_id=? GROUP BY status');$t->execute([$id]);$ts=array_map('intval',array_column($t->fetchAll(PDO::FETCH_ASSOC),'n','status'))+['offen'=>0,'geplant'=>0,'laeuft'=>0,'fertig'=>0];$total=array_sum($ts);
 $c=$pdo->prepare("SELECT COALESCE(SUM(kind='rechnung'),0) invoices,COALESCE(SUM(CASE WHEN kind='rechnung' THEN amount_cents END),0) invoiced,COALESCE(SUM(CASE WHEN kind='rechnung' AND paid_on IS NOT NULL THEN amount_cents END),0) paid,COALESCE(SUM(CASE WHEN kind='angebot' AND accepted=1 THEN amount_cents END),0) committed,COALESCE(SUM(CASE WHEN kind='rechnung' AND paid_on IS NULL AND due_date IS NOT NULL AND due_date<=? THEN 1 END),0) due FROM project_costs WHERE project_id=?");$c->execute([(new DateTimeImmutable($today))->modify('+3 days')->format('Y-m-d'),$id]);$cs=array_map('intval',$c->fetch(PDO::FETCH_ASSOC));
 $tb=$pdo->prepare('SELECT COALESCE(SUM(budget_cents),0) FROM project_trades WHERE project_id=?');$tb->execute([$id]);$tradeBudget=(int)$tb->fetchColumn();
 $i=$pdo->prepare("SELECT COALESCE(SUM(kind='mangel'),0) defects,COUNT(*) open,COALESCE(SUM(due_date<?),0) late FROM project_items WHERE project_id=? AND done_at IS NULL");$i->execute([$today,$id]);$is=array_map('intval',$i->fetch(PDO::FETCH_ASSOC));
 $w=$pdo->prepare('SELECT name,warranty_until FROM project_trades WHERE project_id=? AND warranty_until IS NOT NULL AND warranty_until>=? ORDER BY warranty_until LIMIT 3');$w->execute([$id,$today]);
 $budget=$p&&$p['budget_cents']!==null?(int)$p['budget_cents']:($tradeBudget?:null);
 return ['trades'=>$ts,'trades_total'=>$total,'progress'=>$total?(int)round(100*$ts['fertig']/$total):0,'budget'=>$budget,'invoiced'=>$cs['invoiced'],'paid'=>$cs['paid'],'committed'=>$cs['committed'],'open_to_pay'=>$cs['invoiced']-$cs['paid'],'due_invoices'=>$cs['due'],
  'items_open'=>$is['open'],'defects_open'=>$is['defects'],'items_late'=>$is['late'],'warranties'=>$w->fetchAll(PDO::FETCH_ASSOC),'next_events'=>array_slice(gszEvents($pdo,$id,true),0,3)];
}
/** Hinweise für „Heute“: fällige Handwerkerrechnungen, überfällige Mängel/To-dos, bald endende Gewährleistungen. */
function gszProjectAttention(PDO $pdo,?string $today=null):array {
 $today??=date('Y-m-d');$out=[];if(!gszProjectsReady($pdo))return $out;
 $q=$pdo->prepare("SELECT k.project_id,COUNT(*) n FROM project_costs k WHERE k.kind='rechnung' AND k.paid_on IS NULL AND k.due_date IS NOT NULL AND k.due_date<=? GROUP BY k.project_id");$q->execute([(new DateTimeImmutable($today))->modify('+3 days')->format('Y-m-d')]);
 foreach($q->fetchAll(PDO::FETCH_ASSOC) as $r)$out[]=['project.php?id='.(int)$r['project_id'].'&tab=kosten',(int)$r['n']===1?'Bau: 1 Handwerkerrechnung bald fällig':'Bau: '.(int)$r['n'].' Handwerkerrechnungen bald fällig'];
 $q=$pdo->prepare('SELECT project_id,COUNT(*) n FROM project_items WHERE done_at IS NULL AND due_date<? GROUP BY project_id');$q->execute([$today]);
 foreach($q->fetchAll(PDO::FETCH_ASSOC) as $r)$out[]=['project.php?id='.(int)$r['project_id'].'&tab=maengel',(int)$r['n']===1?'Bau: 1 Mangel/To-do überfällig':'Bau: '.(int)$r['n'].' Mängel/To-dos überfällig'];
 $q=$pdo->prepare('SELECT t.project_id,t.name,t.warranty_until FROM project_trades t WHERE t.warranty_until BETWEEN ? AND ? ORDER BY t.warranty_until LIMIT 5');$q->execute([$today,(new DateTimeImmutable($today))->modify('+60 days')->format('Y-m-d')]);
 foreach($q->fetchAll(PDO::FETCH_ASSOC) as $r){$days=(int)(new DateTimeImmutable($today))->diff(new DateTimeImmutable($r['warranty_until']))->format('%a');$out[]=['project.php?id='.(int)$r['project_id'].'&tab=gewerke','Gewährleistung '.$r['name'].' endet '.($days===0?'heute':'in '.$days.' Tagen').' – jetzt Mängel prüfen'];}
 return $out;
}
function gszMoney(?int $c):string {return $c===null?'—':moneyDisplay($c);}
