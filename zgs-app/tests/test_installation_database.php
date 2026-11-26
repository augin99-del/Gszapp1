<?php
declare(strict_types=1);
set_error_handler(static function($s,$m,$f,$l){if(error_reporting()&$s)throw new ErrorException($m,0,$s,$f,$l);return false;});
require dirname(__DIR__).'/private/install_core.php';require dirname(__DIR__).'/private/system_check.php';
$dsn=getenv('GSZ_TEST_DSN')?:'';if(!preg_match('/^mysql:unix_socket=[^;]+;dbname=gsz_test_install_[a-z0-9_]+$/D',$dsn))throw new RuntimeException('Empty local installation test DB required.');
$p=new PDO($dsn,'root','',[PDO::ATTR_ERRMODE=>PDO::ERRMODE_EXCEPTION,PDO::ATTR_DEFAULT_FETCH_MODE=>PDO::FETCH_ASSOC,PDO::ATTR_EMULATE_PREPARES=>false]);if($p->query('SHOW TABLES')->fetchAll())throw new RuntimeException('Empty DB required.');
ini_set('session.save_path',sys_get_temp_dir());
$n=0;function gi(bool $v,string $message):void{global $n;$n++;if(!$v)throw new RuntimeException($message);}
function row(array $report,string $id):array{foreach($report['checks'] as $r)if($r['id']===$id)return $r;throw new RuntimeException('Missing check');}
$c=require dirname(__DIR__).'/private/config.example.php';$c=array_replace($c,['db_host'=>'localhost','db_socket'=>'local-test-socket','db_name'=>'synthetic','db_user'=>'synthetic','db_pass'=>'not-an-actual-password']);$ctx=['web_root'=>dirname(__DIR__).'/public'];
gi(gszDmlGrantStatus(['GRANT SELECT ON `example`.* TO `readonly`@`localhost`'],'example')==='block','read-only account blocked');
gi(gszDmlGrantStatus(['GRANT ALL PRIVILEGES ON `example`.* TO `owner`@`localhost`'],'example')==='ok','direct schema permissions recognized');
gi(gszDmlGrantStatus(['GRANT `runtime_role` TO `owner`@`localhost`'],'example')==='warn','indirect role never falsely confirmed');
gi(gszIniBytes('10M')===10485760&&gszIniBytes('1G')===1073741824&&gszIniBytes('-1')===PHP_INT_MAX&&gszIniBytes('bad')===0,'INI unit boundaries');
gi(row(gszSystemCheck($c,null,$ctx),'database')['status']==='block','no database detected');
gi(row(gszSystemCheck($c,$p,$ctx),'schema')['status']==='block','empty schema detected');
$database=(string)$p->query('SELECT DATABASE()')->fetchColumn();$p->exec('ALTER DATABASE `'.$database.'` CHARACTER SET utf8mb3');$blocked=false;try{gszInstallFresh($p);}catch(RuntimeException $e){$blocked=$e->getMessage()==='GSZ_INSTALL_DATABASE_CHARSET';}gi($blocked&&!$p->query('SHOW TABLES')->fetchAll(),'fresh rejects wrong charset before DDL');$p->exec('ALTER DATABASE `'.$database.'` CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci');
$second=new PDO($dsn,'root','',[PDO::ATTR_ERRMODE=>PDO::ERRMODE_EXCEPTION]);$installLock='gsz_install_'.substr(hash('sha256',$database),0,32);$lock=$second->prepare('SELECT GET_LOCK(?,0)');$lock->execute([$installLock]);$blocked=false;try{gszInstallFresh($p);}catch(RuntimeException $e){$blocked=$e->getMessage()==='GSZ_INSTALL_BUSY';}gi($blocked,'concurrent fresh installer blocked before DDL');$lock=$second->prepare('SELECT RELEASE_LOCK(?)');$lock->execute([$installLock]);
$r=gszInstallFresh($p);gi($r['tables']===83&&$r['schema']==='440'&&!$r['admin_created']&&!$r['objects_seeded'],'fresh installer complete, no invented business rows');
$report=gszSystemCheck($c,$p,$ctx);foreach(['schema','migrations','identity_keys','engines','database','db_charset'] as $id)gi(row($report,$id)['status']==='ok','real '.$id.' check');
gi(row($report,'admin')['status']==='block','missing first owner detected');
$p->prepare('INSERT INTO admins(email,password_hash) VALUES(?,?)')->execute(['owner@example.invalid',password_hash('Local-Test-Only-2026!',PASSWORD_DEFAULT)]);$p->exec("INSERT INTO customers(name) VALUES('Synthetic preserved customer')");$before=$p->query('SELECT * FROM admins')->fetchAll();
$blocked=false;try{gszInstallFresh($p);}catch(RuntimeException $e){$blocked=$e->getMessage()==='GSZ_INSTALL_DATABASE_NOT_EMPTY';}gi($blocked,'fresh refuses nonempty database');gi($p->query('SELECT * FROM admins')->fetchAll()===$before,'owner unchanged after rejected reinstall');gi((int)$p->query('SELECT COUNT(*) FROM customers')->fetchColumn()===1,'existing customer unchanged');
gszMigrateGesamt($p);gszMigrateGesamt($p);gi($p->query('SELECT * FROM admins')->fetchAll()===$before,'repeated upgrade preserves credentials');
$report=gszSystemCheck($c,$p,$ctx);gi($report['blocks']===0&&!$report['production_approved'],'local prerequisites pass without production claim');
gi(row(gszSystemCheck($c,$p,['web_root'=>dirname(__DIR__)]),'document_root')['status']==='block','unsafe root detected');
gi(row(gszSystemCheck($c,$p,['web_root'=>dirname(__DIR__)]),'private_outside')['status']==='block','exposed private detected');
$c['db_pass']='';gi(row(gszSystemCheck($c,$p,$ctx),'config_db_pass')['status']==='block','missing secret detected without output');$c['db_pass']='PRIVATE_SYNTHETIC_MARKER';gi(!str_contains(json_encode(gszSystemCheck($c,$p,$ctx)),'PRIVATE_SYNTHETIC_MARKER'),'diagnostics never disclose config values');
$p->exec('CREATE INDEX idx_test_recurring_fk ON tasks(recurring_job_id)');$p->exec('ALTER TABLE tasks DROP INDEX uq_recurring_occurrence');gi(row(gszSystemCheck($c,$p,$ctx),'identity_keys')['status']==='block','missing unique recurrence identity detected');$p->exec('ALTER TABLE tasks ADD UNIQUE KEY uq_recurring_occurrence(recurring_job_id,scheduled_for)');$p->exec('ALTER TABLE tasks DROP INDEX idx_test_recurring_fk');
gi(gszStorageProbe(),'private actual write-read-delete probe');gi(!glob(dirname(__DIR__).'/private/uploads/.system-check-*'),'no probe file retained');
gi(row(gszSystemCheck($c,$p,$ctx),'identity_keys')['status']==='ok','restored index verified');
echo "Installation/system database tests passed ($n assertions).\n";
