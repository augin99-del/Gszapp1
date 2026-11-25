<?php
declare(strict_types=1);
require __DIR__.'/bootstrap.php';
if(empty($_SESSION['admin_id'])){http_response_code(403);exit('Nicht angemeldet.');}
require_once dirname(__DIR__).'/private/service_pdf.php';
try{$pdf=gszReviewCardsPdf($pdo);}catch(RuntimeException $e){http_response_code(400);exit($e->getMessage());}catch(Throwable $e){error_log('Karten: '.$e->getMessage());http_response_code(500);exit('PDF konnte nicht erzeugt werden.');}
header('Content-Type: application/pdf');header('Content-Disposition: inline; filename="bewertungskarten-85x55.pdf"');header('Content-Length: '.strlen($pdf));echo $pdf;
