const {chromium}=require('playwright');
(async()=>{
 const base=process.env.GSZ_TEST_URL||'https://127.0.0.1:8464';if(new URL(base).hostname!=='127.0.0.1')throw Error('Local synthetic test service required');
 let n=0;const ok=(v,s)=>{n++;if(!v)throw Error(s)};const browser=await chromium.launch({executablePath:'/usr/bin/chromium',args:['--no-sandbox']});
 try{
  const c=await browser.newContext({ignoreHTTPSErrors:true,viewport:{width:390,height:844}});const p=await c.newPage();let response=await p.goto(base+'/');ok(response.status()===200&&await p.getByRole('button',{name:/Sicher anmelden/}).count()===1,'HTTPS root opens login');
  ok(await p.locator('[name=email]').evaluate(e=>parseFloat(getComputedStyle(e).fontSize)>=16),'mobile field font avoids input zoom');ok(await p.getByRole('button',{name:/Sicher anmelden/}).evaluate(e=>e.getBoundingClientRect().height>=44),'mobile submit target at least44px');
  const before=(await c.cookies()).find(x=>x.name==='ZGSSESSID').value;await p.locator('[name=email]').fill('owner@example.invalid');await p.locator('[name=password]').fill('Local-Test-Only-2026!');await p.getByRole('button',{name:/Sicher anmelden/}).click();const after=(await c.cookies()).find(x=>x.name==='ZGSSESSID').value;ok(before!==after,'login rotates session identity');
  ok(await p.locator('a[href="system_check.php"]:visible').count()>=1,'systemcheck accessible from mobile dashboard');await p.locator('a[href="system_check.php"]:visible').first().click();ok((await p.textContent('body')).includes('0 Blocker'),'actual HTTPS web prerequisites pass');
  await p.goto(base+'/index.php');await p.getByRole('button',{name:/Abmelden/}).click();await p.goto(base+'/planner.php');ok(p.url().includes('index.php'),'logout removes owner access');response=await c.request.get(base+'/document_download.php?id=1');ok(response.status()===403,'logout protects original download');
  if(process.env.GSZ_TEST_HTTP_URL){response=await c.request.get(process.env.GSZ_TEST_HTTP_URL+'/index.php');ok(response.status()===403&&(await response.text()).includes('HTTPS'),'plain HTTP denied before database login');}
  console.log(`Installation smoke browser passed (${n} checks).`);
 }finally{await browser.close()}
})().catch(e=>{console.error(e);process.exit(1)});
