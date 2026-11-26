const {chromium}=require('playwright');const {execFileSync}=require('child_process');
// Schema 430 (Wunsch 25.11.2026): QR-Meldung, Portal, Helfer-App, Schlüsseldienst-Einsätze, Preisrechner, Bewertungskarten. Synthetische Daten.
(async()=>{const base=process.env.GSZ_TEST_URL;if(!base||!/^https:\/\/127\.0\.0\.1:/.test(base))throw Error('Loopback fixture only');let n=0;const ok=(v,s)=>{n++;globalThis.__last=s;if(!v)throw Error(s)};
const q=sql=>JSON.parse(execFileSync('php',['-r',`$p=new PDO(getenv('GSZ_TEST_DSN'),'root','',[PDO::ATTR_ERRMODE=>PDO::ERRMODE_EXCEPTION,PDO::ATTR_DEFAULT_FETCH_MODE=>PDO::FETCH_ASSOC]);$s=$p->query(${JSON.stringify(sql)});echo json_encode($s->columnCount()?$s->fetchAll():[]);`]).toString());
// Testfoto mit erfundenen GPS-Daten im Dateinamen-Bereich: echtes JPEG mit EXIF-Kommentar
const jpg=execFileSync('php',['-r','$i=imagecreatetruecolor(320,240);imagefill($i,0,0,imagecolorallocate($i,200,40,40));ob_start();imagejpeg($i);$j=ob_get_clean();$c="Exif\\0\\0GPS-SECRET-51.9N-8.38E";echo substr($j,0,2)."\\xFF\\xE1".pack("n",strlen($c)+2).$c.substr($j,2);']);
ok(jpg.includes('GPS-SECRET'),'test photo carries metadata');
const pid=q("SELECT id FROM properties ORDER BY id LIMIT 1")[0].id;q(`UPDATE properties SET contact_email='hv@example.invalid' WHERE id=${pid}`);
const b=await chromium.launch({executablePath:'/usr/bin/chromium',args:['--no-sandbox']});const errors=[];
try{const c=await b.newContext({ignoreHTTPSErrors:true,viewport:{width:390,height:844},isMobile:true,hasTouch:true});const p=await c.newPage();p.on('pageerror',e=>errors.push(e.message));
 await p.goto(base+'/index.php');await p.locator('[name=email]').fill('owner@example.invalid');await p.locator('[name=password]').fill('Local-Test-Only-2026!');await p.getByRole('button',{name:/^Anmelden$/}).click();
 // A: QR-Meldung einrichten → Aushang-PDF
 await p.goto(base+`/property.php?id=${pid}`);const sec=p.locator('#meldungen');ok(await sec.count()===1,'property shows the section');
 await Promise.all([p.waitForNavigation(),sec.getByRole('button',{name:'QR-Meldung einrichten'}).tap()]);ok((await p.textContent('main')).includes('Aushang drucken'),'QR set up');
 let r=await c.request.get(base+`/aushang.php?id=${pid}`);ok(r.status()===200&&r.headers()['content-type']==='application/pdf'&&(await r.body()).slice(0,4).toString()==='%PDF','notice PDF');
 const formUrl=await sec.getByRole('link',{name:'Formular ansehen'}).getAttribute('href');ok(/\/melden\.php\?k=[A-Za-z0-9_-]{32}$/.test(formUrl),'secret report link');
 // Mieter: ohne Anmeldung, ohne Cookie, mit Foto
 const tc=await b.newContext({ignoreHTTPSErrors:true,viewport:{width:390,height:844},isMobile:true,hasTouch:true});const t=await tc.newPage();t.on('pageerror',e=>errors.push(e.message));
 const path=new URL(formUrl).pathname+new URL(formUrl).search;r=await t.goto(base+path);ok(r.status()===200&&!r.headers()['set-cookie']&&(r.headers()['x-robots-tag']||'').includes('noindex'),'public form: no cookie, noindex');
 ok((await t.textContent('main')).includes('Was ist im Haus kaputt'),'form visible');
 ok(await t.locator('.pub-logo-img').count()===1&&(await t.locator('label.tile').count())>=16&&await t.locator('a.pub-sos[href^="tel:"]').count()===1,'tile form with logo and emergency call');
 await t.locator('label.tile',{hasText:'Treppenhaus'}).tap();await t.locator('label.tile',{hasText:'Licht defekt'}).tap();ok(await t.locator('#location-1').isChecked()&&await t.locator('input[name=kind][value="Licht defekt"]').isChecked(),'tiles selectable by tap');
 await t.locator('[name=message]').fill('Licht im 2. OG geht nicht.');await t.getByRole('button',{name:'Meldung senden'}).tap();await t.waitForTimeout(300);ok(await t.locator('[name=name]').evaluate(el=>!el.checkValidity())&&(await t.textContent('main')).includes('Was ist im Haus kaputt'),'cannot send without name and phone');await t.locator('[name=name]').fill('Whg. 4 Müller');await t.locator('[name=phone]').fill('0176 1112223');
 await t.locator('[name=photo]').setInputFiles({name:'IMG_0001.jpg',mimeType:'image/jpeg',buffer:jpg});ok(await t.locator('[data-photo-preview]').isVisible(),'photo preview shown');await Promise.all([t.waitForNavigation(),t.getByRole('button',{name:'Meldung senden'}).tap()]);ok(!(await t.textContent('main')).includes('Meldungsnummer'),'confirmation without report number');
 ok((await t.textContent('main')).includes('Ihre Meldung ist angekommen'),'tenant sees confirmation');ok((await tc.cookies()).length===0,'tenant got no cookies');
 r=await t.goto(base+'/melden.php?k='+'A'.repeat(32));ok(r.status()===404,'wrong link → 404');
 // Zentrale: Heute-Hinweis, Foto ohne Metadaten, als To-do übernehmen
 await p.goto(base+'/index.php');ok((await p.locator('.attention').textContent()).includes('1 neue Mieter-Meldung'),'Heute shows the new report');
 await p.locator('.attention a').filter({hasText:'Mieter-Meldung'}).tap();let card=p.locator('.card.defect').filter({hasText:'Licht im 2. OG'});ok((await card.textContent()).includes('Whg. 4 Müller · 0176 1112223'),'report card');
 const img=await card.locator('.defect-photo img').getAttribute('src');const photo=await (await c.request.get(base+'/'+img)).body();ok(photo.slice(0,2).toString('hex')==='ffd8'&&!photo.includes('GPS-SECRET'),'photo stored without metadata');
 r=await tc.request.get(base+'/'+img);ok(r.status()===403,'tenant cannot open the admin photo URL');
 await Promise.all([p.waitForNavigation(),card.getByRole('button',{name:'Als To-do übernehmen'}).tap()]);ok((await p.textContent('main')).includes('Als To-do in die Merkliste übernommen'),'taken over');
 ok(q("SELECT title FROM quick_notes WHERE kind='todo' ORDER BY id DESC LIMIT 1")[0].title==='Treppenhaus: Licht defekt: Licht im 2. OG geht nicht.','to-do in Merkliste');
 // Abhaken und Löschen
 q(`INSERT INTO defect_reports(property_id,location,message) VALUES(${pid},'Keller','Tür klemmt'),(${pid},'Müllplatz','Deckel kaputt')`);await p.goto(base+'/meldungen.php');
 card=p.locator('.card.defect').filter({hasText:'Tür klemmt'});await Promise.all([p.waitForNavigation(),card.getByRole('button',{name:'✓ Erledigt'}).tap()]);ok((await p.textContent('main')).includes('Meldung abgehakt')&&q("SELECT status FROM defect_reports WHERE message='Tür klemmt'")[0].status==='erledigt'&&await p.locator('.card.defect').filter({hasText:'Tür klemmt'}).count()===0,'tick off removes it from the open list');
 card=p.locator('.card.defect').filter({hasText:'Deckel kaputt'});await card.locator('details.move summary',{hasText:'Löschen'}).tap();await Promise.all([p.waitForNavigation(),card.getByRole('button',{name:'Endgültig löschen'}).tap()]);ok((await p.textContent('main')).includes('Meldung gelöscht')&&q("SELECT COUNT(*) n FROM defect_reports WHERE message='Deckel kaputt'")[0].n==0,'delete after confirmation');
 await p.goto(base+'/meldungen.php?filter=erledigt');await Promise.all([p.waitForNavigation(),p.locator('.card.defect').filter({hasText:'Tür klemmt'}).getByRole('button',{name:'Wieder öffnen'}).tap()]);ok(q("SELECT status FROM defect_reports WHERE message='Tür klemmt'")[0].status==='neu','reopen');
 // B: Portal für die Verwaltung
 await p.goto(base+`/property.php?id=${pid}`);await Promise.all([p.waitForNavigation(),p.locator('#meldungen').getByRole('button',{name:'Portal einrichten'}).tap()]);
 const portal=await p.locator('#meldungen [data-copy]').inputValue();ok(/\/portal\.php\?k=[A-Za-z0-9_-]{32}$/.test(portal),'portal link');ok(await p.locator('#meldungen a[href^="compose.php?to=hv%40example.invalid"]').count()===1,'send link by mail');
 q(`INSERT INTO work_logs(property_id,performed_on,description,duration_minutes) VALUES(${pid},CURDATE(),'Glasreinigung Eingang',30)`);
 r=await t.goto(base+new URL(portal).pathname+new URL(portal).search);ok(r.status()===200&&!r.headers()['set-cookie'],'portal public, no cookie');const pm=await t.textContent('main');
 ok(!pm.includes('Glasreinigung')&&pm.includes('Licht im 2. OG')&&pm.includes('In Arbeit')&&!/Termine|Monatsbericht|Erledigte Arbeiten/.test(pm),'portal shows only tenant reports, no work/appointments');ok(await t.locator('.pub-defect-photo img').count()===1,'portal shows the report photo');
 const kk=new URL(portal).searchParams.get('k');const lc=t.locator('article.pub-defect').filter({hasText:'Licht im 2. OG'});await Promise.all([t.waitForNavigation(),lc.getByRole('button',{name:/Erledigt – abhaken/}).tap()]);
 ok((await t.textContent('main')).includes('Erledigt – danke')&&q("SELECT status FROM defect_reports WHERE message LIKE '%Licht im 2. OG%'")[0].status==='erledigt'&&q("SELECT done_at FROM quick_notes WHERE title LIKE 'Treppenhaus: Licht defekt%'")[0].done_at!==null,'property manager ticks off in the portal, to-do done too');
 if(!(await t.locator('.pub-donelist').evaluate(d=>d.open)))await t.locator('.pub-donelist summary').tap();await Promise.all([t.waitForNavigation(),t.locator('article.pub-defect').filter({hasText:'Licht im 2. OG'}).getByRole('button',{name:/wieder öffnen/}).tap()]);ok(q("SELECT status FROM defect_reports WHERE message LIKE '%Licht im 2. OG%'")[0].status==='uebernommen','reopen from the portal');
 const other=q(`SELECT id FROM defect_reports WHERE property_id<>${pid} LIMIT 1`);q(`INSERT INTO defect_reports(property_id,message) SELECT id,'Fremde Meldung' FROM properties WHERE id<>${pid} LIMIT 1`);const fid=q("SELECT id FROM defect_reports WHERE message='Fremde Meldung'")[0].id;
 r=await tc.request.post(base+'/portal.php?k='+kk,{form:{id:String(fid),action:'done'},maxRedirects:0});ok(r.status()===303&&(r.headers()['location']||'').includes('ok=fehler')&&q(`SELECT status FROM defect_reports WHERE id=${fid}`)[0].status==='neu','cannot tick reports of another house');
 r=await tc.request.post(base+'/portal.php?k='+kk,{headers:{Origin:'https://evil.example'},form:{id:String(fid),action:'done'},maxRedirects:0});ok(r.status()===403,'foreign origin rejected');
 const wp=q('SELECT f.id FROM work_log_photos f LIMIT 1');if(wp.length){r=await tc.request.get(base+'/portal_photo.php?k='+kk+'&f='+wp[0].id);ok(r.status()===404,'work photos not reachable via portal');}
 r=await tc.request.get(base+'/portal_photo.php?k='+new URL(portal).searchParams.get('k')+'&d=999999');ok(r.status()===404,'portal cannot fetch foreign photos');
 // C: Helfer-Zugang und Helfer-App
 q("INSERT INTO operations_workers(name,kind) VALUES('Max Helfer','employee')");const wid=q("SELECT MAX(id) id FROM operations_workers")[0].id;
 q(`INSERT INTO tasks(property_id,title,due_date,assigned_worker_id,notes) VALUES(${pid},'Treppenhaus wischen',CURDATE(),${wid},'Bitte auch Keller')`);
 await p.goto(base+`/workers.php?id=${wid}`);const z=p.locator('#zugang');await z.locator('[name=login]').fill('max');await z.locator('[name=password]').fill('Helfer-Test-2026');await Promise.all([p.waitForNavigation(),z.getByRole('button',{name:'Zugang speichern'}).tap()]);
 ok((await p.textContent('main')).includes('Helfer-Zugang gespeichert'),'account saved');
 const hc=await b.newContext({ignoreHTTPSErrors:true,viewport:{width:390,height:844},isMobile:true,hasTouch:true});const h=await hc.newPage();h.on('pageerror',e=>errors.push(e.message));
 await h.goto(base+'/helfer.php');await h.locator('[name=login]').fill('max');await h.locator('[name=password]').fill('falsch');await Promise.all([h.waitForNavigation(),h.getByRole('button',{name:'Anmelden'}).tap()]);ok((await h.textContent('main')).includes('stimmt nicht'),'wrong password');
 await h.locator('[name=login]').fill('max');await h.locator('[name=password]').fill('Helfer-Test-2026');await Promise.all([h.waitForNavigation(),h.getByRole('button',{name:'Anmelden'}).tap()]);
 let hm=await h.textContent('main');ok(hm.includes('Hallo Max')&&hm.includes('Treppenhaus wischen')&&hm.includes('Bitte auch Keller')&&!hm.includes('Glasreinigung'),'helper sees only own task');
 r=await h.goto(base+'/index.php');ok(!(await h.textContent('body')).includes('Heute')||(await h.locator('[name=password]').count())===1,'helper has no access to the Zentrale');
 r=await h.goto(base+'/meldungen.php');ok((await h.locator('[name=password]').count())===1,'helper cannot open admin pages');
 await h.goto(base+'/helfer.php');await Promise.all([h.waitForNavigation(),h.getByRole('button',{name:'Start'}).tap()]);ok((await h.textContent('main')).includes('Läuft seit'),'started');
 const before=h.locator('form').filter({has:h.locator('[name=label][value=before]')});await before.locator('[name=photo]').setInputFiles({name:'a.jpg',mimeType:'image/jpeg',buffer:jpg});await Promise.all([h.waitForNavigation(),before.getByRole('button',{name:'Hochladen'}).tap()]);
 ok((await h.textContent('main')).includes('1 vorher'),'before photo');await h.locator('[name=note]').fill('Licht 2. OG defekt');await Promise.all([h.waitForNavigation(),h.getByRole('button',{name:'Fertig'}).tap()]);
 hm=await h.textContent('main');ok(hm.includes('Erledigt – danke')&&hm.includes('alles erledigt'),'finished');const wl=q("SELECT * FROM work_logs WHERE worker_id IS NOT NULL")[0];
 ok(wl.ended_at&&wl.notes==='Licht 2. OG defekt'&&q(`SELECT done FROM tasks WHERE title='Treppenhaus wischen'`)[0].done==1,'work log and task done');
 q(`UPDATE worker_accounts SET active=0 WHERE worker_id=${wid}`);await h.goto(base+'/helfer.php');ok((await h.locator('[name=password]').count())===1,'switched-off helper is logged out at once');
 // D: Schlüsseldienst-Einsatz
 await p.goto(base+'/einsaetze.php');const f=p.locator('#neu form');await f.locator('[name=place]').fill('Verl');await f.locator('[name=amount]').fill('150');await f.locator('[name=payment]').selectOption('rechnung');await f.locator('[name=channel]').selectOption('google');await f.locator('[name=phone]').fill('0176 1234567');
 await Promise.all([p.waitForNavigation(),f.getByRole('button',{name:'Speichern'}).tap()]);let em=await p.textContent('main');ok(em.includes('Rechnung offen“ steht in der Merkliste')&&em.includes('150 €')&&em.includes('Google'),'job saved, stats');
 ok(q("SELECT COUNT(*) n FROM quick_notes WHERE kind='invoice' AND title LIKE 'Schlüsseldienst%Verl%'")[0].n==1,'invoice reminder');
 const rf=p.locator('.card.lead form').filter({has:p.locator('[name=action][value=review]')});r=await c.request.post(base+'/einsaetze.php',{form:{csrf:await rf.locator('[name=csrf]').inputValue(),action:'review',id:await rf.locator('[name=id]').inputValue()},maxRedirects:0});
 ok(r.status()===303&&(r.headers()['location']||'').startsWith('https://wa.me/491761234567?text='),'review via WhatsApp');
 // E: Preisrechner – aus bis Sätze eingetragen
 let j=await (await c.request.get(base+'/web_calc.php?floors=3',{headers:{Origin:'https://gebaeudeservice-zentrum.de'}})).json();ok(j.enabled===false,'calculator off by default');
 await p.goto(base+'/website.php?rechner');const cf=p.locator('#rechner form');await cf.locator('[name=calc_rate]').fill('36');await cf.locator('[name=calc_enabled]').check();await Promise.all([p.waitForNavigation(),cf.getByRole('button',{name:'Speichern'}).tap()]);
 ok((await p.textContent('main')).includes('erscheint jetzt auf der Website'),'calculator enabled');
 r=await c.request.get(base+'/web_calc.php?floors=3&entrances=1&rhythm=weekly&cellar=1',{headers:{Origin:'https://gebaeudeservice-zentrum.de'}});j=await r.json();
 ok(j.enabled&&j.low===80&&j.high===105&&j.text.includes('zzgl. MwSt.')&&r.headers()['access-control-allow-origin']==='https://gebaeudeservice-zentrum.de'&&!r.headers()['set-cookie'],'calculator endpoint with CORS');
 // Neues Objekt mit neuem Kunden in einem Schritt
 await p.setViewportSize({width:390,height:844});await p.goto(base+'/objects.php');const nf=p.locator('details.card').filter({hasText:'Neues Objekt'});if(!(await nf.evaluate(d=>d.open)))await nf.locator('summary').first().tap();
 await nf.locator('[name=name]').fill('Neubau Testweg 7');await nf.locator('[name=address]').fill('Testweg 7, 33330 Gütersloh');await nf.locator('.new-customer summary').tap();
 await nf.locator('[name=new_customer_name]').fill('Hausverwaltung Beispiel GmbH');await nf.locator('[name=new_customer_contact]').fill('Frau Muster');await nf.locator('[name=new_customer_phone]').fill('05241 000000');await nf.locator('[name=new_customer_email]').fill('verwaltung@example.invalid');
 await Promise.all([p.waitForNavigation(),nf.getByRole('button',{name:'Objekt anlegen'}).tap()]);
 const np=q("SELECT p.*,c.name cname,c.email cmail FROM properties p JOIN customers c ON c.id=p.customer_id WHERE p.name='Neubau Testweg 7'");
 ok(np.length===1&&np[0].cname==='Hausverwaltung Beispiel GmbH'&&np[0].cmail==='verwaltung@example.invalid'&&np[0].contact_name==='Frau Muster'&&np[0].contact_email==='verwaltung@example.invalid'&&/property\.php\?id=/.test(p.url()),'object and new customer created together, contact on the object');
 // Häuser-Übersicht: alle Häuser, für alle einrichten, alle Aushänge in einem PDF, erneuern
 q("INSERT INTO properties(name,address) VALUES('Zweites Haus','Teststraße 2'),('Drittes Haus','Teststraße 3')");
 await p.goto(base+'/objects.php');await p.getByRole('link',{name:/Häuser: QR-Meldung/}).tap();await p.waitForURL(/haeuser\.php/);
 let hs=await p.textContent('main');ok(hs.includes('Zweites Haus')&&hs.includes('Drittes Haus')&&(await p.locator('article.house-row').count())===q('SELECT COUNT(*) n FROM properties')[0].n,'overview lists all houses');
 const missing=q('SELECT COUNT(*) n FROM properties p LEFT JOIN property_links l ON l.property_id=p.id WHERE l.report_token IS NULL')[0].n;await Promise.all([p.waitForNavigation(),p.getByRole('button',{name:'QR-Meldung für alle übrigen einrichten'}).tap()]);ok(missing>=2&&(await p.textContent('main')).includes('für '+missing+' Häuser eingerichtet'),'set up for all remaining');
 const total=q('SELECT COUNT(*) n FROM properties')[0].n;ok(q('SELECT COUNT(*) n FROM property_links WHERE report_token IS NOT NULL')[0].n===total,'every house has a QR link');
 r=await c.request.get(base+'/haeuser.php?pdf=1');const all=(await r.body()).toString('latin1');ok(r.status()===200&&all.startsWith('%PDF')&&(all.match(/\/Type \/Page\b/g)||[]).length===total,'all notices in one PDF, one page per house');
 const row=p.locator('article.house-row').filter({hasText:'Zweites Haus'});const oldTok=q("SELECT l.report_token t FROM property_links l JOIN properties p ON p.id=l.property_id WHERE p.name='Zweites Haus'")[0].t;
 await Promise.all([p.waitForNavigation(),row.locator('.link-box').first().getByRole('button',{name:'Erneuern'}).tap()]);const newTok=q("SELECT l.report_token t FROM property_links l JOIN properties p ON p.id=l.property_id WHERE p.name='Zweites Haus'")[0].t;
 ok(newTok&&newTok!==oldTok&&(await tc.request.get(base+'/melden.php?k='+oldTok)).status()===404,'renew invalidates old link');
 await Promise.all([p.waitForNavigation(),p.locator('article.house-row').filter({hasText:'Drittes Haus'}).locator('.link-box').nth(1).getByRole('button',{name:'Einrichten'}).tap()]);
 ok(await p.locator('article.house-row').filter({hasText:'Drittes Haus'}).locator('[data-copy]').count()===1,'portal per house from the overview');
 // F: Bewertungskarten
 r=await c.request.get(base+'/bewertungskarten.php');ok(r.status()===200&&(await r.body()).slice(0,4).toString()==='%PDF','review cards PDF');
 for(const u of ['/haeuser.php','/objects.php','/meldungen.php','/einsaetze.php','/website.php',`/property.php?id=${pid}`,'/workers.php?id='+wid])for(const w of [390,1440]){await p.setViewportSize({width:w,height:900});await p.goto(base+u);ok(await p.evaluate(()=>document.documentElement.scrollWidth<=innerWidth),'fits '+w+' '+u);}
 for(const u of [path,new URL(portal).pathname+new URL(portal).search])for(const w of [390,1440]){await t.setViewportSize({width:w,height:900});await t.goto(base+u);ok(await t.evaluate(()=>document.documentElement.scrollWidth<=innerWidth),'public fits '+w);}
 ok(errors.length===0,'no JavaScript errors: '+errors.join(' | '));console.log(`Service browser PASS (${n} checks; synthetic data).`);
}finally{await b.close()}})().catch(e=>{console.error('Error after: '+globalThis.__last);console.error(e);process.exit(1)});
