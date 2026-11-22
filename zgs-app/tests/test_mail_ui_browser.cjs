const {chromium}=require('playwright');
// Mail-Bereich (Umbau 22.11.2026): deutsche Ordner, Filter, Vorschau, Lesen, Zuordnen, Aufgabe aus Mail, Neue Mail in einem Schritt.
// Synthetische Daten (tests/dev/screens_fixture.php), kein Postfach: Senden ist serverseitig nicht freigeschaltet und wird abgelehnt.
(async()=>{const base=process.env.GSZ_TEST_URL;if(!base||!/^https:\/\/127\.0\.0\.1:/.test(base))throw Error('Loopback fixture only');let n=0;const ok=(v,s)=>{n++;if(!v)throw Error(s)};
const b=await chromium.launch({executablePath:'/usr/bin/chromium',args:['--no-sandbox']});const errors=[];
try{
 const login=async(p)=>{await p.goto(base+'/index.php');await p.locator('[name=email]').fill('owner@example.invalid');await p.locator('[name=password]').fill('Local-Test-Only-2026!');await p.getByRole('button',{name:/^Anmelden$/}).click();};
 const fits=p=>p.evaluate(()=>document.documentElement.scrollWidth<=innerWidth);
 for(const [w,h] of [[390,844],[820,1180],[1180,820],[1440,900]]){
  const c=await b.newContext({ignoreHTTPSErrors:true,viewport:{width:w,height:h}});const p=await c.newPage();p.on('pageerror',e=>errors.push(e.message));await login(p);
  await p.goto(base+'/mail.php');const main=await p.textContent('main');
  ok(await p.locator('.folder-current').textContent()==='Posteingang','current folder named Posteingang '+w);ok(!/Sent Items|Drafts|Trash|Archive\b/.test(main),'no English folder names '+w);ok(!main.includes('Wochenvorschlag'),'week proposal banner not on Mail '+w);
  await p.locator('.folder-menu summary').click();ok(JSON.stringify(await p.locator('.folder-menu nav a span:first-child').allTextContents())===JSON.stringify(['Posteingang','Gesendet','Entwürfe','Archiv','Spam','Papierkorb']),'German folders in fixed order '+w);await p.locator('.folder-menu summary').click();
  ok(await p.locator('.segmented a').count()===3,'filter Alle/Ungelesen/Wichtig '+w);ok(await p.locator('.mail-item').count()===4,'four inbox mails '+w);
  const first=p.locator('.mail-item').first();ok((await first.locator('.mi-prev').textContent()).includes('Treppenhausreinigung im Haus'),'preview line '+w);ok(await first.locator('.tag').filter({hasText:'Fröbelstraße 19'}).count()===1,'object tag in list '+w);ok(await p.locator('.mail-item.unread').count()===2,'unread marked '+w);
  ok(!(await p.locator('.mail-item').filter({hasText:'Mieter Lindenweg'}).locator('.mi-prev').textContent()).includes('Alte zitierte'),'quoted lines not in preview '+w);
  ok(await fits(p),'list fits '+w);
  await first.click();await p.waitForLoadState();
  ok((await p.locator('.mail-head h2').textContent())==='Treppenhausreinigung Dezember'&&(await p.locator('.mail-from').textContent()).includes('Hausverwaltung Beispiel'),'read view header '+w);
  ok(await p.getByRole('link',{name:'Antworten',exact:true}).isVisible()&&await p.getByRole('button',{name:'Archivieren'}).isVisible()&&await p.getByRole('button',{name:'Löschen'}).isVisible(),'main actions visible '+w);
  const split=w>=1100;ok(await p.locator('.mail-pane-list').isVisible()===split,(split?'list stays beside the mail ':'list hidden while reading ')+w);ok(await p.locator('.back-link').isVisible()===!split,'back link only on narrow '+w);
  ok(await fits(p),'read view fits '+w);
  await p.goto(base+'/compose.php');ok((await p.locator('h1').textContent())==='Neue Mail'&&!(await p.textContent('main')).match(/SMTP|accepted|draft|Versandprotokoll|Serverfreigabe/),'compose without technical words '+w);ok(!await p.locator('[name=cc_address]').isVisible(),'Cc/Bcc folded away '+w);ok(await fits(p),'compose fits '+w);
  await c.close();}
 // Arbeiten mit einer Mail (iPhone)
 const c=await b.newContext({ignoreHTTPSErrors:true,viewport:{width:390,height:844},isMobile:true,hasTouch:true});const p=await c.newPage();p.on('pageerror',e=>errors.push(e.message));await login(p);
 await p.goto(base+'/mail.php?f=important');ok(await p.locator('.mail-item').count()===1&&(await p.locator('.mail-item').textContent()).includes('WEG Musterhof'),'filter Wichtig');
 await p.goto(base+'/mail.php?f=unread');ok(await p.locator('.mail-item').count()===2,'filter Ungelesen');
 await p.goto(base+'/mail.php');await p.locator('.mail-item').filter({hasText:'Mieter Lindenweg'}).tap();
 await p.locator('summary').filter({hasText:'Objekt oder Kunde zuordnen'}).tap();await p.locator('form').filter({has:p.locator('[name=action][value=assign]')}).locator('[name=property_id]').selectOption({label:'Fröbelstraße 19'});await Promise.all([p.waitForNavigation(),p.getByRole('button',{name:'Zuordnen'}).tap()]);ok((await p.textContent('main')).includes('Zugeordnet.')&&await p.locator('.mail-head .tag').filter({hasText:'Fröbelstraße 19'}).count()===1,'assigned to object');
 await p.locator('summary').filter({hasText:'Aufgabe daraus machen'}).tap();const tf=p.locator('form').filter({has:p.locator('[name=action][value=task]')});ok(await tf.locator('[name=title]').inputValue()==='Heizung OG','task title prefilled from subject');ok(await tf.locator('[name=property_id]').inputValue()!=='0','task object preselected');
 await tf.locator('[name=title]').fill('Heizung OG ansehen');await Promise.all([p.waitForNavigation(),tf.getByRole('button',{name:'Aufgabe anlegen'}).tap()]);ok((await p.textContent('main')).includes('Aufgabe angelegt'),'task created from mail');ok(await p.locator('summary').filter({hasText:'Aufgabe daraus machen'}).count()===0&&await p.locator('.mail-head .tag').filter({hasText:'Aufgabe angelegt'}).count()===1,'mail shows the task link');
 await p.goto(base+'/index.php');ok((await p.textContent('main')).includes('Heizung OG ansehen'),'task appears on Heute');
 await p.goto(base+'/mail.php');await p.locator('.mail-item').filter({hasText:'Stadtwerke'}).tap();await p.locator('summary').filter({hasText:'Markieren und verschieben'}).tap();await Promise.all([p.waitForNavigation(),p.getByRole('button',{name:'Als wichtig markieren'}).tap()]);
 await p.goto(base+'/mail.php?f=important');ok(await p.locator('.mail-item').count()===2,'marked as important');
 // Neue Mail: speichern, Rückfrage abbrechen sendet nicht, Senden in einem Schritt
 await p.goto(base+'/compose.php');await p.locator('[name=to_address]').fill('kunde@example.invalid');await p.locator('[name=subject]').fill('Angebot Synthetisch');await p.locator('[name=body]').fill('Synthetischer Text');
 await Promise.all([p.waitForNavigation(),p.getByRole('button',{name:'Als Entwurf sichern'}).tap()]);ok((await p.textContent('main')).includes('Entwurf gespeichert')&&p.url().includes('draft='),'draft saved');
 p.once('dialog',d=>{ok(d.message().includes('kunde@example.invalid'),'confirmation names the recipient');d.dismiss();});await p.getByRole('button',{name:'Senden',exact:true}).tap();await p.waitForTimeout(300);ok(!(await p.textContent('main')).includes('Nicht gesendet'),'cancelled confirmation sends nothing');
 p.once('dialog',d=>d.accept());await Promise.all([p.waitForNavigation(),p.getByRole('button',{name:'Senden',exact:true}).tap()]);ok((await p.textContent('main')).includes('Nicht gesendet'),'send not released on server: plain refusal, nothing claimed as sent');
 await p.goto(base+'/compose.php');ok((await p.locator('.list').first().textContent()).includes('Angebot Synthetisch'),'draft listed under Entwürfe');
 ok(errors.length===0,'no JavaScript errors '+errors.join(' | '));console.log(`Mail UI browser PASS (${n} checks; 390/820/1180/1440, synthetic data, no real mailbox).`);
}finally{await b.close()}})().catch(e=>{console.error(e);process.exit(1)});
