<?php
declare(strict_types=1);
// Übersicht aller Häuser: QR-Meldung für Mieter und Portal für die Verwaltung einrichten, erneuern, abschalten; alle Aushänge in einem PDF.
require __DIR__.'/bootstrap.php';
if(empty($_SESSION['admin_id']))redirect('index.php');
require_once dirname(__DIR__).'/private/reports.php';require_once dirname(__DIR__).'/private/leads.php';require_once dirname(__DIR__).'/private/ui.php';
$admin=(int)$_SESSION['admin_id'];$q=trim((string)($_GET['q']??''));
if(isset($_GET['pdf'])){require_once dirname(__DIR__).'/private/service_pdf.php';try{$pdf=gszAushangAllPdf($pdo,gszLinksOverview($pdo,$q));}catch(RuntimeException $e){$_SESSION['app_error']=$e->getMessage();redirect('haeuser.php');}catch(Throwable $e){error_log('Aushänge: '.$e->getMessage());http_response_code(500);exit('PDF konnte nicht erzeugt werden.');}
 header('Content-Type: application/pdf');header('Content-Disposition: inline; filename="aushaenge-schaden-melden.pdf"');header('Content-Length: '.strlen($pdf));echo $pdf;exit;}
if($_SERVER['REQUEST_METHOD']==='POST'){checkCsrf();$a=(string)($_POST['action']??'');$kind=(string)($_POST['kind']??'');$id=(int)($_POST['id']??0);$label=$kind==='portal'?'Portal':'QR-Meldung';
 try{switch($a){
  case 'renew':gszLinkRenew($pdo,$admin,$id,$kind);$_SESSION['app_notice']=$label.' eingerichtet.'.($kind==='report'?' Aushang jetzt drucken – ein älterer Aushang dieses Hauses gilt nicht mehr.':'');break;
  case 'off':gszLinkRenew($pdo,$admin,$id,$kind,true);$_SESSION['app_notice']=$label.' abgeschaltet.';break;
  case 'all':$n=gszLinksSetupAll($pdo,$admin,$kind);$_SESSION['app_notice']=$n?$label.' für '.$n.' Häuser eingerichtet.':'Alle Häuser haben bereits '.($kind==='portal'?'ein Portal':'eine QR-Meldung').'.';break;
  default:throw new RuntimeException('Aktion unbekannt.');}
 }catch(Throwable $e){gszUiError($e);}redirect('haeuser.php'.($q!==''?'?q='.rawurlencode($q):'').($id?'#haus-'.$id:''));}
$rows=gszServiceReady($pdo)?gszLinksOverview($pdo,$q):[];$csrf=csrf();
$cnt=['report'=>count(array_filter($rows,fn($r)=>$r['report_token'])),'portal'=>count(array_filter($rows,fn($r)=>$r['portal_token'])),'open'=>array_sum(array_column($rows,'open_reports'))];
$form=fn(string $a,string $kind,int $id,string $btn,string $cls='small')=>'<form method="post" action="haeuser.php'.($q!==''?'?q='.e(rawurlencode($q)):'').'"><input type="hidden" name="csrf" value="'.e($csrf).'"><input type="hidden" name="action" value="'.e($a).'"><input type="hidden" name="kind" value="'.e($kind).'"><input type="hidden" name="id" value="'.$id.'"><button class="'.e($cls).'">'.e($btn).'</button></form>';
gszPageStart('Häuser: QR & Portal','Alle Häuser auf einen Blick – Mieter-Meldung per QR-Code und Meldungs-Portal für die Hausverwaltung.');?>
<a class="back-link" href="objects.php">‹ Objekte</a>
<div class="kpis"><div class="kpi"><span class="k">Häuser</span><strong><?=count($rows)?></strong></div><div class="kpi"><span class="k">mit QR-Meldung</span><strong><?=$cnt['report']?></strong></div><div class="kpi"><span class="k">mit Portal</span><strong><?=$cnt['portal']?></strong></div><div class="kpi<?=$cnt['open']?' warn':''?>"><span class="k">offene Meldungen</span><strong><?=$cnt['open']?></strong><?php if($cnt['open']):?><small><a href="meldungen.php">ansehen</a></small><?php endif?></div></div>
<section class="card"><h2>Für alle Häuser</h2><div class="lead-actions"><?php if($cnt['report']<count($rows)):?><?=$form('all','report',0,'QR-Meldung für alle übrigen einrichten','small primary')?><?php endif?><?php if($cnt['report']):?><a class="btn small<?=$cnt['report']===count($rows)?' primary':''?>" href="haeuser.php?pdf=1<?=$q!==''?'&amp;q='.e(rawurlencode($q)):''?>" target="_blank" rel="noopener">Alle Aushänge drucken (PDF, je Haus eine Seite)</a><?php endif?><?php if($cnt['portal']<count($rows)):?><?=$form('all','portal',0,'Portal für alle übrigen einrichten')?><?php endif?></div>
<p class="hint">Jeder Link gilt nur für sein Haus und ist nicht zu erraten. „Erneuern“ macht den alten Link sofort ungültig – z. B. wenn eine Verwaltung wechselt oder ein Aushang verschwunden ist.</p></section>
<form class="search" method="get" role="search"><label class="sr-only" for="q">Haus suchen</label><input id="q" type="search" name="q" value="<?=e($q)?>" placeholder="Haus suchen"></form>
<?php if(!$rows):?><div class="empty"><strong><?=$q!==''?'Kein Haus gefunden.':'Noch keine Objekte.'?></strong><?=$q!==''?'Suchbegriff prüfen.':'Objekte legst du unter „Objekte“ an.'?></div><?php endif?>
<div class="lead-list"><?php foreach($rows as $r):$id=(int)$r['id'];?><article class="card house-row" id="haus-<?=$id?>">
 <div class="lead-head"><strong><a href="property.php?id=<?=$id?>#meldungen"><?=e($r['name'])?></a></strong><?php if((int)$r['open_reports']):?><a class="tag defect-neu" href="property.php?id=<?=$id?>#meldungen"><?=(int)$r['open_reports']?> offen</a><?php endif?></div>
 <?php if($r['address']!==''||$r['contact_name']!==''):?><p class="lead-meta"><?=e(trim($r['address'].($r['contact_name']!==''?' · Verwaltung: '.$r['contact_name']:''),' ·'))?></p><?php endif?>
 <div class="link-grid"><div class="link-box"><h3>QR-Meldung <?=$r['report_token']?'<span class="tag lead-auftrag">aktiv</span>':'<span class="tag">aus</span>'?></h3>
  <?php if($r['report_token']):?><div class="lead-actions"><a class="btn small primary" href="aushang.php?id=<?=$id?>" target="_blank" rel="noopener">Aushang (PDF)</a><a class="btn small" href="<?=e(gszReportUrl($pdo,$r['report_token']))?>" target="_blank" rel="noopener">Formular</a><?=$form('renew','report',$id,'Erneuern','small ghost')?><?=$form('off','report',$id,'Abschalten','small ghost')?></div>
  <?php else:?><?=$form('renew','report',$id,'Einrichten','small primary')?><?php endif?></div>
 <div class="link-box"><h3>Portal Verwaltung <?=$r['portal_token']?'<span class="tag lead-auftrag">aktiv</span>':'<span class="tag">aus</span>'?></h3>
  <?php if($r['portal_token']):$url=gszPortalUrl($pdo,$r['portal_token']);?><label class="sr-only" for="pl<?=$id?>">Portal-Link</label><input id="pl<?=$id?>" readonly value="<?=e($url)?>" class="copy-field" data-copy>
   <div class="lead-actions"><a class="btn small" href="<?=e($url)?>" target="_blank" rel="noopener">Ansehen</a><?php if(trim((string)$r['contact_email'])!==''):?><a class="btn small primary" href="compose.php?to=<?=e(rawurlencode((string)$r['contact_email']))?>&amp;subject=<?=e(rawurlencode('Mieter-Meldungen: '.$r['name']))?>">Per Mail</a><?php endif?><?=$form('renew','portal',$id,'Erneuern','small ghost')?><?=$form('off','portal',$id,'Abschalten','small ghost')?></div>
  <?php else:?><?=$form('renew','portal',$id,'Einrichten','small primary')?><?php endif?></div></div>
</article><?php endforeach?></div>
<?php gszPageEnd();
