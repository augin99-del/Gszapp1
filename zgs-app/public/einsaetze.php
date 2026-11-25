<?php
declare(strict_types=1);
require __DIR__.'/bootstrap.php';
if(empty($_SESSION['admin_id']))redirect('index.php');
require_once dirname(__DIR__).'/private/lock_jobs.php';require_once dirname(__DIR__).'/private/ui.php';
$admin=(int)$_SESSION['admin_id'];$tz=new DateTimeZone('Europe/Berlin');$now=new DateTimeImmutable('now',$tz);
$month=preg_match('/^\d{4}-(0[1-9]|1[0-2])$/D',(string)($_GET['m']??''))?(string)$_GET['m']:$now->format('Y-m');
if($_SERVER['REQUEST_METHOD']==='POST'){checkCsrf();$a=(string)($_POST['action']??'');$id=(int)($_POST['id']??0);$back='einsaetze.php'.(isset($_POST['m'])&&preg_match('/^\d{4}-\d{2}$/D',$_POST['m'])?'?m='.$_POST['m']:'');
 try{switch($a){
  case 'save':$id=gszLockSave($pdo,$admin,$_POST,$id);$j=gszLockJob($pdo,$id);$_SESSION['app_notice']='Einsatz gespeichert'.($j['note_id']&&$j['payment']==='rechnung'?' – Erinnerung „Rechnung offen“ steht in der Merkliste.':'.');break;
  case 'delete':gszLockDelete($pdo,$admin,$id);$_SESSION['app_notice']='Einsatz gelöscht.';break;
  case 'review':$j=gszLockJob($pdo,$id);if(!$j)throw new RuntimeException('Einsatz nicht gefunden.');gszLockReviewed($pdo,$id);header('Location: '.gszReviewLink($pdo,(string)$j['phone'],(string)$j['customer']),true,303);exit;
  default:throw new RuntimeException('Aktion unbekannt.');}
 }catch(Throwable $e){gszUiError($e);}redirect($back);}
$st=gszLockStats($pdo,$month);$csrf=csrf();$prefill=[];
if(isset($_GET['lead'])){$q=$pdo->prepare('SELECT id,name,phone,place,source FROM leads WHERE id=?');$q->execute([(int)$_GET['lead']]);if($l=$q->fetch())$prefill=['lead_id'=>$l['id'],'customer'=>$l['name'],'phone'=>$l['phone'],'place'=>$l['place'],'channel'=>in_array($l['source'],['telefon','whatsapp','website','empfehlung'],true)?$l['source']:'sonstiges'];}
$prev=(new DateTimeImmutable($month.'-01'))->modify('-1 month')->format('Y-m');$next=(new DateTimeImmutable($month.'-01'))->modify('+1 month')->format('Y-m');$isNow=$month===$now->format('Y-m');
$mn=['','Jan','Feb','Mär','Apr','Mai','Jun','Jul','Aug','Sep','Okt','Nov','Dez'];$max=max(1,...array_column($st['months'],'c'));
$form=function(array $j=[])use($csrf,$month,$now){$v=fn($k,$d='')=>e((string)($j[$k]??$d));ob_start();?>
<form method="post" class="lock-form"><input type="hidden" name="csrf" value="<?=e($csrf)?>"><input type="hidden" name="action" value="save"><input type="hidden" name="id" value="<?=(int)($j['id']??0)?>"><input type="hidden" name="m" value="<?=e($month)?>"><?php if(!empty($j['lead_id'])&&empty($j['id'])):?><input type="hidden" name="lead_id" value="<?=(int)$j['lead_id']?>"><?php endif?>
 <div class="grid-3"><label>Datum<input type="date" name="job_date" required max="<?=e($now->format('Y-m-d'))?>" value="<?=$v('job_date',$now->format('Y-m-d'))?>"></label><label>Uhrzeit<input type="time" name="job_time" value="<?=e(substr((string)($j['job_time']??(empty($j['id'])?$now->format('H:i'):'')),0,5))?>"></label>
 <label>Ort<input name="place" required maxlength="120" list="lock-places" value="<?=$v('place','Gütersloh')?>"></label></div>
 <div class="grid-3"><label>Was?<select name="kind"><?php foreach(GSZ_LOCK_KINDS as $k=>$t):?><option value="<?=$k?>"<?=($j['kind']??'')===$k?' selected':''?>><?=e($t)?></option><?php endforeach?></select></label>
 <label>Betrag (€)<input name="amount" inputmode="decimal" maxlength="12" placeholder="z. B. 120" value="<?=isset($j['amount_cents'])&&$j['amount_cents']?e(str_replace('.',',',(string)($j['amount_cents']/100))):''?>"></label>
 <label>Bezahlt per<select name="payment"><?php foreach(GSZ_LOCK_PAYMENTS as $k=>$t):?><option value="<?=$k?>"<?=($j['payment']??'')===$k?' selected':''?>><?=e($t)?></option><?php endforeach?></select></label></div>
 <div class="grid-3"><label>Wie kam der Kunde?<select name="channel"><?php foreach(GSZ_LOCK_CHANNELS as $k=>$t):?><option value="<?=$k?>"<?=($j['channel']??'')===$k?' selected':''?>><?=e($t)?></option><?php endforeach?></select></label>
 <label>Kunde (optional)<input name="customer" maxlength="190" value="<?=$v('customer')?>"></label><label>Handy (optional, für Bewertung)<input name="phone" type="tel" maxlength="80" value="<?=$v('phone')?>"></label></div>
 <label>Notiz (optional)<input name="note" maxlength="1000" value="<?=$v('note')?>"></label><button class="primary">Speichern</button></form>
<?php return ob_get_clean();};
gszPageStart('Schlüsseldienst','Einsätze in 20 Sekunden erfassen – Umsatz je Monat, Ort und Kanal.');?>
<datalist id="lock-places"><?php foreach(GSZ_LOCK_PLACES as $pl):?><option value="<?=e($pl)?>"><?php endforeach?></datalist>
<details class="card" id="neu"<?=$isNow||$prefill?' open':''?>><summary>Einsatz erfassen</summary><?=$form($prefill)?><p class="hint">Bei „Rechnung“ landet automatisch eine Erinnerung „Rechnung offen“ in der Merkliste – die Rechnung selbst schreibst du in Lexware.</p></details>
<nav class="month-nav" aria-label="Monat"><a href="einsaetze.php?m=<?=e($prev)?>">‹</a><strong><?=e(['','Januar','Februar','März','April','Mai','Juni','Juli','August','September','Oktober','November','Dezember'][(int)substr($month,5)].' '.substr($month,0,4))?></strong><?php if(!$isNow):?><a href="einsaetze.php?m=<?=e($next)?>">›</a><?php else:?><span></span><?php endif?></nav>
<div class="kpis"><div class="kpi"><span class="k">Einsätze</span><strong><?=$st['count']?></strong></div><div class="kpi"><span class="k">Umsatz</span><strong><?=e(gszEuro($st['total']))?></strong></div><div class="kpi"><span class="k">pro Einsatz</span><strong><?=$st['count']?e(gszEuro($st['avg'])):'–'?></strong></div><div class="kpi<?=$st['open']['n']?' warn':''?>"><span class="k">offen</span><strong><?=$st['open']['n']?></strong><small><?=$st['open']['c']?e(gszEuro($st['open']['c'])):''?></small></div></div>
<section class="card"><h2>Letzte 12 Monate</h2><div class="bars" role="img" aria-label="Umsatz je Monat"><?php foreach($st['months'] as $m=>$r):?><a class="bar<?=$m===$month?' is-current':''?>" href="einsaetze.php?m=<?=e($m)?>" title="<?=e($mn[(int)substr($m,5)].' '.substr($m,0,4).': '.$r['n'].' Einsätze, '.gszEuro($r['c']))?>"><span style="height:<?=max(2,(int)round(100*$r['c']/$max))?>%"></span><small><?=e($mn[(int)substr($m,5)])?></small></a><?php endforeach?></div>
<?php if($st['count']):?><p class="hint"><?=$st['night']?> nachts (22–6 Uhr) · <?=$st['weekend']?> am Wochenende</p><?php endif?></section>
<?php if($st['count']):?><div class="grid-2"><?php foreach(['places'=>['Nach Ort',null],'channels'=>['Wie kamen die Kunden?',GSZ_LOCK_CHANNELS],'kinds'=>['Art',GSZ_LOCK_KINDS],'payments'=>['Zahlung',GSZ_LOCK_PAYMENTS]] as $k=>[$title,$names]):?>
<section class="card"><h2><?=e($title)?></h2><table class="mini"><?php foreach($st[$k] as $key=>$r):?><tr><td><?=e($names[$key]??$key)?></td><td class="num"><?=$r['n']?>×</td><td class="num"><?=e(gszEuro($r['c']))?></td></tr><?php endforeach?></table></section><?php endforeach?></div><?php endif?>
<h2 class="section-title">Einsätze</h2>
<?php if(!$st['jobs']):?><div class="empty"><strong>Keine Einsätze in diesem Monat.</strong>Oben in 20 Sekunden erfassen.</div><?php endif?>
<div class="lead-list"><?php foreach($st['jobs'] as $j):$wa=gszWaNumber((string)$j['phone']);?><article class="card lead" id="einsatz-<?=(int)$j['id']?>">
 <div class="lead-head"><strong><?=e(GSZ_LOCK_KINDS[$j['kind']])?> · <?=e($j['place'])?></strong><span class="tag"><?=e($j['amount_cents']?gszEuro((int)$j['amount_cents']):'ohne Betrag')?></span></div>
 <p class="lead-meta"><?=e((new DateTimeImmutable($j['job_date']))->format('d.m.Y').($j['job_time']?', '.substr($j['job_time'],0,5).' Uhr':'').' · '.GSZ_LOCK_PAYMENTS[$j['payment']].' · '.GSZ_LOCK_CHANNELS[$j['channel']].($j['customer']!==''?' · '.$j['customer']:''))?><?php if($j['payment']==='rechnung'):?> · <?=$j['invoice_done']?'Rechnung geschrieben':'<a href="notes.php#notiz-'.(int)$j['note_id'].'">Rechnung offen</a>'?><?php endif?></p>
 <?php if($j['note']!==''):?><p class="lead-msg"><?=e($j['note'])?></p><?php endif?>
 <div class="lead-actions"><?php if($wa):?><form method="post" target="_blank"><input type="hidden" name="csrf" value="<?=e($csrf)?>"><input type="hidden" name="action" value="review"><input type="hidden" name="id" value="<?=(int)$j['id']?>"><button class="small primary">★ Bewertung per WhatsApp</button></form><?=$j['review_requested_at']?'<span class="muted small-note">angefragt</span>':''?><?php endif?>
 <details class="move"><summary>Ändern</summary><div class="move-sheet"><?=$form($j)?><form method="post"><input type="hidden" name="csrf" value="<?=e($csrf)?>"><input type="hidden" name="action" value="delete"><input type="hidden" name="id" value="<?=(int)$j['id']?>"><input type="hidden" name="m" value="<?=e($month)?>"><button class="small ghost">Einsatz löschen</button></form></div></details></div>
</article><?php endforeach?></div>
<?php gszPageEnd();
