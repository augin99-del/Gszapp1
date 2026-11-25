<?php
declare(strict_types=1);
/** Gemeinsamer Einstieg der öffentlichen Website-Schnittstellen: keine Sitzung, keine Cookies, nur HTTPS, CORS nur für die eigene Website. */
$https=(!empty($_SERVER['HTTPS'])&&$_SERVER['HTTPS']!=='off')||(($_SERVER['SERVER_PORT']??'')==='443');if(!$https){http_response_code(403);exit;}
ini_set('display_errors','0');header('X-Content-Type-Options: nosniff');header('Referrer-Policy: no-referrer');
require_once __DIR__.'/config_loader.php';require_once __DIR__.'/leads.php';
function gszWebPdo():?PDO {try{$p=gszConnectDatabase(gszLoadConfig());return gszLeadsReady($p)?$p:null;}catch(Throwable $e){return null;}}
/** Liefert die erlaubte Website-Herkunft, wenn Origin oder Referer genau dazu passen (sonst null). */
function gszWebOrigin(PDO $pdo):?string {
 $allowed=gszSetting($pdo,'website_origin');$hosts=[$allowed,str_replace('https://','https://www.',$allowed)];
 $o=(string)($_SERVER['HTTP_ORIGIN']??'');if($o!==''&&in_array($o,$hosts,true))return $o;
 $r=(string)($_SERVER['HTTP_REFERER']??'');foreach($hosts as $h)if($r===$h||str_starts_with($r,$h.'/'))return $h;return null;
}
