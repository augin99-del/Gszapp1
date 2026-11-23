<?php
declare(strict_types=1);
/** Schritt 380 → 390: Merkliste (quick_notes). Rein additiv und wiederholbar; setzt den Marker im selben SQL-Skript zuletzt. */
function gszMigrate390(PDO $pdo):array {
 if(!(int)$pdo->query("SELECT COUNT(*) FROM app_migrations WHERE version='380_zentrale'")->fetchColumn())throw new RuntimeException('Stand 380 erforderlich.');
 $done=(bool)$pdo->query("SELECT COUNT(*) FROM app_migrations WHERE version='390_notizen'")->fetchColumn();
 $sql=file_get_contents(__DIR__.'/migration_v390.sql');if($sql===false)throw new RuntimeException('Migrationsdatei 390 fehlt.');$pdo->exec($sql);
 if(!(int)$pdo->query("SELECT COUNT(*) FROM app_migrations WHERE version='390_notizen'")->fetchColumn())throw new RuntimeException('Migration 390 unvollständig.');
 return ['already_applied'=>$done];
}
