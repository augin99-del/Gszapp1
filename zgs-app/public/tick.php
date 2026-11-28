<?php
declare(strict_types=1);
// Öffentlich, aber nur mit geheimem Schlüssel: „Wecker“ für Hintergrundaufgaben ohne STRATO-Cronjob
// (z. B. alle 15 Minuten von cron-job.org aufgerufen). Prüft DWD-Warnungen, ruft Mails ab, übernimmt Anfragen, sendet Push.
require dirname(__DIR__).'/private/web_public.php';
header('Content-Type: application/json; charset=utf-8');header('Cache-Control: no-store');
$pdo=gszWebPdo();if(!$pdo){http_response_code(503);echo '{"ok":false}';exit;}
require_once dirname(__DIR__).'/private/weather.php';
$tok=gszSetting($pdo,'tick_token');$k=(string)($_GET['k']??'');
if(strlen($tok)<32||!hash_equals($tok,$k)){usleep(300000);http_response_code(403);echo '{"ok":false}';exit;}
@set_time_limit(120);ignore_user_abort(true);$out=['ok'=>true];
try{$w=gszWeatherTick($pdo);$out['weather']=$w['checked']?'geprüft':'aktuell';}catch(Throwable $e){$out['weather']='fehler';}
try{require_once dirname(__DIR__).'/private/automation.php';$config=gszLoadConfig();$m=gszAutomationOnOpen($pdo,$config,'mail');$out['mail']=$m===null?'übersprungen':($m['status']??'');}catch(Throwable $e){$out['mail']='fehler';}
try{$out['leads']=gszLeadsFromMail($pdo);}catch(Throwable $e){$out['leads']='fehler';}
try{$r=gszAutomationOnOpen($pdo,gszLoadConfig(),'heute');$out['tasks']=$r===null?'übersprungen':($r['status']??'');}catch(Throwable $e){$out['tasks']='fehler';}
try{require_once dirname(__DIR__).'/private/backup_auto.php';$b=gszBackupAutoRun($pdo);$out['backup']=$b?'erstellt':'aktuell';}catch(Throwable $e){$out['backup']='fehler';error_log('GSZ auto backup: '.$e->getMessage());}
try{require_once dirname(__DIR__).'/private/push_digest.php';$out['push']=count(gszPushDigests($pdo));}catch(Throwable $e){$out['push']='fehler';}
try{gszSettingSet($pdo,'tick_last',gmdate('Y-m-d H:i:s'));}catch(Throwable $e){}
echo json_encode($out,JSON_UNESCAPED_UNICODE);
