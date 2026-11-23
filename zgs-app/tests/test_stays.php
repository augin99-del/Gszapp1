<?php
declare(strict_types=1);
// Ferienwohnung (Schema 400): Airbnb-/Booking-Kalender → Reinigungen. Synthetische Kalender, kein Netzabruf.
set_error_handler(static function($s,$m,$f,$l){if(error_reporting()&$s)throw new ErrorException($m,0,$s,$f,$l);return false;});
require dirname(__DIR__).'/private/install_core.php';require dirname(__DIR__).'/private/web_update.php';require_once dirname(__DIR__).'/private/stays.php';
$dsn=getenv('GSZ_TEST_DSN')?:'';if(!preg_match('/^mysql:unix_socket=[^;]+;dbname=gsz_test_stays_[a-z0-9_]+$/D',$dsn))throw new RuntimeException('Empty local test DB required.');
$p=new PDO($dsn,'root','',[PDO::ATTR_ERRMODE=>PDO::ERRMODE_EXCEPTION,PDO::ATTR_DEFAULT_FETCH_MODE=>PDO::FETCH_ASSOC,PDO::ATTR_EMULATE_PREPARES=>false]);if($p->query('SHOW TABLES')->fetchAll())throw new RuntimeException('Empty DB required.');
$keyFile=dirname(__DIR__).'/private/secrets/stay.key';$hadKey=is_file($keyFile);
$n=0;function st(bool $v,string $s):void{global $n;$n++;if(!$v)throw new RuntimeException($s);}
try{
gszInstallFresh($p);st(gszSchemaCurrent($p)&&gszStayReady($p),'fresh install has 400');$p->exec("INSERT INTO admins(email,password_hash) VALUES('owner@example.invalid','x')");
$p->exec("INSERT INTO properties(name,address) VALUES('Ferienwohnung Test','Testweg 1, 00000 Musterstadt')");$pid=(int)$p->lastInsertId();
$today='2026-11-23';$d=fn(int $k)=>(new DateTimeImmutable($today))->modify(($k>=0?"+":"").$k." days")->format('Ymd');$iso=fn(int $k)=>(new DateTimeImmutable($today))->modify(($k>=0?"+":"").$k." days")->format('Y-m-d');
$ev=fn(string $s,string $e,string $sum,string $extra='')=>"BEGIN:VEVENT\r\nDTSTAMP:20261123T000000Z\r\nDTSTART;VALUE=DATE:$s\r\nDTEND;VALUE=DATE:$e\r\nSUMMARY:$sum\r\nUID:x".md5($s.$e.$sum)."@example.invalid\r\nDESCRIPTION:Reservation URL: https://example.invalid/r/ABC\\nPhone Number (Last 4 Digits): 0000\r\n$extra"."END:VEVENT\r\n";
$cal=fn(string ...$e)=>"BEGIN:VCALENDAR\r\nVERSION:2.0\r\nPRODID:-//Synthetic//EN\r\n".implode('',$e)."END:VCALENDAR\r\n";
// Airbnb: zwei Buchungen mit Gästewechsel am Tag +5, eine gesperrte Zeit (zählt nicht), eine vergangene
$air=$cal($ev($d(2),$d(5),'Reserved'),$ev($d(5),$d(8),'Reserved'),$ev($d(8),$d(9),'Airbnb (Not available)'),$ev($d(-6),$d(-3),'Reserved'));
// Booking: eine eigene Buchung (+12..+14) und dieselbe wie Airbnb +2..+5 (Doppel durch Kalender-Abgleich)
$book=$cal($ev($d(12),$d(14),'CLOSED - Not available'),$ev($d(2),$d(5),'CLOSED - Not available'));
$x=gszStayParseIcs($air,'airbnb');st(count($x)===3&&$x[0]['start']===$iso(-6),'airbnb: only reservations, blocked days ignored');
st(count(gszStayParseIcs($book,'booking'))===2,'booking: all entries are stays');
st(gszStayParseIcs("BEGIN:VCALENDAR\r\n".$ev($d(1),$d(2),'Reserved',"STATUS:CANCELLED\r\n")."END:VCALENDAR",'airbnb')===[],'cancelled ignored');
st(gszStayParseIcs("BEGIN:VCALENDAR\r\nBEGIN:VEVENT\r\nDTSTART:".$d(1)."T150000Z\r\nDTEND:\r\n ".$d(3)."T100000Z\r\nSUMMARY:Reserved\r\nEND:VEVENT\r\nEND:VCALENDAR",'airbnb')===[['start'=>$iso(1),'end'=>$iso(3)]],'date-times and folded lines');
try{gszStayParseIcs('<html>','airbnb');st(false,'html');}catch(RuntimeException $e){st(true,'');}
$plan=gszStayPlan([...gszStayParseIcs($air,'airbnb'),...gszStayParseIcs($book,'booking')],$today);
st($plan===[$iso(5)=>true,$iso(8)=>false,$iso(14)=>false],'plan: turnover at +5, free after +8 and +14, duplicate counted once, past ignored');
// Links: nur Airbnb/Booking per HTTPS
foreach(['https://www.airbnb.de/calendar/ical/123.ics?s=abc'=>'airbnb','https://airbnb.com/calendar/ical/1.ics?s=x'=>'airbnb','https://ical.booking.com/v1/export?t=abc'=>'booking'] as $u=>$s)st(gszStaySource($u)[0]===$s,'source '.$s);
foreach(['http://www.airbnb.de/calendar/ical/1.ics','https://airbnb.de.evil.example/x.ics','https://example.invalid/cal.ics','https://user:pw@www.airbnb.de/x','https://www.airbnb.de:8443/x'] as $u){try{gszStaySource($u);st(false,'accepted '.$u);}catch(RuntimeException $e){st(true,'');}}
// Einrichten
try{gszStayAddCalendar($p,1,$pid,'https://www.airbnb.de/calendar/ical/123.ics?s=geheim123');st(false,'needs unit');}catch(RuntimeException $e){st(str_contains($e->getMessage(),'einrichten'),'calendar needs the unit first');}
try{gszStaySetUnit($p,1,$pid,['checkout_time'=>'15:00','checkin_time'=>'10:00','duration_minutes'=>60]);st(false,'order');}catch(RuntimeException $e){st(true,'check-in after check-out');}
gszStaySetUnit($p,1,$pid,['checkout_time'=>'10:00','checkin_time'=>'15:00','duration_minutes'=>'60']);
$airUrl='https://www.airbnb.de/calendar/ical/123.ics?s=geheim123';$bookUrl='https://ical.booking.com/v1/export?t=geheim456';
gszStayAddCalendar($p,1,$pid,$airUrl);gszStayAddCalendar($p,1,$pid,$bookUrl);
$raw=json_encode($p->query('SELECT * FROM stay_calendars')->fetchAll(),JSON_UNESCAPED_UNICODE);st(!str_contains($raw,'geheim')&&str_contains($raw,'www.airbnb.de …'),'links encrypted, only a hint stored');
$feeds=[$airUrl=>$air,$bookUrl=>$book];$hosts=[];$fetch=function(string $u,string $h)use(&$feeds,&$hosts){$hosts[]=$h;if(!isset($feeds[$u]))throw new RuntimeException('Kalenderverbindung/TLS nicht bestätigt.');return $feeds[$u];};
$r=gszStaySync($p,1,$pid,$fetch,$today);st($r['created']===3&&!$r['errors']&&$hosts===['www.airbnb.de','ical.booking.com'],'three cleanings created, fetched only the exact hosts');
$t=$p->query("SELECT t.*,c.turnover FROM stay_cleanings c JOIN tasks t ON t.id=c.task_id ORDER BY c.clean_date")->fetchAll();
st($t[0]['due_date']===$iso(5)&&$t[0]['priority']==='urgent'&&str_contains($t[0]['title'],'neue Gäste 15:00')&&$t[0]['start_time']==='10:00:00'&&(int)$t[0]['duration_minutes']===60&&(int)$t[0]['plan_locked']===1&&$t[0]['window_start']===$iso(5)&&$t[0]['window_end']===$iso(5),'turnover cleaning: fixed day, 10:00, 60 min, urgent');
st($t[1]['priority']==='high'&&str_contains($t[1]['title'],'Abreise 10:00')&&str_contains($t[1]['notes'],'frei'),'normal cleaning');
st(!preg_match('/ABC|0000|Phone|Reservation/',json_encode($p->query('SELECT * FROM tasks')->fetchAll()).json_encode($p->query('SELECT stays_json FROM stay_calendars')->fetchAll())),'no booking codes or phone digits stored');
$r=gszStaySync($p,1,$pid,$fetch,$today);st($r['created']===0&&$r['cancelled']===0&&$r['changed']===0,'repeat sync changes nothing');
// Storno der Buchung +5..+8 → +8 entfällt, +5 ist kein Gästewechsel mehr
$feeds[$airUrl]=$cal($ev($d(2),$d(5),'Reserved'));$r=gszStaySync($p,1,$pid,$fetch,$today);
st($r['cancelled']===1&&$r['changed']===1,'cancellation removes cleaning, turnover downgraded');$t=$p->query("SELECT t.* FROM stay_cleanings c JOIN tasks t ON t.id=c.task_id WHERE c.clean_date='".$iso(5)."'")->fetch();st($t['priority']==='urgent'?false:str_contains($t['title'],'Abreise'),'task updated');
st(!(int)$p->query("SELECT COUNT(*) FROM tasks WHERE due_date='".$iso(8)."'")->fetchColumn(),'cancelled task deleted');
// Erledigte Reinigung bleibt, auch wenn die Buchung verschwindet; von Hand gelöschte Aufgabe wird nicht neu angelegt
$p->exec("UPDATE tasks SET done=1 WHERE due_date='".$iso(5)."'");$id14=(int)$p->query("SELECT id FROM tasks WHERE due_date='".$iso(14)."'")->fetchColumn();$p->exec("DELETE FROM tasks WHERE id=$id14");
$feeds[$airUrl]=$cal();$feeds[$bookUrl]=$cal($ev($d(12),$d(14),'CLOSED - Not available'));$r=gszStaySync($p,1,$pid,$fetch,$today);
st($r['created']===0&&(int)$p->query("SELECT COUNT(*) FROM tasks WHERE due_date='".$iso(5)."' AND done=1")->fetchColumn()===1,'done cleaning kept, manually deleted not recreated');
// Abruf scheitert → letzter Stand gilt, nichts wird gelöscht
$feeds[$bookUrl]=$cal($ev($d(20),$d(22),'CLOSED - Not available'));gszStaySync($p,1,$pid,$fetch,$today);st((int)$p->query("SELECT COUNT(*) FROM tasks WHERE due_date='".$iso(22)."'")->fetchColumn()===1,'new booking planned');
unset($feeds[$bookUrl]);$r=gszStaySync($p,1,$pid,$fetch,$today);st($r['errors']&&$r['cancelled']===0&&(int)$p->query("SELECT COUNT(*) FROM tasks WHERE due_date='".$iso(22)."'")->fetchColumn()===1,'failed fetch keeps last known bookings');
st($p->query("SELECT last_status FROM stay_calendars WHERE source='booking'")->fetchColumn()==='error','error shown on the calendar');
$all=gszStaySyncAll($p,1,$fetch);st(count($all)===1&&$all[0]['status']==='BLOCKED'&&str_contains($all[0]['reason'],'Booking.com'),'background job reports the failing calendar');
// keine Frage „Rechnung nötig?“ bei eigener Wohnung
$tid=(int)$p->query("SELECT id FROM tasks WHERE due_date='".$iso(22)."'")->fetchColumn();st(gszStayIsCleaningTask($p,$tid)&&!gszStayIsCleaningTask($p,999999),'cleaning task recognised');
// Beenden entfernt künftige offene Reinigungen
st(gszStayDisable($p,1,$pid,$today)===1&&!(int)$p->query("SELECT COUNT(*) FROM tasks WHERE due_date='".$iso(22)."'")->fetchColumn()&&!gszStayHasCalendars($p),'disable removes future open cleanings');
echo "Ferienwohnung PASS ($n checks; synthetic Airbnb/Booking calendars, no network).\n";
}finally{if(!$hadKey&&is_file($keyFile))unlink($keyFile);}
