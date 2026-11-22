<?php
declare(strict_types=1);
/** Shared money helpers (integer cents). Used by real estate, imports and exports. */
function financeCents(string $raw,string $locale='iso'):int {
 $raw=trim($raw);if($locale==='de'){if(!preg_match('/^[+-]?(?:\d{1,9}|\d{1,3}(?:\.\d{3}){1,2})(?:,\d{1,2})?$/D',$raw))throw new InvalidArgumentException('Deutscher Betrag ungültig.');$raw=str_replace(['.',','],['','.'],$raw);}elseif($locale!=='iso')throw new InvalidArgumentException('Zahlenformat unbekannt.');
 if(!preg_match('/^([+-]?)(\d{1,9})(?:\.(\d{1,2}))?$/D',$raw,$m))throw new InvalidArgumentException('Betrag benötigt maximal zwei Dezimalstellen.');$value=(int)$m[2]*100+(int)str_pad($m[3]??'',2,'0');return ($m[1]==='-'?-1:1)*$value;
}
function financeDecimal(int $cents):string{return ($cents<0?'-':'').intdiv(abs($cents),100).'.'.str_pad((string)(abs($cents)%100),2,'0',STR_PAD_LEFT);}
/** Display format for people: 1.234,56 € */
function moneyDisplay(int $cents):string{return ($cents<0?'−':'').number_format(abs($cents)/100,2,',','.').' €';}
