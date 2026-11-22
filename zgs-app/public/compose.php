<?php
declare(strict_types=1);
require __DIR__.'/bootstrap.php';if(empty($_SESSION['admin_id']))redirect('index.php');require_once dirname(__DIR__).'/private/mail_service.php';require_once dirname(__DIR__).'/private/mail_engine.php';require_once dirname(__DIR__).'/private/ui.php';require_once dirname(__DIR__).'/private/mail_ui.php';
$id=(int)($_GET['draft']??0);$form=['to_address'=>'','cc_address'=>'','bcc_address'=>'','subject'=>'','body'=>(string)$config['mail_signature'],'reply_message_id'=>null];$drafts=[];$sent=[];$attachments=[];$mode='';
$sendAllowed=filter_var($config['allow_mail_send']??false,FILTER_VALIDATE_BOOLEAN);
/** Anhänge aus dem Formular (mehrere möglich) an den Entwurf hängen. */
$attach=static function(PDO $pdo,int $id):void {
 $files=[];foreach(['attachments','attachment'] as $key){if(!isset($_FILES[$key]))continue;$f=$_FILES[$key];if(is_array($f['name'])){foreach(array_keys($f['name']) as $i)$files[]=['name'=>$f['name'][$i],'tmp_name'=>$f['tmp_name'][$i],'error'=>$f['error'][$i],'size'=>$f['size'][$i]];}else $files[]=$f;}
 foreach($files as $f){if($f['error']===UPLOAD_ERR_NO_FILE)continue;if($f['error']!==UPLOAD_ERR_OK||!is_uploaded_file($f['tmp_name'])||$f['size']>10*1024*1024)throw new RuntimeException('Anhang „'.$f['name'].'“: höchstens 10 MB.');gszMailDraftAttachment($pdo,$id,(string)$f['name'],(string)file_get_contents($f['tmp_name']));}
};
if($_SERVER['REQUEST_METHOD']==='POST'){checkCsrf();$target=null;try{
 $action=(string)($_POST['action']??'');$admin=(int)$_SESSION['admin_id'];
 if($action==='save'||$action==='send'){
  if($action==='send'&&empty($_POST['confirm_send']))throw new RuntimeException('Bitte das Senden bestätigen.');
  // Bearbeitbarer Entwurf: erst speichern (inkl. Anhänge), dann – bei „Senden“ – genau einmal senden.
  $editable=!$id||in_array((string)$pdo->query('SELECT status FROM mail_drafts WHERE id='.(int)$id)->fetchColumn(),['draft','rejected'],true);
  if($editable&&isset($_POST['to_address'])){$id=gszMailSaveDraft($pdo,$_POST,$id?:null);$attach($pdo,$id);gszAudit($pdo,$admin,'mail_draft_saved',['draft_id'=>$id]);}
  if($action==='save'){$_SESSION['app_notice']='Entwurf gespeichert.';}
  else{
   if(!$id)throw new RuntimeException('Bitte zuerst Empfänger und Text eingeben.');
   if($sendAllowed&&empty($config['mail_sent_folder']))throw new RuntimeException('Erst unter Einstellungen → E-Mail einrichten „Verbindung testen“, damit der Ordner „Gesendet“ erkannt wird.');
   $remote=$sendAllowed&&filter_var($config['mail_enabled']??false,FILTER_VALIDATE_BOOLEAN)?gszMailRemote($config):null;
   try{gszMailSendDraft($pdo,new GszSmtpSender($config),$remote,$admin,$id,(string)$config['mail_from'],(string)$config['mail_sent_folder']);}
   catch(Throwable $e){$st=(string)$pdo->query('SELECT status FROM mail_drafts WHERE id='.(int)$id)->fetchColumn();throw new RuntimeException($st==='rejected'?'Nicht gesendet: Der Mailserver hat die Mail abgelehnt. Empfänger prüfen und erneut senden.':($st==='uncertain'||$st==='sending'?'Unklar, ob die Mail rausging. Bitte nicht erneut senden – erst unten den Stand prüfen.':$e->getMessage()));}
   $d=$pdo->query('SELECT to_address,sent_copy FROM mail_drafts WHERE id='.(int)$id)->fetch();
   if($d['sent_copy']){$_SESSION['app_notice']='Gesendet an '.mb_strimwidth((string)$d['to_address'],0,80,'…').'.';$target='mail.php';}
   else $_SESSION['app_notice']='Gesendet. Die Kopie im Ordner „Gesendet“ fehlt noch – unten prüfen.';
  }
 }
 elseif($action==='resolve'){if(empty($_POST['confirm_review']))throw new RuntimeException('Postfachprüfung ausdrücklich bestätigen.');gszMailResolveDraft($pdo,$admin,$id,(string)($_POST['decision']??''),(string)($_POST['reason']??''));$_SESSION['app_notice']='Prüfentscheidung gespeichert. Keine E-Mail automatisch versendet.';}
 elseif($action==='copy_preview'){unset($_SESSION['mail_copy_preview']);$_SESSION['mail_copy_preview']=gszMailSentCopyPreview($pdo,gszMailRemote($config),$id);$_SESSION['app_notice']='Ordner „Gesendet“ durchsucht. Ergebnis prüfen und bestätigen.';}
 elseif($action==='copy_confirm'){$preview=$_SESSION['mail_copy_preview']??[];unset($_SESSION['mail_copy_preview']);if(empty($_POST['confirm_copy']))throw new RuntimeException('Bitte die Kopie ausdrücklich bestätigen.');$result=gszMailRecoverSentCopy($pdo,gszMailRemote($config),$admin,$id,$preview,!empty($_POST['acknowledge_uncertain']));$_SESSION['app_notice']=$result==='found'?'Vorhandene Gesendet-Kopie bestätigt.':'Gesendet-Kopie anhand der unveränderten Versandbytes bestätigt.';}
 else throw new RuntimeException('Aktion unbekannt.');
 }catch(Throwable $e){gszUiError($e);}redirect($target??'compose.php'.($id?'?draft='.$id:''));}
try{if($id){$q=$pdo->prepare('SELECT * FROM mail_drafts WHERE id=?');$q->execute([$id]);$form=$q->fetch()?:throw new RuntimeException('Entwurf nicht gefunden.');$q=$pdo->prepare('SELECT * FROM mail_draft_attachments WHERE draft_id=?');$q->execute([$id]);$attachments=$q->fetchAll();}
 elseif(isset($_GET['message'])){$m=gszMailMessage($pdo,(int)$_GET['message']);$mode=(string)($_GET['mode']??'reply');if(!in_array($mode,['reply','reply_all','forward'],true))$mode='reply';$reply=gszReplyRecipients($m,$mode,(string)$config['mail_from']);$form=['to_address'=>$reply['to'],'cc_address'=>'','bcc_address'=>'','subject'=>$reply['subject'],'body'=>$config['mail_signature'].$reply['body'],'reply_message_id'=>$mode==='forward'?null:$m['id']];}
 if(!$id&&!$mode){$drafts=$pdo->query("SELECT id,to_address,subject,status,sent_copy,updated_at FROM mail_drafts WHERE status IN ('draft','rejected') ORDER BY updated_at DESC,id DESC LIMIT 30")->fetchAll();$sent=$pdo->query("SELECT id,to_address,subject,status,sent_copy,last_error,updated_at FROM mail_drafts WHERE status NOT IN ('draft','rejected') ORDER BY id DESC LIMIT 30")->fetchAll();}
}catch(Throwable $e){$_SESSION['app_error']=$e instanceof PDOException?'Mail-Migration erforderlich.':$e->getMessage();}
$status=(string)($form['status']??'');$editable=$status===''||in_array($status,['draft','rejected'],true);
$title=$editable?($mode==='forward'?'Weiterleiten':($mode!==''||!empty($form['reply_message_id'])?'Antworten':($id?'Entwurf':'Neue Mail'))):'Gesendete Mail';
gszPageStart($title);?>
<a class="back-link" href="mail.php">‹ Mail</a>
<?php if($editable):$cc=($form['cc_address']??'')!==''||($form['bcc_address']??'')!=='';?>
<form class="card compose" method="post" enctype="multipart/form-data" action="compose.php<?=$id?'?draft='.$id:''?>"><input type="hidden" name="csrf" value="<?=e(csrf())?>"><input type="hidden" name="reply_message_id" value="<?=e((string)($form['reply_message_id']??''))?>"><input type="hidden" name="confirm_send" value="1">
 <?php if($status==='rejected'):?><p class="error">Nicht gesendet: Der Mailserver hat diese Mail abgelehnt. Empfänger prüfen und erneut senden.</p><?php endif?>
 <label>An<input name="to_address" value="<?=e($form['to_address'])?>" maxlength="3000" inputmode="email" autocomplete="email" placeholder="name@beispiel.de" autocapitalize="off" spellcheck="false"></label>
 <details class="cc"<?=$cc?' open':''?>><summary>Cc / Bcc</summary><label>Cc<input name="cc_address" value="<?=e($form['cc_address'])?>" maxlength="3000" inputmode="email" autocapitalize="off" spellcheck="false"></label><label>Bcc<input name="bcc_address" value="<?=e($form['bcc_address']??'')?>" maxlength="3000" inputmode="email" autocapitalize="off" spellcheck="false"></label><p class="hint">Mehrere Adressen mit Komma trennen, höchstens 10.</p></details>
 <label>Betreff<input name="subject" value="<?=e($form['subject'])?>" maxlength="190"></label>
 <label>Text<textarea name="body" rows="14" maxlength="150000"><?=e($form['body'])?></textarea></label>
 <?php if($attachments):?><div class="mail-files"><?php foreach($attachments as $a):?><span class="file"><span><?=e($a['original_name'])?></span><span class="muted"><?=max(1,(int)round($a['size_bytes']/1024))?> KB</span></span><?php endforeach?></div><?php endif?>
 <label>Anhang hinzufügen<input type="file" name="attachments[]" multiple></label>
 <div class="compose-actions"><button class="primary" name="action" value="send" onclick="return confirm('Mail jetzt senden an '+(this.form.to_address.value||'?')+'?')">Senden</button><button name="action" value="save">Als Entwurf sichern</button></div>
 <?php if(!$sendAllowed):?><p class="hint">Senden ist auf dem Server noch nicht freigeschaltet (Einstellungen → E-Mail einrichten).</p><?php endif?>
</form>
<?php else:?>
<section class="card"><h2><?=e($form['subject']!==''?$form['subject']:'(ohne Betreff)')?></h2><p class="mail-state"><strong><?=e(gszMailDraftState($form))?></strong><?=$status==='accepted'&&$form['sent_copy']?' · Kopie liegt im Ordner „Gesendet“':''?></p><p class="muted">An: <?=e($form['to_address'])?><?php if($form['cc_address']):?><br>Cc: <?=e($form['cc_address'])?><?php endif?></p><div class="mail-body"><?=e($form['body'])?></div><?php if($attachments):?><div class="mail-files"><?php foreach($attachments as $a):?><span class="file"><span><?=e($a['original_name'])?></span><span class="muted"><?=max(1,(int)round($a['size_bytes']/1024))?> KB</span></span><?php endforeach?></div><?php endif?></section>
<?php endif?>
<?php if($id&&$status==='accepted'&&!$form['sent_copy']):?><section class="card"><h2>Fehlende Gesendet-Kopie prüfen</h2><p>Die Mail ist raus. Hier wird nur geprüft, ob eine Kopie im Ordner „Gesendet“ liegt – es wird nichts erneut gesendet. Die Suche beginnt erst mit dem folgenden Knopf.</p>
<?php if(empty($form['smtp_raw'])||empty($form['sent_copy_folder'])):?><p>Für diese Mail fehlt das gespeicherte Original oder der Zielordner (älterer Versand). Die Mail ist trotzdem angekommen; eine Kopie lässt sich hier nicht nachträglich ablegen.</p><?php else:?><form method="post"><?php gszFields('copy_preview');?><button>Ordner „Gesendet“ nach dieser Mail durchsuchen</button></form>
<?php $copyPreview=$_SESSION['mail_copy_preview']??null;if(is_array($copyPreview)&&($copyPreview['draft_id']??null)===$id&&($copyPreview['created']??0)>=time()-600):?><p class="muted">Ordner: <?=e(gszMailFolderLabel((string)$copyPreview['folder']))?> · Nachrichtenidentität: <?=e($copyPreview['message_id'])?> · UIDVALIDITY: <?=(int)$copyPreview['uidvalidity']?></p>
<?php if($copyPreview['result']==='conflict'):?><p>Mehrere oder als gelöscht markierte Kopien gefunden. Keine weitere Kopie möglich; bitte im Webmail prüfen.</p>
<?php elseif($copyPreview['result']==='epoch_changed'):?><p>Der Ordner wurde zwischenzeitlich neu aufgebaut und es wurde keine Kopie gefunden. Keine weitere Kopie möglich; bitte im Webmail prüfen.</p>
<?php else:?><p><?=$copyPreview['result']==='found'?'Eine vorhandene Kopie gefunden. Die Bestätigung übernimmt sie.':'Keine Kopie gefunden. Nach Bestätigung wird die unveränderte Mail einmalig in den Ordner „Gesendet“ gelegt.'?> Vor der Bestätigung wird erneut gesucht.</p><form method="post"><?php gszFields('copy_confirm');?>
<?php if($copyPreview['uncertain']&&$copyPreview['result']==='missing'):?><p>Ein vorheriger Kopierversuch blieb unklar. Eine verzögert sichtbare Kopie kann trotz leerem Suchergebnis zu einer Dublette führen.</p><label><input type="checkbox" name="acknowledge_uncertain" required> Ich habe im Webmail nachgesehen und lege die Kopie bewusst noch einmal ab.</label><?php endif?>
<label><input type="checkbox" name="confirm_copy" required> Ja, so übernehmen.</label><button><?=$copyPreview['result']==='found'?'Gefundene Kopie bestätigen':'Kopie in „Gesendet“ ablegen'?></button></form><?php endif?><?php endif?><?php endif?></section><?php endif?>
<?php if($id&&in_array($status,['sending','uncertain'],true)):?><section class="card"><h2>Unklar, ob die Mail rausging</h2><p>Bitte im Webmail im Ordner „Gesendet“ oder beim Empfänger nachsehen und hier festhalten, was du gefunden hast. Die Zentrale sendet nichts von selbst erneut.</p><form method="post"><?php gszFields('resolve');?><label>Ergebnis<select name="decision"><option value="sent">Die Mail ist rausgegangen – nicht erneut senden</option><option value="not_sent">Die Mail ist nicht rausgegangen – Entwurf wieder freigeben</option></select></label><label>Kurze Notiz<input name="reason" maxlength="120" required placeholder="z. B. im Webmail unter Gesendet gefunden"></label><label><input type="checkbox" name="confirm_review" required> Ich habe nachgesehen.</label><button>Speichern</button></form></section><?php endif?>
<?php if($drafts):?><h2 class="section-title">Entwürfe</h2><div class="list"><?php foreach($drafts as $d):?><a href="compose.php?draft=<?=(int)$d['id']?>"><span class="name"><?=e($d['subject']?:'(ohne Betreff)')?><span class="sub"><?=e($d['to_address']?:'Kein Empfänger')?><?=$d['status']==='rejected'?' · Nicht gesendet':''?></span></span></a><?php endforeach?></div><?php endif?>
<?php if($sent):?><details class="card"><summary>Aus der Zentrale gesendet</summary><div class="list"><?php foreach($sent as $d):?><a href="compose.php?draft=<?=(int)$d['id']?>"><span class="name"><?=e($d['subject']?:'(ohne Betreff)')?><span class="sub"><?=e($d['to_address'])?> · <?=e(gszMailDraftState($d))?></span></span></a><?php endforeach?></div></details><?php endif?>
<?php gszPageEnd();?>
