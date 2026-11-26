<?php
declare(strict_types=1);
// Öffentlich (geheimer Link je Haus, nur für die Hausverwaltung): Meldungs-Portal – Mieter-Meldungen sehen und abhaken. Keine Anmeldung, keine Cookies.
require dirname(__DIR__).'/private/public_page.php';
$pdo=gszPublicPdo();if(!$pdo){http_response_code(503);exit('Gerade nicht erreichbar. Bitte später erneut versuchen.');}
$token=(string)($_GET['k']??'');$prop=gszLinkProperty($pdo,'portal',$token);if(!$prop)gszPublicGone();$k=pe($token);
if(($_SERVER['REQUEST_METHOD']??'')==='POST'){
 // Nur Formulare dieser Seite: fremde Herkunft wird abgewiesen (zusätzlich zum geheimen Link).
 $o=(string)($_SERVER['HTTP_ORIGIN']??'');$host=strtolower((string)($_SERVER['HTTP_HOST']??''));$oh=strtolower((string)parse_url($o,PHP_URL_HOST)).(parse_url($o,PHP_URL_PORT)?':'.parse_url($o,PHP_URL_PORT):'');if($o!==''&&$o!=='null'&&$oh!==$host&&$oh!==preg_replace('/:443$/','',$host)){http_response_code(403);exit;}
 $id=(int)($_POST['id']??0);$msg='';try{gszPortalDefectAction($pdo,$prop['id'],$id,(string)($_POST['action']??''));$msg=($_POST['action']??'')==='done'?'abgehakt':'geoeffnet';}catch(Throwable $e){$msg='fehler';}
 header('Location: portal.php?k='.rawurlencode($token).'&ok='.$msg.($msg!=='fehler'?'#m'.$id:''),true,303);exit;
}
$list=gszPortalDefects($pdo,$prop['id']);$company=gszSetting($pdo,'company');$phone=gszSetting($pdo,'company_phone');
$when=fn(string $ts)=>(new DateTimeImmutable($ts.' UTC'))->setTimezone(new DateTimeZone('Europe/Berlin'))->format('d.m.Y, H:i').' Uhr';
$btn=fn(int $id,string $a,string $label,string $cls='')=>'<form method="post" action="portal.php?k='.$k.'"><input type="hidden" name="id" value="'.$id.'"><input type="hidden" name="action" value="'.$a.'"><button class="'.$cls.'">'.$label.'</button></form>';
$card=function(array $d,bool $open)use($k,$when,$btn):string{$st=gszDefectState($d);$id=(int)$d['id'];
 $h='<article class="pub-card pub-defect'.($open?'':' is-done').'" id="m'.$id.'"><div class="pub-row"><strong>'.pe($d['location']!==''?$d['location']:'Meldung').'</strong><span class="pub-tag '.pe($st).'">'.pe(GSZ_DEFECT_STATUS[$st]).'</span></div>';
 $h.='<p class="pub-msg">'.nl2br(pe((string)$d['message'])).'</p><p class="pub-note">Nr. '.$id.' · gemeldet am '.pe($when((string)$d['created_at'])).'</p>';
 if($d['photo_name'])$h.='<a class="pub-defect-photo" href="portal_photo.php?k='.$k.'&amp;d='.$id.'"><img src="portal_photo.php?k='.$k.'&amp;d='.$id.'" alt="Foto zur Meldung" loading="lazy"></a>';
 $h.=$open?$btn($id,'done','✓ Erledigt – abhaken','pub-done-btn'):$btn($id,'reopen','Doch nicht erledigt – wieder öffnen','ghost');
 return $h.'</article>';};
gszPublicStart('Mieter-Meldungen',$company);$ok=(string)($_GET['ok']??'');?>
<section class="pub-intro"><h1>Mieter-Meldungen</h1><p class="pub-house"><span aria-hidden="true">🏢</span> <?=pe($prop['name'])?><?=$prop['address']!==''&&$prop['address']!==$prop['name']?'<small>'.pe($prop['address']).'</small>':''?></p></section>
<?php if($ok==='abgehakt'):?><p class="pub-card pub-ok" role="status">Erledigt – danke! <?=pe($company)?> sieht das sofort.</p><?php elseif($ok==='geoeffnet'):?><p class="pub-card pub-ok" role="status">Meldung ist wieder offen.</p><?php elseif($ok==='fehler'):?><p class="pub-card pub-err" role="alert">Das hat nicht geklappt. Bitte Seite neu laden.</p><?php endif?>
<div class="pub-kpis pub-kpis-2"><div><strong><?=count($list['open'])?></strong>offen</div><div><strong><?=count($list['done'])?></strong>erledigt (60 Tage)</div></div>
<h2>Offen</h2>
<?php if(!$list['open']):?><section class="pub-card"><p>Keine offenen Meldungen. 👍</p></section><?php endif?>
<?php foreach($list['open'] as $d)echo $card($d,true);?>
<?php if($list['done']):?><details class="pub-donelist"><summary>Erledigt (<?=count($list['done'])?>)</summary><?php foreach($list['done'] as $d)echo $card($d,false);?></details><?php endif?>
<p class="pub-note">Mieter melden über den QR-Code im Treppenhaus. Dieser Link gilt nur für dieses Haus – bitte nicht weitergeben. Bei Bedarf erzeugen wir einen neuen.</p>
<?php gszPublicEnd($phone);
