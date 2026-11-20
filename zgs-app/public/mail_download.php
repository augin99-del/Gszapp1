<?php
declare(strict_types=1);
require __DIR__.'/bootstrap.php';if(empty($_SESSION['admin_id'])){http_response_code(403);exit('Nicht angemeldet.');}require_once dirname(__DIR__).'/private/platform.php';
$q=$pdo->prepare('SELECT * FROM mail_attachments WHERE id=?');$q->execute([(int)($_GET['id']??0)]);$a=$q->fetch();if(!$a){http_response_code(404);exit('Anhang nicht gefunden.');}
try{$raw=gszReadBytes('mail',$a['stored_name'],$a['sha256']);}catch(Throwable $e){http_response_code(500);exit('Anhang nicht verfügbar oder Integrität verletzt.');}
header('Content-Type: application/octet-stream');header('Content-Disposition: attachment; filename="'.preg_replace('/[^a-zA-Z0-9._-]/','_',$a['original_name']).'"');header('Content-Length: '.strlen($raw));header("Content-Security-Policy: default-src 'none'; sandbox");echo $raw;
