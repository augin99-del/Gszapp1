<?php
declare(strict_types=1);
require __DIR__.'/bootstrap.php';if(empty($_SESSION['admin_id'])){http_response_code(403);exit('Nicht angemeldet.');}require_once dirname(__DIR__).'/private/finance_export_service.php';
try{$files=financeBundle($pdo,(string)($_GET['from']??''),(string)($_GET['to']??''));$zip=gszZip($files);}catch(Throwable $e){http_response_code(422);exit('Export nicht möglich. Zeitraum, Limits und Originalintegrität prüfen.');}
header('Content-Type: application/zip');header('Content-Disposition: attachment; filename="gsz-steuerberater-arbeitsexport.zip"');header('Content-Length: '.strlen($zip));echo $zip;
