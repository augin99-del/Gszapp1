const {chromium}=require('playwright');
// Verträge je Objekt (Schema 450): Objekt mit neuem Kunden anlegen → Entwurf entsteht automatisch → Preis eintragen → PDF → versendet → unterschrieben. Synthetische Daten.
(async()=>{const base=process.env.GSZ_TEST_URL;if(!base||!/^https:\/\/127\.0\.0\.1:/.test(base))throw Error('Loopback fixture only');let n=0;const ok=(v,s)=>{n++;if(!v)throw Error(s)};
const b=await chromium.launch({executablePath:'/usr/bin/chromium',args:['--no-sandbox']});const errors=[];const shots=process.env.GSZ_SHOTS||'';
try{const c=await b.newContext({ignoreHTTPSErrors:true,viewport:{width:1440,height:1000}});const p=await c.newPage();p.on('pageerror',e=>errors.push(e.message));
 await p.goto(base+'/index.php');await p.locator('[name=email]').fill('owner@example.invalid');await p.locator('[name=password]').fill('Local-Test-Only-2026!');await p.getByRole('button',{name:/^Anmelden$/}).click();
 // Firmendaten fehlen → Hinweis; eintragen
 await p.goto(base+'/vertraege.php');let main=await p.textContent('main');ok(main.includes('Firmenanschrift fehlt'),'company address missing hint');
 const fs=p.locator('#firma form');await fs.locator('[name=company_address]').fill('Teststraße 1\n33330 Gütersloh');await fs.locator('[name=company_email]').fill('info@example.invalid');await fs.getByRole('button',{name:'Speichern'}).click();
 ok(!(await p.textContent('main')).includes('Firmenanschrift fehlt'),'company saved');
 // Objekt + neuer Kunde + regelmäßige Arbeit
 await p.goto(base+'/objects.php?neu=kunde#neu');const f=p.locator('#neu form');await f.locator('[name=name]').fill('Ahornweg 5');await f.locator('[name=address]').fill('Ahornweg 5, 33330 Gütersloh');
 await f.locator('[name=new_customer_name]').fill('Hausverwaltung Ahorn GmbH');await f.locator('[name=new_customer_billing]').fill('Postfach 1\n33330 Gütersloh');await f.locator('[name=rule_title]').fill('Treppenhausreinigung');
 await f.getByRole('button',{name:'Objekt anlegen'}).click();await p.waitForURL(/property\.php\?id=\d+/);main=await p.textContent('main');
 ok(main.includes('Vertragsentwurf')&&main.includes('automatisch angelegt'),'auto draft announced');ok(await p.locator('#vertrag .pill',{hasText:'Entwurf'}).count()===1,'contract card on property');ok(main.includes('Noch eintragen: Preis'),'missing price shown');
 await p.locator('#vertrag a',{hasText:'Vertrag öffnen'}).click();await p.waitForURL(/vertrag\.php\?id=\d+/);main=await p.textContent('main');
 const form=p.locator('form#daten');ok((await form.locator('[name=client_name]').inputValue())==='Hausverwaltung Ahorn GmbH','client from new customer');ok((await form.locator('[name=services]').inputValue()).includes('Treppenhausreinigung – wöchentlich'),'service from rule');
 ok(await form.locator('[name=customer_kind][value=business]').isChecked(),'GmbH = business');ok(await p.locator('button',{hasText:'Als versendet markieren'}).isDisabled(),'send blocked without price');
 if(shots)await p.screenshot({path:shots+'/vertrag-entwurf.png',fullPage:true});
 await form.locator('[name=price]').fill('189,00');await form.locator('[name=special_terms]').fill('Fensterreinigung Treppenhaus 2× jährlich inklusive');await form.getByRole('button',{name:'Speichern'}).click();
 ok((await p.textContent('main')).includes('Vertrag gespeichert'),'saved');ok(!(await p.textContent('main')).includes('Bevor der Vertrag raus kann'),'complete');
 const id=new URL(p.url()).searchParams.get('id');const pdf=await c.request.get(base+'/vertrag_pdf.php?id='+id);ok(pdf.status()===200&&pdf.headers()['content-type']==='application/pdf'&&(await pdf.body()).subarray(0,4).toString()==='%PDF','pdf');
 ok(/attachment/.test((await c.request.get(base+'/vertrag_pdf.php?id='+id+'&download=1')).headers()['content-disposition']),'pdf download');
 await p.locator('summary',{hasText:'Vertragstext ansehen'}).click();main=await p.textContent('main');ok(main.includes('189,00 € monatlich zuzüglich')&&main.includes('Besondere Vereinbarungen')&&main.includes('Fensterreinigung'),'preview text');
 // Verbraucher-Umschaltung
 await form.locator('[name=customer_kind][value=consumer]').check();await form.locator('[name=term_months]').fill('36');await form.getByRole('button',{name:'Speichern'}).click();
 ok((await p.locator('[name=term_months]').inputValue())==='24'&&(await p.textContent('main')).includes('§ 309 Nr. 9 BGB'),'consumer clamp');
 await p.locator('form#daten [name=customer_kind][value=business]').check();await p.locator('form#daten [name=term_months]').fill('12');await p.locator('form#daten [name=renew_months]').fill('12').catch(()=>{});await p.locator('form#daten').getByRole('button',{name:'Speichern'}).click();
 // Status
 await p.getByRole('button',{name:'Als versendet markieren'}).click();ok(await p.locator('.contract-head .pill',{hasText:'Versendet'}).count()===1,'sent');
 await p.getByRole('button',{name:/Unterschrieben – Vertrag aktiv/}).click();main=await p.textContent('main');ok(await p.locator('.contract-head .pill',{hasText:'Aktiv'}).count()===1&&main.includes('Kündigung möglich bis'),'active with notice date');
 if(shots)await p.screenshot({path:shots+'/vertrag-aktiv.png',fullPage:true});
 // Übersicht + Bereich + Suche
 await p.goto(base+'/vertraege.php');main=await p.textContent('main');ok(main.includes('Ahornweg 5')&&main.includes('189,00 €'),'listed');ok(/Objekte ohne Vertrag/.test(main),'without-contract counter');
 const before=await p.locator('.list > a').count();await p.getByRole('button',{name:/Entwürfe für .* ohne Vertrag anlegen/}).click();ok((await p.textContent('main')).includes('Vertragsentwürfe angelegt')||(await p.textContent('main')).includes('Vertragsentwurf angelegt'),'bulk drafts');ok(await p.locator('.list > a').count()>before,'more contracts');
 ok(await p.locator('.side-areas a[href="vertraege.php"]').count()===1,'sidebar entry');ok(await p.locator('.side-nav > a').count()===6,'still six nav points');
 if(shots)await p.screenshot({path:shots+'/vertraege.png',fullPage:true});
 await p.goto(base+'/suche.php?q=Vertr');ok((await p.textContent('main')).includes('Verträge'),'search');
 // Handy
 const m=await b.newContext({ignoreHTTPSErrors:true,viewport:{width:390,height:844},isMobile:true,hasTouch:true,storageState:await c.storageState()});const q=await m.newPage();q.on('pageerror',e=>errors.push(e.message));
 for(const path of ['/vertraege.php','/vertrag.php?id='+id]){const r=await q.goto(base+path);ok(r.status()===200,'200 '+path);ok(await q.evaluate(()=>document.documentElement.scrollWidth<=innerWidth),'390px '+path);}
 if(shots){await q.goto(base+'/vertrag.php?id='+id);await q.screenshot({path:shots+'/vertrag-handy.png',fullPage:true});}
 ok((await q.goto(base+'/vertrag.php?id=99999')).status()===404,'404');
 const anon=await b.newContext({ignoreHTTPSErrors:true});ok((await anon.request.get(base+'/vertrag_pdf.php?id='+id)).status()===403,'pdf needs login');await anon.close();
 ok(!errors.length,'no page errors '+errors.join('|'));console.log('contracts browser checks passed: '+n);
}finally{await b.close();}})().catch(e=>{console.error(e);process.exit(1)});
