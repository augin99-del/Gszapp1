<?php
declare(strict_types=1);
/**
 * Schema 440: Amtliche DWD-Warnungen (über die freie Schnittstelle api.brightsky.dev, ohne Schlüssel) für den Winterdienst.
 * Glätte, Glatteis, Schnee und Eisregen sind „Winterdienst“ (Push + Aufgaben), Frost nur ein Hinweis.
 * Objekte mit Winterdienst = Objekte mit einer Regelaufgabe „Winterdienst“ (bzw. Streuen/Räumen).
 */
require_once __DIR__.'/push.php';
const GSZ_WEATHER_API='https://api.brightsky.dev/alerts';
const GSZ_WEATHER_EVERY=15; // Minuten zwischen zwei Abrufen

function gszWeatherKind(string $event):int {$e=mb_strtoupper($event);if(preg_match('/GLÄTTE|GLATTEIS|SCHNEE|EISREGEN|GLATT/u',$e))return 2;if(str_contains($e,'FROST'))return 1;return 0;}
/** „Gütersloh:51.9069,8.3786;Rheda-Wiedenbrück:51.8417,8.3003“ → Liste. */
function gszWeatherPoints(PDO $pdo):array {
 $o=[];foreach(preg_split('/[;\n]+/',gszSetting($pdo,'weather_points'))?:[] as $p){if(preg_match('/^\s*([^:]{1,60}):\s*(-?\d{1,2}(?:\.\d+)?)\s*,\s*(-?\d{1,3}(?:\.\d+)?)\s*$/u',$p,$m)&&abs((float)$m[2])<=90&&abs((float)$m[3])<=180)$o[]=['name'=>trim($m[1]),'lat'=>(float)$m[2],'lon'=>(float)$m[3]];}
 return array_slice($o,0,6);
}
function gszWeatherHttp(string $url):?string {
 if(!function_exists('curl_init'))return null;$c=curl_init($url);curl_setopt_array($c,[CURLOPT_RETURNTRANSFER=>true,CURLOPT_TIMEOUT=>8,CURLOPT_CONNECTTIMEOUT=>4,CURLOPT_PROTOCOLS=>CURLPROTO_HTTPS,CURLOPT_USERAGENT=>'GSZ-Zentrale/1.0',CURLOPT_HTTPHEADER=>['Accept: application/json']]);
 $r=curl_exec($c);$code=(int)curl_getinfo($c,CURLINFO_RESPONSE_CODE);curl_close($c);return $code===200&&is_string($r)?$r:null;
}
function gszUtc(?string $iso):?string {if(!$iso)return null;try{return (new DateTimeImmutable($iso))->setTimezone(new DateTimeZone('UTC'))->format('Y-m-d H:i:s');}catch(Throwable $e){return null;}}
/** Ruft die Warnungen für alle Orte ab und speichert sie. Rückgabe: neu hinzugekommene Warnungen (Zeilen). */
function gszWeatherFetch(PDO $pdo,?callable $http=null):array {
 $http??='gszWeatherHttp';$seen=[];$new=[];$ok=false;
 foreach(gszWeatherPoints($pdo) as $pt){
  $raw=$http(GSZ_WEATHER_API.'?lat='.rawurlencode((string)$pt['lat']).'&lon='.rawurlencode((string)$pt['lon']));if($raw===null)continue;$j=json_decode($raw,true);if(!is_array($j)||!isset($j['alerts'])||!is_array($j['alerts']))continue;$ok=true;
  $area=(string)($j['location']['name_short']??$j['location']['name']??$pt['name']);
  foreach($j['alerts'] as $a){
   if(!is_array($a)||($a['status']??'actual')!=='actual')continue;$id=mb_substr((string)($a['alert_id']??$a['id']??''),0,190);if($id===''||isset($seen[$id]))continue;$seen[$id]=true;
   $event=mb_substr((string)($a['event_de']??''),0,80);$kind=gszWeatherKind($event);
   $row=[$id,(int)($a['event_code']??0),$event,mb_substr((string)($a['headline_de']??''),0,190),mb_substr(trim((string)($a['description_de']??'')),0,2000),mb_substr((string)($a['severity']??''),0,20),gszUtc($a['onset']??$a['effective']??null),gszUtc($a['expires']??null),mb_substr($area,0,190),$kind];
   $ins=$pdo->prepare('INSERT INTO weather_alerts(alert_id,event_code,event,headline,description,severity,onset,expires,area,winter) VALUES(?,?,?,?,?,?,?,?,?,?) ON DUPLICATE KEY UPDATE event=VALUES(event),headline=VALUES(headline),description=VALUES(description),severity=VALUES(severity),onset=VALUES(onset),expires=VALUES(expires),winter=VALUES(winter)');
   $ins->execute($row);if($ins->rowCount()===1)$new[]=gszWeatherAlert($pdo,$id);
  }
 }
 if($ok){$pdo->exec('DELETE FROM weather_alerts WHERE expires<UTC_TIMESTAMP()-INTERVAL 3 DAY');gszSettingSet($pdo,'weather_checked',gmdate('Y-m-d H:i:s'));}
 return array_values(array_filter($new));
}
function gszWeatherAlert(PDO $pdo,string $id):?array {$q=$pdo->prepare('SELECT * FROM weather_alerts WHERE alert_id=?');$q->execute([$id]);return $q->fetch(PDO::FETCH_ASSOC)?:null;}
/** Aktuelle bzw. kommende Warnungen (noch nicht abgelaufen), Winterdienst zuerst. */
function gszWeatherActive(PDO $pdo,int $minKind=1):array {$q=$pdo->prepare('SELECT * FROM weather_alerts WHERE winter>=? AND (expires IS NULL OR expires>UTC_TIMESTAMP()) ORDER BY winter DESC,onset,alert_id');$q->execute([$minKind]);return $q->fetchAll(PDO::FETCH_ASSOC);}
function gszWeatherWhen(array $a):string {
 $tz=new DateTimeZone('Europe/Berlin');$f=function(?string $t)use($tz){if(!$t)return '';$d=(new DateTimeImmutable($t.' UTC'))->setTimezone($tz);$today=(new DateTimeImmutable('today',$tz));$diff=(int)$today->diff($d->setTime(0,0))->format('%r%a');return ($diff===0?'heute':($diff===1?'morgen':['','Mo','Di','Mi','Do','Fr','Sa','So'][(int)$d->format('N')].' '.$d->format('d.m.'))).' '.$d->format('H:i');};
 return trim(($a['onset']?'ab '.$f($a['onset']):'').($a['expires']?' bis '.$f($a['expires']):''));
}
/** Objekte mit Winterdienst: per Haken ausgewählt (Einstellung) oder mit aktiver Regelaufgabe Winterdienst/Streuen/Räumen. */
function gszWinterIds(PDO $pdo):array {return array_values(array_unique(array_filter(array_map('intval',explode(',',gszSetting($pdo,'winter_properties'))))));}
function gszWinterProperties(PDO $pdo):array {
 $ids=gszWinterIds($pdo);$in=$ids?' OR p.id IN ('.implode(',',$ids).')':'';
 return $pdo->query("SELECT p.id,p.name,p.address FROM properties p WHERE EXISTS(SELECT 1 FROM recurring_jobs r WHERE r.property_id=p.id AND r.active=1 AND (LOWER(r.title) LIKE '%winter%' OR LOWER(r.title) LIKE '%streu%' OR LOWER(r.title) LIKE '%räum%' OR LOWER(r.title) LIKE '%glätte%'))$in ORDER BY p.name")->fetchAll(PDO::FETCH_ASSOC);
}
/** Haken-Liste speichern (nur vorhandene Objekte). */
function gszWinterSave(PDO $pdo,int $admin,array $ids):int {
 $ids=array_values(array_unique(array_filter(array_map('intval',$ids))));if($ids){$q=$pdo->query('SELECT id FROM properties WHERE id IN ('.implode(',',$ids).')');$ids=array_map('intval',$q->fetchAll(PDO::FETCH_COLUMN));}
 gszSettingSet($pdo,'winter_properties',implode(',',$ids));gszAudit($pdo,$admin,'winter_properties_saved',['count'=>count($ids)]);return count($ids);
}
/** Tag für den Einsatz: Beginn der Warnung (deutsche Zeit), frühestens heute. */
function gszWeatherDay(array $a,?string $today=null):string {
 $tz=new DateTimeZone('Europe/Berlin');$today??=(new DateTimeImmutable('today',$tz))->format('Y-m-d');
 $d=$a['onset']?(new DateTimeImmutable($a['onset'].' UTC'))->setTimezone($tz)->format('Y-m-d'):$today;return max($d,$today);
}
/** Legt je Winterdienst-Objekt eine dringende Aufgabe am Warntag an (keine Doppelten). Rückgabe: Anzahl neu. */
function gszWeatherPlan(PDO $pdo,?int $admin,string $alertId,?string $today=null):int {
 $a=gszWeatherAlert($pdo,$alertId);if(!$a||(int)$a['winter']<2)throw new RuntimeException('Keine Glätte- oder Schneewarnung.');
 $day=gszWeatherDay($a,$today);$time=preg_match('/^([01]\d|2[0-3]):[0-5]\d$/D',gszSetting($pdo,'weather_task_time'))?gszSetting($pdo,'weather_task_time').':00':null;$n=0;
 $exists=$pdo->prepare("SELECT COUNT(*) FROM tasks WHERE property_id=? AND due_date=? AND (LOWER(title) LIKE '%winter%' OR LOWER(title) LIKE '%streu%' OR LOWER(title) LIKE '%räum%' OR LOWER(title) LIKE '%glätte%')");
 $ins=$pdo->prepare("INSERT INTO tasks(property_id,title,due_date,start_time,priority,notes) VALUES(?,?,?,?,'urgent',?)");
 foreach(gszWinterProperties($pdo) as $p){$exists->execute([$p['id'],$day]);if((int)$exists->fetchColumn())continue;
  $ins->execute([$p['id'],'Winterdienst – '.mb_convert_case(mb_strtolower((string)$a['event']),MB_CASE_TITLE),$day,$time,mb_substr('DWD: '.$a['headline'].' ('.gszWeatherWhen($a).'). '.$a['description'],0,1000)]);$n++;}
 $pdo->prepare('UPDATE weather_alerts SET planned_at=UTC_TIMESTAMP() WHERE alert_id=?')->execute([$alertId]);gszAudit($pdo,$admin,'weather_planned',['alert'=>$alertId,'tasks'=>$n,'day'=>$day]);return $n;
}
/** Hintergrundlauf (Wecker/Öffnen): höchstens alle 15 Minuten abrufen; neue Glättewarnung → Push, ggf. Aufgaben. */
function gszWeatherTick(PDO $pdo,bool $force=false,?callable $http=null,?callable $sender=null):array {
 if(!gszPushReady($pdo)||!gszWeatherPoints($pdo))return ['checked'=>false,'new'=>0];
 $last=gszSetting($pdo,'weather_checked');if(!$force&&$last!==''&&strtotime($last.' UTC')>time()-GSZ_WEATHER_EVERY*60)return ['checked'=>false,'new'=>0];
 $new=gszWeatherFetch($pdo,$http);$pushed=0;$planned=0;
 foreach($new as $a){if((int)$a['winter']<2||($a['expires']&&strtotime($a['expires'].' UTC')<time()))continue;
  $props=count(gszWinterProperties($pdo));if(gszSetting($pdo,'weather_tasks')==='auto'&&$props){$planned+=gszWeatherPlan($pdo,null,(string)$a['alert_id']);}
  $pushed+=gszPushNotify($pdo,'weather','weather:'.$a['alert_id'],'❄️ '.mb_convert_case(mb_strtolower((string)$a['event']),MB_CASE_TITLE).' – '.$a['area'],trim(gszWeatherWhen($a).'. '.($props?(gszSetting($pdo,'weather_tasks')==='auto'?'Winterdienst für '.$props.' Objekt(e) eingeplant.':'Winterdienst für '.$props.' Objekt(e) einplanen?'):'')),'index.php#wetter',$sender);}
 return ['checked'=>true,'new'=>count($new),'pushed'=>$pushed,'planned'=>$planned];
}
