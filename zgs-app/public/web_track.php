<?php
declare(strict_types=1);
// Öffentlich: cookiefreie Website-Zählung. Gespeichert werden nur Datum, Seitenpfad, Ereignis und eine Zahl – keine IP, keine Kennung.
require dirname(__DIR__).'/private/web_public.php';
if(($_SERVER['REQUEST_METHOD']??'')!=='POST'){http_response_code(405);exit;}
$pdo=gszWebPdo();if(!$pdo){http_response_code(204);exit;}
$o=gszWebOrigin($pdo);if(!$o){http_response_code(204);exit;}header('Access-Control-Allow-Origin: '.$o);
$raw=(string)file_get_contents('php://input',false,null,0,400);$d=json_decode($raw,true);
if(is_array($d)&&is_string($d['p']??null)&&is_string($d['e']??null))gszWebCount($pdo,$d['p'],$d['e']);
http_response_code(204);
