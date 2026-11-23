<?php
declare(strict_types=1);
require __DIR__.'/bootstrap.php';
if(empty($_SESSION['admin_id'])){http_response_code(403);exit;}
require_once dirname(__DIR__).'/private/notes.php';
$id=filter_var($_GET['id']??null,FILTER_VALIDATE_INT);
$q=$pdo->prepare('SELECT photo_name,photo_mime FROM quick_notes WHERE id=?');$q->execute([(int)$id]);$photo=$q->fetch(PDO::FETCH_ASSOC);
if(!$photo||!$photo['photo_name']||!preg_match('/^[a-f0-9]{32}\.(jpg|png|webp)$/D',(string)$photo['photo_name'])||!in_array($photo['photo_mime'],['image/jpeg','image/png','image/webp'],true)){http_response_code(404);exit('Foto nicht gefunden.');}
$path=gszNotePhotoDir().'/'.$photo['photo_name'];if(is_link($path)||!is_file($path)){http_response_code(404);exit('Foto nicht gefunden.');}
header('Content-Type: '.$photo['photo_mime']);header('Content-Disposition: inline; filename="foto"');header('Content-Length: '.filesize($path));
header('Cache-Control: private, max-age=86400');header('Content-Security-Policy: default-src \'none\'; sandbox');header('X-Content-Type-Options: nosniff');
readfile($path);
