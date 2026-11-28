<?php
declare(strict_types=1);
/** Schritt 420 → 430: Mieter-Meldungen, Hausverwaltungs-Portal, Helfer-Zugang, Schlüsseldienst-Einsätze. Rein additiv und wiederholbar. */
function gszMigrate430(PDO $pdo):array {
 if(!(int)$pdo->query("SELECT COUNT(*) FROM app_migrations WHERE version='420_anfragen'")->fetchColumn())throw new RuntimeException('Stand 420 erforderlich.');
 $done=(bool)$pdo->query("SELECT COUNT(*) FROM app_migrations WHERE version='430_service'")->fetchColumn();
 $sql=file_get_contents(__DIR__.'/migration_v430.sql');if($sql===false)throw new RuntimeException('Migrationsdatei 430 fehlt.');$pdo->exec($sql);
 if(!(int)$pdo->query("SELECT COUNT(*) FROM app_migrations WHERE version='430_service'")->fetchColumn())throw new RuntimeException('Migration 430 unvollständig.');
 return ['already_applied'=>$done];
}
