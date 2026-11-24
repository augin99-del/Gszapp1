<?php
declare(strict_types=1);
// Merkliste (Schema 390): schnelle To-dos und „Rechnungen offen“. Synthetische Objekte, keine echten Adressen oder Kunden.
set_error_handler(static function($s,$m,$f,$l){if(error_reporting()&$s)throw new ErrorException($m,0,$s,$f,$l);return false;});
require dirname(__DIR__).'/private/install_core.php';require dirname(__DIR__).'/private/web_update.php';require_once dirname(__DIR__).'/private/notes.php';
$dsn=getenv('GSZ_TEST_DSN')?:'';if(!preg_match('/^mysql:unix_socket=[^;]+;dbname=gsz_test_notes_[a-z0-9_]+$/D',$dsn))throw new RuntimeException('Empty local test DB required.');
$p=new PDO($dsn,'root','',[PDO::ATTR_ERRMODE=>PDO::ERRMODE_EXCEPTION,PDO::ATTR_DEFAULT_FETCH_MODE=>PDO::FETCH_ASSOC,PDO::ATTR_EMULATE_PREPARES=>false]);if($p->query('SHOW TABLES')->fetchAll())throw new RuntimeException('Empty DB required.');
$n=0;function nt(bool $v,string $s):void{global $n;$n++;if(!$v)throw new RuntimeException($s);}
// Update-Weg 380 → 390: erst 380 herstellen, dann den Schritt anbieten und ausführen
$p->exec(file_get_contents(dirname(__DIR__).'/private/fresh_install_v192.sql'));$p->exec(file_get_contents(dirname(__DIR__).'/private/migration_v200.sql'));gszMigratePhase2($p);gszMigratePhase3($p);
foreach([230,240,250,260,270,280,290,300,310,320,330,340,350,360,370] as $v)$p->exec(file_get_contents(dirname(__DIR__).'/private/migration_v'.$v.'.sql'));
$p->exec("INSERT INTO admins(email,password_hash) VALUES('owner@example.invalid','x')");gszMigrate380($p);
nt(!gszSchemaCurrent($p),'380 is not current any more');$i=gszSetupInspect($p);nt($i['status']==='upgrade'&&$i['version']===380&&$i['steps']===[390,400,410],'380 offers the steps to 390, 400 and 410');
$tasksBefore=$p->query('SELECT COUNT(*) FROM tasks')->fetchColumn();
$after=gszSetupMigrate($p);nt($after['status']==='current'&&$after['version']===410&&gszSchemaCurrent($p),'migrated to 390');
nt(count($p->query('SHOW TABLES')->fetchAll())===73,'73 tables');nt($p->query('SELECT COUNT(*) FROM tasks')->fetchColumn()===$tasksBefore,'existing data untouched');
gszMigrate390($p);nt(gszSetupInspect($p)['status']==='current','390 repeatable');
// Objekte (synthetisch)
$ins=$p->prepare('INSERT INTO properties(name,address) VALUES(?,?)');
foreach([['Wohnanlage Fontänenstraße','Fontänenstraße 5, 00000 Musterstadt'],['Haus Fröbel','Fröbelstraße 12, 00000 Musterstadt'],['Fröbelstraße 14','Fröbelstraße 14, 00000 Musterstadt'],['Am Lindenhof','Lindenweg 3, 00000 Musterstadt']] as $r)$ins->execute($r);
$id=fn(string $name)=>(int)$p->query('SELECT id FROM properties WHERE name='.$p->quote($name))->fetchColumn();
$d=fn(string $t)=>gszNoteDetectProperty($p,$t)['id']??null;
nt($d('Loch Wand Fontänenstraße')===$id('Wohnanlage Fontänenstraße'),'street in text');
nt($d('Loch in der Wand Fontainestrasse')===$id('Wohnanlage Fontänenstraße'),'small typo and ss tolerated');
nt($d('fontaenenstr. Licht')===$id('Wohnanlage Fontänenstraße'),'abbreviation str. and ae');
nt($d('Fröbelstraße Beleuchtung')===null,'two objects on the same street: no guess');
nt($d('Fröbelstraße 12 Beleuchtung')===$id('Haus Fröbel')&&$d('Fröbelstr. 14 Beleuchtung')===$id('Fröbelstraße 14'),'house number decides');
nt($d('Lindenhof Hecke schneiden')===$id('Am Lindenhof'),'object name word');
nt($d('Dübel kaufen')===null&&$d('')===null,'no object');
// Anlegen
nt(gszNoteDue('week','','2026-11-23')==='2026-11-27'&&gszNoteDue('week','','2026-11-28')==='2026-11-28'&&gszNoteDue('tomorrow','','2026-11-23')==='2026-11-24'&&gszNoteDue('','','2026-11-23')===null&&gszNoteDue('date','2026-02-30')===null,'due choices');
$a=gszNoteAdd($p,1,['kind'=>'todo','title'=>'Loch Wand Fontainestraße','property_id'=>'auto','due'=>'','material'=>'Spachtelmasse']);
nt($a['property']['id']===$id('Wohnanlage Fontänenstraße')&&$a['due_date']===null,'quick todo detected object, no date');
$b=gszNoteAdd($p,1,['kind'=>'invoice','title'=>'Fröbelstraße 12 Beleuchtung','property_id'=>'auto','note'=>'2 Std. + 3 Leuchtmittel','due'=>'today','material'=>'ignored']);
$row=gszNoteGet($p,$b['id']);nt($row['kind']==='invoice'&&$row['due_date']===null&&$row['material']===''&&$row['note']==='2 Std. + 3 Leuchtmittel'&&(int)$row['property_id']===$id('Haus Fröbel'),'invoice reminder: note line, no date/material');
$c=gszNoteAdd($p,1,['kind'=>'todo','title'=>'Dübel kaufen','property_id'=>'','due_date'=>date('Y-m-d',strtotime('-3 days'))]);
nt(gszNoteGet($p,$c['id'])['property_id']===null,'without object');
try{gszNoteAdd($p,1,['kind'=>'todo','title'=>'  ']);nt(false,'empty');}catch(RuntimeException $e){nt(str_contains($e->getMessage(),'aufschreiben'),'empty title refused');}
try{gszNoteAdd($p,1,['kind'=>'todo','title'=>'x','due_date'=>'2026-13-01']);nt(false,'date');}catch(RuntimeException $e){nt($e->getMessage()==='Datum ungültig.','bad date refused');}
try{gszNoteAdd($p,1,['kind'=>'rechnung','title'=>'x']);nt(false,'kind');}catch(RuntimeException $e){nt(true,'');}
$counts=gszNotesCounts($p);nt($counts['todo']===2&&$counts['invoice']===1&&$counts['shopping']===1&&$counts['late']===1,'counts');
$list=gszNotesList($p,'todo');nt((int)$list['open'][0]['id']===$c['id'],'overdue first');nt(gszNoteAge($list['open'][0])==='seit 3 Tagen überfällig'&&gszNoteLate($list['open'][0]),'age text overdue');
nt(str_starts_with(gszNoteAge(gszNoteGet($p,$a['id'])),'notiert'),'age text undated');
// Einkaufsliste
$r=gszNoteGet($p,$a['id']);$r=gszNoteMaterialDone($p,$a['id'],true,$r['row_version']);nt((int)$r['material_done']===1&&gszNotesCounts($p)['shopping']===0&&!$r['done_at'],'bought, todo stays open');
// Bearbeiten mit Versionsschutz
$r=gszNoteUpdate($p,1,$a['id'],['title'=>'Loch Wand Treppenhaus','property_id'=>(string)$id('Wohnanlage Fontänenstraße'),'due_date'=>'','material'=>'Spachtelmasse','note'=>'EG links','row_version'=>$r['row_version']]);
nt($r['title']==='Loch Wand Treppenhaus'&&(int)$r['material_done']===1&&$r['note']==='EG links','updated, unchanged material keeps bought');
try{gszNoteUpdate($p,1,$a['id'],['title'=>'alt','row_version'=>1]);nt(false,'stale');}catch(RuntimeException $e){nt(str_contains($e->getMessage(),'inzwischen geändert'),'stale edit refused');}
$r=gszNoteUpdate($p,1,$a['id'],['title'=>'Loch Wand Treppenhaus','property_id'=>(string)$id('Wohnanlage Fontänenstraße'),'material'=>'Spachtelmasse, Farbe','row_version'=>$r['row_version']]);nt((int)$r['material_done']===0,'changed material back on the list');
// Abhaken + Rechnung merken (einmal)
$r=gszNoteSetDone($p,1,$a['id'],true,$r['row_version']);nt($r['done_at']!==null,'todo done');
$x=gszNoteInvoiceFrom($p,1,'note',$a['id']);$y=gszNoteInvoiceFrom($p,1,'note',$a['id']);nt(!$x['existed']&&$y['existed']&&$x['id']===$y['id'],'invoice reminder from todo exactly once');
$inv=gszNoteGet($p,$x['id']);nt($inv['kind']==='invoice'&&$inv['title']==='Loch Wand Treppenhaus'&&(int)$inv['property_id']===$id('Wohnanlage Fontänenstraße'),'reminder copies title and object');
$p->prepare('INSERT INTO tasks(property_id,title,due_date,done) VALUES(?,?,CURDATE(),1)')->execute([$id('Am Lindenhof'),'Hecke schneiden']);$task=(int)$p->lastInsertId();
$t=gszNoteInvoiceFrom($p,1,'task',$task);nt(!$t['existed']&&gszNoteInvoiceFrom($p,1,'task',$task)['existed'],'invoice reminder from finished task once');
try{gszNoteInvoiceFrom($p,1,'note',$b['id']);nt(false,'from invoice');}catch(RuntimeException $e){nt(true,'');}
nt(gszNotesCounts($p)['invoice']===3,'three invoices open');
$i=gszNoteGet($p,$b['id']);gszNoteSetDone($p,1,$b['id'],true,$i['row_version']);$l=gszNotesList($p,'invoice');nt(count($l['open'])===2&&count($l['done'])===1,'written invoice moves to done');
$p->prepare('UPDATE quick_notes SET done_at=NOW()-INTERVAL 31 DAY WHERE id=?')->execute([$b['id']]);nt(count(gszNotesList($p,'invoice')['done'])===0&&gszNoteGet($p,$b['id'])!==null,'done older than 30 days hidden, not deleted');
// Objekt gelöscht → Eintrag bleibt ohne Objekt; Aufgabe gelöscht → Erinnerung bleibt
$p->prepare('DELETE FROM tasks WHERE id=?')->execute([$task]);nt(gszNoteGet($p,$t['id'])['task_id']===null,'reminder survives deleted task');
gszNoteDelete($p,1,$c['id']);nt(gszNoteGet($p,$c['id'])===null,'deleted');
nt(gszNoteReturn('property.php?id=3#todos')==='property.php?id=3#todos'&&gszNoteReturn('https://evil.example/')==='notes.php'&&gszNoteReturn('//evil')==='notes.php'&&gszNoteReturn('../x.php')==='notes.php','return only to own pages');
nt((int)$p->query("SELECT COUNT(*) FROM app_audit WHERE action IN ('note_added','invoice_note_added','invoice_note_done','note_deleted')")->fetchColumn()>=5,'audited');
// Auf Tour mitnehmen
$fo=$id('Wohnanlage Fontänenstraße');$q=gszNoteAdd($p,1,['kind'=>'todo','title'=>'Fontänenstraße Klingel','property_id'=>'auto']);$fut=gszNoteAdd($p,1,['kind'=>'todo','title'=>'Fontänenstraße Lampe','property_id'=>'auto','due_date'=>date('Y-m-d',strtotime('+3 days'))]);
$t=gszNotesTakeAlong($p,date('Y-m-d'),[$fo,$id('Am Lindenhof')]);$ids=array_map('intval',array_column($t[$fo]??[],'id'));
nt(in_array($q['id'],$ids,true)&&!in_array($fut['id'],$ids,true)&&!isset($t[$id('Am Lindenhof')]),'undated todo goes along today, future one not yet');
nt(in_array($fut['id'],array_map('intval',array_column(gszNotesTakeAlong($p,date('Y-m-d',strtotime('+3 days')),[$fo])[$fo],'id')),true),'dated todo from its day on');
nt(gszNotesTakeAlong($p,date('Y-m-d',strtotime('-1 day')),[$fo])===[],'nothing for past days');
$days=[];for($i=0;$i<5;$i++)$days[date('Y-m-d',strtotime("+$i days"))]=[$fo];$w=gszNotesTakeAlongWeek($p,$days);$first=array_key_first($days);
nt(count($w[$first][$fo])>=1&&in_array($q['id'],array_map('intval',array_column($w[$first][$fo],'id')),true)&&!in_array($q['id'],array_map('intval',array_column($w[date('Y-m-d',strtotime('+1 day'))][$fo]??[],'id')),true),'week: only at the first visit');
nt(in_array($fut['id'],array_map('intval',array_column($w[date('Y-m-d',strtotime('+3 days'))][$fo]??[],'id')),true),'week: dated todo at the first visit from its day');
echo "Merkliste PASS ($n checks; migration 380→390, object detection, todo/invoice/shopping, once-only invoice reminder).\n";
