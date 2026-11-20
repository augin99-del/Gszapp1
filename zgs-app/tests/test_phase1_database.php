<?php
declare(strict_types=1);
/** Destructive fixtures ONLY in a dedicated, empty test database. No app config loaded. */
require dirname(__DIR__).'/private/phase1_import.php';
require dirname(__DIR__).'/private/recurring_rules.php';
$dsn=getenv('GSZ_TEST_DSN')?:'';
if(!preg_match('/^mysql:unix_socket=[^;]+;dbname=gsz_test_[a-z0-9_]+$/D',$dsn))throw new RuntimeException('Dedicated local gsz_test_* database DSN required.');
$pdo=new PDO($dsn,getenv('GSZ_TEST_USER')?:'root',getenv('GSZ_TEST_PASSWORD')?:'',[PDO::ATTR_ERRMODE=>PDO::ERRMODE_EXCEPTION,PDO::ATTR_DEFAULT_FETCH_MODE=>PDO::FETCH_ASSOC,PDO::ATTR_EMULATE_PREPARES=>false]);
if($pdo->query('SHOW TABLES')->fetchAll())throw new RuntimeException('Test database must be empty.');
$root=dirname(__DIR__);
$pdo->exec(file_get_contents($root.'/private/fresh_install_v192.sql'));
// Fixture predating v200 proves migration preserves existing business data.
$pdo->exec("INSERT INTO properties(name,address,notes) VALUES('Bestandsname','Fröbelstr. 19, 33330 Gütersloh','Unverändert behalten')");
$propertyId=(int)$pdo->lastInsertId();
$pdo->exec("INSERT INTO recurring_jobs(property_id,title,frequency,notes,next_due_date) VALUES($propertyId,'Treppenhaus reinigen','monthly','Bestandsregel','2026-01-31')");
$existingJob=(int)$pdo->lastInsertId();
$pdo->exec(file_get_contents($root.'/private/migration_v200.sql'));
$n=0;
function dbok(bool $b,string $label):void {global $n;$n++;if(!$b)throw new RuntimeException($label);}
function dbreject(callable $fn,string $label):void {try{$fn();}catch(RuntimeException|InvalidArgumentException $e){dbok(true,$label);return;}dbok(false,$label);}
dbok((int)$pdo->query("SELECT anchor_day FROM recurring_jobs WHERE id=$existingJob")->fetchColumn()===31,'migration anchor');
$p=gszImportPreview($pdo,'2026-11-09');
// Address abbreviation and postal code must resolve the existing object.
dbok(!$p['errors'],'preview has no errors: '.implode(',',$p['errors']));
dbok((int)$p['rows'][0]['match']['id']===$propertyId,'existing property recognized');
$choices=[];foreach($p['rows'] as $row)foreach($row['rules'] as $r)if($r['needs_review'])$choices[$r['spec']['key']]='new';
$before=(int)$pdo->query('SELECT COUNT(*) FROM properties')->fetchColumn();
dbreject(fn()=>gszApplyImport($pdo,'2026-11-09',$p['fingerprint']), 'explicit mapping required');
dbok((int)$pdo->query('SELECT COUNT(*) FROM properties')->fetchColumn()===$before,'mapping failure rolls back');
$r=gszApplyImport($pdo,'2026-11-09',$p['fingerprint'],$choices);
dbok($r['properties_created']===6&&$r['properties_reused']===1,'six new one reused');
dbok($pdo->query("SELECT notes FROM properties WHERE id=$propertyId")->fetchColumn()==='Unverändert behalten','preserve existing property');
dbok($pdo->query("SELECT frequency FROM recurring_jobs WHERE id=$existingJob")->fetchColumn()==='monthly','preserve existing rule');
$p=gszImportPreview($pdo,'2026-11-09');$r=gszApplyImport($pdo,'2026-11-09',$p['fingerprint']);
dbok($r['properties_created']===0&&$r['rules_created']===0,'repeat import idempotent');
dbok((int)$pdo->query('SELECT COUNT(*) FROM properties')->fetchColumn()===7,'seven properties');
$countJobs=(int)$pdo->query('SELECT COUNT(*) FROM recurring_jobs')->fetchColumn();
dbok($countJobs===count(array_merge(...array_map(fn($p)=>$p['rules'],gszPhase1Catalog('2026-11-09')))),'rule count');
$pdo->exec("UPDATE properties SET notes='Änderung nach Vorschau' WHERE id=$propertyId");
dbreject(fn()=>gszApplyImport($pdo,'2026-11-09',$p['fingerprint']),'stale preview rejected');
$pdo->exec("INSERT INTO properties(name,address) VALUES('Fröbelstraße 19','Fröbelstraße 19, Gütersloh')");$duplicate=(int)$pdo->lastInsertId();
$p=gszImportPreview($pdo,'2026-11-09');dbok((bool)$p['errors'],'duplicate property detected');
dbreject(fn()=>gszApplyImport($pdo,'2026-11-09',$p['fingerprint']),'ambiguous import rejected');
$pdo->exec("DELETE FROM properties WHERE id=$duplicate");
// Use an explicit future date, independent of CURRENT_DATE on the DB server.
$pdo->exec('UPDATE recurring_jobs SET active=0');
$pdo->exec("INSERT INTO recurring_jobs(title,frequency,next_due_date,duration_minutes) VALUES('Month end','monthly','2030-01-31',90)");$month=(int)$pdo->lastInsertId();
foreach(['2030-01-31','2030-02-28','2030-03-31'] as $d){$r=zgsGenerateDueTasks($pdo,$d);dbok($r['created']===1,'future supplied date '.$d);dbok(zgsGenerateDueTasks($pdo,$d)['created']===0,'scheduler replay '.$d);}
dbok($pdo->query("SELECT next_due_date FROM recurring_jobs WHERE id=$month")->fetchColumn()==='2030-04-30','anchor persists across invocations');
dbok((int)$pdo->query("SELECT duration_minutes FROM tasks WHERE recurring_job_id=$month LIMIT 1")->fetchColumn()===90,'duration propagated');
$pdo->exec("UPDATE tasks SET due_date='2030-05-01',done=1 WHERE recurring_job_id=$month AND scheduled_for='2030-03-31'");
$pdo->exec("UPDATE recurring_jobs SET next_due_date='2030-03-31' WHERE id=$month");
dbok(zgsGenerateDueTasks($pdo,'2030-03-31')['created']===0,'moved completed occurrence not recreated');
$pdo->exec('UPDATE recurring_jobs SET active=0');
$pdo->exec("INSERT INTO recurring_jobs(title,frequency,next_due_date,anchor_day,season_start_month,season_end_month) VALUES('Winter','monthly','2030-01-15',15,10,4)");$winter=(int)$pdo->lastInsertId();
dbok(zgsGenerateDueTasks($pdo,'2030-12-31')['created']===7,'winter season cross-year');
$pdo->exec('UPDATE recurring_jobs SET active=0');
$pdo->exec("INSERT INTO recurring_jobs(title,frequency,next_due_date,rule_source) VALUES('Unverified','weekly','2030-01-01','routine_unverified')");$unverified=(int)$pdo->lastInsertId();
dbok(zgsGenerateDueTasks($pdo,'2030-12-31')['created']===0,'unverified excluded even if active');
dbreject(fn()=>zgsGenerateManualTask($pdo,$unverified,'2030-01-01'),'unverified manual blocked');
$pdo->exec('UPDATE recurring_jobs SET active=0');
$pdo->exec("INSERT INTO recurring_jobs(title,frequency,next_due_date,duration_minutes) VALUES('Manual and auto','weekly','2030-01-02',15)");$manual=(int)$pdo->lastInsertId();
dbok(zgsGenerateManualTask($pdo,$manual,'2030-01-02')===1,'manual occurrence');
dbok(zgsGenerateManualTask($pdo,$manual,'2030-01-02')===0,'manual replay');
dbok(zgsGenerateDueTasks($pdo,'2030-01-02')['created']===0,'auto after manual no duplicate');
dbreject(fn()=>zgsGenerateManualTask($pdo,$manual,'2030-01-03'),'off-schedule manual blocked');
$pdo->exec('UPDATE recurring_jobs SET active=0');
$pdo->exec("INSERT INTO recurring_jobs(title,frequency) VALUES('As needed','seasonal')");$adhoc=(int)$pdo->lastInsertId();
dbok(zgsGenerateManualTask($pdo,$adhoc,'2030-01-03')===1,'adhoc works');
dbok(zgsGenerateManualTask($pdo,$adhoc,'2030-01-03')===0,'adhoc replay');
$pdo->exec('UPDATE recurring_jobs SET active=0');
$pdo->exec("INSERT INTO recurring_jobs(title,frequency,next_due_date) VALUES('Backlog','weekly','2030-01-01')");
$r=zgsGenerateDueTasks($pdo,'2030-12-31',2);dbok($r['created']===2&&$r['backlog_jobs']===1,'bounded backlog');
// Editing must not silently replace prior task duration or existing monthly anchor.
$old=$pdo->query("SELECT * FROM recurring_jobs WHERE id=$month")->fetch();
gszSaveRule($pdo,array_replace($old,['property_id'=>'','duration_minutes'=>'120']),$month);
dbok((int)$pdo->query("SELECT anchor_day FROM recurring_jobs WHERE id=$month")->fetchColumn()===31,'edit retains anchor');
dbok((int)$pdo->query("SELECT duration_minutes FROM tasks WHERE recurring_job_id=$month LIMIT 1")->fetchColumn()===90,'past estimates retained');
$old=$pdo->query("SELECT * FROM recurring_jobs WHERE id=$unverified")->fetch();
dbreject(fn()=>gszSaveRule($pdo,array_replace($old,['property_id'=>'','rule_source'=>'manual']),$unverified),'routine confirmation required');
// Actual yellow rule weekdays and Sunday routine are tested from the catalog above.
// Exercise the seeded yellow pair through the real scheduler.
$pdo->exec('UPDATE recurring_jobs SET active=0');
$pdo->exec("UPDATE recurring_jobs SET active=1 WHERE rule_source='special_weekly'");
dbok(zgsGenerateDueTasks($pdo,'2026-11-12')['created']===2,'seeded yellow rules create two tasks');
dbok(zgsGenerateDueTasks($pdo,'2026-11-12')['created']===0,'yellow replay');
dbok((int)$pdo->query("SELECT COUNT(*) FROM app_migrations WHERE version='200_phase1'")->fetchColumn()===1,'migration recorded');
echo "MariaDB phase 1 integration passed ($n assertions; $countJobs seeded rules).\n";
