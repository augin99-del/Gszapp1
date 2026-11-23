<?php
declare(strict_types=1);
/**
 * Merkliste (Schema 390): schnelle To-dos und „Rechnungen offen“.
 * Rechnungen entstehen weiter in Lexware – hier steht nur die Erinnerung, dass noch eine Rechnung fehlt.
 * Erledigte Einträge bleiben 30 Tage sichtbar und werden danach nur ausgeblendet (nicht gelöscht).
 */
require_once __DIR__.'/platform.php';
const GSZ_NOTE_KINDS=['todo'=>'To-do','invoice'=>'Rechnung offen'];
const GSZ_NOTE_DONE_DAYS=30;
/** Allgemeine Wörter in Objektnamen, die allein kein Objekt kennzeichnen. */
const GSZ_NOTE_GENERIC=['wohnanlage','anlage','objekt','gebaeude','mehrfamilienhaus','haus','hausverwaltung','verwaltung','wohnhaus','treppenhaus','garage','tiefgarage','buero','praxis','privat','eigentuemergemeinschaft','weg'];

/** Vergleichsform: klein, Umlaute ausgeschrieben, „Straße/Strasse/Str.“ → „str“, nur Buchstaben/Ziffern/Leerzeichen. */
function gszNoteNorm(string $s):string {
 $s=mb_strtolower($s,'UTF-8');$s=strtr($s,['ä'=>'ae','ö'=>'oe','ü'=>'ue','ß'=>'ss','é'=>'e','è'=>'e']);
 $s=preg_replace('/(strasse|str\.)/u','str',$s)??$s;$s=preg_replace('/[^a-z0-9]+/u',' ',$s)??$s;return trim(preg_replace('/\s+/',' ',$s)??$s);
}
/** Kennwörter eines Objekts: Straßenname aus der Adresse (ohne Hausnummer/PLZ/Ort) und markante Wörter aus dem Namen. */
function gszNoteKeys(array $p):array {
 $keys=[];$street=trim((string)preg_split('/[,\n]/',(string)$p['address'])[0]);
 if($street!==''){$name=trim(preg_replace('/\s*\d+.*$/u','',$street)??'');if($name!=='')$keys[]=gszNoteNorm($name);}
 foreach(preg_split('/[,\n]/',(string)$p['name']) as $part){$part=trim(preg_replace('/\s*\d+.*$/u','',$part)??'');$n=gszNoteNorm($part);if(mb_strlen($n)>=5)$keys[]=$n;foreach(explode(' ',$n) as $w)if(mb_strlen($w)>=5&&!in_array($w,GSZ_NOTE_GENERIC,true))$keys[]=$w;}
 return array_values(array_unique(array_filter($keys,fn($k)=>mb_strlen($k)>=4)));
}
/** Hausnummer, falls im Text direkt hinter einem Kennwort genannt („Fröbelstraße 12“). */
function gszNoteNumberAfter(string $text,string $key):?string {
 return preg_match('/\b'.preg_quote($key,'/').'\s*(\d+\s*[a-z]?)\b/u',$text,$m)?str_replace(' ','',$m[1]):null;
}
/**
 * Erkennt das Objekt aus dem Text („Loch Wand Fontänenstraße“). Toleriert kleine Tippfehler (Fontainestraße).
 * Liefert nur ein eindeutiges Ergebnis; bei mehreren gleich guten Treffern null (dann wählt man selbst).
 */
function gszNoteDetectProperty(PDO $pdo,string $text):?array {
 $t=gszNoteNorm($text);if($t==='')return null;$words=array_values(array_filter(explode(' ',$t),fn($w)=>mb_strlen($w)>=4));
 $best=[];$bestScore=0;
 foreach($pdo->query('SELECT id,name,address FROM properties ORDER BY name')->fetchAll(PDO::FETCH_ASSOC) as $p){
  $score=0;$numberHit=false;
  foreach(gszNoteKeys($p) as $key){
   if(preg_match('/(^| )'.preg_quote($key,'/').'( |$)/u',$t)){$score=max($score,100);}
   elseif(!str_contains($key,' ')&&mb_strlen($key)>=7){foreach($words as $w){if(mb_strlen($w)<6)continue;$d=levenshtein($w,$key);if($d<=2||($d<=3&&mb_strlen($key)>=12))$score=max($score,80-$d*5);}}
   if($score>0){$n=gszNoteNumberAfter($t,$key);if($n!==null&&preg_match('/\b'.preg_quote($n,'/').'\b/u',gszNoteNorm((string)$p['address'].' '.(string)$p['name'])))$numberHit=true;}
  }
  if($score<=0)continue;if($numberHit)$score+=10;
  if($score>$bestScore){$bestScore=$score;$best=[$p];}elseif($score===$bestScore)$best[]=$p;
 }
 return count($best)===1?['id'=>(int)$best[0]['id'],'name'=>(string)$best[0]['name']]:null;
}
/** Fälligkeit aus den Schnellwahl-Knöpfen: heute, morgen, diese Woche (Freitag), Datum oder „irgendwann“ (leer). */
function gszNoteDue(string $choice,string $date='',?string $today=null):?string {
 $today??=date('Y-m-d');$d=new DateTimeImmutable($today);
 return match($choice){
  'today'=>$today,'tomorrow'=>$d->modify('+1 day')->format('Y-m-d'),
  'week'=>((int)$d->format('N')>=5?$d:$d->modify('friday this week'))->format('Y-m-d'),
  'date'=>preg_match('/^\d{4}-\d{2}-\d{2}$/D',$date)&&checkdate((int)substr($date,5,2),(int)substr($date,8,2),(int)substr($date,0,4))?$date:null,
  default=>null};
}
function gszNotePhotoDir():string {
 $dir=__DIR__.'/uploads/note_photos';if(!is_dir($dir)&&!@mkdir($dir,0700,true)&&!is_dir($dir))throw new RuntimeException('Fotoverzeichnis kann nicht erstellt werden.');
 $real=realpath($dir);if(!$real)throw new RuntimeException('Fotoverzeichnis fehlt.');return $real;
}
/** Speichert ein hochgeladenes Foto (JPEG/PNG/WebP, max. 8 MB) außerhalb des Webverzeichnisses. */
function gszNoteStorePhoto(array $file):array {
 require_once __DIR__.'/photo_storage.php';[$mime,$ext]=photoValidate($file);$name=bin2hex(random_bytes(16)).'.'.$ext;$path=gszNotePhotoDir().'/'.$name;
 if(!move_uploaded_file((string)$file['tmp_name'],$path))throw new RuntimeException('Foto konnte nicht gespeichert werden.');@chmod($path,0600);return [$name,$mime];
}
function gszNoteRemovePhoto(?string $name):void {if($name&&preg_match('/^[a-f0-9]{32}\.(jpg|png|webp)$/D',$name)){$p=gszNotePhotoDir().'/'.$name;if(is_file($p)&&!is_link($p))@unlink($p);}}
function gszNoteHasUpload(?array $file):bool {return is_array($file)&&($file['error']??UPLOAD_ERR_NO_FILE)!==UPLOAD_ERR_NO_FILE;}
function gszNoteText(mixed $v,int $max):string {return mb_substr(trim(preg_replace('/[\x00-\x08\x0B\x0C\x0E-\x1F\x7F]/u','',(string)$v)??''),0,$max);}

/**
 * Neuer Eintrag. property: 'auto' (aus dem Text erkennen), '' (ohne Objekt) oder Objekt-ID.
 * Rückgabe: id und das zugeordnete Objekt (für die Bestätigung „Zugeordnet: …“).
 */
function gszNoteAdd(PDO $pdo,int $admin,array $in,?array $photo=null):array {
 $kind=(string)($in['kind']??'todo');if(!isset(GSZ_NOTE_KINDS[$kind]))throw new RuntimeException('Unbekannte Art.');
 $title=gszNoteText($in['title']??'',190);if($title==='')throw new RuntimeException('Bitte kurz aufschreiben, worum es geht.');
 $prop=(string)($in['property_id']??'auto');$property=null;
 if($prop==='auto')$property=gszNoteDetectProperty($pdo,$title);
 elseif($prop!==''&&ctype_digit($prop)){$q=$pdo->prepare('SELECT id,name FROM properties WHERE id=?');$q->execute([(int)$prop]);$r=$q->fetch(PDO::FETCH_ASSOC);if(!$r)throw new RuntimeException('Objekt nicht gefunden.');$property=['id'=>(int)$r['id'],'name'=>(string)$r['name']];}
 $choice=(string)($in['due']??'');if(trim((string)($in['due_date']??''))!=='')$choice='date';
 $due=$kind==='todo'?gszNoteDue($choice,trim((string)($in['due_date']??''))):null;
 if($kind==='todo'&&$choice==='date'&&$due===null)throw new RuntimeException('Datum ungültig.');
 [$photoName,$photoMime]=gszNoteHasUpload($photo)?gszNoteStorePhoto($photo):[null,null];
 try{$q=$pdo->prepare('INSERT INTO quick_notes(kind,title,property_id,due_date,material,note,photo_name,photo_mime) VALUES(?,?,?,?,?,?,?,?)');
  $q->execute([$kind,$title,$property['id']??null,$due,$kind==='todo'?gszNoteText($in['material']??'',500):'',gszNoteText($in['note']??'',500),$photoName,$photoMime]);}
 catch(Throwable $e){gszNoteRemovePhoto($photoName);throw $e;}
 $id=(int)$pdo->lastInsertId();gszAudit($pdo,$admin,'note_added',['id'=>$id,'kind'=>$kind]);
 return ['id'=>$id,'property'=>$property,'due_date'=>$due];
}
function gszNoteGet(PDO $pdo,int $id):?array {
 $q=$pdo->prepare('SELECT n.*,p.name property_name,p.address property_address FROM quick_notes n LEFT JOIN properties p ON p.id=n.property_id WHERE n.id=?');$q->execute([$id]);$r=$q->fetch(PDO::FETCH_ASSOC);return $r?:null;
}
function gszNoteCheckVersion(array $row,mixed $version):void {
 if($version!==null&&$version!==''&&(int)$version!==(int)$row['row_version'])throw new RuntimeException('Der Eintrag wurde inzwischen geändert. Bitte Seite neu laden.');
}
/** Bearbeiten: Text, Objekt, Datum, Material, Notiz-Zeile, Foto ersetzen oder entfernen. */
function gszNoteUpdate(PDO $pdo,int $admin,int $id,array $in,?array $photo=null):array {
 $row=gszNoteGet($pdo,$id);if(!$row)throw new RuntimeException('Eintrag nicht gefunden.');gszNoteCheckVersion($row,$in['row_version']??null);
 $title=gszNoteText($in['title']??'',190);if($title==='')throw new RuntimeException('Der Text darf nicht leer sein.');
 $prop=(string)($in['property_id']??'');$propertyId=null;
 if($prop!==''){if(!ctype_digit($prop))throw new RuntimeException('Objekt nicht gefunden.');$q=$pdo->prepare('SELECT id FROM properties WHERE id=?');$q->execute([(int)$prop]);if(!$q->fetchColumn())throw new RuntimeException('Objekt nicht gefunden.');$propertyId=(int)$prop;}
 $due=$row['kind']==='todo'?gszNoteDue(($in['due_date']??'')!==''?'date':'',(string)($in['due_date']??'')):null;
 $material=$row['kind']==='todo'?gszNoteText($in['material']??'',500):'';
 $photoName=$row['photo_name'];$photoMime=$row['photo_mime'];$old=null;
 if(gszNoteHasUpload($photo)){[$photoName,$photoMime]=gszNoteStorePhoto($photo);$old=$row['photo_name'];}
 elseif(!empty($in['remove_photo'])){$photoName=null;$photoMime=null;$old=$row['photo_name'];}
 $q=$pdo->prepare('UPDATE quick_notes SET material_done=IF(material=?,material_done,0),title=?,property_id=?,due_date=?,material=?,note=?,photo_name=?,photo_mime=?,row_version=row_version+1 WHERE id=? AND row_version=?');
 $q->execute([$material,$title,$propertyId,$due,$material,gszNoteText($in['note']??'',500),$photoName,$photoMime,$id,$row['row_version']]);
 if($q->rowCount()!==1){if($photoName!==$row['photo_name'])gszNoteRemovePhoto($photoName);throw new RuntimeException('Der Eintrag wurde inzwischen geändert. Bitte Seite neu laden.');}
 if($old&&$old!==$photoName)gszNoteRemovePhoto($old);
 return gszNoteGet($pdo,$id);
}
/** Abhaken / wieder öffnen. Bei „Rechnung offen“ heißt erledigt: Rechnung in Lexware geschrieben. */
function gszNoteSetDone(PDO $pdo,int $admin,int $id,bool $done,mixed $version=null):array {
 $row=gszNoteGet($pdo,$id);if(!$row)throw new RuntimeException('Eintrag nicht gefunden.');gszNoteCheckVersion($row,$version);
 $q=$pdo->prepare('UPDATE quick_notes SET done_at='.($done?'COALESCE(done_at,NOW())':'NULL').',row_version=row_version+1 WHERE id=? AND row_version=?');$q->execute([$id,$row['row_version']]);
 if($q->rowCount()!==1)throw new RuntimeException('Der Eintrag wurde inzwischen geändert. Bitte Seite neu laden.');
 if($row['kind']==='invoice')gszAudit($pdo,$admin,$done?'invoice_note_done':'invoice_note_reopened',['id'=>$id]);
 return gszNoteGet($pdo,$id);
}
function gszNoteMaterialDone(PDO $pdo,int $id,bool $done,mixed $version=null):array {
 $row=gszNoteGet($pdo,$id);if(!$row||$row['material']==='')throw new RuntimeException('Eintrag nicht gefunden.');gszNoteCheckVersion($row,$version);
 $q=$pdo->prepare('UPDATE quick_notes SET material_done=?,row_version=row_version+1 WHERE id=? AND row_version=?');$q->execute([$done?1:0,$id,$row['row_version']]);
 if($q->rowCount()!==1)throw new RuntimeException('Der Eintrag wurde inzwischen geändert. Bitte Seite neu laden.');return gszNoteGet($pdo,$id);
}
function gszNoteDelete(PDO $pdo,int $admin,int $id):void {
 $row=gszNoteGet($pdo,$id);if(!$row)return;$pdo->prepare('DELETE FROM quick_notes WHERE id=?')->execute([$id]);gszNoteRemovePhoto($row['photo_name']);gszAudit($pdo,$admin,'note_deleted',['id'=>$id,'kind'=>$row['kind']]);
}
/**
 * „Rechnung nötig?“ nach dem Abhaken: legt genau eine Erinnerung je erledigter Aufgabe bzw. je To-do an (doppelt tippen schadet nicht).
 * $source: ['task',id] oder ['note',id]. Rückgabe: id und ob sie schon existierte.
 */
function gszNoteInvoiceFrom(PDO $pdo,int $admin,string $source,int $id):array {
 if($source==='task'){$q=$pdo->prepare('SELECT title,property_id FROM tasks WHERE id=?');$col='task_id';}
 elseif($source==='note'){$q=$pdo->prepare("SELECT title,property_id FROM quick_notes WHERE id=? AND kind='todo'");$col='source_note_id';}
 else throw new RuntimeException('Unbekannte Quelle.');
 $q->execute([$id]);$src=$q->fetch(PDO::FETCH_ASSOC);if(!$src)throw new RuntimeException('Arbeit nicht gefunden.');
 $e=$pdo->prepare("SELECT id FROM quick_notes WHERE $col=?");$e->execute([$id]);$existing=$e->fetchColumn();if($existing)return ['id'=>(int)$existing,'existed'=>true];
 $pdo->prepare("INSERT IGNORE INTO quick_notes(kind,title,property_id,$col) VALUES('invoice',?,?,?)")->execute([mb_substr((string)$src['title'],0,190),$src['property_id'],$id]);
 $e->execute([$id]);$new=(int)$e->fetchColumn();gszAudit($pdo,$admin,'invoice_note_added',['id'=>$new,'from'=>$source]);return ['id'=>$new,'existed'=>false];
}
/** Offene Einträge zuerst (überfällig → heute → ohne Datum → später), dann Erledigte der letzten 30 Tage. */
function gszNotesList(PDO $pdo,string $kind,?int $propertyId=null,bool $withDone=true):array {
 $where='n.kind=?';$args=[$kind];if($propertyId!==null){$where.=' AND n.property_id=?';$args[]=$propertyId;}
 $q=$pdo->prepare("SELECT n.*,p.name property_name FROM quick_notes n LEFT JOIN properties p ON p.id=n.property_id WHERE $where AND n.done_at IS NULL ORDER BY CASE WHEN n.due_date IS NULL THEN 1 WHEN n.due_date<=CURDATE() THEN 0 ELSE 2 END,n.due_date,n.created_at,n.id");$q->execute($args);$open=$q->fetchAll(PDO::FETCH_ASSOC);
 $done=[];if($withDone){$q=$pdo->prepare("SELECT n.*,p.name property_name FROM quick_notes n LEFT JOIN properties p ON p.id=n.property_id WHERE $where AND n.done_at IS NOT NULL AND n.done_at>=NOW()-INTERVAL ".GSZ_NOTE_DONE_DAYS." DAY ORDER BY n.done_at DESC,n.id DESC");$q->execute($args);$done=$q->fetchAll(PDO::FETCH_ASSOC);}
 return ['open'=>$open,'done'=>$done];
}
/** Einkaufsliste: Material offener To-dos, noch nicht besorgt. */
function gszNotesShopping(PDO $pdo):array {
 return $pdo->query("SELECT n.*,p.name property_name FROM quick_notes n LEFT JOIN properties p ON p.id=n.property_id WHERE n.kind='todo' AND n.done_at IS NULL AND n.material<>'' ORDER BY n.material_done,(n.due_date IS NULL),n.due_date,n.created_at")->fetchAll(PDO::FETCH_ASSOC);
}
function gszNotesCounts(PDO $pdo):array {
 $r=$pdo->query("SELECT SUM(kind='todo') todo,SUM(kind='invoice') invoice,SUM(kind='todo' AND material<>'' AND material_done=0) shopping,SUM(kind='todo' AND due_date<CURDATE()) late FROM quick_notes WHERE done_at IS NULL")->fetch(PDO::FETCH_ASSOC);
 return array_map('intval',$r?:[])+['todo'=>0,'invoice'=>0,'shopping'=>0,'late'=>0];
}
/** „seit 3 Tagen“, „heute“, „fällig morgen“ – kurz und verständlich. */
function gszNoteAge(array $n,?string $today=null):string {
 $today??=date('Y-m-d');$t=new DateTimeImmutable($today);
 if($n['done_at'])return 'erledigt am '.(new DateTimeImmutable((string)$n['done_at']))->format('d.m.');
 if($n['kind']==='todo'&&$n['due_date']){$d=new DateTimeImmutable((string)$n['due_date']);$diff=(int)$t->diff($d)->format('%r%a');
  return match(true){$diff<-1=>'seit '.(-$diff).' Tagen überfällig',$diff===-1=>'seit gestern überfällig',$diff===0=>'heute',$diff===1=>'morgen',$diff<7=>gszNoteWeekday($d),default=>'bis '.$d->format('d.m.')};}
 $c=new DateTimeImmutable(substr((string)$n['created_at'],0,10));$age=(int)$c->diff($t)->format('%a');
 return $age===0?'notiert heute':($age===1?'notiert gestern':'notiert vor '.$age.' Tagen');
}
function gszNoteWeekday(DateTimeImmutable $d):string {return ['Montag','Dienstag','Mittwoch','Donnerstag','Freitag','Samstag','Sonntag'][(int)$d->format('N')-1];}
function gszNoteLate(array $n,?string $today=null):bool {return !$n['done_at']&&$n['kind']==='todo'&&$n['due_date']&&$n['due_date']<($today??date('Y-m-d'));}
/** Rücksprung nur auf eigene Seiten (relativer Dateiname, keine Pfade oder fremden Adressen). */
function gszNoteReturn(mixed $r):string {$r=(string)$r;return preg_match('/^[a-z_]+\.php(\?[A-Za-z0-9=&_%.\-]*)?(#[a-z0-9-]+)?$/D',$r)?$r:'notes.php';}
