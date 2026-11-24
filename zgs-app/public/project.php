<?php
declare(strict_types=1);
require __DIR__.'/bootstrap.php';
if(empty($_SESSION['admin_id']))redirect('index.php');
require_once dirname(__DIR__).'/private/projects.php';require_once dirname(__DIR__).'/private/ui.php';
$id=(int)($_GET['id']??$_POST['id']??0);$p=$id>0?gszProjectGet($pdo,$id):null;if(!$p){http_response_code(404);exit('Projekt nicht gefunden.');}
$tabs=['uebersicht'=>'Übersicht','gewerke'=>'Gewerke','termine'=>'Termine','kosten'=>'Kosten','maengel'=>'Mängel & To-dos','fotos'=>'Fotos & Dokumente','handwerker'=>'Handwerker'];
$tab=isset($tabs[$_GET['tab']??''])?(string)$_GET['tab']:'uebersicht';$admin=(int)$_SESSION['admin_id'];$json=($_SERVER['HTTP_X_GSZ_FETCH']??'')==='1';
if($_SERVER['REQUEST_METHOD']==='POST'){checkCsrf();$a=(string)($_POST['action']??'');$back=isset($tabs[$_POST['tab']??''])?(string)$_POST['tab']:$tab;$rid=(int)($_POST['rid']??0);
 try{
  switch($a){
   case 'project_update':gszProjectSave($pdo,$admin,$_POST,$id);$msg='Projekt gespeichert.';break;
   case 'project_delete':if(empty($_POST['confirm']))throw new RuntimeException('Bitte das Löschen bestätigen.');gszProjectDelete($pdo,$admin,$id);$_SESSION['app_notice']='Projekt gelöscht.';redirect('projects.php');
   case 'trade_add':gszTradeSave($pdo,$id,$_POST);$msg='Gewerk angelegt.';break;
   case 'trade_update':gszTradeSave($pdo,$id,$_POST,$rid);$msg='Gewerk gespeichert.';break;
   case 'trade_status':gszTradeStatus($pdo,$id,$rid,(string)($_POST['status']??''));$msg='Status geändert.';break;
   case 'trade_delete':gszTradeDelete($pdo,$id,$rid);$msg='Gewerk gelöscht.';break;
   case 'trade_template':$n=gszTradeTemplate($pdo,$id);$msg=$n?$n.' typische Gewerke ergänzt.':'Alle typischen Gewerke sind schon da.';break;
   case 'event_add':gszEventSave($pdo,$admin,$id,$_POST);$msg='Termin eingetragen – steht auch in deiner Woche.';break;
   case 'event_delete':gszEventDelete($pdo,$id,$rid);$msg='Termin gelöscht.';break;
   case 'cost_add':gszCostSave($pdo,$admin,$id,$_POST,$_FILES['file']??null);$msg=($_POST['kind']??'')==='angebot'?'Angebot erfasst.':'Rechnung erfasst.';break;
   case 'cost_paid':gszCostFlag($pdo,$id,$rid,'paid',($_POST['on']??'1')==='1');$msg='Gespeichert.';break;
   case 'cost_accepted':gszCostFlag($pdo,$id,$rid,'accepted',($_POST['on']??'1')==='1');$msg='Gespeichert.';break;
   case 'cost_delete':gszCostDelete($pdo,$id,$rid);$msg='Gelöscht.';break;
   case 'item_add':gszItemSave($pdo,$id,$_POST,$_FILES['photo']??null);$msg=($_POST['kind']??'')==='mangel'?'Mangel notiert.':'To-do notiert.';break;
   case 'item_done':$r=gszItemDone($pdo,$id,$rid,($_POST['done']??'1')==='1',$_POST['row_version']??null);if($json){header('Content-Type: application/json; charset=utf-8');echo json_encode(['ok'=>true,'done'=>(bool)$r['done_at'],'row_version'=>(int)$r['row_version']]);exit;}$msg='Gespeichert.';break;
   case 'item_delete':gszItemDelete($pdo,$id,$rid);$msg='Gelöscht.';break;
   case 'file_add':if(!gszPHasUpload($_FILES['file']??null))throw new RuntimeException('Bitte eine Datei auswählen.');gszPFileStore($pdo,$id,$_FILES['file'],$_POST);$msg='Datei gespeichert.';break;
   case 'file_delete':gszPFileDelete($pdo,$id,$rid);$msg='Datei gelöscht.';break;
   case 'contractor_add':gszContractorSave($pdo,$admin,$_POST);$msg='Handwerker gespeichert.';break;
   case 'contractor_update':gszContractorSave($pdo,$admin,$_POST,$rid);$msg='Gespeichert.';break;
   case 'contractor_delete':gszContractorDelete($pdo,$rid);$msg='Handwerker entfernt.';break;
   default:throw new RuntimeException('Aktion unbekannt.');
  }
  $_SESSION['app_notice']=$msg;
 }catch(Throwable $e){if($json){header('Content-Type: application/json; charset=utf-8');echo json_encode(['ok'=>false,'error'=>$e instanceof PDOException?'Speichern nicht möglich.':$e->getMessage()],JSON_UNESCAPED_UNICODE);exit;}gszUiError($e);}
 redirect('project.php?id='.$id.'&tab='.$back);
}
$base='project.php?id='.$id;$csrf=csrf();
$f=fn(string $action,int $rid=0)=>'<input type="hidden" name="csrf" value="'.e($csrf).'"><input type="hidden" name="action" value="'.e($action).'"><input type="hidden" name="tab" value="'.e($tab).'"><input type="hidden" name="id" value="'.$id.'">'.($rid?'<input type="hidden" name="rid" value="'.$rid.'">':'');
$trades=gszTrades($pdo,$id);$contractors=gszContractors($pdo);
$sel=function(string $name,array $rows,mixed $current,string $empty,string $label='name')use(&$sel):string{$h='<select name="'.e($name).'"><option value="">'.e($empty).'</option>';foreach($rows as $r)$h.='<option value="'.(int)$r['id'].'"'.((int)$current===(int)$r['id']?' selected':'').'>'.e($r[$label]).(isset($r['trade'])&&$r['trade']!==''?' ('.e($r['trade']).')':'').'</option>';return $h.'</select>';};
$d=fn(?string $x)=>$x?(new DateTimeImmutable($x))->format('d.m.Y'):'';$dd=fn(?string $x)=>$x?(new DateTimeImmutable($x))->format('d.m.'):'';
$tel=fn(?string $ph)=>$ph?'<a href="tel:'.e(preg_replace('/[^0-9+]/','',$ph)).'">'.e($ph).'</a>':'';
gszPageStart($p['title'],trim(($p['building_name']?$p['building_name'].' · ':'').GSZ_PROJECT_STATUS[$p['status']]));?>
<a class="back-link" href="projects.php">‹ Bauprojekte</a>
<nav class="seg-tabs project-tabs" aria-label="Bereiche"><?php foreach($tabs as $k=>$l):?><a href="<?=e($base.'&tab='.$k)?>"<?=$k===$tab?' aria-current="page"':''?>><?=e($l)?></a><?php endforeach?></nav>
<?php if($tab==='uebersicht'):$s=gszProjectSummary($pdo,$id);?>
<div class="kpis">
 <div class="kpi"><span class="k">Fortschritt</span><strong><?=$s['progress']?> %</strong><span class="progress"><span style="width:<?=$s['progress']?>%"></span></span><small><?=$s['trades']['fertig']?> von <?=$s['trades_total']?> Gewerken fertig · <?=$s['trades']['laeuft']?> laufen</small></div>
 <div class="kpi"><span class="k">Kosten</span><strong><?=e(gszMoney($s['invoiced']))?></strong><?php if($s['budget']!==null):$pc=$s['budget']?min(100,(int)round(100*$s['invoiced']/$s['budget'])):0;?><span class="progress<?=$s['invoiced']>$s['budget']?' over':''?>"><span style="width:<?=$pc?>%"></span></span><small>von <?=e(gszMoney($s['budget']))?> Budget<?=$s['invoiced']>$s['budget']?' – <b>überschritten</b>':''?></small><?php else:?><small>Noch kein Budget eingetragen</small><?php endif?></div>
 <div class="kpi"><span class="k">Offen zu zahlen</span><strong><?=e(gszMoney($s['open_to_pay']))?></strong><small>Bezahlt <?=e(gszMoney($s['paid']))?><?=$s['committed']?' · beauftragt (Angebote) '.e(gszMoney($s['committed'])):''?></small></div>
 <div class="kpi"><span class="k">Mängel & To-dos</span><strong><?=$s['items_open']?></strong><small><?=$s['defects_open']?> Mängel<?=$s['items_late']?' · <b>'.$s['items_late'].' überfällig</b>':''?></small></div>
</div>
<?php $span=array_values(array_filter($trades,fn($t)=>$t['start_date']&&$t['end_date']));if($span):$min=min(array_column($span,'start_date'));$max=max(array_column($span,'end_date'));$days=max(1,(int)(new DateTimeImmutable($min))->diff(new DateTimeImmutable($max))->format('%a')+1);$today=date('Y-m-d');?>
<section class="card"><h2>Bauzeitenplan</h2><div class="gantt"><?php if($today>=$min&&$today<=$max):?><span class="gantt-today" style="left:calc(var(--lab) + (100% - var(--lab)) * <?=round(((int)(new DateTimeImmutable($min))->diff(new DateTimeImmutable($today))->format('%a'))/$days,4)?>)"></span><?php endif?>
<?php foreach($span as $t):$l=(int)(new DateTimeImmutable($min))->diff(new DateTimeImmutable($t['start_date']))->format('%a');$w=(int)(new DateTimeImmutable($t['start_date']))->diff(new DateTimeImmutable($t['end_date']))->format('%a')+1;?>
 <div class="g-row"><span class="g-lab"><?=e($t['name'])?></span><span class="g-track"><span class="g-bar st-<?=e($t['status'])?>" style="left:<?=round(100*$l/$days,2)?>%;width:<?=max(1.5,round(100*$w/$days,2))?>%" title="<?=e($dd($t['start_date']).'–'.$dd($t['end_date']))?>"></span></span></div><?php endforeach?>
</div><p class="hint"><?=e($d($min))?> – <?=e($d($max))?> · Grau offen/geplant · Grün läuft · Dunkel fertig<?=count($span)<count($trades)?' · Gewerke ohne Zeitraum fehlen hier':''?></p></section><?php endif?>
<div class="two-col">
 <section class="card"><h2>Nächste Termine</h2><?php if($s['next_events']):?><div class="list"><?php foreach($s['next_events'] as $e):?><div class="li"><span class="name"><?=e($e['title'])?><span class="sub"><?=e(gszDayLabelShort($e['day']).', '.substr($e['start_time'],0,5).'–'.substr($e['end_time'],0,5))?><?=$e['contractor_phone']?' · '.$tel($e['contractor_phone']):''?></span></span></div><?php endforeach?></div><?php else:?><p class="muted">Keine Termine eingetragen.</p><?php endif?><p><a href="<?=e($base.'&tab=termine')?>">Termine planen</a></p></section>
 <section class="card"><h2>Gewährleistung</h2><?php if($s['warranties']):?><div class="list"><?php foreach($s['warranties'] as $w):?><div class="li"><span class="name"><?=e($w['name'])?><span class="sub">bis <?=e($d($w['warranty_until']))?></span></span></div><?php endforeach?></div><p class="hint">60 Tage vor Ablauf erinnert dich „Heute“ daran, Mängel zu prüfen.</p><?php else:?><p class="muted">Gewährleistungsende pro Gewerk unter „Gewerke“ eintragen – dann erinnert die Zentrale rechtzeitig.</p><?php endif?></section>
</div>
<details class="card"><summary>Projektdaten ändern</summary><form method="post"><?=$f('project_update')?>
 <label>Name<input name="title" maxlength="190" required value="<?=e($p['title'])?>"></label>
 <label>Haus<select name="building_id"><option value="">– ohne Zuordnung –</option><?php foreach($pdo->query('SELECT id,name FROM estate_buildings ORDER BY name') as $b):?><option value="<?=(int)$b['id']?>"<?=(int)$p['building_id']===(int)$b['id']?' selected':''?>><?=e($b['name'])?></option><?php endforeach?></select></label>
 <label>Status<select name="status"><?php foreach(GSZ_PROJECT_STATUS as $k=>$l):?><option value="<?=e($k)?>"<?=$p['status']===$k?' selected':''?>><?=e($l)?></option><?php endforeach?></select></label>
 <div class="grid-3"><label>Start<input type="date" name="start_date" value="<?=e((string)$p['start_date'])?>"></label><label>Fertig bis<input type="date" name="target_date" value="<?=e((string)$p['target_date'])?>"></label><label>Budget (€)<input name="budget" inputmode="decimal" value="<?=$p['budget_cents']!==null?e(number_format((int)$p['budget_cents']/100,2,',','.')):''?>"></label></div>
 <label>Notizen<textarea name="notes" rows="3"><?=e((string)$p['notes'])?></textarea></label><button class="primary">Speichern</button></form>
 <form method="post" class="danger-zone" onsubmit="return confirm('Projekt mit allen Gewerken, Terminen, Kosten, Mängeln und Dateien löschen?')"><?=$f('project_delete')?><label class="check-label"><input type="checkbox" name="confirm" value="1" required> Ja, Projekt endgültig löschen</label><button class="small danger">Projekt löschen</button></form></details>

<?php elseif($tab==='gewerke'):?>
<?php if($trades):?><div class="blocks trades"><?php foreach($trades as $t):?>
 <article class="block trade st-<?=e($t['status'])?>"><div class="what"><span class="obj"><?=e($t['name'])?></span>
  <span class="act"><span class="tag status-<?=e($t['status'])?>"><?=e(GSZ_TRADE_STATUS[$t['status']])?></span><?=$t['contractor_name']?' · '.e($t['contractor_name']):''?><?=$t['start_date']?' · '.e($dd($t['start_date']).($t['end_date']?'–'.$dd($t['end_date']):'')):''?></span>
  <span class="note-line"><?=$t['budget_cents']!==null?'Budget '.e(gszMoney((int)$t['budget_cents'])).' · ':''?>abgerechnet <?=e(gszMoney((int)$t['invoiced_cents']))?><?=$t['open_items']?' · '.(int)$t['open_items'].' offen':''?><?=$t['warranty_until']?' · Gewährleistung bis '.e($d($t['warranty_until'])):''?><?=$t['contractor_phone']?' · '.$tel($t['contractor_phone']):''?></span>
  <?php if($t['notes']!==''):?><span class="note-line muted"><?=e($t['notes'])?></span><?php endif?>
  <div class="status-row"><?php foreach(GSZ_TRADE_STATUS as $k=>$l):if($k===$t['status'])continue;?><form method="post"><?=$f('trade_status',(int)$t['id'])?><input type="hidden" name="status" value="<?=e($k)?>"><button class="small ghost"><?=e($l)?></button></form><?php endforeach?></div>
  <details class="move"><summary>Bearbeiten</summary><div class="move-sheet"><form method="post"><?=$f('trade_update',(int)$t['id'])?>
   <label>Gewerk<input name="name" maxlength="120" required value="<?=e($t['name'])?>"></label><label>Handwerker<?=$sel('contractor_id',$contractors,$t['contractor_id'],'– noch offen –')?></label>
   <label>Status<select name="status"><?php foreach(GSZ_TRADE_STATUS as $k=>$l):?><option value="<?=e($k)?>"<?=$t['status']===$k?' selected':''?>><?=e($l)?></option><?php endforeach?></select></label>
   <div class="grid-3"><label>Von<input type="date" name="start_date" value="<?=e((string)$t['start_date'])?>"></label><label>Bis<input type="date" name="end_date" value="<?=e((string)$t['end_date'])?>"></label><label>Budget (€)<input name="budget" inputmode="decimal" value="<?=$t['budget_cents']!==null?e(number_format((int)$t['budget_cents']/100,2,',','.')):''?>"></label></div>
   <label>Gewährleistung bis<input type="date" name="warranty_until" value="<?=e((string)$t['warranty_until'])?>"></label><label>Notiz<input name="notes" maxlength="1000" value="<?=e($t['notes'])?>"></label><button class="small primary">Speichern</button></form>
   <form method="post" onsubmit="return confirm('Gewerk löschen?')"><?=$f('trade_delete',(int)$t['id'])?><button class="small danger">Löschen</button></form></div></details>
 </div></article><?php endforeach?></div><?php else:?><div class="empty"><strong>Noch keine Gewerke.</strong>Lege sie einzeln an oder übernimm die typische Liste.</div><?php endif?>
<details class="card"<?=$trades?'':' open'?>><summary>Gewerk hinzufügen</summary><form method="post"><?=$f('trade_add')?>
 <label>Gewerk<input name="name" maxlength="120" required list="trade-list" placeholder="z. B. Elektro"></label><datalist id="trade-list"><?php foreach(GSZ_TRADE_TEMPLATE as $x):?><option value="<?=e($x)?>"><?php endforeach?></datalist>
 <label>Handwerker<?=$sel('contractor_id',$contractors,null,'– noch offen –')?></label>
 <div class="grid-3"><label>Von<input type="date" name="start_date"></label><label>Bis<input type="date" name="end_date"></label><label>Budget (€)<input name="budget" inputmode="decimal"></label></div>
 <label>Gewährleistung bis<input type="date" name="warranty_until"></label><button class="primary">Hinzufügen</button></form>
 <form method="post"><?=$f('trade_template')?><button class="small">Typische Gewerke einer Sanierung ergänzen</button></form></details>

<?php elseif($tab==='termine'):$events=gszEvents($pdo,$id);$up=array_filter($events,fn($e)=>$e['day']>=date('Y-m-d'));$past=array_filter($events,fn($e)=>$e['day']<date('Y-m-d'));?>
<p class="hint">Termine erscheinen automatisch in deiner Woche und auf „Heute“.</p>
<?php if($up):?><div class="list"><?php foreach(array_reverse($up) as $e):?><div class="li"><span class="name"><?=e($e['title'])?><span class="sub"><?=e(gszDayLabelShort($e['day']).', '.substr($e['start_time'],0,5).'–'.substr($e['end_time'],0,5))?><?=$e['contractor_name']?' · '.e($e['contractor_name']):''?><?=$e['contractor_phone']?' · '.$tel($e['contractor_phone']):''?></span></span><form method="post" class="inline" onsubmit="return confirm('Termin löschen?')"><?=$f('event_delete',(int)$e['id'])?><button class="small ghost">Löschen</button></form></div><?php endforeach?></div><?php else:?><div class="empty"><strong>Keine anstehenden Termine.</strong>Trag ein, wann welcher Handwerker kommt.</div><?php endif?>
<details class="card" open><summary>Termin eintragen</summary><form method="post"><?=$f('event_add')?>
 <div class="grid-3"><label>Tag<input type="date" name="day" required value="<?=e(date('Y-m-d'))?>"></label><label>Von<input type="time" name="start_time" required value="08:00"></label><label>Bis<input type="time" name="end_time" required value="12:00"></label></div>
 <label>Gewerk<?=$sel('trade_id',$trades,null,'– ohne –')?></label><label>Handwerker<?=$sel('contractor_id',$contractors,null,'– ohne –')?></label>
 <label>Worum geht es? (optional)<input name="title" maxlength="190" placeholder="z. B. Elektro-Abnahme Wohnung 2"></label><button class="primary">Eintragen</button></form></details>
<?php if($past):?><details class="card"><summary>Vergangene Termine (<?=count($past)?>)</summary><div class="list"><?php foreach($past as $e):?><div class="li"><span class="name"><?=e($e['title'])?><span class="sub"><?=e($d($e['day']))?></span></span></div><?php endforeach?></div></details><?php endif?>

<?php elseif($tab==='kosten'):$costs=gszCosts($pdo,$id);$s=gszProjectSummary($pdo,$id);?>
<div class="kpis"><div class="kpi"><span class="k">Rechnungen</span><strong><?=e(gszMoney($s['invoiced']))?></strong><small><?=$s['budget']!==null?'Budget '.e(gszMoney($s['budget'])):'kein Budget'?></small></div><div class="kpi"><span class="k">Bezahlt</span><strong><?=e(gszMoney($s['paid']))?></strong><small>offen <?=e(gszMoney($s['open_to_pay']))?></small></div><div class="kpi"><span class="k">Angebote angenommen</span><strong><?=e(gszMoney($s['committed']))?></strong><small>beauftragt</small></div></div>
<p class="hint">Nur Übersicht für dich – deine Buchhaltung bleibt in Lexware.</p>
<?php foreach(['rechnung'=>'Rechnungen','angebot'=>'Angebote'] as $kind=>$label):$rows=array_filter($costs,fn($c)=>$c['kind']===$kind);if(!$rows)continue;?>
<h2 class="section-title"><?=e($label)?></h2><div class="list costs"><?php foreach($rows as $c):$late=$kind==='rechnung'&&!$c['paid_on']&&$c['due_date']&&$c['due_date']<=date('Y-m-d');?>
 <div class="li<?=$late?' is-late':''?>"><span class="name"><?=e($c['title'])?><span class="sub"><?=e(trim(($c['trade_name']?:'').($c['contractor_name']?' · '.$c['contractor_name']:''),' ·'))?><?=$c['doc_date']?' · vom '.e($d($c['doc_date'])):''?><?=$kind==='rechnung'?($c['paid_on']?' · bezahlt am '.e($d($c['paid_on'])):($c['due_date']?' · fällig '.e($d($c['due_date'])):'')):($c['accepted']?' · angenommen':'')?><?=$c['file_id']?' · <a href="project_file.php?id='.(int)$c['file_id'].'" target="_blank" rel="noopener">PDF ansehen</a>':''?></span></span>
  <strong class="amount"><?=e(gszMoney((int)$c['amount_cents']))?></strong>
  <form method="post" class="inline"><?=$f($kind==='rechnung'?'cost_paid':'cost_accepted',(int)$c['id'])?><input type="hidden" name="on" value="<?=($kind==='rechnung'?$c['paid_on']:$c['accepted'])?'0':'1'?>"><button class="small<?=($kind==='rechnung'?$c['paid_on']:$c['accepted'])?' ghost':''?>"><?=$kind==='rechnung'?($c['paid_on']?'Doch offen':'Bezahlt'):($c['accepted']?'Zurücknehmen':'Annehmen')?></button></form>
  <form method="post" class="inline" onsubmit="return confirm('Eintrag löschen?')"><?=$f('cost_delete',(int)$c['id'])?><button class="small ghost" aria-label="Löschen">✕</button></form></div><?php endforeach?></div><?php endforeach?>
<?php if(!$costs):?><div class="empty"><strong>Noch keine Kosten erfasst.</strong>Angebote und Rechnungen der Handwerker hier eintragen – mit PDF.</div><?php endif?>
<details class="card" open><summary>Angebot oder Rechnung erfassen</summary><form method="post" enctype="multipart/form-data" class="quick-note"><?=$f('cost_add')?>
 <div class="seg" role="radiogroup" aria-label="Art"><label><input type="radio" name="kind" value="rechnung" checked><span>Rechnung</span></label><label><input type="radio" name="kind" value="angebot"><span>Angebot</span></label></div>
 <label>Beschreibung<input name="title" maxlength="190" required placeholder="z. B. Elektro Abschlag 1"></label>
 <div class="grid-3"><label>Betrag (€)<input name="amount" inputmode="decimal" required placeholder="z. B. 4.850,00"></label><label>Datum<input type="date" name="doc_date" value="<?=e(date('Y-m-d'))?>"></label><label class="cost-due">Fällig am<input type="date" name="due_date"></label></div>
 <label>Gewerk<?=$sel('trade_id',$trades,null,'– ohne –')?></label><label>Handwerker<?=$sel('contractor_id',$contractors,null,'– ohne –')?></label>
 <label>PDF / Foto (optional)<input type="file" name="file" accept="application/pdf,image/jpeg,image/png,image/webp"></label>
 <label class="check-label"><input type="checkbox" name="paid" value="1"> Schon bezahlt</label><button class="primary">Speichern</button></form></details>

<?php elseif($tab==='maengel'):$open=gszItems($pdo,$id,true);$done=gszItems($pdo,$id,false);$csrf2=$csrf;?>
<?php $units=array_values(array_unique(array_filter(array_column($open,'unit_label'))));?>
<?php if($open):?><div class="blocks notes"><?php foreach($open as $i):$late=$i['due_date']&&$i['due_date']<date('Y-m-d');?>
 <article class="block note<?=$late?' is-late':''?><?=$i['kind']==='mangel'?' is-defect':''?>"><div class="what"><span class="obj"><?=e($i['title'])?></span>
  <span class="act"><span class="tag"><?=$i['kind']==='mangel'?'Mangel':'To-do'?></span><?=$i['unit_label']!==''?' · '.e($i['unit_label']):''?><?=$i['trade_name']?' · '.e($i['trade_name']):''?><?=$i['due_date']?' · <span class="'.($late?'late':'age').'">bis '.e($d($i['due_date'])).'</span>':''?></span>
  <?php if($i['contractor_name']):?><span class="note-line"><?=e($i['contractor_name'])?><?=$i['contractor_phone']?' · '.$tel($i['contractor_phone']):''?></span><?php endif?>
  <?php if($i['photo_file_id']):?><a class="note-photo" href="project_file.php?id=<?=(int)$i['photo_file_id']?>" target="_blank" rel="noopener"><img src="project_file.php?id=<?=(int)$i['photo_file_id']?>" alt="Foto" width="72" height="72" loading="lazy"></a><?php endif?>
  <details class="move"><summary>Mehr</summary><div class="move-sheet"><form method="post" onsubmit="return confirm('Löschen?')"><?=$f('item_delete',(int)$i['id'])?><button class="small danger">Löschen</button></form></div></details></div>
  <div class="acts"><form method="post" action="<?=e($base.'&tab=maengel')?>"><?=$f('item_done',(int)$i['id'])?><input type="hidden" name="row_version" value="<?=(int)$i['row_version']?>"><input type="hidden" name="done" value="1"><button class="check" aria-label="<?=e('Erledigt: '.$i['title'])?>" title="Erledigt"><?=gszNavIcon('check')?></button></form></div></article><?php endforeach?></div>
<?php else:?><div class="empty"><strong>Nichts offen.</strong>Mängel und To-dos je Wohnung oder Gewerk hier notieren.</div><?php endif?>
<details class="card" open><summary>Mangel oder To-do notieren</summary><form method="post" enctype="multipart/form-data" class="quick-note"><?=$f('item_add')?>
 <div class="seg" role="radiogroup" aria-label="Art"><label><input type="radio" name="kind" value="todo" checked><span>To-do</span></label><label><input type="radio" name="kind" value="mangel"><span>Mangel</span></label></div>
 <label>Was?<input name="title" maxlength="190" required placeholder="z. B. Steckdose Küche fehlt"></label>
 <div class="grid-3"><label>Wohnung / Bereich<input name="unit_label" maxlength="80" list="unit-list" placeholder="z. B. Whg. 2 OG"></label><label>Bis wann<input type="date" name="due_date"></label><label>Foto<input type="file" name="photo" accept="image/jpeg,image/png,image/webp"></label></div>
 <datalist id="unit-list"><?php foreach(array_unique(array_merge($units,['Whg. 1 EG','Whg. 2 EG','Whg. 3 OG','Whg. 4 OG','Treppenhaus','Keller','Dach','Außen'])) as $u):?><option value="<?=e($u)?>"><?php endforeach?></datalist>
 <label>Gewerk<?=$sel('trade_id',$trades,null,'– ohne –')?></label><label>Wer kümmert sich?<?=$sel('contractor_id',$contractors,null,'– ich selbst / offen –')?></label><button class="primary">Notieren</button></form></details>
<?php if($done):?><details class="card"><summary>Erledigt (<?=count($done)?>)</summary><div class="list"><?php foreach(array_slice($done,0,100) as $i):?><div class="li"><span class="name"><?=e($i['title'])?><span class="sub"><?=e(trim($i['unit_label'].' · erledigt '.$d(substr((string)$i['done_at'],0,10)),' ·'))?></span></span></div><?php endforeach?></div></details><?php endif?>

<?php elseif($tab==='fotos'):$files=gszPFiles($pdo,$id);$photos=array_filter($files,fn($x)=>$x['kind']==='foto');$docs=array_filter($files,fn($x)=>$x['kind']==='dokument');?>
<details class="card" open><summary>Foto oder PDF hochladen</summary><form method="post" enctype="multipart/form-data"><?=$f('file_add')?>
 <label>Datei<input type="file" name="file" required accept="application/pdf,image/jpeg,image/png,image/webp"></label>
 <div class="grid-3"><label>Phase<select name="phase"><?php foreach(GSZ_FILE_PHASES as $k=>$l):?><option value="<?=e($k)?>"><?=e($l)?></option><?php endforeach?></select></label><label>Wohnung / Bereich<input name="unit_label" maxlength="80" placeholder="z. B. Bad Whg. 3"></label><label>Gewerk<?=$sel('trade_id',$trades,null,'– ohne –')?></label></div>
 <label>Beschreibung<input name="caption" maxlength="190" placeholder="z. B. Bad vor dem Umbau"></label><button class="primary">Hochladen</button></form></details>
<?php if($photos):?><h2 class="section-title">Fotos</h2><div class="photo-grid"><?php foreach($photos as $x):?><figure><a href="project_file.php?id=<?=(int)$x['id']?>" target="_blank" rel="noopener"><img src="project_file.php?id=<?=(int)$x['id']?>" alt="<?=e($x['caption']?:'Foto')?>" loading="lazy"></a><figcaption><?=$x['phase']!==''?'<span class="tag">'.e(GSZ_FILE_PHASES[$x['phase']]).'</span> ':''?><?=e(trim($x['caption'].($x['unit_label']!==''?' · '.$x['unit_label']:''),' ·'))?></figcaption><form method="post" onsubmit="return confirm('Foto löschen?')"><?=$f('file_delete',(int)$x['id'])?><button class="small ghost">Löschen</button></form></figure><?php endforeach?></div><?php endif?>
<?php if($docs):?><h2 class="section-title">Dokumente</h2><div class="list"><?php foreach($docs as $x):?><div class="li"><span class="name"><a href="project_file.php?id=<?=(int)$x['id']?>" target="_blank" rel="noopener"><?=e($x['caption']?:$x['original_name'])?></a><span class="sub"><?=e(trim(($x['trade_name']?:'').' · '.$d(substr((string)$x['created_at'],0,10)),' ·'))?></span></span><form method="post" class="inline" onsubmit="return confirm('Dokument löschen?')"><?=$f('file_delete',(int)$x['id'])?><button class="small ghost">Löschen</button></form></div><?php endforeach?></div><?php endif?>
<?php if(!$files):?><div class="empty"><strong>Noch keine Dateien.</strong>Vorher-/Nachher-Fotos, Pläne, Genehmigungen, Abnahmeprotokolle.</div><?php endif?>

<?php else:?>
<p class="hint">Deine Handwerker-Liste gilt für alle Projekte.</p>
<?php if($contractors):?><div class="list"><?php foreach($contractors as $c):?><div class="li"><span class="name"><?=e($c['name'])?><span class="sub"><?=e($c['trade'])?><?=$c['phone']?' · '.$tel($c['phone']):''?><?=$c['email']?' · <a href="mailto:'.e($c['email']).'">'.e($c['email']).'</a>':''?></span></span>
 <details class="move inline-edit"><summary>Ändern</summary><div class="move-sheet"><form method="post"><?=$f('contractor_update',(int)$c['id'])?><label>Name / Firma<input name="name" maxlength="190" required value="<?=e($c['name'])?>"></label><label>Gewerk<input name="trade" maxlength="120" value="<?=e($c['trade'])?>"></label><label>Telefon<input name="phone" type="tel" maxlength="80" value="<?=e($c['phone'])?>"></label><label>E-Mail<input name="email" type="email" maxlength="190" value="<?=e($c['email'])?>"></label><label>Notiz<input name="notes" maxlength="500" value="<?=e($c['notes'])?>"></label><button class="small primary">Speichern</button></form><form method="post" onsubmit="return confirm('Handwerker entfernen?')"><?=$f('contractor_delete',(int)$c['id'])?><button class="small danger">Entfernen</button></form></div></details></div><?php endforeach?></div>
<?php else:?><div class="empty"><strong>Noch keine Handwerker.</strong>Einmal anlegen, dann in allen Gewerken, Terminen und Kosten auswählbar.</div><?php endif?>
<details class="card" open><summary>Handwerker hinzufügen</summary><form method="post"><?=$f('contractor_add')?>
 <label>Name / Firma<input name="name" maxlength="190" required></label><label>Gewerk<input name="trade" maxlength="120" list="trade-list2" placeholder="z. B. Elektro"></label><datalist id="trade-list2"><?php foreach(GSZ_TRADE_TEMPLATE as $x):?><option value="<?=e($x)?>"><?php endforeach?></datalist>
 <div class="grid-3"><label>Telefon<input name="phone" type="tel" maxlength="80"></label><label>E-Mail<input name="email" type="email" maxlength="190"></label><label>Notiz<input name="notes" maxlength="500"></label></div><button class="primary">Speichern</button></form></details>
<?php endif;gszPageEnd();
function gszDayLabelShort(string $d):string {$x=new DateTimeImmutable($d);return ['','Mo','Di','Mi','Do','Fr','Sa','So'][(int)$x->format('N')].', '.$x->format('d.m.');}
