<?php
declare(strict_types=1);
require __DIR__.'/bootstrap.php';if(empty($_SESSION['admin_id'])){http_response_code(403);exit('Nicht angemeldet.');}
require_once dirname(__DIR__).'/private/db_backup.php';
try{$file=(string)($_GET['file']??'');$path=gszBackupPath($file);}catch(Throwable $e){http_response_code(404);exit('Sicherung nicht gefunden.');}
gszAudit($pdo,(int)$_SESSION['admin_id'],'backup_downloaded',['file'=>$file]);
header('Content-Type: application/gzip');header('Content-Disposition: attachment; filename="'.$file.'"');header('Content-Length: '.filesize($path));readfile($path);
