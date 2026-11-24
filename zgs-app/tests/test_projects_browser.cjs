const {chromium}=require('playwright');
// Bauprojekte im Browser (Wunsch 24.11.2026: Sanierung 4-Familienhaus planen und übersichtlich haben). Synthetische Daten.
(async()=>{const base=process.env.GSZ_TEST_URL;if(!base||!/^https:\/\/127\.0\.0\.1:/.test(base))throw Error('Loopback fixture only');let n=0;const ok=(v,s)=>{n++;if(!v)throw Error(s)};
const b=await chromium.launch({executablePath:'/usr/bin/chromium',args:['--no-sandbox']});const errors=[];
const png=Buffer.from('iVBORw0KGgoAAAANSUhEUgAAAAgAAAAICAIAAABLbSncAAAAEklEQVR4nGP4z8CAFWEXHbQSACj/P8Fu7N9hAAAAAElFTkSuQmCC','base64');
const pdf=Buffer.from('%PDF-1.4\n1 0 obj<</Type/Catalog/Pages 2 0 R>>endobj 2 0 obj<</Type/Pages/Kids[3 0 R]/Count 1>>endobj 3 0 obj<</Type/Page/Parent 2 0 R/MediaBox[0 0 200 200]>>endobj\ntrailer<</Root 1 0 R>>\n%%EOF\n');
const iso=k=>new Intl.DateTimeFormat('sv-SE',{timeZone:'Europe/Berlin'}).format(new Date(Date.now()+(k)*864e5)); // Datum in deutscher Zeit (wie der Server)
try{const c=await b.newContext({ignoreHTTPSErrors:true,viewport:{width:390,height:844},isMobile:true,hasTouch:true});const p=await c.newPage();p.on('pageerror',e=>errors.push(e.message));
 await p.goto(base+'/index.php');await p.locator('[name=email]').fill('owner@example.invalid');await p.locator('[name=password]').fill('Local-Test-Only-2026!');await p.getByRole('button',{name:/^Anmelden$/}).click();
 await p.goto(base+'/more.php');await p.getByRole('link',{name:/Bauprojekte/}).tap();ok((await p.textContent('main')).includes('Noch kein Projekt'),'empty project list');
 const nf=p.locator('#neu form');await nf.locator('[name=title]').fill('Sanierung 4-Familienhaus');const opt=await nf.locator('[name=building_id] option').nth(1).getAttribute('value');if(opt)await nf.locator('[name=building_id]').selectOption(opt);
 await nf.locator('[name=start_date]').fill(iso(-20));await nf.locator('[name=target_date]').fill(iso(100));await nf.locator('[name=budget]').fill('180.000');
 await Promise.all([p.waitForNavigation(),nf.getByRole('button',{name:'Projekt anlegen'}).tap()]);ok(p.url().includes('project.php?id=')&&(await p.textContent('main')).includes('Typische Gewerke sind schon drin'),'project created with template trades');
 const pid=new URL(p.url()).searchParams.get('id');const go=t=>p.goto(base+`/project.php?id=${pid}&tab=${t}`);
 // Handwerker
 await go('handwerker');let f=p.locator('form').filter({has:p.locator('[name=action][value=contractor_add]')});await f.locator('[name=name]').fill('Elektro Muster GmbH');await f.locator('[name=trade]').fill('Elektro');await f.locator('[name=phone]').fill('0000 000000');
 await Promise.all([p.waitForNavigation(),f.getByRole('button',{name:'Speichern'}).tap()]);ok(await p.locator('.list .li a[href^="tel:"]').count()===1,'contractor with tap-to-call');
 // Gewerk bearbeiten: Elektro mit Handwerker, Zeitraum, Budget, Gewährleistung
 await go('gewerke');ok(await p.locator('.block.trade').count()>=15,'template trades listed');let el=p.locator('.block.trade').filter({has:p.locator('.obj',{hasText:/^Elektro$/})});
 await el.locator('summary').filter({hasText:'Bearbeiten'}).tap();let ef=el.locator('form').filter({has:p.locator('[name=action][value=trade_update]')});
 await ef.locator('[name=contractor_id]').selectOption({label:'Elektro Muster GmbH (Elektro)'});await ef.locator('[name=status]').selectOption('laeuft');await ef.locator('[name=start_date]').fill(iso(-5));await ef.locator('[name=end_date]').fill(iso(20));await ef.locator('[name=budget]').fill('25.000');await ef.locator('[name=warranty_until]').fill(iso(30));
 await Promise.all([p.waitForNavigation(),ef.getByRole('button',{name:'Speichern'}).tap()]);el=p.locator('.block.trade').filter({has:p.locator('.obj',{hasText:/^Elektro$/})});ok((await el.textContent()).includes('Läuft')&&(await el.textContent()).includes('Elektro Muster GmbH')&&(await el.textContent()).includes('25.000,00 €'),'trade updated');
 const dach=p.locator('.block.trade').filter({has:p.locator('.obj',{hasText:/^Dach$/})});await dach.locator('summary').filter({hasText:'Bearbeiten'}).tap();const df=dach.locator('form').filter({has:p.locator('[name=action][value=trade_update]')});await df.locator('[name=start_date]').fill(iso(-20));await df.locator('[name=end_date]').fill(iso(-8));await Promise.all([p.waitForNavigation(),df.getByRole('button',{name:'Speichern'}).tap()]);
 await Promise.all([p.waitForNavigation(),p.locator('.block.trade').filter({has:p.locator('.obj',{hasText:/^Dach$/})}).getByRole('button',{name:'Fertig'}).tap()]);ok((await p.locator('.block.trade').filter({has:p.locator('.obj',{hasText:/^Dach$/})}).textContent()).includes('Fertig'),'trade status one tap');
 // Termin → Woche
 await go('termine');f=p.locator('form').filter({has:p.locator('[name=action][value=event_add]')});await f.locator('[name=day]').fill(iso(1));await f.locator('[name=trade_id]').selectOption({label:'Elektro'});await f.locator('[name=contractor_id]').selectOption({label:'Elektro Muster GmbH (Elektro)'});
 await Promise.all([p.waitForNavigation(),f.getByRole('button',{name:'Eintragen'}).tap()]);ok((await p.textContent('main')).includes('steht auch in deiner Woche'),'event saved');
 await p.goto(base+'/planner.php?date='+iso(1));ok((await p.textContent('main')).includes('Bau: Elektro – Elektro Muster GmbH'),'appears in the day plan');
 // Kosten mit PDF
 await go('kosten');f=p.locator('form').filter({has:p.locator('[name=action][value=cost_add]')});await f.locator('[name=title]').fill('Elektro Abschlag 1');await f.locator('[name=amount]').fill('8.000');await f.locator('[name=due_date]').fill(iso(2));await f.locator('[name=trade_id]').selectOption({label:'Elektro'});
 await f.locator('[name=file]').setInputFiles({name:'rechnung.pdf',mimeType:'application/pdf',buffer:pdf});await Promise.all([p.waitForNavigation(),f.getByRole('button',{name:'Speichern'}).tap()]);
 let inv=p.locator('.list.costs .li').filter({hasText:'Elektro Abschlag 1'});ok((await inv.textContent()).includes('8.000,00 €')&&await inv.locator('a',{hasText:'PDF ansehen'}).count()===1,'invoice with PDF');
 const href=await inv.locator('a',{hasText:'PDF ansehen'}).getAttribute('href');const r=await p.request.get(base+'/'+href);ok(r.status()===200&&r.headers()['content-type']==='application/pdf','PDF served to the owner');
 await p.goto(base+'/index.php');ok((await p.locator('.attention').textContent()).includes('Handwerkerrechnung bald fällig')&&(await p.locator('.attention').textContent()).includes('Gewährleistung Elektro endet in 30 Tagen'),'Heute: invoice due and warranty reminder');
 await go('kosten');await Promise.all([p.waitForNavigation(),p.locator('.list.costs .li').filter({hasText:'Elektro Abschlag 1'}).getByRole('button',{name:'Bezahlt'}).tap()]);ok((await p.locator('.list.costs .li').filter({hasText:'Elektro Abschlag 1'}).textContent()).includes('bezahlt am'),'marked paid');
 // Mangel mit Foto, abhaken ohne Neuladen
 await go('maengel');f=p.locator('form').filter({has:p.locator('[name=action][value=item_add]')});await f.locator('label').filter({hasText:'Mangel'}).first().tap();await f.locator('[name=title]').fill('Steckdose Küche fehlt');await f.locator('[name=unit_label]').fill('Whg. 2 EG');await f.locator('[name=photo]').setInputFiles({name:'m.png',mimeType:'image/png',buffer:png});
 await Promise.all([p.waitForNavigation(),f.getByRole('button',{name:'Notieren'}).tap()]);let it=p.locator('.block.note').filter({hasText:'Steckdose Küche fehlt'});ok((await it.textContent()).includes('Mangel')&&(await it.textContent()).includes('Whg. 2 EG')&&await it.locator('img').evaluate(i=>i.complete&&i.naturalWidth===8),'defect with flat and photo');
 await p.evaluate(()=>window.__nr=1);await it.locator('button.check').tap();await p.waitForFunction(()=>/erledigt/.test(document.getElementById('gsz-toast')?.textContent||''));ok(await p.evaluate(()=>window.__nr===1)&&await it.evaluate(e=>e.classList.contains('is-done')),'ticked without reload');
 // Übersicht
 await go('uebersicht');const m=await p.textContent('main');ok(m.includes('Fortschritt')&&m.includes('8.000,00 €')&&m.includes('180.000,00 €')&&m.includes('Bauzeitenplan')&&await p.locator('.g-row').count()===2,'overview with KPIs and schedule');
 ok(m.includes('Elektro Muster GmbH')||m.includes('Nächste Termine'),'next events');
 for(const t of ['uebersicht','gewerke','termine','kosten','maengel','fotos','handwerker'])for(const w of [390,1440]){await p.setViewportSize({width:w,height:900});await go(t);ok(await p.evaluate(()=>document.documentElement.scrollWidth<=innerWidth),'fits '+w+' '+t);}
 await p.goto(base+'/projects.php');ok((await p.locator('.project-card').textContent()).includes('Sanierung 4-Familienhaus'),'project card in list');
 ok(errors.length===0,'no JavaScript errors: '+errors.join(' | '));console.log(`Bauprojekte browser PASS (${n} checks; synthetic data).`);
}finally{await b.close()}})().catch(e=>{console.error(e);process.exit(1)});
