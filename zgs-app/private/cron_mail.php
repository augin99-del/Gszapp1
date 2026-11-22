<?php
declare(strict_types=1);
// Einstieg für Cron-Oberflächen, die keine Aufrufparameter erlauben (z. B. STRATO-Cronjob-Verwaltung):
// entspricht „cron_gesamt.php --mail-sync“. Nur über die Kommandozeile, nie über HTTP.
if(PHP_SAPI!=='cli'){http_response_code(403);exit;}
$argv=[__FILE__,'--mail-sync'];$_SERVER['argv']=$argv;$argc=2;
require __DIR__.'/cron_gesamt.php';
