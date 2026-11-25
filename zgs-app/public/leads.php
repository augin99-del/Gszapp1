<?php
declare(strict_types=1);
require __DIR__.'/bootstrap.php';
if(empty($_SESSION['admin_id']))redirect('index.php');
require_once dirname(__DIR__).'/private/leads.php';require_once dirname(__DIR__).'/private/ui.php';
$admin=(int)$_SESSION['admin_id'];
if($_SERVER['REQUEST_METHOD']==='POST'){checkCsrf();$a=(string)($_POST['action']??'');$id=(int)($_POST['id']??0);$back='leads.php'.(isset($_POST['view'])&&isset(GSZ_LEAD_STATUS[$_POST['view']])?'?status='.$_POST['view']:'');
 try{
  switch($a){
   case 'add':$id=gszLeadSave($pdo,$admin,$_POST);$_SESSION['app_notice']='Anfrage gespeichert.';break;
   case 'status':gszLeadStatus($pdo,$admin,$id,(string)($_POST['status']??''));$_SESSION['app_notice']='Status: '.GSZ_LEAD_STATUS[$_POST['status']].'.';break;
   case 'note':gszLeadNote($pdo,$id,(string)($_POST['note']??''));$_SESSION['app_notice']='Notiz gespeichert.';break;
   case 'task':gszLeadToTask($pdo,$admin,$id);$_SESSION['app_notice']='Als Aufgabe für heute angelegt.';break;
   case 'review':$l=gszLead($pdo,$id);if(!$l)throw new RuntimeException('Anfrage nicht gefunden.');gszLeadReviewed($pdo,$id);header('Location: '.gszReviewLink($pdo,(string)$l['phone'],(string)$l['name']),true,303);exit;
   case 'review_free':header('Location: '.gszReviewLink($pdo,(string)($_POST['phone']??''),(string)($_POST['name']??'')),true,303);exit;
   case 'settings':gszSettingsSave($pdo,$admin,$_POST);$_SESSION['app_notice']='Einstellungen gespeichert.';break;
   default:throw new RuntimeException('Aktion unbekannt.');
  }
 }catch(Throwable $e){gszUiError($e);}
 redirect($back);
}
$imported=gszLeadsFromMail($pdo);
$status=isset(GSZ_LEAD_STATUS[$_GET['status']??''])?(string)$_GET['status']:null;$leads=gszLeads($pdo,$status);$counts=gszLeadCounts($pdo);$csrf=csrf();
$f=fn(string $a,int $id=0)=>'<input type="hidden" name="csrf" value="'.e($csrf).'"><input type="hidden" name="action" value="'.e($a).'"><input type="hidden" name="id" value="'.$id.'">'.($status?'<input type="hidden" name="view" value="'.e($status).'">':'');
$when=fn(string $ts)=>(new DateTimeImmutable($ts.' UTC'))->setTimezone(new DateTimeZone('Europe/Berlin'))->format('d.m., H:i');
gszPageStart('Anfragen','Von der Website, per Telefon oder WhatsApp – nichts geht verloren.');?>
<?php if($imported):?><p class="notice flash" role="status"><?=$imported===1?'1 neue Anfrage von der Website übernommen.':$imported.' neue Anfragen von der Website übernommen.'?></p><?php endif?>
<?php $reviewBox=function()use($f){?><details class="card" id="bewertung"<?=isset($_GET['bewertung'])?' open':''?>><summary>Bewertung anfragen – ohne Anfrage (z. B. nach einer Türöffnung)</summary><form method="post" target="_blank"><?=$f('review_free')?>
 <div class="grid-3"><label>Vorname (optional)<input name="name" maxlength="60"></label><label>Handynummer (optional)<input name="phone" type="tel" maxlength="40"></label></div>
 <p class="hint">Öffnet WhatsApp mit fertigem Text und deinem Google-Bewertungslink. Ohne Nummer wählst du den Kontakt in WhatsApp selbst.</p><button class="primary">WhatsApp öffnen</button></form>
 <p class="hint">Zum Mitgeben nach dem Einsatz: <a href="bewertungskarten.php" target="_blank" rel="noopener">Bewertungskarten mit QR-Code drucken</a> (10 Karten 85 × 55 mm auf A4, mit Schnittmarken – am besten auf 250-g-Karton).</p></details><?php };if(isset($_GET['bewertung']))$reviewBox();?>
<nav class="seg-tabs" aria-label="Status"><a href="leads.php"<?=$status===null?' aria-current="page"':''?>>Offen <span class="count"><?=$counts['neu']+$counts['kontaktiert']+$counts['angebot']?></span></a><?php foreach(['auftrag','abgesagt'] as $k):?><a href="leads.php?status=<?=$k?>"<?=$status===$k?' aria-current="page"':''?>><?=e(GSZ_LEAD_STATUS[$k])?> <span class="count"><?=$counts[$k]?></span></a><?php endforeach?></nav>
<?php if(!$leads):?><div class="empty"><strong><?=$status?'Keine Einträge.':'Keine offenen Anfragen.'?></strong>Anfragen über das Kontaktformular der Website erscheinen hier automatisch, sobald die Mail abgerufen ist.</div><?php endif?>
<div class="lead-list"><?php foreach($leads as $l):$wa=gszWaNumber((string)$l['phone']);?>
 <article class="card lead st-<?=e($l['status'])?>" id="anfrage-<?=(int)$l['id']?>">
  <div class="lead-head"><strong><?=e($l['name']?:($l['company']?:'Ohne Namen'))?></strong><span class="tag lead-<?=e($l['status'])?>"><?=e(GSZ_LEAD_STATUS[$l['status']])?></span></div>
  <p class="lead-meta"><?=e(trim(($l['service']?:'Anfrage').($l['place']?' · '.$l['place']:'').' · '.GSZ_LEAD_SOURCES[$l['source']].' · '.$when($l['created_at']),' ·'))?><?=$l['company']&&$l['name']?' · '.e($l['company']):''?><?=$l['object_type']?' · '.e($l['object_type']):''?></p>
  <?php if($l['message']):?><p class="lead-msg"><?=nl2br(e($l['message']))?></p><?php endif?>
  <div class="lead-actions"><?php if($l['phone']):?><a class="btn small primary" href="tel:<?=e(preg_replace('/[^0-9+]/','',$l['phone']))?>">Anrufen</a><?php if($wa):?><a class="btn small" href="https://wa.me/<?=e($wa)?>" target="_blank" rel="noopener">WhatsApp</a><?php endif?><?php endif?><?php if($l['email']):?><a class="btn small" href="compose.php?to=<?=e(rawurlencode($l['email']))?>&amp;subject=<?=e(rawurlencode('Ihre Anfrage bei Zentrum Gebäudeservice'))?>">Mail</a><?php endif?>
   <?php if(preg_match('/schl(ü|ue)ssel|tür|aufsperr/iu',(string)$l['service'].' '.(string)$l['message'])):?><a class="btn small" href="einsaetze.php?lead=<?=(int)$l['id']?>#neu">Als Einsatz erfassen</a><?php endif?>
   <?php if(!$l['task_id']):?><form method="post"><?=$f('task',(int)$l['id'])?><button class="small">Als Aufgabe für heute</button></form><?php else:?><span class="muted small-note">Aufgabe angelegt</span><?php endif?></div>
  <div class="status-row"><?php foreach(GSZ_LEAD_STATUS as $k=>$lab):if($k===$l['status'])continue;?><form method="post"><?=$f('status',(int)$l['id'])?><input type="hidden" name="status" value="<?=e($k)?>"><button class="small ghost"><?=e($lab)?></button></form><?php endforeach?></div>
  <?php if($l['status']==='auftrag'&&$wa):?><form method="post" class="review-row" target="_blank"><?=$f('review',(int)$l['id'])?><button class="small primary">★ Bewertung per WhatsApp anfragen</button><?=$l['review_requested_at']?'<span class="muted small-note">angefragt am '.e((new DateTimeImmutable($l['review_requested_at']))->format('d.m.')).'</span>':''?></form><?php endif?>
  <details class="move"><summary>Notiz<?=$l['note']!==''?': '.e(mb_strimwidth($l['note'],0,40,'…')):''?></summary><div class="move-sheet"><form method="post"><?=$f('note',(int)$l['id'])?><label class="sr-only" for="n<?=(int)$l['id']?>">Notiz</label><input id="n<?=(int)$l['id']?>" name="note" maxlength="1000" value="<?=e($l['note'])?>"><button class="small">Speichern</button></form></div></details>
  <?php if($l['source_page']):?><p class="hint">Gesendet von: <?=e($l['source_page'])?></p><?php endif?>
 </article><?php endforeach?></div>
<details class="card" id="neu"><summary>Anfrage selbst erfassen (Telefon, WhatsApp …)</summary><form method="post"><?=$f('add')?>
 <div class="grid-3"><label>Name<input name="name" maxlength="190"></label><label>Telefon<input name="phone" type="tel" maxlength="80"></label><label>Woher?<select name="source"><?php foreach(GSZ_LEAD_SOURCES as $k=>$lab):if($k==='website')continue;?><option value="<?=e($k)?>"><?=e($lab)?></option><?php endforeach?></select></label></div>
 <div class="grid-3"><label>Leistung<input name="service" maxlength="120" list="svc" placeholder="z. B. Treppenhausreinigung"></label><label>Ort<input name="place" maxlength="190"></label><label>E-Mail<input name="email" type="email" maxlength="190"></label></div>
 <datalist id="svc"><option value="Schlüsseldienst"><option value="Treppenhausreinigung"><option value="Hausmeisterservice"><option value="Gebäudereinigung"><option value="Außenanlagenpflege"><option value="Winterdienst"></datalist>
 <label>Worum geht es?<textarea name="message" rows="3" maxlength="5000"></textarea></label><button class="primary">Speichern</button></form></details>
<?php if(!isset($_GET['bewertung']))$reviewBox();?>
<details class="card"><summary>Einstellungen</summary><form method="post"><?=$f('settings')?>
 <label>Google-Bewertungslink<input name="review_url" maxlength="500" value="<?=e(gszSetting($pdo,'review_url'))?>"></label>
 <label>Dein Name in Nachrichten<input name="owner_name" maxlength="120" value="<?=e(gszSetting($pdo,'owner_name'))?>"></label>
 <label>Telefon für Aushang und Karten<input name="company_phone" maxlength="40" value="<?=e(gszSetting($pdo,'company_phone'))?>"></label>
 <label>Website (für Notdienst-Status und Zahlen)<input name="website_origin" maxlength="120" value="<?=e(gszSetting($pdo,'website_origin'))?>"></label><button class="small primary">Speichern</button></form>
 <p class="hint">Vorschau der Bewertungsnachricht:</p><pre><?=e(gszReviewText($pdo,'Max'))?></pre></details>
<?php gszPageEnd();
