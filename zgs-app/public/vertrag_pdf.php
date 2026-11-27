<?php
declare(strict_types=1);
require __DIR__.'/bootstrap.php';if(empty($_SESSION['admin_id'])){http_response_code(403);exit('Nicht angemeldet.');}
require_once dirname(__DIR__).'/private/contracts.php';
if(!gszContractsReady($pdo)){http_response_code(409);exit('Update nötig.');}
$c=gszContractGet($pdo,(int)($_GET['id']??0));if(!$c){http_response_code(404);exit('Vertrag nicht gefunden.');}
try{$pdf=gszContractPdf($pdo,$c);}catch(Throwable $e){error_log('GSZ contract pdf: '.$e->getMessage());http_response_code(500);exit('PDF konnte nicht erstellt werden.');}
$name='Vertrag-'.gszContractNo($c).'-'.preg_replace('/[^A-Za-z0-9]+/','-',iconv('UTF-8','ASCII//TRANSLIT//IGNORE',(string)$c['object_name'])?:'Objekt').'.pdf';
header('Content-Type: application/pdf');header('Content-Disposition: '.(!empty($_GET['download'])?'attachment':'inline').'; filename="'.trim($name,'-').'"');header('Cache-Control: private, no-store');header('X-Content-Type-Options: nosniff');echo $pdf;
