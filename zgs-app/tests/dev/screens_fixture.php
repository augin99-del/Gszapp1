<?php
declare(strict_types=1);
// Nur lokale QA: synthetische Vorführdaten für Screenshots. Reines SQL, läuft auf Schema 370 UND 380.
// Aufruf: GSZ_TEST_DSN=mysql:unix_socket=…;dbname=gsz_test_… php tests/dev/screens_fixture.php <app-root>
if(PHP_SAPI!=='cli')exit(1);$root=$argv[1]??dirname(__DIR__,2);
$dsn=getenv('GSZ_TEST_DSN')?:'';if(!preg_match('/^mysql:unix_socket=[^;]+;dbname=gsz_test_[a-z0-9_]+$/D',$dsn))throw new RuntimeException('Local test DB required.');
$p=new PDO($dsn,'root','',[PDO::ATTR_ERRMODE=>PDO::ERRMODE_EXCEPTION,PDO::ATTR_DEFAULT_FETCH_MODE=>PDO::FETCH_ASSOC]);if($p->query('SHOW TABLES')->fetchAll())throw new RuntimeException('Empty DB required.');
require $root.'/private/install_core.php';gszInstallFresh($p);
$today=new DateTimeImmutable('today',new DateTimeZone('Europe/Berlin'));$d=fn(int $n)=>$today->modify(($n>=0?'+':'').$n.' days')->format('Y-m-d');
$p->prepare('INSERT INTO admins(email,password_hash) VALUES(?,?)')->execute(['owner@example.invalid',password_hash('Local-Test-Only-2026!',PASSWORD_DEFAULT)]);
$p->exec("INSERT INTO customers(name,email) VALUES('Hausverwaltung Beispiel','hv@example.invalid'),('WEG Musterhof','weg@example.invalid')");
$objects=[['Heiligenhäuschenweg 2–4','Heiligenhäuschenweg 2, 33378 Rheda-Wiedenbrück'],['Fröbelstraße 19','Fröbelstraße 19, 33330 Gütersloh'],['Fontänenstraße 29–31','Fontänenstraße 29, 33378 Rheda-Wiedenbrück'],['Auf der Warte 8–10','Auf der Warte 8, 33378 Rheda-Wiedenbrück'],['Berliner Straße 45','Berliner Straße 45, 33330 Gütersloh'],['Diekstraße 3','Diekstraße 3, 33330 Gütersloh']];
foreach($objects as $i=>[$n,$a])$p->prepare('INSERT INTO properties(customer_id,name,address) VALUES(?,?,?)')->execute([$i%2+1,$n,$a]);
// Regelmäßige Arbeit heute/diese Woche
$task=$p->prepare('INSERT INTO tasks(property_id,title,due_date,duration_minutes,done,start_time,sort_order) VALUES(?,?,?,?,?,?,?)');
$task->execute([2,'Treppenhaus reinigen',$d(0),75,0,null,0]);$task->execute([4,'Treppenhaus reinigen',$d(0),75,1,null,0]);$task->execute([5,'Außenanlage pflegen',$d(0),90,0,null,0]);
$task->execute([6,'Fenster reinigen',$d(1),90,0,null,0]);$task->execute([2,'Außenanlage pflegen',$d(2),90,0,null,0]);$task->execute([4,'Tiefgarage reinigen',$d(3),90,0,null,0]);$task->execute([5,'Treppenhaus reinigen',$d(-1),75,0,null,0]);
$p->prepare('INSERT INTO planning_appointments(day,start_time,end_time,title,address) VALUES(?,?,?,?,?)')->execute([$d(0),'14:00:00','14:30:00','Besichtigung WEG Musterhof','']);
// Abfall: mehrere Tonnenarten am selben Tag → in 370 je Tonnenart eine eigene Aufgabe (so wie 370 sie anlegte)
$p->prepare("INSERT INTO waste_sources(property_id,name,address,source_date,valid_until,checked_at) VALUES(1,'Abfallkalender Kreis','Heiligenhäuschenweg 2, 33378 Rheda-Wiedenbrück',?,?,NOW()),(2,'Abfallkalender Stadt','Fröbelstraße 19, 33330 Gütersloh',?,?,NOW())")->execute([$d(-30),$d(300),$d(-30),$d(300)]);
$ev=$p->prepare("INSERT INTO waste_events(property_id,collection_date,fraction,address,status,event_hash) VALUES(?,?,?,?,'geprueft',?)");$or=$p->prepare('INSERT INTO waste_origins(event_id,source_id,external_id,original_title,original_start,record_hash) VALUES(?,?,?,?,?,?)');
$link=$p->prepare('INSERT INTO waste_task_links(event_id,direction,task_id) VALUES(?,?,?)');
foreach([[1,$d(1),['Restmüll','Bio']],[2,$d(2),['Papier']],[1,$d(4),['Gelb']]] as [$prop,$coll,$fractions])foreach($fractions as $f){$ev->execute([$prop,$coll,$f,'x',hash('sha256',$prop.$coll.$f)]);$e=(int)$p->lastInsertId();$or->execute([$e,$prop,'uid-'.$e,$f,$coll,hash('sha256','o'.$e)]);
 foreach(['raus'=>-1,'rein'=>0] as $dir=>$off){$due=(new DateTimeImmutable($coll))->modify(($off>=0?'+':'').$off.' days')->format('Y-m-d');$p->prepare('INSERT INTO tasks(property_id,title,due_date,duration_minutes,waste_key,window_start,window_end) VALUES(?,?,?,15,?,?,?)')->execute([$prop,'Tonnen '.$dir.'stellen: '.$f,$due,'waste:'.$e.':'.$dir,$due,$due]);$link->execute([$e,$dir,(int)$p->lastInsertId()]);}}
// Mail
$p->exec("INSERT INTO mail_folders(name,uidvalidity,last_uid,last_sync) VALUES('INBOX',1,6,UTC_TIMESTAMP())");$m=$p->prepare('INSERT INTO mail_messages(folder_id,uidvalidity,remote_uid,sender,recipients,subject,body,sent_at,seen) VALUES(1,1,?,?,?,?,?,UTC_TIMESTAMP()-INTERVAL ? HOUR,?)');
foreach([['Hausverwaltung Beispiel <hv@example.invalid>','Treppenhausreinigung Dezember','Synthetische Nachricht.',2,0],['WEG Musterhof <weg@example.invalid>','Termin Besichtigung','Synthetische Nachricht.',5,0],['Stadtwerke <info@example.invalid>','Abschlag Wasser','Synthetische Nachricht.',30,1],['Mieter Lindenweg <a@example.invalid>','Heizung OG','Synthetische Nachricht.',50,1]] as $i=>[$from,$sub,$body,$h,$seen])$m->execute([$i+1,$from,'owner@example.invalid',$sub,$body,$h,$seen]);
// Eigene Immobilien: zwei Häuser, je ein Konto
$p->exec("INSERT INTO estate_buildings(name,address) VALUES('Haus Lindenweg','Lindenweg 7, 33330 Gütersloh'),('Haus Bergstraße','Bergstraße 12, 33378 Rheda-Wiedenbrück')");
$p->exec("INSERT INTO estate_units(building_id,label,area_units) VALUES(1,'EG',6200),(1,'OG',6200),(1,'DG',4100),(2,'Whg 1',7300),(2,'Whg 2',5400)");
$p->exec("INSERT INTO estate_tenants(name) VALUES('Anna Beispiel'),('Bernd Muster'),('Carla Probe'),('Dieter Test'),('Eva Neu')");
$p->exec("INSERT INTO estate_leases(unit_id,tenant_id,starts_on,due_day,operating_mode,heating_mode) VALUES(1,1,'2024-01-01',3,'advance','advance'),(2,2,'2024-01-01',3,'advance','advance'),(3,3,'2024-01-01',3,'advance','advance'),(4,4,'2024-01-01',3,'advance','advance'),(5,5,'2024-01-01',3,'advance','advance')");
$p->exec("INSERT INTO estate_terms(lease_id,valid_from,rent_cents,operating_cents,heating_cents,created_by) VALUES(1,'2024-01-01',52000,11000,6000,1),(2,'2024-01-01',61000,12000,6500,1),(3,'2024-01-01',41000,8000,4500,1),(4,'2024-01-01',72000,15000,7000,1),(5,'2024-01-01',55000,11000,5500,1)");
$month=$today->format('Y-m-01');$due=$today->format('Y-m-03');$c=$p->prepare('INSERT INTO estate_rent_charges(lease_id,month,due_on,rent_cents,operating_cents,heating_cents,terms_hash,created_by) VALUES(?,?,?,?,?,?,?,1)');
foreach([[1,52000,11000,6000],[2,61000,12000,6500],[3,41000,8000,4500],[4,72000,15000,7000],[5,55000,11000,5500]] as [$l,$r,$o,$h])$c->execute([$l,$month,$due,$r,$o,$h,str_repeat('0',64)]);
$p->exec("UPDATE estate_bank_accounts SET alias='Volksbank Lindenweg' WHERE id=1");$p->exec("UPDATE estate_bank_accounts SET alias='Volksbank Bergstraße' WHERE id=2");
$cols=$p->query('SHOW COLUMNS FROM estate_bank_accounts')->fetchAll(PDO::FETCH_COLUMN);if(in_array('building_id',$cols,true))$p->exec('UPDATE estate_bank_accounts SET building_id=id');
$t=$p->prepare('INSERT INTO estate_bank_transactions(account_id,source_key,record_hash,external_id,booked_on,value_on,amount_cents,currency,purpose,counterparty) VALUES(?,?,?,?,?,?,?,?,?,?)');
$tx=[[1,$today->format('Y-m-02'),69000,'Miete EG','Anna Beispiel'],[1,$today->format('Y-m-03'),79500,'Miete '.$today->format('m/Y'),'B. Muster'],[1,$today->format('Y-m-04'),30000,'Miete','Carla Probe'],[1,$today->format('Y-m-05'),-31850,'Grundsteuer','Stadtkasse'],[2,$today->format('Y-m-02'),94000,'Miete Whg 1','Dieter Test'],[2,$today->format('Y-m-06'),-8900,'Abschlag Wasser','Stadtwerke']];
foreach($tx as $i=>[$a,$day,$amt,$pur,$cp]){$day=min($day,$today->format('Y-m-d'));$t->execute([$a,hash('sha256','fx'.$i),hash('sha256','r'.$i),'fx'.$i,$day,$day,$amt,'EUR',$pur,$cp]);}
// Eine bereits zugeordnete Miete (vollständig) als Beispiel
$p->exec("INSERT INTO estate_payments(bank_transaction_id,paid_on,amount_cents,description,request_key,created_by) SELECT id,booked_on,amount_cents,'Miete Anna Beispiel','".str_repeat('a',64)."',1 FROM estate_bank_transactions WHERE external_id='fx0'");
$pay=(int)$p->lastInsertId();foreach([['rent',52000],['operating',11000],['heating',6000]] as $k=>[$comp,$amt])$p->prepare('INSERT INTO estate_allocations(payment_id,lease_id,charge_id,component,amount_cents,allocation_from,allocation_to,request_key,created_by) VALUES(?,?,?,?,?,?,?,?,1)')->execute([$pay,1,1,$comp,$amt,$comp==='rent'?null:$month,$comp==='rent'?null:$today->format('Y-m-t'),hash('sha256','fa'.$k)]);
echo "Screen fixture ready.\n";
