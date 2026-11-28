<?php
declare(strict_types=1);
// Schema 450: Verträge je Objekt – automatischer Entwurf, Laufzeit/Fristen (Unternehmer/Verbraucher), Status, PDF. Synthetische Daten.
set_error_handler(static function($s,$m,$f,$l){if(error_reporting()&$s)throw new ErrorException($m,0,$s,$f,$l);return false;});
require dirname(__DIR__).'/private/install_core.php';require dirname(__DIR__).'/private/web_update.php';require_once dirname(__DIR__).'/private/contracts.php';require_once dirname(__DIR__).'/private/export_core.php';
$dsn=getenv('GSZ_TEST_DSN')?:'';if(!preg_match('/^mysql:unix_socket=[^;]+;dbname=gsz_test_contracts_[a-z0-9_]+$/D',$dsn))throw new RuntimeException('Empty local test DB required.');
$p=new PDO($dsn,'root','',[PDO::ATTR_ERRMODE=>PDO::ERRMODE_EXCEPTION,PDO::ATTR_DEFAULT_FETCH_MODE=>PDO::FETCH_ASSOC,PDO::ATTR_EMULATE_PREPARES=>false]);if($p->query('SHOW TABLES')->fetchAll())throw new RuntimeException('Empty DB required.');
$n=0;function ct(bool $v,string $s):void{global $n;$n++;if(!$v)throw new RuntimeException($s);}
function ctThrows(callable $f,string $s):void{try{$f();ct(false,$s);}catch(RuntimeException|InvalidArgumentException $e){ct(true,$s);}}
$r=gszInstallFresh($p);ct($r['schema']==='460'&&$r['tables']===87&&gszContractsReady($p)&&gszSchemaCurrent($p),'fresh install 450, 84 tables');
$p->exec("INSERT INTO admins(email,password_hash) VALUES('owner@example.invalid','x')");$a=(int)$p->lastInsertId();
$p->exec("INSERT INTO customers(name,email,billing_address) VALUES('Hausverwaltung Beispiel GmbH','hv@example.invalid','Musterweg 1\n33330 Gütersloh'),('Erika Probe','',''),('Max Muster','','')");
$p->exec("INSERT INTO properties(customer_id,name,address) VALUES(1,'Lindenweg 7','Lindenweg 7, 33330 Gütersloh'),(2,'Bergstraße 12','Bergstraße 12, 33378 Rheda-Wiedenbrück'),(NULL,'Ohne Kunde','')");
$p->exec("INSERT INTO recurring_jobs(property_id,title,frequency,notes) VALUES(1,'Treppenhausreinigung','weekly','EG bis DG, Geländer feucht'),(1,'Winterdienst Gehweg','seasonal',''),(1,'Alt','monthly','')");$p->exec("UPDATE recurring_jobs SET active=0 WHERE title='Alt'");
// Automatik
ct(gszContractGuessKind('Hausverwaltung Beispiel GmbH')==='business'&&gszContractGuessKind('WEG Lindenweg 7')==='business'&&gszContractGuessKind('Erika Probe')==='consumer','kind guess');
$id=gszContractAutoDraft($p,$a,1);$c=gszContractGet($p,(int)$id);
ct($c&&$c['status']==='entwurf'&&(int)$c['auto_created']===1&&$c['client_name']==='Hausverwaltung Beispiel GmbH'&&str_contains($c['client_address'],'Gütersloh'),'auto draft from customer');
ct(str_contains($c['services'],'Treppenhausreinigung – wöchentlich (EG bis DG')&&str_contains($c['services'],'Winterdienst Gehweg')&&!str_contains($c['services'],'Alt')&&(int)$c['winter_service']===1,'services from active rules, winter detected');
ct($c['customer_kind']==='business'&&(int)$c['term_months']===12&&(int)$c['notice_months']===3&&(int)$c['renew_months']===12&&$c['vat_mode']==='zzgl'&&$c['starts_on']===(new DateTimeImmutable('first day of next month'))->format('Y-m-d'),'business defaults');
ct(gszContractAutoDraft($p,$a,1)===$id&&(int)$p->query('SELECT COUNT(*) FROM property_contracts')->fetchColumn()===1,'no duplicate draft');
ct(gszContractProblems($c)===['Preis'],'only price missing');
$c2=gszContractGet($p,(int)gszContractAutoDraft($p,$a,2));ct($c2['customer_kind']==='consumer'&&(int)$c2['notice_months']===1&&(int)$c2['renew_months']===0&&$c2['vat_mode']==='inkl','consumer defaults (1 month, inkl. USt)');
gszSettingSet($p,'contract_auto','0');ct(gszContractAutoDraft($p,$a,3)===null,'automatic can be switched off');gszSettingSet($p,'contract_auto','1');
ct(gszContractCreateMissing($p,$a)===1&&gszContractForProperty($p,3)['client_name']==='','create missing drafts');
// Speichern + Verbraucher-Grenzen
ctThrows(fn()=>gszContractSave($p,$a,(int)$id,['client_name'=>'','object_name'=>'x','services'=>'x']),'client required');
ctThrows(fn()=>gszContractSave($p,$a,(int)$id,['client_name'=>'A','object_name'=>'x','services'=>'x','price'=>'abc']),'price validated');
$base=['client_name'=>'Hausverwaltung Beispiel GmbH','client_address'=>"Musterweg 1\n33330 Gütersloh",'object_name'=>'Lindenweg 7','object_address'=>'Lindenweg 7','services'=>"Treppenhausreinigung – wöchentlich\nWinterdienst Gehweg – saisonal",'winter_service'=>'1','price'=>'1.180,50','price_period'=>'month','vat_mode'=>'zzgl','starts_on'=>'2026-01-01','term_months'=>'12','renew_months'=>'12','notice_months'=>'3','customer_kind'=>'business'];
gszContractSave($p,$a,(int)$id,$base+['row_version'=>'0']);$c=gszContractGet($p,(int)$id);ct((int)$c['price_cents']===118050&&!gszContractProblems($c),'saved with price');
ctThrows(fn()=>gszContractSave($p,$a,(int)$id,$base+['row_version'=>'0']),'stale version rejected');
gszContractSave($p,$a,(int)$c2['id'],array_merge($base,['client_name'=>'Erika Probe','customer_kind'=>'consumer','term_months'=>'36','renew_months'=>'12','notice_months'=>'3','vat_mode'=>'zzgl']));$c2=gszContractGet($p,(int)$c2['id']);
ct((int)$c2['term_months']===24&&(int)$c2['renew_months']===0&&(int)$c2['notice_months']===1&&$c2['vat_mode']==='inkl','consumer clamped to § 309 Nr. 9 BGB');
// Laufzeit/Fristen
$per=gszContractPeriod($c,new DateTimeImmutable('2026-05-10'));ct($per['period_end']==='2026-12-31'&&$per['notice_by']==='2026-09-30','business: end and notice in first term');
$per=gszContractPeriod($c,new DateTimeImmutable('2026-10-01'));ct($per['period_end']==='2027-12-31'&&$per['notice_by']==='2027-09-30','business: renewed after missed notice');
$per=gszContractPeriod($c2,new DateTimeImmutable('2026-06-01'));ct($per['period_end']==='2027-12-31'&&$per['notice_by']==='2027-11-30'&&!$per['open_ended'],'consumer: in first term');
$per=gszContractPeriod($c2,new DateTimeImmutable('2028-03-15'));ct($per['open_ended']&&$per['earliest_end']==='2028-04-15','consumer: open ended, 1 month');
// Text + PDF
$txt=json_encode(gszContractSections($p,$c),JSON_UNESCAPED_UNICODE);ct(str_contains($txt,'1.180,50 € monatlich zuzüglich der gesetzlichen Umsatzsteuer')&&str_contains($txt,'Winterdienst')&&str_contains($txt,'Preisanpassung')&&str_contains($txt,'verlängert sich jeweils um 12 Monate')&&str_contains($txt,'Gerichtsstand'),'business text');
$txt2=json_encode(gszContractSections($p,$c2),JSON_UNESCAPED_UNICODE);ct(!str_contains($txt2,'Preisanpassung')&&!str_contains($txt2,'Gerichtsstand')&&str_contains($txt2,'unbestimmte Zeit')&&str_contains($txt2,'einschließlich der gesetzlichen Umsatzsteuer'),'consumer text without B2B clauses');
$pdf=gszContractPdf($p,$c);ct(str_starts_with($pdf,'%PDF')&&strlen($pdf)>5000,'pdf business');$pdf2=gszContractPdf($p,$c2);ct(str_starts_with($pdf2,'%PDF')&&strlen($pdf2)>strlen($pdf)-2000,'pdf consumer with withdrawal annex');
ct(count(gszContractWithdrawal($p))===3,'withdrawal annex sections');
// Status
ctThrows(fn()=>gszContractStatus($p,$a,(int)gszContractForProperty($p,3)['id'],'versendet'),'incomplete draft cannot be sent');
gszContractStatus($p,$a,(int)$id,'versendet',['date'=>'2026-01-02']);ct(gszContractGet($p,(int)$id)['status']==='versendet','sent');
ctThrows(fn()=>gszContractDelete($p,$a,(int)$id),'sent contract not deletable');
gszContractStatus($p,$a,(int)$id,'aktiv',['date'=>'2026-01-05']);$c=gszContractGet($p,(int)$id);ct($c['status']==='aktiv'&&$c['signed_on']==='2026-01-05'&&$c['sent_on']==='2026-01-02','signed');
$att=gszContractAttention($p,new DateTimeImmutable('2026-08-15'));ct(count($att)===1&&str_contains($att[0][1],'Kündigungsfrist bis 30.09.2026'),'notice reminder 60 days before');
ct(gszContractAttention($p,new DateTimeImmutable('2026-05-01'))===[],'no reminder too early');
gszContractStatus($p,$a,(int)$id,'gekuendigt',['date'=>'2026-09-01']);$c=gszContractGet($p,(int)$id);ct($c['status']==='gekuendigt'&&$c['ends_on']==='2026-12-31','cancellation ends at period end');
gszContractStatus($p,$a,(int)$id,'beendet');ct(gszContractGet($p,(int)$id)['status']==='beendet','ended');
// Entwurf neu übernehmen + löschen; Objekt löschen lässt Vertrag bestehen
$d3=(int)gszContractForProperty($p,3)['id'];$p->exec("UPDATE properties SET customer_id=3 WHERE id=3");gszContractRefresh($p,$a,$d3);ct(gszContractGet($p,$d3)['client_name']==='Max Muster','refresh draft from property');
gszContractDelete($p,$a,$d3);ct(gszContractGet($p,$d3)===null,'draft deleted');
$p->exec('DELETE FROM recurring_jobs WHERE property_id=2');$p->exec('DELETE FROM properties WHERE id=2');ct(gszContractGet($p,(int)$c2['id'])['property_id']===null,'contract survives property deletion');
// Firmendaten
gszContractSettingsSave($p,$a,['company'=>'Zentrum Gebäudeservice','company_address'=>"Teststraße 1\n33330 Gütersloh",'company_email'=>'info@example.invalid','contract_term'=>'99','contract_notice'=>'0','contract_vat'=>'none']);
ct(gszSetting($p,'contract_term')==='60'&&gszSetting($p,'contract_notice')==='1'&&gszSetting($p,'contract_vat')==='none'&&gszSetting($p,'contract_auto')==='0','settings clamped');
ct((int)$p->query("SELECT COUNT(*) FROM app_audit WHERE action LIKE 'contract_%'")->fetchColumn()>=8,'audited');
// CSV: negative Zahlen bleiben Zahlen, Formeln nicht
ct(gszCsvCell('-318,50')==='"-318,50"'&&gszCsvCell('=1+1')==='"\'=1+1"'&&gszCsvCell('-1+2')==='"\'-1+2"','csv number vs formula');
echo "Contracts PASS ($n checks; synthetic data, no legal review implied).\n";
