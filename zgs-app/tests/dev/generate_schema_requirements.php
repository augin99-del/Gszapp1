<?php
declare(strict_types=1);
// Nur lokale QA: erzeugt private/schema_requirements.php aus einer frisch installierten gsz_test_*-Datenbank.
if(PHP_SAPI!=='cli')exit(1);$dsn=getenv('GSZ_TEST_DSN')?:'';if(!preg_match('/^mysql:unix_socket=[^;]+;dbname=gsz_test_[a-z0-9_]+$/D',$dsn))throw new RuntimeException('Local test DB required.');
$p=new PDO($dsn,'root','',[PDO::ATTR_ERRMODE=>PDO::ERRMODE_EXCEPTION]);$old=require dirname(__DIR__,2).'/private/schema_requirements.php';
$tables=[];foreach($p->query("SELECT TABLE_NAME,COLUMN_NAME FROM information_schema.COLUMNS WHERE TABLE_SCHEMA=DATABASE() ORDER BY TABLE_NAME,ORDINAL_POSITION")->fetchAll(PDO::FETCH_NUM) as [$t,$c])$tables[$t][]=$c;
$unique=array_intersect_key($old['unique_keys'],$tables);$unique['estate_bank_accounts'][]='building_id';$unique['estate_bank_accounts']=array_values(array_unique($unique['estate_bank_accounts']));
$migrations=$p->query('SELECT version FROM app_migrations ORDER BY version')->fetchAll(PDO::FETCH_COLUMN);
file_put_contents(dirname(__DIR__,2).'/private/schema_requirements.php',"<?php\ndeclare(strict_types=1);\n// Column/identity contract from the locally migration-tested v380 schema. No records.\nreturn ".var_export(['tables'=>$tables,'unique_keys'=>$unique,'migrations'=>$migrations],true).";\n");echo count($tables)," tables\n";
