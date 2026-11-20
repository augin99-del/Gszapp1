<?php
declare(strict_types=1);
require_once __DIR__.'/migrate_gesamt.php';require_once __DIR__.'/platform.php';require_once __DIR__.'/release_integrity.php';
/** Owner-only update of an existing database. Never a fresh installer or password reset. */
function gszWebUpgrade(PDO $pdo,array $config,int $admin,string $password,array $evidence,string $ip):void {
 if(!filter_var($config['allow_web_update']??false,FILTER_VALIDATE_BOOLEAN)||$admin<1)throw new RuntimeException('Webupdate serverseitig nicht freigegeben.');
 foreach(['files_backed_up','db_backed_up','restore_checked','exclusive_window'] as $key)if(empty($evidence[$key]))throw new RuntimeException('Datei-/Datenbanksicherung, Restoreprüfung und exklusives Wartungsfenster bestätigen.');
 $hash=(string)($evidence['backup_sha256']??'');if(!preg_match('/^[a-f0-9]{64}$/D',$hash))throw new RuntimeException('SHA256 der tatsächlich geprüften Datenbanksicherung erforderlich.');
 $q=$pdo->prepare('SELECT email,password_hash FROM admins WHERE id=?');$q->execute([$admin]);$owner=$q->fetch(PDO::FETCH_ASSOC);
 if(!$owner||!gszLoginAttempt('update:'.$owner['email'],$ip)||!password_verify($password,$owner['password_hash']))throw new RuntimeException('Erneute Anmeldung nicht bestätigt oder zeitweise gesperrt.');
 foreach($pdo->query('SELECT version FROM app_migrations')->fetchAll(PDO::FETCH_COLUMN) as $v)if((int)$v>370)throw new RuntimeException('Neuere Datenbankversion: kein Downgrade zulässig.');
 if(gszReleaseIntegrity()['status']!=='PASS')throw new RuntimeException('Release unvollständig/abweichend: zuerst Upload und Dateimanifest prüfen.');
 gszAudit($pdo,$admin,'web_upgrade_requested',['target'=>370,'backup_sha256'=>$hash,'restoration_attested'=>true]);
 gszMigrateGesamt($pdo);gszAudit($pdo,$admin,'web_upgrade_finished',['target'=>370]);
 gszLoginAttempt('update:'.$owner['email'],$ip,true);
}
