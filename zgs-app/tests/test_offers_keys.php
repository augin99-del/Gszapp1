<?php
declare(strict_types=1);
// Schema 460: Angebote (aus Anfrage, Nachfassen, Annahme → Kunde/Objekt/Vertrag) und Schlüsselverwaltung. Synthetische Daten.
set_error_handler(static function($s,$m,$f,$l){if(error_reporting()&$s)throw new ErrorException($m,0,$s,$f,$l);return false;});
require dirname(__DIR__).'/private/install_core.php';require dirname(__DIR__).'/private/web_update.php';require_once dirname(__DIR__).'/private/offers.php';require_once dirname(__DIR__).'/private/keys.php';
$dsn=getenv('GSZ_TEST_DSN')?:'';if(!preg_match('/^mysql:unix_socket=[^;]+;dbname=gsz_test_offerkeys_[a-z0-9_]+$/D',$dsn))throw new RuntimeException('Empty local test DB required.');
$p=new PDO($dsn,'root','',[PDO::ATTR_ERRMODE=>PDO::ERRMODE_EXCEPTION,PDO::ATTR_DEFAULT_FETCH_MODE=>PDO::FETCH_ASSOC,PDO::ATTR_EMULATE_PREPARES=>false]);if($p->query('SHOW TABLES')->fetchAll())throw new RuntimeException('Empty DB required.');
$n=0;function ok(bool $v,string $s):void{global $n;$n++;if(!$v)throw new RuntimeException($s);}
function bad(callable $f,string $s):void{try{$f();ok(false,$s);}catch(RuntimeException|InvalidArgumentException $e){ok(true,$s);}}
$r=gszInstallFresh($p);ok($r['schema']==='460'&&$r['tables']===87&&gszOffersReady($p)&&gszKeysReady($p)&&gszSchemaCurrent($p),'fresh install 460, 87 tables');
$p->exec("INSERT INTO admins(email,password_hash) VALUES('owner@example.invalid','x')");$a=(int)$p->lastInsertId();
gszSettingSet($p,'company_address',"Teststraße 1\n33330 Gütersloh");
// ---------- Angebote ----------
$p->exec("INSERT INTO leads(source,service,object_type,name,company,phone,email,place,message) VALUES('website','Treppenhausreinigung','Mehrfamilienhaus','Petra Probe','Hausverwaltung Probe GmbH','0170 0000000','hv@example.invalid','Gütersloh','3 Etagen, 1 Eingang')");$lead=(int)$p->lastInsertId();
$id=gszOfferCreate($p,$a,$lead);$o=gszOfferGet($p,$id);
ok($o['client_name']==='Hausverwaltung Probe GmbH'&&$o['client_email']==='hv@example.invalid'&&$o['customer_kind']==='business'&&$o['vat_mode']==='zzgl','offer from lead');
ok(gszOfferItems($o)[0]['t']==='Treppenhausreinigung'&&gszOfferProblems($o)===['Preis'],'service as first item, price missing');
ok(gszLead($p,$lead)['status']==='angebot','lead → angebot');ok(gszOfferCreate($p,$a,$lead)===$id,'no duplicate open offer per lead');
bad(fn()=>gszOfferStatus($p,$a,$id,'versendet'),'cannot send without price');
$in=['client_name'=>'Hausverwaltung Probe GmbH','client_address'=>"Probeweg 1\n33330 Gütersloh",'client_email'=>'hv@example.invalid','client_phone'=>'0170 0000000','object_name'=>'Lindenweg 9','object_address'=>'Lindenweg 9, 33330 Gütersloh','customer_kind'=>'business','vat_mode'=>'zzgl','valid_until'=>'2026-12-31','starts_on'=>'2027-01-01','intro'=>'',
 'items'=>[['t'=>'Treppenhausreinigung – wöchentlich','p'=>'189,00','per'=>'month'],['t'=>'Grundreinigung zum Start','p'=>'240','per'=>'once'],['t'=>'','p'=>'5','per'=>'month'],['t'=>'Winterdienst nach Bedarf','p'=>'','per'=>'visit']],'row_version'=>'0'];
gszOfferSave($p,$a,$id,$in);$o=gszOfferGet($p,$id);ok(count(gszOfferItems($o))===3&&gszOfferTotals($o)===['month'=>18900,'once'=>24000]&&!gszOfferProblems($o),'items parsed, empty row dropped, totals per period');
ok(gszOfferTotalText($o)==='189,00 € monatlich + 240,00 € einmalig','total text');
bad(fn()=>gszOfferSave($p,$a,$id,$in),'stale version');bad(fn()=>gszOfferSave($p,$a,$id,array_merge($in,['row_version'=>'1','client_email'=>'kein-mail'])),'email validated');
bad(fn()=>gszOfferSave($p,$a,$id,array_merge($in,['row_version'=>'1','items'=>[['t'=>'x','p'=>'-5']]])),'negative price');
$pdf=gszOfferPdf($p,$o);ok(str_starts_with($pdf,'%PDF')&&strlen($pdf)>4000,'offer pdf');
$day=new DateTimeImmutable('2026-12-01');gszOfferStatus($p,$a,$id,'versendet',['date'=>'2026-12-01'],$day);$o=gszOfferGet($p,$id);ok($o['status']==='versendet'&&$o['followup_on']==='2026-12-04','sent, follow-up in 3 days');
ok(gszOfferAttention($p,new DateTimeImmutable('2026-12-03'))===[],'no reminder before day 3');$att=gszOfferAttention($p,new DateTimeImmutable('2026-12-04'));ok(count($att)===1&&str_contains($att[0][1],'nachfassen'),'reminder on day 3');
gszOfferStatus($p,$a,$id,'nachgefasst',['note'=>'Rückruf Montag'],new DateTimeImmutable('2026-12-04'));$o=gszOfferGet($p,$id);ok((int)$o['followups']===1&&$o['followup_on']==='2026-12-08'&&str_contains($o['notes'],'Rückruf Montag'),'second reminder on day 7');
gszOfferStatus($p,$a,$id,'nachgefasst',[],new DateTimeImmutable('2026-12-08'));ok(gszOfferGet($p,$id)['followup_on']===null,'no third reminder');
$att=gszOfferAttention($p,new DateTimeImmutable('2027-01-05'));ok(count($att)===1&&str_contains($att[0][1],'abgelaufen'),'expired hint');
$res=gszOfferStatus($p,$a,$id,'angenommen',[],new DateTimeImmutable('2026-12-10'));$o=gszOfferGet($p,$id);
ok($o['status']==='angenommen'&&$res['new_customer']&&$res['new_property']&&$o['contract_id']==$res['contract_id'],'accepted: customer + property + contract');
$c=gszContractGet($p,(int)$res['contract_id']);ok($c['status']==='entwurf'&&(int)$c['price_cents']===18900&&$c['price_period']==='month'&&$c['starts_on']==='2027-01-01'&&str_contains($c['services'],'Grundreinigung zum Start (240,00 € einmalig)')&&str_contains((string)$c['special_terms'],'Grundlage: Angebot A-'),'contract filled from offer');
$prop=$p->query('SELECT * FROM properties WHERE id='.(int)$res['property_id'])->fetch();ok($prop['name']==='Lindenweg 9'&&(int)$prop['customer_id']===(int)$res['customer_id'],'property created');
ok(gszLead($p,$lead)['status']==='auftrag'&&(int)gszLead($p,$lead)['property_id']===(int)$res['property_id'],'lead → auftrag with object');
bad(fn()=>gszOfferStatus($p,$a,$id,'abgelehnt'),'decided cannot change');bad(fn()=>gszOfferSave($p,$a,$id,$in),'decided cannot be edited');
// Angebot für bestehendes Objekt (Verbraucher), abgelehnt
$p->exec("INSERT INTO customers(name,billing_address) VALUES('Erika Probe','Probeweg 2')");$cu=(int)$p->lastInsertId();$p->exec("INSERT INTO properties(customer_id,name,address) VALUES($cu,'Haus Probe','Probeweg 2')");$pp=(int)$p->lastInsertId();
$p->exec("INSERT INTO recurring_jobs(property_id,title,frequency) VALUES($pp,'Gartenpflege','monthly')");
$id2=gszOfferCreate($p,$a,null,$pp);$o2=gszOfferGet($p,$id2);ok($o2['customer_kind']==='consumer'&&$o2['vat_mode']==='inkl'&&gszOfferItems($o2)[0]['t']==='Gartenpflege – monatlich','offer for existing property (consumer)');
gszOfferStatus($p,$a,$id2,'abgelehnt',['note'=>'zu teuer']);ok(gszOfferGet($p,$id2)['status']==='abgelehnt'&&gszOfferGet($p,$id2)['decision_note']==='zu teuer','rejected with reason');
$id3=gszOfferCreate($p,$a);gszOfferDelete($p,$a,$id3);ok(gszOfferGet($p,$id3)===null,'draft deleted');
// Mail-Entwurf mit Anhang
$draft=gszDocMailDraft($p,'hv@example.invalid','Ihr Angebot',gszOfferMailText($p,$o),gszOfferFileName($o),$pdf);
ok((int)$p->query('SELECT COUNT(*) FROM mail_draft_attachments WHERE draft_id='.$draft)->fetchColumn()===1&&str_contains((string)$p->query('SELECT body FROM mail_drafts WHERE id='.$draft)->fetchColumn(),'Angebot A-'),'mail draft with pdf attachment');
bad(fn()=>gszDocMailDraft($p,'kein-mail','x','y','a.pdf',$pdf),'invalid recipient');
// ---------- Schlüssel ----------
$k1=gszKeyAdd($p,$a,['property_id'=>(string)$res['property_id'],'label'=>'Haustür + Keller','copies'=>'2','storage'=>'Schlüsselkasten Fach 1','from'=>'Hausverwaltung Probe GmbH']);$k=gszKeyGet($p,$k1);
ok($k['key_no']==='S-001'&&$k['state']==='da'&&(int)$k['copies']===2&&gszKeyMoves($p,$k1)[0]['action']==='erhalten','key added with number S-001');
$k2=gszKeyAdd($p,$a,['property_id'=>(string)$pp,'label'=>'Gartentor']);ok(gszKeyGet($p,$k2)['key_no']==='S-002','next number');
bad(fn()=>gszKeyAdd($p,$a,['label'=>'x','key_no'=>'S-001']),'duplicate number');bad(fn()=>gszKeyAdd($p,$a,['label'=>'']),'label required');
bad(fn()=>gszKeyAction($p,$a,$k1,'ausgegeben',[]),'person required');bad(fn()=>gszKeyAction($p,$a,$k1,'zurueck'),'cannot return a key that is here');
gszKeyAction($p,$a,$k1,'ausgegeben',['person'=>'Mitarbeiter A']);$k=gszKeyGet($p,$k1);ok($k['state']==='ausgegeben'&&$k['holder']==='Mitarbeiter A'&&$k['holder_since'],'issued');
ok(gszKeyAttention($p)===[]||!str_contains(json_encode(gszKeyAttention($p)),'S-001'),'no hint on day 1');
$att=gszKeyAttention($p,new DateTimeImmutable('+3 days'));ok(str_contains(json_encode($att,JSON_UNESCAPED_UNICODE),'S-001')&&str_contains(json_encode($att,JSON_UNESCAPED_UNICODE),'Mitarbeiter A'),'hint after 2 days');
gszKeyAction($p,$a,$k1,'zurueck');$k=gszKeyGet($p,$k1);ok($k['state']==='da'&&$k['holder']===''&&gszKeyMoves($p,$k1)[0]['person']==='Mitarbeiter A','returned, history keeps person');
gszKeyAction($p,$a,$k2,'verloren',['note'=>'Einsatz 12.12.']);ok(str_contains(json_encode(gszKeyAttention($p),JSON_UNESCAPED_UNICODE),'verloren'),'lost hint');gszKeyAction($p,$a,$k2,'gefunden');ok(gszKeyGet($p,$k2)['state']==='da','found again');
ok(count(gszKeysList($p,'','Haustür'))===1&&count(gszKeysList($p,'unterwegs'))===0&&gszKeyCounts($p)['da']===2,'list/search/counts');
foreach(['erhalten','an_kunden','ausgegeben'] as $kind){$pdf=gszKeyReceiptPdf($p,gszKeysForProperty($p,(int)$res['property_id']),$kind,$kind==='ausgegeben'?'Mitarbeiter A':'');ok(str_starts_with($pdf,'%PDF'),'receipt '.$kind);}
// Vertrag beendet → Schlüssel zurückgeben
$p->exec("UPDATE property_contracts SET status='beendet' WHERE id=".(int)$res['contract_id']);ok(str_contains(json_encode(gszKeyAttention($p),JSON_UNESCAPED_UNICODE),'Schlüssel an den Kunden zurückgeben'),'contract ended → return keys');
gszKeyAction($p,$a,$k1,'an_kunden',['person'=>'Hausverwaltung Probe GmbH']);ok(gszKeyGet($p,$k1)['state']==='zurueckgegeben'&&!str_contains(json_encode(gszKeyAttention($p),JSON_UNESCAPED_UNICODE),'zurückgeben'),'returned to customer, hint gone');
ok(gszKeyAdd($p,$a,['label'=>'Neu','key_no'=>'S-001'])>0,'number free again after return to customer');
ok((int)$p->query("SELECT COUNT(*) FROM app_audit WHERE action LIKE 'offer_%' OR action LIKE 'key_%'")->fetchColumn()>=12,'audited');
echo "Offers+keys PASS ($n checks; synthetic data).\n";
