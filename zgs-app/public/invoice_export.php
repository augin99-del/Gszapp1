<?php
declare(strict_types=1);
require __DIR__.'/bootstrap.php';
if(empty($_SESSION['admin_id'])){http_response_code(403);exit('Nicht angemeldet');}
require_once dirname(__DIR__).'/private/invoice_lib.php';
$id=filter_input(INPUT_GET,'id',FILTER_VALIDATE_INT);$format=(string)($_GET['format']??'');
if(!$id||!in_array($format,['pdf','xml'],true)){http_response_code(400);exit('Ungültige Anfrage');}
if($format==='xml'&&!filter_var($config['allow_unvalidated_xml']??false,FILTER_VALIDATE_BOOLEAN)){http_response_code(403);exit('Nicht validierter XML-Prototyp gesperrt. Keine geprüfte E-Rechnung verfügbar.');}
$inv=invGet($pdo,$id);if($inv['status']==='draft'){http_response_code(403);exit('Entwurf noch nicht ausgestellt');}
$items=invItems($pdo,$id);if($format==='xml'&&($inv['document_kind']??'invoice')!=='invoice'){http_response_code(400);exit('XML-Prototyp unterstützt diese Dokumentart nicht.');}
if($format==='pdf'){
 require_once dirname(__DIR__).'/private/invoice_archive.php';
 try{$content=invoiceDocumentPdf($inv,$items);}catch(Throwable $e){http_response_code(422);exit('PDF konnte nicht sicher erzeugt werden. Zeichensätze und Umfang prüfen.');}$type='application/pdf';$extension='pdf';
}else{
 // UBL XML demonstrator: deliberately NOT a validated EN 16931 / XRechnung output.
 $x=static fn($s)=>invXml((string)$s);
 $content='<?xml version="1.0" encoding="UTF-8"?>' ."\n";
 $content.='<Invoice xmlns="urn:oasis:names:specification:ubl:schema:xsd:Invoice-2" xmlns:cac="urn:oasis:names:specification:ubl:schema:xsd:CommonAggregateComponents-2" xmlns:cbc="urn:oasis:names:specification:ubl:schema:xsd:CommonBasicComponents-2">';
 $content.='<cbc:ID>'.$x($inv['number']).'</cbc:ID><cbc:IssueDate>'.$x($inv['invoice_date']).'</cbc:IssueDate><cbc:DueDate>'.$x($inv['due_date']).'</cbc:DueDate><cbc:InvoiceTypeCode>380</cbc:InvoiceTypeCode><cbc:DocumentCurrencyCode>EUR</cbc:DocumentCurrencyCode>';
 $content.='<cac:AccountingSupplierParty><cac:Party><cac:PartyName><cbc:Name>'.$x($inv['issuer_name']).'</cbc:Name></cac:PartyName></cac:Party></cac:AccountingSupplierParty>';
 $content.='<cac:AccountingCustomerParty><cac:Party><cac:PartyName><cbc:Name>'.$x($inv['recipient_name']).'</cbc:Name></cac:PartyName></cac:Party></cac:AccountingCustomerParty>';
 $content.='<cac:LegalMonetaryTotal><cbc:LineExtensionAmount currencyID="EUR">'.$x($inv['net_total']).'</cbc:LineExtensionAmount><cbc:TaxExclusiveAmount currencyID="EUR">'.$x($inv['net_total']).'</cbc:TaxExclusiveAmount><cbc:TaxInclusiveAmount currencyID="EUR">'.$x($inv['gross_total']).'</cbc:TaxInclusiveAmount><cbc:PayableAmount currencyID="EUR">'.$x($inv['gross_total']).'</cbc:PayableAmount></cac:LegalMonetaryTotal>';
 foreach($items as $item){$content.='<cac:InvoiceLine><cbc:ID>'.$x($item['position']).'</cbc:ID><cbc:InvoicedQuantity unitCode="C62">'.$x($item['quantity']).'</cbc:InvoicedQuantity><cbc:LineExtensionAmount currencyID="EUR">'.$x($item['net_amount']).'</cbc:LineExtensionAmount><cac:Item><cbc:Name>'.$x($item['description']).'</cbc:Name></cac:Item><cac:Price><cbc:PriceAmount currencyID="EUR">'.$x($item['unit_price']).'</cbc:PriceAmount></cac:Price></cac:InvoiceLine>';}
 $content.='</Invoice>';
 $type='application/xml';$extension='xml';
}
header('X-Content-Type-Options: nosniff');header('Cache-Control: private, no-store');header('Content-Type: '.$type);header('Content-Disposition: attachment; filename="'.preg_replace('/[^a-zA-Z0-9_-]/','_', $inv['number']).'.'.$extension.'"');header('Content-Length: '.strlen($content));echo $content;
