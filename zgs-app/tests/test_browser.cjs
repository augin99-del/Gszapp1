const {chromium}=require('playwright');
(async()=>{const browser=await chromium.launch({executablePath:'/usr/bin/chromium',headless:true,args:['--no-sandbox']});const ctx=await browser.newContext({ignoreHTTPSErrors:true,viewport:{width:390,height:844}});const page=await ctx.newPage();let n=0;function ok(b,l){n++;if(!b)throw Error(l)}
const base=process.env.GSZ_TEST_URL||'https://127.0.0.1:8443';
await page.goto(base+'/object_import.php');ok(page.url().includes('index.php'),'anonymous redirected');
await page.locator('[name=email]').fill('owner@example.invalid');await page.locator('[name=password]').fill('Local-Test-Only-2026!');await page.getByRole('button',{name:/Sicher anmelden/}).click();
await page.goto(base+'/object_import.php?start=2035-01-01');ok(await page.locator('section.card').count()===7,'seven preview objects');
await page.locator('[name=confirm]').check();await page.getByRole('button',{name:'Geprüfte Objekte und Regeln importieren'}).click();ok((await page.textContent('body')).includes('7 Objekte und 28 Regeln neu'),'import');
await page.locator('[name=confirm]').check();await page.getByRole('button',{name:'Geprüfte Objekte und Regeln importieren'}).click();ok((await page.textContent('body')).includes('0 Objekte und 0 Regeln neu'),'repeat import');
await page.goto(base+'/recurring.php');const row=page.locator('div.item').filter({has:page.locator('strong',{hasText:'Treppenhaus reinigen'})}).first();await row.getByRole('link',{name:'Bearbeiten',exact:true}).click();await page.locator('[name=duration_minutes]').fill('85');await page.locator('[name=frequency]').selectOption('biweekly');await page.getByRole('button',{name:'Leistung speichern'}).click();ok((await page.textContent('body')).includes('85 Min.'),'duration editable');
const response=await ctx.request.post(base+'/object_import.php',{form:{confirm:'yes',start:'2035-01-01',csrf:'invalid'}});ok(response.status()===403,'csrf rejected');
ok(await page.evaluate(()=>document.documentElement.scrollWidth<=window.innerWidth),'mobile width');await page.screenshot({path:process.env.GSZ_TEST_SCREENSHOT||'/tmp/gsz-mobile.png',fullPage:true});
await page.setViewportSize({width:1440,height:900});await page.goto(base+'/object_import.php');ok(await page.evaluate(()=>document.documentElement.scrollWidth<=innerWidth),'desktop width');
await browser.close();console.log(`Browser tests passed (${n} checks, mobile and desktop).`);
})().catch(e=>{console.error(e);process.exit(1)});
