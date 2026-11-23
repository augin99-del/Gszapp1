<?php
declare(strict_types=1);
require_once __DIR__.'/migrate_phase2.php';require_once __DIR__.'/migrate_phase3.php';require_once __DIR__.'/migrate_gesamt.php';
/** Fresh means EMPTY. Never tries to resume a partially-created fresh schema by guessing. */
function gszInstallFresh(PDO $pdo):array {
 $version=(string)$pdo->query('SELECT VERSION()')->fetchColumn();preg_match('/(?:5\.5\.5-)?(\d+\.\d+\.\d+)/',$version,$m);if(!str_contains(strtolower($version),'mariadb')||!isset($m[1])||version_compare($m[1],'10.6.0','<'))throw new RuntimeException('GSZ_INSTALL_DATABASE_ENGINE');
 if((string)$pdo->query('SELECT @@character_set_database')->fetchColumn()!=='utf8mb4')throw new RuntimeException('GSZ_INSTALL_DATABASE_CHARSET');
 $database=(string)$pdo->query('SELECT DATABASE()')->fetchColumn();if($database==='')throw new RuntimeException('GSZ_INSTALL_NO_DATABASE');
 $lock='gsz_install_'.substr(hash('sha256',$database),0,32);$q=$pdo->prepare('SELECT GET_LOCK(?,0)');$q->execute([$lock]);if((int)$q->fetchColumn()!==1)throw new RuntimeException('GSZ_INSTALL_BUSY');
 try{
  if($pdo->query('SHOW FULL TABLES')->fetchAll())throw new RuntimeException('GSZ_INSTALL_DATABASE_NOT_EMPTY');
  $files=['fresh_install_v192.sql','migration_v200.sql','migration_v210.sql','migration_v220.sql','migration_v230.sql','migration_v240.sql','migration_v250.sql','migration_v260.sql','migration_v270.sql','migration_v280.sql','migration_v290.sql','migration_v300.sql','migration_v310.sql','migration_v320.sql','migration_v330.sql','migration_v340.sql','migration_v350.sql','migration_v360.sql','migration_v370.sql','migration_v380.sql','migration_v390.sql'];foreach($files as $f)if(!is_file(__DIR__.'/'.$f))throw new RuntimeException('GSZ_INSTALL_MISSING_FILE');
  $pdo->exec(file_get_contents(__DIR__.'/fresh_install_v192.sql'));$pdo->exec(file_get_contents(__DIR__.'/migration_v200.sql'));gszMigratePhase2($pdo);gszMigratePhase3($pdo);gszMigrateGesamt($pdo);
  return ['tables'=>count($pdo->query('SHOW TABLES')->fetchAll()),'schema'=>'390','admin_created'=>false,'objects_seeded'=>false];
 }finally{$q=$pdo->prepare('SELECT RELEASE_LOCK(?)');$q->execute([$lock]);}
}
