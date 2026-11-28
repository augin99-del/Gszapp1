<?php
declare(strict_types=1);
// Öffentlich mit Portal-Link: liefert nur Fotos von Mieter-Meldungen genau dieses Objekts (keine Arbeitsfotos).
require dirname(__DIR__).'/private/public_page.php';
$pdo=gszPublicPdo();if(!$pdo){http_response_code(503);exit;}
$prop=gszLinkProperty($pdo,'portal',(string)($_GET['k']??''));if(!$prop){http_response_code(404);exit;}
$path=null;$mime='';
if(isset($_GET['d'])){$q=$pdo->prepare("SELECT photo_name,photo_mime FROM defect_reports WHERE id=? AND property_id=? AND status<>'verworfen'");$q->execute([(int)$_GET['d'],$prop['id']]);$r=$q->fetch(PDO::FETCH_ASSOC);if($r){$path=gszDefectPhotoPath($r['photo_name']);$mime=(string)$r['photo_mime'];}}
if(!$path||!in_array($mime,['image/jpeg','image/png','image/webp'],true)){http_response_code(404);exit;}
header('Content-Type: '.$mime);header('Content-Length: '.filesize($path));header('Cache-Control: private, max-age=86400');header("Content-Security-Policy: default-src 'none'");readfile($path);
