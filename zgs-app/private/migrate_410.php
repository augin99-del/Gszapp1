<?php
declare(strict_types=1);
/** Schritt 400 → 410: Bauprojekte. Rein additiv und wiederholbar. */
function gszMigrate410(PDO $pdo):array {
 if(!(int)$pdo->query("SELECT COUNT(*) FROM app_migrations WHERE version='400_ferienwohnung'")->fetchColumn())throw new RuntimeException('Stand 400 erforderlich.');
 $done=(bool)$pdo->query("SELECT COUNT(*) FROM app_migrations WHERE version='410_projekte'")->fetchColumn();
 $sql=file_get_contents(__DIR__.'/migration_v410.sql');if($sql===false)throw new RuntimeException('Migrationsdatei 410 fehlt.');$pdo->exec($sql);
 if(!(int)$pdo->query("SELECT COUNT(*) FROM app_migrations WHERE version='410_projekte'")->fetchColumn())throw new RuntimeException('Migration 410 unvollständig.');
 return ['already_applied'=>$done];
}
