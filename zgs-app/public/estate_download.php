<?php
declare(strict_types=1);
require __DIR__.'/bootstrap.php';if(empty($_SESSION['admin_id'])){http_response_code(403);exit('Nicht angemeldet.');}require_once dirname(__DIR__).'/private/estate_documents.php';
$id=filter_input(INPUT_GET,'id',FILTER_VALIDATE_INT);if(!$id||$id<1){http_response_code(404);exit('Nicht gefunden.');}
try{estateRequire($pdo);$q=$pdo->prepare('SELECT * FROM estate_documents WHERE id=?');$q->execute([$id]);$d=$q->fetch();if(!$d){http_response_code(404);exit('Nicht gefunden.');}$bytes=gszReadBytes('estate',$d['sha256'],$d['sha256']);if(strlen($bytes)!==(int)$d['size_bytes'])throw new RuntimeException();}catch(Throwable $e){http_response_code(422);exit('Original nicht verfügbar. Integrität/Migration prüfen.');}
$name=preg_replace('/[^a-zA-Z0-9._-]/','_',$d['original_name']);header('Content-Type: application/octet-stream');header('Content-Disposition: attachment; filename="'.$name.'"');header('Content-Length: '.strlen($bytes));echo $bytes;
