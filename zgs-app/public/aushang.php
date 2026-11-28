<?php
declare(strict_types=1);
require __DIR__.'/bootstrap.php';
if(empty($_SESSION['admin_id'])){http_response_code(403);exit('Nicht angemeldet.');}
require_once dirname(__DIR__).'/private/service_pdf.php';
$id=(int)($_GET['id']??0);$q=$pdo->prepare('SELECT id,name,address FROM properties WHERE id=?');$q->execute([$id]);$p=$q->fetch();$l=$p?gszLinks($pdo,$id):null;
if(!$p||!$l['report_token']){http_response_code(404);exit('Für dieses Objekt ist keine QR-Meldung eingerichtet.');}
try{$pdf=gszAushangPdf($pdo,$p,(string)$l['report_token']);}catch(Throwable $e){error_log('Aushang: '.$e->getMessage());http_response_code(500);exit('PDF konnte nicht erzeugt werden.');}
$slug=trim(preg_replace('/[^a-z0-9]+/','-',strtolower(iconv('UTF-8','ASCII//TRANSLIT',$p['name'])?:'objekt')),'-')?:'objekt';
header('Content-Type: application/pdf');header('Content-Disposition: inline; filename="aushang-schaden-melden-'.$slug.'.pdf"');header('Content-Length: '.strlen($pdf));echo $pdf;
