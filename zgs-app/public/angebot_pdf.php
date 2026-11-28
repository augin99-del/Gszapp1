<?php
declare(strict_types=1);
require __DIR__.'/bootstrap.php';if(empty($_SESSION['admin_id'])){http_response_code(403);exit('Nicht angemeldet.');}
require_once dirname(__DIR__).'/private/offers.php';if(!gszOffersReady($pdo)){http_response_code(409);exit('Update nötig.');}
$o=gszOfferGet($pdo,(int)($_GET['id']??0));if(!$o){http_response_code(404);exit('Angebot nicht gefunden.');}
try{$pdf=gszOfferPdf($pdo,$o);}catch(Throwable $e){error_log('GSZ offer pdf: '.$e->getMessage());http_response_code(500);exit('PDF konnte nicht erstellt werden.');}
header('Content-Type: application/pdf');header('Content-Disposition: '.(!empty($_GET['download'])?'attachment':'inline').'; filename="'.gszOfferFileName($o).'"');header('Cache-Control: private, no-store');header('X-Content-Type-Options: nosniff');echo $pdf;
