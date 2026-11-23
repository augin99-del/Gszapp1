<?php
declare(strict_types=1);
// Geschütztes Browser-Update auf 380: App-Sicherung Pflicht, erneute Passwortbestätigung, Altbestand (außer Finanzblock) unverändert,
// Rücksicherung der App-Sicherung ergibt exakt den Ausgangsstand. Nur synthetische Daten in leeren gsz_test_final_*-Datenbanken.
set_error_handler(static function($s,$m,$f,$l){if(error_reporting()&$s)throw new ErrorException($m,0,$s,$f,$l);return false;});
require dirname(__DIR__).'/private/install_core.php';require dirname(__DIR__).'/private/web_update.php';
$dsn=getenv('GSZ_TEST_DSN')?:'';if(!preg_match('/^(mysql:unix_socket=[^;]+;dbname=)(gsz_test_final_[a-z0-9_]+)$/D',$dsn,$dm))throw new RuntimeException('Fresh synthetic local upgrade DB required.');
$opt=[PDO::ATTR_ERRMODE=>PDO::ERRMODE_EXCEPTION,PDO::ATTR_DEFAULT_FETCH_MODE=>PDO::FETCH_ASSOC,PDO::ATTR_EMULATE_PREPARES=>false];$p=new PDO($dsn,'root','',$opt);if($p->query('SHOW TABLES')->fetchAll())throw new RuntimeException('Empty DB required.');
$sourceVersion=(int)(getenv('GSZ_TEST_SOURCE_SCHEMA')?:370);if(!in_array($sourceVersion,[340,350,360,370],true))throw new RuntimeException('Only 340/350/360/370 fixtures.');
$p->exec(file_get_contents(dirname(__DIR__).'/private/fresh_install_v192.sql'));$p->exec(file_get_contents(dirname(__DIR__).'/private/migration_v200.sql'));gszMigratePhase2($p);gszMigratePhase3($p);
foreach([230,240,250,260,270,280,290,300,310,320,330,340,350,360,370] as $v)if($v<=$sourceVersion)$p->exec(file_get_contents(dirname(__DIR__).'/private/migration_v'.$v.'.sql'));
$pass='Synthetic-Final-Only!';$p->prepare('INSERT INTO admins(email,password_hash) VALUES(?,?)')->execute(['upgrade@example.invalid',password_hash($pass,PASSWORD_DEFAULT)]);
$p->exec("INSERT INTO customers(name) VALUES('Synthetic preserved client');INSERT INTO properties(name,address) VALUES('Synthetic legacy object','Preserved town');INSERT INTO tasks(property_id,title,due_date,done,plan_locked,planned_order,row_version,notes) VALUES(1,'Preserved completed task','2026-11-09',1,1,4,7,'Preserved');INSERT INTO tasks(property_id,title,due_date,done,plan_locked,planned_order,row_version) VALUES(1,'Preserved moved task','2026-11-20',0,1,2,3);INSERT INTO bank_accounts(name) VALUES('Synthetic Sparkasse test data');INSERT INTO estate_buildings(name,address) VALUES('Synthetic house','Synthetic street')");
$p->exec("INSERT INTO invoices(customer_id,recipient_name,recipient_address,issuer_name,issuer_address) VALUES(1,'Synthetic','Synthetic','Synthetic','Synthetic')");
$finance=GSZ_FINANCE_TABLES_380;$before=[];$cols=[];foreach($p->query('SHOW TABLES')->fetchAll(PDO::FETCH_COLUMN) as $t){if(in_array($t,$finance,true))continue;$cols[$t]=$p->query('SHOW COLUMNS FROM '.$t)->fetchAll(PDO::FETCH_COLUMN);$before[$t]=$p->query('SELECT * FROM '.$t.' ORDER BY 1')->fetchAll();}
$dump=static function(PDO $x):array{$o=[];foreach($x->query('SHOW TABLES')->fetchAll(PDO::FETCH_COLUMN) as $t)$o[$t]=[$x->query('SHOW CREATE TABLE `'.$t.'`')->fetch(PDO::FETCH_NUM)[1],$x->query('SELECT * FROM `'.$t.'` ORDER BY 1')->fetchAll()];ksort($o);return $o;};$original=$dump($p);
$n=0;function ug(bool $v,string $s):void{global $n;$n++;if(!$v)throw new RuntimeException($s);}function ub(callable $f,string $s):void{$blocked=false;try{$f();}catch(Throwable $e){$blocked=true;}ug($blocked,$s);}
$created=[];
try{
 ub(fn()=>gszWebUpgrade($p,['allow_web_update'=>true],1,$pass,['backup_kept'=>1,'backup_sha256'=>hash('sha256','not-a-real-backup')],'127.0.0.9'),'app backup required (invented hash refused)');
 $b=gszDatabaseBackup($p,'test');$created[]=$b['file'];ug($b['tables']===count($original)&&hash_file('sha256',gszBackupPath($b['file']))===$b['sha256'],'backup file complete with hash');ug((fileperms(gszBackupPath($b['file']))&0077)===0,'backup file private (0600)');
 ug(!str_contains(gzdecode(file_get_contents(gszBackupPath($b['file']))),$pass),'backup holds no clear-text password');
 $e=['backup_kept'=>1,'backup_sha256'=>$b['sha256']];
 ub(fn()=>gszWebUpgrade($p,['allow_web_update'=>true],1,$pass,['backup_sha256'=>$b['sha256']],'127.0.0.9'),'download confirmation required');
 ub(fn()=>gszWebUpgrade($p,['allow_web_update'=>true],1,'wrong-synthetic',$e,'127.0.0.9'),'owner reauthentication required');
 if($sourceVersion!==370)ub(fn()=>gszWebUpgrade($p,[],1,$pass,$e,'127.0.0.9'),'older than 370 needs server flag');
 ug(!(int)$p->query("SELECT COUNT(*) FROM app_migrations WHERE version='380_zentrale'")->fetchColumn(),'denied requests perform no migration');
 $r=gszWebUpgrade($p,$sourceVersion===370?[]:['allow_web_update'=>true],1,$pass,$e,'127.0.0.9');ug($r['status']==='current','update reaches 380');
 ug(gszWebUpgrade($p,[],1,$pass,$e,'127.0.0.9')['status']==='current','repeat is a no-op');
 foreach($before as $t=>$rows){if($t==='app_audit')continue;$after=$p->query('SELECT `'.implode('`,`',$cols[$t]).'` FROM '.$t.' ORDER BY 1')->fetchAll();if($t==='app_migrations'){$old=array_column($rows,'version');$after=array_values(array_filter($after,fn($r)=>in_array($r['version'],$old,true)));}ug($after===$rows,'non-finance table preserved '.$t);}
 foreach($finance as $t)ug(!$p->query("SHOW TABLES LIKE '$t'")->fetchAll(),'finance table removed '.$t);
 ug(count($p->query('SHOW TABLES')->fetchAll())===66,'400 has 66 tables');ug(gszAuditVerify($p)['valid'],'audit chain valid');
 // Rollback: restore the app backup into a second empty database → identical to the state before the update.
 $rdb=$dm[2].'_restore';$p->exec('DROP DATABASE IF EXISTS `'.$rdb.'`');$p->exec('CREATE DATABASE `'.$rdb.'` CHARACTER SET utf8mb4');$r2=new PDO($dm[1].$rdb,'root','',$opt);$res=gszDatabaseRestore($r2,gszBackupPath($b['file']));
 ug($dump($r2)===$original,'restored backup is identical to the pre-update database (structure and rows)');$p->exec('DROP DATABASE `'.$rdb.'`');
 $p->exec("INSERT INTO app_migrations(version) VALUES('999_future')");ub(fn()=>gszWebUpgrade($p,['allow_web_update'=>true],1,$pass,$e,'127.0.0.9'),'future schema downgrade refused');
}finally{foreach($created as $f){@unlink(gszBackupDir().'/'.$f);@unlink(gszBackupDir().'/'.$f.'.json');}}
echo "FINAL upgrade PASS ($n assertions; $sourceVersion -> 380; backup, rollback restore, owner and non-finance rows preserved).\n";
