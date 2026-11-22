<?php
declare(strict_types=1);
require __DIR__.'/bootstrap.php';
if (empty($_SESSION['admin_id'])) redirect('index.php');
$error='';
$selected=max(0,(int)($_GET['property_id']??$_POST['property_id']??0));
$st=$pdo->query('SELECT id,name,address FROM properties ORDER BY name,id LIMIT 2000');
$properties=$st->fetchAll();
$allowed=array_fill_keys(array_map(static fn($r)=>(int)$r['id'],$properties),true);
if($selected && !isset($allowed[$selected])){$selected=0;$error='Objekt nicht gefunden.';}
if($_SERVER['REQUEST_METHOD']==='POST'){
 checkCsrf();
 $pid=(int)($_POST['property_id']??0);
 $tid=(int)($_POST['task_id']??0);
 $date=(string)($_POST['performed_on']??'');
 $minutes=filter_var($_POST['duration_minutes']??null,FILTER_VALIDATE_INT);
 $description=trim((string)($_POST['description']??''));
 $notes=trim((string)($_POST['notes']??''));
 $validDate=preg_match('/^\d{4}-\d{2}-\d{2}$/D',$date) && DateTimeImmutable::createFromFormat('!Y-m-d',$date)?->format('Y-m-d')===$date;
 if(!isset($allowed[$pid]) || !$validDate || $minutes===false || $minutes<1 || $minutes>1440 || $description==='' || mb_strlen($description)>500 || mb_strlen($notes)>5000){
  $error='Bitte Objekt, Datum, Dauer (1–1440 Minuten) und Beschreibung prüfen.';
 }else{
  if($tid>0){
   $st=$pdo->prepare('SELECT id FROM tasks WHERE id=? AND property_id=?');$st->execute([$tid,$pid]);
   if(!$st->fetchColumn())$error='Die Aufgabe gehört nicht zu diesem Objekt.';
  }
  if(!$error){
   $st=$pdo->prepare('INSERT INTO work_logs(property_id,task_id,performed_on,duration_minutes,description,notes) VALUES(?,?,?,?,?,?)');
   $st->execute([$pid,$tid?:null,$date,$minutes,$description,$notes?:null]);
   redirect('worklogs.php?property_id='.$pid.'&saved=1');
  }
 }
}
$tasks=[];
if($selected){$st=$pdo->prepare('SELECT id,title FROM tasks WHERE property_id=? ORDER BY id DESC LIMIT 200');$st->execute([$selected]);$tasks=$st->fetchAll();}
$sql='SELECT w.id,w.performed_on,w.duration_minutes,w.description,w.notes,w.created_at,p.name AS property_name,t.title AS task_title FROM work_logs w JOIN properties p ON p.id=w.property_id LEFT JOIN tasks t ON t.id=w.task_id';
$params=[];
if($selected){$sql.=' WHERE w.property_id=?';$params[]=$selected;}
$sql.=' ORDER BY w.performed_on DESC,w.id DESC LIMIT 200';
$st=$pdo->prepare($sql);$st->execute($params);$logs=$st->fetchAll();
$totalMinutes=array_sum(array_map(static fn($l)=>(int)$l['duration_minutes'],$logs));
?><!doctype html><html lang="de"><head><meta charset="utf-8"><meta name="viewport" content="width=device-width,initial-scale=1"><meta name="robots" content="noindex,nofollow"><title>Arbeitsnachweise · ZGS</title><link rel="stylesheet" href="zentrale.css"><style>.note{white-space:pre-wrap;overflow-wrap:anywhere}.summary{font-weight:700}.hint{font-size:13px;color:#a6b4c0}</style></head><body><main><header><div class="brand"><img class="brand-logo" src="assets/gsz-logo-horizontal.webp" alt="Gebäudeservice Zentrum" width="640" height="188"></div><a href="index.php">← Dashboard</a></header><h1>Arbeitsnachweise</h1><p class="muted">Arbeiten objektbezogen dokumentieren – mit geschützter Fotodokumentation; PDF-Berichte als Download verfügbar.</p>
<?php if($error):?><p class="error" role="alert"><?=e($error)?></p><?php endif;?>
<?php if(isset($_GET['saved'])):?><p role="status">Arbeitsnachweis gespeichert.</p><?php endif;?>
<div class="grid"><section class="card"><h2>Arbeit dokumentieren</h2><form method="post"><input type="hidden" name="csrf" value="<?=e(csrf())?>">
<label>Objekt<select name="property_id" required id="worklog-property" onchange="location.href='worklogs.php?property_id='+encodeURIComponent(this.value)"><option value="">Objekt auswählen</option><?php foreach($properties as $p):?><option value="<?=(int)$p['id']?>" <?=$selected===(int)$p['id']?'selected':''?>><?=e($p['name'])?></option><?php endforeach;?></select></label>
<?php if($selected):?><label>Aufgabe (optional)<select name="task_id"><option value="">Keine Aufgabe zuordnen</option><?php foreach($tasks as $t):?><option value="<?=(int)$t['id']?>"><?=e($t['title'])?></option><?php endforeach;?></select></label><?php endif;?>
<label>Ausgeführt am<input type="date" name="performed_on" value="<?=e((string)($_POST['performed_on']??date('Y-m-d')))?>" required></label>
<label>Dauer in Minuten<input type="number" name="duration_minutes" min="1" max="1440" required value="<?=e((string)($_POST['duration_minutes']??''))?>" placeholder="z. B. 90"></label>
<label>Durchgeführte Arbeit<input name="description" maxlength="500" required value="<?=e((string)($_POST['description']??''))?>" placeholder="z. B. Laub auf Parkplatz entfernt"></label>
<label>Besonderheiten (optional)<textarea name="notes" maxlength="5000" rows="4"><?=e((string)($_POST['notes']??''))?></textarea></label>
<button type="submit">Arbeitsnachweis speichern</button></form><p class="hint">Nachweise sind bewusst nicht nachträglich bearbeitbar. Eine Korrektur- und Historienfunktion folgt.</p></section>
<section class="card"><h2>Letzte Nachweise</h2><p class="summary"><?=count($logs)?> Einträge · <?=number_format($totalMinutes/60,2,',','.')?> Stunden</p><p class="hint">Summe der maximal 200 angezeigten Einträge, kein vollständiger Zeitraumbericht.</p>
<?php if(!$logs):?><p class="muted">Noch keine Arbeitsnachweise.</p><?php endif;?>
<?php foreach($logs as $log):?><div class="item"><strong><?=e($log['description'])?></strong> · <a href="worklog_photos.php?id=<?=(int)$log['id']?>">📷 Fotos</a> · <a href="worklog_pdf.php?id=<?=(int)$log['id']?>">📄 PDF herunterladen</a><div class="muted"><?=e($log['performed_on'])?> · <?=(int)$log['duration_minutes']?> Minuten · <?=e($log['property_name'])?></div><?php if($log['task_title']):?><div class="hint">Aufgabe: <?=e($log['task_title'])?></div><?php endif;?><?php if($log['notes']):?><p class="note"><?=e($log['notes'])?></p><?php endif;?></div><?php endforeach;?></section></div></main></body></html>
