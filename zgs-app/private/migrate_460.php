<?php
declare(strict_types=1);
/** Schritt 450 → 460: Angebote und Schlüsselverwaltung. Rein additiv und wiederholbar. */
function gszMigrate460(PDO $pdo):array {
 if(!(int)$pdo->query("SELECT COUNT(*) FROM app_migrations WHERE version='450_vertraege'")->fetchColumn())throw new RuntimeException('Stand 450 erforderlich.');
 $done=(bool)$pdo->query("SELECT COUNT(*) FROM app_migrations WHERE version='460_angebote'")->fetchColumn();
 $sql=file_get_contents(__DIR__.'/migration_v460.sql');if($sql===false)throw new RuntimeException('Migrationsdatei 460 fehlt.');$pdo->exec($sql);
 if(!(int)$pdo->query("SELECT COUNT(*) FROM app_migrations WHERE version='460_angebote'")->fetchColumn())throw new RuntimeException('Migration 460 unvollständig.');
 return ['already_applied'=>$done];
}
