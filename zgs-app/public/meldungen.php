<?php
declare(strict_types=1);
require __DIR__.'/bootstrap.php';
if(empty($_SESSION['admin_id']))redirect('index.php');
require_once dirname(__DIR__).'/private/reports_view.php';require_once dirname(__DIR__).'/private/ui.php';
$admin=(int)$_SESSION['admin_id'];
if($_SERVER['REQUEST_METHOD']==='POST'){checkCsrf();$id=(int)($_POST['id']??0);$r=(string)($_POST['return']??'');$back=preg_match('/^(meldungen\.php(\?filter=[a-z]+)?|property\.php\?id=\d+)(#[a-z0-9-]+)?$/D',$r)?$r:'meldungen.php';
 try{switch((string)($_POST['action']??'')){
  case 'note':gszDefectToNote($pdo,$admin,$id);$_SESSION['app_notice']='Als To-do in die Merkliste übernommen.';break;
  case 'status':gszDefectSetStatus($pdo,$admin,$id,(string)($_POST['status']??''));$_SESSION['app_notice']='Status: '.(GSZ_DEFECT_STATUS[$_POST['status']]??'').'.';break;
  default:throw new RuntimeException('Aktion unbekannt.');}
 }catch(Throwable $e){gszUiError($e);}redirect($back);}
$filter=in_array($_GET['filter']??'',['erledigt','alle'],true)?(string)$_GET['filter']:'offen';$list=gszDefects($pdo,$filter==='alle'?null:$filter);$csrf=csrf();
$c=['offen'=>count(gszDefects($pdo,'offen')),'erledigt'=>count(gszDefects($pdo,'erledigt',null,90))];
gszPageStart('Mieter-Meldungen','Schäden, die Mieter per QR-Code im Haus gemeldet haben.');?>
<nav class="seg-tabs" aria-label="Filter"><a href="meldungen.php"<?=$filter==='offen'?' aria-current="page"':''?>>Offen <span class="count"><?=$c['offen']?></span></a><a href="meldungen.php?filter=erledigt"<?=$filter==='erledigt'?' aria-current="page"':''?>>Erledigt</a><a href="meldungen.php?filter=alle"<?=$filter==='alle'?' aria-current="page"':''?>>Alle</a></nav>
<?php if(!$list):?><div class="empty"><strong><?=$filter==='offen'?'Keine offenen Meldungen.':'Keine Einträge.'?></strong>Den QR-Aushang richtest du auf der Seite des Objekts ein (Abschnitt „Mieter-Meldungen &amp; Hausverwaltung“).</div><?php endif?>
<div class="lead-list"><?php foreach($list as $d)echo gszRenderDefect($d,$csrf,'meldungen.php'.($filter!=='offen'?'?filter='.$filter:''));?></div>
<?php gszPageEnd();
