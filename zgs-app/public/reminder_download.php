<?php
declare(strict_types=1);
require __DIR__.'/bootstrap.php';if(empty($_SESSION['admin_id'])){http_response_code(403);exit('Nicht angemeldet.');}require_once dirname(__DIR__).'/private/receivables.php';
$id=filter_input(INPUT_GET,'id',FILTER_VALIDATE_INT);if(!$id||$id<1){http_response_code(404);exit('Nicht gefunden.');}
try{$q=$pdo->prepare('SELECT * FROM invoice_reminders WHERE id=?');$q->execute([$id]);$r=$q->fetch();if(!$r){http_response_code(404);exit('Nicht gefunden.');}$bytes=reminderReadPdf($r);}catch(Throwable $e){http_response_code(422);exit('PDF nicht verfügbar. Integrität/Migration prüfen.');}
header('Content-Type: application/pdf');header('Content-Disposition: attachment; filename="zahlungserinnerung-'.$id.'.pdf"');header('Content-Length: '.strlen($bytes));echo $bytes;
