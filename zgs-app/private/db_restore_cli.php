<?php
/** Rücksicherung per SSH/CLI: php private/db_restore_cli.php gsz-sicherung-….sql.gz --bestaetigt
 *  Ersetzt ALLE Tabellen der konfigurierten Datenbank durch den Stand der Sicherung. Nicht im Browser aufrufbar. */
declare(strict_types=1);
if(PHP_SAPI!=='cli'){http_response_code(403);exit;}
require_once __DIR__.'/config_loader.php';require_once __DIR__.'/db_backup.php';
$file=(string)($argv[1]??'');if($file===''||!in_array('--bestaetigt',$argv,true)){fwrite(STDERR,"Aufruf: php private/db_restore_cli.php <sicherungsdatei.sql.gz> --bestaetigt\nVerfügbare Sicherungen:\n");foreach(gszBackupList(20) as $m)fwrite(STDERR,'  '.$m['file'].'  '.$m['created_at'].'  sha256 '.$m['sha256']."\n");exit(1);}
try{$path=str_contains($file,'/')?$file:gszBackupPath(basename($file));$pdo=gszConnectDatabase(gszLoadConfig());$r=gszDatabaseRestore($pdo,$path);echo "Rücksicherung abgeschlossen: {$r['tables']} Tabellen, {$r['statements']} Anweisungen.\nJetzt die zur Sicherung passenden Programmdateien (z. B. Paket 370) wieder hochladen.\n";}
catch(Throwable $e){fwrite(STDERR,"Rücksicherung gestoppt (".(str_starts_with($e->getMessage(),'GSZ_')?$e->getMessage():'Datenbankfehler')."). Keine Zugangsdaten ausgegeben.\n");exit(2);}
