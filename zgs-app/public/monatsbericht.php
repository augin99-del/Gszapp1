<?php
declare(strict_types=1);
require __DIR__.'/bootstrap.php';
if(empty($_SESSION['admin_id'])){http_response_code(403);exit('Nicht angemeldet.');}
require_once dirname(__DIR__).'/private/service_pdf.php';
$id=(int)($_GET['id']??0);$m=(string)($_GET['m']??'');$q=$pdo->prepare('SELECT id,name,address FROM properties WHERE id=?');$q->execute([$id]);$p=$q->fetch();if(!$p){http_response_code(404);exit('Objekt nicht gefunden.');}
try{$pdf=gszMonthReportPdf($pdo,$p,gszPortalData($pdo,$id,$m));}catch(Throwable $e){error_log('Monatsbericht: '.$e->getMessage());http_response_code(400);exit('Bericht konnte nicht erzeugt werden.');}
header('Content-Type: application/pdf');header('Content-Disposition: inline; filename="monatsbericht-'.$m.'-objekt-'.$id.'.pdf"');header('Content-Length: '.strlen($pdf));echo $pdf;
