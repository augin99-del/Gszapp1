const {chromium}=require('playwright');const {execFileSync}=require('child_process');const path=require('path');const fs=require('fs');
// Google-Sichtbarkeit (Search Console) im Browser. Kein Google-Kontakt: Testschlüssel und Ergebniszusammenfassung werden
// lokal erzeugt (synthetisch); geprüft werden Anzeige, Verständlichkeit, Schutz der Zugangsdaten und Löschen.
(async()=>{const base=process.env.GSZ_TEST_URL;if(!base||!/^https:\/\/127\.0\.0\.1:/.test(base))throw Error('Loopback fixture only');let n=0;const ok=(v,s)=>{n++;if(!v)throw Error(s)};
const app=path.resolve(__dirname,'..');const secrets=path.join(app,'private/secrets');const pw='Local-Test-Only-2026!';
const php=code=>execFileSync('php',['-r',`require ${JSON.stringify(app+'/private/gsc.php')};${code}`]).toString();
const b=await chromium.launch({executablePath:'/usr/bin/chromium',args:['--no-sandbox']});const errors=[];
try{const c=await b.newContext({ignoreHTTPSErrors:true,viewport:{width:390,height:844}});const p=await c.newPage();p.on('pageerror',e=>errors.push(e.message));
 await p.goto(base+'/index.php');await p.locator('[name=email]').fill('owner@example.invalid');await p.locator('[name=password]').fill(pw);await p.getByRole('button',{name:/^Anmelden$/}).click();
 await p.goto(base+'/seo.php');let main=await p.textContent('main');ok((await p.locator('h1').textContent())==='Google-Sichtbarkeit'&&main.includes('Google Search Console')&&!main.includes('BLOCKED'),'page explains the free connection, no technical status');
 ok(await p.locator('input[type=file][name=gsc_key]').count()===1&&await p.locator('#search-console [name=owner_password]').count()===1,'upload of the key file needs the Zentrale password');
 await p.locator('summary').filter({hasText:'So richtest du den Zugang ein'}).click();ok((await p.locator('ol.steps li').count())===6,'six setup steps');
 for(const w of [390,1440]){await p.setViewportSize({width:w,height:900});ok(await p.evaluate(()=>document.documentElement.scrollWidth<=innerWidth),'fits '+w);}
 // Verbunden (synthetisch): Schlüssel + Zusammenfassung lokal anlegen
 php(`$k=openssl_pkey_new(['private_key_bits'=>2048,'private_key_type'=>OPENSSL_KEYTYPE_RSA]);openssl_pkey_export($k,$pem);gszGscSave(json_encode(['type'=>'service_account','private_key'=>$pem,'client_email'=>'zentrale@synthetic-project.iam.gserviceaccount.com','token_uri'=>'https://oauth2.googleapis.com/token']),'sc-domain:example.invalid');`);
 const rows=[];for(let i=0;i<30;i++)rows.push({query:'synthetischer begriff '+String(i).padStart(2,'0'),clicks:30-i,impressions:300-i*5,position:1.5+i,previous:i===1?null:2.5+i-(i%3===0?2:0),new:i===1});
 const data={site:'sc-domain:example.invalid',fetched_at:new Date().toISOString(),from:'2026-10-24',to:'2026-11-20',totals:{clicks:465,impressions:6825,clicks_prev:400,impressions_prev:7000},queries:rows,lost:[{query:'verschwundener begriff',impressions:12,position:14.0}],days:[]};
 php(`gszPrivateWrite(gszGscPaths()['data'],${JSON.stringify(JSON.stringify(data))});`);
 await p.goto(base+'/seo.php');main=await p.textContent('main');ok(main.includes('465')&&main.includes('Klicks')&&main.includes('+65 zu den 28 Tagen davor')&&main.includes('6.825'),'totals with comparison in German number format');
 ok(await p.locator('.gsc-list .li').count()===25&&(await p.locator('.gsc-list .li').first().textContent()).includes('synthetischer begriff 00'),'top 25 search terms first');
 ok(await p.locator('.gsc-list .li').filter({hasText:'synthetischer begriff 01'}).locator('.tag').filter({hasText:'neu'}).count()===1,'new term marked');ok(await p.locator('.gsc-list .li').filter({hasText:'synthetischer begriff 03'}).locator('.down').count()===1&&await p.locator('.gsc-list .li').filter({hasText:'synthetischer begriff 02'}).locator('.up').count()===1,'better/worse arrows');
 ok(main.includes('Position = durchschnittlicher Platz bei Google'),'position explained');await p.getByRole('link',{name:/Alle 30 Suchbegriffe/}).click();ok(await p.locator('.gsc-list .li').count()===30,'all terms on request');
 ok(!(await p.content()).includes('PRIVATE KEY'),'key never in the page');ok(!fs.readFileSync(path.join(secrets,'gsc.vault'),'latin1').includes('PRIVATE KEY'),'key encrypted on the server');
 await p.locator('summary').filter({hasText:'Nicht mehr gefunden'}).click();ok((await p.textContent('main')).includes('verschwundener begriff'),'lost terms listed');
 ok(!/\bseo\b.*Fehler/i.test(main),'no error badge');for(const w of [390,1440]){await p.setViewportSize({width:w,height:900});ok(await p.evaluate(()=>document.documentElement.scrollWidth<=innerWidth),'connected view fits '+w);}
 // Systemstatus nennt die Search Console statt „SEO-Onlineabruf“
 await p.goto(base+'/system_check.php');const sc=await p.textContent('main');ok(sc.includes('Google Search Console')&&!sc.includes('SEO-Onlineabruf'),'status row renamed');
 // Löschen nur mit Passwort
 await p.goto(base+'/seo.php');await p.locator('summary').filter({hasText:'Google-Zugang'}).click();let f=p.locator('form').filter({has:p.locator('[name=action][value=gsc_delete]')});await f.locator('[name=confirm_delete]').check();await f.locator('[name=owner_password]').fill('wrong');await f.getByRole('button',{name:'Zugang löschen'}).click();ok((await p.textContent('main')).includes('Zentrale-Passwort stimmt nicht')&&fs.existsSync(path.join(secrets,'gsc.vault')),'wrong password deletes nothing');
 await p.locator('summary').filter({hasText:'Google-Zugang'}).click();f=p.locator('form').filter({has:p.locator('[name=action][value=gsc_delete]')});await f.locator('[name=confirm_delete]').check();await f.locator('[name=owner_password]').fill(pw);await f.getByRole('button',{name:'Zugang löschen'}).click();
 ok((await p.textContent('main')).includes('vom Server gelöscht')&&!fs.existsSync(path.join(secrets,'gsc.vault'))&&!fs.existsSync(path.join(secrets,'gsc-data.json')),'access and data deleted');
 ok(errors.length===0,'no JavaScript errors');console.log(`Search Console browser PASS (${n} checks; synthetic key and data, no Google contact).`);
}finally{await b.close()}})().catch(e=>{console.error(e);process.exit(1)});
