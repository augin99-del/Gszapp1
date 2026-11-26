<?php
declare(strict_types=1);
// Immobilien-Startseite: alle Häuser auf einen Blick – Mieten des Monats, Belegung, Überschuss im Jahr, Aufgaben und Fristen.
require __DIR__.'/bootstrap.php';if(empty($_SESSION['admin_id']))redirect('index.php');
require_once dirname(__DIR__).'/private/estate_portfolio.php';
$today=estateNow();$month=$today->format('Y-m-01');$buildings=[];$ready=true;
try{estateRequire($pdo);$buildings=$pdo->query('SELECT * FROM estate_buildings ORDER BY name,id LIMIT 200')->fetchAll(PDO::FETCH_ASSOC);}catch(Throwable $e){$ready=false;gszUiError($e);}
$all=['due'=>0,'paid'=>0,'open'=>0,'units'=>0,'vacant'=>0,'income'=>0,'expense'=>0,'surplus'=>0];$cards=[];$tasks=[];
foreach($buildings as $b){try{$s=estateBuildingStats($pdo,$b,$month,$today);$t=estateBuildingTasks($pdo,$b,$s,$today);}catch(Throwable $e){continue;}
 $cards[]=[$b,$s,$t];foreach(['due','paid','open','units','vacant'] as $k)$all[$k]+=$s[$k];$all['income']+=$s['income_year'];$all['expense']+=$s['expense_year'];$all['surplus']+=$s['surplus_year'];
 foreach($t as $x)$tasks[]=[$x[0],$b['name'],$x[1],$x[2]];}
$order=['red'=>0,'amber'=>1,'info'=>2];usort($tasks,fn($a,$b)=>$order[$a[0]]<=>$order[$b[0]]);
$mn=GSZ_MONTHS[(int)$today->format('n')];$pct=$all['due']?(int)round(100*$all['paid']/$all['due']):0;
gszPageStart('Immobilien','Deine Häuser auf einen Blick – '.$mn.' '.$today->format('Y').'.');?>
<div class="estate-actions"><a class="btn primary" href="estate_bank.php#hochladen">Kontoauszug hochladen</a><a class="btn" href="estate_bank.php">Mieten &amp; Buchungen zuordnen</a>
 <details class="menu-btn"><summary class="btn">+ Anlegen</summary><div class="menu"><a href="estate.php?view=building">Haus</a><a href="estate.php?view=unit">Wohnung / Fläche</a><a href="estate.php?view=tenant">Mieter</a><a href="estate.php?view=lease">Mietvertrag</a><a href="estate.php?view=document">Dokument</a></div></details>
 <details class="menu-btn"><summary class="btn ghost">Export</summary><div class="menu"><a href="estate_export.php?type=rent&amp;month=<?=e($month)?>">Mietkonto (CSV)</a><a href="estate_export.php?type=payments">Zahlungen (CSV)</a><a href="estate_export.php?type=allocations">Zuordnungen (CSV)</a><a href="estate.php">Alle Stammdaten (Experten-Ansicht)</a></div></details></div>
<?php if($ready&&!$buildings):?><div class="empty"><strong>Noch keine Häuser angelegt.</strong><a href="estate.php?view=building">Erstes Haus anlegen</a></div><?php endif?>
<?php if($buildings):?>
<nav class="day-kpis estate-kpis" aria-label="Kennzahlen"><div class="dk"><strong><?=e(estatePortfolioMoney($all['due']))?></strong><span>Miete <?=e($mn)?> (Soll)</span><small class="meter" style="--p:<?=$pct?>%"><i></i></small></div>
 <div class="dk<?=$all['open']?' is-warn':''?>"><strong><?=e(estatePortfolioMoney($all['paid']))?></strong><span>eingegangen · <?=$pct?> %</span><?php if($all['open']):?><small class="bad">offen <?=e(estatePortfolioMoney($all['open']))?></small><?php endif?></div>
 <div class="dk"><strong><?=$all['units']-$all['vacant']?>/<?=$all['units']?></strong><span>Einheiten vermietet</span><?php if($all['vacant']):?><small class="bad"><?=$all['vacant']?> leer</small><?php endif?></div>
 <div class="dk"><strong class="<?=$all['surplus']<0?'neg':''?>"><?=e(estatePortfolioMoney($all['surplus']))?></strong><span>Überschuss <?=e($today->format('Y'))?></span><small>Einnahmen <?=e(estatePortfolioMoney($all['income']))?> · Kosten <?=e(estatePortfolioMoney($all['expense']))?></small></div></nav>
<div class="estate-grid"><section><h2 class="section-title">Häuser</h2><div class="house-cards"><?php foreach($cards as [$b,$s,$t]):$p=$s['due']?(int)round(100*$s['paid']/$s['due']):0;?>
 <a class="house-card st-<?=e($s['status'])?>" href="haus.php?id=<?=(int)$b['id']?>"><div class="hc-head"><span class="hc-dot" aria-hidden="true"></span><div><strong><?=e($b['name'])?></strong><small><?=e($b['address'])?></small></div><span class="hc-go" aria-hidden="true">›</span></div>
  <div class="hc-bar" role="img" aria-label="<?=$p?> % der Miete eingegangen"><i style="width:<?=$p?>%"></i></div>
  <dl class="hc-facts"><div><dt>Miete <?=e($mn)?></dt><dd><?=e(estatePortfolioMoney($s['paid']))?> <span>von <?=e(estatePortfolioMoney($s['due']))?></span></dd></div><div><dt>Offen</dt><dd class="<?=$s['open']?'bad':''?>"><?=e(estatePortfolioMoney($s['open']))?></dd></div><div><dt>Vermietet</dt><dd><?=$s['units']-$s['vacant']?>/<?=$s['units']?></dd></div><div><dt>Überschuss <?=e($today->format('Y'))?></dt><dd class="<?=$s['surplus_year']<0?'bad':''?>"><?=e(estatePortfolioMoney($s['surplus_year']))?></dd></div></dl>
  <?php if($t):?><p class="hc-todo"><?=count($t)===1?'1 Aufgabe':count($t).' Aufgaben'?> · <?=e($t[0][1])?></p><?php else:?><p class="hc-todo ok">Alles erledigt</p><?php endif?></a><?php endforeach?></div></section>
<aside><h2 class="section-title">Zu erledigen</h2><?php if(!$tasks):?><div class="empty"><strong>Nichts offen.</strong>Alle Mieten sind da, keine Fristen in Sicht.</div><?php else:?><div class="task-feed"><?php foreach(array_slice($tasks,0,14) as [$lvl,$house,$text,$url]):?><a class="tf tf-<?=e($lvl)?>" href="<?=e($url)?>"><span class="tf-dot" aria-hidden="true"></span><span><strong><?=e($text)?></strong><small><?=e($house)?></small></span></a><?php endforeach?></div><?php endif?>
<p class="hint">Fristen sind Hinweise, keine Rechtsberatung. Nebenkosten: Abrechnung innerhalb von 12 Monaten nach Ende des Abrechnungszeitraums (§ 556 BGB).</p></aside></div>
<?php endif;gszPageEnd();
