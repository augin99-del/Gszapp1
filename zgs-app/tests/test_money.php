<?php
declare(strict_types=1);
require dirname(__DIR__).'/private/money.php';require dirname(__DIR__).'/private/export_core.php';
$n=0;function mo(bool $ok,string $s):void{global $n;$n++;if(!$ok)throw new RuntimeException($s);}
foreach([['1234.56','iso',123456],['-1.234,56','de',-123456],['+0,01','de',1],['0.01','iso',1],['-19,00','de',-1900],['12,3','de',1230]] as [$raw,$loc,$exp])mo(financeCents($raw,$loc)===$exp,'cents '.$raw);
foreach(['1,2,3','abc','1.0001','1,234'] as $bad){$x=false;try{financeCents($bad,'de');}catch(InvalidArgumentException $e){$x=true;}mo($x,'reject '.$bad);}
mo(financeDecimal(-1900)==='-19.00','decimal');mo(moneyDisplay(123456)==='1.234,56 €','display');mo(moneyDisplay(-5)==='−0,05 €','negative display');
mo(gszCsvCell('=SUM(A1)')==="\"'=SUM(A1)\"",'csv formula injection neutralised');mo(str_starts_with(gszCsv(['a'],[['b']]),"\xEF\xBB\xBF"),'csv bom');
$zip=gszZip(['a.txt'=>'x']);mo(str_starts_with($zip,"PK\x03\x04"),'zip header');$x=false;try{gszZip(['../a'=>'x']);}catch(RuntimeException $e){$x=true;}mo($x,'zip path traversal');
echo "Money/CSV/ZIP tests passed ($n assertions).\n";
