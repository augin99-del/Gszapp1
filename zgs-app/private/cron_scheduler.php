<?php
/** Optionaler SSH/CLI-Cronjob; NICHT im public-Verzeichnis ablegen. */
declare(strict_types=1);
if (PHP_SAPI !== 'cli') { http_response_code(403); exit; }
require_once __DIR__.'/config_loader.php';
$config=gszLoadConfig();if(filter_var($config['maintenance']??false,FILTER_VALIDATE_BOOLEAN)){fwrite(STDERR,"GSZ_CRON_MAINTENANCE\n");exit(2);}
$pdo=gszConnectDatabase($config);
date_default_timezone_set('Europe/Berlin');
require __DIR__.'/scheduler.php';
if(gszV1Ready($pdo)){require_once __DIR__.'/automation.php';$result=gszRunAutomation($pdo,$config);}else $result=zgsGenerateDueTasks($pdo,date('Y-m-d'));
echo json_encode($result,JSON_UNESCAPED_UNICODE),PHP_EOL;
