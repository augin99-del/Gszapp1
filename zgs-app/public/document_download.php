<?php
declare(strict_types=1);
require __DIR__.'/bootstrap.php';
if(empty($_SESSION['admin_id'])) {http_response_code(403);exit('Nicht angemeldet.');}
require_once dirname(__DIR__).'/private/document_storage.php';
$id=filter_var($_GET['id']??null,FILTER_VALIDATE_INT);
if(!$id||$id<1){http_response_code(400);exit('Ungültige ID.');}
$stmt=$pdo->prepare('SELECT original_name,stored_name,mime_type,size_bytes,sha256 FROM documents WHERE id=?');$stmt->execute([$id]);$doc=$stmt->fetch();
if(!$doc){http_response_code(404);exit('Dokument nicht gefunden.');}
$stored=(string)$doc['stored_name'];if(!preg_match('/^[a-f0-9]{32}$/D',$stored)){http_response_code(500);exit('Ungültiger Speicherverweis.');}
$path=documentStorageDir().'/'.$stored;
if(is_link($path)||!is_file($path)||!is_readable($path)){http_response_code(404);exit('Datei nicht verfügbar.');}
if((int)filesize($path)!==(int)$doc['size_bytes']||!hash_equals((string)$doc['sha256'],hash_file('sha256',$path))){http_response_code(500);exit('Integritätsprüfung fehlgeschlagen.');}
$name=preg_replace('/[^a-zA-Z0-9._-]+/','_',(string)$doc['original_name'])?:'dokument';
header('Content-Type: application/octet-stream');header('Content-Disposition: attachment; filename="'.substr($name,0,150).'"');
header('Content-Length: '.filesize($path));header('X-Content-Type-Options: nosniff');header('Cache-Control: private, no-store');
readfile($path);
