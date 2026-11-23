const {chromium}=require('playwright');const {execFileSync}=require('child_process');const path=require('path');
// Ferienwohnung im Browser (Wunsch 23.11.2026): Airbnb-/Booking-Kalender → Reinigung am Abreisetag. Synthetische Daten;
// der echte Abruf ist im Test nicht erreichbar (Fehleranzeige wird geprüft), die Belegung wird lokal mit einem Testkalender geplant.
(async()=>{const base=process.env.GSZ_TEST_URL;if(!base||!/^https:\/\/127\.0\.0\.1:/.test(base))throw Error('Loopback fixture only');let n=0;const ok=(v,s)=>{n++;if(!v)throw Error(s)};
const app=path.resolve(__dirname,'..');const php=code=>execFileSync('php',['-r',`require ${JSON.stringify(app+'/private/stays.php')};$p=new PDO(getenv('GSZ_TEST_DSN'),'root','',[PDO::ATTR_ERRMODE=>PDO::ERRMODE_EXCEPTION,PDO::ATTR_DEFAULT_FETCH_MODE=>PDO::FETCH_ASSOC]);${code}`]).toString();
const b=await chromium.launch({executablePath:'/usr/bin/chromium',args:['--no-sandbox']});const errors=[];
try{const c=await b.newContext({ignoreHTTPSErrors:true,viewport:{width:390,height:844},isMobile:true,hasTouch:true});const p=await c.newPage();p.on('pageerror',e=>errors.push(e.message));
 await p.goto(base+'/index.php');await p.locator('[name=email]').fill('owner@example.invalid');await p.locator('[name=password]').fill('Local-Test-Only-2026!');await p.getByRole('button',{name:/^Anmelden$/}).click();
 await p.goto(base+'/objects.php');await p.getByRole('link',{name:/Diekstraße 3/}).first().click();const pid=new URL(p.url()).searchParams.get('id');ok(pid,'object page');
 let sec=p.locator('#ferienwohnung');ok((await sec.textContent()).includes('Airbnb oder Booking.com'),'offer to set up a holiday flat');
 await sec.locator('summary').filter({hasText:'Als Ferienwohnung einrichten'}).tap();ok(await sec.locator('[name=checkout_time]').inputValue()==='10:00'&&await sec.locator('[name=checkin_time]').inputValue()==='15:00'&&await sec.locator('[name=duration_minutes]').inputValue()==='60','defaults 10:00 / 15:00 / 60 min');
 await Promise.all([p.waitForNavigation(),sec.getByRole('button',{name:'Einrichten'}).tap()]);ok((await p.textContent('main')).includes('Kalender-Link von Airbnb bzw. Booking.com eintragen'),'set up');
 sec=p.locator('#ferienwohnung');ok(await sec.locator('details').filter({hasText:'Kalender-Link hinzufügen'}).getAttribute('open')!==null,'link form open while no calendar');
 await sec.locator('[name=calendar_url]').fill('https://example.invalid/kalender.ics');await Promise.all([p.waitForNavigation(),sec.getByRole('button',{name:'Verbinden'}).tap()]);ok((await p.textContent('main')).includes('kein Kalender-Link von Airbnb oder Booking.com'),'foreign link refused');
 sec=p.locator('#ferienwohnung');await sec.locator('[name=calendar_url]').fill('https://www.airbnb.de/calendar/ical/111.ics?s=synthetischgeheim');await Promise.all([p.waitForNavigation({timeout:60000}),sec.getByRole('button',{name:'Verbinden'}).tap()]);
 let main=await p.textContent('main');ok(main.includes('Airbnb-Kalender gespeichert')||main.includes('Airbnb-Kalender verbunden'),'airbnb link saved');ok(!(await p.content()).includes('synthetischgeheim')&&(await p.locator('#ferienwohnung .stay-cals').textContent()).includes('www.airbnb.de …'),'full link never shown again');
 // Belegung lokal planen (synthetischer Kalender, Gästewechsel morgen, Abreise in 3 Tagen)
 php(`$t=new DateTimeImmutable('today',new DateTimeZone('Europe/Berlin'));$f=fn($k)=>$t->modify($k.' days')->format('Ymd');$ics="BEGIN:VCALENDAR\\r\\n".implode('',array_map(fn($x)=>"BEGIN:VEVENT\\r\\nDTSTART;VALUE=DATE:".$f($x[0])."\\r\\nDTEND;VALUE=DATE:".$f($x[1])."\\r\\nSUMMARY:Reserved\\r\\nEND:VEVENT\\r\\n",[[-2,1],[1,3]]))."END:VCALENDAR\\r\\n";gszStaySync($p,1,${pid},fn($u,$h)=>$ics);`);
 await p.reload();sec=p.locator('#ferienwohnung');const up=await sec.textContent();ok(up.includes('Nächste Reinigungen')&&up.includes('Gästewechsel – bis 15:00 fertig')&&up.includes('Abreise 10:00, danach frei'),'upcoming cleanings with turnover');
 ok((await sec.locator('.stay-cals').textContent()).includes('2 Buchungen'),'calendar status after fetch');
 const tomorrow=await p.evaluate(()=>{const d=new Date();d.setDate(d.getDate()+1);return d.toISOString().slice(0,10)});
 await p.goto(base+'/planner.php?date='+tomorrow);const blk=p.locator('article.block').filter({hasText:'Reinigung Ferienwohnung – neue Gäste 15:00'});ok(await blk.count()===1&&(await blk.textContent()).includes('10:00'),'cleaning in the day plan at 10:00');
 await blk.locator('.acts button.check').tap();await p.waitForFunction(()=>/erledigt/.test(document.getElementById('gsz-toast')?.textContent||''));ok(await p.locator('#gsz-toast').getByRole('button',{name:'Rechnung merken'}).count()===0,'own flat: no invoice question');
 await p.goto(base+'/system_check.php');ok((await p.textContent('main')).includes('Ferienwohnung-Kalender'),'status lists the calendar job');
 for(const w of [390,1440]){await p.setViewportSize({width:w,height:900});await p.goto(base+'/property.php?id='+pid);ok(await p.evaluate(()=>document.documentElement.scrollWidth<=innerWidth),'fits '+w);}
 // Beenden
 await p.goto(base+'/property.php?id='+pid);p.once('dialog',d=>d.accept());await Promise.all([p.waitForNavigation(),p.locator('#ferienwohnung').getByRole('button',{name:'Ferienwohnung beenden'}).tap()]);ok((await p.textContent('main')).includes('Ferienwohnung beendet'),'can be ended');
 ok(errors.length===0,'no JavaScript errors: '+errors.join(' | '));console.log(`Ferienwohnung browser PASS (${n} checks; synthetic data, no Airbnb/Booking contact).`);
}finally{await b.close()}})().catch(e=>{console.error(e);process.exit(1)});
