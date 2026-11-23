<?php
declare(strict_types=1);
/**
 * Ferienwohnung (Schema 400): Belegungskalender von Airbnb und Booking.com (iCal-Export) → Reinigungen am Abreisetag.
 * Übernommen werden nur An- und Abreisedaten. Keine Gastnamen, Telefonnummern oder Buchungscodes.
 * Die Kalender-Links sind geheim (wer sie kennt, sieht die Belegung) und werden verschlüsselt gespeichert.
 */
require_once __DIR__.'/platform.php';require_once __DIR__.'/managed_config.php';require_once __DIR__.'/secret_vault.php';
const GSZ_STAY_SOURCES=['airbnb'=>'Airbnb','booking'=>'Booking.com'];
const GSZ_STAY_HORIZON_DAYS=120;

/** Welche Plattform? Nur exakt bekannte HTTPS-Hosts (kein beliebiger Abruf vom Server aus). */
function gszStaySource(string $url):array {
 $url=trim($url);if(strlen($url)>2048||preg_match('/[\x00-\x20\x7f]/',$url))throw new RuntimeException('Kalender-Link ungültig.');$u=parse_url($url);$host=strtolower((string)($u['host']??''));
 if(!$u||($u['scheme']??'')!=='https'||isset($u['user'])||isset($u['pass'])||($u['port']??443)!==443)throw new RuntimeException('Bitte den kompletten Link mit https:// einfügen.');
 if(preg_match('/^(www\.)?airbnb\.(de|com|at|ch|fr|it|es|nl|be|dk|pl|co\.uk)$/D',$host))return ['airbnb',$host];
 if(in_array($host,['ical.booking.com','admin.booking.com'],true))return ['booking',$host];
 throw new RuntimeException('Das ist kein Kalender-Link von Airbnb oder Booking.com.');
}
function gszStayKey():string {
 gszVaultCrypto();$dir=__DIR__.'/secrets';gszPrivateDirectory($dir);$file=$dir.'/stay.key';
 if(!is_file($file)){$k=random_bytes(32);try{gszPrivateWrite($file,$k);}finally{sodium_memzero($k);}}return gszVaultRead($file,32);
}
function gszStaySeal(string $url):string {$key=gszStayKey();try{$n=random_bytes(24);return base64_encode($n.sodium_crypto_aead_xchacha20poly1305_ietf_encrypt($url,'GSZ-stay-v1',$n,$key));}finally{sodium_memzero($key);}}
function gszStayOpen(string $sealed):string {
 $raw=base64_decode($sealed,true);if($raw===false||strlen($raw)<41)throw new RuntimeException('Gespeicherter Kalender-Link beschädigt.');$key=gszStayKey();
 try{$plain=sodium_crypto_aead_xchacha20poly1305_ietf_decrypt(substr($raw,24),'GSZ-stay-v1',substr($raw,0,24),$key);}finally{sodium_memzero($key);}
 if($plain===false)throw new RuntimeException('Gespeicherter Kalender-Link nicht lesbar (Schlüssel fehlt?). Bitte Link neu eintragen.');return $plain;
}
/** Kurzer Wiedererkennungs-Hinweis ohne das Geheimnis: Host und die letzten 4 Zeichen. */
function gszStayHint(string $url):string {$u=parse_url($url);return strtolower((string)$u['host']).' …'.substr(preg_replace('/[^A-Za-z0-9]/','',$url),-4);}
function gszStayTime(mixed $v,string $default):string {$v=(string)$v;return preg_match('/^([01]\d|2[0-3]):[0-5]\d$/D',$v)?$v.':00':$default;}

function gszStayUnit(PDO $pdo,int $propertyId):?array {$q=$pdo->prepare('SELECT u.*,p.name property_name FROM stay_units u JOIN properties p ON p.id=u.property_id WHERE u.property_id=?');$q->execute([$propertyId]);$r=$q->fetch(PDO::FETCH_ASSOC);return $r?:null;}
function gszStayCalendars(PDO $pdo,int $propertyId):array {$q=$pdo->prepare('SELECT id,property_id,source,url_hint,last_fetch_at,last_status,last_error,stays_found FROM stay_calendars WHERE property_id=? ORDER BY id');$q->execute([$propertyId]);return $q->fetchAll(PDO::FETCH_ASSOC);}
/** Wohnung einrichten oder Zeiten ändern (Check-out, Check-in, Reinigungsdauer). */
function gszStaySetUnit(PDO $pdo,int $admin,int $propertyId,array $in):void {
 $q=$pdo->prepare('SELECT id FROM properties WHERE id=?');$q->execute([$propertyId]);if(!$q->fetchColumn())throw new RuntimeException('Objekt nicht gefunden.');
 $out=gszStayTime($in['checkout_time']??'','10:00:00');$inT=gszStayTime($in['checkin_time']??'','15:00:00');$dur=(int)($in['duration_minutes']??60);
 if($dur<15||$dur>600)throw new RuntimeException('Dauer bitte zwischen 15 und 600 Minuten.');if($inT<=$out)throw new RuntimeException('Check-in muss nach dem Check-out liegen.');
 $pdo->prepare('INSERT INTO stay_units(property_id,checkout_time,checkin_time,duration_minutes,enabled) VALUES(?,?,?,?,1) ON DUPLICATE KEY UPDATE checkout_time=VALUES(checkout_time),checkin_time=VALUES(checkin_time),duration_minutes=VALUES(duration_minutes),enabled=1,row_version=row_version+1')->execute([$propertyId,$out,$inT,$dur]);
 gszAudit($pdo,$admin,'stay_unit_saved',['property'=>$propertyId]);
}
function gszStayAddCalendar(PDO $pdo,int $admin,int $propertyId,string $url):array {
 if(!gszStayUnit($pdo,$propertyId))throw new RuntimeException('Erst die Ferienwohnung einrichten.');[$source]=gszStaySource($url);$url=trim($url);
 $n=$pdo->prepare('SELECT COUNT(*) FROM stay_calendars WHERE property_id=?');$n->execute([$propertyId]);if((int)$n->fetchColumn()>=6)throw new RuntimeException('Höchstens 6 Kalender je Wohnung.');
 $pdo->prepare('INSERT INTO stay_calendars(property_id,source,url_cipher,url_hint) VALUES(?,?,?,?)')->execute([$propertyId,$source,gszStaySeal($url),gszStayHint($url)]);
 $id=(int)$pdo->lastInsertId();gszAudit($pdo,$admin,'stay_calendar_added',['property'=>$propertyId,'source'=>$source]);return ['id'=>$id,'source'=>$source];
}
function gszStayRemoveCalendar(PDO $pdo,int $admin,int $propertyId,int $calendarId):void {
 $q=$pdo->prepare('DELETE FROM stay_calendars WHERE id=? AND property_id=?');$q->execute([$calendarId,$propertyId]);if(!$q->rowCount())throw new RuntimeException('Kalender nicht gefunden.');gszAudit($pdo,$admin,'stay_calendar_removed',['property'=>$propertyId]);
}

/** ICS lesen: nur Zeiträume. Airbnb: nur echte Buchungen („Reserved“), gesperrte Tage („Not available“) zählen nicht. Booking.com: alle Einträge. */
function gszStayParseIcs(string $ics,string $source):array {
 if(strlen($ics)>1048576||!str_starts_with(ltrim($ics,"\xEF\xBB\xBF\r\n\t "),'BEGIN:VCALENDAR'))throw new RuntimeException('Keine Kalenderdatei.');
 $ics=preg_replace("/\r?\n[ \t]/",'',str_replace("\r\n","\n",$ics))??'';$stays=[];$ev=null;
 foreach(explode("\n",$ics) as $line){$line=rtrim($line,"\r");
  if($line==='BEGIN:VEVENT'){$ev=[];continue;}
  if($line==='END:VEVENT'){
   if($ev!==null&&isset($ev['DTSTART'])){$start=gszStayIcsDate($ev['DTSTART']);$end=isset($ev['DTEND'])?gszStayIcsDate($ev['DTEND']):($start?(new DateTimeImmutable($start))->modify('+1 day')->format('Y-m-d'):null);
    $summary=strtolower($ev['SUMMARY']??'');$cancelled=strtoupper($ev['STATUS']??'')==='CANCELLED';
    $isStay=$source==='airbnb'?str_contains($summary,'reserved'):true;
    if($start&&$end&&$end>$start&&!$cancelled&&$isStay)$stays[]=['start'=>$start,'end'=>$end];
    if(count($stays)>2000)throw new RuntimeException('Kalender zu groß.');}
   $ev=null;continue;}
  if($ev!==null&&preg_match('/^(DTSTART|DTEND|SUMMARY|STATUS)(;[^:]*)?:(.*)$/',$line,$m))$ev[$m[1]]=$m[3];
 }
 usort($stays,fn($a,$b)=>[$a['start'],$a['end']]<=>[$b['start'],$b['end']]);return array_values(array_unique($stays,SORT_REGULAR));
}
/** Abruffehler verständlich formulieren (ohne Link oder technische Einzelheiten). */
function gszStayFetchMessage(string $m):string {
 return match(true){
  (bool)preg_match('/auflösbar|TLS|Verbindung|Zeitbudget|unterbrochen|Anfrage fehlgeschlagen/u',$m)=>'Kalender nicht erreichbar. Später erneut versuchen; bleibt es so, Link prüfen.',
  str_contains($m,'Weiterleitung')||str_contains($m,'nicht bestätigt;')=>'Die Plattform hat den Link nicht angenommen. Link bitte neu unter „Kalender exportieren“ kopieren.',
  str_contains($m,'Keine Kalenderdatei')||str_contains($m,'ICS')=>'Unter dem Link liegt kein Kalender. Link bitte neu kopieren.',
  str_contains($m,'Schlüssel')||str_contains($m,'beschädigt')=>'Gespeicherter Link nicht lesbar. Bitte Kalender entfernen und neu eintragen.',
  default=>mb_substr($m,0,200)};
}
function gszStayIcsDate(string $v):?string {return preg_match('/^(\d{4})(\d{2})(\d{2})/',trim($v),$m)&&checkdate((int)$m[2],(int)$m[3],(int)$m[1])?"$m[1]-$m[2]-$m[3]":null;}
/**
 * Reinigungstage aus allen Belegungen: am Abreisetag, wenn die Wohnung in dieser Nacht nicht weiter belegt ist.
 * Gästewechsel, wenn am selben Tag jemand anreist. Doppelte Einträge (dieselbe Buchung in beiden Kalendern) zählen einmal.
 * Rückgabe: [Datum => true|false (Gästewechsel)].
 */
function gszStayPlan(array $stays,string $today,int $horizon=GSZ_STAY_HORIZON_DAYS):array {
 $last=(new DateTimeImmutable($today))->modify('+'.$horizon.' days')->format('Y-m-d');$out=[];
 foreach($stays as $s){$d=$s['end'];if($d<$today||$d>$last)continue;$continues=false;$turn=false;
  foreach($stays as $o){if($o['start']<$d&&$o['end']>$d)$continues=true;if($o['start']===$d)$turn=true;}
  if(!$continues)$out[$d]=($out[$d]??false)||$turn;}
 ksort($out);return $out;
}
function gszStayTaskFields(array $unit,bool $turnover):array {
 $out=substr((string)$unit['checkout_time'],0,5);$in=substr((string)$unit['checkin_time'],0,5);
 return ['title'=>$turnover?'Reinigung Ferienwohnung – neue Gäste '.$in:'Reinigung Ferienwohnung (Abreise '.$out.')',
  'notes'=>'Automatisch aus dem Belegungskalender. Check-out '.$out.', Check-in '.$in.'.'.($turnover?' Gästewechsel: bis '.$in.' fertig sein.':' Danach ist die Wohnung frei.'),
  'priority'=>$turnover?'urgent':'high'];
}
/**
 * Abrufen und planen. $fetch(url, host): string – für Tests ersetzbar.
 * Schlägt ein Kalender fehl, wird mit seinem letzten bekannten Stand geplant; ohne jeden bekannten Stand wird nichts gelöscht.
 */
function gszStaySync(PDO $pdo,int $admin,int $propertyId,?callable $fetch=null,?string $today=null):array {
 $unit=gszStayUnit($pdo,$propertyId);if(!$unit||!$unit['enabled'])throw new RuntimeException('Ferienwohnung nicht eingerichtet.');
 $today??=(new DateTimeImmutable('today',new DateTimeZone('Europe/Berlin')))->format('Y-m-d');
 $fetch??=static function(string $url,string $host):string{require_once __DIR__.'/waste_sync.php';return wasteFetchRemote($url,$host);};
 $q=$pdo->prepare('SELECT * FROM stay_calendars WHERE property_id=? ORDER BY id');$q->execute([$propertyId]);$cals=$q->fetchAll(PDO::FETCH_ASSOC);if(!$cals)throw new RuntimeException('Noch kein Kalender-Link eingetragen.');
 $stays=[];$errors=[];$complete=true;
 foreach($cals as $c){
  try{$url=gszStayOpen((string)$c['url_cipher']);[$source,$host]=gszStaySource($url);$list=gszStayParseIcs($fetch($url,$host),$source);
   $pdo->prepare("UPDATE stay_calendars SET last_fetch_at=UTC_TIMESTAMP(),last_status='ok',last_error='',stays_found=?,stays_json=? WHERE id=?")->execute([count($list),json_encode($list),$c['id']]);$stays=[...$stays,...$list];}
  catch(Throwable $e){$msg=$e instanceof PDOException?'Datenbankfehler':gszStayFetchMessage($e->getMessage());$errors[]=GSZ_STAY_SOURCES[$c['source']].': '.$msg;
   $pdo->prepare("UPDATE stay_calendars SET last_fetch_at=UTC_TIMESTAMP(),last_status='error',last_error=? WHERE id=?")->execute([$msg,$c['id']]);
   $old=json_decode((string)($c['stays_json']??''),true);if(is_array($old))$stays=[...$stays,...$old];else $complete=false;}
 }
 $plan=gszStayPlan($stays,$today);$r=gszStayApply($pdo,$unit,$plan,$today,$complete);
 $pdo->prepare('UPDATE stay_units SET last_sync_at=UTC_TIMESTAMP() WHERE property_id=?')->execute([$propertyId]);
 if($r['created']||$r['cancelled']||$r['changed'])gszAudit($pdo,$admin,'stay_cleanings_synced',['property'=>$propertyId]+$r);
 return $r+['errors'=>$errors,'planned'=>array_keys($plan)];
}
/** Plan in Aufgaben umsetzen. Nur Tage ab heute; erledigte Reinigungen und von Hand gelöschte Aufgaben werden nicht angefasst. */
function gszStayApply(PDO $pdo,array $unit,array $plan,string $today,bool $mayCancel=true):array {
 $pid=(int)$unit['property_id'];$created=0;$cancelled=0;$changed=0;
 $pdo->beginTransaction();
 try{
  $q=$pdo->prepare('SELECT c.*,t.done task_done FROM stay_cleanings c LEFT JOIN tasks t ON t.id=c.task_id WHERE c.property_id=? AND c.clean_date>=? FOR UPDATE');$q->execute([$pid,$today]);$rows=[];foreach($q->fetchAll(PDO::FETCH_ASSOC) as $r)$rows[$r['clean_date']]=$r;
  foreach($rows as $d=>$r){if(isset($plan[$d])||!$mayCancel||$r['status']!=='planned')continue;if($r['task_id']&&(int)$r['task_done']===1)continue;
   if($r['task_id'])$pdo->prepare('DELETE FROM tasks WHERE id=? AND done=0')->execute([$r['task_id']]);$pdo->prepare("UPDATE stay_cleanings SET status='cancelled',task_id=NULL WHERE id=?")->execute([$r['id']]);$cancelled++;}
  foreach($plan as $d=>$turn){$f=gszStayTaskFields($unit,$turn);$r=$rows[$d]??null;
   if($r&&$r['status']==='planned'){
    if((int)$r['turnover']!==(int)$turn){$pdo->prepare('UPDATE stay_cleanings SET turnover=? WHERE id=?')->execute([$turn?1:0,$r['id']]);
     if($r['task_id']&&!(int)$r['task_done'])$pdo->prepare('UPDATE tasks SET title=?,notes=?,priority=?,row_version=row_version+1 WHERE id=? AND done=0')->execute([$f['title'],$f['notes'],$f['priority'],$r['task_id']]);$changed++;}
    continue;}
   $pdo->prepare('INSERT INTO tasks(property_id,title,due_date,duration_minutes,start_time,priority,plan_locked,window_start,window_end,notes) VALUES(?,?,?,?,?,?,1,?,?,?)')->execute([$pid,$f['title'],$d,(int)$unit['duration_minutes'],$unit['checkout_time'],$f['priority'],$d,$d,$f['notes']]);$task=(int)$pdo->lastInsertId();
   if($r)$pdo->prepare("UPDATE stay_cleanings SET status='planned',turnover=?,task_id=? WHERE id=?")->execute([$turn?1:0,$task,$r['id']]);
   else $pdo->prepare('INSERT INTO stay_cleanings(property_id,clean_date,turnover,task_id) VALUES(?,?,?,?)')->execute([$pid,$d,$turn?1:0,$task]);$created++;}
  $pdo->commit();
 }catch(Throwable $e){if($pdo->inTransaction())$pdo->rollBack();throw $e;}
 return ['created'=>$created,'cancelled'=>$cancelled,'changed'=>$changed];
}
/** Für die Hintergrundaufgabe: alle eingerichteten Wohnungen mit Kalender. */
function gszStaySyncAll(PDO $pdo,int $admin,?callable $fetch=null):array {
 $out=[];foreach($pdo->query('SELECT DISTINCT u.property_id,p.name FROM stay_units u JOIN properties p ON p.id=u.property_id JOIN stay_calendars c ON c.property_id=u.property_id WHERE u.enabled=1')->fetchAll(PDO::FETCH_ASSOC) as $u){
  try{$r=gszStaySync($pdo,$admin,(int)$u['property_id'],$fetch);$out[]=['property'=>$u['name'],'status'=>$r['errors']?'BLOCKED':'PASS','reason'=>implode('; ',$r['errors'])]+$r;}
  catch(Throwable $e){$out[]=['property'=>$u['name'],'status'=>'BLOCKED','reason'=>$e instanceof PDOException?'Datenbankfehler':$e->getMessage()];}}
 return $out;
}
function gszStayReady(PDO $pdo):bool {try{return (bool)$pdo->query("SELECT COUNT(*) FROM app_migrations WHERE version='400_ferienwohnung'")->fetchColumn();}catch(Throwable $e){return false;}}
function gszStayHasCalendars(PDO $pdo):bool {return gszStayReady($pdo)&&(bool)$pdo->query('SELECT 1 FROM stay_calendars c JOIN stay_units u ON u.property_id=c.property_id WHERE u.enabled=1 LIMIT 1')->fetchColumn();}
/** Ferienwohnung beenden: Kalender bleiben gespeichert, künftige offene Reinigungen werden entfernt. */
function gszStayDisable(PDO $pdo,int $admin,int $propertyId,?string $today=null):int {
 $unit=gszStayUnit($pdo,$propertyId);if(!$unit)return 0;$today??=(new DateTimeImmutable('today',new DateTimeZone('Europe/Berlin')))->format('Y-m-d');
 $r=gszStayApply($pdo,$unit,[],$today);$pdo->prepare('UPDATE stay_units SET enabled=0,row_version=row_version+1 WHERE property_id=?')->execute([$propertyId]);gszAudit($pdo,$admin,'stay_unit_disabled',['property'=>$propertyId]);return $r['cancelled'];
}
function gszStayUpcoming(PDO $pdo,int $propertyId,int $limit=8):array {
 $q=$pdo->prepare("SELECT c.clean_date,c.turnover,t.done FROM stay_cleanings c LEFT JOIN tasks t ON t.id=c.task_id WHERE c.property_id=? AND c.status='planned' AND c.task_id IS NOT NULL AND c.clean_date>=CURDATE() ORDER BY c.clean_date LIMIT ".max(1,min(50,$limit)));$q->execute([$propertyId]);return $q->fetchAll(PDO::FETCH_ASSOC);
}
/** Ist die Aufgabe eine automatische Ferienwohnungs-Reinigung? (dann keine Frage „Rechnung nötig?“) */
function gszStayIsCleaningTask(PDO $pdo,int $taskId):bool {if(!gszStayReady($pdo))return false;$q=$pdo->prepare('SELECT 1 FROM stay_cleanings WHERE task_id=?');$q->execute([$taskId]);return (bool)$q->fetchColumn();}
