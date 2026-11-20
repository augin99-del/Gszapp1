const {chromium}=require('playwright');
(async()=>{
  const base=process.env.GSZ_TEST_URL||'https://127.0.0.1:8461';
  let checks=0;
  const ok=(value,message)=>{checks++;if(!value)throw Error(message)};
  const browser=await chromium.launch({executablePath:'/usr/bin/chromium',args:['--no-sandbox']});
  try {
    const context=await browser.newContext({ignoreHTTPSErrors:true});
    let response=await context.request.get(base+'/estate_settlement_export.php?id=1');
    ok(response.status()===403,'private archive requires authentication');
    const page=await context.newPage();
    await page.goto(base+'/estate_settlements.php');
    await page.locator('[name=email]').fill('owner@example.invalid');
    await page.locator('[name=password]').fill('Local-Test-Only-2026!');
    await page.getByRole('button',{name:/Sicher anmelden/}).click();
    await page.goto(base+'/estate_settlements.php?building=1&from=2024-01-01&to=2024-12-31&as_of=2025-03-01&settlement=1');
    const link=page.getByRole('link',{name:'Privates Eigentümer-/Beraterpaket mit Originalen und Prüfsummen'});
    ok(await link.count()===1,'actual final settlement exposes owner-only export');
    response=await context.request.get(new URL(await link.getAttribute('href'),base).toString());
    const bytes=await response.body();
    ok(response.status()===200&&bytes.subarray(0,2).toString()==='PK','actual archive download');
    ok(/^attachment;/.test(response.headers()['content-disposition']||''),'forced attachment');
    console.log(`Estate owner export browser passed (${checks} checks).`);
  } finally {await browser.close()}
})().catch(error=>{console.error(error);process.exit(1)});
