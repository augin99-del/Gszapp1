<?php
declare(strict_types=1);
// Öffentlich: Preisspanne Treppenhausreinigung für die Website. Keine Personendaten, keine Cookies. Ohne eingestellte Sätze: {"enabled":false}.
require dirname(__DIR__).'/private/web_public.php';require_once dirname(__DIR__).'/private/calc.php';
header('Content-Type: application/json; charset=utf-8');header('Cache-Control: no-store');
$pdo=gszWebPdo();if(!$pdo){http_response_code(503);echo '{"enabled":false}';exit;}
$o=gszWebOrigin($pdo);if($o){header('Access-Control-Allow-Origin: '.$o);header('Vary: Origin');}
try{$q=gszCalcQuote($pdo,$_GET);}catch(Throwable $e){$q=null;}
echo json_encode($q?['enabled'=>true]+$q:['enabled'=>false],JSON_UNESCAPED_UNICODE);
