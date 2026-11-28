const {chromium}=require('playwright');
// Datensicherung, Angebote (Anfrage → Angebot → PDF/Mail → versendet → angenommen → Vertrag) und Schlüssel (erfassen, ausgeben, zurück, Quittung). Synthetische Daten.
(async()=>{const base=process.env.GSZ_TEST_URL;if(!base||!/^https:\/\/127\.0\.0\.1:/.test(base))throw Error('Loopback fixture only');let n=0;const ok=(v,s)=>{n++;if(!v)throw Error(s)};
const b=await chromium.launch({executablePath:'/usr/bin/chromium',args:['--no-sandbox']});const errors=[];const shots=process.env.GSZ_SHOTS||'';
try{const c=await b.newContext({ignoreHTTPSErrors:true,viewport:{width:1440,height:1000}});const p=await c.newPage();p.on('pageerror',e=>errors.push(e.message));
 await p.goto(base+'/index.php');await p.locator('[name=email]').fill('owner@example.invalid');await p.locator('[name=password]').fill('Local-Test-Only-2026!');await p.getByRole('button',{name:/^Anmelden$/}).click();
 // ---------- Datensicherung ----------
 ok(await p.locator('.attention .att',{hasText:'Noch keine Datensicherung'}).count()===1,'today: no backup yet');
 await p.goto(base+'/settings.php');await p.getByRole('link',{name:/Datensicherung/}).click();await p.waitForURL(/sicherung\.php/);
 await p.getByRole('button',{name:'Jetzt sichern'}).click();let main=await p.textContent('main');ok(main.includes('Sicherung erstellt')&&main.includes('von Hand'),'manual backup listed');
 const dl=await c.request.get(base+'/'+(await p.locator('.list a').first().getAttribute('href')));ok(dl.status()===200&&/gzip/.test(dl.headers()['content-type'])&&(await dl.body()).subarray(0,2).toString('hex')==='1f8b','download gzip');
 await p.goto(base+'/sicherung.php');ok((await p.locator('.dk').nth(1).textContent()).includes('heute'),'download recorded');
 await p.goto(base+'/index.php');main=await p.textContent('main');ok(await p.locator('.attention .att',{hasText:'Datensicherung'}).count()===0,'no backup hint after backup+download');
 // ---------- Angebote ----------
 await p.goto(base+'/leads.php#neu');const lf=p.locator('#neu form');await p.locator('#neu summary').click();await lf.locator('[name=name]').fill('Petra Probe');await lf.locator('[name=company]').fill('Hausverwaltung Probe GmbH');await lf.locator('[name=phone]').fill('0170 0000000');await lf.locator('[name=email]').fill('hv@example.invalid').catch(()=>{});await lf.locator('[name=service]').fill('Treppenhausreinigung').catch(()=>{});await lf.locator('[name=place]').fill('Gütersloh').catch(()=>{});
 await lf.locator('button').last().click();await p.getByRole('button',{name:'Angebot erstellen'}).first().click();await p.waitForURL(/angebot\.php\?id=\d+/);
 const f=p.locator('form#daten');ok((await f.locator('[name=client_name]').inputValue())==='Hausverwaltung Probe GmbH','client from lead');ok(await f.locator('[name=customer_kind][value=business]').isChecked(),'business');
 ok(await p.getByRole('button',{name:'Per E-Mail senden'}).isDisabled(),'mail blocked without price');
 await f.locator('[name=object_name]').fill('Lindenweg 9');await f.locator('[name=object_address]').fill('Lindenweg 9, 33330 Gütersloh');await f.locator('[name="items[0][t]"]').fill('Treppenhausreinigung wöchentlich, 3 Etagen');await f.locator('[name="items[0][p]"]').fill('189,00');
 await f.locator('[name="items[1][t]"]').fill('Grundreinigung zum Start');await f.locator('[name="items[1][per]"]').selectOption('once');await f.locator('[name="items[1][p]"]').fill('240');
 await f.getByRole('button',{name:'Speichern'}).click();main=await p.textContent('main');ok(main.includes('Angebot gespeichert')&&main.includes('189,00 € monatlich + 240,00 € einmalig'),'saved with totals');
 const oid=new URL(p.url()).searchParams.get('id');const pdf=await c.request.get(base+'/angebot_pdf.php?id='+oid);ok(pdf.status()===200&&(await pdf.body()).subarray(0,4).toString()==='%PDF','offer pdf');
 if(shots)await p.screenshot({path:shots+'/angebot.png',fullPage:true});
 await p.getByRole('button',{name:'Per E-Mail senden'}).click();await p.waitForURL(/compose\.php\?draft=\d+/);main=await p.textContent('main');ok(main.includes('Angebot-A-')&&main.includes('.pdf'),'mail draft with pdf');ok((await p.locator('[name=to_address]').inputValue())==='hv@example.invalid','recipient prefilled');
 await p.goto(base+'/angebot.php?id='+oid);await p.getByRole('button',{name:'Als versendet markieren'}).click();main=await p.textContent('main');ok(main.includes('Versendet am')&&main.includes('nächste Erinnerung'),'sent with reminder');
 await p.goto(base+'/leads.php');ok(await p.locator('a',{hasText:'Angebot Versendet'}).count()===1,'lead shows offer');
 await p.goto(base+'/angebot.php?id='+oid);await p.getByRole('button',{name:/Angenommen – Kunde, Objekt und Vertrag anlegen/}).click();await p.waitForURL(/vertrag\.php\?id=\d+/);
 main=await p.textContent('main');ok(main.includes('Angenommen!')&&main.includes('Vertragsentwurf'),'accepted → contract');ok((await p.locator('form#daten [name=price]').inputValue())==='189,00','contract price from offer');ok((await p.locator('form#daten [name=services]').inputValue()).includes('Grundreinigung zum Start (240,00 € einmalig)'),'services from offer');
 ok(await p.getByRole('button',{name:'Per E-Mail senden'}).count()===1,'contract mail button');
 await p.goto(base+'/angebote.php');main=await p.textContent('main');ok(main.includes('Hausverwaltung Probe GmbH')&&main.includes('Angenommen')&&main.includes('100 %'),'offers list + quota');
 // ---------- Schlüssel ----------
 await p.goto(base+'/objects.php');await p.locator('.list a, a[href*="property.php?id="]',{hasText:'Lindenweg 9'}).first().click();await p.waitForURL(/property\.php/);ok(await p.locator('#vertrag .pill',{hasText:'Entwurf'}).count()===1,'property has contract');
 await p.locator('#schluessel a',{hasText:'+ Erfassen'}).click();await p.waitForURL(/schluessel\.php\?objekt=/);const kf=p.locator('#neu form');await kf.locator('[name=label]').fill('Haustür + Keller');await kf.locator('[name=copies]').fill('2');await kf.locator('[name=storage]').fill('Schlüsselkasten Fach 1');await kf.locator('[name=from]').fill('Hausverwaltung Probe GmbH');await kf.getByRole('button',{name:'Erfassen'}).click();
 main=await p.textContent('main');ok(main.includes('Schlüssel S-001 erfasst')&&main.includes('Haustür + Keller (2 Stück)'),'key added');
 const card=p.locator('.key-card').first();await card.locator('.kc-give [name=person]').fill('Mitarbeiter A');await card.getByRole('button',{name:'Ausgeben'}).click();main=await p.textContent('main');ok(main.includes('Ausgegeben an Mitarbeiter A')&&main.includes('bei Mitarbeiter A'),'issued');
 ok(await p.locator('.side-areas a[href="schluessel.php"] .count-pill').count()===1,'sidebar counter for keys out');
 const rec=await c.request.get(base+'/'+(await p.locator('a',{hasText:'Quittung Mitarbeiter'}).getAttribute('href')).replace(/&amp;/g,'&'));ok(rec.status()===200&&(await rec.body()).subarray(0,4).toString()==='%PDF','receipt worker');
 await p.locator('.key-card').first().getByRole('button',{name:'Zurück im Büro'}).click();ok((await p.textContent('main')).includes('Zurück im Büro.'),'returned');
 await p.locator('.key-card').first().locator('summary',{hasText:'Verlauf'}).click();main=await p.textContent('main');ok(main.includes('Vom Kunden erhalten Hausverwaltung Probe GmbH')&&main.includes('Ausgegeben an Mitarbeiter A')&&main.includes('Zurück ins Büro Mitarbeiter A'),'history');
 if(shots)await p.screenshot({path:shots+'/schluessel.png',fullPage:true});
 await p.goto(base+'/schluessel.php?q=S-001');ok(await p.locator('.key-card').count()===1,'search by number');
 const prop=await c.request.get(base+'/schluessel_pdf.php?objekt=999&art=erhalten');ok(prop.status()===404,'receipt unknown object 404');
 // Suche/Navigation
 await p.goto(base+'/suche.php?q=Schl');ok((await p.textContent('main')).includes('Schlüssel'),'search keys page');ok(await p.locator('.side-nav > a').count()===6,'six nav points');
 // Handy
 const m=await b.newContext({ignoreHTTPSErrors:true,viewport:{width:390,height:844},isMobile:true,hasTouch:true,storageState:await c.storageState()});const q=await m.newPage();q.on('pageerror',e=>errors.push(e.message));
 for(const path of ['/sicherung.php','/angebote.php','/angebot.php?id='+oid,'/schluessel.php','/leads.php']){const r=await q.goto(base+path);ok(r.status()===200,'200 '+path);ok(await q.evaluate(()=>document.documentElement.scrollWidth<=innerWidth),'390px '+path);}
 if(shots){await q.goto(base+'/schluessel.php');await q.screenshot({path:shots+'/schluessel-handy.png',fullPage:true});await q.goto(base+'/angebot.php?id='+oid);await q.screenshot({path:shots+'/angebot-handy.png',fullPage:true});}
 const anon=await b.newContext({ignoreHTTPSErrors:true});for(const u of ['/angebot_pdf.php?id='+oid,'/schluessel_pdf.php?objekt=1'])ok((await anon.request.get(base+u)).status()===403,'login required '+u);await anon.close();
 ok(!errors.length,'no page errors '+errors.join('|'));console.log('offers/keys/backup browser checks passed: '+n);
}finally{await b.close();}})().catch(e=>{console.error(e);process.exit(1)});
