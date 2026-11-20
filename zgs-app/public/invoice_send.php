<?php
declare(strict_types=1);
require __DIR__.'/bootstrap.php';if(empty($_SESSION['admin_id']))redirect('index.php');
require_once dirname(__DIR__).'/private/invoice_lib.php';
require_once dirname(__DIR__).'/private/invoice_archive.php';
require_once dirname(__DIR__).'/private/smtp.php';
$id=(int)($_GET['id']??$_POST['id']??0);$error='';$sent=false;
$inv=invGet($pdo,$id);$items=invItems($pdo,$id);
if(!in_array($inv['status'],['issued','paid'],true)){http_response_code(403);exit('Nur ausgestellte Rechnungen dürfen versendet werden.');}
$to=(string)$inv['recipient_email'];$subject=($inv['document_kind']==='credit_note'?'Stornobeleg ':'Rechnung ').$inv['number'].' – '. $inv['issuer_name'];
$body="Sehr geehrte Damen und Herren,\n\nanbei erhalten Sie unsere Rechnung ".$inv['number'].".\n\nMit freundlichen Grüßen\n".$inv['issuer_name'];
if($_SERVER['REQUEST_METHOD']==='POST'){
 checkCsrf();$to=trim((string)($_POST['to']??''));$subject=trim((string)($_POST['subject']??''));$body=(string)($_POST['body']??'');
 try {
  if(!filter_var($to,FILTER_VALIDATE_EMAIL)||preg_match('/[\r\n]/',$to)||!$subject||preg_match('/[\r\n]/',$subject)||mb_strlen($subject)>190||!trim($body))throw new RuntimeException('Ungültige E-Mail-Angaben.');
  if(!empty($_SESSION['last_invoice_sent'])&&time()-(int)$_SESSION['last_invoice_sent']<60)throw new RuntimeException('Bitte 60 Sekunden zwischen Rechnungssendungen warten.');
  // SMTP accepted does not guarantee final delivery or saved copy in Sent.
  $file=invoiceDocumentPdf($inv,$items);
  zgsSmtpSend($config,$to,$subject,$body,null,['name'=>$inv['number'].'.pdf','data'=>$file]);
  $_SESSION['last_invoice_sent']=time();invEvent($pdo,$id,'smtp_accepted','Empfänger: '.$to.'; SMTP angenommen, keine Zustellbestätigung');$sent=true;
 } catch(Throwable $ex){error_log('GSZ_INVOICE_SMTP_UNCONFIRMED');$error='Versand nicht bestätigt. Prüfe die SMTP-Konfiguration und ob die Nachricht möglicherweise trotzdem angekommen ist.';}
}
?><!doctype html><html lang="de"><head><meta charset="utf-8"><meta name="viewport" content="width=device-width,initial-scale=1"><meta name="robots" content="noindex,nofollow"><title>Rechnung versenden</title><link rel="stylesheet" href="assets.css"><style>main{max-width:760px;margin:auto;padding:20px}.card{padding:20px}.error{color:#ffb5b5}</style></head><body><main><header><div class="brand"><img class="brand-logo" src="assets/gsz-logo-horizontal.webp" alt="Gebäudeservice Zentrum" width="640" height="188"></div><a href="invoice.php?id=<?=$id?>">← Rechnung</a></header><h1>Rechnung per E-Mail versenden</h1><p class="muted">PDF wird automatisch angehängt. E-Rechnung-XML ist noch nicht validiert und wird nicht versendet.</p><?php if($error):?><p class="error"><?=e($error)?></p><?php endif;?><?php if($sent):?><p role="status">SMTP-Server hat die Nachricht angenommen. Das ist keine Zustellbestätigung. Bitte Empfängerpostfach prüfen.</p><?php else:?><section class="card"><form method="post"><input type="hidden" name="csrf" value="<?=e(csrf())?>"><input type="hidden" name="id" value="<?=$id?>"><label>Empfänger<input type="email" name="to" required value="<?=e($to)?>"></label><label>Betreff<input name="subject" maxlength="190" required value="<?=e($subject)?>"></label><label>Nachricht<textarea name="body" rows="9" required><?=e($body)?></textarea></label><p>Anhang: <?=e($inv['number'])?>.pdf</p><button>Rechnung per E-Mail senden</button></form></section><?php endif;?><p class="muted">Mehrseitiges PDF; kein PDF/A-/E-Rechnungsnachweis. Nicht ohne fachliche Prüfung produktiv einsetzen.</p></main></body></html>
