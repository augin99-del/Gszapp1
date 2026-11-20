<?php
declare(strict_types=1);
require dirname(__DIR__).'/private/invoice_lib.php';
foreach (['12,34'=>1234,'0.01'=>1,'1.005'=>101,'1.004'=>100] as $raw=>$expected) {
 if(invMoney((string)$raw)!==$expected) throw new RuntimeException('Money calculation mismatch: '.$raw);
}
if(invDecimal('1,250')!==1250)throw new RuntimeException('Quantity mismatch');
echo "Money/CSV smoke tests passed\n";
