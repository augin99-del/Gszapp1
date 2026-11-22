<?php
declare(strict_types=1);
require __DIR__.'/bootstrap.php';
if(empty($_SESSION['admin_id'])){http_response_code(403);exit('Nicht angemeldet.');}
require dirname(__DIR__).'/private/photo_storage.php';
require dirname(__DIR__).'/private/worklog_pdf.php';
$id=filter_var($_GET['id']??null,FILTER_VALIDATE_INT);
if(!$id||$id<1){http_response_code(404);exit('Nachweis nicht gefunden.');}
$st=$pdo->prepare('SELECT w.id,w.description,w.notes,w.performed_on,w.duration_minutes,p.name property_name,p.address property_address,t.title task_title FROM work_logs w JOIN properties p ON p.id=w.property_id LEFT JOIN tasks t ON t.id=w.task_id WHERE w.id=?');
$st->execute([$id]);$log=$st->fetch();
if(!$log){http_response_code(404);exit('Nachweis nicht gefunden.');}
$st=$pdo->prepare('SELECT id,stored_name,label,created_at FROM work_log_photos WHERE work_log_id=? ORDER BY id ASC LIMIT 30');
$st->execute([$id]);$photos=$st->fetchAll();
try{$pdf=worklogReportPdf($log,$photos,photoStorageDir());}
catch(Throwable $e){error_log('Worklog PDF: '.$e->getMessage());http_response_code(500);exit('PDF konnte nicht erzeugt werden.');}
header('Content-Type: application/pdf');
header('Content-Disposition: attachment; filename="arbeitsnachweis-'.$id.'.pdf"');
header('Content-Length: '.strlen($pdf));
header('X-Content-Type-Options: nosniff');
echo $pdf;
