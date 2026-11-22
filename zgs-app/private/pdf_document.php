<?php
declare(strict_types=1);
require_once __DIR__.'/vendor/tfpdf/font/unifont/ttfonts.php';require_once __DIR__.'/vendor/tfpdf/tfpdf.php';
/** Embedded-font multi-page output for statements and work reports. Technical PDF, not PDF/A. */
final class GszPdfDocument extends tFPDF {
 public string $documentLabel='DOKUMENT';public string $documentNumber='';
 public function __construct(){parent::__construct();$cache=__DIR__.'/uploads/pdf_fonts/unifont';if(!is_dir($cache)&&!mkdir($cache,0700,true)&&!is_dir($cache))throw new RuntimeException('PDF-Schriftcache nicht verfügbar.');$font=__DIR__.'/vendor/tfpdf/font/unifont/DejaVuSans.ttf';$copy=$cache.'/DejaVuSans.ttf';if(!is_file($copy)){if(!copy($font,$copy))throw new RuntimeException('PDF-Schrift fehlt.');@chmod($copy,0600);}if(is_link($copy)||hash_file('sha256',$font)!==hash_file('sha256',$copy))throw new RuntimeException('PDF-Schriftintegrität verletzt.');$this->fontpath=dirname($cache).'/';$this->AddFont('DejaVu','','DejaVuSans.ttf',true);$this->SetMargins(18,24,18);$this->SetAutoPageBreak(true,20);}
 public function Header(){ $this->SetFont('DejaVu','',11);$this->SetY(10);$this->Cell(0,7,$this->documentLabel.' '.$this->documentNumber,0,1);$this->Ln(4);}
 public function Footer(){ $this->SetY(-14);$this->SetFont('DejaVu','',8);$this->Cell(0,6,'Seite '.$this->PageNo().' · Technischer Dokumentstand, fachliche Prüfung erforderlich',0,0,'C');}
 public function paragraph(string $text,int $size=10):void{$this->SetFont('DejaVu','',$size);if(!mb_check_encoding($text,'UTF-8'))throw new RuntimeException('Ungültiger PDF-Zeichensatz.');foreach(mb_str_split($text) as $char){$code=mb_ord($char,'UTF-8');if(in_array($code,[9,10,13],true))continue;$cw=$this->CurrentFont['cw'];if($code>65535||!isset($cw[$code*2+1])||(ord($cw[$code*2])===0&&ord($cw[$code*2+1])===0))throw new RuntimeException('PDF-Schrift unterstützt ein Zeichen nicht. Keine stille Ersetzung.');}$this->MultiCell(0,5.5,$text,0,'L');}
}
