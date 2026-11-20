<?php
declare(strict_types=1);
require_once __DIR__.'/planning_engine.php';
const WASTE_FRACTIONS=['Restmüll','Bio','Papier','Gelb','Windeln','Grünschnitt','Weihnachtsbäume'];
function wasteInput(string $raw):string {
 if(strlen($raw)>1048576||$raw===''||str_contains($raw,"\0")||!mb_check_encoding($raw,'UTF-8'))throw new InvalidArgumentException('Import benötigt UTF-8-Text, maximal 1 MB, ohne Nullbytes.');
 return preg_replace('/^\xEF\xBB\xBF/','',$raw)??$raw;
}
function wasteIcsDate(string $value,array $params):string {
 if(($params['VALUE']??'')==='DATE'||preg_match('/^\d{8}$/D',$value)){
  $d=DateTimeImmutable::createFromFormat('!Ymd',$value,new DateTimeZone('Europe/Berlin'));if(!$d||$d->format('Ymd')!==$value)throw new InvalidArgumentException('Ungültiges ICS-Datum.');return $d->format('Y-m-d');
 }
 $tz=$params['TZID']??'Europe/Berlin';if(!in_array($tz,['Europe/Berlin','UTC'],true))throw new InvalidArgumentException('ICS-Zeitzone nicht unterstützt: nur Europe/Berlin/UTC.');
 $utc=str_ends_with($value,'Z');$format=$utc?'!Ymd\THis\Z':'!Ymd\THis';$d=DateTimeImmutable::createFromFormat($format,$value,new DateTimeZone($utc?'UTC':$tz));
 if(!$d||$d->format($utc?'Ymd\THis\Z':'Ymd\THis')!==$value)throw new InvalidArgumentException('Ungültiger ICS-Zeitpunkt.');
 if(!$utc&&$tz==='Europe/Berlin'){
  // Ambiguous fall-back local times require UTC, never guess an offset.
  $trans=(new DateTimeZone($tz))->getTransitions($d->getTimestamp()-10800,$d->getTimestamp()+10800);
  for($i=1;$i<count($trans);$i++)if($trans[$i]['offset']<$trans[$i-1]['offset']&&$d->format('H')==='02')throw new InvalidArgumentException('Mehrdeutige Winterzeit: UTC-Zeitpunkt verwenden.');
 }
 return $d->setTimezone(new DateTimeZone('Europe/Berlin'))->format('Y-m-d');
}
function wasteParseIcs(string $raw):array {
 $raw=wasteInput($raw);$raw=str_replace(["\r\n","\r"],"\n",$raw);$raw=preg_replace('/\n[ \t]/','',$raw)??$raw;
 if(!str_contains($raw,'BEGIN:VCALENDAR')||!str_contains($raw,'END:VCALENDAR'))throw new InvalidArgumentException('Kein vollständiger iCalendar.');
 $events=[];$event=null;$calendar='';$calendarTz='Europe/Berlin';
 foreach(explode("\n",$raw) as $line){if(strlen($line)>8192)throw new InvalidArgumentException('ICS-Zeile zu lang.');
  if($line==='BEGIN:VEVENT'){if($event!==null)throw new InvalidArgumentException('Verschachtelte Termine.');$event=[];continue;}
  if($line==='END:VEVENT'){
   if($event===null||!isset($event['DTSTART'],$event['SUMMARY']))throw new InvalidArgumentException('Termin ohne DTSTART/SUMMARY.');
   foreach(['RRULE','RDATE','EXDATE','RECURRENCE-ID','DURATION'] as $unsupported)if(isset($event[$unsupported]))throw new InvalidArgumentException('ICS-Wiederholungen/Dauer zuerst als Einzeltermine exportieren.');
   if(($event['STATUS']['value']??'')==='CANCELLED')throw new InvalidArgumentException('Stornierter Termin: manuelle Konfliktprüfung erforderlich.');
   $params=$event['DTSTART']['params'];if(!isset($params['TZID']))$params['TZID']=$calendarTz;
   $date=wasteIcsDate($event['DTSTART']['value'],$params);
   if(isset($event['DTEND'])){ $endParams=$event['DTEND']['params'];if(!isset($endParams['TZID']))$endParams['TZID']=$calendarTz;$end=wasteIcsDate($event['DTEND']['value'],$endParams);if($end<$date||$end>planDate($date,1))throw new InvalidArgumentException('Mehrdeutiger mehrtägiger Abfuhrtermin.'); }
   $text=static fn($s)=>str_replace(['\\n','\\N','\\,','\\;','\\\\'],["\n","\n",',',';','\\'],$s);
   $events[]=['date'=>$date,'label'=>$text($event['SUMMARY']['value']),'uid'=>$event['UID']['value']??'','address'=>$text($event['LOCATION']['value']??''),'original_start'=>$event['DTSTART']['value'].' ['.$params['TZID'].']','calendar'=>$calendar];$event=null;if(count($events)>500)throw new InvalidArgumentException('Maximal 500 Abfuhrtermine je Datei.');continue;
  }
  if(!str_contains($line,':'))continue;[$head,$value]=explode(':',$line,2);$parts=explode(';',$head);$key=strtoupper(array_shift($parts));$params=[];foreach($parts as $p){if(str_contains($p,'=')){[$k,$v]=explode('=',$p,2);$params[strtoupper($k)]=trim($v,'"');}}
  if($event!==null){if(isset($event[$key])&&in_array($key,['DTSTART','DTEND','SUMMARY','UID','LOCATION'],true))throw new InvalidArgumentException('Doppeltes ICS-Feld.');$event[$key]=['value'=>$value,'params'=>$params];}
  elseif($key==='X-WR-CALNAME')$calendar=$value;
  elseif($key==='X-WR-TIMEZONE'){if(!in_array($value,['Europe/Berlin','UTC'],true))throw new InvalidArgumentException('Kalender-Zeitzone nicht unterstützt.');$calendarTz=$value;}
 }
 if($event!==null||!$events)throw new InvalidArgumentException('Keine vollständigen Abfuhrtermine.');return wasteValidateRows($events);
}
function wasteParseCsv(string $raw,array $map,string $delimiter=';'):array {
 $raw=wasteInput($raw);if(!in_array($delimiter,[';',',',"\t"],true))throw new InvalidArgumentException('Ungültiges Trennzeichen.');
 $f=fopen('php://temp','w+');fwrite($f,$raw);rewind($f);$header=fgetcsv($f,8192,$delimiter,'"','');if(!$header||count($header)!==count(array_unique($header)))throw new InvalidArgumentException('CSV-Kopfzeile fehlt oder doppelt.');
 foreach(['date','label'] as $k)if(!isset($map[$k])||!in_array($map[$k],$header,true))throw new InvalidArgumentException('CSV-Feldzuordnung für Datum/Fraktion fehlt.');
 $out=[];while(($row=fgetcsv($f,8192,$delimiter,'"',''))!==false){if($row===[null])continue;if(count($row)!==count($header))throw new InvalidArgumentException('CSV-Spaltenzahl abweichend.');$r=array_combine($header,$row);$date=trim($r[$map['date']]);if(!zgsValidDate($date))throw new InvalidArgumentException('CSV-Datum muss YYYY-MM-DD sein.');$out[]=['date'=>$date,'label'=>trim($r[$map['label']]),'uid'=>$r[$map['uid']??'']??'','address'=>$r[$map['address']??'']??'','original_start'=>$date,'calendar'=>'CSV'];if(count($out)>500)throw new InvalidArgumentException('Maximal 500 CSV-Termine.');}fclose($f);if(!$out)throw new InvalidArgumentException('CSV ohne Termine.');return wasteValidateRows($out);
}
function wasteValidateRows(array $rows):array {
 $out=[];$uids=[];foreach($rows as $r){if(mb_strlen($r['label'])>500||mb_strlen($r['address'])>255||strlen($r['uid'])>255||trim($r['label'])==='')throw new InvalidArgumentException('Termintext/Adresse/ID ungültig.');
  $key=$r['uid']?:hash('sha256',json_encode([$r['date'],$r['label'],$r['address']]));$r['uid']=$key;$hash=hash('sha256',json_encode($r,JSON_THROW_ON_ERROR));if(isset($uids[$key])&&$uids[$key]!==$hash)throw new InvalidArgumentException('Eine Quellen-ID bezeichnet unterschiedliche Termine.');$uids[$key]=$hash;$out[$hash]=$r;
 }return array_values($out);
}
