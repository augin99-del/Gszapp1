<?php
declare(strict_types=1);
$dsn=getenv('GSZ_TEST_DSN')?:'';if(!preg_match('/^mysql:unix_socket=[^;]+;dbname=gsz_test_cashassets_[a-z0-9_]+$/D',$dsn))throw new RuntimeException('Local synthetic cashassets test database required.');
require __DIR__.'/prepare_gesamt_browser.php';
require_once dirname(__DIR__).'/private/finance_service.php';
$pdo->exec(file_get_contents(dirname(__DIR__).'/private/migration_v300.sql'));
$receipt=financeArchive($pdo,'Synthetic cash/assets browser original','synthetic-cash-assets.txt');financeSaveReceipt($pdo,1,$receipt,['merchant'=>'Synthetic cash shop','receipt_date'=>'2026-11-02','direction'=>'expense','gross'=>'99,00','tax_rate'=>19,'category'=>'Synthetic test only','payment_method'=>'cash'],true);$payment=financePay($pdo,1,'receipt',$receipt,-9900,'2026-11-02',hash('sha256','synthetic-cash-browser-payment'));
echo "Synthetic cash/assets fixture ready; one expense payment, no initial cash balance.\n";
