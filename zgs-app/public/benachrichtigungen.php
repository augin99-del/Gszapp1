<?php
declare(strict_types=1);
// Push-Benachrichtigungen (Geräte, Arten), DWD-Warnungen für den Winterdienst und der „Wecker“ für Hintergrundaufgaben.
require __DIR__.'/bootstrap.php';
if(empty($_SESSION['admin_id']))redirect('index.php');
require_once dirname(__DIR__).'/private/weather.php';require_once dirname(__DIR__).'/private/ui.php';
$admin=(int)$_SESSION['admin_id'];
if(!gszPushReady($pdo)){gszPageStart('Benachrichtigungen');echo '<div class="empty"><strong>Update nötig.</strong>Benachrichtigungen gibt es ab Stand 440 – bitte unter Einstellungen → Sicherung und Update aktualisieren.</div>';gszPageEnd();exit;}
if($_SERVER['REQUEST_METHOD']==='POST'){checkCsrf();$a=(string)($_POST['action']??'');$ajax=($_SERVER['HTTP_X_REQUESTED_WITH']??'')==='fetch';
 try{switch($a){
  case 'subscribe':$sub=json_decode((string)($_POST['sub']??''),true);if(!is_array($sub))throw new RuntimeException('Gerät ungültig.');gszPushSubscribe($pdo,$admin,$sub,(string)($_POST['label']??''));if($ajax){header('Content-Type: application/json');echo '{"ok":true}';exit;}$_SESSION['app_notice']='Gerät angemeldet.';break;
  case 'remove':gszPushRemove($pdo,$admin,(int)($_POST['id']??0));$_SESSION['app_notice']='Gerät entfernt.';break;
  case 'test':$n=gszPushNotify($pdo,'test','test:'.bin2hex(random_bytes(6)),'✅ Test aus der GSZ-Zentrale','Benachrichtigungen funktionieren auf diesem Gerät.','benachrichtigungen.php');$_SESSION[$n?'app_notice':'app_error']=$n?'Test an '.$n.' Gerät'.($n>1?'e':'').' gesendet.':'Kein Gerät hat den Test angenommen – Gerät neu anmelden.';break;
  case 'kinds':foreach(array_keys(GSZ_PUSH_KINDS) as $k)if($k!=='test')gszSettingSet($pdo,'push_'.$k,!empty($_POST['push_'.$k])?'1':'0');foreach(['push_day_time','push_waste_time'] as $t){$v=(string)($_POST[$t]??'');if(preg_match('/^([01]\d|2[0-3]):[0-5]\d$/D',$v))gszSettingSet($pdo,$t,$v);}$_SESSION['app_notice']='Gespeichert.';break;
  case 'weather':$pts=trim((string)($_POST['weather_points']??''));gszSettingSet($pdo,'weather_points',$pts);if($pts!==''&&!gszWeatherPoints($pdo))throw new RuntimeException('Orte bitte wie „Gütersloh:51.9069,8.3786“ angeben.');
   $m=(string)($_POST['weather_tasks']??'ask');gszSettingSet($pdo,'weather_tasks',in_array($m,['ask','auto','off'],true)?$m:'ask');$t=(string)($_POST['weather_task_time']??'');gszSettingSet($pdo,'weather_task_time',preg_match('/^([01]\d|2[0-3]):[0-5]\d$/D',$t)?$t:'');$_SESSION['app_notice']='Gespeichert.';break;
  case 'winter':$n=gszWinterSave($pdo,$admin,(array)($_POST['winter']??[]));$_SESSION['app_notice']=$n?'Winterdienst bei '.$n.' Objekt'.($n>1?'en':'').' gespeichert.':'Keine Objekte mit Winterdienst ausgewählt.';break;
  case 'check':$r=gszWeatherTick($pdo,true);$_SESSION['app_notice']=$r['checked']?'DWD-Warnungen abgerufen'.($r['new']?': '.$r['new'].' neu.':' – nichts Neues.'):'Abruf nicht möglich (keine Orte?).';break;
  case 'tick':gszSettingSet($pdo,'tick_token',bin2hex(random_bytes(24)));gszAudit($pdo,$admin,'tick_token_renewed');$_SESSION['app_notice']='Neue Wecker-Adresse erzeugt. Bei cron-job.org die alte Adresse ersetzen.';break;
  default:throw new RuntimeException('Aktion unbekannt.');}
 }catch(Throwable $e){if($ajax){http_response_code(400);header('Content-Type: application/json');echo json_encode(['ok'=>false,'error'=>$e->getMessage()]);exit;}gszUiError($e);}
 redirect('benachrichtigungen.php'.(in_array($a,['weather','check'],true)?'#wetter':($a==='winter'?'#winterdienst':($a==='tick'?'#wecker':'#geraete'))));}
if(isset($_GET['gelesen'])){require_once dirname(__DIR__).'/private/shell.php';gszBellSeen($pdo);}
$devices=gszPushDevices($pdo);$csrf=csrf();$recent=gszPushRecent($pdo,20);$wx=gszWeatherActive($pdo);$wp=gszWinterProperties($pdo);
$tz=new DateTimeZone('Europe/Berlin');$fmt=fn(?string $t)=>$t?(new DateTimeImmutable($t.' UTC'))->setTimezone($tz)->format('d.m., H:i'):'–';
$tok=gszSetting($pdo,'tick_token');$tickUrl=$tok!==''?rtrim(gszSetting($pdo,'zentrale_url'),'/').'/tick.php?k='.$tok:'';$tickLast=gszSetting($pdo,'tick_last');
gszPageStart('Benachrichtigungen','Push aufs Handy bei Anfragen, Mieter-Meldungen und Glättewarnungen.');?>
<?php if($recent):?><section class="card" id="verlauf"><h2>Zuletzt benachrichtigt</h2><div class="list feed"><?php foreach(array_slice($recent,0,12) as $r):?><a href="<?=e($r['url'])?>"><span class="feed-ico" aria-hidden="true"><?=e(GSZ_PUSH_KINDS[$r['kind']][2]??'•')?></span><span class="name"><?=e($r['title'])?><span class="sub"><?=e($r['body'])?> · <?=e($fmt($r['created_at']))?></span></span></a><?php endforeach?></div></section><?php endif?>
<section class="card" id="geraete" data-push data-key="<?=e(gszPushPublicKey())?>" data-csrf="<?=e($csrf)?>"><h2>Dieses Gerät</h2>
 <p class="hint">Am iPhone funktioniert das nur, wenn die Zentrale als App auf dem Home-Bildschirm liegt (Safari → Teilen → „Zum Home-Bildschirm“) und von dort geöffnet wird.</p>
 <button type="button" class="primary" data-push-on>Benachrichtigungen auf diesem Gerät aktivieren</button><p data-push-msg hidden></p>
 <?php if($devices):?><h3>Angemeldete Geräte</h3><div class="list"><?php foreach($devices as $d):?><div class="li"><span class="name"><?=e($d['label']?:'Gerät')?><span class="sub">seit <?=e($fmt($d['created_at']))?><?=$d['last_ok_at']?' · zuletzt zugestellt '.e($fmt($d['last_ok_at'])):''?><?=$d['last_error']!==''?' · Problem: '.e($d['last_error']):''?></span></span><form method="post"><?php gszFields('remove');?><input type="hidden" name="id" value="<?=(int)$d['id']?>"><button class="small ghost">Entfernen</button></form></div><?php endforeach?></div>
 <form method="post"><?php gszFields('test');?><button class="small">Test-Benachrichtigung senden</button></form><?php endif?></section>
<section class="card" id="arten"><h2>Wobei benachrichtigen?</h2><form method="post" class="push-kinds"><?php gszFields('kinds');?><?php foreach(GSZ_PUSH_KINDS as $k=>[$t,$d,$i]):if($k==='test')continue;?><label class="push-kind"><span class="pk-ico" aria-hidden="true"><?=$i?></span><span class="pk-txt"><strong><?=e($t)?></strong><small><?=e($d)?></small></span><input type="checkbox" role="switch" class="switch" name="push_<?=$k?>" value="1"<?=gszSetting($pdo,'push_'.$k)==='1'?' checked':''?>></label><?php endforeach?>
 <div class="grid-3"><label>„Mein Tag“ um<input type="time" name="push_day_time" value="<?=e(gszSetting($pdo,'push_day_time'))?>"></label><label>„Tonnen“ um<input type="time" name="push_waste_time" value="<?=e(gszSetting($pdo,'push_waste_time'))?>"></label></div><button class="small primary">Speichern</button></form>
 <?php if($recent):?><details><summary>Zuletzt gesendet</summary><ul class="history"><?php foreach($recent as $r):?><li><?=e($fmt($r['created_at']))?> · <?=e($r['title'])?> · an <?=(int)$r['delivered']?> Gerät(e)</li><?php endforeach?></ul></details><?php endif?></section>
<section class="card" id="wetter"><h2>Glättewarnung (DWD) für den Winterdienst</h2>
 <?php if($wx):foreach($wx as $a):?><p><strong><?=(int)$a['winter']===2?'❄️':'🌡️'?> <?=e(mb_convert_case(mb_strtolower((string)$a['event']),MB_CASE_TITLE))?></strong> · <?=e($a['area'])?> · <?=e(gszWeatherWhen($a))?></p><?php endforeach;else:?><p class="muted">Zurzeit keine Glätte-, Schnee- oder Frostwarnung für deine Orte.</p><?php endif?>
 <p class="hint">Zuletzt geprüft: <?=e($fmt(gszSetting($pdo,'weather_checked')?:null))?>. Quelle: amtliche Warnungen des Deutschen Wetterdienstes (über Bright Sky). Glätte, Glatteis und Schnee lösen Push und Winterdienst aus, Frost wird nur angezeigt.</p>
 <form method="post"><?php gszFields('check');?><button class="small">Jetzt prüfen</button></form>
 <form method="post"><?php gszFields('weather');?>
  <label>Orte (Name:Breite,Länge – je Zeile)<textarea name="weather_points" rows="2" maxlength="600"><?=e(str_replace(';',"\n",gszSetting($pdo,'weather_points')))?></textarea></label>
  <div class="grid-3"><label>Bei Glättewarnung<select name="weather_tasks"><?php foreach(['ask'=>'Nachfragen','auto'=>'Automatisch einplanen','off'=>'Nur informieren'] as $k=>$l):?><option value="<?=$k?>"<?=gszSetting($pdo,'weather_tasks')===$k?' selected':''?>><?=e($l)?></option><?php endforeach?></select></label>
  <label>Uhrzeit der Einsätze<input type="time" name="weather_task_time" value="<?=e(gszSetting($pdo,'weather_task_time'))?>"></label></div><button class="small primary">Speichern</button></form>
 </section>
<section class="card" id="winterdienst"><h2>Winterdienst bei diesen Objekten</h2><p class="hint">Haken setzen, wo du bei Glätte und Schnee raus musst. Bei einer Warnung plant die Zentrale genau diese Objekte ein – sonst entstehen keine Aufgaben.</p>
 <?php $all=$pdo->query('SELECT id,name,address FROM properties ORDER BY name LIMIT 1000')->fetchAll();$sel=array_flip(array_column($wp,'id'));$byRule=array_diff(array_column($wp,'id'),gszWinterIds($pdo));?>
 <?php if(!$all):?><p class="muted">Noch keine Objekte angelegt.</p><?php else:?><form method="post" class="winter-list"><?php gszFields('winter');?>
 <p class="winter-tools"><span><?=count($wp)?> von <?=count($all)?> ausgewählt</span></p>
 <?php foreach($all as $o):$rule=in_array((int)$o['id'],$byRule,true);?><label class="push-kind"><span class="pk-ico" aria-hidden="true">🏢</span><span class="pk-txt"><strong><?=e($o['name'])?></strong><small><?=e($o['address'])?><?=$rule?' · über Regelaufgabe „Winterdienst“':''?></small></span><input type="checkbox" role="switch" class="switch" name="winter[]" value="<?=(int)$o['id']?>"<?=isset($sel[(int)$o['id']])?' checked':''?><?=$rule?' disabled':''?>></label><?php endforeach?>
 <button class="primary">Speichern</button></form><?php endif?></section>
<section class="card" id="wecker"><h2>Wecker für nachts und unterwegs</h2>
 <p>Dein STRATO-Tarif hat keine Cronjobs. Damit Glättewarnungen und Anfragen auch dann ankommen, wenn die Zentrale nicht geöffnet ist, ruft ein kostenloser Dienst (z. B. <strong>cron-job.org</strong>) alle 15 Minuten diese geheime Adresse auf:</p>
 <?php if($tickUrl):?><label class="sr-only" for="tick">Wecker-Adresse</label><input id="tick" readonly class="copy-field" data-copy value="<?=e($tickUrl)?>"><p class="hint">Zuletzt geweckt: <?=e($tickLast!==''?$fmt($tickLast):'noch nie')?>.</p><?php endif?>
 <form method="post"><?php gszFields('tick');?><button class="small<?=$tickUrl?'':' primary'?>"><?=$tickUrl?'Neue Adresse erzeugen (alte wird ungültig)':'Wecker-Adresse erzeugen'?></button></form>
 <details><summary>So richtest du cron-job.org ein</summary><ol><li>Auf cron-job.org kostenlos registrieren.</li><li>„Cronjob erstellen“: Adresse oben einfügen, Ausführung „alle 15 Minuten“.</li><li>Speichern. Nach 15 Minuten steht hier „Zuletzt geweckt“ mit Uhrzeit.</li></ol><p class="hint">Die Adresse ist wie ein Schlüssel: nicht weitergeben. Sie kann nur die Hintergrundaufgaben anstoßen, keine Daten abrufen.</p></details></section>
<script src="push-client.js?v=1" defer></script>
<?php gszPageEnd();
