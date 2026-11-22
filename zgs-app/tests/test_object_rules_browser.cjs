const {chromium}=require('playwright');
// Regelmäßige Arbeiten am Objekt (Wunsch 22.11.2026: „Treppenhausreinigung wöchentlich“ schon beim Anlegen einstellen).
// Synthetische Daten (tests/dev/screens_fixture.php).
(async()=>{const base=process.env.GSZ_TEST_URL;if(!base||!/^https:\/\/127\.0\.0\.1:/.test(base))throw Error('Loopback fixture only');let n=0;const ok=(v,s)=>{n++;if(!v)throw Error(s)};
const b=await chromium.launch({executablePath:'/usr/bin/chromium',args:['--no-sandbox']});const errors=[];
try{const c=await b.newContext({ignoreHTTPSErrors:true,viewport:{width:390,height:844},isMobile:true,hasTouch:true});const p=await c.newPage();p.on('pageerror',e=>errors.push(e.message));
 await p.goto(base+'/index.php');await p.locator('[name=email]').fill('owner@example.invalid');await p.locator('[name=password]').fill('Local-Test-Only-2026!');await p.getByRole('button',{name:/^Anmelden$/}).click();
 await p.goto(base+'/objects.php');await p.locator('summary').filter({hasText:'Neues Objekt'}).tap();const f=p.locator('form').filter({has:p.locator('[name=action][value=property]')});
 await f.locator('[name=name]').fill('Synthetikweg 5');await f.locator('[name=address]').fill('Synthetikweg 5, 00000 Teststadt');await f.locator('[name=rule_title]').fill('Treppenhausreinigung');ok(await f.locator('[name=rule_frequency]').inputValue()==='weekly','weekly preselected');
 await Promise.all([p.waitForNavigation(),f.getByRole('button',{name:'Objekt anlegen'}).tap()]);ok(p.url().includes('property.php?id='),'new object opens its page');
 const sec=p.locator('#regelmaessig');ok((await sec.textContent()).includes('Treppenhausreinigung')&&(await sec.textContent()).includes('wöchentlich')&&(await sec.textContent()).includes('nächstes Mal'),'weekly stairwell cleaning set up while creating the object');
 const id=new URL(p.url()).searchParams.get('id');
 // Aufgabe erscheint in den nächsten Tagen (Treppenhaus: Mo–Mi)
 let found=false;for(let d=0;d<8&&!found;d++){const day=new Date(Date.now()+d*864e5).toLocaleDateString('sv-SE',{timeZone:'Europe/Berlin'});await p.goto(base+'/planner.php?date='+day);found=await p.locator('article.block').filter({hasText:'Synthetikweg 5'}).filter({hasText:'Treppenhausreinigung'}).count()>0;}
 ok(found,'cleaning appears as a block within the next week');
 // weitere Arbeit am bestehenden Objekt
 await p.goto(base+'/property.php?id='+id);await p.locator('#regelmaessig summary').filter({hasText:'Regelmäßige Arbeit hinzufügen'}).tap();const a=p.locator('#regelmaessig form').filter({has:p.locator('[name=action][value=rule_add]')});
 await a.locator('[name=rule_title]').fill('Fenster reinigen');await a.locator('[name=rule_frequency]').selectOption('monthly');await a.locator('[name=rule_duration]').fill('90');await Promise.all([p.waitForNavigation(),a.getByRole('button',{name:'Hinzufügen'}).tap()]);
 ok((await p.textContent('main')).includes('Regelmäßige Arbeit angelegt')&&(await p.locator('#regelmaessig').textContent()).includes('Fenster reinigen')&&(await p.locator('#regelmaessig').textContent()).includes('monatlich · ')&&(await p.locator('#regelmaessig').textContent()).includes('90 Min.'),'second job on existing object');
 const row=p.locator('#regelmaessig .li').filter({hasText:'Fenster reinigen'});await Promise.all([p.waitForNavigation(),row.getByRole('button',{name:'Pausieren'}).tap()]);ok((await p.locator('#regelmaessig .li').filter({hasText:'Fenster reinigen'}).textContent()).includes('pausiert'),'job can be paused');
 ok(await p.evaluate(()=>document.documentElement.scrollWidth<=innerWidth),'object page fits 390');ok(errors.length===0,'no JavaScript errors');
 console.log(`Object rules browser PASS (${n} checks; synthetic object, 390 px).`);
}finally{await b.close()}})().catch(e=>{console.error(e);process.exit(1)});
