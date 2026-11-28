<?php
declare(strict_types=1);
/**
 * Schema 430: Mieter-Meldungen per QR-Code und Hausverwaltungs-Portal.
 * Jedes Objekt bekommt auf Wunsch zwei geheime Links (Meldung / Portal). Ein neuer Link macht den alten sofort ungültig.
 * Mieter-Fotos werden ohne Metadaten (z. B. GPS) neu gespeichert. Keine IP-Adressen, keine Cookies.
 */
require_once __DIR__.'/platform.php';
const GSZ_DEFECT_STATUS=['neu'=>'Neu','uebernommen'=>'In Arbeit','erledigt'=>'Erledigt','verworfen'=>'Verworfen'];
/** Orte und Arten für die Kacheln im Meldeformular (Symbol => Text). */
const GSZ_DEFECT_PLACES=['Treppenhaus'=>'🪜','Eingang / Haustür'=>'🚪','Keller'=>'📦','Dachboden'=>'🏠','Aufzug'=>'🛗','Außenanlage / Garten'=>'🌳','Müllplatz'=>'🗑️','Garage / Stellplätze'=>'🚗','Sonstiges'=>'📍'];
const GSZ_DEFECT_KINDS=['Licht defekt'=>'💡','Tür / Schloss'=>'🔑','Wasser / Feuchtigkeit'=>'💧','Heizung'=>'🔥','Müll / Tonnen'=>'♻️','Verschmutzung'=>'🧽','Etwas ist kaputt'=>'🔨','Sonstiges'=>'💬'];
const GSZ_DEFECT_LIMIT_HOUR=6;const GSZ_DEFECT_LIMIT_DAY=20;

/** Einheitliche Linien-Symbole (24×24, Strich) für das Meldeformular – statt Emojis. */
const GSZ_DEFECT_ICONS=[
 'Treppenhaus'=>'M4 20h4v-4h4v-4h4V8h4','Eingang / Haustür'=>'M6 3h12v18H6z M15 12h.01','Keller'=>'M3 7l9-4 9 4-9 4-9-4z M3 7v10l9 4 9-4V7 M12 11v10',
 'Dachboden'=>'M3 11l9-7 9 7 M5 10v10h14V10 M10 20v-5h4v5','Aufzug'=>'M5 3h14v18H5z M9 9l3-3 3 3 M9 15l3 3 3-3','Außenanlage / Garten'=>'M12 22v-7 M7 10a5 5 0 1 1 10 0 5 5 0 0 1-10 0z',
 'Müllplatz'=>'M4 7h16 M10 11v6 M14 11v6 M6 7l1 13h10l1-13 M9 7V4h6v3','Garage / Stellplätze'=>'M5 15l1.5-5h11L19 15 M4 15h16v4H4z M7 19v2 M17 19v2','Sonstiges'=>'M12 21s-6-6-6-11a6 6 0 0 1 12 0c0 5-6 11-6 11z M12 10h.01',
 'Licht defekt'=>'M9 18h6 M10 21h4 M12 3a6 6 0 0 0-3.5 10.9c.6.5 1 1.2 1 2.1h5c0-.9.4-1.6 1-2.1A6 6 0 0 0 12 3z','Tür / Schloss'=>'M15 7a4 4 0 1 1-3.9 4.9L3 20v-3h3v-3h3l2.1-2.1 M16 7h.01',
 'Wasser / Feuchtigkeit'=>'M12 3s-6 7-6 11a6 6 0 0 0 12 0c0-4-6-11-6-11z','Heizung'=>'M3 6h18 M3 18h18 M6 6v12 M10 6v12 M14 6v12 M18 6v12','Müll / Tonnen'=>'M4 7h16 M10 11v6 M14 11v6 M6 7l1 13h10l1-13 M9 7V4h6v3',
 'Verschmutzung'=>'M12 3l1.8 4.2L18 9l-4.2 1.8L12 15l-1.8-4.2L6 9l4.2-1.8z M19 15l.8 2 2 .8-2 .8-.8 2-.8-2-2-.8 2-.8z','Etwas ist kaputt'=>'M14.7 6.3a4 4 0 0 0-5.4 5.4L3 18l3 3 6.3-6.3a4 4 0 0 0 5.4-5.4l-2.6 2.6-2.4-.6-.6-2.4z',
 'Sonstiges (Art)'=>'M21 12a8 8 0 0 1-11.6 7.1L4 20l1-4.6A8 8 0 1 1 21 12z'];
function gszDefectIcon(string $label,bool $kind=false):string {$d=GSZ_DEFECT_ICONS[$kind&&$label==='Sonstiges'?'Sonstiges (Art)':$label]??GSZ_DEFECT_ICONS['Sonstiges'];
 return '<svg viewBox="0 0 24 24" width="26" height="26" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="'.$d.'"/></svg>';}
function gszServiceReady(PDO $pdo):bool {try{return (bool)$pdo->query("SELECT COUNT(*) FROM app_migrations WHERE version='430_service'")->fetchColumn();}catch(Throwable $e){return false;}}
function gszRText(mixed $v,int $max):string {return mb_substr(trim(preg_replace('/[\x00-\x08\x0B\x0C\x0E-\x1F\x7F]/u','',(string)$v)??''),0,$max);}
function gszToken():string {return rtrim(strtr(base64_encode(random_bytes(24)),'+/','-_'),'=');}
function gszTokenValid(string $t):bool {return (bool)preg_match('/^[A-Za-z0-9_-]{32}$/D',$t);}

/* ---------- Links je Objekt ---------- */
function gszLinks(PDO $pdo,int $property):array {
 $q=$pdo->prepare('SELECT * FROM property_links WHERE property_id=?');$q->execute([$property]);
 return $q->fetch(PDO::FETCH_ASSOC)?:['property_id'=>$property,'report_token'=>null,'portal_token'=>null,'report_since'=>null,'portal_since'=>null];
}
/** kind: report|portal. Erzeugt einen neuen Link (der alte wird ungültig) oder schaltet ihn ab ($off). */
function gszLinkRenew(PDO $pdo,int $admin,int $property,string $kind,bool $off=false):?string {
 if(!in_array($kind,['report','portal'],true))throw new RuntimeException('Unbekannter Link.');
 $q=$pdo->prepare('SELECT id FROM properties WHERE id=?');$q->execute([$property]);if(!$q->fetchColumn())throw new RuntimeException('Objekt nicht gefunden.');
 $token=$off?null:gszToken();$since=$off?null:gmdate('Y-m-d H:i:s');
 $pdo->prepare("INSERT INTO property_links(property_id,{$kind}_token,{$kind}_since) VALUES(?,?,?) ON DUPLICATE KEY UPDATE {$kind}_token=VALUES({$kind}_token),{$kind}_since=VALUES({$kind}_since)")->execute([$property,$token,$since]);
 gszAudit($pdo,$admin,$off?'link_disabled':'link_renewed',['property'=>$property,'kind'=>$kind]);return $token;
}
/** Objekt zu einem geheimen Link (zeitkonstanter Vergleich nach dem Index-Treffer). */
function gszLinkProperty(PDO $pdo,string $kind,string $token):?array {
 if(!in_array($kind,['report','portal'],true)||!gszTokenValid($token))return null;
 $q=$pdo->prepare("SELECT p.id,p.name,p.address,l.{$kind}_token t FROM property_links l JOIN properties p ON p.id=l.property_id WHERE l.{$kind}_token=?");$q->execute([$token]);$r=$q->fetch(PDO::FETCH_ASSOC);
 return $r&&hash_equals((string)$r['t'],$token)?['id'=>(int)$r['id'],'name'=>(string)$r['name'],'address'=>(string)$r['address']]:null;
}
function gszZentraleUrl(PDO $pdo):string {require_once __DIR__.'/leads.php';$u=rtrim(gszSetting($pdo,'zentrale_url'),'/');return $u!==''?$u:'https://gszintern.de';}
function gszReportUrl(PDO $pdo,string $token):string {return gszZentraleUrl($pdo).'/melden.php?k='.$token;}
function gszPortalUrl(PDO $pdo,string $token):string {return gszZentraleUrl($pdo).'/portal.php?k='.$token;}

/* ---------- Fotos (außerhalb des Webverzeichnisses, ohne Metadaten) ---------- */
function gszDefectPhotoDir():string {
 $dir=__DIR__.'/uploads/defect_photos';if(!is_dir($dir)&&!@mkdir($dir,0700,true)&&!is_dir($dir))throw new RuntimeException('Fotoverzeichnis kann nicht erstellt werden.');
 $real=realpath($dir);if(!$real)throw new RuntimeException('Fotoverzeichnis fehlt.');return $real;
}
/** Speichert ein Foto neu als JPEG (max. 2000 px) – dabei fallen Standort- und Kameradaten weg. Ohne GD: Original. */
function gszStoreCleanPhoto(array $file,string $dir):array {
 require_once __DIR__.'/photo_storage.php';[$mime,$ext]=photoValidate($file);$tmp=(string)$file['tmp_name'];$name=bin2hex(random_bytes(16));
 if(function_exists('imagecreatefromstring')&&function_exists('imagejpeg')){
  $img=@imagecreatefromstring((string)file_get_contents($tmp));
  if($img!==false){
   if($mime==='image/jpeg'&&function_exists('exif_read_data')){$o=(int)(@exif_read_data($tmp)['Orientation']??1);$img=match($o){3=>imagerotate($img,180,0),6=>imagerotate($img,-90,0),8=>imagerotate($img,90,0),default=>$img};}
   $w=imagesx($img);$h=imagesy($img);$s=min(1,2000/max($w,$h));if($s<1){$n=imagescale($img,max(1,(int)round($w*$s)),max(1,(int)round($h*$s)));if($n!==false)$img=$n;}
   $path=$dir.'/'.$name.'.jpg';if(!imagejpeg($img,$path,82))throw new RuntimeException('Foto konnte nicht gespeichert werden.');@chmod($path,0600);return [$name.'.jpg','image/jpeg'];
  }
 }
 $path=$dir.'/'.$name.'.'.$ext;if(!move_uploaded_file($tmp,$path))throw new RuntimeException('Foto konnte nicht gespeichert werden.');@chmod($path,0600);return [$name.'.'.$ext,$mime];
}
function gszDefectPhotoPath(?string $name):?string {if(!$name||!preg_match('/^[a-f0-9]{32}\.(jpg|png|webp)$/D',$name))return null;$p=gszDefectPhotoDir().'/'.$name;return is_file($p)&&!is_link($p)?$p:null;}

/* ---------- Meldungen ---------- */
/** Öffentliche Meldung über den QR-Link. Begrenzung je Objekt statt IP-Speicherung. */
function gszDefectSubmit(PDO $pdo,array $property,array $in,?array $photo=null,?int $now=null):int {
 if(trim((string)($in['website']??''))!=='')throw new DomainException('spam');
 $text=gszRText($in['message']??'',1900);$kind=gszRText($in['kind']??'',60);if(!array_key_exists($kind,GSZ_DEFECT_KINDS))$kind='';
 if($kind===''&&mb_strlen($text)<3)throw new RuntimeException('Bitte antippen, was los ist – oder kurz beschreiben.');
 if($kind==='Sonstiges'&&mb_strlen($text)<3)throw new RuntimeException('Bitte kurz beschreiben, was los ist.');
 $message=$kind!==''&&$kind!=='Sonstiges'?$kind.($text!==''?': '.$text:''):$text;
 $location=gszRText($in['location']??'',120);
 // Name und Telefon sind Pflicht (Rückfragen); gespeichert zusammen in „contact“.
 $cname=gszRText($in['name']??'',120);$cphone=gszRText($in['phone']??'',60);
 if(mb_strlen($cname)<2)throw new RuntimeException('Bitte Ihren Namen angeben.');
 if(!preg_match('/^[0-9+ ()\/-]{6,40}$/D',$cphone)||strlen(preg_replace('/\D/','',$cphone))<6)throw new RuntimeException('Bitte eine Telefonnummer für Rückfragen angeben.');
 $contact=mb_substr($cname.' · '.$cphone,0,190);
 $now??=time();$q=$pdo->prepare('SELECT SUM(created_at>=?) h,COUNT(*) d FROM defect_reports WHERE property_id=? AND created_at>=?');$q->execute([gmdate('Y-m-d H:i:s',$now-3600),$property['id'],gmdate('Y-m-d H:i:s',$now-86400)]);$c=$q->fetch(PDO::FETCH_ASSOC);
 if((int)$c['h']>=GSZ_DEFECT_LIMIT_HOUR||(int)$c['d']>=GSZ_DEFECT_LIMIT_DAY)throw new RuntimeException('Für dieses Haus sind gerade sehr viele Meldungen eingegangen. Bitte später erneut versuchen oder anrufen.');
 $name=null;$mime=null;if(is_array($photo)&&($photo['error']??UPLOAD_ERR_NO_FILE)!==UPLOAD_ERR_NO_FILE)[$name,$mime]=gszStoreCleanPhoto($photo,gszDefectPhotoDir());
 try{$pdo->prepare('INSERT INTO defect_reports(property_id,location,message,contact,photo_name,photo_mime) VALUES(?,?,?,?,?,?)')->execute([$property['id'],$location,$message,$contact,$name,$mime]);}
 catch(Throwable $e){if($p=gszDefectPhotoPath($name))@unlink($p);throw $e;}
 $id=(int)$pdo->lastInsertId();gszAudit($pdo,null,'defect_reported',['id'=>$id,'property'=>$property['id']]);
 try{require_once __DIR__.'/push.php';gszPushNotify($pdo,'reports','defect:'.$id,'🛠 Mieter-Meldung: '.$property['name'],mb_strimwidth(($location!==''?$location.': ':'').$message,0,140,'…'),'meldungen.php#meldung-'.$id);}catch(Throwable $e){}
 return $id;
}
function gszDefect(PDO $pdo,int $id):?array {$q=$pdo->prepare('SELECT d.*,p.name property_name,p.address property_address,n.done_at note_done FROM defect_reports d JOIN properties p ON p.id=d.property_id LEFT JOIN quick_notes n ON n.id=d.note_id WHERE d.id=?');$q->execute([$id]);return $q->fetch(PDO::FETCH_ASSOC)?:null;}
/** Sichtbarer Status: eine übernommene Meldung gilt als erledigt, sobald ihr To-do abgehakt ist. */
function gszDefectState(array $d):string {return $d['status']==='uebernommen'&&!empty($d['note_done'])?'erledigt':(string)$d['status'];}
function gszDefects(PDO $pdo,?string $filter=null,?int $property=null,int $days=0):array {
 $w=[];$a=[];if($filter==='offen')$w[]="(d.status='neu' OR (d.status='uebernommen' AND n.done_at IS NULL))";elseif($filter==='neu')$w[]="d.status='neu'";elseif($filter==='erledigt')$w[]="(d.status IN ('erledigt','verworfen') OR (d.status='uebernommen' AND n.done_at IS NOT NULL))";
 if($property){$w[]='d.property_id=?';$a[]=$property;}if($days>0){$w[]='d.created_at>=?';$a[]=gmdate('Y-m-d H:i:s',time()-$days*86400);}
 $q=$pdo->prepare('SELECT d.*,p.name property_name,p.address property_address,n.done_at note_done FROM defect_reports d JOIN properties p ON p.id=d.property_id LEFT JOIN quick_notes n ON n.id=d.note_id'.($w?' WHERE '.implode(' AND ',$w):'').' ORDER BY d.created_at DESC,d.id DESC LIMIT 300');$q->execute($a);return $q->fetchAll(PDO::FETCH_ASSOC);
}
function gszDefectNewCount(PDO $pdo):int {return (int)$pdo->query("SELECT COUNT(*) FROM defect_reports WHERE status='neu'")->fetchColumn();}
/** Übernimmt eine Meldung als To-do in die Merkliste (mit Objekt und Foto); einmalig. */
function gszDefectToNote(PDO $pdo,int $admin,int $id):int {
 require_once __DIR__.'/notes.php';$d=gszDefect($pdo,$id);if(!$d)throw new RuntimeException('Meldung nicht gefunden.');if($d['note_id'])return (int)$d['note_id'];
 $title=mb_substr(($d['location']!==''?$d['location'].': ':'').preg_replace('/\s+/u',' ',(string)$d['message']),0,190);
 $photo=null;$mime=null;if($src=gszDefectPhotoPath($d['photo_name'])){$photo=bin2hex(random_bytes(16)).'.'.pathinfo($src,PATHINFO_EXTENSION);if(!@copy($src,gszNotePhotoDir().'/'.$photo))$photo=null;else{$mime=$d['photo_mime'];@chmod(gszNotePhotoDir().'/'.$photo,0600);}}
 $pdo->beginTransaction();try{
  $pdo->prepare("INSERT INTO quick_notes(kind,title,property_id,note,photo_name,photo_mime) VALUES('todo',?,?,?,?,?)")->execute([$title,$d['property_id'],mb_substr('Mieter-Meldung'.($d['contact']!==''?' · Rückfragen: '.$d['contact']:''),0,500),$photo,$mime]);$note=(int)$pdo->lastInsertId();
  $u=$pdo->prepare("UPDATE defect_reports SET status='uebernommen',note_id=?,handled_at=UTC_TIMESTAMP() WHERE id=? AND note_id IS NULL");$u->execute([$note,$id]);if(!$u->rowCount())throw new RuntimeException('Meldung wurde gerade schon übernommen.');
  gszAudit($pdo,$admin,'defect_to_note',['id'=>$id,'note'=>$note]);$pdo->commit();return $note;
 }catch(Throwable $e){$pdo->rollBack();if($photo)gszNoteRemovePhoto($photo);throw $e;}
}
/** Abhaken: Meldung erledigt; ein zugehöriges To-do in der Merkliste wird mit erledigt. */
function gszDefectDone(PDO $pdo,?int $admin,int $id,string $via='zentrale'):void {
 $d=gszDefect($pdo,$id);if(!$d)throw new RuntimeException('Meldung nicht gefunden.');
 $pdo->prepare("UPDATE defect_reports SET status='erledigt',handled_at=UTC_TIMESTAMP() WHERE id=?")->execute([$id]);
 if($d['note_id'])$pdo->prepare('UPDATE quick_notes SET done_at=NOW(),row_version=row_version+1 WHERE id=? AND done_at IS NULL')->execute([$d['note_id']]);
 gszAudit($pdo,$admin,'defect_done',['id'=>$id,'note'=>$d['note_id'],'via'=>$via]);
}
/** Wieder öffnen: mit To-do → „In Arbeit“ (To-do wieder offen), sonst „Neu“. */
function gszDefectReopen(PDO $pdo,?int $admin,int $id,string $via='zentrale'):void {
 $d=gszDefect($pdo,$id);if(!$d)throw new RuntimeException('Meldung nicht gefunden.');
 if($d['note_id']){$pdo->prepare("UPDATE defect_reports SET status='uebernommen' WHERE id=?")->execute([$id]);$pdo->prepare('UPDATE quick_notes SET done_at=NULL,row_version=row_version+1 WHERE id=?')->execute([$d['note_id']]);}
 else $pdo->prepare("UPDATE defect_reports SET status='neu',handled_at=NULL WHERE id=?")->execute([$id]);
 gszAudit($pdo,$admin,'defect_reopened',['id'=>$id,'via'=>$via]);
}
/** Endgültig löschen (inkl. Foto). Ein schon angelegtes To-do in der Merkliste bleibt bestehen. */
function gszDefectDelete(PDO $pdo,int $admin,int $id):void {
 $d=gszDefect($pdo,$id);if(!$d)throw new RuntimeException('Meldung nicht gefunden.');
 $pdo->prepare('DELETE FROM defect_reports WHERE id=?')->execute([$id]);if($f=gszDefectPhotoPath($d['photo_name']))@unlink($f);
 gszAudit($pdo,$admin,'defect_deleted',['id'=>$id,'property'=>(int)$d['property_id']]);
}
function gszDefectSetStatus(PDO $pdo,int $admin,int $id,string $status):void {
 if(!in_array($status,['neu','erledigt','verworfen'],true))throw new RuntimeException('Status unbekannt.');
 $q=$pdo->prepare('UPDATE defect_reports SET status=?,handled_at=IF(?=\'neu\',NULL,UTC_TIMESTAMP()) WHERE id=?');$q->execute([$status,$status,$id]);if(!$q->rowCount()&&!gszDefect($pdo,$id))throw new RuntimeException('Meldung nicht gefunden.');
 gszAudit($pdo,$admin,'defect_status',['id'=>$id,'status'=>$status]);
}

/* ---------- Portal / Monatsbericht ---------- */
/** Daten für Portal und Monatsbericht. $month = 'YYYY-MM' (Berlin). Nur, was eine Hausverwaltung sehen soll – keine Namen von Mitarbeitern. */
function gszPortalData(PDO $pdo,int $property,string $month,?string $today=null):array {
 if(!preg_match('/^(\d{4})-(0[1-9]|1[0-2])$/D',$month))throw new RuntimeException('Monat ungültig.');
 $from=$month.'-01';$to=(new DateTimeImmutable($from))->modify('last day of this month')->format('Y-m-d');$today??=(new DateTimeImmutable('today',new DateTimeZone('Europe/Berlin')))->format('Y-m-d');
 $q=$pdo->prepare('SELECT w.id,w.performed_on,w.description,w.duration_minutes,w.started_at,w.ended_at,(SELECT COUNT(*) FROM work_log_photos f WHERE f.work_log_id=w.id) photos FROM work_logs w WHERE w.property_id=? AND w.performed_on BETWEEN ? AND ? ORDER BY w.performed_on,w.id');$q->execute([$property,$from,$to]);$logs=$q->fetchAll(PDO::FETCH_ASSOC);
 $q=$pdo->prepare('SELECT t.id,t.title,t.completed_on day FROM tasks t WHERE t.property_id=? AND t.done=1 AND t.completed_on BETWEEN ? AND ? AND NOT EXISTS(SELECT 1 FROM work_logs w WHERE w.task_id=t.id) ORDER BY t.completed_on,t.id LIMIT 300');$q->execute([$property,$from,$to]);$done=$q->fetchAll(PDO::FETCH_ASSOC);
 $q=$pdo->prepare('SELECT f.id,f.work_log_id,f.label,f.stored_name,f.mime_type FROM work_log_photos f JOIN work_logs w ON w.id=f.work_log_id WHERE w.property_id=? AND w.performed_on BETWEEN ? AND ? ORDER BY w.performed_on,f.label=\'after\',f.id LIMIT 60');$q->execute([$property,$from,$to]);$photos=$q->fetchAll(PDO::FETCH_ASSOC);
 $q=$pdo->prepare('SELECT d.id,d.created_at,d.location,d.message,d.status,d.photo_name,n.done_at note_done FROM defect_reports d LEFT JOIN quick_notes n ON n.id=d.note_id WHERE d.property_id=? AND d.status<>\'verworfen\' AND (DATE(d.created_at) BETWEEN ? AND ? OR (d.status IN (\'neu\',\'uebernommen\') AND n.done_at IS NULL)) ORDER BY d.created_at DESC LIMIT 100');$q->execute([$property,$from,$to]);$defects=$q->fetchAll(PDO::FETCH_ASSOC);
 $q=$pdo->prepare('SELECT due_date,title FROM tasks WHERE property_id=? AND done=0 AND due_date BETWEEN ? AND ? ORDER BY due_date,start_time IS NULL,start_time,id LIMIT 40');$q->execute([$property,$today,(new DateTimeImmutable($today))->modify('+21 days')->format('Y-m-d')]);$next=$q->fetchAll(PDO::FETCH_ASSOC);
 return ['month'=>$month,'from'=>$from,'to'=>$to,'logs'=>$logs,'done'=>$done,'photos'=>$photos,'defects'=>$defects,'next'=>$next,'minutes'=>array_sum(array_map(fn($l)=>(int)$l['duration_minutes'],$logs)),'visits'=>count(array_unique(array_merge(array_column($logs,'performed_on'),array_column($done,'day'))))];
}
function gszMonthName(string $month):string {return ['','Januar','Februar','März','April','Mai','Juni','Juli','August','September','Oktober','November','Dezember'][(int)substr($month,5,2)].' '.substr($month,0,4);}
function gszPortalMonths(?string $today=null):array {$d=new DateTimeImmutable(($today??(new DateTimeImmutable('today',new DateTimeZone('Europe/Berlin')))->format('Y-m-d')));$d=$d->modify('first day of this month');$o=[];for($i=0;$i<6;$i++)$o[]=$d->modify("-$i months")->format('Y-m');return $o;}

/** Übersicht aller Häuser mit Stand von QR-Meldung und Portal, offenen Meldungen und Kontakt der Verwaltung. */
function gszLinksOverview(PDO $pdo,string $q=''):array {
 $w='';$a=[];if(($q=trim($q))!==''){$w=' WHERE p.name LIKE ? OR p.address LIKE ?';$a=['%'.$q.'%','%'.$q.'%'];}
 $s=$pdo->prepare("SELECT p.id,p.name,p.address,p.contact_name,p.contact_email,l.report_token,l.portal_token,l.report_since,l.portal_since,
  (SELECT COUNT(*) FROM defect_reports d LEFT JOIN quick_notes n ON n.id=d.note_id WHERE d.property_id=p.id AND (d.status='neu' OR (d.status='uebernommen' AND n.done_at IS NULL))) open_reports
  FROM properties p LEFT JOIN property_links l ON l.property_id=p.id$w ORDER BY p.name,p.id LIMIT 1000");$s->execute($a);return $s->fetchAll(PDO::FETCH_ASSOC);
}
/** Richtet für alle Häuser ohne Link einen ein (kind report|portal). Rückgabe: Anzahl neu eingerichtet. */
function gszLinksSetupAll(PDO $pdo,int $admin,string $kind):int {
 if(!in_array($kind,['report','portal'],true))throw new RuntimeException('Unbekannter Link.');$n=0;
 foreach(gszLinksOverview($pdo) as $r)if(empty($r[$kind.'_token'])){gszLinkRenew($pdo,$admin,(int)$r['id'],$kind);$n++;}return $n;
}

/** Meldungs-Portal der Hausverwaltung: offene Meldungen und Erledigte der letzten 60 Tage (ohne Verworfene). */
function gszPortalDefects(PDO $pdo,int $property):array {
 $q=$pdo->prepare("SELECT d.*,n.done_at note_done FROM defect_reports d LEFT JOIN quick_notes n ON n.id=d.note_id WHERE d.property_id=? AND d.status<>'verworfen' AND (d.status IN ('neu','uebernommen') OR d.handled_at>=UTC_TIMESTAMP()-INTERVAL 60 DAY OR n.done_at>=NOW()-INTERVAL 60 DAY) ORDER BY d.created_at DESC,d.id DESC LIMIT 200");
 $q->execute([$property]);$open=[];$done=[];foreach($q->fetchAll(PDO::FETCH_ASSOC) as $d){if(gszDefectState($d)==='erledigt')$done[]=$d;else $open[]=$d;}return ['open'=>$open,'done'=>$done];
}
/** Abhaken/Wieder öffnen aus dem Portal – nur für Meldungen genau dieses Hauses. */
function gszPortalDefectAction(PDO $pdo,int $property,int $id,string $action):void {
 $d=gszDefect($pdo,$id);if(!$d||(int)$d['property_id']!==$property||$d['status']==='verworfen')throw new RuntimeException('Meldung nicht gefunden.');
 if($action==='done')gszDefectDone($pdo,null,$id,'portal');elseif($action==='reopen')gszDefectReopen($pdo,null,$id,'portal');else throw new RuntimeException('Aktion unbekannt.');
}
