<?php
declare(strict_types=1);
require __DIR__.'/bootstrap.php';
if(empty($_SESSION['admin_id'])) redirect('index.php');
require_once dirname(__DIR__).'/private/invoice_lib.php';
require_once dirname(__DIR__).'/private/business_documents.php';
$action=(string)($_POST['action']??'');$error='';$success='';
try {
 if($_SERVER['REQUEST_METHOD']==='POST'){
  checkCsrf();
  if($action==='create'){
   $customer=(int)($_POST['customer_id']??0);$property=(int)($_POST['property_id']??0);
   $s=$pdo->prepare('SELECT * FROM customers WHERE id=?');$s->execute([$customer]);$c=$s->fetch();if(!$c)throw new RuntimeException('Bitte einen vorhandenen Kunden wählen.');
   if($property){$s=$pdo->prepare('SELECT id FROM properties WHERE id=? AND customer_id=?');$s->execute([$property,$customer]);if(!$s->fetch())throw new RuntimeException('Das Objekt gehört nicht zum Kunden.');}
   $name=trim((string)($_POST['recipient_name']??''));$address=trim((string)($_POST['recipient_address']??''));if($address==='')$address=trim((string)($c['billing_address']??''));if(!empty($c['archived_at']))throw new RuntimeException('Archivierten Kunden zuerst reaktivieren.');
   $email=trim((string)($_POST['recipient_email']??''));
   if(!$name||mb_strlen($name)>190||!$address||mb_strlen($address)>2000||($email&&!filter_var($email,FILTER_VALIDATE_EMAIL)))throw new RuntimeException('Empfängername, Adresse oder E-Mail ungültig.');
   $issuerName=trim((string)($config['invoice_issuer_name']??''));$issuerAddress=trim((string)($config['invoice_issuer_address']??''));
   if(!$issuerName||!$issuerAddress)throw new RuntimeException('Bitte erst Rechnungssteller und Adresse in private/config.php konfigurieren.');
   $s=$pdo->prepare('INSERT INTO invoices(customer_id,property_id,recipient_name,recipient_address,recipient_email,issuer_name,issuer_address,issuer_tax_id,issuer_vat_id,issuer_iban,issuer_bic,issuer_bank,notes) VALUES(?,?,?,?,?,?,?,?,?,?,?,?,?)');
   $s->execute([$customer,$property?:null,$name,$address,$email,$issuerName,$issuerAddress,(string)($config['invoice_tax_id']??''),(string)($config['invoice_vat_id']??''),(string)($config['invoice_iban']??''),(string)($config['invoice_bic']??''),(string)($config['invoice_bank']??''),'']);
   $id=(int)$pdo->lastInsertId();invEvent($pdo,$id,'created');redirect('invoice.php?id='.$id);
  }
  if($action==='status'){
   if(!filter_var($config['allow_invoice_issue']??false,FILTER_VALIDATE_BOOLEAN))throw new RuntimeException('Rechnungs-/Stornofreigabe fehlt.');
   $id=(int)($_POST['id']??0);if(($_POST['status']??'')!=='cancelled'||empty($_POST['confirm_credit']))throw new RuntimeException('Zahlungen über Buchhaltung; Storno ausdrücklich bestätigen.');
   $credit=invoiceCredit($pdo,(int)$_SESSION['admin_id'],$id,(string)($_POST['date']??''),(string)($_POST['reason']??''));redirect('invoice.php?id='.$credit);
  }
 }
} catch(Throwable $ex){if($pdo->inTransaction())$pdo->rollBack();$error=$ex->getMessage();}
$customers=$pdo->query('SELECT id,name,email FROM customers ORDER BY name')->fetchAll();
$properties=$pdo->query('SELECT id,name,customer_id FROM properties ORDER BY name')->fetchAll();
$invoices=$pdo->query("SELECT i.id,i.number,i.status,i.recipient_name,i.invoice_date,i.due_date,i.gross_total FROM invoices i WHERE i.document_kind<>'offer' ORDER BY i.id DESC LIMIT 100")->fetchAll();
?><!doctype html><html lang="de"><head><meta charset="utf-8"><meta name="viewport" content="width=device-width,initial-scale=1"><meta name="robots" content="noindex,nofollow"><title>Rechnungen · GSZ</title><link rel="stylesheet" href="assets.css"><style>main{max-width:980px;margin:auto;padding:20px}.grid{display:grid;grid-template-columns:repeat(auto-fit,minmax(280px,1fr));gap:16px}.card{padding:18px}.item{padding:12px;border-bottom:1px solid #40535f}.error{color:#ffb5b5}</style></head><body><main><header><div class="brand"><img class="brand-logo" src="assets/gsz-logo-horizontal.webp" alt="Gebäudeservice Zentrum" width="640" height="188"></div><a href="index.php">← Dashboard</a></header><h1>Rechnungsverwaltung</h1><p><a href="receivables.php">Offene Posten / Zahlungserinnerungen</a> · <a href="finance_recurrences.php">Wiederkehrende Finanzvorlagen</a></p><p><a href="offers.php">Angebote verwalten</a> · <a href="accounting.php">Belege/Zahlungen</a></p><p class="muted">Entwurf → Prüfung → Ausstellung → Zahlung oder Storno</p><?php if($error):?><p class="error"><?=e($error)?></p><?php endif;?><div class="grid"><section class="card"><h2>Neuen Entwurf anlegen</h2><form method="post"><input type="hidden" name="csrf" value="<?=e(csrf())?>"><input type="hidden" name="action" value="create"><label>Kunde<select name="customer_id" required><option value="">Bitte wählen</option><?php foreach($customers as $c):?><option value="<?=(int)$c['id']?>"><?=e($c['name'])?></option><?php endforeach;?></select></label><label>Objekt (optional)<select name="property_id"><option value="">Ohne Objekt</option><?php foreach($properties as $p):?><option value="<?=(int)$p['id']?>"><?=e($p['name'])?> (Kunde #<?=(int)$p['customer_id']?>)</option><?php endforeach;?></select></label><label>Rechnungsempfänger<input name="recipient_name" maxlength="190" required></label><label>Rechnungsanschrift<textarea name="recipient_address" maxlength="2000" rows="3"></textarea></label><label>E-Mail-Adresse<input name="recipient_email" type="email" maxlength="190"></label><button>Entwurf anlegen</button></form></section><section class="card"><h2>Letzte Rechnungen</h2><?php foreach($invoices as $i):?><div class="item"><a href="invoice.php?id=<?=(int)$i['id']?>"><?=e($i['number']?:'Entwurf #'.$i['id'])?></a> · <?=e($i['status'])?><div class="muted"><?=e($i['recipient_name'])?> · <?=e($i['gross_total'])?> EUR · <?=e($i['due_date'])?></div></div><?php endforeach;?></section></div><p class="muted">Achtung: Diese Entwicklungsfassung ist noch nicht für rechtsverbindliche Rechnungen freigegeben.</p></main></body></html>
