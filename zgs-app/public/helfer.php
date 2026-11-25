<?php
declare(strict_types=1);
// Helfer-App: eigener Zugang je Mitarbeiter. Nur eigene, zugeteilte Aufgaben; Start/Fertig und Vorher-/Nachher-Fotos. Kein GPS.
require __DIR__.'/bootstrap.php';
require_once dirname(__DIR__).'/private/worker_app.php';
if(!gszServiceReady($pdo)){http_response_code(503);exit('Die Helfer-App ist nach dem nächsten Update verfügbar.');}
$now=time();$wid=(int)($_SESSION['worker_id']??0);
if($wid&&(($now-(int)($_SESSION['worker_seen']??0))>12*3600||!gszWorkerActive($pdo,$wid))){unset($_SESSION['worker_id'],$_SESSION['worker_seen']);$wid=0;}
if($wid)$_SESSION['worker_seen']=$now;
$flash=fn(string $m,bool $err=false)=>$_SESSION[$err?'worker_error':'worker_notice']=$m;
if($_SERVER['REQUEST_METHOD']==='POST'){checkCsrf();$a=(string)($_POST['action']??'');
 try{
  if($a==='login'){$id=gszWorkerLogin($pdo,(string)($_POST['login']??''),(string)($_POST['password']??''));if(!$id){usleep(400000);throw new RuntimeException('Benutzername oder Passwort stimmt nicht.');}session_regenerate_id(true);$_SESSION['worker_id']=$id;$_SESSION['worker_seen']=$now;}
  elseif($a==='logout'){unset($_SESSION['worker_id'],$_SESSION['worker_seen']);session_regenerate_id(true);}
  elseif(!$wid)throw new RuntimeException('Bitte zuerst anmelden.');
  elseif($a==='start'){gszWorkerStart($pdo,$wid,(int)($_POST['task']??0));$flash('Gestartet. Denk an das Vorher-Foto.');}
  elseif($a==='photo'){if(!isset($_FILES['photo'])||($_FILES['photo']['error']??4)===UPLOAD_ERR_NO_FILE)throw new RuntimeException('Bitte ein Foto auswählen.');gszWorkerPhoto($pdo,$wid,(int)($_POST['log']??0),(string)($_POST['label']??''),$_FILES['photo']);$flash('Foto gespeichert.');}
  elseif($a==='finish'){gszWorkerFinish($pdo,$wid,(int)($_POST['log']??0),(string)($_POST['note']??''));$flash('Erledigt – danke!');}
  else throw new RuntimeException('Aktion unbekannt.');
 }catch(Throwable $e){$flash($e instanceof PDOException?'Speichern nicht möglich.':$e->getMessage(),true);}
 header('Location: helfer.php'.(isset($_POST['task'])?'#a'.(int)$_POST['task']:''),true,303);exit;}
$tz=new DateTimeZone('Europe/Berlin');$today=(new DateTimeImmutable('today',$tz))->format('Y-m-d');$csrf=csrf();
$hm=fn(?string $ts)=>$ts?(new DateTimeImmutable($ts.' UTC'))->setTimezone($tz)->format('H:i'):'';
$hidden=fn(string $a)=>'<input type="hidden" name="csrf" value="'.e($csrf).'"><input type="hidden" name="action" value="'.e($a).'">';
header("Content-Security-Policy: default-src 'none'; img-src 'self'; style-src 'self'; form-action 'self'; base-uri 'none'; frame-ancestors 'none'");
?><!doctype html><html lang="de"><head><meta charset="utf-8"><meta name="viewport" content="width=device-width,initial-scale=1"><meta name="robots" content="noindex,nofollow"><meta name="apple-mobile-web-app-capable" content="yes"><meta name="apple-mobile-web-app-title" content="GSZ Helfer"><title>Helfer · GSZ</title><link rel="stylesheet" href="aussen.css?v=1"><link rel="apple-touch-icon" href="assets/apple-touch-icon.png"></head><body>
<header class="pub-head"><span class="pub-logo">GSZ Helfer</span></header><main class="pub">
<?php foreach(['worker_error'=>'pub-err','worker_notice'=>'pub-ok'] as $k=>$cls)if(!empty($_SESSION[$k])){echo '<p class="pub-card '.$cls.'" role="'.($cls==='pub-err'?'alert':'status').'">'.e($_SESSION[$k]).'</p>';unset($_SESSION[$k]);}
if(!$wid):?>
<section class="pub-card"><h1>Anmelden</h1><p class="pub-sub">Deine Aufgaben für heute, Fotos und Zeiten.</p>
<form method="post"><?=$hidden('login')?><label>Benutzername<input name="login" autocomplete="username" autocapitalize="none" required></label><label>Passwort<input name="password" type="password" autocomplete="current-password" required></label><button>Anmelden</button></form>
<p class="pub-note">Zugang bekommst du vom Inhaber. Die App speichert nur, wann du Start und Fertig tippst – keinen Standort.</p></section>
<?php else:$acc=gszWorkerAccount($pdo,$wid);$tasks=gszWorkerTasks($pdo,$wid,$today);$open=count(array_filter($tasks,fn($t)=>!(int)$t['done']));?>
<section class="pub-card"><div class="pub-row"><h1>Hallo <?=e(explode(' ',(string)$acc['name'])[0])?></h1><form method="post"><?=$hidden('logout')?><button class="ghost">Abmelden</button></form></div>
<p class="pub-sub"><?=e(['','Montag','Dienstag','Mittwoch','Donnerstag','Freitag','Samstag','Sonntag'][(int)date('N')].', '.(new DateTimeImmutable($today))->format('d.m.Y'))?> · <?=$open===0?'alles erledigt':($open===1?'1 Aufgabe offen':$open.' Aufgaben offen')?></p></section>
<?php if(!$tasks):?><section class="pub-card"><p>Für heute ist dir nichts zugeteilt.</p></section><?php endif;
foreach($tasks as $t):$l=$t['log'];$running=$l&&!$l['ended_at'];$cnt=['before'=>0,'after'=>0];foreach($l['photos']??[] as $ph)$cnt[$ph['label']]=($cnt[$ph['label']]??0)+1;?>
<article class="pub-task<?=(int)$t['done']?' done':''?>" id="a<?=(int)$t['id']?>"><h2><?=e($t['title'])?></h2>
<p class="pub-note"><?=e($t['property_name'])?><?=$t['due_date']<$today?' · von '.e((new DateTimeImmutable($t['due_date']))->format('d.m.')):''?><?=$t['start_time']?' · '.e(substr((string)$t['start_time'],0,5)).' Uhr':''?></p>
<?php if($t['address']):?><p><a href="https://maps.apple.com/?q=<?=e(rawurlencode((string)$t['address']))?>" rel="noopener"><?=e($t['address'])?></a></p><?php endif?>
<?php if(trim((string)$t['notes'])!==''):?><p><?=nl2br(e($t['notes']))?></p><?php endif?>
<?php if(trim((string)$t['property_notes'])!==''):?><details><summary>Hinweise zum Objekt</summary><p><?=nl2br(e($t['property_notes']))?></p></details><?php endif?>
<?php if((int)$t['done']):?><p class="pub-note">✓ Erledigt<?=$l&&$l['ended_at']?' um '.e($hm($l['ended_at'])).($l['duration_minutes']?' · '.(int)$l['duration_minutes'].' Min.':'').' · '.count($l['photos']).' Foto(s)':''?></p>
<?php elseif(!$running):?><form method="post" class="pub-actions"><?=$hidden('start')?><input type="hidden" name="task" value="<?=(int)$t['id']?>"><button>Start</button></form>
<?php else:?><p class="pub-note">Läuft seit <?=e($hm($l['started_at']))?> Uhr · Fotos: <?=$cnt['before']?> vorher, <?=$cnt['after']?> nachher</p>
<div class="pub-steps"><?php foreach(['before'=>'Foto vorher','after'=>'Foto nachher'] as $lab=>$txt):?><form method="post" enctype="multipart/form-data"><?=$hidden('photo')?><input type="hidden" name="task" value="<?=(int)$t['id']?>"><input type="hidden" name="log" value="<?=(int)$l['id']?>"><input type="hidden" name="label" value="<?=$lab?>"><label><?=$txt?><input type="file" name="photo" accept="image/*" capture="environment" required></label><button class="ghost">Hochladen</button></form><?php endforeach?></div>
<form method="post" class="pub-actions"><?=$hidden('finish')?><input type="hidden" name="task" value="<?=(int)$t['id']?>"><input type="hidden" name="log" value="<?=(int)$l['id']?>"><label>Notiz <small>(optional, z. B. „Licht 2. OG defekt“)</small><textarea name="note" maxlength="2000" rows="2"></textarea></label><button>Fertig</button></form>
<?php endif?></article>
<?php endforeach;endif?>
</main></body></html>
