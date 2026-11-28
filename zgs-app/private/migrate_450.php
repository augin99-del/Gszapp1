<?php
declare(strict_types=1);
/** Schritt 440 → 450: Verträge je Objekt. Rein additiv und wiederholbar. */
function gszMigrate450(PDO $pdo):array {
 if(!(int)$pdo->query("SELECT COUNT(*) FROM app_migrations WHERE version='440_push'")->fetchColumn())throw new RuntimeException('Stand 440 erforderlich.');
 $done=(bool)$pdo->query("SELECT COUNT(*) FROM app_migrations WHERE version='450_vertraege'")->fetchColumn();
 $sql=file_get_contents(__DIR__.'/migration_v450.sql');if($sql===false)throw new RuntimeException('Migrationsdatei 450 fehlt.');$pdo->exec($sql);
 if(!(int)$pdo->query("SELECT COUNT(*) FROM app_migrations WHERE version='450_vertraege'")->fetchColumn())throw new RuntimeException('Migration 450 unvollständig.');
 return ['already_applied'=>$done];
}
