<?php
declare(strict_types=1);
require_once __DIR__.'/config_loader.php';
function gszMigratePhase3(PDO $pdo):void {
 if((int)$pdo->query("SELECT COUNT(*) FROM app_migrations WHERE version='210_phase2'")->fetchColumn()!==1)throw new RuntimeException('Phase 2 / v210 fehlt.');
 foreach(['tasks'=>['plan_locked','planned_order','scheduled_for'],'planning_settings'=>['buffer_minutes'],'recurring_jobs'=>['rule_source']] as $table=>$columns){$actual=$pdo->query('SHOW COLUMNS FROM '.$table)->fetchAll(PDO::FETCH_COLUMN);foreach($columns as $c)if(!in_array($c,$actual,true))throw new RuntimeException('Fehlende Spalte: '.$table.'.'.$c);}
 $pdo->exec(file_get_contents(__DIR__.'/migration_v220.sql'));
}
if(realpath($_SERVER['SCRIPT_FILENAME']??'')===__FILE__){if(PHP_SAPI!=='cli'){http_response_code(403);exit;}try{gszMigratePhase3(gszConnectDatabase(gszLoadConfig()));echo "Migration Phase 3 ausgeführt.\n";}catch(Throwable $e){fwrite(STDERR,"Migration gestoppt: Phase-2-Schema, Rechte und Teilmigration prüfen.\n");exit(1);}}
