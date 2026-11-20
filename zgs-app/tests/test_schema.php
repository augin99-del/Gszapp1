<?php
declare(strict_types=1);
$root=dirname(__DIR__).'/private/';
$bundle=file_get_contents($root.'fresh_install_v192.sql');
foreach(['admins','customers','properties','tasks','recurring_jobs','invoices','invoice_items','work_logs','work_log_photos','documents'] as $table){
 if(!preg_match('/CREATE TABLE(?: IF NOT EXISTS)? '.preg_quote($table,'/').'\s*\(/',$bundle)) throw new RuntimeException('Missing table '.$table);
}
foreach(['migration_v16.sql'=>['property_id INT UNSIGNED NOT NULL','task_id INT UNSIGNED NULL'], 'migration_v19.sql'=>['customer_id INT UNSIGNED NULL','property_id INT UNSIGNED NULL']] as $file=>$needles){
 $text=file_get_contents($root.$file);
 foreach($needles as $needle) if(!str_contains($text,$needle)) throw new RuntimeException('FK mismatch: '.$file.' / '.$needle);
}
$setupPath=dirname(__DIR__).'/public/setup.php';
if(is_file($setupPath)) {
 $setup=file_get_contents($setupPath);
 if(str_contains($setup,"explode(';',")) throw new RuntimeException('Unsafe SQL splitting in setup');
} // setup.php is intentionally absent in the post-setup deployment.
echo "Schema static checks passed (not a database integration test).\n";
