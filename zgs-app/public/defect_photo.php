<?php
declare(strict_types=1);
require __DIR__.'/bootstrap.php';
if(empty($_SESSION['admin_id'])){http_response_code(403);exit;}
require_once dirname(__DIR__).'/private/reports.php';
$d=gszDefect($pdo,(int)($_GET['id']??0));$path=$d?gszDefectPhotoPath($d['photo_name']):null;if(!$path){http_response_code(404);exit;}
header('Content-Type: '.(in_array($d['photo_mime'],['image/jpeg','image/png','image/webp'],true)?$d['photo_mime']:'application/octet-stream'));header('Content-Length: '.filesize($path));header('Cache-Control: private, max-age=3600');header("Content-Security-Policy: default-src 'none'");readfile($path);
