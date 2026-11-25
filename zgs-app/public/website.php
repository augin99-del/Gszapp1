<?php
declare(strict_types=1);
require __DIR__.'/bootstrap.php';
if(empty($_SESSION['admin_id']))redirect('index.php');
require_once dirname(__DIR__).'/private/leads.php';require_once dirname(__DIR__).'/private/ui.php';
$days=in_array((int)($_GET['tage']??30),[7,30,90],true)?(int)($_GET['tage']??30):30;$s=gszWebStats($pdo,$days);$t=$s['totals'];
$contacts=$t['call']+$t['whatsapp']+$t['form'];$rate=$t['view']?round(100*$contacts/$t['view'],1):0;
$name=static function(string $p):string{$map=['/'=>'Startseite','/leistungen/tueroeffnung-guetersloh/'=>'Notdienst','/leistungen/schluesseldienst/'=>'Schlüsseldienst','/kontakt/'=>'Kontakt','/loesungen/hausverwaltungen/'=>'Hausverwaltungen'];if(isset($map[$p]))return $map[$p];if(preg_match('~^/standorte/([a-z-]+)/$~',$p,$m))return 'Ort: '.ucwords(str_replace('-',' ',$m[1]));return $p;};
gszPageStart('Website','Was auf gebaeudeservice-zentrum.de passiert – ohne Cookies gezählt.');?>
<nav class="seg-tabs" aria-label="Zeitraum"><?php foreach([7=>'7 Tage',30=>'30 Tage',90=>'90 Tage'] as $k=>$l):?><a href="website.php?tage=<?=$k?>"<?=$k===$days?' aria-current="page"':''?>><?=$l?></a><?php endforeach?></nav>
<div class="kpis">
 <div class="kpi"><span class="k">Seitenaufrufe</span><strong><?=number_format($t['view'],0,',','.')?></strong><small>letzte <?=$days?> Tage</small></div>
 <div class="kpi"><span class="k">Anrufen getippt</span><strong><?=number_format($t['call'],0,',','.')?></strong><small>Tipp auf eine Telefonnummer</small></div>
 <div class="kpi"><span class="k">WhatsApp getippt</span><strong><?=number_format($t['whatsapp'],0,',','.')?></strong><small>Tipp auf WhatsApp</small></div>
 <div class="kpi"><span class="k">Formular gesendet</span><strong><?=number_format($t['form'],0,',','.')?></strong><small><?=$rate?> % der Aufrufe führen zu Kontakt</small></div>
</div>
<?php if($s['days']):$max=max(1,max(array_map(fn($d)=>(int)$d['views'],$s['days'])));?>
<section class="card"><h2>Verlauf</h2><div class="spark" role="img" aria-label="Aufrufe und Kontakte pro Tag"><?php foreach($s['days'] as $d):?><span title="<?=e((new DateTimeImmutable($d['day']))->format('d.m.').': '.(int)$d['views'].' Aufrufe, '.(int)$d['contacts'].' Kontakte')?>"><i style="height:<?=max(2,round(100*(int)$d['views']/$max))?>%"></i><b style="height:<?=min(100,round(100*(int)$d['contacts']/$max*4))?>%"></b></span><?php endforeach?></div><p class="hint">Grau: Aufrufe · Grün: Anrufen, WhatsApp und Formular</p></section><?php endif?>
<?php if($s['pages']):?><h2 class="section-title">Seiten, die Kontakte bringen</h2><div class="list web-pages"><?php foreach($s['pages'] as $p):?><div class="li"><span class="name"><?=e($name($p['page']))?><span class="sub"><?=(int)$p['views']?> Aufrufe</span></span><span class="web-nums"><span title="Anrufen">📞 <?=(int)$p['calls']?></span><span title="WhatsApp">💬 <?=(int)$p['whatsapp']?></span><span title="Formular">✉️ <?=(int)$p['forms']?></span></span></div><?php endforeach?></div>
<?php else:?><div class="empty"><strong>Noch keine Zahlen.</strong>Sobald die neue Website online ist, zählt sie Aufrufe und Kontakt-Tipps – ohne Cookies und ohne Personendaten.</div><?php endif?>
<p class="hint">Gezählt wird nur: Tag, Seite und ob jemand die Seite geöffnet, „Anrufen“ oder „WhatsApp“ getippt oder das Formular gesendet hat. Keine IP-Adressen, keine Cookies, keine Wiedererkennung. Ob aus einem Tipp wirklich ein Anruf wurde, kann die Website nicht wissen. Google-Suchbegriffe stehen unter <a href="seo.php">Google-Sichtbarkeit</a>.</p>
<?php gszPageEnd();
