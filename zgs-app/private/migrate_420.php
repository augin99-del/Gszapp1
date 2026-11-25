<?php
declare(strict_types=1);
/** Schritt 410 → 420: Anfragen, Website-Zahlen, Einstellungen. Rein additiv und wiederholbar. */
function gszMigrate420(PDO $pdo):array {
 if(!(int)$pdo->query("SELECT COUNT(*) FROM app_migrations WHERE version='410_projekte'")->fetchColumn())throw new RuntimeException('Stand 410 erforderlich.');
 $done=(bool)$pdo->query("SELECT COUNT(*) FROM app_migrations WHERE version='420_anfragen'")->fetchColumn();
 $sql=file_get_contents(__DIR__.'/migration_v420.sql');if($sql===false)throw new RuntimeException('Migrationsdatei 420 fehlt.');$pdo->exec($sql);
 if(!(int)$pdo->query("SELECT COUNT(*) FROM app_migrations WHERE version='420_anfragen'")->fetchColumn())throw new RuntimeException('Migration 420 unvollständig.');
 return ['already_applied'=>$done];
}
