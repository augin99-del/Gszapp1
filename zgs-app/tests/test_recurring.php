<?php
declare(strict_types=1);
require dirname(__DIR__).'/private/phase1_catalog.php';
require dirname(__DIR__).'/private/recurring_rules.php';
require dirname(__DIR__).'/private/config_loader.php';
require dirname(__DIR__).'/private/smtp.php';
$n=0;
function ok(bool $b,string $label):void {global $n;$n++;if(!$b)throw new RuntimeException($label);}
function rejects(callable $fn,string $label):void {try{$fn();}catch(InvalidArgumentException $e){ok(true,$label);return;}ok(false,$label);}
foreach(['2024-02-29'=>true,'2026-02-29'=>false,'2026-02-30'=>false,'2026-13-01'=>false,'2026-11-09'=>true,'2026-1-01'=>false] as $d=>$v)ok(zgsValidDate($d)===$v,'date '.$d);
ok(zgsAdvanceDate('2026-01-31','monthly',31)==='2026-02-28','month end');
ok(zgsAdvanceDate('2026-02-28','monthly',31)==='2026-03-31','stable monthly anchor');
ok(zgsAdvanceDate('2024-01-31','monthly',31)==='2024-02-29','leap year');
ok(zgsAdvanceDate('2026-11-30','quarterly',30)==='2027-02-28','quarter');
ok(zgsAdvanceDate('2026-12-27','weekly',27)==='2027-01-03','Sunday and year boundary');
ok(zgsAdvanceDate('2026-12-27','biweekly',27)==='2027-01-10','biweekly');
rejects(fn()=>zgsAdvanceDate('2026-02-30','weekly',30),'invalid date');
rejects(fn()=>zgsAdvanceDate('2026-01-01','seasonal',1),'manual frequency');
foreach(range(1,12) as $m){$d=sprintf('2026-%02d-15',$m);ok(zgsInSeason($d,5,9)===($m>=5&&$m<=9),'summer');ok(zgsInSeason($d,10,4)===($m>=10||$m<=4),'winter');}
rejects(fn()=>zgsInSeason('2026-01-01',0,12),'bad season');
$c=gszPhase1Catalog('2026-11-09');ok(count($c)===7,'seven objects');
ok($c[3]['city']==='Rheda-Wiedenbrück','confirmed city');
$keys=[];$rules=[];
foreach($c as $p)foreach($p['rules'] as $r){$keys[]=$r['key'];$rules[]=$r;}
ok(count($keys)===count(array_unique($keys)),'unique rule identities');
foreach($rules as $r){if($r['rule_source']==='routine_unverified')ok($r['active']===0&&$r['duration_minutes']===15,'routines paused');if($r['frequency']==='quarterly')ok($r['duration_minutes']===90,'current editable planning default; existing rules preserved');}
$special=array_values(array_filter($rules,fn($r)=>$r['rule_source']==='special_weekly'));
ok(count($special)===2,'two yellow rules');ok($special[0]['next_due_date']==='2026-11-11'&&$special[1]['next_due_date']==='2026-11-12','Wednesday Thursday');
ok($c[5]['rules'][0]['next_due_date']==='2026-11-15'&&$c[5]['rules'][1]['next_due_date']==='2026-11-09','Sunday Monday');
ok(gszNormalize('Fröbelstr. 19')===gszNormalize('Fröbelstraße 19'),'street abbreviation');
ok(gszNormalize('Rheda Wiedenbrück')===gszNormalize('Rheda-Wiedenbrück'),'city spelling');
$base=['title'=>'Test','frequency'=>'weekly','duration_minutes'=>'75','next_due_date'=>'2026-11-09'];
ok(gszValidateRule($base)['duration_minutes']===75,'duration');
foreach([['duration_minutes'=>'-1'],['duration_minutes'=>'0'],['duration_minutes'=>'1441'],['next_due_date'=>'2026-02-30'],['frequency'=>'not_a_frequency'],['season_start_month'=>'5'],['rule_source'=>'calendar_fake']] as $bad)rejects(fn()=>gszValidateRule(array_replace($base,$bad)),'rule validation');
$blocked=false;try{zgsSmtpSend(['allow_mail_send'=>false],'nobody@example.invalid','Test','Test');}catch(RuntimeException $e){$blocked=str_contains($e->getMessage(),'gesperrt');}ok($blocked,'mail blocked before network');
putenv('GSZ_DB_PASS=unit-test-placeholder');ok(gszLoadConfig()['db_pass']==='unit-test-placeholder','environment override');putenv('GSZ_DB_PASS');
echo "Recurring/catalog/config tests passed ($n assertions).\n";
