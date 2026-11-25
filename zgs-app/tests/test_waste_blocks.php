<?php
declare(strict_types=1);
// Teil C: genau ein Abfall-Block je Objekt/Tag/Richtung. Nur synthetische Daten in einer leeren gsz_test_*-Datenbank.
set_error_handler(static function($s,$m,$f,$l){if(error_reporting()&$s)throw new ErrorException($m,0,$s,$f,$l);return false;});
require dirname(__DIR__).'/private/install_core.php';require dirname(__DIR__).'/private/waste_connections.php';require_once dirname(__DIR__).'/private/waste_blocks.php';
$dsn=getenv('GSZ_TEST_DSN')?:'';if(!preg_match('/^mysql:unix_socket=[^;]+;dbname=gsz_test_[a-z0-9_]+$/D',$dsn))throw new RuntimeException('Empty local test DB required.');
$p=new PDO($dsn,'root','',[PDO::ATTR_ERRMODE=>PDO::ERRMODE_EXCEPTION,PDO::ATTR_DEFAULT_FETCH_MODE=>PDO::FETCH_ASSOC,PDO::ATTR_EMULATE_PREPARES=>false]);if($p->query('SHOW TABLES')->fetchAll())throw new RuntimeException('Fresh schema only.');gszInstallFresh($p);
$n=0;function wb(bool $v,string $s):void{global $n;$n++;if(!$v)throw new RuntimeException($s);}
$p->exec("INSERT INTO admins(email,password_hash) VALUES('owner@example.invalid','synthetic');INSERT INTO properties(name,address,seed_key) VALUES('Heiligenhäuschenweg 2–4','Synthetic street 2',NULL),('Zweites Objekt','Synthetic street 9',NULL),('Fontänenstraße 29–31','Synthetic street 29','fontaenen29')");
$today=(new DateTimeImmutable('today',new DateTimeZone('Europe/Berlin')));$d=fn(int $days)=>$today->modify('+'.$days.' days')->format('Y-m-d');
$p->exec("INSERT INTO waste_sources(property_id,name,address,source_date,valid_until,checked_at) VALUES(1,'S1','Synthetic street 2',CURDATE(),DATE_ADD(CURDATE(),INTERVAL 1 YEAR),NOW()),(2,'S2','Synthetic street 9',CURDATE(),DATE_ADD(CURDATE(),INTERVAL 1 YEAR),NOW()),(3,'S3','Synthetic street 29',CURDATE(),DATE_ADD(CURDATE(),INTERVAL 1 YEAR),NOW())");
$event=function(int $property,string $date,string $fraction,string $uid)use($p):array{$p->prepare("INSERT INTO waste_events(property_id,collection_date,fraction,address,status,event_hash) VALUES(?,?,?,'x','geprueft',?)")->execute([$property,$date,$fraction,hash('sha256',$uid)]);$id=(int)$p->lastInsertId();$p->prepare("INSERT INTO waste_origins(event_id,source_id,external_id,original_title,original_start,record_hash) VALUES(?,?,?,?,?,?)")->execute([$id,$property,$uid,$fraction,$date,hash('sha256',$uid.'r')]);$q=$p->prepare('SELECT * FROM waste_events WHERE id=?');$q->execute([$id]);return $q->fetch();};
$create=function(array $e)use($p):void{$p->beginTransaction();wasteCreateTasks($p,$e);$p->commit();};
$blocks=fn(int $property)=>$p->query('SELECT id,title,due_date,done,waste_key FROM tasks WHERE property_id='.$property.' ORDER BY due_date,id')->fetchAll();

// 1. Mehrere Tonnenarten am selben Tag → ein Block raus, ein Block zurück.
wb(wasteBlockTitle(['Bio','Restmüll'],'raus')==='Restmüll und Biotonne rausstellen','title lists all bins in fixed order');wb(wasteBlockTitle(['Gelb','Papier','Restmüll'],'rein')==='Restmüll, Papiertonne und Gelbe Tonne zurückstellen','three bins joined naturally');
$day=$d(7);$rest=$event(1,$day,'Restmüll','u-rest');$bio=$event(1,$day,'Bio','u-bio');$create($rest);$create($bio);$create($bio);
$b=$blocks(1);wb(count($b)===2,'exactly one out block and one return block for the object and day');
wb($b[0]['title']==='Restmüll und Biotonne rausstellen'&&$b[0]['due_date']===$d(6),'out block names all bins (default: evening before)');wb($b[1]['title']==='Restmüll und Biotonne zurückstellen'&&$b[1]['due_date']===$day,'return block on collection day');
wb((int)$p->query('SELECT COUNT(*) FROM waste_task_links')->fetchColumn()===4,'each bin type stays individually traceable (2 bins × 2 directions)');
$info=wasteBlockInfo($p,array_column($b,'id'));wb($info[(int)$b[0]['id']]['labels']===['Restmüll','Biotonne']&&$info[(int)$b[0]['id']]['direction']==='raus','block info lists bins for exports');
// 2. Anderes Objekt am selben Tag bleibt getrennt.
$other=$event(2,$day,'Restmüll','u-other');$create($other);wb(count($blocks(2))===2&&count($blocks(1))===2,'other object gets its own blocks');
// 3. Feiertagsverschiebung einer einzelnen Tonnenart: Block wird neu zusammengesetzt, ohne Dubletten.
$p->beginTransaction();wasteMoveEventCore($p,1,1,$bio,['uid'=>'u-bio','date'=>$d(8),'fraction'=>'Bio','original_start'=>$d(8),'label'=>'Bio'],1,$today->format('Y-m-d'));$p->commit();
$b=$blocks(1);$titles=array_map(fn($x)=>$x['due_date'].' '.$x['title'],$b);sort($titles);
wb(count($b)===4,'moved bin forms its own blocks on the new days');$want=[$d(6).' Restmüll rausstellen',$day.' Biotonne rausstellen',$day.' Restmüll zurückstellen',$d(8).' Biotonne zurückstellen'];sort($want);wb($titles===$want,'old blocks recomposed, moved bin in new blocks on its new days');
wb((int)$p->query('SELECT COUNT(*) FROM waste_events WHERE property_id=1')->fetchColumn()===2&&(int)$p->query('SELECT COUNT(*) FROM waste_task_links')->fetchColumn()===6,'no duplicate events or links');
// Verschiebung zurück auf den alten Tag → wieder ein gemeinsamer Block.
$q=$p->prepare('SELECT * FROM waste_events WHERE id=?');$q->execute([$bio['id']]);$bio=$q->fetch();$p->beginTransaction();wasteMoveEventCore($p,1,1,$bio,['uid'=>'u-bio','date'=>$day,'fraction'=>'Bio','original_start'=>$day,'label'=>'Bio'],1,$today->format('Y-m-d'));$p->commit();
$b=$blocks(1);wb(count($b)===2&&$b[0]['title']==='Restmüll und Biotonne rausstellen'&&$b[1]['title']==='Restmüll und Biotonne zurückstellen','moving back re-joins the shared block, empty blocks removed');
// 4. Erledigter Block bleibt unverändert.
$p->exec('UPDATE tasks SET done=1,completed_at=NOW() WHERE id='.(int)$b[0]['id']);$doneBefore=$p->query('SELECT * FROM tasks WHERE id='.(int)$b[0]['id'])->fetch();$doneLinks=$p->query('SELECT * FROM waste_task_links WHERE task_id='.(int)$b[0]['id'].' ORDER BY event_id')->fetchAll();
$q->execute([$bio['id']]);$bio=$q->fetch();$p->beginTransaction();wasteMoveEventCore($p,1,1,$bio,['uid'=>'u-bio','date'=>$d(9),'fraction'=>'Bio','original_start'=>$d(9),'label'=>'Bio'],1,$today->format('Y-m-d'));$p->commit();
wb($p->query('SELECT * FROM tasks WHERE id='.(int)$b[0]['id'])->fetch()===$doneBefore&&$p->query('SELECT * FROM waste_task_links WHERE task_id='.(int)$b[0]['id'].' ORDER BY event_id')->fetchAll()===$doneLinks,'done block and its bins unchanged by later date change');
wb($p->query("SELECT title FROM tasks WHERE due_date='".$d(9)."' AND property_id=1")->fetchColumn()==='Biotonne zurückstellen'&&$p->query("SELECT title FROM tasks WHERE due_date='".$day."' AND property_id=1 AND done=0")->fetchColumn()==='Restmüll zurückstellen','only open return block recomposed');
// 5. Fontänen-Sonderregel Gelbe Tonne: dasselbe Datum ergibt EINEN Block.
$p->exec("INSERT INTO recurring_jobs(property_id,title,frequency,duration_minutes,next_due_date,active,rule_source) VALUES(3,'Gelbe Tonne rausstellen','weekly',15,'2026-11-18',1,'special_weekly')");
$wed=$today->modify('next wednesday')->format('Y-m-d');$thu=planDate($wed,1);$fr=$event(3,$thu,'Restmüll','u-f-rest');$create($fr);
$fb=$p->query("SELECT t.title,t.recurring_job_id FROM tasks t WHERE t.property_id=3 AND t.due_date='$wed'")->fetchAll();
wb(count($fb)===1&&$fb[0]['title']==='Restmüll und Gelbe Tonne rausstellen'&&$fb[0]['recurring_job_id'],'special weekly yellow bin and Restmüll share one block');
require_once dirname(__DIR__).'/private/scheduler.php';zgsGenerateDueTasks($p,$wed);wb((int)$p->query("SELECT COUNT(*) FROM tasks WHERE property_id=3 AND due_date='$wed'")->fetchColumn()===1,'scheduler does not add a second yellow-bin task');
// 6. Migration: Einzelaufgaben aus 370 werden zusammengeführt, erledigte bleiben.
$legacyDay=$d(20);$l1=$event(2,$legacyDay,'Papier','u-l1');$l2=$event(2,$legacyDay,'Gelb','u-l2');$l3=$event(2,$legacyDay,'Bio','u-l3');
foreach([[$l1,0],[$l2,0],[$l3,1]] as [$e,$done]){$p->prepare("INSERT INTO tasks(property_id,title,due_date,duration_minutes,waste_key,done,notes) VALUES(2,?,?,15,?,?,?)")->execute(['Tonnen rausstellen: '.$e['fraction'],planDate($legacyDay,-1),'waste:'.$e['id'].':raus',$done,$e['fraction']==='Gelb'?'Schlüssel im Kasten':null]);$t=(int)$p->lastInsertId();$p->prepare("INSERT INTO waste_task_links(event_id,direction,task_id) VALUES(?,'raus',?)")->execute([$e['id'],$t]);if($e['fraction']==='Gelb')$gelbTask=$t;}
$p->exec('INSERT INTO work_logs(task_id,property_id,performed_on,duration_minutes,description) VALUES('.$gelbTask.",2,CURDATE(),5,'Synthetic')");
$doneLegacy=$p->query("SELECT * FROM tasks WHERE done=1 AND property_id=2")->fetchAll();
$p->beginTransaction();$r=wasteMergeOpenBlocks($p,1);$p->commit();$rows=$p->query("SELECT * FROM tasks WHERE property_id=2 AND due_date='".planDate($legacyDay,-1)."' ORDER BY id")->fetchAll();
wb($r['merged']===1&&count($rows)===2,'two open single tasks merged into one block, done one kept');wb($rows[0]['title']==='Papiertonne und Gelbe Tonne rausstellen'&&$rows[0]['waste_key']===wasteBlockKey(2,planDate($legacyDay,-1),'raus'),'merged block titled and keyed');
wb(str_contains((string)$rows[0]['notes'],'Schlüssel im Kasten'),'notes of absorbed task kept');wb((int)$p->query('SELECT task_id FROM work_logs LIMIT 1')->fetchColumn()===(int)$rows[0]['id'],'work report re-pointed to the block');
wb($p->query("SELECT * FROM tasks WHERE done=1 AND property_id=2")->fetchAll()===$doneLegacy,'done legacy task untouched');
wb((int)$p->query("SELECT COUNT(*) FROM waste_history WHERE event_type='blocks_merged'")->fetchColumn()===1,'merge mapping recorded');
$snap=$p->query('SELECT * FROM tasks ORDER BY id')->fetchAll();$p->beginTransaction();$again=wasteMergeOpenBlocks($p,1);$p->commit();wb($again['merged']===0&&$p->query('SELECT * FROM tasks ORDER BY id')->fetchAll()===$snap,'merge is repeatable without changes');
// 7. ICS-/CSV-Export nennt den Block mit allen Tonnen.
$_SESSION=[];require_once dirname(__DIR__).'/private/waste_export.php';$ics=wasteExportIcs($p,$today->format('Y-m-d'),$d(30));wb(substr_count($ics,'BEGIN:VEVENT')===(int)$p->query("SELECT COUNT(*) FROM tasks t WHERE t.due_date BETWEEN '".$today->format('Y-m-d')."' AND '".$d(30)."' AND (t.waste_key LIKE 'waste:%' OR EXISTS(SELECT 1 FROM waste_task_links l WHERE l.task_id=t.id) OR EXISTS(SELECT 1 FROM recurring_jobs r WHERE r.id=t.recurring_job_id AND r.rule_source='special_weekly'))")->fetchColumn(),'ICS: one event per block (incl. weekly special collections)');
wb(str_contains($ics,'SUMMARY:Heiligenhäuschenweg 2–4: Restmüll zurückstellen')||str_contains($ics,'SUMMARY:Heiligenhäuschenweg 2–4: Restmüll und Biotonne'),'ICS summary = object name + bins');wb(!preg_match('~SUMMARY:[^\r]*Synthetic street~',$ics)&&str_contains($ics,'LOCATION:Synthetic street 2'),'ICS summary shows object name only, address only in LOCATION');
$csv=wasteExportCsv($p,$today->format('Y-m-d'),$d(30));wb(str_contains($csv,'"Papiertonne, Gelbe Tonne"')||str_contains($csv,'Papiertonne, Gelbe Tonne'),'CSV lists bin types of the block individually');
echo "Waste blocks PASS ($n assertions; synthetic data only).\n";
