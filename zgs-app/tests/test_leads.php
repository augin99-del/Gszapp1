<?php
declare(strict_types=1);
// Schema 420: Anfragen aus Formular-Mails, Bewertung per WhatsApp, Notdienst-Status, cookiefreie Website-Zahlen. Synthetische Daten.
set_error_handler(static function($s,$m,$f,$l){if(error_reporting()&$s)throw new ErrorException($m,0,$s,$f,$l);return false;});
require dirname(__DIR__).'/private/install_core.php';require dirname(__DIR__).'/private/web_update.php';require_once dirname(__DIR__).'/private/leads.php';
$dsn=getenv('GSZ_TEST_DSN')?:'';if(!preg_match('/^mysql:unix_socket=[^;]+;dbname=gsz_test_leads_[a-z0-9_]+$/D',$dsn))throw new RuntimeException('Empty local test DB required.');
$p=new PDO($dsn,'root','',[PDO::ATTR_ERRMODE=>PDO::ERRMODE_EXCEPTION,PDO::ATTR_DEFAULT_FETCH_MODE=>PDO::FETCH_ASSOC,PDO::ATTR_EMULATE_PREPARES=>false]);if($p->query('SHOW TABLES')->fetchAll())throw new RuntimeException('Empty DB required.');
$n=0;function ld(bool $v,string $s):void{global $n;$n++;if(!$v)throw new RuntimeException($s);}
function ldThrows(callable $f,string $s):void{try{$f();ld(false,$s);}catch(RuntimeException $e){ld(true,$s);}}
gszInstallFresh($p);ld(gszSchemaCurrent($p)&&gszLeadsReady($p),'fresh install has 420');$p->exec("INSERT INTO admins(email,password_hash) VALUES('owner@example.invalid','x')");
// Formular-Mail genau im Format von api/contact.php
$body="Neue Anfrage über gebaeudeservice-zentrum.de\n\nObjektart: Wohnanlage / MFH\nLeistung: Treppenhausreinigung\nUnternehmen / Verwaltung: Hausverwaltung Beispiel\nName: Erika Muster\nTelefon: 0176 0000000\nE-Mail: erika@example.invalid\nPLZ / Ort: 00000 Musterstadt\nNachricht: 12 Parteien, 3 Etagen.\nBitte wöchentlich.\nQuellseite: /kontakt/\nSeiten-URL: https://gebaeudeservice-zentrum.de/kontakt/\n\n---\nAutomatisch über das Kontaktformular übermittelt.";
$f=gszParseFormMail($body);ld($f['name']==='Erika Muster'&&$f['phone']==='0176 0000000'&&$f['service']==='Treppenhausreinigung'&&$f['company']==='Hausverwaltung Beispiel'&&$f['place']==='00000 Musterstadt'&&$f['message']==="12 Parteien, 3 Etagen.\nBitte wöchentlich."&&$f['source_page']==='/kontakt/'&&$f['object_type']==='Wohnanlage / MFH','form mail parsed incl. multi-line message');
ld(gszParseFormMail("Hallo,\nName: x")===null,'ordinary mails ignored');
$p->exec("INSERT INTO mail_folders(name) VALUES('INBOX')");$fid=(int)$p->lastInsertId();$ins=$p->prepare('INSERT INTO mail_messages(folder_id,uidvalidity,remote_uid,message_id,sender,recipients,subject,sent_at,body) VALUES(?,?,?,?,?,?,?,NOW(),?)');
$ins->execute([$fid,1,1,'<a@example.invalid>','info@example.invalid','info@example.invalid','Neue Website-Anfrage – Treppenhausreinigung',$body]);
$ins->execute([$fid,1,2,'<b@example.invalid>','x@example.invalid','info@example.invalid','Neue Website-Anfrage – Hallo','Spam ohne Formularinhalt']);
$ins->execute([$fid,1,3,'<c@example.invalid>','x@example.invalid','info@example.invalid','Rechnung','Neue Anfrage über gebaeudeservice-zentrum.de\nName: Nicht übernehmen']);
ld(gszLeadsFromMail($p)===1&&gszLeadsFromMail($p)===0,'one lead from the form mail, exactly once');
$l=$p->query('SELECT * FROM leads')->fetch();ld($l['source']==='website'&&$l['status']==='neu'&&$l['email']==='erika@example.invalid'&&gszLeadCounts($p)['neu']===1,'lead fields');
// Status, Aufgabe, Notiz
gszLeadStatus($p,1,(int)$l['id'],'angebot');ldThrows(fn()=>gszLeadStatus($p,1,(int)$l['id'],'quatsch'),'bad status');
$t=gszLeadToTask($p,1,(int)$l['id']);ld($t>0&&gszLeadToTask($p,1,(int)$l['id'])===$t&&str_contains((string)$p->query("SELECT notes FROM tasks WHERE id=$t")->fetchColumn(),'0176 0000000'),'task for today once, with phone');
gszLeadNote($p,(int)$l['id'],'Besichtigung Do');ld(gszLead($p,(int)$l['id'])['note']==='Besichtigung Do','note');
$m=gszLeadSave($p,1,['source'=>'telefon','name'=>'Max Beispiel','phone'=>'+49 176 1111111','service'=>'Schlüsseldienst']);ldThrows(fn()=>gszLeadSave($p,1,['source'=>'telefon']),'empty lead refused');ldThrows(fn()=>gszLeadSave($p,1,['name'=>'x','email'=>'kaputt']),'bad email refused');
ld(count(gszLeads($p))===2&&count(gszLeads($p,'auftrag'))===0,'open list');
// Bewertung
ld(gszWaNumber('0176 1234567')==='491761234567'&&gszWaNumber('+49 (176) 123-4567')==='491761234567'&&gszWaNumber('0049 176 1234567')==='491761234567'&&gszWaNumber('abc')==='','phone → wa.me digits');
$link=gszReviewLink($p,'0176 1234567','Max Beispiel');ld(str_starts_with($link,'https://wa.me/491761234567?text=')&&str_contains(urldecode($link),'Hallo Max,')&&str_contains(urldecode($link),'https://share.google/'),'review link with name and Google link');
ld(str_starts_with(gszReviewLink($p,'',''),'https://wa.me/?text='),'without number: choose contact in WhatsApp');
ldThrows(fn()=>gszSettingsSave($p,1,['review_url'=>'javascript:alert(1)','website_origin'=>'https://example.invalid']),'unsafe review link refused');
gszSettingsSave($p,1,['review_url'=>'https://g.page/r/synthetic/review','website_origin'=>'https://example.invalid','owner_name'=>'A. Inhaber']);ld(str_contains(gszReviewText($p),'A. Inhaber')&&str_contains(gszReviewText($p),'https://g.page/r/synthetic/review'),'settings saved and used');
// Notdienst-Status
$s=gszSosStatus($p);ld($s['status']==='frei'&&str_contains($s['text'],'30 Min'),'default free');
gszSosSet($p,1,'einsatz',40,2);$s=gszSosStatus($p,time());ld($s['status']==='einsatz'&&str_contains($s['text'],'40 Min'),'busy with callback time');
ld(gszSosStatus($p,time()+3*3600)['status']==='frei','busy expires automatically');ldThrows(fn()=>gszSosSet($p,1,'urlaub'),'bad status refused');
// Website-Zahlen
ld(gszWebCount($p,'/','view')&&gszWebCount($p,'/standorte/verl/','call')&&gszWebCount($p,'/standorte/verl/','call')&&gszWebCount($p,'/kontakt/index.html','form'),'events counted');
ld(!gszWebCount($p,'/<script>','view')&&!gszWebCount($p,'https://evil.example/','view')&&!gszWebCount($p,'/','hack'),'bad page/event refused');
$st=gszWebStats($p,30);ld($st['totals']['view']===1&&$st['totals']['call']===2&&$st['totals']['form']===1&&$st['pages'][0]['page']==='/standorte/verl/'&&(int)$st['pages'][0]['calls']===2,'stats per page');
ld(!$p->query("SELECT COUNT(*) FROM information_schema.COLUMNS WHERE TABLE_SCHEMA=DATABASE() AND TABLE_NAME='web_events' AND COLUMN_NAME REGEXP 'ip|agent|session|user'")->fetchColumn(),'no personal columns in web_events');
echo "Anfragen PASS ($n checks; form mails, status, review via WhatsApp, emergency status, cookie-free counts).\n";
