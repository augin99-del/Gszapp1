<?php
declare(strict_types=1);
// Teil B: Mietabgleich der zwei Volksbank-Konten. Synthetische Daten, synthetische IBANs mit gültiger Prüfziffer.
// KEIN Test mit echten Bankdaten: Das CSV-Layout ist dem öffentlich beschriebenen VR-Banking-Export nachgebildet.
set_error_handler(static function($s,$m,$f,$l){if(error_reporting()&$s)throw new ErrorException($m,0,$s,$f,$l);return false;});
require __DIR__.'/fixtures_final.php';require dirname(__DIR__).'/private/install_core.php';require dirname(__DIR__).'/private/estate_rent_match.php';
$dsn=getenv('GSZ_TEST_DSN')?:'';if(!preg_match('/^mysql:unix_socket=[^;]+;dbname=gsz_test_estate_[a-z0-9_]+$/D',$dsn))throw new RuntimeException('Empty local estate test DB required.');
$p=new PDO($dsn,'root','',[PDO::ATTR_ERRMODE=>PDO::ERRMODE_EXCEPTION,PDO::ATTR_DEFAULT_FETCH_MODE=>PDO::FETCH_ASSOC,PDO::ATTR_EMULATE_PREPARES=>false]);if($p->query('SHOW TABLES')->fetchAll())throw new RuntimeException('Fresh schema only.');gszInstallFresh($p);
$n=0;function rm(bool $v,string $s):void{global $n;$n++;if(!$v)throw new RuntimeException($s);}function rb(callable $f,string $s):void{$x=false;try{$f();}catch(Throwable $e){$x=true;}rm($x,$s);}
$p->exec("INSERT INTO admins(email,password_hash) VALUES('owner@example.invalid','synthetic')");
$p->exec("INSERT INTO estate_buildings(name,address) VALUES('Haus Lindenweg','Synthetic 1'),('Haus Bergstraße','Synthetic 2')");
$p->exec("INSERT INTO estate_units(building_id,label,area_units) VALUES(1,'EG',6000),(1,'OG',6000),(1,'DG',4000),(2,'Whg 1',7000)");
$p->exec("INSERT INTO estate_tenants(name) VALUES('Anna Beispiel'),('Bernd Muster'),('Carla Probe'),('Dieter Test')");
$month='2026-11-01';$p->exec("INSERT INTO estate_leases(unit_id,tenant_id,starts_on,due_day,operating_mode,heating_mode) VALUES(1,1,'2025-01-01',3,'advance','advance'),(2,2,'2025-01-01',3,'advance','advance'),(3,3,'2025-01-01',3,'advance','advance'),(4,4,'2025-01-01',3,'advance','advance')");
$p->exec("INSERT INTO estate_terms(lease_id,valid_from,rent_cents,operating_cents,heating_cents,created_by) VALUES(1,'2025-01-01',50000,10000,5000,1),(2,'2025-01-01',60000,12000,6000,1),(3,'2025-01-01',40000,8000,4000,1),(4,'2025-01-01',70000,15000,5000,1)");
$ibanHouse1=finalIban(11);$ibanHouse2=finalIban(12);$ibanAnna=finalIban(21);
// --- 1. Parser: Vorspann, Windows-1252, deutsches Datum/Betrag, S/H, identische Zeilen.
$hdr="Bezeichnung Auftragskonto;IBAN Auftragskonto;BIC Auftragskonto;Bankname Auftragskonto;Buchungstag;Valutadatum;Name Zahlungsbeteiligter;IBAN Zahlungsbeteiligter;BIC (SWIFT-Code) Zahlungsbeteiligter;Buchungstext;Verwendungszweck;Betrag;Waehrung;Saldo nach Buchung;Bemerkung;Kategorie;Steuerrelevant;Glaeubiger ID;Mandatsreferenz";
$line=fn(string $iban,string $day,string $name,string $cp,string $text,string $purpose,string $amount,string $saldo)=>"Vermieterkonto;$iban;GENODEM1XXX;Volksbank;$day;$day;$name;$cp;;$text;$purpose;$amount;EUR;$saldo;;;;;";
$rowsA=[$line($ibanHouse1,'02.11.2026','Anna Beispiel',$ibanAnna,'Gutschrift','Miete November EG','650,00','10.650,00'),$line($ibanHouse1,'03.11.2026','B. Muster','','Dauerauftrag','Miete 11/2026','780,00','11.430,00'),$line($ibanHouse1,'05.11.2026','Stadt Synthetic','','Lastschrift','Grundsteuer 4. Quartal','-123,45','11.306,55'),$line($ibanHouse1,'06.11.2026','Sparschwein','','Gutschrift','Bargeld','10,00',''),$line($ibanHouse1,'06.11.2026','Sparschwein','','Gutschrift','Bargeld','10,00','')];
$csvA="Umsatzanzeige;Synthetic\n\n".$hdr."\n".implode("\n",$rowsA)."\n";$parsed=bankParseVolksbankCsv(mb_convert_encoding($csvA,'Windows-1252','UTF-8'));
rm(count($parsed['rows'])===5&&$parsed['own_iban']===$ibanHouse1,'header after preamble and Windows-1252 file parsed');rm($parsed['rows'][2]['amount_cents']===-12345&&$parsed['rows'][0]['booked_on']==='2026-11-02','German date and negative amount');
rm($parsed['rows'][3]['external_id']!==$parsed['rows'][4]['external_id']&&str_ends_with($parsed['rows'][4]['external_id'],'-2'),'two identical same-day rows stay two distinct movements');rm($parsed['rows'][0]['counterparty_iban_hash']===hash('sha256',$ibanAnna)&&!str_contains(json_encode($parsed),$ibanAnna),'counterparty IBAN only as hash');
$sh=bankParseVolksbankCsv("Buchungstag;Valuta;Auftraggeber/Empfänger;Vorgang/Verwendungszweck;Umsatz;S/H\n01.11.26;01.11.26;Versicherung AG;Beitrag;99,90;S\n");rm($sh['rows'][0]['amount_cents']===-9990&&$sh['rows'][0]['booked_on']==='2026-11-01','older layout with S/H and two-digit year');
rb(fn()=>bankParseVolksbankCsv("Datum;Wert\n1;2\n"),'unknown CSV refused with clear message');rb(fn()=>bankParseVolksbankCsv($hdr."\n".$line($ibanHouse1,'02.11.2026','A','','x','y','1,00','')."\n".$line($ibanHouse2,'02.11.2026','A','','x','y','2,00','')),'file with two accounts refused');
// --- 2. Konto = Haus; Upload mit Überschneidung zählt nichts doppelt.
estateLinkAccount($p,1,1,1);estateLinkAccount($p,1,2,2);rb(fn()=>estateLinkAccount($p,1,2,1),'a house belongs to one account only');
$t=estateStatementPreview($p,1,1,$csvA);rm((int)$p->query('SELECT COUNT(*) FROM estate_bank_transactions')->fetchColumn()===0,'preview stores nothing');rm(estateBankApply($p,1,$t)===5,'first upload imports 5 movements');
$rowsB=[$rowsA[2],$rowsA[3],$rowsA[4],$line($ibanHouse1,'10.11.2026','Carla Probe','','Gutschrift','Miete','300,00','11.606,55'),$line($ibanHouse1,'20.11.2026','Wasserwerk Synthetic','','Lastschrift','Abschlag Wasser','-80,00','11.526,55')];
$csvB=$hdr."\n".implode("\n",$rowsB)."\n";$t=estateStatementPreview($p,1,1,$csvB);$prev=json_decode($p->query("SELECT payload FROM estate_proposals WHERE token=".$p->quote($t))->fetchColumn(),true);rm($prev['duplicates']===3&&!$prev['possible_duplicates'],'overlapping export recognises the 3 known rows');
rm(estateBankApply($p,1,$t)===2&&(int)$p->query('SELECT COUNT(*) FROM estate_bank_transactions')->fetchColumn()===7,'overlap: only 2 new rows, nothing counted twice');
$t=estateStatementPreview($p,1,1,$csvA."\n");rm(estateBankApply($p,1,$t)===0,'re-upload of an earlier export adds nothing');
rm((int)$p->query('SELECT COUNT(*) FROM estate_bank_uploads WHERE account_id=1')->fetchColumn()===3&&$p->query('SELECT MAX(period_to) FROM estate_bank_uploads')->fetchColumn()==='2026-11-20','every upload logged with its period');
rb(fn()=>estateStatementPreview($p,1,2,$csvA),'export of house 1 cannot be uploaded into account 2');
$camt=finalCamt($ibanHouse2,'camt-synthetic-1');$t=estateStatementPreview($p,1,2,$camt);rm(estateBankApply($p,1,$t)===1,'CAMT detected automatically, IBAN taken from the file');
rm((int)$p->query('SELECT COUNT(*) FROM estate_payments')->fetchColumn()===0&&(int)$p->query('SELECT COUNT(*) FROM estate_expenses')->fetchColumn()===0,'import never books rent or costs silently');
// --- 3. Sollmieten + Monatsübersicht.
rm(estateMonthOverview($p,1,$month)[0]['status']==='no_charge','without charges the overview says so');rm(estateCreateMonthCharges($p,1,1,$month)===3&&estateCreateMonthCharges($p,1,1,$month)===0,'monthly charges created once per house');
estateSaveTenantHints($p,1,3,"Carla und Max Probe\nC. Probe",'');
$props=estateRentProposals($p,1);$by=[];foreach($props as $x)$by[$x['transaction']['counterparty']][]=$x;
rm($by['Anna Beispiel'][0]['sure']&&(int)$by['Anna Beispiel'][0]['proposal']['charge']['lease_id']===1,'full name + exact amount → sure proposal');rm((int)$by['B. Muster'][0]['proposal']['charge']['lease_id']===2,'surname + exact amount + month in text');
rm((int)$by['Carla Probe'][0]['proposal']['charge']['lease_id']===3&&!$by['Carla Probe'][0]['sure'],'partial amount proposed but not marked sure');rm(empty($by['Sparschwein'][0]['proposal']),'unrelated income gets no proposal');
foreach(['Anna Beispiel','B. Muster','Carla Probe'] as $who){$x=$by[$who][0];estateAssignRent($p,1,(int)$x['transaction']['id'],(int)$x['proposal']['charge']['id']);}
$ov=array_column(estateMonthOverview($p,1,$month),null,'tenant_name');rm($ov['Anna Beispiel']['status']==='complete'&&$ov['Anna Beispiel']['payments'][0]['paid_on']==='2026-11-02'&&(int)$ov['Anna Beispiel']['paid_cents']===65000,'complete with amount and date');
rm($ov['Carla Probe']['status']==='partial'&&(int)$ov['Carla Probe']['open_cents']===22000,'partial with open amount');
$alloc=$p->query("SELECT component,amount_cents FROM estate_allocations WHERE lease_id=1 ORDER BY id")->fetchAll(PDO::FETCH_KEY_PAIR);rm($alloc===['rent'=>50000,'operating'=>10000,'heating'=>5000],'payment split over rent, operating and heating advance');
$before=$p->query('SELECT * FROM estate_allocations ORDER BY id')->fetchAll();rm(count($before)===7,'3 + 3 + 1 component allocations');$x=$by['Anna Beispiel'][0];estateAssignRent($p,1,(int)$x['transaction']['id'],(int)$x['proposal']['charge']['id']);rm($p->query('SELECT * FROM estate_allocations ORDER BY id')->fetchAll()===$before,'repeated tap is idempotent');
rb(fn()=>estateAssignRent($p,1,(int)$by['Sparschwein'][0]['transaction']['id'],(int)estateMonthOverview($p,2,$month)[0]['charge_id']??0),'house-1 money cannot pay a house-2 charge');
$tenantIban=$p->query('SELECT iban_hash FROM estate_tenants WHERE id=1')->fetchColumn();rm($tenantIban===null,'no IBAN stored unless entered');estateSaveTenantHints($p,1,1,'',$ibanAnna);rm($p->query('SELECT iban_hash FROM estate_tenants WHERE id=1')->fetchColumn()===hash('sha256',$ibanAnna),'tenant IBAN stored as hash only');
// --- 4. Kosten: Vorschlag, Bestätigung durch Inhaber.
$ex=array_column(estateExpenseProposals($p,1),null,'category');rm(isset($ex['Grundsteuer'],$ex['Wasser / Abwasser'])&&$ex['Grundsteuer']['building_id']===1,'cost proposals per house with category');
rm((int)$p->query('SELECT COUNT(*) FROM estate_expenses')->fetchColumn()===0,'no cost booked before confirmation');estateConfirmExpense($p,1,(int)$ex['Grundsteuer']['transaction']['id'],'Grundsteuer');rm((int)$p->query('SELECT building_id FROM estate_expenses')->fetchColumn()===1&&count(estateExpenseProposals($p,1))===1,'confirmed cost assigned to the house of the account');
// --- 5. Startseite: Hinweis ab dem 10., solange kein Export im laufenden Monat.
$p->exec('DELETE FROM estate_bank_uploads');$a9=estateAttention($p,new DateTimeImmutable('2026-11-09'));$a10=estateAttention($p,new DateTimeImmutable('2026-11-10'));rm(!$a9['missing_uploads']&&count($a10['missing_uploads'])===2,'upload reminder only from the 10th, per account');
$p->exec("INSERT INTO estate_bank_uploads(account_id,admin_id,uploaded_on,uploaded_at,file_hash,format,period_from,period_to,rows_total,rows_new,rows_known) VALUES(1,1,'2026-11-10',UTC_TIMESTAMP(),'".str_repeat('a',64)."','volksbank_csv','2026-10-10','2026-11-09',1,1,0)");
$a=estateAttention($p,new DateTimeImmutable('2026-11-12'));rm(count($a['missing_uploads'])===1&&$a['missing_uploads'][0]['label']==='Haus Bergstraße','reminder disappears for the uploaded account');rm($a['missing_rents']===1,'one partial rent counted as needing attention');rm($a['charges_missing']===['Haus Bergstraße'],'house without monthly charges flagged');
echo "Estate rent matching PASS ($n assertions; synthetic data, no real bank export).\n";
