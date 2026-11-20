<?php
declare(strict_types=1);
require_once __DIR__.'/setup_schema.php';require_once __DIR__.'/platform.php';require_once __DIR__.'/release_integrity.php';require_once __DIR__.'/db_backup.php';require_once __DIR__.'/migrate_380.php';
/** Is the database on the schema this package expects? Cheap check for every request. */
function gszSchemaCurrent(PDO $pdo):bool {try{return (bool)$pdo->query("SELECT COUNT(*) FROM app_migrations WHERE version='380_zentrale'")->fetchColumn();}catch(PDOException $e){return false;}}
/**
 * Owner-only update of an existing database. Never a fresh installer or password reset.
 * Requires: re-entered owner password, an intact app backup of the last two hours, confirmed download of that backup,
 * a complete release upload. The direct predecessor 370 is allowed without the server flag; any older stand needs allow_web_update.
 */
function gszWebUpgrade(PDO $pdo,array $config,int $admin,string $password,array $evidence,string $ip):array {
 if($admin<1)throw new RuntimeException('Anmeldung erforderlich.');
 $before=gszSetupInspect($pdo);if($before['status']==='current')return ['status'=>'current'];
 if(!$before['can_migrate']||$before['status']!=='upgrade')throw new RuntimeException(gszSetupSchemaMessage($before['code']));
 if($before['version']!==370&&!filter_var($config['allow_web_update']??false,FILTER_VALIDATE_BOOLEAN))throw new RuntimeException('Dieser ältere Datenbankstand wird nur mit serverseitiger Freigabe (allow_web_update) aktualisiert.');
 if(empty($evidence['backup_kept']))throw new RuntimeException('Bitte bestätigen, dass die Sicherung heruntergeladen und aufbewahrt ist.');
 $backup=gszRecentBackup((string)($evidence['backup_sha256']??''));
 $q=$pdo->prepare('SELECT email,password_hash FROM admins WHERE id=?');$q->execute([$admin]);$owner=$q->fetch(PDO::FETCH_ASSOC);
 if(!$owner||!gszLoginAttempt('update:'.$owner['email'],$ip)||!password_verify($password,$owner['password_hash']))throw new RuntimeException('Passwort nicht bestätigt (oder zu viele Versuche – bitte 15 Minuten warten).');
 if(gszReleaseIntegrity()['status']!=='PASS')throw new RuntimeException('Programmdateien unvollständig hochgeladen. Bitte das komplette Paket erneut hochladen.');
 gszAudit($pdo,$admin,'web_upgrade_requested',['from'=>$before['version'],'target'=>380,'backup_sha256'=>$backup['sha256'],'backup_file'=>$backup['file']]);
 $after=gszSetupMigrate($pdo);$files=gszRemoveObsoleteFiles380();$keys=gszPurgeObsoleteConfig380();require_once __DIR__.'/mail_credentials.php';try{$mailMoved=gszMailCredentialMigrate($config);}catch(Throwable $e){$mailMoved=false;}
 gszAudit($pdo,$admin,'web_upgrade_finished',['target'=>380,'obsolete_files_removed'=>count($files['removed']),'obsolete_files_failed'=>$files['failed'],'obsolete_settings_removed'=>$keys,'mail_password_moved_to_vault'=>$mailMoved]);
 gszLoginAttempt('update:'.$owner['email'],$ip,true);
 return ['status'=>$after['status'],'files_failed'=>$files['failed']];
}
