<?php
declare(strict_types=1);
require dirname(__DIR__).'/private/migrate_phase2.php';require dirname(__DIR__).'/private/planning_store.php';require dirname(__DIR__).'/private/phase1_import.php';
$dsn=getenv('GSZ_TEST_DSN')?:'';if(!preg_match('/^mysql:unix_socket=[^;]+;dbname=gsz_test_[a-z0-9_]+$/D',$dsn))throw new RuntimeException('Local empty test DSN required.');
$pdo=new PDO($dsn,'root','',[PDO::ATTR_ERRMODE=>PDO::ERRMODE_EXCEPTION,PDO::ATTR_DEFAULT_FETCH_MODE=>PDO::FETCH_ASSOC,PDO::ATTR_EMULATE_PREPARES=>false]);if($pdo->query('SHOW TABLES')->fetchAll())throw new RuntimeException('Empty DB required.');
$root=dirname(__DIR__);$pdo->exec(file_get_contents($root.'/private/fresh_install_v192.sql'));$pdo->exec(file_get_contents($root.'/private/migration_v200.sql'));gszMigratePhase2($pdo);gszMigratePhase2($pdo);
$n=0;function c(bool $b,string $l):void{global $n;$n++;if(!$b)throw new RuntimeException($l);}
$p=gszImportPreview($pdo,'2026-11-09');gszApplyImport($pdo,'2026-11-09',$p['fingerprint']);$before=planLoad($pdo);
$t=planSaveProposal($pdo,1,'2026-11-09',null);c(planLoad($pdo)===$before,'preview read-only business data');
planApply($pdo,1,$t);$count=(int)$pdo->query('SELECT COUNT(*) FROM tasks')->fetchColumn();c($count>0,'tasks materialized');planApply($pdo,1,$t);c((int)$pdo->query('SELECT COUNT(*) FROM tasks')->fetchColumn()===$count,'apply idempotent');
$t2=planSaveProposal($pdo,1,'2026-11-09',null);planApply($pdo,1,$t2);c((int)$pdo->query('SELECT COUNT(*) FROM tasks')->fetchColumn()===$count,'second proposal no duplicates');
zgsGenerateDueTasks($pdo,'2026-11-15');c((int)$pdo->query('SELECT COUNT(*) FROM tasks')->fetchColumn()===$count,'scheduler after proposal');
c((int)$pdo->query("SELECT COUNT(*) FROM tasks t JOIN recurring_jobs r ON r.id=t.recurring_job_id WHERE r.rule_source='routine_unverified'")->fetchColumn()===0,'no unverified Sunday');
c((int)$pdo->query("SELECT COUNT(*) FROM tasks t JOIN recurring_jobs r ON r.id=t.recurring_job_id WHERE r.rule_source='special_weekly' AND t.due_date=t.scheduled_for")->fetchColumn()===2,'yellow fixed');
$id=(int)$pdo->query('SELECT MIN(id) FROM tasks')->fetchColumn();$pdo->exec("UPDATE tasks SET done=1,notes='keep',plan_locked=1,sort_order=3,due_date='2026-11-15' WHERE id=$id");$old=$pdo->query("SELECT * FROM tasks WHERE id=$id")->fetch();$t=planSaveProposal($pdo,1,'2026-11-09',null);planApply($pdo,1,$t);c($pdo->query("SELECT * FROM tasks WHERE id=$id")->fetch()===$old,'done manually moved task retained');
$t=planSaveProposal($pdo,1,'2026-11-09',null);$pdo->exec("UPDATE tasks SET notes='changed' WHERE id=$id");$rejected=false;try{planApply($pdo,1,$t);}catch(RuntimeException $e){$rejected=true;}c($rejected,'stale snapshot rejected');
$pdo->exec("INSERT INTO planning_appointments(day,start_time,end_time,title) VALUES('2026-11-09','13:00','14:30','Appointment')");$t=planSaveProposal($pdo,1,'2026-11-09',null);$q=$pdo->query("SELECT payload FROM planning_proposals WHERE token='$t'")->fetchColumn();$v=json_decode($q,true);foreach($v['plan']['days']['2026-11-09']['rows'] as $r)if(!$r['task']['done']&&$r['end']!==null)c($r['end']<=780||$r['start']>=870,'appointment not overlaid');
c((int)$pdo->query("SELECT COUNT(*) FROM app_migrations WHERE version='210_phase2'")->fetchColumn()===1,'migration replay');
echo "Planning database tests passed ($n assertions; $count tasks).\n";
