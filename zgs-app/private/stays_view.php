<?php
declare(strict_types=1);
/** Ferienwohnung auf der Objektseite. Braucht e() aus bootstrap.php. */
require_once __DIR__.'/stays.php';
function gszStaySummary(array $r):string {
 $n=count($r['planned']);$s=$n===0?'Keine Abreise in den nächsten '.GSZ_STAY_HORIZON_DAYS.' Tagen.':($n===1?'1 Reinigung geplant.':$n.' Reinigungen geplant.');
 if($r['created'])$s.=' Neu: '.$r['created'].'.';if($r['cancelled'])$s.=' Entfallen: '.$r['cancelled'].'.';if($r['changed'])$s.=' Geändert: '.$r['changed'].'.';return $s;
}
function gszStayFields(string $csrf,string $action):string {return '<input type="hidden" name="csrf" value="'.e($csrf).'"><input type="hidden" name="action" value="'.e($action).'">';}
function gszStayWhen(?string $utc):string {if(!$utc)return '';return (new DateTimeImmutable($utc,new DateTimeZone('UTC')))->setTimezone(new DateTimeZone('Europe/Berlin'))->format('d.m., H:i').' Uhr';}
function gszStaySection(PDO $pdo,int $propertyId,string $csrf):string {
 if(!gszStayReady($pdo))return '';$u=gszStayUnit($pdo,$propertyId);$wd=['','Mo','Di','Mi','Do','Fr','Sa','So'];
 $timeForm=fn(array $v,string $button)=>'<form method="post">'.gszStayFields($csrf,'stay_setup').'<div class="grid-3"><label>Check-out<input type="time" name="checkout_time" value="'.e(substr((string)$v['checkout_time'],0,5)).'" required></label><label>Check-in<input type="time" name="checkin_time" value="'.e(substr((string)$v['checkin_time'],0,5)).'" required></label><label>Reinigung (Min.)<input type="number" name="duration_minutes" min="15" max="600" inputmode="numeric" value="'.(int)$v['duration_minutes'].'" required></label></div><button class="primary">'.e($button).'</button></form>';
 $h='<section class="card" id="ferienwohnung"><h2>Ferienwohnung</h2>';
 if(!$u||!$u['enabled']){
  return $h.'<p class="muted">Vermietest du dieses Objekt über Airbnb oder Booking.com? Dann plant die Zentrale die Reinigung am Abreisetag automatisch – mit Gästewechsel-Hinweis, wenn am selben Tag neue Gäste kommen.</p><details class="sub"><summary>Als Ferienwohnung einrichten</summary>'.$timeForm($u?:['checkout_time'=>'10:00','checkin_time'=>'15:00','duration_minutes'=>60],'Einrichten').'</details></section>';}
 $h.='<p>Check-out <strong>'.e(substr((string)$u['checkout_time'],0,5)).'</strong> · Check-in <strong>'.e(substr((string)$u['checkin_time'],0,5)).'</strong> · Reinigung '.(int)$u['duration_minutes'].' Min.</p>';
 $cals=gszStayCalendars($pdo,$propertyId);
 if($cals){$h.='<div class="list stay-cals">';foreach($cals as $c){$st=$c['last_status']==='ok'?'Abgerufen '.gszStayWhen($c['last_fetch_at']).' · '.((int)$c['stays_found']===1?'1 Buchung':(int)$c['stays_found'].' Buchungen'):($c['last_status']==='error'?'Abruf fehlgeschlagen: '.$c['last_error']:'Noch nicht abgerufen');
  $h.='<div class="li"><span class="name">'.e(GSZ_STAY_SOURCES[$c['source']]).'<span class="sub'.($c['last_status']==='error'?' late':'').'">'.e($st).'</span><span class="sub">'.e($c['url_hint']).'</span></span><form method="post" class="inline" onsubmit="return confirm(\'Diesen Kalender entfernen?\')">'.gszStayFields($csrf,'stay_calendar_remove').'<input type="hidden" name="calendar_id" value="'.(int)$c['id'].'"><button class="small ghost">Entfernen</button></form></div>';}
  $h.='</div><form method="post" class="stay-sync">'.gszStayFields($csrf,'stay_sync').'<button class="small">Jetzt abrufen</button><span class="hint">Sonst automatisch beim Öffnen von „Heute“, höchstens stündlich.</span></form>';}
 else $h.='<p class="muted">Noch kein Kalender verbunden.</p>';
 $h.='<details class="sub"'.($cals?'':' open').'><summary>Kalender-Link hinzufügen</summary><form method="post">'.gszStayFields($csrf,'stay_calendar_add').'<label>Link zum Kalender-Export (iCal)<input type="url" name="calendar_url" required autocomplete="off" spellcheck="false" placeholder="https://www.airbnb.de/calendar/ical/…"></label>'
  .'<ul class="hint steps-small"><li><strong>Airbnb:</strong> Kalender → Verfügbarkeit → „Kalender synchronisieren“ → „Kalender exportieren“ → Link kopieren.</li><li><strong>Booking.com:</strong> Extranet → Preise &amp; Verfügbarkeit → „Kalender synchronisieren“ → „Kalender exportieren“ → Link kopieren.</li></ul>'
  .'<p class="hint">Der Link wird verschlüsselt gespeichert und nie wieder vollständig angezeigt. Übernommen werden nur An- und Abreisedaten.</p><button class="primary">Verbinden</button></form></details>';
 $up=gszStayUpcoming($pdo,$propertyId);
 if($up){$h.='<h3 class="section-title small">Nächste Reinigungen</h3><div class="list">';foreach($up as $c){$d=new DateTimeImmutable($c['clean_date']);$h.='<div class="li'.($c['done']?' is-done':'').'"><span class="name">'.e($wd[(int)$d->format('N')].', '.$d->format('d.m.')).'<span class="sub">'.($c['turnover']?'Gästewechsel – bis '.e(substr((string)$u['checkin_time'],0,5)).' fertig':'Abreise '.e(substr((string)$u['checkout_time'],0,5)).', danach frei').($c['done']?' · erledigt':'').'</span></span>'.($c['turnover']?'<span class="tag">Gästewechsel</span>':'').'</div>';}$h.='</div>';}
 elseif($cals&&!array_filter($cals,fn($c)=>$c['last_status']!=='ok'))$h.='<p class="muted">Keine Abreise in den nächsten '.GSZ_STAY_HORIZON_DAYS.' Tagen.</p>';
 $h.='<details class="sub"><summary>Zeiten ändern</summary>'.$timeForm($u,'Speichern').'<p class="hint">Gilt für neu geplante Reinigungen.</p></details>';
 $h.='<form method="post" onsubmit="return confirm(\'Ferienwohnung beenden? Künftige offene Reinigungen werden entfernt.\')">'.gszStayFields($csrf,'stay_disable').'<button class="small ghost">Ferienwohnung beenden</button></form>';
 return $h.'</section>';
}
