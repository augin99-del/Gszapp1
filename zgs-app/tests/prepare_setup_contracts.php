<?php
declare(strict_types=1);
if(PHP_SAPI!=='cli')exit(1);require dirname(__DIR__).'/private/setup_schema.php';
$dsn=getenv('GSZ_TEST_DSN')?:'';if(!preg_match('/^mysql:unix_socket=[^;]+;dbname=gsz_test_setupcontracts_[a-z0-9_]+$/D',$dsn))throw new RuntimeException('Empty local contract fixture required.');$p=new PDO($dsn,'root','',[PDO::ATTR_ERRMODE=>PDO::ERRMODE_EXCEPTION,PDO::ATTR_DEFAULT_FETCH_MODE=>PDO::FETCH_ASSOC]);if($p->query('SHOW FULL TABLES')->fetchAll())throw new RuntimeException('Empty fixture required.');
$p->exec(file_get_contents(dirname(__DIR__).'/private/fresh_install_v192.sql'));$out=['192'=>['tables'=>gszSetupSchemaMetadata($p)]];$files=[];foreach([200,210,220,230,240,250,260,270,280,290,300,310,320,330,340,350,360] as $v)$files[$v]='migration_v'.$v.'.sql';
foreach(['migration_v370.sql','migration_v370_bank.sql'] as $candidate)if(is_file(dirname(__DIR__).'/private/'.$candidate)){$files[370]=$candidate;break;}
foreach($files as $v=>$f){$p->exec(file_get_contents(dirname(__DIR__).'/private/'.$f));$q=$p->prepare('SELECT version FROM app_migrations WHERE version LIKE ?');$q->execute([$v.'_%']);$marker=$q->fetchColumn();if(!$marker)throw new RuntimeException('Marker not found for fixture stage.');$out[(string)$v]=['marker'=>$marker,'tables'=>gszSetupSchemaMetadata($p)];}
file_put_contents(dirname(__DIR__).'/private/setup_schema_contracts.json',json_encode($out,JSON_UNESCAPED_SLASHES|JSON_THROW_ON_ERROR)."\n");foreach($out as $v=>$c)echo $v.':'.count($c['tables'])." tables\n";
