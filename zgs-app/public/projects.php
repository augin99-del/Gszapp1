<?php
declare(strict_types=1);
require __DIR__.'/bootstrap.php';
if(empty($_SESSION['admin_id']))redirect('index.php');
require_once dirname(__DIR__).'/private/projects.php';require_once dirname(__DIR__).'/private/ui.php';
if($_SERVER['REQUEST_METHOD']==='POST'){checkCsrf();
 try{if(($_POST['action']??'')!=='create')throw new RuntimeException('Aktion unbekannt.');$id=gszProjectSave($pdo,(int)$_SESSION['admin_id'],$_POST);
  if(!empty($_POST['template']))gszTradeTemplate($pdo,$id);$_SESSION['app_notice']='Projekt angelegt.'.(!empty($_POST['template'])?' Typische Gewerke sind schon drin – einfach anpassen.':'');redirect('project.php?id='.$id);}
 catch(Throwable $e){gszUiError($e);redirect('projects.php#neu');}
}
$projects=gszProjectsList($pdo);$buildings=$pdo->query('SELECT id,name FROM estate_buildings ORDER BY name')->fetchAll(PDO::FETCH_ASSOC);
gszPageStart('Bauprojekte','Sanierungen planen und im Blick behalten.');?>
<?php if(!$projects):?><div class="empty"><strong>Noch kein Projekt.</strong>Lege unten dein erstes Bauprojekt an, z. B. „Sanierung 4-Familienhaus“.</div><?php endif?>
<div class="project-cards"><?php foreach($projects as $p):$s=$p['summary'];?>
 <a class="project-card" href="project.php?id=<?=(int)$p['id']?>">
  <span class="pc-head"><strong><?=e($p['title'])?></strong><span class="tag status-<?=e($p['status'])?>"><?=e(GSZ_PROJECT_STATUS[$p['status']])?></span></span>
  <?php if($p['building_name']):?><span class="muted"><?=e($p['building_name'])?></span><?php endif?>
  <span class="progress" aria-label="<?=$s['progress']?> % der Gewerke fertig"><span style="width:<?=$s['progress']?>%"></span></span>
  <span class="pc-facts"><span><b><?=$s['progress']?> %</b> fertig (<?=$s['trades']['fertig']?>/<?=$s['trades_total']?> Gewerke)</span><span><b><?=e(gszMoney($s['invoiced']))?></b><?=$s['budget']!==null?' von '.e(gszMoney($s['budget'])):''?></span><span><b><?=$s['items_open']?></b> offen<?=$s['defects_open']?' · '.$s['defects_open'].' Mängel':''?></span></span>
  <?php if($s['next_events']):$ev=$s['next_events'][0];?><span class="pc-next">Nächster Termin: <?=e((new DateTimeImmutable($ev['day']))->format('d.m.').', '.substr($ev['start_time'],0,5).' · '.$ev['title'])?></span><?php endif?>
 </a><?php endforeach?></div>
<details class="card" id="neu"<?=$projects?'':' open'?>><summary>Neues Projekt</summary>
<form method="post"><?php gszFields('create');?>
 <label>Name<input name="title" maxlength="190" required placeholder="z. B. Sanierung 4-Familienhaus"></label>
 <label>Haus<select name="building_id"><option value="">– ohne Zuordnung –</option><?php foreach($buildings as $b):?><option value="<?=(int)$b['id']?>"><?=e($b['name'])?></option><?php endforeach?></select></label>
 <?php if(!$buildings):?><p class="hint">Noch kein Haus unter Immobilien angelegt – kannst du später zuordnen.</p><?php endif?>
 <div class="grid-3"><label>Start<input type="date" name="start_date"></label><label>Fertig bis<input type="date" name="target_date"></label><label>Budget (€)<input name="budget" inputmode="decimal" placeholder="z. B. 180.000"></label></div>
 <label class="check-label"><input type="checkbox" name="template" value="1" checked> Typische Gewerke einer Sanierung gleich anlegen (Dach, Fenster, Elektro, Sanitär, Heizung, Fliesen, Maler …)</label>
 <button class="primary">Projekt anlegen</button>
</form></details>
<?php gszPageEnd();
