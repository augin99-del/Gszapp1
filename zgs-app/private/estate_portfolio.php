<?php
declare(strict_types=1);
/**
 * Immobilien-Übersicht (11/2026): rein lesende Auswertung der vorhandenen Immobilien-Daten, um das Haus herum.
 * Portfolio-Kennzahlen, Aufgaben/Fristen je Haus, Mietkonto-Raster (Mieter × Monate), Kosten nach Kategorie.
 * Schreibt nichts – alle Änderungen laufen weiter über die bestehenden, geprüften Wege (estate.php, estate_bank.php, Nebenkosten).
 */
require_once __DIR__.'/estate_rent_match.php';require_once __DIR__.'/estate_ui.php';require_once __DIR__.'/day_view.php';

function estatePortfolioMoney(int $c):string {return number_format($c/100,$c%100?2:0,',','.').' €';}
/** Aktive Mietverhältnisse (Mieter) eines Hauses an einem Tag, mit aktuellem Mietbetrag. */
function estateActiveLeases(PDO $pdo,int $building,string $day):array {
 $q=$pdo->prepare("SELECT l.*,u.label unit_label,u.area_units,t.name tenant_name,t.phone tenant_phone,t.email tenant_email,
  (SELECT rent_cents+operating_cents+heating_cents FROM estate_terms x WHERE x.lease_id=l.id AND x.valid_from<=? ORDER BY x.valid_from DESC,x.id DESC LIMIT 1) monthly_cents,
  (SELECT rent_cents FROM estate_terms x WHERE x.lease_id=l.id AND x.valid_from<=? ORDER BY x.valid_from DESC,x.id DESC LIMIT 1) rent_cents,
  (SELECT MAX(valid_from) FROM estate_terms x WHERE x.lease_id=l.id AND x.valid_from<=?) terms_since,
  (SELECT COALESCE(SUM(a.amount_cents),0) FROM estate_allocations a WHERE a.lease_id=l.id AND a.component='deposit') deposit_paid_cents
  FROM estate_leases l JOIN estate_units u ON u.id=l.unit_id JOIN estate_tenants t ON t.id=l.tenant_id
  WHERE u.building_id=? AND l.starts_on<=? AND (l.ends_on IS NULL OR l.ends_on>=?) ORDER BY u.label,l.id");
 $q->execute([$day,$day,$day,$building,$day,$day]);return $q->fetchAll(PDO::FETCH_ASSOC);
}
/** Wohnungen eines Hauses mit dem heute gültigen Mietverhältnis (oder leer). */
function estateUnitsWithLease(PDO $pdo,int $building,string $day):array {
 $q=$pdo->prepare('SELECT * FROM estate_units WHERE building_id=? ORDER BY label,id');$q->execute([$building]);$units=$q->fetchAll(PDO::FETCH_ASSOC);
 $by=[];foreach(estateActiveLeases($pdo,$building,$day) as $l)$by[(int)$l['unit_id']]=$l;
 foreach($units as &$u){$u['lease']=$by[(int)$u['id']]??null;}unset($u);return $units;
}
/** Kosten (bestätigte Bankausgaben) eines Hauses je Kategorie im Zeitraum. */
function estateCostsByCategory(PDO $pdo,int $building,string $from,string $to):array {
 $q=$pdo->prepare('SELECT e.category,SUM(-t.amount_cents) cents,COUNT(*) n FROM estate_expenses e JOIN estate_bank_transactions t ON t.id=e.transaction_id WHERE e.building_id=? AND t.booked_on BETWEEN ? AND ? GROUP BY e.category ORDER BY cents DESC');
 $q->execute([$building,$from,$to]);return $q->fetchAll(PDO::FETCH_ASSOC);
}
function estateCostList(PDO $pdo,int $building,string $from,string $to,int $limit=200):array {
 $q=$pdo->prepare('SELECT e.id,e.category,t.booked_on,t.counterparty,t.purpose,-t.amount_cents cents FROM estate_expenses e JOIN estate_bank_transactions t ON t.id=e.transaction_id WHERE e.building_id=? AND t.booked_on BETWEEN ? AND ? ORDER BY t.booked_on DESC,e.id DESC LIMIT '.max(1,min(500,$limit)));
 $q->execute([$building,$from,$to]);return $q->fetchAll(PDO::FETCH_ASSOC);
}
/** Kennzahlen eines Hauses: Monat (Soll/Ist/Offen), Belegung, Jahr (Einnahmen/Kosten/Überschuss). */
function estateBuildingStats(PDO $pdo,array $b,string $month,?DateTimeImmutable $today=null):array {
 $today??=estateNow();$day=$today->format('Y-m-d');$year=$today->format('Y');$id=(int)$b['id'];
 $rows=estateMonthOverview($pdo,$id,$month);$due=0;$paid=0;$open=0;$late=0;$noCharge=0;
 foreach($rows as $r){if($r['due_cents']!==null){$due+=(int)$r['due_cents'];$paid+=min((int)$r['paid_cents'],(int)$r['due_cents']);}if(in_array($r['status'],['missing','partial'],true)){$open+=(int)$r['open_cents'];$late++;}if($r['status']==='no_charge')$noCharge++;}
 $units=estateUnitsWithLease($pdo,$id,$day);$vacant=count(array_filter($units,fn($u)=>!$u['lease']||$u['lease']['occupancy_kind']!=='tenant'));
 $fin=['income_cents'=>0,'expense_cents'=>0,'cash_surplus_cents'=>0];foreach(estateFinancialOverview($pdo,$year.'-01-01',$day) as $f)if((int)$f['building_id']===$id)$fin=$f;
 $status=$late?'red':($noCharge?'amber':'green');
 return ['rows'=>$rows,'due'=>$due,'paid'=>$paid,'open'=>$open,'late'=>$late,'no_charge'=>$noCharge,'units'=>count($units),'vacant'=>$vacant,'income_year'=>(int)$fin['income_cents'],'expense_year'=>(int)$fin['expense_cents'],'surplus_year'=>(int)$fin['cash_surplus_cents'],'status'=>$status];
}
/** Aufgaben und Fristen eines Hauses – nur Dinge, die eine Entscheidung auslösen. Jeweils [Dringlichkeit, Text, Link]. */
function estateBuildingTasks(PDO $pdo,array $b,array $stats,?DateTimeImmutable $today=null):array {
 $today??=estateNow();$day=$today->format('Y-m-d');$id=(int)$b['id'];$t=[];$m=GSZ_MONTHS[(int)$today->format('n')];
 // Konto, Kontoauszug, offene Buchungen
 $acc=null;foreach(estateAccounts($pdo) as $a)if((int)$a['building_id']===$id)$acc=$a;
 if(!$acc)$t[]=['amber','Kein Bankkonto zugeordnet – Mieten können nicht automatisch erkannt werden','estate_bank.php#konten'];
 else{
  if((int)$today->format('j')>=10&&(!$acc['last_upload_at']||substr((string)$acc['last_upload_at'],0,7)<$today->format('Y-m'))) $t[]=['amber','Kontoauszug für '.$m.' hochladen','estate_bank.php?account='.(int)$acc['id'].'#hochladen'];
  try{$n=count(estateRentProposals($pdo,(int)$acc['id']))+count(estateExpenseProposals($pdo,(int)$acc['id']));if($n)$t[]=['amber',$n===1?'1 Buchung zuordnen':$n.' Buchungen zuordnen','estate_bank.php#eingaenge'];}catch(Throwable $e){}
 }
 if($stats['late'])$t[]=['red',$stats['late']===1?'1 Miete fehlt oder ist unvollständig ('.estatePortfolioMoney($stats['open']).')':$stats['late'].' Mieten fehlen oder sind unvollständig ('.estatePortfolioMoney($stats['open']).')','haus.php?id='.$id.'&tab=mietkonto'];
 if($stats['no_charge'])$t[]=['amber','Sollmieten für '.$m.' anlegen','estate_bank.php'];
 // Nebenkostenabrechnung Vorjahr: Frist 12 Monate nach Ende des Abrechnungszeitraums (Kalenderjahr → 31.12. des Folgejahres)
 $py=(int)$today->format('Y')-1;
 try{$q=$pdo->prepare('SELECT COUNT(*) FROM estate_settlements WHERE building_id=? AND period_from<=? AND period_to>=?');$q->execute([$id,$py.'-01-01',$py.'-12-31']);
  if(!(int)$q->fetchColumn()&&$stats['units']){$left=(int)$today->diff(new DateTimeImmutable($today->format('Y').'-12-31'))->format('%a');$t[]=[$left<=60?'red':'info','Nebenkostenabrechnung '.$py.' erstellen – Frist 31.12.'.$today->format('Y').' ('.$left.' Tage)','estate_settlements.php?building='.$id.'&from='.$py.'-01-01&to='.$py.'-12-31'];}}catch(Throwable $e){}
 $stale=[];foreach(estateActiveLeases($pdo,$id,$day) as $l){
  if($l['occupancy_kind']!=='tenant')continue;
  if($l['ends_on']&&$l['ends_on']<=$today->modify('+90 days')->format('Y-m-d'))$t[]=['amber','Mietvertrag '.$l['tenant_name'].' ('.$l['unit_label'].') endet am '.(new DateTimeImmutable($l['ends_on']))->format('d.m.Y').' – Übergabe und Kaution planen','estate.php?view=lease&id='.(int)$l['id']];
  if((int)$l['deposit_cents']>0&&(int)$l['deposit_paid_cents']<(int)$l['deposit_cents'])$t[]=['info','Kaution '.$l['tenant_name'].': '.estatePortfolioMoney((int)$l['deposit_paid_cents']).' von '.estatePortfolioMoney((int)$l['deposit_cents']).' eingegangen','estate.php?view=payment'];
  if($l['terms_since']&&$l['terms_since']<=$today->modify('-15 months')->format('Y-m-d'))$stale[]=$l;
 }
 if(count($stale)===1)$t[]=['info','Miete '.$stale[0]['tenant_name'].' seit '.(new DateTimeImmutable($stale[0]['terms_since']))->format('m/Y').' unverändert – Mieterhöhung prüfen','estate.php?view=lease&id='.(int)$stale[0]['id']];
 elseif($stale)$t[]=['info','Miete bei '.count($stale).' Mietern seit über 15 Monaten unverändert – Mieterhöhung prüfen','haus.php?id='.$id.'&tab=wohnungen'];
 $order=['red'=>0,'amber'=>1,'info'=>2];usort($t,fn($a,$b)=>$order[$a[0]]<=>$order[$b[0]]);return $t;
}
/** Mietkonto-Raster eines Jahres: je Mietverhältnis 12 Monate mit Status (complete|partial|missing|not_due|no_charge|none). */
function estateRentGrid(PDO $pdo,int $building,int $year,?DateTimeImmutable $today=null):array {
 $today??=estateNow();$todayS=$today->format('Y-m-d');
 $q=$pdo->prepare("SELECT l.id,l.starts_on,l.ends_on,l.due_day,u.label unit_label,t.name tenant_name FROM estate_leases l JOIN estate_units u ON u.id=l.unit_id JOIN estate_tenants t ON t.id=l.tenant_id WHERE u.building_id=? AND l.occupancy_kind='tenant' AND l.starts_on<=? AND (l.ends_on IS NULL OR l.ends_on>=?) ORDER BY u.label,l.starts_on");
 $q->execute([$building,$year.'-12-31',$year.'-01-01']);$leases=$q->fetchAll(PDO::FETCH_ASSOC);
 $cells=[];$sum=array_fill(1,12,['due'=>0,'paid'=>0]);
 for($m=1;$m<=12;$m++){$month=sprintf('%04d-%02d-01',$year,$m);$end=(new DateTimeImmutable($month))->format('Y-m-t');
  $led=[];foreach(estateRentLedger($pdo,$month,$building) as $c)$led[(int)$c['lease_id']]=$c;
  foreach($leases as $l){$lid=(int)$l['id'];
   if($l['starts_on']>$end||($l['ends_on']&&$l['ends_on']<$month)){$cells[$lid][$m]=['s'=>'none'];continue;}
   $c=$led[$lid]??null;
   if(!$c){$cells[$lid][$m]=['s'=>$month>$todayS?'future':'no_charge'];continue;}
   $due=(int)$c['due_cents'];$paid=(int)$c['paid_cents'];$open=(int)$c['open_cents'];$sum[$m]['due']+=$due;$sum[$m]['paid']+=min($paid,$due);
   $s=$due<=0||$open<=0?'complete':($paid>0?'partial':($c['due_on']>$todayS?'not_due':'missing'));
   $cells[$lid][$m]=['s'=>$s,'due'=>$due,'paid'=>$paid,'open'=>$open];}
 }
 return ['leases'=>$leases,'cells'=>$cells,'sum'=>$sum];
}
