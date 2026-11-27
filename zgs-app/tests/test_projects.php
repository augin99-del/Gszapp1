<?php
declare(strict_types=1);
// Bauprojekte (Schema 410): Gewerke, Handwerker, Termine → Woche, Kosten, Mängel, Gewährleistung. Synthetische Daten.
set_error_handler(static function($s,$m,$f,$l){if(error_reporting()&$s)throw new ErrorException($m,0,$s,$f,$l);return false;});
require dirname(__DIR__).'/private/install_core.php';require dirname(__DIR__).'/private/web_update.php';require_once dirname(__DIR__).'/private/projects.php';
$dsn=getenv('GSZ_TEST_DSN')?:'';if(!preg_match('/^mysql:unix_socket=[^;]+;dbname=gsz_test_projects_[a-z0-9_]+$/D',$dsn))throw new RuntimeException('Empty local test DB required.');
$p=new PDO($dsn,'root','',[PDO::ATTR_ERRMODE=>PDO::ERRMODE_EXCEPTION,PDO::ATTR_DEFAULT_FETCH_MODE=>PDO::FETCH_ASSOC,PDO::ATTR_EMULATE_PREPARES=>false]);if($p->query('SHOW TABLES')->fetchAll())throw new RuntimeException('Empty DB required.');
$n=0;function pj(bool $v,string $s):void{global $n;$n++;if(!$v)throw new RuntimeException($s);}
function pjThrows(callable $f,string $s):void{try{$f();pj(false,$s);}catch(RuntimeException $e){pj(true,$s);}}
// Update-Weg 400 → 410
$p->exec(file_get_contents(dirname(__DIR__).'/private/fresh_install_v192.sql'));$p->exec(file_get_contents(dirname(__DIR__).'/private/migration_v200.sql'));gszMigratePhase2($p);gszMigratePhase3($p);
foreach([230,240,250,260,270,280,290,300,310,320,330,340,350,360,370] as $v)$p->exec(file_get_contents(dirname(__DIR__).'/private/migration_v'.$v.'.sql'));
$p->exec("INSERT INTO admins(email,password_hash) VALUES('owner@example.invalid','x')");gszMigrate380($p);require_once dirname(__DIR__).'/private/migrate_390.php';require_once dirname(__DIR__).'/private/migrate_400.php';gszMigrate390($p);gszMigrate400($p);
$i=gszSetupInspect($p);pj($i['status']==='upgrade'&&$i['version']===400&&$i['steps']===[410,420,430,440,450],'400 offers the steps up to 450');pj(!gszProjectsReady($p),'not ready before update');
pj(gszSetupMigrate($p)['version']===450&&gszSchemaCurrent($p)&&gszProjectsReady($p)&&count($p->query('SHOW TABLES')->fetchAll())===84,'migrated to 450, 84 tables');
gszMigrate410($p);pj(gszSetupInspect($p)['status']==='current','410 repeatable');
$p->exec("INSERT INTO estate_buildings(name,address) VALUES('Haus Synthetik','Testweg 4, 00000 Musterstadt')");$b=(int)$p->lastInsertId();
// Projekt
pj(gszPMoney('180.000')===18000000&&gszPMoney('4.850,50')===485050&&gszPMoney('99')===9900&&gszPMoney('12.5')===1250&&gszPMoney('')===null,'money input forms');pjThrows(fn()=>gszPMoney('abc'),'bad money refused');
pjThrows(fn()=>gszProjectSave($p,1,['title'=>'']),'title required');pjThrows(fn()=>gszProjectSave($p,1,['title'=>'x','start_date'=>'2026-12-10','target_date'=>'2026-12-01']),'dates in order');
$id=gszProjectSave($p,1,['title'=>'Sanierung 4-Familienhaus','building_id'=>(string)$b,'status'=>'laeuft','start_date'=>'2026-11-01','target_date'=>'2027-03-31','budget'=>'180.000']);
$pr=gszProjectGet($p,$id);pj($pr['building_name']==='Haus Synthetik'&&(int)$pr['budget_cents']===18000000,'project with house and budget');
pj(gszTradeTemplate($p,$id)===count(GSZ_TRADE_TEMPLATE)&&gszTradeTemplate($p,$id)===0,'template trades once');
$c=gszContractorSave($p,1,['name'=>'Elektro Muster GmbH','trade'=>'Elektro','phone'=>'0000 000000','email'=>'elektro@example.invalid']);pjThrows(fn()=>gszContractorSave($p,1,['name'=>'X','email'=>'kaputt']),'bad email refused');
$el=(int)$p->query("SELECT id FROM project_trades WHERE project_id=$id AND name='Elektro'")->fetchColumn();
gszTradeSave($p,$id,['name'=>'Elektro','contractor_id'=>(string)$c,'status'=>'laeuft','start_date'=>'2026-11-10','end_date'=>'2026-12-05','budget'=>'25.000','warranty_until'=>(new DateTimeImmutable('today'))->modify('+20 days')->format('Y-m-d')],$el);
gszTradeStatus($p,$id,(int)$p->query("SELECT id FROM project_trades WHERE project_id=$id AND name='Dach'")->fetchColumn(),'fertig');
// Termin → Woche
$ev=gszEventSave($p,1,$id,['day'=>'2026-12-01','start_time'=>'08:00','end_time'=>'12:00','trade_id'=>(string)$el,'contractor_id'=>(string)$c]);
$app=$p->query("SELECT a.* FROM project_events e JOIN planning_appointments a ON a.id=e.appointment_id WHERE e.id=$ev")->fetch();
pj($app&&$app['day']==='2026-12-01'&&str_contains($app['title'],'Elektro – Elektro Muster GmbH')&&str_contains($app['title'],'Sanierung')&&$app['address']==='Testweg 4, 00000 Musterstadt','event creates a week appointment with title and address');
pjThrows(fn()=>gszEventSave($p,1,$id,['day'=>'2026-12-01','start_time'=>'12:00','end_time'=>'08:00','title'=>'x']),'end after start');
gszEventDelete($p,$id,$ev);pj($p->query('SELECT cancelled_at FROM planning_appointments WHERE id='.(int)$app['id'])->fetchColumn()!==null&&!$p->query("SELECT COUNT(*) FROM project_events")->fetchColumn(),'deleting the event cancels the appointment');
// Kosten
gszCostSave($p,1,$id,['kind'=>'angebot','title'=>'Angebot Elektro','amount'=>'24.000','trade_id'=>(string)$el,'accepted'=>'1']);
$r1=gszCostSave($p,1,$id,['kind'=>'rechnung','title'=>'Elektro Abschlag 1','amount'=>'8.000','trade_id'=>(string)$el,'due_date'=>date('Y-m-d',strtotime('+2 days'))]);
gszCostSave($p,1,$id,['kind'=>'rechnung','title'=>'Gerüst','amount'=>'2.500,50','paid'=>'1']);
$s=gszProjectSummary($p,$id);pj($s['invoiced']===1050050&&$s['paid']===250050&&$s['open_to_pay']===800000&&$s['committed']===2400000&&$s['budget']===18000000&&$s['due_invoices']===1,'cost summary');
pj($s['progress']===(int)round(100/count(GSZ_TRADE_TEMPLATE))&&$s['trades']['laeuft']===1,'progress from finished trades');
gszCostFlag($p,$id,$r1,'paid',true);pj(gszProjectSummary($p,$id)['open_to_pay']===0,'mark invoice paid');pjThrows(fn()=>gszCostFlag($p,$id,$r1,'accepted',true),'accepting an invoice is not possible');
// Mängel
$it=gszItemSave($p,$id,['kind'=>'mangel','title'=>'Steckdose Küche fehlt','unit_label'=>'Whg. 2 EG','trade_id'=>(string)$el,'due_date'=>date('Y-m-d',strtotime('-1 day'))]);gszItemSave($p,$id,['title'=>'Silikonfugen Bad','unit_label'=>'Whg. 3 OG']);
$s=gszProjectSummary($p,$id);pj($s['items_open']===2&&$s['defects_open']===1&&$s['items_late']===1,'items counted');
$att=array_column(gszProjectAttention($p),1);pj((bool)array_filter($att,fn($x)=>str_contains($x,'überfällig'))&&(bool)array_filter($att,fn($x)=>str_starts_with($x,'Gewährleistung Elektro endet in 20 Tagen')),'Heute: overdue item and warranty reminder');
$r=gszItemDone($p,$id,$it,true);pj($r['done_at']!==null&&gszProjectSummary($p,$id)['items_open']===1,'item done');pjThrows(fn()=>gszItemDone($p,$id,$it,false,1),'stale version refused');
// Fremde IDs werden abgewiesen
$id2=gszProjectSave($p,1,['title'=>'Anderes Projekt']);pjThrows(fn()=>gszItemSave($p,$id2,['title'=>'x','trade_id'=>(string)$el]),'trade of another project refused');
// Löschen räumt mit auf
gszEventSave($p,1,$id,['day'=>'2026-12-02','start_time'=>'08:00','end_time'=>'09:00','title'=>'Abnahme']);gszProjectDelete($p,1,$id);
pj(!$p->query("SELECT COUNT(*) FROM project_trades WHERE project_id=$id")->fetchColumn()&&!$p->query("SELECT COUNT(*) FROM planning_appointments WHERE title LIKE 'Bau: Abnahme%' AND cancelled_at IS NULL")->fetchColumn()&&$p->query('SELECT COUNT(*) FROM project_contractors')->fetchColumn()==1,'project delete cascades, cancels appointments, keeps contractor list');
pj((int)$p->query("SELECT COUNT(*) FROM app_audit WHERE action LIKE 'project_%'")->fetchColumn()>=5,'audited');
echo "Bauprojekte PASS ($n checks; migration 400→410, trades, contractors, events→week, costs, defects, warranty).\n";
