<?php
declare(strict_types=1);
/**
 * Geplante Push-Benachrichtigungen (Schema 440), ausgelöst vom Wecker (tick.php) oder beim Öffnen der Zentrale:
 * „Mein Tag“ (morgens), Tonnen für morgen (abends), Termin-Erinnerungen, fehlende Mieten, neue Mails, Systemprobleme, Frost.
 * Jede Nachricht hat einen festen Schlüssel → kommt höchstens einmal, auch wenn der Wecker öfter läuft.
 */
require_once __DIR__.'/push.php';
const GSZ_WASTE_NAMES=['rest'=>'Restmüll','bio'=>'Bio','papier'=>'Papier','gelb'=>'Gelbe Tonne','glas'=>'Glas','sperr'=>'Sperrmüll'];

/** Uhrzeit HH:MM aus den Einstellungen, sonst Standard. */
function gszPushTime(PDO $pdo,string $key,string $default):string {$v=gszSetting($pdo,$key);return preg_match('/^([01]\d|2[0-3]):[0-5]\d$/D',$v)?$v:$default;}
function gszWasteName(string $f):string {$k=mb_strtolower($f);foreach(GSZ_WASTE_NAMES as $n=>$l)if(str_contains($k,$n))return $l;return mb_convert_case($f,MB_CASE_TITLE);}

/** Alle fälligen geplanten Nachrichten. $now: Zeitpunkt (Berlin), für Tests. Rückgabe: Schlüssel der erzeugten Nachrichten. */
function gszPushDigests(PDO $pdo,?DateTimeImmutable $now=null,?callable $sender=null):array {
 if(!gszPushReady($pdo))return [];$tz=new DateTimeZone('Europe/Berlin');$now=($now??new DateTimeImmutable('now',$tz))->setTimezone($tz);
 $today=$now->format('Y-m-d');$hm=$now->format('H:i');$sent=[];$send=function(string $kind,string $key,string $t,string $b,string $u)use($pdo,$sender,&$sent){if(!gszPushEnabled($pdo,$kind))return;$k=$kind.':'.$key;$exists=$pdo->prepare('SELECT 1 FROM push_events WHERE event_key=?');$exists->execute([$k]);if($exists->fetchColumn())return;gszPushNotify($pdo,$kind,$k,$t,$b,$u,$sender);$sent[]=$k;};
 // ☀️ Mein Tag – ab der eingestellten Uhrzeit, bis 11 Uhr (danach lohnt es nicht mehr)
 if($hm>=gszPushTime($pdo,'push_day_time','06:30')&&$hm<'11:00'){
  $q=$pdo->prepare('SELECT SUM(due_date=?) heute,SUM(due_date<?) alt FROM tasks WHERE done=0');$q->execute([$today,$today]);$t=$q->fetch(PDO::FETCH_ASSOC);
  $a=$pdo->prepare('SELECT start_time,title FROM planning_appointments WHERE day=? AND cancelled_at IS NULL ORDER BY start_time');$a->execute([$today]);$apps=$a->fetchAll(PDO::FETCH_ASSOC);
  $inv=0;try{$inv=(int)$pdo->query("SELECT COUNT(*) FROM quick_notes WHERE kind='invoice' AND done_at IS NULL")->fetchColumn();}catch(Throwable $e){}
  $parts=[(int)$t['heute']===1?'1 Aufgabe':(int)$t['heute'].' Aufgaben'];if((int)$t['alt'])$parts[]=(int)$t['alt'].' von Vortagen offen';
  if($apps)$parts[]=count($apps)===1?'Termin '.substr((string)$apps[0]['start_time'],0,5).' '.$apps[0]['title']:count($apps).' Termine (ab '.substr((string)$apps[0]['start_time'],0,5).')';
  if($inv)$parts[]=$inv===1?'1 Rechnung offen':$inv.' Rechnungen offen';
  $send('day',$today,'☀️ Dein Tag',mb_strimwidth(implode(' · ',$parts),0,220,'…'),'index.php');
 }
 // 🗑️ Tonnen für morgen – abends ab der eingestellten Uhrzeit
 if($hm>=gszPushTime($pdo,'push_waste_time','18:00')){
  $tm=$now->modify('+1 day')->format('Y-m-d');
  try{$q=$pdo->prepare("SELECT fraction,COUNT(DISTINCT property_id) n FROM waste_events WHERE collection_date=? AND status<>'veraltet' GROUP BY fraction ORDER BY n DESC");$q->execute([$tm]);$rows=$q->fetchAll(PDO::FETCH_ASSOC);}catch(Throwable $e){$rows=[];}
  if($rows){$txt=implode(' · ',array_map(fn($r)=>gszWasteName((string)$r['fraction']).' ('.(int)$r['n'].' Objekt'.((int)$r['n']===1?'':'e').')',$rows));$send('waste',$tm,'🗑️ Morgen Abfuhr',$txt,'waste.php');}
 }
 // 📅 Termin-Erinnerungen (Vorlauf aus dem Termin, sonst 60 Min.)
 try{$q=$pdo->prepare('SELECT id,day,start_time,title,address,reminder_minutes FROM planning_appointments WHERE cancelled_at IS NULL AND start_time IS NOT NULL AND day BETWEEN ? AND ?');$q->execute([$today,$now->modify('+1 day')->format('Y-m-d')]);
  foreach($q->fetchAll(PDO::FETCH_ASSOC) as $ap){$start=new DateTimeImmutable($ap['day'].' '.$ap['start_time'],$tz);$lead=(int)($ap['reminder_minutes']??0)?:60;
   if($now>=$start->modify('-'.$lead.' minutes')&&$now<$start)$send('appt',$ap['id'].':'.$ap['day'],'📅 '.$start->format('H:i').' '.mb_substr((string)$ap['title'],0,80),$ap['address']?:'In '.max(1,(int)round(($start->getTimestamp()-$now->getTimestamp())/60)).' Min.','appointments.php');}}catch(Throwable $e){}
 // 🏠 Mieten fehlen – ab dem 6., einmal je Monat und Anzahl
 if((int)$now->format('j')>=6&&$hm>='09:00'){try{require_once __DIR__.'/estate_rent_match.php';$e=estateAttention($pdo,$now);$m=(int)($e['missing_rents']??0);
  if($m)$send('rent',$now->format('Y-m').':'.$m,'🏠 Mieten prüfen',$m===1?'1 Miete fehlt oder ist unvollständig.':$m.' Mieten fehlen oder sind unvollständig.','estate_bank.php');}catch(Throwable $e){}}
 // ✉️ Neue Mails (Posteingang, ungelesen) – fasst zusammen
 try{$last=(int)gszSetting($pdo,'push_mail_last');$q=$pdo->prepare("SELECT MAX(m.id) mx,COUNT(*) n FROM mail_messages m JOIN mail_folders f ON f.id=m.folder_id WHERE f.name='INBOX' AND m.active=1 AND m.seen=0 AND m.id>?");$q->execute([$last]);$r=$q->fetch(PDO::FETCH_ASSOC);
  if((int)$r['n']){gszSettingSet($pdo,'push_mail_last',(string)(int)$r['mx']);if($last>0)$send('mail',(string)$r['mx'],'✉️ '.((int)$r['n']===1?'1 neue Mail':(int)$r['n'].' neue Mails'),'Im Posteingang der Zentrale.','mail.php');}}catch(Throwable $e){}
 // ⚠️ Systemüberwachung
 try{require_once __DIR__.'/mail_credentials.php';if(gszMailAuthBlockedNow())$send('system','mailauth:'.$today,'⚠️ Mail-Passwort abgelehnt','Bitte in der Zentrale neu eintragen – bis dahin kommen keine Mails und Anfragen an.','connections.php');}catch(Throwable $e){}
 try{foreach($pdo->query("SELECT job_key,last_error_code FROM automation_state WHERE status='BLOCKED' AND last_finished>=UTC_TIMESTAMP()-INTERVAL 1 DAY")->fetchAll(PDO::FETCH_ASSOC) as $j)$send('system','job:'.$j['job_key'].':'.$today,'⚠️ Hintergrundaufgabe gestoppt',(string)$j['job_key'].': '.(string)$j['last_error_code'],'system_check.php');}catch(Throwable $e){}
 // 📨 Angebote nachfassen, Vertragsfristen, Schlüssel – einmal täglich ab 9 Uhr (je Hinweis nur einmal am Tag)
 if($hm>='09:00'){try{require_once __DIR__.'/offers.php';require_once __DIR__.'/keys.php';$hints=array_merge(gszOfferAttention($pdo),gszContractAttention($pdo),gszKeyAttention($pdo));if($hints)$send('offers',$today.':'.count($hints),'📨 '.(count($hints)===1?'1 Sache zum Nachfassen':count($hints).' Sachen zum Nachfassen'),implode(' · ',array_map(fn($h)=>$h[1],array_slice($hints,0,3))),$hints[0][0]);}catch(Throwable $e){}}
 // 💾 Datensicherung: fehlt seit 3 Tagen oder 30 Tage nicht heruntergeladen (einmal je Woche)
 if($hm>='10:00'){try{require_once __DIR__.'/backup_auto.php';foreach(gszBackupAttention($pdo) as [$u,$t])$send('system','backup:'.$now->format('o-W'),'💾 Datensicherung',$t,$u);}catch(Throwable $e){}}
 // 🌡️ Frost-Hinweis (nur wenn eingeschaltet)
 try{foreach($pdo->query("SELECT * FROM weather_alerts WHERE winter=1 AND (expires IS NULL OR expires>UTC_TIMESTAMP())")->fetchAll(PDO::FETCH_ASSOC) as $a){require_once __DIR__.'/weather.php';$send('frost',(string)$a['alert_id'],'🌡️ Frost – '.$a['area'],gszWeatherWhen($a).'. Auf Glätte achten.','index.php#wetter');}}catch(Throwable $e){}
 return $sent;
}
