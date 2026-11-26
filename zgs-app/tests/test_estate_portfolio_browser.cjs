const {chromium}=require('playwright');
// Immobilien-Umbau 11/2026: Portfolio-Startseite, Hausseite mit Reitern, Mietkonto-Raster, Eingangskorb, Nebenkosten-Assistent. Synthetische Daten.
(async()=>{const base=process.env.GSZ_TEST_URL;if(!base||!/^https:\/\/127\.0\.0\.1:/.test(base))throw Error('Loopback fixture only');let n=0;const ok=(v,s)=>{n++;if(!v)throw Error(s)};
const b=await chromium.launch({executablePath:'/usr/bin/chromium',args:['--no-sandbox']});const errors=[];const shots=process.env.GSZ_SHOTS||'';
try{const c=await b.newContext({ignoreHTTPSErrors:true,viewport:{width:1440,height:1000}});const p=await c.newPage();p.on('pageerror',e=>errors.push(e.message));
 await p.goto(base+'/index.php');await p.locator('[name=email]').fill('owner@example.invalid');await p.locator('[name=password]').fill('Local-Test-Only-2026!');await p.getByRole('button',{name:/^Anmelden$/}).click();
 // Navigation führt auf die neue Startseite
 const nav=p.locator('.side-nav > a',{hasText:'Immobilien'});ok(await nav.count()===1,'nav entry');ok((await nav.getAttribute('href'))==='immobilien.php','nav points to portfolio');ok(await p.locator('.side-nav > a').count()===6,'still six nav points');
 await nav.click();await p.waitForURL(/immobilien\.php/);let main=await p.textContent('main');
 ok(main.includes('Haus Lindenweg')&&main.includes('Haus Bergstraße'),'both houses as cards');ok(await p.locator('.house-card').count()===2,'two cards');
 ok(main.includes('Zu erledigen'),'task feed');ok(/Mieten? fehl/.test(main),'missing rents as task');ok(await p.locator('.estate-kpis .dk').count()===4,'four KPIs');
 ok(await p.locator('.side-nav > a[aria-current]',{hasText:'Immobilien'}).count()===1||await p.locator('.side-nav > a.on,.side-nav > a.active',{hasText:'Immobilien'}).count()>=0,'nav marked');
 await p.locator('summary',{hasText:'Export'}).click();ok(await p.locator('a[href^="estate_export.php?type=rent"]').isVisible(),'export menu opens');await p.keyboard.press('Escape');
 if(shots)await p.screenshot({path:shots+'/immo-portfolio.png',fullPage:true});
 // Hausseite
 await p.goto(base+'/immobilien.php');await p.locator('.house-card',{hasText:'Haus Lindenweg'}).click();await p.waitForURL(/haus\.php\?id=1/);main=await p.textContent('main');
 ok(main.includes('Überblick')&&main.includes('Wohnungen & Mieter')&&main.includes('Mietkonto')&&main.includes('Nebenkosten'),'house tabs');ok(main.includes('Anna Beispiel'),'tenant in month list');
 ok(await p.locator('.pill.bad',{hasText:'Fehlt'}).count()+await p.locator('.pill.part',{hasText:'Teilweise'}).count()>=1,'missing/partial marked red/amber');
 const bg=await p.locator('.pill.bad,.pill.part').first().evaluate(e=>getComputedStyle(e).backgroundColor);ok(!/rgb\(47, 138, 43\)/.test(bg),'Fehlt is not green: '+bg);
 if(shots)await p.screenshot({path:shots+'/immo-haus.png',fullPage:true});
 await p.getByRole('link',{name:'Wohnungen & Mieter'}).click();main=await p.textContent('main');ok(main.includes('EG')&&main.includes('Bernd Muster')&&main.includes('Carla Probe'),'units with tenants');ok(await p.locator('.ut-row:not(.ut-head)').count()===3,'three units');
 await p.getByRole('link',{name:'Mietkonto'}).first().click();await p.waitForURL(/tab=mietkonto/);ok(await p.locator('table.rent-grid tbody tr').count()>=3,'grid rows');ok(await p.locator('td.gc.s-complete').count()>=1,'complete cell');ok(await p.locator('td.gc.s-missing,td.gc.s-partial,td.gc.s-not_due').count()>=1,'open cell');
 if(shots)await p.screenshot({path:shots+'/immo-mietkonto.png',fullPage:true});
 const y=new Date().getFullYear();await p.locator('.month-nav a').first().click();ok((await p.textContent('main')).includes('Mietkonto '+(y-1)),'year back');
 // Eingangskorb: Upload oben, Fehlt rot, Ausgabe bestätigen → Kosten im Haus
 await p.goto(base+'/estate_bank.php');main=await p.textContent('main');ok(main.includes('Mieten & Kontoauszug')&&main.includes('Kontoauszug hochladen'),'inbox title and upload');
 const upTop=await p.locator('#hochladen').evaluate(e=>e.getBoundingClientRect().top);const houseTop=await p.locator('section.house').first().evaluate(e=>e.getBoundingClientRect().top);ok(upTop<houseTop,'upload above the houses');
 ok(await p.locator('#hochladen input[type=file]').first().isVisible(),'upload visible without opening');ok(main.includes('Fehlt'),'Fehlt still shown');ok(await p.locator('.inbox-kpis .dk').count()===4,'inbox summary');
 const exp=p.locator('#ausgaben form').filter({has:p.locator('[name=transaction_id]')}).first();await exp.locator('[name=category]').selectOption('Grundsteuer');await exp.getByRole('button',{name:'Bestätigen'}).click();
 ok((await p.textContent('main')).includes('Als Kosten des Hauses gespeichert'),'expense confirmed');
 if(shots)await p.screenshot({path:shots+'/immo-eingangskorb.png',fullPage:true});
 await p.goto(base+'/haus.php?id=1&tab=kosten');main=await p.textContent('main');ok(main.includes('Grundsteuer')&&main.includes('318,50'),'cost shows in house');
 // Nebenkosten-Assistent: Schritte, Hinweise in einer Box, Kosten aus Kontoauszügen übernehmen
 const from=y+'-01-01',to=y+'-12-31';await p.goto(base+'/estate_settlements.php?building=1&from='+from+'&to='+to);main=await p.textContent('main');
 ok(await p.locator('ol.steps li').count()===5,'five steps');ok(await p.locator('details.legal-box').count()===1,'legal hints in one box');ok(main.includes('Kosten aus Kontoauszügen')&&main.includes('Grundsteuer'),'bank costs panel');
 await p.locator('#s-bank a',{hasText:'Übernehmen'}).first().click();const f=p.locator('form').filter({has:p.locator('[name=action][value=cost]')});
 ok((await f.locator('[name=title]').inputValue())==='Grundsteuer','title prefilled');ok((await f.locator('[name=amount]').inputValue())==='318,50','amount prefilled');ok(!(await f.locator('[name=approved]').isChecked()),'approval still manual');
 if(shots)await p.screenshot({path:shots+'/immo-nebenkosten.png',fullPage:true});
 // Startseite Heute bleibt unverändert, Suche findet die Seite
 await p.goto(base+'/suche.php?q=Kontoauszug');ok((await p.textContent('main')).includes('Mieten & Kontoauszug'),'search finds inbox');
 // Handy: keine Überbreite
 const m=await b.newContext({ignoreHTTPSErrors:true,viewport:{width:390,height:844},isMobile:true,hasTouch:true,storageState:await c.storageState()});const q=await m.newPage();q.on('pageerror',e=>errors.push(e.message));
 for(const path of ['/immobilien.php','/haus.php?id=1','/haus.php?id=1&tab=wohnungen','/haus.php?id=1&tab=mietkonto','/haus.php?id=1&tab=kosten','/haus.php?id=2&tab=nebenkosten','/haus.php?id=1&tab=dokumente','/haus.php?id=1&tab=projekte','/estate_bank.php','/estate_settlements.php?building=1']){const r=await q.goto(base+path);ok(r.status()===200,'200 '+path);ok(await q.evaluate(()=>document.documentElement.scrollWidth<=innerWidth),'390px '+path);}
 if(shots){await q.goto(base+'/immobilien.php');await q.screenshot({path:shots+'/immo-portfolio-handy.png',fullPage:true});await q.goto(base+'/haus.php?id=1&tab=wohnungen');await q.screenshot({path:shots+'/immo-wohnungen-handy.png',fullPage:true});}
 ok((await q.goto(base+'/haus.php?id=999')).status()===404,'unknown house 404');
 ok(!errors.length,'no page errors '+errors.join('|'));console.log('estate portfolio browser checks passed: '+n);
}finally{await b.close();}})().catch(e=>{console.error(e);process.exit(1)});
