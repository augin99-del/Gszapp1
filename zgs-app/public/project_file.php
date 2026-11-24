<?php
declare(strict_types=1);
require __DIR__.'/bootstrap.php';
if(empty($_SESSION['admin_id'])){http_response_code(403);exit;}
require_once dirname(__DIR__).'/private/projects.php';
$q=$pdo->prepare('SELECT stored_name,mime_type,original_name FROM project_files WHERE id=?');$q->execute([(int)($_GET['id']??0)]);$f=$q->fetch(PDO::FETCH_ASSOC);
if(!$f||!preg_match('/^[a-f0-9]{32}\.(jpg|png|webp|pdf)$/D',(string)$f['stored_name'])||!in_array($f['mime_type'],['image/jpeg','image/png','image/webp','application/pdf'],true)){http_response_code(404);exit('Datei nicht gefunden.');}
$path=gszPFileDir().'/'.$f['stored_name'];if(is_link($path)||!is_file($path)){http_response_code(404);exit('Datei nicht gefunden.');}
$safe=preg_replace('/[^A-Za-z0-9._-]+/','_',(string)$f['original_name'])?:'datei';
header('Content-Type: '.$f['mime_type']);header('Content-Disposition: inline; filename="'.$safe.'"');header('Content-Length: '.filesize($path));
header('Cache-Control: private, max-age=86400');if($f['mime_type']!=='application/pdf')header('Content-Security-Policy: default-src \'none\'; sandbox');header('X-Content-Type-Options: nosniff');
readfile($path);
