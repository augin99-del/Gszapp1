<?php
declare(strict_types=1);
// Öffentlich (geheimer Link je Haus, per QR-Code im Aushang): Mieter melden einen Schaden mit Foto. Keine Anmeldung, keine Cookies.
require dirname(__DIR__).'/private/public_page.php';
$pdo=gszPublicPdo();if(!$pdo){http_response_code(503);exit('Gerade nicht erreichbar. Bitte später erneut versuchen.');}
$token=(string)($_GET['k']??'');$prop=gszLinkProperty($pdo,'report',$token);if(!$prop)gszPublicGone();
$company=gszSetting($pdo,'company');$phone=gszSetting($pdo,'company_phone');$hv=gszLinks($pdo,$prop['id'])['portal_token']!==null;
$tel=preg_replace('/[^0-9+]/','',$phone);$done=null;$error='';$old=[];
if(($_SERVER['REQUEST_METHOD']??'')==='POST'){
 $old=$_POST;
 try{$done=gszDefectSubmit($pdo,$prop,$_POST,$_FILES['photo']??null);}
 catch(DomainException $e){$done=0;}
 catch(RuntimeException $e){$error=$e->getMessage();}
 catch(Throwable $e){error_log('melden: '.$e->getMessage());$error='Senden hat nicht geklappt. Bitte erneut versuchen oder anrufen.';}
}
$tile=function(string $name,array $items)use($old):string{$o='';$i=0;foreach(array_keys($items) as $label){$id=$name.'-'.(++$i);$o.='<label class="tile" for="'.$id.'"><input type="radio" id="'.$id.'" name="'.$name.'" value="'.pe($label).'"'.(($old[$name]??'')===$label?' checked':'').'><span><i>'.gszDefectIcon($label,$name==='kind').'</i>'.pe($label).'</span></label>';}return $o;};
gszPublicStart('Schaden melden',$company);
if($done!==null):?>
<section class="pub-card pub-done" role="status"><div class="pub-check" aria-hidden="true"><svg viewBox="0 0 24 24" width="40" height="40" fill="none" stroke="currentColor" stroke-width="2.6" stroke-linecap="round" stroke-linejoin="round"><path d="M5 12.5l4.5 4.5L19 7.5"/></svg></div><h1>Danke! Ihre Meldung ist angekommen.</h1>
<?php if($done>0):?><p class="pub-ref">Meldungsnummer <strong><?=(int)$done?></strong></p><?php endif?>
<p>Wir kümmern uns darum.<?=$hv?' Die Hausverwaltung sieht den Stand ebenfalls.':''?></p>
<a class="pub-btn ghost" href="melden.php?k=<?=pe($token)?>">Noch etwas melden</a></section>
<?php else:?>
<section class="pub-hero"><p class="pub-hero-kicker">Schadensmeldung</p><h1>Was ist im Haus kaputt?</h1><p class="pub-hero-house"><svg viewBox="0 0 24 24" width="20" height="20" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M3 21h18 M5 21V7l7-4v18 M12 9h7v12 M8 9h1 M8 13h1 M8 17h1 M15 13h1 M15 17h1"/></svg><span><?=pe($prop['name'])?><?=$prop['address']!==''&&$prop['address']!==$prop['name']?'<small>'.pe($prop['address']).'</small>':''?></span></p>
<ul class="pub-hero-facts"><li>Dauert 1 Minute</li><li>Ohne Anmeldung</li><li>Mit Foto</li></ul></section>
<?php if($tel!==''):?><a class="pub-sos" href="tel:<?=pe($tel)?>"><span><b>Notfall?</b> Wasserrohrbruch, Heizungsausfall, Sturm</span><strong>Jetzt anrufen</strong></a><?php endif?>
<?php if($error):?><p class="pub-card pub-err" role="alert"><?=pe($error)?></p><?php endif?>
<form method="post" enctype="multipart/form-data" action="melden.php?k=<?=pe($token)?>" data-report-form>
 <fieldset class="pub-step"><legend><span class="n">1</span> Wo ist es?</legend><div class="tiles"><?=$tile('location',GSZ_DEFECT_PLACES)?></div></fieldset>
 <fieldset class="pub-step"><legend><span class="n">2</span> Was ist los?</legend><div class="tiles"><?=$tile('kind',GSZ_DEFECT_KINDS)?></div>
  <label class="pub-label" for="msg">Kurz beschreiben <small>(optional – z. B. „2. OG, Licht flackert“)</small></label><textarea id="msg" name="message" maxlength="1900" rows="3" placeholder="Was genau? Wo genau?"><?=pe($old['message']??'')?></textarea></fieldset>
 <fieldset class="pub-step"><legend><span class="n">3</span> Foto <small>(hilft uns sehr)</small></legend>
  <label class="pub-photo" for="photo"><input type="file" id="photo" name="photo" accept="image/*" data-photo><span data-photo-label><svg viewBox="0 0 24 24" width="24" height="24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M4 8h3l2-3h6l2 3h3v11H4z M12 17a4 4 0 1 0 0-8 4 4 0 0 0 0 8z"/></svg> Foto aufnehmen oder auswählen</span><img alt="" hidden data-photo-preview></label></fieldset>
 <details class="pub-step pub-more"<?=($old['contact']??'')!==''?' open':''?>><summary><span class="n">4</span> Rückfragen erlaubt? <small>(optional)</small></summary>
  <label class="pub-label" for="contact">Name oder Wohnung und Telefon</label><input id="contact" name="contact" maxlength="190" autocomplete="name" placeholder="z. B. Müller, 2. OG links, 0176 …" value="<?=pe($old['contact']??'')?>"></details>
 <div class="hp" aria-hidden="true"><label>Website<input name="website" tabindex="-1" autocomplete="off"></label></div>
 <div class="pub-send-bar"><button class="pub-send" data-send>Meldung senden</button></div>
 <p class="pub-note">Ihre Angaben gehen nur an <?=pe($company)?><?=$hv?' und die Hausverwaltung dieses Hauses':''?> – zur Erledigung der Meldung. Fotos speichern wir ohne Standortdaten. Keine Cookies. <a href="<?=pe(gszSetting($pdo,'website_origin'))?>/datenschutz/" rel="noopener">Datenschutz</a></p>
</form>
<p class="pub-note pub-lang">EN: Tap where and what – add a photo – send. · TR: Yeri ve sorunu seçin, fotoğraf ekleyin, gönderin. · Gefahr für Leib und Leben: 112</p>
<?php endif;gszPublicEnd($phone);
