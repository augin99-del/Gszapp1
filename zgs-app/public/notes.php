<?php
declare(strict_types=1);
require __DIR__.'/bootstrap.php';
if(empty($_SESSION['admin_id']))redirect('index.php');
require_once dirname(__DIR__).'/private/notes_view.php';
$admin=(int)$_SESSION['admin_id'];
$json=($_SERVER['HTTP_X_GSZ_FETCH']??'')==='1';
$reply=static function(array $data):never{header('Content-Type: application/json; charset=utf-8');header('Cache-Control: no-store');echo json_encode($data,JSON_UNESCAPED_UNICODE);exit;};
if($_SERVER['REQUEST_METHOD']==='POST'){
 checkCsrf();$action=(string)($_POST['action']??'');$return=gszNoteReturn($_POST['return']??'notes.php');$id=(int)($_POST['id']??0);
 try{
  if($action==='add'){
   $r=gszNoteAdd($pdo,$admin,$_POST,$_FILES['photo']??null);$kind=(string)($_POST['kind']??'todo');
   $_SESSION['app_notice']=($kind==='invoice'?'Gemerkt: Rechnung offen':'Notiert').($r['property']?' · Objekt: '.$r['property']['name']:(($_POST['property_id']??'auto')==='auto'?' · kein Objekt erkannt (unter „Bearbeiten“ wählbar)':'')).'.';
  }elseif($action==='note_done'){
   $n=gszNoteSetDone($pdo,$admin,$id,($_POST['done']??'1')==='1',$_POST['row_version']??null);
   if($json)$reply(['ok'=>true,'done'=>(bool)$n['done_at'],'row_version'=>(int)$n['row_version'],'invoice'=>$n['kind']==='todo'&&$n['done_at']?['source'=>'note','id'=>(int)$n['id']]:null]);
  }elseif($action==='material_done'){
   $n=gszNoteMaterialDone($pdo,$id,($_POST['done']??'1')==='1',$_POST['row_version']??null);
   if($json)$reply(['ok'=>true,'done'=>(bool)$n['material_done'],'row_version'=>(int)$n['row_version']]);
  }elseif($action==='invoice_from'){
   $r=gszNoteInvoiceFrom($pdo,$admin,(string)($_POST['source']??''),$id);$msg=$r['existed']?'Steht schon auf „Rechnungen offen“.':'Auf „Rechnungen offen“ gemerkt.';
   if($json)$reply(['ok'=>true,'message'=>$msg]);$_SESSION['app_notice']=$msg;
  }elseif($action==='update'){gszNoteUpdate($pdo,$admin,$id,$_POST,$_FILES['photo']??null);$_SESSION['app_notice']='Gespeichert.';}
  elseif($action==='delete'){gszNoteDelete($pdo,$admin,$id);$_SESSION['app_notice']='Gelöscht.';}
  else throw new RuntimeException('Aktion unbekannt.');
 }catch(Throwable $e){$msg=$e instanceof PDOException?'Speichern nicht möglich (Datenbank).':$e->getMessage();if($json)$reply(['ok'=>false,'error'=>$msg]);$_SESSION['app_error']=$msg;}
 redirect($return);
}
$view=in_array($_GET['view']??'',['rechnungen','einkauf'],true)?(string)$_GET['view']:'todo';
$counts=gszNotesCounts($pdo);$csrf=csrf();$return='notes.php'.($view==='todo'?'':'?view='.$view);
require_once dirname(__DIR__).'/private/ui.php';
gszPageStart('Merkliste','Schnell notieren, später erledigen.');
$tabs=['todo'=>['To-dos',$counts['todo']],'rechnungen'=>['Rechnungen',$counts['invoice']],'einkauf'=>['Einkauf',$counts['shopping']]];?>
<nav class="seg-tabs" aria-label="Listen"><?php foreach($tabs as $k=>[$l,$c]):?><a href="notes.php<?=$k==='todo'?'':'?view='.$k?>"<?=$k===$view?' aria-current="page"':''?>><?=e($l)?><?=$c?' <span class="count">'.(int)$c.'</span>':''?></a><?php endforeach?></nav>
<section class="card" id="neu"><h2 class="sr-only">Neu notieren</h2><?=gszNoteQuickForm($pdo,$csrf,$return,$view==='rechnungen'?'invoice':'todo')?></section>
<?php if($view==='einkauf'):$rows=gszNotesShopping($pdo);?>
<?php if(!$rows):?><div class="empty"><strong>Nichts zu besorgen.</strong>Material, das du bei einem To-do einträgst, landet hier.</div>
<?php else:?><div class="blocks shopping"><?php foreach($rows as $n):$got=(bool)$n['material_done'];?><article class="block<?=$got?' is-done':''?>"><div class="what"><span class="obj"><?=e($n['material'])?></span><span class="act">für: <?=e($n['title'])?><?=$n['property_id']?' · '.e((string)$n['property_name']):''?></span></div><div class="acts"><form method="post" action="notes.php"><?=gszNoteHidden($csrf,'material_done',$return)?><input type="hidden" name="id" value="<?=(int)$n['id']?>"><input type="hidden" name="row_version" value="<?=(int)$n['row_version']?>"><input type="hidden" name="done" value="<?=$got?'0':'1'?>"><button class="check" aria-label="<?=e(($got?'Doch noch nicht besorgt: ':'Besorgt: ').$n['material'])?>" title="<?=$got?'Doch noch nicht besorgt':'Besorgt'?>"><?=gszNavIcon('check')?></button></form></div></article><?php endforeach?></div>
<p class="hint">Abhaken = besorgt. Das To-do selbst bleibt offen, bis die Arbeit erledigt ist.</p><?php endif?>
<?php else:$kind=$view==='rechnungen'?'invoice':'todo';$list=gszNotesList($pdo,$kind);?>
<?php if($kind==='invoice'):?><p class="hint">Hier steht nur, <strong>dass</strong> noch eine Rechnung fehlt. Geschrieben wird sie wie immer in Lexware – danach hier abhaken.</p><?php endif?>
<?php if($list['open']):?><?=gszRenderNoteList($pdo,$list['open'],$csrf,$return)?>
<?php else:?><div class="empty"><strong><?=$kind==='invoice'?'Keine Rechnung offen.':'Keine offenen To-dos.'?></strong><?=$kind==='invoice'?'Alles abgerechnet.':'Neues oben notieren oder unten rechts mit „+“.'?></div><?php endif?>
<?php if($list['done']):?><details class="card"><summary><?=$kind==='invoice'?'Geschrieben':'Erledigt'?> in den letzten <?=GSZ_NOTE_DONE_DAYS?> Tagen (<?=count($list['done'])?>)</summary><?=gszRenderNoteList($pdo,$list['done'],$csrf,$return)?></details><?php endif?>
<?php endif;gszPageEnd();
