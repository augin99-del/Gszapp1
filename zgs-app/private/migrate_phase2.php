<?php
declare(strict_types=1);
require_once __DIR__.'/config_loader.php';
function gszMigratePhase2(PDO $pdo):void {
    if((int)$pdo->query("SELECT COUNT(*) FROM app_migrations WHERE version='200_phase1'")->fetchColumn()!==1)throw new RuntimeException('Voraussetzung: vollständige Phase 1 / v200.');
    foreach(['tasks'=>['scheduled_for','duration_minutes','sort_order'],'recurring_jobs'=>['rule_source','seed_key','anchor_day']] as $table=>$columns){
        $actual=$pdo->query('SHOW COLUMNS FROM '.$table)->fetchAll(PDO::FETCH_COLUMN);
        foreach($columns as $c)if(!in_array($c,$actual,true))throw new RuntimeException('Fehlende Voraussetzung: '.$table.'.'.$c);
    }
    $pdo->exec(file_get_contents(__DIR__.'/migration_v210.sql'));
    foreach(['planning_settings','planning_days','planning_appointments','planning_proposals'] as $t)$pdo->query('SELECT * FROM '.$t.' LIMIT 0');
}
if(realpath($_SERVER['SCRIPT_FILENAME']??'')===__FILE__){
 if(PHP_SAPI!=='cli'){http_response_code(403);exit;}
 try{gszMigratePhase2(gszConnectDatabase(gszLoadConfig()));echo "Phase-2-Migration geprüft und ausgeführt.\n";}catch(Throwable $e){fwrite(STDERR,"Migration gestoppt. Phase-1-Struktur, Rechte und Teilmigration prüfen; keine automatische Rücknahme von DDL.\n");exit(1);}
}
