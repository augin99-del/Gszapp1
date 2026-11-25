const {chromium}=require('playwright');const {execFileSync}=require('child_process');const path=require('path');
// Anfragen, Bewertung, Notdienst-Status, Website-Zahlen (Wunsch 25.11.2026). Synthetische Daten.
(async()=>{const base=process.env.GSZ_TEST_URL;if(!base||!/^https:\/\/127\.0\.0\.1:/.test(base))throw Error('Loopback fixture only');let n=0;const ok=(v,s)=>{n++;if(!v)throw Error(s)};
const app=path.resolve(__dirname,'..');const sql=q=>execFileSync('php',['-r',`$p=new PDO(getenv('GSZ_TEST_DSN'),'root','',[PDO::ATTR_ERRMODE=>PDO::ERRMODE_EXCEPTION]);$p->exec(${JSON.stringify(q)});`]).toString();
const body="Neue Anfrage über gebaeudeservice-zentrum.de\\n\\nLeistung: Treppenhausreinigung\\nName: Erika Muster\\nTelefon: 0176 0000000\\nE-Mail: erika@example.invalid\\nPLZ / Ort: 00000 Musterstadt\\nNachricht: 12 Parteien\\nQuellseite: /kontakt/\\n\\n---\\nAutomatisch";
sql(`INSERT INTO mail_messages(folder_id,uidvalidity,remote_uid,message_id,sender,recipients,subject,sent_at,body) SELECT id,1,900,'<lead@example.invalid>','info@example.invalid','info@example.invalid','Neue Website-Anfrage – Treppenhausreinigung',NOW(),REPLACE('${body}','\\\\n',CHAR(10)) FROM mail_folders WHERE name='INBOX' LIMIT 1`);
const b=await chromium.launch({executablePath:'/usr/bin/chromium',args:['--no-sandbox']});const errors=[];
try{const c=await b.newContext({ignoreHTTPSErrors:true,viewport:{width:390,height:844},isMobile:true,hasTouch:true});const p=await c.newPage();p.on('pageerror',e=>errors.push(e.message));
 // Öffentliche Schnittstellen: ohne Anmeldung, ohne Cookies, CORS nur für die eigene Website
 let r=await c.request.get(base+'/web_status.php',{headers:{Origin:'https://gebaeudeservice-zentrum.de'}});let j=await r.json();
 ok(r.status()===200&&j.status==='frei'&&r.headers()['access-control-allow-origin']==='https://gebaeudeservice-zentrum.de'&&!r.headers()['set-cookie'],'status endpoint: public, CORS for the website, no cookie');
 r=await c.request.get(base+'/web_status.php',{headers:{Origin:'https://evil.example'}});ok(!r.headers()['access-control-allow-origin'],'no CORS for foreign sites');
 r=await c.request.post(base+'/web_track.php',{headers:{Origin:'https://gebaeudeservice-zentrum.de','Content-Type':'text/plain'},data:JSON.stringify({p:'/standorte/verl/',e:'call'})});ok(r.status()===204&&!r.headers()['set-cookie'],'track accepted, no cookie');
 await c.request.post(base+'/web_track.php',{headers:{Origin:'https://evil.example','Content-Type':'text/plain'},data:JSON.stringify({p:'/',e:'view'})});
 await c.request.post(base+'/web_track.php',{headers:{Origin:'https://gebaeudeservice-zentrum.de','Content-Type':'text/plain'},data:JSON.stringify({p:'/',e:'view'})});
 // Anmelden; Heute zeigt neue Anfrage und Notdienst-Schalter
 await p.goto(base+'/index.php');await p.locator('[name=email]').fill('owner@example.invalid');await p.locator('[name=password]').fill('Local-Test-Only-2026!');await p.getByRole('button',{name:/^Anmelden$/}).click();
 await p.goto(base+'/index.php');ok((await p.locator('.attention').textContent()).includes('1 neue Anfrage'),'Heute: new lead from the website form mail');
 ok((await p.locator('.sos-bar').textContent()).includes('Jetzt erreichbar'),'emergency status shown');await p.locator('.sos-form select').selectOption('40');await Promise.all([p.waitForNavigation(),p.locator('.sos-form').getByRole('button',{name:'Im Einsatz'}).tap()]);
 ok((await p.locator('.sos-bar').textContent()).includes('Rückruf ca. 40 Min.'),'switched to busy');j=await (await c.request.get(base+'/web_status.php')).json();ok(j.status==='einsatz'&&j.text.includes('40 Min'),'website sees busy');
 await Promise.all([p.waitForNavigation(),p.locator('.sos-form').getByRole('button',{name:'Wieder frei'}).tap()]);ok((await (await c.request.get(base+'/web_status.php')).json()).status==='frei','back to free');
 // Anfragen
 await p.locator('.attention a').filter({hasText:'neue Anfrage'}).tap();let lead=p.locator('.card.lead').filter({hasText:'Erika Muster'});
 ok((await lead.textContent()).includes('Treppenhausreinigung')&&(await lead.textContent()).includes('12 Parteien')&&await lead.locator('a[href^="tel:"]').count()===1&&await lead.locator('a[href^="https://wa.me/49176"]').count()===1,'lead card with call and WhatsApp');
 await Promise.all([p.waitForNavigation(),lead.getByRole('button',{name:'Als Aufgabe für heute'}).tap()]);ok((await p.textContent('main')).includes('Als Aufgabe für heute angelegt'),'task created');
 lead=p.locator('.card.lead').filter({hasText:'Erika Muster'});await Promise.all([p.waitForNavigation(),lead.getByRole('button',{name:'Auftrag'}).tap()]);
 await p.goto(base+'/leads.php?status=auftrag');lead=p.locator('.card.lead').filter({hasText:'Erika Muster'});ok(await lead.locator('button',{hasText:'Bewertung per WhatsApp'}).count()===1,'review button for won jobs');
 const form=lead.locator('form.review-row');const csrfv=await form.locator('[name=csrf]').inputValue();const idv=await form.locator('[name=id]').inputValue();
 r=await c.request.post(base+'/leads.php',{form:{csrf:csrfv,action:'review',id:idv,view:'auftrag'},maxRedirects:0});const loc=r.headers()['location']||'';
 ok(r.status()===303&&loc.startsWith('https://wa.me/491760000000?text=')&&decodeURIComponent(loc).includes('Hallo Erika,')&&decodeURIComponent(loc).includes('https://share.google/'),'review opens WhatsApp with text and Google link');
 await p.reload();ok((await p.locator('.card.lead').filter({hasText:'Erika Muster'}).textContent()).includes('angefragt am'),'review request remembered');
 // Website-Zahlen
 await p.goto(base+'/more.php');await p.getByRole('link',{name:/Website: Aufrufe/}).tap();const m=await p.textContent('main');ok(m.includes('Seitenaufrufe')&&m.includes('Ort: Verl')&&await p.locator('.kpi strong').first().textContent()==='1','stats page: 1 view (foreign origin not counted), Verl call');
 for(const u of ['/leads.php','/website.php','/index.php'])for(const w of [390,1440]){await p.setViewportSize({width:w,height:900});await p.goto(base+u);ok(await p.evaluate(()=>document.documentElement.scrollWidth<=innerWidth),'fits '+w+' '+u);}
 ok(errors.length===0,'no JavaScript errors: '+errors.join(' | '));console.log(`Anfragen browser PASS (${n} checks; synthetic data).`);
}finally{await b.close()}})().catch(e=>{console.error(e);process.exit(1)});
