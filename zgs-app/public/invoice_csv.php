<?php
declare(strict_types=1);
require __DIR__.'/bootstrap.php';if(empty($_SESSION['admin_id'])){http_response_code(403);exit('Nicht angemeldet');}
require_once dirname(__DIR__).'/private/invoice_lib.php';
header('Content-Type: text/csv; charset=UTF-8');header('Content-Disposition: attachment; filename="gsz-rechnungen-export.csv"');
$out=fopen('php://output','w');fwrite($out,"\xEF\xBB\xBF");
$columns=['Nummer','Status','Rechnungsdatum','Leistungsdatum','Fällig','Kunde','Adresse','Netto','Umsatzsteuer','Brutto','Zahlungsstatus bestaetigt am UTC','Storniert am'];
fwrite($out,implode(';',array_map('invCsvCell',$columns))."\r\n");
$s=$pdo->query("SELECT number,status,invoice_date,service_date,due_date,recipient_name,recipient_address,net_total,tax_total,gross_total,paid_at,cancelled_at FROM invoices WHERE status<>'draft' AND document_kind<>'offer' ORDER BY id");
while($row=$s->fetch())fwrite($out,implode(';',array_map('invCsvCell',array_values($row)))."\r\n");
fclose($out);
