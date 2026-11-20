<?php
declare(strict_types=1);
function invMoney(string $raw, int $scale=2): int {
 $raw=str_replace(',','.',trim($raw));
 if(!preg_match('/^\d{1,9}(?:\.\d{1,4})?$/D',$raw)) throw new InvalidArgumentException('Ungültiger Geldbetrag.');
 [$whole,$fraction]=array_pad(explode('.',$raw,2),2,'');
 $fraction=str_pad($fraction,4,'0');
 $scaled=(int)$whole*10000+(int)$fraction;
 return intdiv($scaled+50,100);
}
function invDecimal(string $raw, int $scale=3): int {
 $raw=str_replace(',','.',trim($raw));
 if(!preg_match('/^\d{1,7}(?:\.\d{1,3})?$/D',$raw)) throw new InvalidArgumentException('Ungültige Menge.');
 [$whole,$fraction]=array_pad(explode('.',$raw,2),2,'');
 return (int)$whole*1000+(int)str_pad($fraction,3,'0');
}
function invFormat(int $cents): string {return number_format($cents/100,2,',','.');}
function invEvent(PDO $pdo,int $id,string $type,string $detail=''): void {
 $s=$pdo->prepare('INSERT INTO invoice_events(invoice_id,admin_id,event_type,detail) VALUES(?,?,?,?)');
 $s->execute([$id,$_SESSION['admin_id']??null,$type,$detail]);
}
function invGet(PDO $pdo,int $id): array {
 $s=$pdo->prepare('SELECT * FROM invoices WHERE id=?');$s->execute([$id]);
 $inv=$s->fetch();if(!$inv)throw new RuntimeException('Rechnung nicht gefunden.');return $inv;
}
function invItems(PDO $pdo,int $id): array {
 $s=$pdo->prepare('SELECT * FROM invoice_items WHERE invoice_id=? ORDER BY position');$s->execute([$id]);return $s->fetchAll();
}
function invRecalc(PDO $pdo,int $id): void {
 $s=$pdo->prepare('SELECT COALESCE(SUM(net_amount),0) n,COALESCE(SUM(tax_amount),0) t,COALESCE(SUM(gross_amount),0) g FROM invoice_items WHERE invoice_id=?');$s->execute([$id]);$t=$s->fetch();
 $pdo->prepare('UPDATE invoices SET net_total=?,tax_total=?,gross_total=? WHERE id=?')->execute([$t['n'],$t['t'],$t['g'],$id]);
}
function invXml(string $v): string {return htmlspecialchars($v,ENT_XML1|ENT_QUOTES,'UTF-8');}
function invCsvCell(mixed $v): string {
 $s=(string)$v;if(preg_match('/^[=+@\-\t\r]/u',$s))$s="'".$s;
 return '"'.str_replace('"','""',$s).'"';
}
