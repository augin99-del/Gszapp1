<?php
declare(strict_types=1);
require __DIR__.'/bootstrap.php';
if(empty($_SESSION['admin_id']))redirect('index.php');
require_once dirname(__DIR__).'/private/invoice_lib.php';
require_once dirname(__DIR__).'/private/invoice_archive.php';
$id=(int)($_GET['id']??$_POST['id']??0);$error='';$inv=[];$items=[];
try {
 if($id<1)throw new RuntimeException('Ungültige Rechnungs-ID.');
 if($_SERVER['REQUEST_METHOD']==='POST'){
  checkCsrf();$action=(string)($_POST['action']??'');
  if($action==='item'){
   $description=trim((string)($_POST['description']??''));$unit=trim((string)($_POST['unit']??'Stk'));
   $qty=invDecimal((string)($_POST['quantity']??''));$price=invMoney((string)($_POST['unit_price']??''));
   $rate=(string)($_POST['tax_rate']??'');
   if(!in_array($rate,['0','7','19'],true))throw new RuntimeException('Nur 0, 7 oder 19 % Umsatzsteuer in dieser Version. Steuerrechtliche Zulässigkeit bitte prüfen.');
   if(!$description||mb_strlen($description)>500||!$unit||mb_strlen($unit)>20||$qty<1)throw new RuntimeException('Position ungültig.');
   if($price>0&&$qty>intdiv(PHP_INT_MAX-500,$price))throw new RuntimeException('Positionsbetrag zu groß.');$net=intdiv($qty*$price+500,1000);$tax=intdiv($net*(int)$rate+50,100);$gross=$net+$tax;
   $pdo->beginTransaction();$s=$pdo->prepare('SELECT status FROM invoices WHERE id=? FOR UPDATE');$s->execute([$id]);if($s->fetchColumn()!=='draft')throw new RuntimeException('Ausgestellte Rechnungen können nicht geändert werden.');
   $s=$pdo->prepare('SELECT COALESCE(MAX(position),0)+1 FROM invoice_items WHERE invoice_id=?');$s->execute([$id]);$pos=(int)$s->fetchColumn();
   $s=$pdo->prepare('INSERT INTO invoice_items(invoice_id,position,description,quantity,unit,unit_price,tax_rate,net_amount,tax_amount,gross_amount) VALUES(?,?,?,?,?,?,?,?,?,?)');
   $s->execute([$id,$pos,$description,number_format($qty/1000,3,'.',''),$unit,number_format($price/100,2,'.',''),$rate,number_format($net/100,2,'.',''),number_format($tax/100,2,'.',''),number_format($gross/100,2,'.','')]);
   invRecalc($pdo,$id);invEvent($pdo,$id,'item_added',$description);$pdo->commit();redirect('invoice.php?id='.$id);
  }
  if($action==='remove'){
   $pdo->beginTransaction();$s=$pdo->prepare('SELECT status FROM invoices WHERE id=? FOR UPDATE');$s->execute([$id]);if($s->fetchColumn()!=='draft')throw new RuntimeException('Ausgestellte Rechnungen sind gesperrt.');
   $pdo->prepare('DELETE FROM invoice_items WHERE invoice_id=? AND id=?')->execute([$id,(int)($_POST['item_id']??0)]);invRecalc($pdo,$id);invEvent($pdo,$id,'item_removed');$pdo->commit();redirect('invoice.php?id='.$id);
  }
  if($action==='issue'){
   if(!filter_var($config['allow_invoice_issue']??false,FILTER_VALIDATE_BOOLEAN))throw new RuntimeException('Rechnungsausstellung ist bis technischer und fachlicher Freigabe serverseitig gesperrt.');
   $date=(string)($_POST['invoice_date']??'');$service=(string)($_POST['service_date']??'');$due=(string)($_POST['due_date']??'');
   foreach([$date,$service,$due] as $d){$dt=DateTimeImmutable::createFromFormat('!Y-m-d',$d);if(!$dt||$dt->format('Y-m-d')!==$d)throw new RuntimeException('Bitte gültige Datumsangaben eingeben.');}
   if($due<$date)throw new RuntimeException('Das Zahlungsziel liegt vor dem Rechnungsdatum.');
   $pdo->beginTransaction();$inv=invGetLocked($pdo,$id);
   if(($inv['document_kind']??'invoice')!=='invoice')throw new RuntimeException('Nur Rechnungsentwürfe hier ausstellen.');
   if($inv['status']!=='draft')throw new RuntimeException('Diese Rechnung ist nicht mehr als Entwurf bearbeitbar.');
   if(!invItems($pdo,$id))throw new RuntimeException('Mindestens eine Position ist erforderlich.');
   if(!$inv['issuer_tax_id']&&!$inv['issuer_vat_id'])throw new RuntimeException('Steuernummer oder USt-IdNr. des Rechnungsstellers fehlt.');
   if(!$inv['issuer_iban'])throw new RuntimeException('IBAN fehlt.');
   $year=(int)substr($date,0,4);
   $pdo->prepare('INSERT INTO invoice_sequences(year,last_number) VALUES(?,0) ON DUPLICATE KEY UPDATE year=VALUES(year)')->execute([$year]);
   $s=$pdo->prepare('SELECT last_number FROM invoice_sequences WHERE year=? FOR UPDATE');$s->execute([$year]);$next=(int)$s->fetchColumn()+1;
   $number=sprintf('GSZ-%04d-%05d',$year,$next);
   $pdo->prepare('UPDATE invoice_sequences SET last_number=? WHERE year=?')->execute([$next,$year]);
   $pdo->prepare("UPDATE invoices SET number=?,status='issued',invoice_date=?,service_date=?,due_date=?,issued_at=NOW() WHERE id=?")->execute([$number,$date,$service,$due,$id]);
   $check=invGet($pdo,$id);invoiceArchive($pdo,$id);invEvent($pdo,$id,'issued','Rechnungsnummer '.$number);$pdo->commit();redirect('invoice.php?id='.$id);
  }
 }
 $inv=invGet($pdo,$id);if(($inv['document_kind']??'invoice')==='offer')redirect('offers.php?id='.$id);$items=invItems($pdo,$id);
} catch(Throwable $ex){if($pdo->inTransaction())$pdo->rollBack();$error=$ex instanceof PDOException?'Rechnungsdaten nicht verfügbar. Schema/Parameter prüfen.':$ex->getMessage();try{$inv=$id?invGet($pdo,$id):[];$items=$id?invItems($pdo,$id):[];}catch(Throwable $missing){$inv=[];$items=[];http_response_code(404);}}
function invGetLocked(PDO $pdo,int $id):array{$s=$pdo->prepare('SELECT * FROM invoices WHERE id=? FOR UPDATE');$s->execute([$id]);$v=$s->fetch();if(!$v)throw new RuntimeException('Rechnung nicht gefunden.');return $v;}
?><!doctype html><html lang="de"><head><meta charset="utf-8"><meta name="viewport" content="width=device-width,initial-scale=1"><meta name="robots" content="noindex,nofollow"><title>Rechnung · GSZ</title><link rel="stylesheet" href="assets.css"><style>main{max-width:960px;margin:auto;padding:20px}.card{padding:20px}.error{color:#ffb5b5}.item{padding:12px;border-bottom:1px solid #40535f}form.inline{display:inline}form.inline button{width:auto}.actions{display:flex;gap:12px;flex-wrap:wrap}</style></head><body><main><header><div class="brand"><img class="brand-logo" src="assets/gsz-logo-horizontal.webp" alt="Gebäudeservice Zentrum" width="640" height="188"></div><a href="invoices.php">← Rechnungen</a></header><?php if($error):?><p class="error"><?=e($error)?></p><?php endif;?><?php if($inv):?><h1><?=e($inv['number']?:'Entwurf #'.$id)?></h1><p class="muted">Status: <?=e($inv['status'])?> · <?=e($inv['recipient_name'])?></p><section class="card"><h2>Rechnungspositionen</h2><?php foreach($items as $i):?><div class="item"><strong><?=e($i['description'])?></strong><div><?=e($i['quantity'])?> <?=e($i['unit'])?> × <?=e($i['unit_price'])?> € + <?=e($i['tax_rate'])?> % = <?=e($i['gross_amount'])?> €</div><?php if($inv['status']==='draft'):?><form method="post" class="inline"><input type="hidden" name="csrf" value="<?=e(csrf())?>"><input type="hidden" name="action" value="remove"><input type="hidden" name="id" value="<?=$id?>"><input type="hidden" name="item_id" value="<?=(int)$i['id']?>"><button>Entfernen</button></form><?php endif;?></div><?php endforeach;?><p>Netto: <?=e($inv['net_total'])?> € · USt: <?=e($inv['tax_total'])?> €</p><h2>Gesamt: <?=e($inv['gross_total'])?> €</h2></section>
<?php if($inv['status']==='draft'):?><section class="card"><h2>Position hinzufügen</h2><form method="post"><input type="hidden" name="csrf" value="<?=e(csrf())?>"><input type="hidden" name="action" value="item"><input type="hidden" name="id" value="<?=$id?>"><label>Beschreibung<input name="description" maxlength="500" required></label><label>Menge<input name="quantity" inputmode="decimal" value="1" required></label><label>Einheit<input name="unit" value="Stk" maxlength="20" required></label><label>Einzelpreis netto (€)<input name="unit_price" inputmode="decimal" placeholder="100,00" required></label><label>USt-Satz<select name="tax_rate"><option value="19">19 %</option><option value="7">7 %</option><option value="0">0 % (nur falls rechtlich zutreffend)</option></select></label><button>Position hinzufügen</button></form></section><section class="card"><h2>Rechnung ausstellen</h2><p class="muted">Prüfe sämtliche Angaben vor der endgültigen Ausstellung. Danach ist die Rechnung gesperrt.</p><form method="post"><input type="hidden" name="csrf" value="<?=e(csrf())?>"><input type="hidden" name="action" value="issue"><input type="hidden" name="id" value="<?=$id?>"><label>Rechnungsdatum<input type="date" name="invoice_date" value="<?=e($inv['invoice_date']?:date('Y-m-d'))?>" required></label><label>Leistungsdatum<input type="date" name="service_date" value="<?=e($inv['service_date']?:date('Y-m-d'))?>" required></label><label>Fällig am<input type="date" name="due_date" value="<?=e($inv['due_date']??'')?>" required></label><button>Verbindlich ausstellen</button></form></section><?php else:?>
<section class="card"><h2>Ausgabe und Versand</h2><p><a href="invoice_export.php?id=<?=$id?>&amp;format=pdf"><?=e(invoiceArchiveLabel($inv))?> herunterladen (fachliche Prüfung erforderlich)</a></p><?php if(filter_var($config['allow_unvalidated_xml']??false,FILTER_VALIDATE_BOOLEAN)):?><p><a href="invoice_export.php?id=<?=$id?>&amp;format=xml">XML-Prototyp, keine geprüfte E-Rechnung</a></p><?php else:?><p>Validierte E-Rechnung nicht verfügbar; XML-Prototyp gesperrt.</p><?php endif?><p><a href="invoice_send.php?id=<?=$id?>">Per E-Mail senden →</a></p><p class="muted">Mehrseitiges PDF mit eingebetteter Schrift, nicht PDF/A. XML ist nicht EN-16931/XRechnung-validiert. E-Rechnung erst nach validierter Implementierung und Fachprüfung. Keine Lexware-Anbindung erforderlich.</p></section>
<?php if(in_array($inv['status'],['issued','paid'],true)&&($inv['document_kind']??'invoice')==='invoice'):?><section class="card"><h2>Zahlungen und Storno</h2><p><a href="accounting.php?invoice=<?=$id?>">Zahlung/Teilzahlung nachvollziehbar erfassen</a></p><form method="post" action="invoices.php"><input type="hidden" name="csrf" value="<?=e(csrf())?>"><input type="hidden" name="action" value="status"><input type="hidden" name="status" value="cancelled"><input type="hidden" name="id" value="<?=$id?>"><label>Stornodatum<input type="date" name="date" required value="<?=date('Y-m-d')?>"></label><label>Begründung<input name="reason" maxlength="500" required></label><label><input type="checkbox" name="confirm_credit" required> Korrektur geprüft; eigenen negativen Referenzbeleg erstellen, keine Bankaktion.</label><button>Stornobeleg erzeugen (Serverfreigabe erforderlich)</button></form></section><?php endif;?><?php endif;?><?php endif;?></main></body></html>
