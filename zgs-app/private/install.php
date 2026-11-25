<?php
declare(strict_types=1);
if(PHP_SAPI!=='cli'){http_response_code(403);exit;}
require_once __DIR__.'/install_core.php';require_once __DIR__.'/release_integrity.php';
$mode=$argv[1]??'';
if(!in_array($mode,['--fresh','--upgrade'],true)||($mode==='--upgrade'&&($argv[2]??'')!=='--backup-confirmed')||count($argv)!==($mode==='--upgrade'?3:2)){fwrite(STDERR,"Nur nach Sicherung und Stagingprüfung: php private/install.php --fresh ODER --upgrade --backup-confirmed. Fresh verweigert jede nicht leere Datenbank; Upgrade verlangt vollständige v220-Struktur. Kein Webinstaller.\n");exit(2);}
try{
 if(gszReleaseIntegrity()['status']!=='PASS')throw new RuntimeException('GSZ_INSTALL_INCOMPLETE_UPLOAD');
 if(PHP_VERSION_ID<80200||PHP_INT_SIZE!==8||!extension_loaded('pdo_mysql')||!extension_loaded('mbstring'))throw new RuntimeException('GSZ_INSTALL_RUNTIME');
 $pdo=gszConnectDatabase(gszLoadConfig());$v=(string)$pdo->query('SELECT VERSION()')->fetchColumn();if(!str_contains(strtolower($v),'mariadb'))throw new RuntimeException('GSZ_INSTALL_DATABASE_ENGINE');
 if($mode==='--fresh')echo json_encode(gszInstallFresh($pdo),JSON_THROW_ON_ERROR),"\n";
 else{require_once __DIR__.'/db_backup.php';require_once __DIR__.'/migrate_380.php';require_once __DIR__.'/mail_credentials.php';$b=gszDatabaseBackup($pdo,'cli-update');echo "Sicherung: private/backups/{$b['file']} (sha256 {$b['sha256']})\n";gszMigrateGesamt($pdo);$f=gszRemoveObsoleteFiles380();gszPurgeObsoleteConfig380();try{gszMailCredentialMigrate(gszLoadConfig());}catch(Throwable $e){}echo "Update auf Schema 430 ausgeführt. Alte Dateien entfernt: ".count($f['removed']).(count($f['failed'])?' – nicht löschbar: '.implode(', ',$f['failed']):'').". Jetzt Systemstatus prüfen.\n";}
}catch(Throwable $e){$code=preg_match('/^GSZ_INSTALL_[A-Z_]+$/D',$e->getMessage())?$e->getMessage():'GSZ_INSTALL_FAILED';fwrite(STDERR,$code.". Keine Datenbank gelöscht. Bei Teil-DDL Struktur prüfen; Fresh nicht auf bestehenden Tabellen wiederholen. Konfiguration/Zugangsdaten werden nicht ausgegeben.\n");exit(1);}
