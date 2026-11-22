<?php
declare(strict_types=1);
// Ersatz für den Cronjob (STRATO Hosting Basic hat keine Cronjob-Verwaltung): Aufgaben laufen beim Öffnen nach, gedrosselt.
// Synthetische Daten, kein Postfach: der Mailabruf wird nur bis zur Entscheidung „jetzt abrufen oder nicht“ geprüft.
set_error_handler(static function($s,$m,$f,$l){if(error_reporting()&$s)throw new ErrorException($m,0,$s,$f,$l);return false;});
require dirname(__DIR__).'/private/install_core.php';require dirname(__DIR__).'/private/web_update.php';require_once dirname(__DIR__).'/private/automation.php';
$dsn=getenv('GSZ_TEST_DSN')?:'';if(!preg_match('/^mysql:unix_socket=[^;]+;dbname=gsz_test_open_[a-z0-9_]+$/D',$dsn))throw new RuntimeException('Empty local test DB required.');
$p=new PDO($dsn,'root','',[PDO::ATTR_ERRMODE=>PDO::ERRMODE_EXCEPTION,PDO::ATTR_DEFAULT_FETCH_MODE=>PDO::FETCH_ASSOC,PDO::ATTR_EMULATE_PREPARES=>false]);if($p->query('SHOW TABLES')->fetchAll())throw new RuntimeException('Empty DB required.');
$n=0;function ao(bool $v,string $s):void{global $n;$n++;if(!$v)throw new RuntimeException($s);}
$p->exec(file_get_contents(dirname(__DIR__).'/private/fresh_install_v192.sql'));$p->exec(file_get_contents(dirname(__DIR__).'/private/migration_v200.sql'));gszMigratePhase2($p);gszMigratePhase3($p);
foreach([230,240,250,260,270,280,290,300,310,320,330,340,350,360,370] as $v)$p->exec(file_get_contents(dirname(__DIR__).'/private/migration_v'.$v.'.sql'));gszSetupMigrate($p);
$p->prepare('INSERT INTO admins(email,password_hash) VALUES(?,?)')->execute(['owner@example.invalid',password_hash('x',PASSWORD_DEFAULT)]);
$cfg=['mail_enabled'=>false];
ao(gszAutomationDue($p,'tasks',60),'never run: due');
$r=gszAutomationOnOpen($p,$cfg,'heute');ao(($r['jobs']['tasks']['status']??'')==='PASS','opening Heute runs the regular jobs');
ao(gszAutomationOnOpen($p,$cfg,'heute')===null,'second opening within the hour does nothing (no delay)');
$p->exec("UPDATE automation_state SET last_started=UTC_TIMESTAMP()-INTERVAL 61 MINUTE WHERE job_key='tasks'");ao(is_array(gszAutomationOnOpen($p,$cfg,'heute')),'after an hour it runs again');
ao(gszAutomationOnOpen($p,['automation_on_open'=>'false'],'heute')===null,'can be switched off by configuration');
ao(gszAutomationOnOpen($p,$cfg,'mail')===null,'mail not set up: no fetch attempt');
ao(gszAutomationOnOpen($p,['mail_enabled'=>true,'mail_pass'=>''],'mail')===null,'no stored password: no fetch attempt');
$p->exec("INSERT INTO automation_state(job_key,last_started,status) VALUES('mail',UTC_TIMESTAMP()-INTERVAL 2 MINUTE,'BLOCKED')");ao(!gszAutomationDue($p,'mail',5),'failed fetch 2 minutes ago: not retried yet');
$p->exec("UPDATE automation_state SET last_started=UTC_TIMESTAMP()-INTERVAL 6 MINUTE WHERE job_key='mail'");ao(gszAutomationDue($p,'mail',5),'after 5 minutes due again');
$x=false;try{gszAutomationOnOpen($p,$cfg,'other');}catch(InvalidArgumentException $e){$x=true;}ao($x,'unknown trigger refused');
foreach(['index.php'=>"'heute'",'mail.php'=>"'mail'"] as $f=>$k)ao(str_contains(file_get_contents(dirname(__DIR__).'/public/'.$f),"gszAutomationOnOpen(\$pdo,\$config,$k)"),$f.' triggers on open');
echo "Automation on open PASS ($n assertions; synthetic, no mailbox contacted).\n";
