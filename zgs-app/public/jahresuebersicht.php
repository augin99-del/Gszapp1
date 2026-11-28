<?php
declare(strict_types=1);
// Jahresübersicht Immobilien für den Steuerberater: Einnahmen, Kosten nach Art, Kautionen, Überschuss je Haus. Drucken oder als CSV.
require __DIR__.'/bootstrap.php';if(empty($_SESSION['admin_id'])){http_response_code(403);exit('Nicht angemeldet.');}
require_once dirname(__DIR__).'/private/estate_portfolio.php';require_once dirname(__DIR__).'/private/export_core.php';
$cur=(int)estateNow()->format('Y');$year=max(2000,min($cur,(int)($_GET['jahr']??($cur-1))));$rep=[];
try{estateRequire($pdo);$rep=estateYearReport($pdo,$year);}catch(Throwable $e){gszUiError($e);}
$eur=fn(int $c)=>number_format($c/100,2,',','');
if(($_GET['format']??'')==='csv'){
 $rows=[];foreach($rep as $r){$n=$r['building']['name'];
  foreach(['rent'=>'Einnahmen: Kaltmiete','operating'=>'Einnahmen: Betriebskosten-Vorauszahlungen','heating'=>'Einnahmen: Heizkosten-Vorauszahlungen'] as $k=>$l)$rows[]=[$year,$n,$l,$eur($r[$k])];
  foreach($r['costs'] as $c)$rows[]=[$year,$n,'Ausgaben: '.$c['category'],$eur(-(int)$c['cents'])];
  $rows[]=[$year,$n,'Überschuss (Einnahmen minus Ausgaben)',$eur($r['surplus'])];$rows[]=[$year,$n,'Kaution (Saldo, keine Einnahme)',$eur($r['deposit'])];
  if($r['open_in_n']||$r['open_out_n'])$rows[]=[$year,$n,'Hinweis: nicht zugeordnete Bankumsätze (nicht enthalten)','Eingänge '.$eur($r['open_in']).' EUR, Ausgaben '.$eur($r['open_out']).' EUR'];}
 while(ob_get_level())ob_end_clean();header('Content-Type: text/csv; charset=utf-8');header('Content-Disposition: attachment; filename="immobilien-jahresuebersicht-'.$year.'.csv"');header('Cache-Control: no-store');
 echo gszCsv(['Jahr','Haus','Position','Betrag EUR'],$rows);exit;}
$m=fn(int $c)=>estatePortfolioMoney($c);$tot=['income'=>0,'expense'=>0,'surplus'=>0];foreach($rep as $r)foreach($tot as $k=>$_)$tot[$k]+=$r[$k];
gszPageStart('Jahresübersicht '.$year,'Immobilien – Grundlage für den Steuerberater');?>
<a class="back-link no-print" href="immobilien.php">‹ Alle Häuser</a>
<nav class="month-nav no-print" aria-label="Jahr"><a href="?jahr=<?=$year-1?>" aria-label="Vorheriges Jahr">‹</a><strong><?=$year?></strong><?php if($year<$cur):?><a href="?jahr=<?=$year+1?>" aria-label="Nächstes Jahr">›</a><?php else:?><span></span><?php endif?></nav>
<div class="estate-actions no-print"><button type="button" class="btn primary" onclick="window.print()">Drucken / als PDF sichern</button><a class="btn" href="?jahr=<?=$year?>&amp;format=csv">CSV für den Steuerberater</a></div>
<nav class="day-kpis estate-kpis" aria-label="Summe aller Häuser"><div class="dk"><strong><?=e($m($tot['income']))?></strong><span>Einnahmen <?=$year?></span></div><div class="dk"><strong><?=e($m($tot['expense']))?></strong><span>Ausgaben <?=$year?></span></div><div class="dk"><strong class="<?=$tot['surplus']<0?'neg':''?>"><?=e($m($tot['surplus']))?></strong><span>Überschuss</span></div><div class="dk"><strong><?=count($rep)?></strong><span><?=count($rep)===1?'Haus':'Häuser'?></span></div></nav>
<?php foreach($rep as $r):$b=$r['building'];?><section class="card year-house"><h2><?=e($b['name'])?></h2><p class="muted"><?=e($b['address'])?></p>
<?php if($r['open_in_n']||$r['open_out_n']):?><?php $parts=[];if($r['open_in_n'])$parts[]=($r['open_in_n']===1?'1 Eingang':$r['open_in_n'].' Eingänge').' ('.$m($r['open_in']).')';if($r['open_out_n'])$parts[]=($r['open_out_n']===1?'1 Ausgabe':$r['open_out_n'].' Ausgaben').' ('.$m($r['open_out']).')';?><p class="error">Nicht vollständig: <?=e(implode(' und ',$parts))?> im Jahr <?=$year?> <?=count($parts)>1||$r['open_in_n']+$r['open_out_n']>1?'sind':'ist'?> noch nicht zugeordnet und hier nicht enthalten. <a class="no-print" href="estate_bank.php">Jetzt zuordnen</a></p><?php endif?>
<table class="year-table"><tbody>
<tr class="grp"><th colspan="2">Einnahmen (Zahlungseingang <?=$year?>)</th></tr>
<tr><td>Kaltmiete</td><td><?=e($m($r['rent']))?></td></tr><tr><td>Betriebskosten-Vorauszahlungen</td><td><?=e($m($r['operating']))?></td></tr><tr><td>Heizkosten-Vorauszahlungen</td><td><?=e($m($r['heating']))?></td></tr>
<tr class="sum"><td>Summe Einnahmen</td><td><?=e($m($r['income']))?></td></tr>
<tr class="grp"><th colspan="2">Ausgaben (bestätigte Kosten des Hauses)</th></tr>
<?php foreach($r['costs'] as $c):?><tr><td><?=e($c['category'])?> <small>(<?=(int)$c['n']?>)</small></td><td><?=e($m((int)$c['cents']))?></td></tr><?php endforeach?><?php if(!$r['costs']):?><tr><td class="muted">Keine bestätigten Ausgaben</td><td>0 €</td></tr><?php endif?>
<tr class="sum"><td>Summe Ausgaben</td><td><?=e($m($r['expense']))?></td></tr>
<tr class="total"><td>Überschuss</td><td class="<?=$r['surplus']<0?'neg':''?>"><?=e($m($r['surplus']))?></td></tr>
<tr><td class="muted">Kaution (Saldo des Jahres, keine Einnahme)</td><td class="muted"><?=e($m($r['deposit']))?></td></tr>
</tbody></table></section><?php endforeach?>
<?php if(!$rep):?><div class="empty"><strong>Noch keine Häuser angelegt.</strong></div><?php endif?>
<p class="hint">Grundlage für den Steuerberater, keine fertige Anlage V. Es zählen nur Zahlungen und Ausgaben, die du in der Zentrale zugeordnet bzw. bestätigt hast, nach Zahlungs-/Buchungstag (Zuflussprinzip). Abschreibung (AfA), Schuldzinsen-Aufteilung, Nebenkosten-Nachzahlungen/-Erstattungen und Ausgaben außerhalb der Hauskonten sind nicht enthalten. Bei „Kredit / Zinsen“ enthält der Betrag ggf. auch Tilgung – vom Steuerberater trennen lassen.</p>
<?php gszPageEnd();
