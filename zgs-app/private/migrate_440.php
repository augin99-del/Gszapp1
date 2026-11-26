<?php
declare(strict_types=1);
/** Schritt 430 → 440: Push-Benachrichtigungen und DWD-Warnungen für den Winterdienst. Rein additiv und wiederholbar. */
function gszMigrate440(PDO $pdo):array {
 if(!(int)$pdo->query("SELECT COUNT(*) FROM app_migrations WHERE version='430_service'")->fetchColumn())throw new RuntimeException('Stand 430 erforderlich.');
 $done=(bool)$pdo->query("SELECT COUNT(*) FROM app_migrations WHERE version='440_push'")->fetchColumn();
 $sql=file_get_contents(__DIR__.'/migration_v440.sql');if($sql===false)throw new RuntimeException('Migrationsdatei 440 fehlt.');$pdo->exec($sql);
 if(!(int)$pdo->query("SELECT COUNT(*) FROM app_migrations WHERE version='440_push'")->fetchColumn())throw new RuntimeException('Migration 440 unvollständig.');
 return ['already_applied'=>$done];
}
