<?php
declare(strict_types=1);
/**
 * Schritt 370 → 380. Wiederholbar: jeder Teil prüft vorher den Ist-Zustand.
 * 1. Fremdschlüssel anderer Module, die auf Finanztabellen zeigen, lösen (nur die Bindung, keine Spalte/Zeile).
 * 2. migration_v380.sql (Finanztabellen entfernen, Mietabgleich additiv).
 * 3. Offene Einzel-Abfallaufgaben zu einem Block je Objekt/Tag/Richtung zusammenführen.
 * 4. Versionsmarker erst danach setzen.
 * Programmdateien des entfernten Finanzblocks räumt gszRemoveObsoleteFiles380() gesondert auf.
 */
const GSZ_FINANCE_TABLES_380=['bank_connections','cash_entries','cash_accounts','asset_plan_rows','asset_events','asset_register','asset_sequences','finance_payments','finance_receipts','finance_recurrence_occurrences','finance_recurrence_proposals','finance_recurrence_revisions','finance_recurrences','invoice_reminder_proposals','invoice_reminders','invoice_events','invoice_items','invoices','invoice_sequences','offer_sequences','receipt_sequences','bank_imports','bank_transactions','bank_accounts'];
const GSZ_OBSOLETE_CONFIG_KEYS_380=['allow_unvalidated_xml','allow_invoice_issue','banking_enabled','banking_live_approved','banking_provider','banking_app_id','banking_private_key','banking_redirect_uri','bank_import_max_rows','invoice_issuer_name','invoice_issuer_address','invoice_tax_id','invoice_vat_id','invoice_iban','invoice_bic','invoice_bank'];
function gszMigrate380(PDO $pdo):array {
 require_once __DIR__.'/waste_blocks.php';
 $done=(bool)$pdo->query("SELECT COUNT(*) FROM app_migrations WHERE version='380_zentrale'")->fetchColumn();
 $finance="'".implode("','",GSZ_FINANCE_TABLES_380)."'";
 $foreign=$pdo->query("SELECT DISTINCT TABLE_NAME,CONSTRAINT_NAME FROM information_schema.KEY_COLUMN_USAGE WHERE TABLE_SCHEMA=DATABASE() AND REFERENCED_TABLE_NAME IN($finance) AND TABLE_NAME NOT IN($finance)")->fetchAll(PDO::FETCH_ASSOC);
 foreach($foreign as $fk){if(!preg_match('/^[A-Za-z0-9_]{1,64}$/D',$fk['TABLE_NAME'].$fk['CONSTRAINT_NAME']))throw new RuntimeException('GSZ_380_FOREIGN_NAME');$pdo->exec('ALTER TABLE `'.$fk['TABLE_NAME'].'` DROP FOREIGN KEY IF EXISTS `'.$fk['CONSTRAINT_NAME'].'`');}
 $sql=file_get_contents(__DIR__.'/migration_v380.sql');if($sql===false)throw new RuntimeException('Migrationsdatei 380 fehlt.');$pdo->exec($sql);
 gszRepair380($pdo);
 $admin=(int)$pdo->query('SELECT COALESCE(MIN(id),0) FROM admins')->fetchColumn();
 $pdo->beginTransaction();try{$merge=wasteMergeOpenBlocks($pdo,$admin);$pdo->exec("INSERT IGNORE INTO app_migrations(version) VALUES('380_zentrale')");$pdo->commit();}catch(Throwable $e){if($pdo->inTransaction())$pdo->rollBack();throw $e;}
 return ['already_applied'=>$done,'foreign_keys_released'=>count($foreign),'waste_blocks_merged'=>$merge['merged']];
}
/** Removes known program files of the removed finance block left over from an upload over 370. Only exact listed regular files. */
function gszRemoveObsoleteFiles380(?string $root=null):array {
 $root=realpath($root??dirname(__DIR__));$list=@file(__DIR__.'/obsolete_files_380.txt',FILE_IGNORE_NEW_LINES|FILE_SKIP_EMPTY_LINES)?:[];$removed=[];$failed=[];
 foreach($list as $rel){$rel=trim($rel);if(!preg_match('~^(public|private)/[a-z0-9_-]+\.(php|css|js)$~D',$rel))continue;$path=$root.'/'.$rel;if(is_link($path)||!is_file($path))continue;if(@unlink($path))$removed[]=$rel;else $failed[]=$rel;}
 return ['removed'=>$removed,'failed'=>$failed];
}
function gszObsoleteFilesPresent380(?string $root=null):array {
 $root=realpath($root??dirname(__DIR__));$out=[];foreach(@file(__DIR__.'/obsolete_files_380.txt',FILE_IGNORE_NEW_LINES|FILE_SKIP_EMPTY_LINES)?:[] as $rel){$rel=trim($rel);if(preg_match('~^(public|private)/[a-z0-9_-]+\.(php|css|js)$~D',$rel)&&is_file($root.'/'.$rel))$out[]=$rel;}return $out;
}
/** Removes stored settings of the removed finance block (e.g. old provider key) from the encrypted managed config. */
function gszPurgeObsoleteConfig380():int {require_once __DIR__.'/managed_config.php';try{return gszManagedRemove(GSZ_OBSOLETE_CONFIG_KEYS_380);}catch(Throwable $e){return 0;}}
/**
 * Mit 380 neu angelegte Textspalten erben bei manchen Hostern den Standardzeichensatz der alten Tabelle (z. B. utf8mb3).
 * Gleicht genau diese Spalten auf utf8mb4 an. Wiederholbar; ändert keine Inhalte (nur Hex-Prüfwerte/Namen).
 */
const GSZ_REPAIR_COLUMNS_380=[
 ['estate_bank_transactions','counterparty_iban_hash','CHAR(64) CHARACTER SET utf8mb4 NULL'],
 ['estate_tenants','iban_hash','CHAR(64) CHARACTER SET utf8mb4 NULL'],
 ['estate_tenants','payer_names',"VARCHAR(500) CHARACTER SET utf8mb4 NOT NULL DEFAULT ''"],
];
function gszRepair380(PDO $pdo):array {
 $fixed=[];$q=$pdo->prepare('SELECT CHARACTER_SET_NAME FROM information_schema.COLUMNS WHERE TABLE_SCHEMA=DATABASE() AND TABLE_NAME=? AND COLUMN_NAME=?');
 foreach(GSZ_REPAIR_COLUMNS_380 as [$t,$c,$def]){$q->execute([$t,$c]);$cs=$q->fetchColumn();if($cs!==false&&$cs!=='utf8mb4'){$pdo->exec('ALTER TABLE `'.$t.'` MODIFY `'.$c.'` '.$def);$fixed[]=$t.'.'.$c;}}
 return $fixed;
}
/** True if the only structural differences are the known, repairable 380 column charsets. */
function gszRepairable380(array $inspection):bool {
 if(!in_array($inspection['version']??null,[380,390,400,410,420,430,440,450],true)||($inspection['code']??'')!=='GSZ_SCHEMA_STRUCTURE_MISMATCH'||empty($inspection['blockers']))return false;
 $ok=array_map(fn($x)=>$x[0].'.'.$x[1].':shape',GSZ_REPAIR_COLUMNS_380);foreach($inspection['blockers'] as $b)if(!in_array($b,$ok,true))return false;return true;
}
