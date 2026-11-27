<?php
declare(strict_types=1);
/** Shared CSV/ZIP export helpers (formerly part of the removed finance export). */
function gszCsvCell(mixed $v):string {
 $s=(string)$v;if(preg_match('/^[=+@\-\t\r]/u',$s)&&!preg_match('/^-?\d{1,15}(?:[.,]\d{1,6})?$/D',$s))$s="'".$s; // reine Zahlen (auch negativ) bleiben Zahlen
 return '"'.str_replace('"','""',$s).'"';
}
function gszCsv(array $headers,array $rows):string {$out="\xEF\xBB\xBF".implode(';',array_map('gszCsvCell',$headers))."\r\n";foreach($rows as $row)$out.=implode(';',array_map('gszCsvCell',array_values($row)))."\r\n";return $out;}
/** Bounded, dependency-free ZIP (stored), no extraction or user-controlled paths. */
function gszZip(array $files):string {
 if(count($files)>200)throw new RuntimeException('Export maximal 200 Dateien.');$body='';$central='';$count=0;$total=0;foreach($files as $name=>$bytes){if($name===''||str_contains($name,'..')||str_contains($name,"\0")||str_contains($name,'\\')||str_starts_with($name,'/'))throw new RuntimeException('Unsicherer Exportpfad.');$total+=strlen($bytes);if($total>20*1024*1024)throw new RuntimeException('Export maximal 20 MB; Zeitraum verkleinern.');$offset=strlen($body);$size=strlen($bytes);$crc=hexdec(hash('crc32b',$bytes));$body.=pack('VvvvvvVVVvv',0x04034b50,20,0x0800,0,0,33,$crc,$size,$size,strlen($name),0).$name.$bytes;$central.=pack('VvvvvvvVVVvvvvvVV',0x02014b50,20,20,0x0800,0,0,33,$crc,$size,$size,strlen($name),0,0,0,0,0,$offset).$name;$count++;}return $body.$central.pack('VvvvvVVv',0x06054b50,0,0,$count,$count,strlen($central),strlen($body),0);
}
