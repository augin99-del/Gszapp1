<?php
declare(strict_types=1);
// Upgrade 260 → 380 auf synthetischem Altbestand: alle Nicht-Finanz-Tabellen bleiben zeilen- und spaltengleich,
// reine Finanztabellen sind danach entfernt, die Migration ist wiederholbar.
require dirname(__DIR__).'/private/migrate_phase2.php';require dirname(__DIR__).'/private/migrate_phase3.php';require dirname(__DIR__).'/private/migrate_gesamt.php';require_once dirname(__DIR__).'/private/platform.php';
$dsn=getenv('GSZ_TEST_DSN')?:'';if(!preg_match('/^mysql:unix_socket=[^;]+;dbname=gsz_test_[a-z0-9_]+$/D',$dsn))throw new RuntimeException('Local empty test database required.');$pdo=new PDO($dsn,'root','',[PDO::ATTR_ERRMODE=>PDO::ERRMODE_EXCEPTION,PDO::ATTR_DEFAULT_FETCH_MODE=>PDO::FETCH_ASSOC,PDO::ATTR_EMULATE_PREPARES=>false]);if($pdo->query('SHOW TABLES')->fetchAll())throw new RuntimeException('Empty database required.');$root=dirname(__DIR__);
$pdo->exec(file_get_contents($root.'/private/fresh_install_v192.sql'));$pdo->exec(file_get_contents($root.'/private/migration_v200.sql'));gszMigratePhase2($pdo);gszMigratePhase3($pdo);foreach([230,240,250,260] as $v)$pdo->exec(file_get_contents($root.'/private/migration_v'.$v.'.sql'));
$pdo->exec("INSERT INTO customers(name) VALUES('Synthetic upgrade client');INSERT INTO properties(name,address) VALUES('Synthetic legacy property','Unchanged address');INSERT INTO tasks(property_id,title,due_date,done,notes,plan_locked,planned_order,sort_order) VALUES(1,'Protected historical task','2026-11-09',1,'Keep',1,3,2);INSERT INTO planning_appointments(day,start_time,end_time,title) VALUES('2026-11-10','13:00','14:30','Legacy fixed appointment');INSERT INTO admins(email,password_hash) VALUES('owner@example.invalid','synthetic')");
$pdo->exec("INSERT INTO invoices(customer_id,property_id,recipient_name,recipient_address,issuer_name,issuer_address) VALUES(1,1,'Synthetic','Synthetic','Synthetic','Synthetic')");$pdo->exec("INSERT INTO bank_accounts(name) VALUES('Synthetic Sparkasse test data')");
$finance=GSZ_FINANCE_TABLES_380;
$before=[];$cols=[];foreach($pdo->query('SHOW TABLES')->fetchAll(PDO::FETCH_COLUMN) as $table){if(in_array($table,$finance,true))continue;$cols[$table]=$pdo->query('SHOW COLUMNS FROM '.$table)->fetchAll(PDO::FETCH_COLUMN);$before[$table]=$pdo->query('SELECT * FROM '.$table.' ORDER BY 1')->fetchAll();}
gszMigrateGesamt($pdo);gszMigrateGesamt($pdo);
foreach($before as $table=>$rows){$after=$pdo->query('SELECT `'.implode('`,`',$cols[$table]).'` FROM '.$table.' ORDER BY 1')->fetchAll();if($table==='app_migrations'){$old=array_column($rows,'version');$after=array_values(array_filter($after,fn($a)=>in_array($a['version'],$old,true)));}if($after!==$rows)throw new RuntimeException('Historical rows changed: '.$table);}
foreach($finance as $t)if($pdo->query("SHOW TABLES LIKE '$t'")->fetchAll())throw new RuntimeException('Finance table still present: '.$t);
if(!(int)$pdo->query("SELECT COUNT(*) FROM app_migrations WHERE version='380_zentrale'")->fetchColumn())throw new RuntimeException('380 marker missing');
if(!gszAuditVerify($pdo)['valid'])throw new RuntimeException('Audit invalid.');
echo 'Overall upgrade v260→380 passed ('.count($before).' non-finance tables unchanged; finance tables removed; repeated migration).',PHP_EOL;
