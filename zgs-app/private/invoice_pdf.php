<?php
declare(strict_types=1);
require_once __DIR__.'/vendor/tfpdf/font/unifont/ttfonts.php';require_once __DIR__.'/vendor/tfpdf/tfpdf.php';
/** Embedded-font multi-page output. Technical PDF, not a validated e-invoice/PDF-A. */
final class GszInvoicePdf extends tFPDF {
 public string $documentLabel='RECHNUNG';public string $documentNumber='';
 public function __construct(){parent::__construct();$cache=__DIR__.'/uploads/pdf_fonts/unifont';if(!is_dir($cache)&&!mkdir($cache,0700,true)&&!is_dir($cache))throw new RuntimeException('PDF-Schriftcache nicht verfügbar.');$font=__DIR__.'/vendor/tfpdf/font/unifont/DejaVuSans.ttf';$copy=$cache.'/DejaVuSans.ttf';if(!is_file($copy)){if(!copy($font,$copy))throw new RuntimeException('PDF-Schrift fehlt.');@chmod($copy,0600);}if(is_link($copy)||hash_file('sha256',$font)!==hash_file('sha256',$copy))throw new RuntimeException('PDF-Schriftintegrität verletzt.');$this->fontpath=dirname($cache).'/';$this->AddFont('DejaVu','','DejaVuSans.ttf',true);$this->SetMargins(18,24,18);$this->SetAutoPageBreak(true,20);}
 public function Header(){ $this->SetFont('DejaVu','',11);$this->SetY(10);$this->Cell(0,7,$this->documentLabel.' '.$this->documentNumber,0,1);$this->Ln(4);}
 public function Footer(){ $this->SetY(-14);$this->SetFont('DejaVu','',8);$this->Cell(0,6,'Seite '.$this->PageNo().' · Technischer Dokumentstand, fachliche Prüfung erforderlich',0,0,'C');}
 public function paragraph(string $text,int $size=10):void{$this->SetFont('DejaVu','',$size);if(!mb_check_encoding($text,'UTF-8'))throw new RuntimeException('Ungültiger PDF-Zeichensatz.');foreach(mb_str_split($text) as $char){$code=mb_ord($char,'UTF-8');if(in_array($code,[9,10,13],true))continue;$cw=$this->CurrentFont['cw'];if($code>65535||!isset($cw[$code*2+1])||(ord($cw[$code*2])===0&&ord($cw[$code*2+1])===0))throw new RuntimeException('PDF-Schrift unterstützt ein Zeichen nicht. Keine stille Ersetzung.');}$this->MultiCell(0,5.5,$text,0,'L');}
}
function invoicePdf(array $invoice,array $items):string {
 if(count($items)>500)throw new RuntimeException('Maximal 500 PDF-Positionen.');$pdf=new GszInvoicePdf();$pdf->documentNumber=(string)($invoice['number']??'ENTWURF');$pdf->documentLabel=match($invoice['document_kind']??'invoice'){'offer'=>'ANGEBOT','credit_note'=>'STORNOBELEG',default=>'RECHNUNG'};$pdf->AddPage();
 $lines=['Von: '.($invoice['issuer_name']??''),(string)($invoice['issuer_address']??''),'','An: '.($invoice['recipient_name']??''),(string)($invoice['recipient_address']??''),'','Dokumentdatum: '.($invoice['invoice_date']??''),'Leistungsdatum: '.($invoice['service_date']??''),'Fällig am: '.($invoice['due_date']??''),''];
 foreach($lines as $line)$pdf->paragraph($line);
 foreach($items as $i){$description=(string)$i['description'];if(!mb_check_encoding($description,'UTF-8')||mb_strlen($description)>500)throw new RuntimeException('Positionsbeschreibung ungültig.');$pdf->paragraph((string)$i['position'].'. '.$description,11);$pdf->paragraph('Menge '.$i['quantity'].' '.$i['unit'].' · Einzelpreis netto '.$i['unit_price'].' EUR · USt '.$i['tax_rate'].' % · Brutto '.$i['gross_amount'].' EUR');$pdf->Ln(3);}
 $pdf->Ln(3);foreach(['Netto: '.($invoice['net_total']??'').' EUR','Umsatzsteuer: '.($invoice['tax_total']??'').' EUR','Gesamt: '.($invoice['gross_total']??'').' EUR','IBAN: '.($invoice['issuer_iban']??''),'BIC: '.($invoice['issuer_bic']??''),'Steuernummer: '.($invoice['issuer_tax_id']??''),'USt-IdNr: '.($invoice['issuer_vat_id']??''),'Hinweise: '.($invoice['notes']??'')] as $line)$pdf->paragraph($line);
 return $pdf->Output('S');
}
