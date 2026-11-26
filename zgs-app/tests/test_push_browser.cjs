const {chromium}=require('playwright');const {execFileSync}=require('child_process');const crypto=require('crypto');
// Schema 440: Benachrichtigungen, Wecker, DWD-Warnung → Winterdienst. Synthetische Daten, kein echter Push-Dienst.
(async()=>{const base=process.env.GSZ_TEST_URL;if(!base||!/^https:\/\/127\.0\.0\.1:/.test(base))throw Error('Loopback fixture only');let n=0;const ok=(v,s)=>{n++;globalThis.__last=s;if(!v)throw Error(s)};
const q=sql=>JSON.parse(execFileSync('php',['-r',`$p=new PDO(getenv('GSZ_TEST_DSN'),'root','',[PDO::ATTR_ERRMODE=>PDO::ERRMODE_EXCEPTION,PDO::ATTR_DEFAULT_FETCH_MODE=>PDO::FETCH_ASSOC]);$s=$p->query(${JSON.stringify(sql)});echo json_encode($s->columnCount()?$s->fetchAll():[]);`]).toString());
const pid=q('SELECT id FROM properties ORDER BY id LIMIT 1')[0].id;
q("INSERT INTO app_settings(setting_key,setting_value) VALUES('weather_checked',UTC_TIMESTAMP()) ON DUPLICATE KEY UPDATE setting_value=VALUES(setting_value)");
q("INSERT INTO weather_alerts(alert_id,event_code,event,headline,description,severity,onset,expires,area,winter) VALUES('dwd-test-glaette',84,'GLÄTTE','Amtliche WARNUNG vor GLÄTTE','Es tritt Glätte auf.','minor',UTC_TIMESTAMP()+INTERVAL 8 HOUR,UTC_TIMESTAMP()+INTERVAL 16 HOUR,'Gütersloh',2),('dwd-test-frost',22,'FROST','Amtliche WARNUNG vor FROST','Leichter Frost.','minor',UTC_TIMESTAMP(),UTC_TIMESTAMP()+INTERVAL 10 HOUR,'Gütersloh',1)");
q(`INSERT INTO recurring_jobs(property_id,title,frequency,active,next_due_date) VALUES(${pid},'Winterdienst','seasonal',1,CURDATE()+INTERVAL 30 DAY)`);
const b=await chromium.launch({executablePath:'/usr/bin/chromium',args:['--no-sandbox']});const errors=[];
try{const c=await b.newContext({ignoreHTTPSErrors:true,viewport:{width:390,height:844},isMobile:true,hasTouch:true});const p=await c.newPage();p.on('pageerror',e=>errors.push(e.message));
 await p.goto(base+'/index.php');await p.locator('[name=email]').fill('owner@example.invalid');await p.locator('[name=password]').fill('Local-Test-Only-2026!');await p.getByRole('button',{name:/^Anmelden$/}).click();
 await p.goto(base+'/index.php');const wx=p.locator('#wetter');ok((await wx.textContent()).includes('Glätte · Gütersloh')&&(await wx.textContent()).includes('Frost'),'Heute shows ice warning and frost hint');
 ok(await p.locator('link[rel=manifest]').count()===1&&(await c.request.get(base+'/manifest.json')).status()===200&&(await c.request.get(base+'/sw.js')).status()===200,'app manifest and service worker served');
 await Promise.all([p.waitForNavigation(),wx.getByRole('button',{name:/Winterdienst für 1 Objekt einplanen/}).tap()]);ok((await p.textContent('main')).includes('1 Winterdienst-Einsätze eingeplant')&&(await p.locator('#wetter').textContent()).includes('Winterdienst eingeplant'),'winter service planned from Heute');
 ok(q("SELECT COUNT(*) n FROM tasks WHERE title='Winterdienst – Glätte' AND priority='urgent'")[0].n==1,'urgent task created');
 // Benachrichtigungen
 await p.goto(base+'/more.php');await p.getByRole('link',{name:/Benachrichtigungen, Glättewarnung/}).tap();await p.waitForURL(/benachrichtigungen\.php/);
 const box=p.locator('[data-push]');ok(Buffer.from(await box.getAttribute('data-key'),'base64url').length===65&&await p.getByRole('button',{name:'Benachrichtigungen auf diesem Gerät aktivieren'}).count()===1,'page with device button and VAPID key');
 const ua=crypto.createECDH('prime256v1');ua.generateKeys();const sub={endpoint:'https://push.example.invalid/send/'+crypto.randomBytes(8).toString('hex'),keys:{p256dh:ua.getPublicKey().toString('base64url'),auth:crypto.randomBytes(16).toString('base64url')}};
 const res=await p.evaluate(async([sub,csrf])=>{const f=new FormData();f.append('csrf',csrf);f.append('action','subscribe');f.append('sub',JSON.stringify(sub));f.append('label','iPhone · Safari');const r=await fetch('benachrichtigungen.php',{method:'POST',body:f,headers:{'X-Requested-With':'fetch'}});return [r.status,await r.text()];},[sub,await box.getAttribute('data-csrf')]);
 ok(res[0]===200&&res[1].includes('"ok":true'),'device subscription saved (as the browser would)');await p.reload();ok((await p.locator('#geraete').textContent()).includes('iPhone · Safari'),'device listed');
 await Promise.all([p.waitForNavigation(),p.getByRole('button',{name:'Test-Benachrichtigung senden'}).tap()]);ok((await p.textContent('main')).includes('Kein Gerät hat den Test angenommen')&&(await p.locator('#geraete').textContent()).includes('Problem: nicht erreichbar'),'unreachable push service reported honestly');
 const kinds=p.locator('form').filter({has:p.locator('[name=push_leads]')});await kinds.locator('[name=push_reports]').uncheck();await Promise.all([p.waitForNavigation(),kinds.getByRole('button',{name:'Speichern'}).tap()]);ok(q("SELECT setting_value v FROM app_settings WHERE setting_key='push_reports'")[0].v==='0','kinds saved');
 // Wecker
 await Promise.all([p.waitForNavigation(),p.getByRole('button',{name:'Wecker-Adresse erzeugen'}).tap()]);const url=await p.locator('#tick').inputValue();ok(/\/tick\.php\?k=[a-f0-9]{48}$/.test(url),'secret wake-up address');
 const k=new URL(url).searchParams.get('k');let r=await c.request.get(base+'/tick.php?k='+'0'.repeat(48));ok(r.status()===403,'wrong key refused');
 r=await c.request.get(base+'/tick.php?k='+k);const j=await r.json();ok(r.status()===200&&j.ok===true&&!r.headers()['set-cookie'],'wake-up runs without login/cookie: '+JSON.stringify(j));
 await p.reload();ok(!(await p.locator('#wecker').textContent()).includes('noch nie'),'last wake-up shown');
 await Promise.all([p.waitForNavigation(),p.locator('#geraete').getByRole('button',{name:'Entfernen'}).tap()]);ok(q('SELECT COUNT(*) n FROM push_subscriptions')[0].n==0,'device removed');
 for(const u of ['/benachrichtigungen.php','/index.php'])for(const w of [390,1440]){await p.setViewportSize({width:w,height:900});await p.goto(base+u);ok(await p.evaluate(()=>document.documentElement.scrollWidth<=innerWidth),'fits '+w+' '+u);}
 ok(errors.length===0,'no JavaScript errors: '+errors.join(' | '));console.log(`Push browser PASS (${n} checks; synthetic data, no real push service).`);
}finally{await b.close()}})().catch(e=>{console.error('Error after: '+globalThis.__last);console.error(e);process.exit(1)});
