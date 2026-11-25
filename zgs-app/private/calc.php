<?php
declare(strict_types=1);
/**
 * Preisrechner Treppenhausreinigung für die Website (Schema 430, Sätze in app_settings).
 * Der Rechner bleibt auf der Website ausgeblendet, bis Stundensatz und „Rechner an“ in der Zentrale eingetragen sind.
 * Ergebnis ist immer eine unverbindliche Spanne („ca. … bis … € im Monat“), nie ein Festpreis.
 */
require_once __DIR__.'/leads.php';
const GSZ_CALC_RHYTHM=['weekly'=>['wöchentlich',4.33],'biweekly'=>['alle 2 Wochen',2.17],'monthly'=>['monatlich',1.0]];
const GSZ_CALC_VAT=['zzgl'=>'zzgl. MwSt.','inkl'=>'inkl. MwSt.','ohne'=>''];
const GSZ_CALC_KEYS=['calc_rate','calc_base_min','calc_floor_min','calc_cellar_min','calc_elevator_min','calc_window_min','calc_min_visit','calc_range_pct'];
function gszCalcNum(string $v):?float {$v=str_replace(',','.',trim($v));return $v!==''&&is_numeric($v)&&(float)$v>=0?(float)$v:null;}
function gszCalcConfig(PDO $pdo):array {$c=[];foreach(GSZ_CALC_KEYS as $k)$c[substr($k,5)]=gszCalcNum(gszSetting($pdo,$k));$c['vat']=gszSetting($pdo,'calc_vat');$c['enabled']=gszSetting($pdo,'calc_enabled')==='1'&&($c['rate']??0)>0;return $c;}
function gszCalcSave(PDO $pdo,int $admin,array $in):void {
 foreach(GSZ_CALC_KEYS as $k){$v=trim((string)($in[$k]??''));if($v!==''&&(gszCalcNum($v)===null||gszCalcNum($v)>10000))throw new RuntimeException('Bitte nur Zahlen eintragen (z. B. 32,50).');}
 $rate=gszCalcNum((string)($in['calc_rate']??''));$on=!empty($in['calc_enabled']);if($on&&!$rate)throw new RuntimeException('Zum Einschalten bitte zuerst den Stundensatz eintragen.');
 if(($p=gszCalcNum((string)($in['calc_range_pct']??'10')))!==null&&$p>40)throw new RuntimeException('Spanne höchstens 40 %.');
 $vat=(string)($in['calc_vat']??'zzgl');if(!isset(GSZ_CALC_VAT[$vat]))throw new RuntimeException('MwSt.-Angabe ungültig.');
 foreach(GSZ_CALC_KEYS as $k)gszSettingSet($pdo,$k,str_replace(',','.',trim((string)($in[$k]??''))));gszSettingSet($pdo,'calc_vat',$vat);gszSettingSet($pdo,'calc_enabled',$on?'1':'0');
 gszAudit($pdo,$admin,'calc_saved',['enabled'=>$on]);
}
/** Spanne für die Website. null, solange der Rechner aus ist. */
function gszCalcQuote(PDO $pdo,array $in):?array {
 $c=gszCalcConfig($pdo);if(!$c['enabled'])return null;
 $floors=max(1,min(20,(int)($in['floors']??3)));$ent=max(1,min(10,(int)($in['entrances']??1)));$rh=isset(GSZ_CALC_RHYTHM[$in['rhythm']??''])?(string)$in['rhythm']:'weekly';
 $cellar=!empty($in['cellar'])&&($in['cellar']!=='0');$lift=!empty($in['elevator'])&&($in['elevator']!=='0');$win=!empty($in['windows'])&&($in['windows']!=='0');
 $min=($c['base_min']??0)+$ent*($floors*($c['floor_min']??0)+($cellar?($c['cellar_min']??0):0)+($lift?($c['elevator_min']??0):0)+($win?$floors*($c['window_min']??0):0));
 $visit=max($c['min_visit']??0,$min/60*$c['rate']);$month=$visit*GSZ_CALC_RHYTHM[$rh][1];$pct=($c['range_pct']??10)/100;
 $lo=max(5,5*floor($month*(1-$pct)/5));$hi=max($lo+5,5*ceil($month*(1+$pct)/5));$vat=GSZ_CALC_VAT[$c['vat']]??'';
 return ['low'=>(int)$lo,'high'=>(int)$hi,'minutes'=>(int)round($min),'rhythm'=>GSZ_CALC_RHYTHM[$rh][0],
  'text'=>'ca. '.number_format($lo,0,',','.').'–'.number_format($hi,0,',','.').' € im Monat'.($vat!==''?' '.$vat:''),
  'note'=>'Unverbindliche Schätzung für '.$ent.' Treppenhaus'.($ent>1?'äuser':'').' mit '.$floors.' Etage'.($floors>1?'n':'').', '.GSZ_CALC_RHYTHM[$rh][0].'. Den festen Preis nennen wir nach kurzer Besichtigung.'];
}
