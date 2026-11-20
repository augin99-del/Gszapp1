<?php
declare(strict_types=1);
// Gemeinsame PDF-Erzeugung (Nebenkostenabrechnung, Arbeitsnachweise): Schrift eingebettet, Mehrseitigkeit, keine stille Zeichenersetzung.
require dirname(__DIR__).'/private/pdf_document.php';
$n=0;function pd(bool $ok,string $s):void{global $n;$n++;if(!$ok)throw new RuntimeException($s);}
$pdf=new GszPdfDocument();$pdf->documentLabel='NEBENKOSTENABRECHNUNG';$pdf->documentNumber='2026';$pdf->AddPage();$pdf->paragraph('Synthetisches Haus – Größe 19 € für Gütersloh');$bytes=$pdf->Output('S');
pd(str_starts_with($bytes,'%PDF-'),'PDF header');pd(str_contains($bytes,'/FontFile2'),'Unicode font embedded');
$long=new GszPdfDocument();$long->AddPage();for($i=1;$i<=80;$i++)$long->paragraph('ZEILE-'.$i.' '.str_repeat('Lange Beschreibung mit Umlaut ü ',8));$out=$long->Output('S');preg_match_all('~/Type /Page\b~',$out,$m);pd(count($m[0])>=3,'paginated');
if(getenv('GSZ_TEST_PDF_PATH'))file_put_contents(getenv('GSZ_TEST_PDF_PATH'),$out);
$blocked=false;try{$x=new GszPdfDocument();$x->AddPage();$x->paragraph("\u{1F984}");}catch(RuntimeException $e){$blocked=true;}pd($blocked,'unsupported glyph is rejected, not replaced');
echo "PDF document tests passed ($n assertions).\n";
