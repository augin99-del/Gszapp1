<?php
declare(strict_types=1);
require __DIR__.'/bootstrap.php';if(empty($_SESSION['admin_id'])){http_response_code(403);exit('Nicht angemeldet.');}require_once dirname(__DIR__).'/private/estate_settlement_export.php';
$id=filter_input(INPUT_GET,'id',FILTER_VALIDATE_INT);if(!$id||$id<1){http_response_code(404);exit('Nicht gefunden.');}try{estateSettlementRequire($pdo);$bytes=gszZip(estateSettlementBundle($pdo,$id));}catch(Throwable $e){http_response_code(422);exit('Abrechnungspaket nicht verfügbar. Quellenintegrität und Archivlimits prüfen.');}
header('Content-Type: application/zip');header('Content-Disposition: attachment; filename="private-abrechnung-'.$id.'.zip"');header('Content-Length: '.strlen($bytes));echo $bytes;
