<?php
declare(strict_types=1);
// Schlüsselquittung als PDF: ?objekt=ID&art=erhalten|an_kunden (alle aktuellen Schlüssel des Objekts) oder ?key=ID&art=ausgegeben (an Mitarbeiter).
require __DIR__.'/bootstrap.php';if(empty($_SESSION['admin_id'])){http_response_code(403);exit('Nicht angemeldet.');}
require_once dirname(__DIR__).'/private/keys.php';if(!gszKeysReady($pdo)){http_response_code(409);exit('Update nötig.');}
$art=(string)($_GET['art']??'erhalten');if(!in_array($art,['erhalten','an_kunden','ausgegeben'],true))$art='erhalten';
if(isset($_GET['key'])){$k=gszKeyGet($pdo,(int)$_GET['key']);if(!$k){http_response_code(404);exit('Schlüssel nicht gefunden.');}$keys=[$k];$person=$art==='ausgegeben'?(string)$k['holder']:'';}
else{$keys=gszKeysForProperty($pdo,(int)($_GET['objekt']??0));if(!$keys){http_response_code(404);exit('Für dieses Objekt sind keine Schlüssel erfasst.');}$person='';}
try{$pdf=gszKeyReceiptPdf($pdo,$keys,$art,mb_substr(trim((string)($_GET['person']??$person)),0,190));}catch(Throwable $e){error_log('GSZ key pdf: '.$e->getMessage());http_response_code(500);exit('PDF konnte nicht erstellt werden.');}
header('Content-Type: application/pdf');header('Content-Disposition: inline; filename="Schluesselquittung-'.date('Y-m-d').'.pdf"');header('Cache-Control: private, no-store');header('X-Content-Type-Options: nosniff');echo $pdf;
