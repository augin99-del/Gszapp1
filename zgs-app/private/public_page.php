<?php
declare(strict_types=1);
/** Einstieg für öffentliche Seiten mit geheimem Link (Mieter-Meldung, Hausverwaltungs-Portal): nur HTTPS, keine Sitzung, keine Cookies, nicht indexieren. */
$https=(!empty($_SERVER['HTTPS'])&&$_SERVER['HTTPS']!=='off')||(($_SERVER['SERVER_PORT']??'')==='443');if(!$https){http_response_code(403);exit('HTTPS erforderlich.');}
ini_set('display_errors','0');ini_set('zend.exception_ignore_args','1');date_default_timezone_set('Europe/Berlin');
header('X-Content-Type-Options: nosniff');header('X-Frame-Options: DENY');header('Referrer-Policy: no-referrer');header('Cache-Control: no-store');header('X-Robots-Tag: noindex, nofollow');
header("Content-Security-Policy: default-src 'none'; img-src 'self' data: blob:; style-src 'self'; script-src 'self'; form-action 'self'; base-uri 'none'; frame-ancestors 'none'");
require_once __DIR__.'/config_loader.php';require_once __DIR__.'/reports.php';require_once __DIR__.'/leads.php';
function pe(?string $s):string {return htmlspecialchars($s??'',ENT_QUOTES|ENT_SUBSTITUTE,'UTF-8');}
function gszPublicPdo():?PDO {try{$p=gszConnectDatabase(gszLoadConfig());return gszServiceReady($p)?$p:null;}catch(Throwable $e){return null;}}
function gszPublicStart(string $title,string $company):void {
 echo '<!doctype html><html lang="de"><head><meta charset="utf-8"><meta name="viewport" content="width=device-width,initial-scale=1"><meta name="robots" content="noindex,nofollow"><title>'.pe($title).' · '.pe($company).'</title><link rel="stylesheet" href="aussen.css?v=5"><script src="aussen.js?v=1" defer></script></head><body><header class="pub-head"><img class="pub-logo-img" src="assets/gsz-logo-horizontal.png" alt="'.pe($company).'" width="1375" height="404"></header><main class="pub">';
}
function gszPublicEnd(string $phone=''):void {echo '</main><footer class="pub-foot">'.($phone!==''?'Dringend? <a href="tel:'.pe(preg_replace('/[^0-9+]/','',$phone)).'">'.pe($phone).'</a> · ':'').'Diese Seite setzt keine Cookies.</footer></body></html>';}
function gszPublicGone():never {http_response_code(404);gszPublicStart('Link nicht gültig','Zentrum Gebäudeservice');echo '<section class="pub-card"><h1>Dieser Link ist nicht (mehr) gültig.</h1><p>Bitte den aktuellen QR-Code im Haus verwenden oder uns anrufen.</p></section>';gszPublicEnd();exit;}
