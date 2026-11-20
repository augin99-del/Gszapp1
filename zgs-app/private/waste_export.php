<?php
declare(strict_types=1);
/** ICS/CSV export of waste blocks: one entry per object, day and direction, listing every bin type. Read-only. */
require_once __DIR__.'/waste_blocks.php';require_once __DIR__.'/export_core.php';
function wasteExportRows(PDO $pdo,string $from,string $to):array {
 if(!zgsValidDate($from)||!zgsValidDate($to)||$from>$to||(new DateTimeImmutable($from))->diff(new DateTimeImmutable($to))->days>400)throw new InvalidArgumentException('Zeitraum ungültig (maximal 400 Tage).');
 $q=$pdo->prepare("SELECT t.id,t.title,t.due_date,t.done,t.start_time,p.name,p.address FROM tasks t JOIN properties p ON p.id=t.property_id WHERE t.due_date BETWEEN ? AND ? AND (EXISTS(SELECT 1 FROM waste_task_links l WHERE l.task_id=t.id) OR EXISTS(SELECT 1 FROM recurring_jobs r WHERE r.id=t.recurring_job_id AND r.rule_source='special_weekly')) ORDER BY t.due_date,p.name,t.id LIMIT 5001");$q->execute([$from,$to]);$rows=$q->fetchAll(PDO::FETCH_ASSOC);if(count($rows)>5000)throw new RuntimeException('Zeitraum verkleinern.');
 $info=wasteBlockInfo($pdo,array_column($rows,'id'));foreach($rows as &$r){$i=$info[(int)$r['id']]??['labels'=>[],'direction'=>null];$r['bins']=$i['labels'];$r['direction']=$i['direction'];}unset($r);return $rows;
}
function wasteIcsText(string $s):string {return str_replace(["\\",";",",","\r\n","\n"],["\\\\","\;","\\,","\\n","\\n"],$s);}
function wasteIcsFold(string $line):string {$out='';while(strlen($line)>74){$cut=74;while($cut>1&&(ord($line[$cut])&0xC0)===0x80)$cut--;$out.=substr($line,0,$cut)."\r\n ";$line=substr($line,$cut);}return $out.$line."\r\n";}
function wasteExportIcs(PDO $pdo,string $from,string $to):string {
 $out="BEGIN:VCALENDAR\r\nVERSION:2.0\r\nPRODID:-//GSZ Zentrale//Abfall-Bloecke//DE\r\nCALSCALE:GREGORIAN\r\nX-WR-CALNAME:GSZ Tonnen\r\n";$stamp=gmdate('Ymd\THis\Z');
 foreach(wasteExportRows($pdo,$from,$to) as $r){$day=str_replace('-','',$r['due_date']);$next=str_replace('-','',planDate($r['due_date'],1));
  $out.="BEGIN:VEVENT\r\n".wasteIcsFold('UID:gsz-abfall-block-'.(int)$r['id'].'@gsz-zentrale')."DTSTAMP:$stamp\r\nDTSTART;VALUE=DATE:$day\r\nDTEND;VALUE=DATE:$next\r\n".wasteIcsFold('SUMMARY:'.wasteIcsText($r['name'].': '.$r['title'])).wasteIcsFold('LOCATION:'.wasteIcsText((string)$r['address'])).wasteIcsFold('DESCRIPTION:'.wasteIcsText('Tonnen: '.($r['bins']?implode(', ',$r['bins']):'–').($r['done']?' · erledigt':''))).($r['done']?"STATUS:CONFIRMED\r\n":'')."END:VEVENT\r\n";}
 return $out."END:VCALENDAR\r\n";
}
function wasteExportCsv(PDO $pdo,string $from,string $to):string {
 $rows=[];foreach(wasteExportRows($pdo,$from,$to) as $r)$rows[]=[$r['due_date'],$r['name'],$r['address'],$r['direction']==='rein'?'zurückstellen':($r['direction']==='raus'?'rausstellen':''),$r['title'],implode(', ',$r['bins']),$r['done']?'ja':'nein'];
 return gszCsv(['Datum','Objekt','Adresse','Richtung','Tätigkeit','Tonnenarten','Erledigt'],$rows);
}
