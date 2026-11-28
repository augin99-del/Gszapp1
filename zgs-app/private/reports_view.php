<?php
declare(strict_types=1);
/** Ansichten für Mieter-Meldungen und Hausverwaltungs-Portal in der Zentrale (Schema 430). */
require_once __DIR__.'/reports.php';
function gszDefectWhen(string $ts):string {$d=(new DateTimeImmutable($ts.' UTC'))->setTimezone(new DateTimeZone('Europe/Berlin'));$today=(new DateTimeImmutable('today',new DateTimeZone('Europe/Berlin')))->format('Y-m-d');return ($d->format('Y-m-d')===$today?'heute':$d->format('d.m.')).', '.$d->format('H:i');}
function gszRenderDefect(array $d,string $csrf,string $return,bool $showProperty=true):string {
 $st=gszDefectState($d);$f=fn(string $a,string $extra='')=>'<form method="post" action="meldungen.php"><input type="hidden" name="csrf" value="'.e($csrf).'"><input type="hidden" name="action" value="'.e($a).'"><input type="hidden" name="id" value="'.(int)$d['id'].'"><input type="hidden" name="return" value="'.e($return).'">'.$extra;
 $h='<article class="card defect st-'.e($st).'" id="meldung-'.(int)$d['id'].'"><div class="lead-head"><strong>'.e($d['location']!==''?$d['location']:'Meldung').'</strong><span class="tag defect-'.e($st).'">'.e(GSZ_DEFECT_STATUS[$st]).'</span></div>';
 $h.='<p class="lead-meta">'.($showProperty?'<a href="property.php?id='.(int)$d['property_id'].'#meldungen">'.e($d['property_name']).'</a> · ':'').e(gszDefectWhen((string)$d['created_at'])).($d['contact']!==''?' · '.e($d['contact']):'').'</p>';
 $h.='<p class="lead-msg">'.nl2br(e($d['message'])).'</p>';
 if($d['photo_name'])$h.='<a class="defect-photo" href="defect_photo.php?id='.(int)$d['id'].'" target="_blank" rel="noopener"><img src="defect_photo.php?id='.(int)$d['id'].'" alt="Foto zur Meldung" loading="lazy"></a>';
 $h.='<div class="lead-actions">';
 if($st==='neu')$h.=$f('note').'<button class="small primary">Als To-do übernehmen</button></form>';
 elseif($st==='uebernommen')$h.='<a class="btn small" href="notes.php#notiz-'.(int)$d['note_id'].'">To-do ansehen</a>';
 if(in_array($st,['neu','uebernommen'],true))$h.=$f('done').'<button class="small'.($st==='uebernommen'?' primary':'').'">✓ Erledigt</button></form>';
 else $h.=$f('reopen').'<button class="small ghost">Wieder öffnen</button></form>';
 $h.='<details class="move"><summary>Löschen</summary><div class="move-sheet">'.$f('delete').'<p class="hint">Meldung'.($d['photo_name']?' und Foto':'').' endgültig löschen? Sie verschwindet auch aus dem Portal der Verwaltung.'.($d['note_id']?' Das To-do in der Merkliste bleibt.':'').'</p><button class="small delete-now">Endgültig löschen</button></form></div></details>';
 return $h.'</div></article>';
}
/** Abschnitt auf der Objektseite: Meldungen, QR-Aushang, Portal für die Hausverwaltung. */
function gszPropertyServiceSection(PDO $pdo,int $pid,string $csrf):string {
 if(!gszServiceReady($pdo))return '';$l=gszLinks($pdo,$pid);$ret='property.php?id='.$pid.'#meldungen';$open=gszDefects($pdo,'offen',$pid);
 $f=fn(string $a,string $extra='')=>'<form method="post" action="property.php?id='.$pid.'"><input type="hidden" name="csrf" value="'.e($csrf).'"><input type="hidden" name="action" value="'.e($a).'">'.$extra;
 $h='<section class="card" id="meldungen"><div class="section-head"><h2>Mieter-Meldungen &amp; Hausverwaltung</h2><a href="meldungen.php">Alle Meldungen</a></div>';
 foreach($open as $d)$h.=gszRenderDefect($d,$csrf,$ret,false);
 if(!$open)$h.='<p class="muted">Keine offenen Meldungen für dieses Haus.</p>';
 $h.='<div class="link-grid"><div class="link-box"><h3>QR-Code im Treppenhaus</h3>';
 if($l['report_token']){$h.='<p class="hint">Mieter scannen den Aushang und melden Schäden mit Foto – ohne App.</p><p><a class="btn small primary" href="aushang.php?id='.$pid.'">Aushang (PDF) drucken</a> <a class="btn small" href="'.e(gszReportUrl($pdo,$l['report_token'])).'" target="_blank" rel="noopener">Formular ansehen</a></p>'.
  '<details class="move"><summary>Link erneuern oder abschalten</summary><div class="move-sheet">'.$f('link_renew','<input type="hidden" name="kind" value="report">').'<button class="small">Neuen QR-Code erzeugen</button></form>'.$f('link_off','<input type="hidden" name="kind" value="report">').'<button class="small ghost">Abschalten</button></form><p class="hint">Danach funktioniert der alte Aushang nicht mehr.</p></div></details>';}
 else $h.='<p class="hint">Ein Aushang mit QR-Code: Mieter melden Schäden direkt an dich – mit Foto, ohne Anruf.</p>'.$f('link_renew','<input type="hidden" name="kind" value="report">').'<button class="small primary">QR-Meldung einrichten</button></form>';
 $h.='</div><div class="link-box"><h3>Meldungs-Portal für die Hausverwaltung</h3>';
 if($l['portal_token']){$url=gszPortalUrl($pdo,$l['portal_token']);$month=(new DateTimeImmutable('first day of last month',new DateTimeZone('Europe/Berlin')))->format('Y-m');
  $h.='<p class="hint">Die Verwaltung sieht die Mieter-Meldungen dieses Hauses mit Foto und kann Erledigtes selbst abhaken.</p><label>Link für die Verwaltung<input readonly value="'.e($url).'" class="copy-field" data-copy></label>'.
   '<p><a class="btn small" href="'.e($url).'" target="_blank" rel="noopener">Portal ansehen</a> <a class="btn small primary" href="monatsbericht.php?id='.$pid.'&amp;m='.e($month).'">Monatsbericht '.e(gszMonthName($month)).' (PDF)</a></p>';
  $q=$pdo->prepare('SELECT contact_email FROM properties WHERE id=?');$q->execute([$pid]);$mail=trim((string)$q->fetchColumn());
  if($mail!=='')$h.='<p><a class="btn small" href="compose.php?to='.e(rawurlencode($mail)).'&amp;subject='.e(rawurlencode('Mieter-Meldungen für Ihr Objekt – Ihr Link')).'">Link per Mail an die Verwaltung</a></p>';
  $h.='<details class="move"><summary>Link erneuern oder abschalten</summary><div class="move-sheet">'.$f('link_renew','<input type="hidden" name="kind" value="portal">').'<button class="small">Neuen Link erzeugen</button></form>'.$f('link_off','<input type="hidden" name="kind" value="portal">').'<button class="small ghost">Abschalten</button></form><p class="hint">Der bisherige Link funktioniert danach nicht mehr.</p></div></details>';}
 else $h.='<p class="hint">Ein geheimer Link nur für die Verwaltung dieses Hauses: alle Mieter-Meldungen mit Foto, Erledigtes hakt sie selbst ab.</p>'.$f('link_renew','<input type="hidden" name="kind" value="portal">').'<button class="small primary">Portal einrichten</button></form>';
 return $h.'</div></div></section>';
}
