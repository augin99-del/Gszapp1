<?php
declare(strict_types=1);
require __DIR__.'/bootstrap.php';
if (empty($_SESSION['admin_id'])) redirect('index.php');
require dirname(__DIR__).'/private/photo_storage.php';
$id=filter_var($_GET['id']??null,FILTER_VALIDATE_INT);
if (!$id || $id<1) {http_response_code(404);exit('Foto nicht gefunden.');}
$st=$pdo->prepare('SELECT stored_name,mime_type,size_bytes FROM work_log_photos WHERE id=?');
$st->execute([$id]);$photo=$st->fetch();
if (!$photo) {http_response_code(404);exit('Foto nicht gefunden.');}
if(!preg_match('/^[a-f0-9]{32}\.(jpg|png|webp)$/D',(string)$photo['stored_name'])){http_response_code(500);exit('Ungültiger Speicherverweis.');}
$path=photoStorageDir().'/'.$photo['stored_name'];
if(is_link($path)){http_response_code(500);exit('Ungültiger Speicherpfad.');}
if (!is_file($path)) {http_response_code(404);exit('Fotodatei nicht gefunden.');}
header('Content-Type: '.$photo['mime_type']);
header('Content-Disposition: inline; filename="photo"');
header('Content-Length: '.filesize($path));
header('Content-Security-Policy: default-src \'none\'; sandbox');
header('X-Content-Type-Options: nosniff');
readfile($path);
