<?php
declare(strict_types=1);
/** Schritt 390 → 400: Ferienwohnung (Belegungskalender → Reinigungen). Rein additiv und wiederholbar. */
function gszMigrate400(PDO $pdo):array {
 if(!(int)$pdo->query("SELECT COUNT(*) FROM app_migrations WHERE version='390_notizen'")->fetchColumn())throw new RuntimeException('Stand 390 erforderlich.');
 $done=(bool)$pdo->query("SELECT COUNT(*) FROM app_migrations WHERE version='400_ferienwohnung'")->fetchColumn();
 $sql=file_get_contents(__DIR__.'/migration_v400.sql');if($sql===false)throw new RuntimeException('Migrationsdatei 400 fehlt.');$pdo->exec($sql);
 if(!(int)$pdo->query("SELECT COUNT(*) FROM app_migrations WHERE version='400_ferienwohnung'")->fetchColumn())throw new RuntimeException('Migration 400 unvollständig.');
 return ['already_applied'=>$done];
}
