<?php
declare(strict_types=1);
require_once __DIR__.'/invoice_lib.php';
require_once __DIR__.'/invoice_pdf.php';
require_once __DIR__.'/platform.php';
/** Capture only inside the issuance transaction. Files are immutable through the app.
 * An aborted transaction may leave an unreferenced hash file; never delete automatically.
 * This is a technical original, not a GoBD/WORM/PDF-A certification.
 */
function invoiceArchive(PDO $pdo,int $id):string {
 if(!$pdo->inTransaction())throw new RuntimeException('PDF-Ablage benötigt Ausstellungstransaktion.');
 $q=$pdo->prepare('SELECT * FROM invoices WHERE id=? FOR UPDATE');$q->execute([$id]);$invoice=$q->fetch();
 if(!$invoice||!in_array($invoice['status'],['issued','paid','cancelled'],true)||empty($invoice['number']))throw new RuntimeException('Nur ausgestellte Dokumente archivieren.');
 if(!empty($invoice['archived_pdf_sha256']))return invoiceDocumentPdf($invoice,[]);
 $bytes=invoicePdf($invoice,invItems($pdo,$id));$stored=gszStoreBytes('invoices',$bytes);
 $pdo->prepare('UPDATE invoices SET archived_pdf_sha256=?,archived_pdf_size=?,archived_pdf_at=UTC_TIMESTAMP() WHERE id=? AND archived_pdf_sha256 IS NULL')->execute([$stored['sha256'],$stored['size_bytes'],$id]);
 return $bytes;
}
/** Existing historical documents without snapshots remain explicitly regenerated. */
function invoiceDocumentPdf(array $invoice,array $items):string {
 $hash=(string)($invoice['archived_pdf_sha256']??'');
 if($hash==='')return invoicePdf($invoice,$items);
 $bytes=gszReadBytes('invoices',$hash,$hash);
 if(strlen($bytes)!==(int)$invoice['archived_pdf_size']||!str_starts_with($bytes,'%PDF-'))throw new RuntimeException('Archiviertes PDF ist beschädigt. Kein stiller Ersatz.');
 return $bytes;
}
function invoiceArchiveLabel(array $invoice):string {
 return !empty($invoice['archived_pdf_sha256'])?'Archiviertes PDF der Ausstellung (SHA-256 geprüft)':'Historischer Bestand ohne PDF-Original: technisch neu erzeugte Darstellung';
}
