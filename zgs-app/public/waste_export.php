<?php
declare(strict_types=1);
require __DIR__.'/bootstrap.php';if(empty($_SESSION['admin_id'])){http_response_code(403);exit('Nicht angemeldet.');}
require_once dirname(__DIR__).'/private/waste_export.php';
$from=(string)($_GET['from']??date('Y-m-d'));$to=(string)($_GET['to']??(new DateTimeImmutable('+8 weeks'))->format('Y-m-d'));$format=(string)($_GET['format']??'ics');
try{$bytes=$format==='csv'?wasteExportCsv($pdo,$from,$to):wasteExportIcs($pdo,$from,$to);}catch(Throwable $e){http_response_code(422);exit('Export nicht möglich: Zeitraum prüfen.');}
header('Content-Type: '.($format==='csv'?'text/csv':'text/calendar').'; charset=utf-8');header('Content-Disposition: attachment; filename="gsz-tonnen-'.$from.'.'.($format==='csv'?'csv':'ics').'"');header('Content-Length: '.strlen($bytes));echo $bytes;
