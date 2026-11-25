<?php
declare(strict_types=1);
// Öffentlich (geheimer Link je Haus, per QR-Code im Aushang): Mieter melden einen Schaden mit Foto. Keine Anmeldung, keine Cookies.
require dirname(__DIR__).'/private/public_page.php';
$pdo=gszPublicPdo();if(!$pdo){http_response_code(503);exit('Gerade nicht erreichbar. Bitte später erneut versuchen.');}
$token=(string)($_GET['k']??'');$prop=gszLinkProperty($pdo,'report',$token);if(!$prop)gszPublicGone();
$company=gszSetting($pdo,'company');$hv=gszLinks($pdo,$prop['id'])['portal_token']!==null;$phone=gszSetting($pdo,'company_phone');$done=false;$error='';$old=[];
if(($_SERVER['REQUEST_METHOD']??'')==='POST'){
 $old=$_POST;
 try{gszDefectSubmit($pdo,$prop,$_POST,$_FILES['photo']??null);$done=true;}
 catch(DomainException $e){$done=true;}
 catch(RuntimeException $e){$error=$e->getMessage();}
 catch(Throwable $e){error_log('melden: '.$e->getMessage());$error='Senden hat nicht geklappt. Bitte erneut versuchen oder anrufen.';}
}
gszPublicStart('Schaden melden',$company);
if($done):?>
<section class="pub-card pub-ok" role="status"><h1>Danke – Ihre Meldung ist angekommen.</h1><p>Wir kümmern uns darum.<?=$hv?' Die Hausverwaltung sieht den Stand ebenfalls.':''?></p><p><a href="melden.php?k=<?=pe($token)?>">Noch etwas melden</a></p></section>
<?php else:?>
<section class="pub-card"><h1>Schaden oder Mangel melden</h1><p class="pub-sub"><?=pe($prop['name'])?><?=$prop['address']!==''?' · '.pe($prop['address']):''?></p>
<?php if($error):?><p class="pub-card pub-err" role="alert"><?=pe($error)?></p><?php endif?>
<form method="post" enctype="multipart/form-data" action="melden.php?k=<?=pe($token)?>">
 <label>Wo ist es?<select name="location"><option value="">Bitte wählen</option><?php foreach(GSZ_DEFECT_PLACES as $pl):?><option<?=($old['location']??'')===$pl?' selected':''?>><?=pe($pl)?></option><?php endforeach?></select></label>
 <label>Was ist passiert?<textarea name="message" required maxlength="2000" placeholder="z. B. Licht im 2. OG geht nicht, Haustür schließt nicht richtig …"><?=pe($old['message']??'')?></textarea></label>
 <label>Foto <small>(optional – hilft uns sehr)</small><input type="file" name="photo" accept="image/*" capture="environment"></label>
 <label>Name oder Wohnung und Telefon <small>(optional, nur für Rückfragen)</small><input name="contact" maxlength="190" autocomplete="name" value="<?=pe($old['contact']??'')?>"></label>
 <div class="hp" aria-hidden="true"><label>Website<input name="website" tabindex="-1" autocomplete="off"></label></div>
 <button>Meldung senden</button>
 <p class="pub-note">Ihre Angaben gehen nur an <?=pe($company)?><?=$hv?' und die Hausverwaltung dieses Hauses':''?> – zur Erledigung der Meldung. Fotos speichern wir ohne Standortdaten. <a href="<?=pe(gszSetting($pdo,'website_origin'))?>/datenschutz/" rel="noopener">Datenschutz</a></p>
</form></section>
<section class="pub-card"><h2>Notfall?</h2><p>Wasserrohrbruch, Heizungsausfall im Winter, Sturmschaden: bitte sofort anrufen<?=$phone!==''?' – <a href="tel:'.pe(preg_replace('/[^0-9+]/','',$phone)).'">'.pe($phone).'</a>':''?>. Bei Gefahr für Leib und Leben: 112.</p></section>
<?php endif;gszPublicEnd($phone);
