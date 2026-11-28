<?php
declare(strict_types=1);
// Schema 430: Mieter-Meldungen, Portal-Daten, Helfer-Zugang, Schlüsseldienst-Einsätze, Preisrechner, QR und Druck-PDFs. Synthetische Daten.
set_error_handler(static function($s,$m,$f,$l){if(error_reporting()&$s)throw new ErrorException($m,0,$s,$f,$l);return false;});
require dirname(__DIR__).'/private/install_core.php';require dirname(__DIR__).'/private/web_update.php';
foreach(['reports','worker_app','lock_jobs','calc','service_pdf','qr'] as $f)require_once dirname(__DIR__).'/private/'.$f.'.php';
$dsn=getenv('GSZ_TEST_DSN')?:'';if(!preg_match('/^mysql:unix_socket=[^;]+;dbname=gsz_test_service_[a-z0-9_]+$/D',$dsn))throw new RuntimeException('Empty local test DB required.');
$p=new PDO($dsn,'root','',[PDO::ATTR_ERRMODE=>PDO::ERRMODE_EXCEPTION,PDO::ATTR_DEFAULT_FETCH_MODE=>PDO::FETCH_ASSOC,PDO::ATTR_EMULATE_PREPARES=>false]);if($p->query('SHOW TABLES')->fetchAll())throw new RuntimeException('Empty DB required.');
$n=0;function sv(bool $v,string $s):void{global $n;$n++;if(!$v)throw new RuntimeException($s);}
function svThrows(callable $f,string $s,string $cls=RuntimeException::class):void{try{$f();sv(false,$s);}catch(Throwable $e){sv($e instanceof $cls,$s.' ('.get_class($e).')');}}
$r=gszInstallFresh($p);sv($r['schema']==='460'&&$r['tables']===87&&gszSchemaCurrent($p)&&gszServiceReady($p),'fresh install has 430 with 80 tables');
$p->exec("INSERT INTO admins(email,password_hash) VALUES('owner@example.invalid','x')");$p->exec("INSERT INTO properties(name,address,contact_email) VALUES('Musterweg 1','Musterweg 1, 00000 Musterstadt','hv@example.invalid'),('Beispielstraße 2','Beispielstraße 2','')");
$today=(new DateTimeImmutable('today',new DateTimeZone('Europe/Berlin')))->format('Y-m-d');
// QR-Code: Struktur (Größe, Suchmuster) – die Lesbarkeit prüft der Browsertest mit einem echten Leser
$m=gszQrMatrix('https://gszintern.de/melden.php?k='.str_repeat('A',32));sv(count($m)===37&&$m[0][0]&&$m[0][6]&&!$m[1][1]&&$m[2][2]&&$m[0][36]&&$m[36][0],'QR version 5 with finder patterns');
sv(str_starts_with(gszQrSvg('x'),'<svg')&&substr_count(gszQrSvg('x'),'<path')===1,'QR as SVG');svThrows(fn()=>gszQrMatrix(str_repeat('x',300)),'QR rejects too long text',InvalidArgumentException::class);
// A: Links und Meldungen
sv(gszLinks($p,1)['report_token']===null,'no link by default');$t=gszLinkRenew($p,1,1,'report');sv(gszTokenValid($t)&&gszLinkProperty($p,'report',$t)['id']===1,'report link created');
sv(gszLinkProperty($p,'portal',$t)===null&&gszLinkProperty($p,'report','x')===null&&gszLinkProperty($p,'report',str_repeat('A',32))===null,'link only valid for its kind');
$t2=gszLinkRenew($p,1,1,'report');sv($t2!==$t&&gszLinkProperty($p,'report',$t)===null&&gszLinkProperty($p,'report',$t2)!==null,'renewing invalidates the old QR code');
svThrows(fn()=>gszLinkRenew($p,1,999,'report'),'unknown property');svThrows(fn()=>gszLinkRenew($p,1,1,'admin'),'unknown link kind');
$prop=gszLinkProperty($p,'report',$t2);$id=gszDefectSubmit($p,$prop,['name'=>'Mieter Test','phone'=>'0176 0000000','location'=>'Treppenhaus','message'=>"Licht im 2. OG defekt 💡",'name'=>'Whg. 3 Müller','phone'=>'0176 000000']);
$d=gszDefect($p,$id);sv($d['status']==='neu'&&$d['contact']==='Whg. 3 Müller · 0176 000000'&&gszDefectNewCount($p)===1,'report stored as new');
svThrows(fn()=>gszDefectSubmit($p,$prop,['name'=>'Mieter Test','phone'=>'0176 0000000','message'=>'x']),'too short report refused');svThrows(fn()=>gszDefectSubmit($p,$prop,['kind'=>'Licht defekt','phone'=>'0176 0000000']),'name required');svThrows(fn()=>gszDefectSubmit($p,$prop,['kind'=>'Licht defekt','name'=>'Müller','phone'=>'abc']),'phone required');svThrows(fn()=>gszDefectSubmit($p,$prop,['kind'=>'Licht defekt','name'=>'Müller','phone'=>'12']),'too short phone refused');svThrows(fn()=>gszDefectSubmit($p,$prop,['name'=>'Mieter Test','phone'=>'0176 0000000','message'=>'Spam Spam','website'=>'http://x']),'honeypot',DomainException::class);
for($i=0;$i<GSZ_DEFECT_LIMIT_HOUR-1;$i++)gszDefectSubmit($p,$prop,['name'=>'Mieter Test','phone'=>'0176 0000000','message'=>'Meldung '.$i]);svThrows(fn()=>gszDefectSubmit($p,$prop,['name'=>'Mieter Test','phone'=>'0176 0000000','message'=>'Eine zu viel']),'rate limit per house');
sv(gszDefectSubmit($p,$prop,['name'=>'Mieter Test','phone'=>'0176 0000000','message'=>'Zwei Stunden später'],null,time()+7200)>0,'limit resets after an hour');
$note=gszDefectToNote($p,1,$id);$nr=$p->query("SELECT * FROM quick_notes WHERE id=$note")->fetch();sv($nr['kind']==='todo'&&(int)$nr['property_id']===1&&str_starts_with($nr['title'],'Treppenhaus: Licht')&&str_contains($nr['note'],'Whg. 3 Müller · 0176 000000'),'report becomes a to-do with property and contact');
sv(gszDefectToNote($p,1,$id)===$note&&gszDefectState(gszDefect($p,$id))==='uebernommen','taking over twice is harmless');
$p->exec("UPDATE quick_notes SET done_at=NOW() WHERE id=$note");sv(gszDefectState(gszDefect($p,$id))==='erledigt','report done as soon as the to-do is ticked');
$open=gszDefects($p,'offen',1);sv(!in_array($id,array_column($open,'id'))&&count($open)===GSZ_DEFECT_LIMIT_HOUR,'open list excludes done');
gszDefectSetStatus($p,1,(int)$open[0]['id'],'verworfen');
// Abhaken, wieder öffnen, löschen
$p->exec("UPDATE quick_notes SET done_at=NULL WHERE id=$note");gszDefectDone($p,1,$id);sv(gszDefect($p,$id)['status']==='erledigt'&&$p->query("SELECT done_at FROM quick_notes WHERE id=$note")->fetchColumn()!==null,'tick off: report done, to-do done too');
gszDefectReopen($p,1,$id);sv(gszDefectState(gszDefect($p,$id))==='uebernommen'&&$p->query("SELECT done_at FROM quick_notes WHERE id=$note")->fetchColumn()===null,'reopen: back to in progress, to-do open');
$plain=(int)$open[1]['id'];gszDefectDone($p,1,$plain);gszDefectReopen($p,1,$plain);sv(gszDefect($p,$plain)['status']==='neu','reopen without to-do → new');
gszDefectDelete($p,1,$id);sv(gszDefect($p,$id)===null&&(int)$p->query("SELECT COUNT(*) FROM quick_notes WHERE id=$note")->fetchColumn()===1,'delete removes report, keeps the to-do');svThrows(fn()=>gszDefectDelete($p,1,$id),'deleting twice');svThrows(fn()=>gszDefectSetStatus($p,1,$id,'quatsch'),'bad status');
// B: Portal und Monatsbericht
$pt=gszLinkRenew($p,1,1,'portal');sv(gszLinkProperty($p,'portal',$pt)['name']==='Musterweg 1'&&gszLinkProperty($p,'report',$t2)!==null,'portal link next to report link');
$month=substr($today,0,7);$p->prepare("INSERT INTO tasks(property_id,title,due_date,done,completed_on) VALUES(1,'Treppenhausreinigung',?,1,?)")->execute([$today,$today]);
$p->prepare("INSERT INTO work_logs(property_id,performed_on,description,duration_minutes) VALUES(1,?,'Glasreinigung Eingang',45)")->execute([$today]);
$p->prepare("INSERT INTO tasks(property_id,title,due_date) VALUES(1,'Winterdienst-Kontrolle',?),(2,'Fremdes Objekt',?)")->execute([(new DateTimeImmutable($today))->modify('+3 days')->format('Y-m-d'),$today]);
$pd=gszPortalData($p,1,$month,$today);sv(count($pd['logs'])===1&&count($pd['done'])===1&&$pd['minutes']===45&&$pd['visits']===1,'portal: work logs + ticked tasks, minutes, visit days');
sv(count($pd['next'])===1&&$pd['next'][0]['title']==='Winterdienst-Kontrolle','portal: only this property\'s next appointments');
sv(!in_array('verworfen',array_column($pd['defects'],'status'),true)&&count($pd['defects'])>=1,'portal: discarded reports hidden');svThrows(fn()=>gszPortalData($p,1,'2026-13'),'bad month');
sv(count(gszPortalMonths('2026-01-15'))===6&&gszPortalMonths('2026-01-15')[1]==='2025-12'&&gszMonthName('2026-03')==='März 2026','portal months');
$pdf=gszMonthReportPdf($p,['id'=>1,'name'=>'Musterweg 1','address'=>'Musterweg 1'],$pd);sv(str_starts_with($pdf,'%PDF')&&strlen($pdf)>3000,'monthly report PDF (emoji replaced, not crashing)');
$pdf=gszAushangPdf($p,['id'=>1,'name'=>'Musterweg 1','address'=>'Musterweg 1'],$t2);sv(str_starts_with($pdf,'%PDF')&&strlen($pdf)>3000,'notice PDF with QR code');
gszLinkRenew($p,1,1,'portal',true);sv(gszLinkProperty($p,'portal',$pt)===null&&gszLinks($p,1)['portal_token']===null,'portal switched off');
// C: Helfer
$p->exec("INSERT INTO operations_workers(name,kind) VALUES('Max Helfer','employee'),('Erika Andere','employee')");
svThrows(fn()=>gszWorkerAccountSave($p,1,1,['login'=>'max','password'=>'kurz','active'=>1]),'short password refused');svThrows(fn()=>gszWorkerAccountSave($p,1,1,['login'=>'M ax!','password'=>'Lang-genug-2026','active'=>1]),'bad login refused');
gszWorkerAccountSave($p,1,1,['login'=>'Max','password'=>'Lang-genug-2026','active'=>1]);gszWorkerAccountSave($p,1,2,['login'=>'erika','password'=>'Anderes-Pw-2026','active'=>1]);
svThrows(fn()=>gszWorkerAccountSave($p,1,2,['login'=>'max','password'=>'','active'=>1]),'login must be unique');
sv(!str_contains((string)$p->query('SELECT password_hash FROM worker_accounts WHERE worker_id=1')->fetchColumn(),'Lang-genug'),'password stored as hash only');
sv(gszWorkerLogin($p,'MAX','Lang-genug-2026')===1&&gszWorkerLogin($p,'max','falsch')===null&&gszWorkerLogin($p,'niemand','x')===null,'login (case-insensitive name)');
for($i=0;$i<GSZ_WORKER_MAX_FAILS-1;$i++)gszWorkerLogin($p,'max','falsch');svThrows(fn()=>gszWorkerLogin($p,'max','Lang-genug-2026'),'locked after 5 failures');
sv(gszWorkerLogin($p,'max','Lang-genug-2026',time()+GSZ_WORKER_LOCK_MIN*60+5)===1,'lock expires');
gszWorkerAccountSave($p,1,2,['login'=>'erika','password'=>'','active'=>0]);sv(gszWorkerLogin($p,'erika','Anderes-Pw-2026')===null&&!gszWorkerActive($p,2),'switched-off account cannot log in');
$p->prepare("INSERT INTO tasks(property_id,title,due_date,assigned_worker_id,notes) VALUES(1,'Treppenhaus wischen',?,1,'Bitte auch Keller'),(2,'Für Erika',?,2,''),(1,'Nicht zugeteilt',?,NULL,'')")->execute([$today,$today,$today]);
$mine=gszWorkerTasks($p,1,$today);sv(count($mine)===1&&$mine[0]['title']==='Treppenhaus wischen'&&$mine[0]['address']!=='','helper sees only own tasks with address');$tid=(int)$mine[0]['id'];
$other=(int)$p->query("SELECT id FROM tasks WHERE title='Für Erika'")->fetchColumn();svThrows(fn()=>gszWorkerStart($p,1,$other),'cannot start someone else\'s task');
$log=gszWorkerStart($p,1,$tid,$today);sv(gszWorkerStart($p,1,$tid,$today)===$log,'start once');svThrows(fn()=>gszWorkerLog($p,2,$log)??throw new RuntimeException('x'),'other helper cannot see the log');
svThrows(fn()=>gszWorkerPhoto($p,1,$log,'selfie',['error'=>0]),'photo label checked');
$p->exec("UPDATE work_logs SET started_at=UTC_TIMESTAMP()-INTERVAL 37 MINUTE WHERE id=$log");gszWorkerFinish($p,1,$log,'Licht 2. OG defekt');
$w=$p->query("SELECT * FROM work_logs WHERE id=$log")->fetch();$tk=$p->query("SELECT * FROM tasks WHERE id=$tid")->fetch();
sv($w['ended_at']&&(int)$w['duration_minutes']>=36&&(int)$w['duration_minutes']<=38&&$w['notes']==='Licht 2. OG defekt'&&(int)$w['worker_id']===1,'finish: end time, duration, note');
sv((int)$tk['done']===1&&$tk['completed_on']===$today,'finish completes the task');gszWorkerFinish($p,1,$log);sv(gszWorkerMonth($p,$month)[0]['name']==='Max Helfer'&&(int)gszWorkerMonth($p,$month)[0]['jobs']===1,'owner sees helper times');
sv(gszPortalData($p,1,$month,$today)['minutes']>=81,'helper work shows in the portal');
// D: Schlüsseldienst
sv(gszEuroCents('120')===12000&&gszEuroCents('85,50')===8550&&gszEuroCents('1.200,00')===120000&&gszEuroCents('')===0,'euro parsing');svThrows(fn()=>gszEuroCents('12abc'),'bad amount');
$j1=gszLockSave($p,1,['job_date'=>$today,'job_time'=>'23:15','place'=>'Verl','kind'=>'zugefallen','amount'=>'150','payment'=>'bar','channel'=>'google','phone'=>'0176 1234567','customer'=>'Max Beispiel']);
$j2=gszLockSave($p,1,['job_date'=>$today,'place'=>'Gütersloh','kind'=>'zylinder','amount'=>'89,90','payment'=>'rechnung','channel'=>'website','customer'=>'Firma Beispiel']);
$j=gszLockJob($p,$j2);$inv=$p->query('SELECT * FROM quick_notes WHERE id='.(int)$j['note_id'])->fetch();sv($inv['kind']==='invoice'&&str_contains($inv['title'],'Gütersloh')&&str_contains($inv['title'],'89,90 €'),'payment "invoice" creates reminder in Merkliste');
gszLockSave($p,1,['job_date'=>$today,'place'=>'Gütersloh','kind'=>'zylinder','amount'=>'95','payment'=>'rechnung','channel'=>'website'],$j2);sv((int)$p->query("SELECT COUNT(*) FROM quick_notes WHERE kind='invoice'")->fetchColumn()===1,'editing does not duplicate the reminder');
svThrows(fn()=>gszLockSave($p,1,['job_date'=>(new DateTimeImmutable($today))->modify('+2 days')->format('Y-m-d'),'place'=>'Verl']),'future date refused');svThrows(fn()=>gszLockSave($p,1,['place'=>'','job_date'=>$today]),'place required');
$st=gszLockStats($p,$month);sv($st['count']===2&&$st['total']===24500&&$st['places']['Verl']['c']===15000&&$st['channels']['website']['n']===1&&$st['night']===1,'stats per place, channel, night');
sv($st['open']['n']===1&&$st['open']['c']===9500,'open invoices counted');$p->exec('UPDATE quick_notes SET done_at=NOW() WHERE id='.(int)$j['note_id']);sv(gszLockStats($p,$month)['open']['n']===0,'invoice written → no longer open');
sv(count($st['months'])===12&&array_key_last($st['months'])===$month,'12-month history');
$p->exec("INSERT INTO leads(source,name,phone,service) VALUES('telefon','Lead Kunde','0176 2','Schlüsseldienst')");$lid=(int)$p->lastInsertId();gszLockSave($p,1,['job_date'=>$today,'place'=>'Verl','lead_id'=>$lid,'amount'=>'80']);
sv($p->query("SELECT status FROM leads WHERE id=$lid")->fetchColumn()==='auftrag','job from a lead marks the lead as won');
// E: Preisrechner
sv(gszCalcQuote($p,['floors'=>3])===null&&!gszCalcConfig($p)['enabled'],'calculator off by default (no invented prices)');
svThrows(fn()=>gszCalcSave($p,1,['calc_enabled'=>'1','calc_rate'=>'']),'cannot enable without rate');svThrows(fn()=>gszCalcSave($p,1,['calc_rate'=>'abc']),'numbers only');
gszCalcSave($p,1,['calc_enabled'=>'1','calc_rate'=>'36','calc_base_min'=>'10','calc_floor_min'=>'5','calc_cellar_min'=>'10','calc_elevator_min'=>'5','calc_window_min'=>'3','calc_min_visit'=>'','calc_range_pct'=>'10','calc_vat'=>'zzgl']);
$q=gszCalcQuote($p,['floors'=>3,'entrances'=>1,'rhythm'=>'weekly','cellar'=>1]);// (10+15+10)=35 min → 21 € je Einsatz → 90,93 €/Monat → 80–105
sv($q['minutes']===35&&$q['low']===80&&$q['high']===105&&str_contains($q['text'],'zzgl. MwSt.'),'quote range from settings');
$q2=gszCalcQuote($p,['floors'=>99,'entrances'=>-3,'rhythm'=>'hack']);sv($q2['minutes']===110&&$q2['rhythm']==='wöchentlich','inputs clamped');
sv(gszCalcQuote($p,['floors'=>3,'rhythm'=>'monthly'])['high']<$q['low'],'monthly cheaper than weekly');
// F: Bewertungskarten
$pdf=gszReviewCardsPdf($p);sv(str_starts_with($pdf,'%PDF')&&strlen($pdf)>3000,'review cards PDF');gszSettingSet($p,'review_url','');svThrows(fn()=>gszReviewCardsPdf($p),'cards need a review link');
echo "Service PASS ($n checks; QR reports, portal, helper app, locksmith jobs, calculator, print PDFs).\n";
