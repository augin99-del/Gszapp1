<?php
declare(strict_types=1);
// Öffentlich: Notdienst-Status für die Website (frei / im Einsatz). Keine Personendaten, keine Cookies.
require dirname(__DIR__).'/private/web_public.php';
header('Content-Type: application/json; charset=utf-8');header('Cache-Control: public, max-age=60');
$pdo=gszWebPdo();if(!$pdo){http_response_code(503);echo '{"status":"unbekannt"}';exit;}
$o=gszWebOrigin($pdo);if($o){header('Access-Control-Allow-Origin: '.$o);header('Vary: Origin');}
$s=gszSosStatus($pdo);echo json_encode(['status'=>$s['status'],'text'=>$s['text'],'short'=>$s['short']],JSON_UNESCAPED_UNICODE);
