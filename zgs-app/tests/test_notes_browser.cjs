const {chromium}=require('playwright');
// Merkliste im Browser (Wunsch 23.11.2026): „+“ schnell notieren mit Objekterkennung, To-dos auf Heute/Objektseite,
// „Rechnung merken?“ nach dem Abhaken, Liste „Rechnungen offen“, Einkaufsliste, Foto. Synthetische Daten (tests/dev/screens_fixture.php).
(async()=>{const base=process.env.GSZ_TEST_URL;if(!base||!/^https:\/\/127\.0\.0\.1:/.test(base))throw Error('Loopback fixture only');let n=0;const ok=(v,s)=>{n++;if(!v)throw Error(s)};
const b=await chromium.launch({executablePath:'/usr/bin/chromium',args:['--no-sandbox']});const errors=[];
const png=Buffer.from('iVBORw0KGgoAAAANSUhEUgAAAAgAAAAICAIAAABLbSncAAAAEklEQVR4nGP4z8CAFWEXHbQSACj/P8Fu7N9hAAAAAElFTkSuQmCC','base64');
try{const c=await b.newContext({ignoreHTTPSErrors:true,viewport:{width:390,height:844},isMobile:true,hasTouch:true});const p=await c.newPage();p.on('pageerror',e=>errors.push(e.message));
 await p.goto(base+'/index.php');await p.locator('[name=email]').fill('owner@example.invalid');await p.locator('[name=password]').fill('Local-Test-Only-2026!');await p.getByRole('button',{name:/^Anmelden$/}).click();
 await p.goto(base+'/index.php');ok((await p.locator('#todos').textContent()).includes('To-dos')&&await p.locator('header a.top-add').isVisible()&&!(await p.locator('a.fab').isVisible()),'Heute shows To-dos; + Notieren in the header on the phone');
 // „+“ → Dialog, Objekt wird aus dem Text erkannt (Tippfehler Fontaine…)
 await p.locator('header a.top-add').tap();const dlg=p.locator('dialog#quick-note');ok(await dlg.isVisible(),'+ opens the quick dialog');
 await dlg.locator('[name=title]').fill('Loch Wand Fontainestraße');await dlg.locator('summary').filter({hasText:'Objekt, Material'}).tap();await dlg.locator('[name=material]').fill('Spachtelmasse');
 await Promise.all([p.waitForNavigation(),dlg.getByRole('button',{name:'Merken'}).tap()]);ok(new URL(p.url()).pathname.endsWith('/index.php'),'back on the page it was opened from');
 ok((await p.textContent('main')).includes('Notiert · Objekt: Fontänenstraße 29–31'),'object recognised and confirmed');
 let todo=p.locator('#todos ~ .blocks.notes .block').filter({hasText:'Loch Wand Fontainestraße'});ok(await todo.count()===1&&(await todo.textContent()).includes('Fontänenstraße 29–31')&&(await todo.textContent()).includes('Spachtelmasse'),'todo on Heute with object and material');
 // Abhaken ohne Neuladen → „Rechnung merken“
 await p.evaluate(()=>window.__noReload=1);await todo.locator('button.check').tap();const toast=p.locator('#gsz-toast');await toast.getByRole('button',{name:'Rechnung merken'}).waitFor();
 ok((await toast.textContent()).includes('Rechnung nötig?')&&await toast.getByRole('button',{name:'Rückgängig'}).count()===1,'toast asks: invoice needed? with undo');
 await toast.getByRole('button',{name:'Rechnung merken'}).tap();await p.waitForFunction(()=>(document.getElementById('gsz-toast')?.textContent||'').includes('gemerkt'));ok(await p.evaluate(()=>window.__noReload===1),'no reload');
 await p.reload();ok((await p.locator('.attention').textContent()).includes('1 Rechnung noch schreiben (Lexware)'),'Heute reminds to write the invoice');
 // Rechnungen offen: zweite Erinnerung direkt notieren, Notiz-Zeile, abhaken
 await p.goto(base+'/notes.php?view=rechnungen');let main=await p.textContent('main');ok(main.includes('Loch Wand Fontainestraße')&&main.includes('Lexware'),'invoice list explains Lexware');
 const qf=p.locator('#neu form');ok(await qf.locator('[name=kind][value=invoice]').isChecked(),'invoice preselected on that tab');ok(!(await qf.locator('fieldset.when').isVisible()),'no date choice for invoices');
 await qf.locator('[name=title]').fill('Fröbelstraße Beleuchtung');await qf.locator('summary').tap();await qf.locator('[name=note]').fill('3 Std. + 2 Leuchtmittel');await Promise.all([p.waitForNavigation(),qf.getByRole('button',{name:'Merken'}).tap()]);
 let inv=p.locator('.block.note').filter({hasText:'Fröbelstraße Beleuchtung'});ok((await inv.textContent()).includes('Fröbelstraße 19')&&(await inv.textContent()).includes('3 Std. + 2 Leuchtmittel'),'invoice reminder with object and note line');
 ok((await p.locator('.seg-tabs a[aria-current=page]').textContent()).includes('2'),'tab counts open invoices');
 await inv.locator('button.check').tap();await p.waitForFunction(()=>(document.getElementById('gsz-toast')?.textContent||'').includes('Rechnung geschrieben'));ok(await p.locator('#gsz-toast').getByRole('button',{name:'Rechnung merken'}).count()===0,'writing an invoice does not ask again');
 await p.reload();ok((await p.locator('details.card summary').filter({hasText:'Geschrieben'}).textContent()).includes('(1)'),'written invoices stay visible for 30 days');
 // To-do mit Datum, Material und Foto auf der Merkliste; Einkaufsliste
 await p.goto(base+'/notes.php');const tf=p.locator('#neu form');ok(await tf.locator('[name=kind][value=todo]').isChecked(),'todo preselected');
 await tf.locator('[name=title]').fill('Diekstraße Briefkasten richten');await tf.locator('label').filter({hasText:'Morgen'}).tap();await tf.locator('summary').tap();await tf.locator('[name=material]').fill('Scharnier');
 await tf.locator('[name=photo]').setInputFiles({name:'foto.png',mimeType:'image/png',buffer:png});await Promise.all([p.waitForNavigation(),tf.getByRole('button',{name:'Merken'}).tap()]);
 let t2=p.locator('.block.note').filter({hasText:'Diekstraße Briefkasten richten'});ok((await t2.textContent()).includes('morgen')&&(await t2.textContent()).includes('Diekstraße 3'),'dated todo with object');
 ok(await t2.locator('.note-photo img').evaluate(i=>i.complete&&i.naturalWidth===8),'photo stored and shown');
 await p.goto(base+'/index.php');ok(await p.locator('.block.note').filter({hasText:'Briefkasten'}).count()===0,'tomorrow\'s todo not yet on Heute');
 await p.goto(base+'/notes.php?view=einkauf');let shop=p.locator('.shopping .block').filter({hasText:'Scharnier'});ok((await shop.textContent()).includes('Diekstraße Briefkasten richten'),'material on the shopping list');
 await shop.locator('button.check').tap();await p.waitForFunction(()=>(document.getElementById('gsz-toast')?.textContent||'').includes('besorgt'));ok(await shop.evaluate(e=>e.classList.contains('is-done')),'ticked as bought');
 // Bearbeiten: Objekt wechseln
 await p.goto(base+'/notes.php');t2=p.locator('.block.note').filter({hasText:'Briefkasten'});await t2.locator('summary').filter({hasText:'Bearbeiten'}).tap();
 await t2.locator('select[name=property_id]').selectOption({label:'Berliner Straße 45'});await Promise.all([p.waitForNavigation(),t2.getByRole('button',{name:'Speichern'}).tap()]);ok((await p.locator('.block.note').filter({hasText:'Briefkasten'}).textContent()).includes('Berliner Straße 45'),'object changed');
 // Objektseite: To-dos des Objekts + Notieren mit festem Objekt
 const pid=await p.locator('.block.note').filter({hasText:'Briefkasten'}).locator('a[href^="property.php"]').getAttribute('href');await p.goto(base+'/'+pid);
 ok((await p.locator('#todos ~ .blocks.notes').first().textContent()).includes('Briefkasten'),'todo on its object page');
 await p.locator('summary').filter({hasText:'To-do für dieses Objekt notieren'}).tap();const pf=p.locator('details.card form.quick-note');await pf.locator('[name=title]').fill('Hecke kontrollieren');await Promise.all([p.waitForNavigation(),pf.getByRole('button',{name:'Merken'}).tap()]);
 ok(p.url().includes(pid)&&(await p.textContent('main')).includes('Objekt: Berliner Straße 45'),'object page note keeps its object');
 // Einmalige Aufgabe aus dem Tagesplan abhaken → „Rechnung merken“; Abfall-/Regelaufgaben fragen nicht
 await p.goto(base+'/index.php');const plan=p.locator('.blocks:not(.notes) .block:not(.is-done):not(.is-appointment)');
 const wasteBlk=plan.filter({hasText:/Restmüll|Biotonne|Tonnen/}).first();await wasteBlk.locator('button.check').tap();await p.waitForFunction(()=>/erledigt/.test((document.getElementById('gsz-toast')?.textContent||'')));
 ok(await p.locator('#gsz-toast').getByRole('button',{name:'Rechnung merken'}).count()===0,'waste blocks do not ask for an invoice');
 const oneOff=plan.filter({hasText:'Außenanlage pflegen'}).first();await oneOff.locator('button.check').tap();await p.locator('#gsz-toast').getByRole('button',{name:'Rechnung merken'}).waitFor();
 await p.locator('#gsz-toast').getByRole('button',{name:'Rechnung merken'}).tap();await p.waitForFunction(()=>(document.getElementById('gsz-toast')?.textContent||'').includes('gemerkt'));
 await p.goto(base+'/notes.php?view=rechnungen');ok((await p.locator('.block.note').filter({hasText:'Außenanlage pflegen'}).textContent()).includes('Berliner Straße 45'),'one-off task from the day plan remembered for invoicing with its object');
 await p.setViewportSize({width:1440,height:900});await p.goto(base+'/index.php');ok(await p.locator('a.fab').isVisible()&&!(await p.locator('header a.top-add').isVisible()),'+ bottom right on the computer');
 // Layout
 for(const path of ['/index.php','/notes.php','/notes.php?view=rechnungen','/notes.php?view=einkauf']){for(const w of [390,1440]){await p.setViewportSize({width:w,height:900});await p.goto(base+path);ok(await p.evaluate(()=>document.documentElement.scrollWidth<=innerWidth),'fits '+w+' '+path);}}
 ok(errors.length===0,'no JavaScript errors: '+errors.join(' | '));console.log(`Merkliste browser PASS (${n} checks; synthetic data).`);
}finally{await b.close()}})().catch(e=>{console.error(e);process.exit(1)});
