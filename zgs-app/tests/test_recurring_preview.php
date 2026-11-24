<?php
declare(strict_types=1);
// Vorgesehene Termine regelmäßiger Arbeiten (Wunsch 24.11.2026: Treppenhausreinigung schon vorher im Plan sehen). Synthetische Daten.
set_error_handler(static function($s,$m,$f,$l){if(error_reporting()&$s)throw new ErrorException($m,0,$s,$f,$l);return false;});
require dirname(__DIR__).'/private/install_core.php';require_once dirname(__DIR__).'/private/recurring_quick.php';require_once dirname(__DIR__).'/private/recurring_preview.php';
$dsn=getenv('GSZ_TEST_DSN')?:'';if(!preg_match('/^mysql:unix_socket=[^;]+;dbname=gsz_test_preview_[a-z0-9_]+$/D',$dsn))throw new RuntimeException('Empty local test DB required.');
$p=new PDO($dsn,'root','',[PDO::ATTR_ERRMODE=>PDO::ERRMODE_EXCEPTION,PDO::ATTR_DEFAULT_FETCH_MODE=>PDO::FETCH_ASSOC,PDO::ATTR_EMULATE_PREPARES=>false]);if($p->query('SHOW TABLES')->fetchAll())throw new RuntimeException('Empty DB required.');
$n=0;function rp(bool $v,string $s):void{global $n;$n++;if(!$v)throw new RuntimeException($s);}
gszInstallFresh($p);$p->exec("INSERT INTO properties(name,address) VALUES('Synthetikweg 5','Synthetikweg 5, 00000 Teststadt')");$pid=(int)$p->lastInsertId();
$t=new DateTimeImmutable('today',new DateTimeZone('Europe/Berlin'));$d=fn(int $k)=>$t->modify($k.' days')->format('Y-m-d');$today=$d(0);
gszQuickRule($p,$pid,['rule_title'=>'Treppenhausreinigung','rule_frequency'=>'weekly','rule_first'=>$d(2)]);
rp(!(int)$p->query('SELECT COUNT(*) FROM tasks')->fetchColumn(),'no task before the due day (unchanged behaviour)');
$u=zgsUpcomingOccurrences($p,$today,$d(14),$today);rp(array_keys($u)===[$d(2),$d(9)],'weekly cleaning visible on its coming dates');
rp($u[$d(2)][0]['title']==='Treppenhausreinigung'&&$u[$d(2)][0]['property_name']==='Synthetikweg 5','title and object');
rp(zgsUpcomingOccurrences($p,$d(-7),$today,$today)===[],'only future days');
// Fälligkeitstag erreicht: echte Aufgabe entsteht, Vorschau zeigt sie nicht doppelt
zgsGenerateDueTasks($p,$d(2));rp((int)$p->query("SELECT COUNT(*) FROM tasks WHERE due_date='".$d(2)."'")->fetchColumn()===1,'task generated on the day');
rp(!isset(zgsUpcomingOccurrences($p,$d(1),$d(14),$today)[$d(2)]),'no duplicate next to the real task');
rp(isset(zgsUpcomingOccurrences($p,$d(1),$d(14),$today)[$d(9)]),'next week still shown');
// Pausiert → sofort weg (keine verwaisten Aufgaben)
$p->exec('UPDATE recurring_jobs SET active=0');rp(zgsUpcomingOccurrences($p,$today,$d(30),$today)===[],'paused rule shows nothing');$p->exec('UPDATE recurring_jobs SET active=1');
// Monatlich und alle 2 Wochen
gszQuickRule($p,$pid,['rule_title'=>'Außenanlage pflegen','rule_frequency'=>'biweekly','rule_first'=>$d(3)]);$u=zgsUpcomingOccurrences($p,$today,$d(20),$today);
rp(in_array('Außenanlage pflegen',array_column($u[$d(3)]??[],'title'),true)&&in_array('Außenanlage pflegen',array_column($u[$d(17)]??[],'title'),true)&&!in_array('Außenanlage pflegen',array_column($u[$d(10)]??[],'title'),true),'every two weeks');
echo "Recurring preview PASS ($n checks).\n";
