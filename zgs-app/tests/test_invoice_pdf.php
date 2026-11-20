<?php
declare(strict_types=1);
require dirname(__DIR__).'/private/invoice_pdf.php';
$invoice=['number'=>'TEST-001','invoice_date'=>'2026-11-03','issuer_name'=>'GSZ','issuer_address'=>'Testweg 1','recipient_name'=>'Testkunde','recipient_address'=>'Testweg 2','service_date'=>'2026-11-03','due_date'=>'2026-11-17','net_total'=>'100.00','tax_total'=>'19.00','gross_total'=>'119.00','issuer_iban'=>'DE000','issuer_bic'=>'TEST','issuer_tax_id'=>'1','issuer_vat_id'=>'','notes'=>''];
$item=['position'=>1,'description'=>'Testleistung für Gütersloh – Größe 19 €','quantity'=>'1','unit'=>'Stk','unit_price'=>'100.00','tax_rate'=>'19','gross_amount'=>'119.00'];
$pdf=invoicePdf($invoice,[$item]);if(!str_starts_with($pdf,'%PDF-'))throw new RuntimeException('PDF header missing');if(!str_contains($pdf,'/FontFile2'))throw new RuntimeException('Unicode font not embedded');
$items=[];for($i=1;$i<=80;$i++)$items[]=array_replace($item,['position'=>$i,'description'=>'POSITION-'.$i.' '.str_repeat('Lange Beschreibung mit Umlaut ü ',8)]);$long=invoicePdf($invoice,$items);preg_match_all('~/Type /Page\b~',$long,$matches);if(count($matches[0])<3)throw new RuntimeException('Long invoice not paginated');
if(getenv('GSZ_TEST_PDF_PATH'))file_put_contents(getenv('GSZ_TEST_PDF_PATH'),$long);
$failed=false;try{invoicePdf($invoice,array_fill(0,501,$item));}catch(RuntimeException $e){$failed=true;}if(!$failed)throw new RuntimeException('Resource limit not enforced');
$unsupported=false;try{invoicePdf(array_replace($invoice,['recipient_name'=>"\u{1F984}"]),[$item]);}catch(RuntimeException $e){$unsupported=true;}if(!$unsupported)throw new RuntimeException('Unsupported glyph silently replaced');
echo "Invoice PDF tests passed (header, embedded font, 80 positions/multiple pages, size and unsupported-glyph limits).\n";
