<?php
declare(strict_types=1);
require __DIR__.'/bootstrap.php';
if(empty($_SESSION['admin_id']))redirect('index.php');
require_once dirname(__DIR__).'/private/leads.php';require_once dirname(__DIR__).'/private/ui.php';
require_once dirname(__DIR__).'/private/calc.php';
if($_SERVER['REQUEST_METHOD']==='POST'){checkCsrf();try{if(($_POST['action']??'')!=='calc')throw new RuntimeException('Aktion unbekannt.');gszCalcSave($pdo,(int)$_SESSION['admin_id'],$_POST);$_SESSION['app_notice']=gszCalcConfig($pdo)['enabled']?'Preisrechner gespeichert – er erscheint jetzt auf der Website.':'Preisrechner gespeichert (auf der Website ausgeblendet).';}catch(Throwable $e){gszUiError($e);}redirect('website.php#rechner');}
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
<?php $cc=gszCalcConfig($pdo);$val=fn(string $k)=>e(str_replace('.',',',gszSetting($pdo,$k)));$ex=gszCalcQuote($pdo,['floors'=>3,'entrances'=>1,'rhythm'=>'weekly','cellar'=>1]);?>
<details class="card" id="rechner"<?=isset($_GET['rechner'])?' open':''?>><summary>Preisrechner Treppenhausreinigung <?=$cc['enabled']?'<span class="tag lead-auftrag">an</span>':'<span class="tag">aus</span>'?></summary>
<p class="hint">Besucher stellen Etagen, Treppenhäuser und Rhythmus ein und sehen sofort eine Spanne „ca. … bis … € im Monat“. Grundlage sind deine Sätze hier – solange kein Stundensatz eingetragen und der Rechner nicht eingeschaltet ist, bleibt er auf der Website unsichtbar.</p>
<form method="post"><?php gszFields('calc');?>
 <div class="grid-3"><label>Stundensatz (€)<input name="calc_rate" inputmode="decimal" value="<?=$val('calc_rate')?>" placeholder="dein Satz"></label><label>Mindestpreis je Reinigung (€, optional)<input name="calc_min_visit" inputmode="decimal" value="<?=$val('calc_min_visit')?>"></label><label>Preisangabe<select name="calc_vat"><?php foreach(['zzgl'=>'zzgl. MwSt.','inkl'=>'inkl. MwSt.','ohne'=>'ohne Zusatz'] as $k=>$l):?><option value="<?=$k?>"<?=gszSetting($pdo,'calc_vat')===$k?' selected':''?>><?=$l?></option><?php endforeach?></select></label></div>
 <div class="grid-3"><label>Grundzeit je Einsatz (Min.)<input name="calc_base_min" inputmode="decimal" value="<?=$val('calc_base_min')?>"></label><label>Minuten je Etage<input name="calc_floor_min" inputmode="decimal" value="<?=$val('calc_floor_min')?>"></label><label>Spanne ± (%)<input name="calc_range_pct" inputmode="decimal" value="<?=$val('calc_range_pct')?>"></label></div>
 <div class="grid-3"><label>+ Keller (Min. je Treppenhaus)<input name="calc_cellar_min" inputmode="decimal" value="<?=$val('calc_cellar_min')?>"></label><label>+ Aufzug (Min.)<input name="calc_elevator_min" inputmode="decimal" value="<?=$val('calc_elevator_min')?>"></label><label>+ Fenster (Min. je Etage)<input name="calc_window_min" inputmode="decimal" value="<?=$val('calc_window_min')?>"></label></div>
 <label><input type="checkbox" name="calc_enabled" value="1"<?=gszSetting($pdo,'calc_enabled')==='1'?' checked':''?>> Rechner auf der Website zeigen</label><button class="primary">Speichern</button></form>
 <p class="hint"><?=$ex?'Beispiel 3 Etagen, 1 Treppenhaus mit Keller, wöchentlich: <strong>'.e($ex['text']).'</strong> ('.(int)$ex['minutes'].' Min. je Einsatz).':'Die Minutenwerte sind Vorschläge – bitte mit deinen echten Erfahrungswerten prüfen.'?></p></details>
<?php gszPageEnd();
