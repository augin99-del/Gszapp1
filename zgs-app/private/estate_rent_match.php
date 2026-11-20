<?php
declare(strict_types=1);
/**
 * Mietabgleich für die zwei Volksbank-Vermieterkonten. Konto = Haus.
 * Vorschläge sind lokal berechnet und werden NUR nach Tippen des Inhabers gebucht (keine stille Automatik).
 */
require_once __DIR__.'/estate_bank.php';require_once __DIR__.'/estate_documents.php';
function estateNow():DateTimeImmutable {return new DateTimeImmutable('today',new DateTimeZone('Europe/Berlin'));}
/** Konto ↔ Haus. Ein Haus gehört zu höchstens einem Konto (UNIQUE). */
function estateLinkAccount(PDO $pdo,int $admin,int $account,int $building):void {
 if(!in_array($account,[1,2],true))throw new InvalidArgumentException('Konto 1 oder 2 wählen.');
 estateTx($pdo,$admin,function()use($pdo,$admin,$account,$building){$old=estateLock($pdo,'estate_bank_accounts',$account);if($building){estateLock($pdo,'estate_buildings',$building);$q=$pdo->prepare('SELECT id FROM estate_bank_accounts WHERE building_id=? AND id<>?');$q->execute([$building,$account]);if($q->fetchColumn())throw new RuntimeException('Dieses Haus ist bereits dem anderen Konto zugeordnet.');}
  $pdo->prepare('UPDATE estate_bank_accounts SET building_id=?,row_version=row_version+1 WHERE id=?')->execute([$building?:null,$account]);estateEvent($pdo,$admin,'account',$account,'account_building',['building_id'=>$old['building_id']??null],['building_id'=>$building?:null]);});
}
/** Zuordnungshilfen je Mieter: abweichende Namen von Zahlern (z. B. Partner, Jobcenter) und optional die IBAN (nur als Hash gespeichert). */
function estateSaveTenantHints(PDO $pdo,int $admin,int $tenant,string $names,string $iban):void {
 $names=trim(preg_replace('/\s*\n\s*/',', ',$names));if(mb_strlen($names)>500)throw new InvalidArgumentException('Zahlernamen maximal 500 Zeichen.');$hash=null;$iban=trim($iban);if($iban!=='')$hash=hash('sha256',bankIban($iban));
 estateTx($pdo,$admin,function()use($pdo,$admin,$tenant,$names,$hash,$iban){$old=estateLock($pdo,'estate_tenants',$tenant);$keep=$iban===''&&!empty($old['iban_hash']);$pdo->prepare('UPDATE estate_tenants SET payer_names=?,iban_hash=?,row_version=row_version+1 WHERE id=?')->execute([$names,$keep?$old['iban_hash']:$hash,$tenant]);estateEvent($pdo,$admin,'tenant',$tenant,'payment_hints',['payer_names'=>$old['payer_names']??''],['payer_names'=>$names,'iban_saved'=>$keep||$hash!==null]);});
}
function estateClearTenantIban(PDO $pdo,int $admin,int $tenant):void {estateTx($pdo,$admin,function()use($pdo,$admin,$tenant){estateLock($pdo,'estate_tenants',$tenant);$pdo->prepare('UPDATE estate_tenants SET iban_hash=NULL,row_version=row_version+1 WHERE id=?')->execute([$tenant]);estateEvent($pdo,$admin,'tenant',$tenant,'payment_iban_removed',[],[]);});}
function estateAccounts(PDO $pdo):array {return $pdo->query('SELECT a.*,b.name building_name,(SELECT MAX(uploaded_at) FROM estate_bank_uploads u WHERE u.account_id=a.id) last_upload_at,(SELECT MAX(period_to) FROM estate_bank_uploads u WHERE u.account_id=a.id) covered_until FROM estate_bank_accounts a LEFT JOIN estate_buildings b ON b.id=a.building_id ORDER BY a.id')->fetchAll(PDO::FETCH_ASSOC);}
function estateAccountLabel(array $a):string {return $a['building_name']?:$a['alias'];}

/** Monatsübersicht je Haus: jede aktive Mietpartei mit Soll, Ist, Zahlungsdaten und Status. */
function estateMonthOverview(PDO $pdo,int $building,string $month):array {
 $month=estateMonth($month);$end=(new DateTimeImmutable($month))->format('Y-m-t');$today=estateNow()->format('Y-m-d');
 $q=$pdo->prepare("SELECT l.id lease_id,l.due_day,u.label unit_label,t.id tenant_id,t.name tenant_name FROM estate_leases l JOIN estate_units u ON u.id=l.unit_id JOIN estate_tenants t ON t.id=l.tenant_id WHERE u.building_id=? AND l.occupancy_kind='tenant' AND l.starts_on<=? AND (l.ends_on IS NULL OR l.ends_on>=?) ORDER BY u.label,l.id");$q->execute([$building,$end,$month]);$leases=$q->fetchAll(PDO::FETCH_ASSOC);
 $ledger=[];foreach(estateRentLedger($pdo,$month,$building) as $c)$ledger[(int)$c['lease_id']]=$c;
 $pay=$pdo->prepare('SELECT p.paid_on,SUM(a.amount_cents) amount FROM estate_allocations a JOIN estate_payments p ON p.id=a.payment_id WHERE a.charge_id=? GROUP BY p.id,p.paid_on ORDER BY p.paid_on');
 $rows=[];foreach($leases as $l){$c=$ledger[(int)$l['lease_id']]??null;$r=$l+['charge_id'=>null,'due_cents'=>null,'paid_cents'=>0,'open_cents'=>null,'due_on'=>null,'payments'=>[]];
  if(!$c){$r['status']='no_charge';$rows[]=$r;continue;}
  $r['charge_id']=(int)$c['id'];$r['due_cents']=(int)$c['due_cents'];$r['paid_cents']=(int)$c['paid_cents'];$r['open_cents']=(int)$c['open_cents'];$r['due_on']=$c['due_on'];$pay->execute([$c['id']]);$r['payments']=$pay->fetchAll(PDO::FETCH_ASSOC);
  $r['status']=$r['due_cents']<=0||$r['open_cents']<=0?'complete':($r['paid_cents']>0?'partial':($c['due_on']>$today?'not_due':'missing'));$rows[]=$r;}
 return $rows;
}
/** Sollmieten des Monats für ein Haus anlegen (bestehende Vorschau/Übernahme-Logik, ein Tipp). */
function estateCreateMonthCharges(PDO $pdo,int $admin,int $building,string $month):int {return estateRentApply($pdo,$admin,estateRentPreview($pdo,$admin,$month,$building));}

function estateNameTokens(string $name):array {$name=mb_strtolower(str_replace(['ä','ö','ü','ß'],['ae','oe','ue','ss'],$name));$t=preg_split('/[^a-z0-9]+/',$name,-1,PREG_SPLIT_NO_EMPTY);return array_values(array_filter($t,fn($x)=>strlen($x)>=3&&!in_array($x,['und','von','der','die','das','herr','frau','familie','gbr','fam'],true)));}
function estateTextNorm(string $s):string {return ' '.mb_strtolower(str_replace(['ä','ö','ü','ß'],['ae','oe','ue','ss'],preg_replace('/\s+/u',' ',$s))).' ';}
/** Bewertung einer Zahlung gegen eine offene Sollmiete. Heuristik, keine Wahrscheinlichkeit. */
function estateScoreRent(array $t,array $c,array $tenant):array {
 $score=0;$why=[];$text=estateTextNorm($t['counterparty'].' '.$t['purpose']);$who=estateTextNorm((string)$t['counterparty']);
 if(!empty($tenant['iban_hash'])&&!empty($t['counterparty_iban_hash'])&&hash_equals($tenant['iban_hash'],$t['counterparty_iban_hash'])){$score+=60;$why[]='IBAN des Mieters';}
 $tokens=estateNameTokens((string)$tenant['name']);$hit=array_filter($tokens,fn($x)=>str_contains($text,$x));if($tokens&&count($hit)===count($tokens)){$score+=45;$why[]='Name';}elseif($hit){$score+=30;$why[]='Nachname';}
 foreach(array_filter(array_map('trim',explode(',',(string)($tenant['payer_names']??'')))) as $alt){$at=estateNameTokens($alt);if($at&&!array_filter($at,fn($x)=>!str_contains($who.' '.$text,$x))){$score+=45;$why[]='Zahlername „'.$alt.'“';break;}}
 if($c['unit_label']!==''&&mb_strlen($c['unit_label'])>=2&&str_contains($text,' '.mb_strtolower($c['unit_label']).' ')){$score+=10;$why[]='Wohnung im Text';}
 $amount=(int)$t['amount_cents'];$open=(int)$c['open_cents'];if($amount===$open){$score+=30;$why[]='Betrag passt genau';}elseif($amount<$open){$score+=5;$why[]='Teilbetrag';}else{$score+=0;$why[]='mehr als offen';}
 $m=(new DateTimeImmutable($c['month']));$monthNames=['januar','februar','maerz','april','mai','juni','juli','august','september','oktober','november','dezember'];if(str_contains($text,$m->format('m/Y'))||str_contains($text,$m->format('m/y').' ')||str_contains($text,$m->format('m.Y'))||str_contains($text,' '.$monthNames[(int)$m->format('n')-1].' ')){$score+=10;$why[]='Monat im Text';}
 if(str_contains($text,'miete')){$score+=5;}
 return ['score'=>min(100,$score),'reasons'=>$why];
}
/** Offene Eingänge eines Kontos mit dem besten Mieter-Vorschlag. sure=true nur bei eindeutigem, starkem Treffer. */
function estateRentProposals(PDO $pdo,int $account,int $limit=60):array {
 $q=$pdo->prepare('SELECT * FROM estate_bank_accounts WHERE id=?');$q->execute([$account]);$a=$q->fetch(PDO::FETCH_ASSOC);if(!$a||!$a['building_id'])return [];
 $q=$pdo->prepare('SELECT t.* FROM estate_bank_transactions t WHERE t.account_id=? AND t.amount_cents>0 AND NOT EXISTS(SELECT 1 FROM estate_payments p WHERE p.bank_transaction_id=t.id) AND NOT EXISTS(SELECT 1 FROM estate_transfers x WHERE x.incoming_id=t.id OR x.outgoing_id=t.id) ORDER BY t.booked_on DESC,t.id DESC LIMIT '.max(1,min(200,$limit)));$q->execute([$account]);$tx=$q->fetchAll(PDO::FETCH_ASSOC);if(!$tx)return [];
 $months=[];foreach($tx as $t){$m=new DateTimeImmutable(substr($t['booked_on'],0,7).'-01');foreach([-1,0,1] as $d)$months[$m->modify(($d>=0?'+':'').$d.' month')->format('Y-m-01')]=true;}
 $tenants=[];foreach($pdo->query('SELECT id,name,payer_names,iban_hash FROM estate_tenants')->fetchAll(PDO::FETCH_ASSOC) as $tn)$tenants[(int)$tn['id']]=$tn;
 $charges=[];$lease=$pdo->prepare('SELECT l.tenant_id,u.label FROM estate_leases l JOIN estate_units u ON u.id=l.unit_id WHERE l.id=?');
 foreach(array_keys($months) as $m)foreach(estateRentLedger($pdo,$m,(int)$a['building_id']) as $c){if((int)$c['open_cents']<=0||!empty($c['settled_period']))continue;$lease->execute([$c['lease_id']]);$l=$lease->fetch(PDO::FETCH_ASSOC);$c['tenant_id']=(int)$l['tenant_id'];$c['unit_label']=(string)$l['label'];$charges[]=$c;}
 $out=[];foreach($tx as $t){$best=[];foreach($charges as $c){$s=estateScoreRent($t,$c,$tenants[$c['tenant_id']]??['name'=>$c['tenant_name']]);$age=abs((new DateTimeImmutable($t['booked_on']))->diff(new DateTimeImmutable($c['due_on']))->days);$s['score']-=min(20,intdiv($age,7)*3);if($s['score']>=40)$best[]=['charge'=>$c,'score'=>$s['score'],'reasons'=>$s['reasons']];}
  usort($best,fn($x,$y)=>[$y['score'],$x['charge']['month']]<=>[$x['score'],$y['charge']['month']]);$top=$best[0]??null;$second=$best[1]??null;
  $out[]=['transaction'=>$t,'proposal'=>$top,'sure'=>$top&&$top['score']>=70&&in_array('Betrag passt genau',$top['reasons'],true)&&(!$second||$second['score']<$top['score']-15||(int)$second['charge']['lease_id']===(int)$top['charge']['lease_id']),'alternatives'=>array_slice($best,1,3)];}
 return $out;
}
/** Ein Tipp: Bankeingang als Mietzahlung erfassen und auf Kaltmiete, Betriebs- und Heizkosten der Sollmiete verteilen. */
function estateAssignRent(PDO $pdo,int $admin,int $transaction,int $charge):array {
 $q=$pdo->prepare('SELECT t.*,a.building_id FROM estate_bank_transactions t JOIN estate_bank_accounts a ON a.id=t.account_id WHERE t.id=?');$q->execute([$transaction]);$t=$q->fetch(PDO::FETCH_ASSOC);if(!$t||(int)$t['amount_cents']<=0)throw new RuntimeException('Nur Geldeingänge können als Miete zugeordnet werden.');
 $c=estateChargeBalance($pdo,$charge);$q=$pdo->prepare('SELECT u.building_id,l.id lease_id,tn.name FROM estate_leases l JOIN estate_units u ON u.id=l.unit_id JOIN estate_tenants tn ON tn.id=l.tenant_id WHERE l.id=?');$q->execute([$c['lease_id']]);$l=$q->fetch(PDO::FETCH_ASSOC);
 if(!$l||($t['building_id']&&(int)$t['building_id']!==(int)$l['building_id']))throw new RuntimeException('Diese Sollmiete gehört zu einem anderen Haus als das Konto.');
 $payment=estatePayment($pdo,$admin,$transaction,'',0,'Miete '.$l['name'].' '.substr($c['month'],0,7),hash('sha256','rent-payment|'.$transaction));
 $q=$pdo->prepare('SELECT COALESCE(SUM(amount_cents),0) FROM estate_allocations WHERE payment_id=?');$q->execute([$payment]);$left=(int)$t['amount_cents']-(int)$q->fetchColumn();$done=0;
 foreach(['rent','operating','heating'] as $k){if($left<=0)break;$c=estateChargeBalance($pdo,$charge);$part=min($left,$c['open'][$k]);if($part<=0)continue;estateAllocate($pdo,$admin,$payment,(int)$c['lease_id'],$charge,$k,$part,hash('sha256','rent-alloc|'.$transaction.'|'.$charge.'|'.$k));$left-=$part;$done+=$part;}
 return ['payment_id'=>$payment,'allocated_cents'=>$done,'rest_cents'=>$left];
}
const ESTATE_COST_CATEGORIES=['Grundsteuer'=>['grundsteuer','grundbesitzabgaben'],'Gebäudeversicherung'=>['versicherung','provinzial','allianz','huk','gebaeudevers','wohngebaeude'],'Wasser / Abwasser'=>['wasser','abwasser','stadtwerke'],'Müllabfuhr'=>['muell','abfall','entsorgung'],'Strom Allgemein'=>['strom','energie','eon','rwe','vattenfall'],'Heizung / Gas'=>['gas','heizoel','fernwaerme','waerme'],'Schornsteinfeger'=>['schornstein','kaminkehrer'],'Hausreinigung / Hausmeister'=>['reinigung','hausmeister','gebaeudeservice'],'Reparatur / Instandhaltung'=>['reparatur','handwerk','sanitaer','elektro','dach','maler','heizungsbau'],'Kredit / Zinsen'=>['darlehen','kredit','zins','tilgung'],'Bankgebühren'=>['entgelt','kontofuehrung','abschluss'],'Verwaltung'=>['verwaltung','hausverwaltung']];
/** Vorschläge für Ausgaben des Hauskontos. Kategorie aus früher bestätigter Ausgabe desselben Empfängers, sonst aus Stichworten. */
function estateExpenseProposals(PDO $pdo,int $account,int $limit=60):array {
 $q=$pdo->prepare('SELECT * FROM estate_bank_accounts WHERE id=?');$q->execute([$account]);$a=$q->fetch(PDO::FETCH_ASSOC);if(!$a)return [];
 $q=$pdo->prepare('SELECT t.* FROM estate_bank_transactions t WHERE t.account_id=? AND t.amount_cents<0 AND NOT EXISTS(SELECT 1 FROM estate_expenses e WHERE e.transaction_id=t.id) AND NOT EXISTS(SELECT 1 FROM estate_payments p WHERE p.bank_transaction_id=t.id) AND NOT EXISTS(SELECT 1 FROM estate_transfers x WHERE x.incoming_id=t.id OR x.outgoing_id=t.id) ORDER BY t.booked_on DESC,t.id DESC LIMIT '.max(1,min(200,$limit)));$q->execute([$account]);
 $prev=$pdo->prepare('SELECT e.category FROM estate_expenses e JOIN estate_bank_transactions t ON t.id=e.transaction_id WHERE t.counterparty=? ORDER BY e.id DESC LIMIT 1');$out=[];
 foreach($q->fetchAll(PDO::FETCH_ASSOC) as $t){$category='';$why='';if(trim($t['counterparty'])!==''){$prev->execute([$t['counterparty']]);$category=(string)$prev->fetchColumn();if($category!=='')$why='wie zuletzt bei diesem Empfänger';}
  if($category===''){$text=estateTextNorm($t['counterparty'].' '.$t['purpose']);foreach(ESTATE_COST_CATEGORIES as $cat=>$words)foreach($words as $w)if(str_contains($text,$w)){$category=$cat;$why='Stichwort „'.$w.'“';break 2;}}
  $out[]=['transaction'=>$t,'building_id'=>$a['building_id']?(int)$a['building_id']:null,'category'=>$category?:'Sonstige Kosten','reason'=>$why];}
 return $out;
}
function estateConfirmExpense(PDO $pdo,int $admin,int $transaction,string $category):int {
 $q=$pdo->prepare('SELECT a.building_id FROM estate_bank_transactions t JOIN estate_bank_accounts a ON a.id=t.account_id WHERE t.id=?');$q->execute([$transaction]);$building=(int)$q->fetchColumn();if(!$building)throw new RuntimeException('Bitte das Konto zuerst einem Haus zuordnen.');
 return estateExpense($pdo,$admin,$transaction,$building,0,$category,0,'Aus Kontoauszug bestätigt');
}
/** Hinweise für die Startseite: fehlender Monatsexport ab dem 10. und offene Mieten (nur entscheidungsrelevante Zahlen). */
function estateAttention(PDO $pdo,?DateTimeImmutable $today=null):array {
 $today??=estateNow();$out=['missing_uploads'=>[],'missing_rents'=>0,'charges_missing'=>[]];try{if(!(int)$pdo->query("SELECT COUNT(*) FROM app_migrations WHERE version='380_zentrale'")->fetchColumn())return $out;}catch(PDOException $e){return $out;}
 $first=$today->format('Y-m-01');$day=(int)$today->format('j');
 foreach(estateAccounts($pdo) as $a){if(!$a['building_id'])continue;if($day>=10){$q=$pdo->prepare('SELECT COUNT(*) FROM estate_bank_uploads WHERE account_id=? AND uploaded_on>=?');$q->execute([$a['id'],$first]);if(!(int)$q->fetchColumn())$out['missing_uploads'][]=['account_id'=>(int)$a['id'],'label'=>estateAccountLabel($a)];}
  foreach(estateMonthOverview($pdo,(int)$a['building_id'],$first) as $r){if($r['status']==='missing'||$r['status']==='partial')$out['missing_rents']++;if($r['status']==='no_charge'&&$day>=(int)$r['due_day'])$out['charges_missing'][(int)$a['building_id']]=estateAccountLabel($a);}}
 $out['charges_missing']=array_values($out['charges_missing']);return $out;
}
