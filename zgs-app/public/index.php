<?php
require __DIR__.'/bootstrap.php';
require_once dirname(__DIR__).'/private/platform.php';
require_once dirname(__DIR__).'/private/operations.php';
$operations=opsReady($pdo);$error=$_SESSION['operations_error']??'';unset($_SESSION['operations_error']);
if($_SERVER['REQUEST_METHOD']==='POST') {
 checkCsrf(); $action=(string)($_POST['action']??'');
 if($action==='login') {
  $stmt=$pdo->prepare('SELECT * FROM admins WHERE email=?');$stmt->execute([trim((string)($_POST['email']??''))]);$admin=$stmt->fetch();
  $lock=(int)($_SESSION['login_lock']??0);
  try{if(!gszLoginAttempt((string)($_POST['email']??''),(string)($_SERVER['REMOTE_ADDR']??'')))$lock=time()+900;}catch(Throwable $e){$lock=time()+900;}
  if(time()<$lock) $error='Bitte spaeter erneut versuchen.';
  elseif($admin && password_verify((string)($_POST['password']??''),$admin['password_hash'])) {session_regenerate_id(true);$_SESSION['admin_id']=(int)$admin['id'];$_SESSION['login_at']=time();$_SESSION['last_activity']=time();gszLoginAttempt((string)$admin['email'],(string)($_SERVER['REMOTE_ADDR']??''),true);$_SESSION['fails']=0;redirect();}
  else {$_SESSION['fails']=(int)($_SESSION['fails']??0)+1;if($_SESSION['fails']>=5){$_SESSION['login_lock']=time()+900;$_SESSION['fails']=0;}$error='Anmeldung fehlgeschlagen.';}
 } elseif($action==='logout') {$_SESSION=[];session_destroy();redirect();}
 elseif(!empty($_SESSION['admin_id'])) {
  if($operations){try{if($action==='customer')opsSaveCustomer($pdo,(int)$_SESSION['admin_id'],$_POST);elseif($action==='toggle'){$q=$pdo->prepare('SELECT done FROM tasks WHERE id=?');$q->execute([(int)($_POST['id']??0)]);$v=$q->fetchColumn();if($v===false)throw new RuntimeException('Aufgabe fehlt.');opsSetDone($pdo,(int)$_SESSION['admin_id'],(int)$_POST['id'],isset($_POST['done'])?$_POST['done']==='1':!(bool)$v,opsVersion($_POST));}elseif($action==='task'){$title=opsText($_POST,'title',190,true);$date=(string)($_POST['due_date']??'');if($date!==''&&!zgsValidDate($date))throw new RuntimeException('Datum ungültig.');opsSaveTask($pdo,(int)$_SESSION['admin_id'],[$title,$date?:null,'',null,null,'normal',0,null,null],(int)($_POST['property_id']??0),0,false,null);}elseif($action==='generate'){require_once dirname(__DIR__).'/private/scheduler.php';zgsGenerateDueTasks($pdo,date('Y-m-d'));}elseif($action==='property'){$name=opsText($_POST,'name',190,true);$address=opsText($_POST,'address',255);$cid=(int)($_POST['customer_id']??0);$newProperty=opsTransaction($pdo,function()use($pdo,$name,$address,$cid){if($cid){$c=opsLock($pdo,'customers',$cid);if($c['archived_at'])throw new RuntimeException('Kunde archiviert.');}$pdo->prepare('INSERT INTO properties(customer_id,name,address) VALUES(?,?,?)')->execute([$cid?:null,$name,$address]);$newId=(int)$pdo->lastInsertId();opsEvent($pdo,(int)$_SESSION['admin_id'],'property',$newId,'created',[],['name'=>$name,'address'=>$address,'customer_id'=>$cid?:null]);return $newId;});if(!empty($_POST['waste_service_enabled'])){require_once dirname(__DIR__).'/private/waste_connections.php';wasteConfigure($pdo,(int)$_SESSION['admin_id'],$newProperty,$_POST);wasteDiscover($pdo,(int)$_SESSION['admin_id'],$newProperty);redirect('waste_setup.php?id='.$newProperty);}}else throw new RuntimeException('Aktion unbekannt.');if(in_array($action,['toggle','task','generate','property'],true)){require_once dirname(__DIR__).'/private/planning_store.php';gszSuggestWeek($pdo,(int)$_SESSION['admin_id']);}}catch(Throwable $e){$_SESSION['operations_error']=$e instanceof PDOException?'Datenbank nicht bereit.':$e->getMessage();}redirect(in_array($action,['property','customer'],true)?'objects.php':'index.php');}
  if($action==='customer') { $name=trim((string)($_POST['name']??''));if($name!==''&&mb_strlen($name)<=190){$stmt=$pdo->prepare('INSERT INTO customers(name,email,phone,notes) VALUES(?,?,?,?)');$stmt->execute([$name,mb_substr(trim((string)($_POST['email']??'')),0,190),mb_substr(trim((string)($_POST['phone']??'')),0,80),mb_substr(trim((string)($_POST['notes']??'')),0,2000)]);} }
  if($action==='property') {$name=trim((string)($_POST['name']??''));$id=(int)($_POST['customer_id']??0);if($name!==''&&mb_strlen($name)<=190){$stmt=$pdo->prepare('INSERT INTO properties(customer_id,name,address) VALUES(?,?,?)');$stmt->execute([$id?:null,$name,mb_substr(trim((string)($_POST['address']??'')),0,255)]);} }
  if($action==='task') {$title=trim((string)($_POST['title']??''));$id=(int)($_POST['property_id']??0);$due=(string)($_POST['due_date']??'');if($title!==''&&mb_strlen($title)<=190){$stmt=$pdo->prepare('INSERT INTO tasks(property_id,title,due_date) VALUES(?,?,?)');$stmt->execute([$id?:null,$title,preg_match('/^\d{4}-\d{2}-\d{2}$/',$due)?$due:null]);} }
  if($action==='toggle') {$stmt=$pdo->prepare('UPDATE tasks SET done=1-done WHERE id=?');$stmt->execute([(int)($_POST['id']??0)]);}
  redirect();
 }
}
$logged=!empty($_SESSION['admin_id']);
if(!$logged):?><!doctype html><html lang="de"><head><meta charset="utf-8"><meta name="viewport" content="width=device-width,initial-scale=1,viewport-fit=cover"><meta name="robots" content="noindex,nofollow"><meta name="theme-color" content="#f3f3f1"><title>Anmelden · GSZ-Zentrale</title><link rel="icon" href="assets/favicon-32.png"><link rel="apple-touch-icon" href="assets/apple-touch-icon.png"><link rel="stylesheet" href="zentrale.css?v=380"></head><body>
<div class="login"><div class="login-card"><img src="assets/gsz-logo-horizontal.webp" alt="Gebäudeservice Zentrum" width="300" height="88"><h1>Willkommen zurück</h1><?php if($error):?><p class="error" role="alert"><?=e($error)?></p><?php endif?><form method="post"><input type="hidden" name="csrf" value="<?=e(csrf())?>"><input type="hidden" name="action" value="login"><label>E-Mail-Adresse<input type="email" name="email" autocomplete="username" required></label><label>Passwort<input type="password" name="password" autocomplete="current-password" required></label><button class="primary">Anmelden</button></form></div></div>
</body></html>
<?php exit;endif;
require_once dirname(__DIR__).'/private/day_view.php';require_once dirname(__DIR__).'/private/web_update.php';
if(!gszSchemaCurrent($pdo))redirect('update.php');
$today=date('Y-m-d');$attention=[];
try{require_once dirname(__DIR__).'/private/estate_rent_match.php';$estate=estateAttention($pdo);
 if($estate['unlinked_accounts'])$attention[]=['estate_bank.php#konten','Volksbank-Konten den Häusern zuordnen'];
 foreach($estate['missing_uploads'] as $u)$attention[]=['estate_bank.php?account='.$u['account_id'].'#hochladen','Kontoauszug für '.$u['label'].' hochladen'];
 if($estate['missing_rents'])$attention[]=['estate_bank.php',$estate['missing_rents']===1?'1 Miete fehlt oder ist unvollständig':$estate['missing_rents'].' Mieten fehlen oder sind unvollständig'];
 foreach($estate['charges_missing'] as $h)$attention[]=['estate_bank.php','Sollmieten für '.GSZ_MONTHS[(int)date('n')].' anlegen: '.$h];
}catch(Throwable $e){}
try{require_once dirname(__DIR__).'/private/mail_credentials.php';if(gszMailAuthBlockedNow())$attention[]=['connections.php','Mail-Passwort stimmt nicht mehr – neu eintragen'];
 $unread=(int)$pdo->query("SELECT COUNT(*) FROM mail_messages m JOIN mail_folders f ON f.id=m.folder_id WHERE m.active=1 AND m.seen=0 AND f.name='INBOX'")->fetchColumn();if($unread)$attention[]=['mail.php',$unread===1?'1 neue Mail':$unread.' neue Mails'];}catch(Throwable $e){}
try{$q=$pdo->prepare('SELECT COUNT(*) FROM tasks WHERE done=0 AND due_date<?');$q->execute([$today]);$late=(int)$q->fetchColumn();if($late)$attention[]=['planner.php?view=week',$late===1?'1 Aufgabe von einem Vortag ist noch offen':$late.' Aufgaben von Vortagen sind noch offen'];
 $q=$pdo->prepare('SELECT COUNT(*) FROM waste_imports WHERE admin_id=? AND applied_at IS NULL AND created_at>=UTC_TIMESTAMP()-INTERVAL 1 DAY');$q->execute([(int)$_SESSION['admin_id']]);if((int)$q->fetchColumn())$attention[]=['waste.php','Neue Abfuhrtermine bestätigen'];}catch(Throwable $e){}
?><!doctype html><html lang="de"><head><meta charset="utf-8"><title>Heute</title></head><body><main>
<?php $planError=$_SESSION['plan_error']??'';$planNotice=$_SESSION['plan_notice']??'';unset($_SESSION['plan_error'],$_SESSION['plan_notice']);if($error||$planError):?><p class="error flash" role="alert"><?=e($error?:$planError)?></p><?php endif?><?php if($planNotice):?><p class="notice flash" role="status"><?=e($planNotice)?></p><?php endif?>
<div class="page-head"><div><h1>Heute</h1><p class="sub"><?=e(gszDayLabel($today,true))?></p></div></div>
<?=gszRenderDay($pdo,$today,'index.php')?>
<?php if($attention):?><h2 class="section-title">Braucht Aufmerksamkeit</h2><div class="attention"><?php foreach($attention as [$href,$text]):?><a class="att" href="<?=e($href)?>"><span><?=e($text)?></span></a><?php endforeach?></div><?php endif?>
<p><a href="planner.php?date=<?=e(planDate($today,1))?>">Morgen ansehen</a></p>
</main></body></html>
