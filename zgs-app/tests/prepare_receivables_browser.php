<?php
declare(strict_types=1);
require __DIR__.'/prepare_gesamt_browser.php';
require_once dirname(__DIR__).'/private/finance_service.php';
$pdo->exec("INSERT INTO invoices(customer_id,number,status,invoice_date,due_date,recipient_name,recipient_address,issuer_name,issuer_address,issuer_iban,net_total,tax_total,gross_total) VALUES(1,'SYNTHETIC-REMINDER-001','issued','2026-10-01','2026-11-01','Synthetic customer','Test address','Synthetic issuer','Test address','TEST',100,19,119)");$id=(int)$pdo->lastInsertId();
$pdo->prepare("INSERT INTO invoice_items(invoice_id,position,description,quantity,unit_price,tax_rate,net_amount,tax_amount,gross_amount) VALUES(?,1,'Synthetic service',1,100,19,100,19,119)")->execute([$id]);financePay($pdo,1,'invoice',$id,1900,'2026-11-02',hash('sha256','synthetic-browser-part'));echo "Synthetic open invoice fixture ready (100.00 EUR outstanding).\n";
