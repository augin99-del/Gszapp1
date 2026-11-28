<?php
declare(strict_types=1);
/**
 * Schema 450: Verträge je Objekt (Dienstleistungsvertrag Gebäudeservice).
 * Beim Anlegen eines Objekts entsteht automatisch ein Entwurf aus Kunde, Objekt und Regelaufgaben.
 * Die Vorlage ist ein Muster und keine Rechtsberatung – vor der ersten Verwendung prüfen lassen.
 */
require_once __DIR__.'/leads.php';require_once __DIR__.'/money.php';

const GSZ_CONTRACT_STATUS=['entwurf'=>['Entwurf','wait'],'versendet'=>['Versendet','part'],'aktiv'=>['Aktiv','ok'],'gekuendigt'=>['Gekündigt','bad'],'beendet'=>['Beendet','wait']];
const GSZ_CONTRACT_FREQ=['weekly'=>'wöchentlich','biweekly'=>'alle 2 Wochen','monthly'=>'monatlich','quarterly'=>'vierteljährlich','seasonal'=>'saisonal nach Bedarf','daily'=>'täglich'];
const GSZ_CONTRACT_PERIOD=['month'=>'monatlich (Pauschale)','visit'=>'je Einsatz','year'=>'jährlich'];
const GSZ_CONTRACT_VAT=['zzgl'=>'zzgl. gesetzlicher Umsatzsteuer','inkl'=>'inkl. gesetzlicher Umsatzsteuer','none'=>'gemäß § 19 UStG wird keine Umsatzsteuer berechnet (Kleinunternehmer)'];

function gszContractsReady(PDO $pdo):bool {try{return (bool)$pdo->query("SELECT COUNT(*) FROM app_migrations WHERE version='450_vertraege'")->fetchColumn();}catch(Throwable $e){return false;}}
function gszContractNo(array $c):string {return 'V-'.substr((string)($c['created_at']??date('Y')),0,4).'-'.str_pad((string)(int)$c['id'],4,'0',STR_PAD_LEFT);}
function gszContractGet(PDO $pdo,int $id):?array {$q=$pdo->prepare('SELECT * FROM property_contracts WHERE id=?');$q->execute([$id]);return $q->fetch(PDO::FETCH_ASSOC)?:null;}
function gszContractForProperty(PDO $pdo,int $property):?array {$q=$pdo->prepare('SELECT * FROM property_contracts WHERE property_id=? ORDER BY FIELD(status,\'aktiv\',\'versendet\',\'entwurf\',\'gekuendigt\',\'beendet\'),id DESC LIMIT 1');$q->execute([$property]);return $q->fetch(PDO::FETCH_ASSOC)?:null;}

/** Unternehmer oder Verbraucher? Nur ein Vorschlag aus dem Namen – im Formular ausdrücklich prüfen. */
function gszContractGuessKind(string $name):string {return preg_match('/\b(GmbH|mbH|AG|KG|UG|OHG|GbR|e\.\s?V\.|eG|WEG|Hausverwaltung|Verwaltung|Immobilien|Wohnungsbau|Genossenschaft|Gesellschaft|Stiftung|Kirchengemeinde|Stadt|Gemeinde)\b/iu',$name)?'business':'consumer';}
/** Leistungsverzeichnis aus den aktiven Regelaufgaben des Objekts. */
function gszContractServicesFromProperty(PDO $pdo,int $property):array {
 $q=$pdo->prepare('SELECT title,frequency,notes FROM recurring_jobs WHERE property_id=? AND active=1 ORDER BY id');$q->execute([$property]);$lines=[];$winter=false;
 foreach($q->fetchAll(PDO::FETCH_ASSOC) as $r){$t=trim((string)$r['title']);if($t==='')continue;if(preg_match('/winter|streu|räum|glätte|schnee/iu',$t))$winter=true;
  $note=trim(strtok((string)($r['notes']??''),"\n")?:'');$lines[]=$t.' – '.(GSZ_CONTRACT_FREQ[$r['frequency']]??$r['frequency']).($note!==''?' ('.mb_strimwidth($note,0,120,'…').')':'');}
 return ['text'=>implode("\n",array_unique($lines)),'winter'=>$winter];
}
/** Vertragsdaten aus Objekt und Kunde (für neuen Entwurf oder „neu übernehmen“). */
function gszContractSourceData(PDO $pdo,int $property):array {
 $q=$pdo->prepare('SELECT p.*,c.name customer_name,c.billing_address,c.email customer_email FROM properties p LEFT JOIN customers c ON c.id=p.customer_id WHERE p.id=?');$q->execute([$property]);$p=$q->fetch(PDO::FETCH_ASSOC);
 if(!$p)throw new RuntimeException('Objekt nicht gefunden.');$s=gszContractServicesFromProperty($pdo,$property);$client=(string)($p['customer_name']??'');
 return ['property_id'=>$property,'customer_id'=>$p['customer_id']?(int)$p['customer_id']:null,'client_name'=>$client,'client_address'=>(string)($p['billing_address']??''),'object_name'=>(string)$p['name'],'object_address'=>(string)$p['address'],'services'=>$s['text'],'winter_service'=>$s['winter']?1:0,'customer_kind'=>$client!==''?gszContractGuessKind($client):'business'];
}
/** Legt einen Entwurf an (oder gibt den vorhandenen zurück). $auto=true beim automatischen Anlegen mit dem Objekt. */
function gszContractCreate(PDO $pdo,?int $admin,int $property,bool $auto=false):int {
 if($existing=gszContractForProperty($pdo,$property))return (int)$existing['id'];
 $d=gszContractSourceData($pdo,$property);$consumer=$d['customer_kind']==='consumer';
 $term=max(1,min(60,(int)gszSetting($pdo,'contract_term')?:12));if($consumer)$term=min($term,24);
 $notice=$consumer?1:max(1,min(12,(int)gszSetting($pdo,'contract_notice')?:3));$vat=in_array(gszSetting($pdo,'contract_vat'),['zzgl','inkl','none'],true)?gszSetting($pdo,'contract_vat'):'zzgl';if($consumer&&$vat==='zzgl')$vat='inkl';
 $start=(new DateTimeImmutable('first day of next month'))->format('Y-m-d');
 $pdo->prepare('INSERT INTO property_contracts(property_id,customer_id,customer_kind,client_name,client_address,object_name,object_address,services,winter_service,vat_mode,starts_on,term_months,renew_months,notice_months,auto_created) VALUES(?,?,?,?,?,?,?,?,?,?,?,?,?,?,?)')
  ->execute([$property,$d['customer_id'],$d['customer_kind'],$d['client_name'],$d['client_address'],$d['object_name'],$d['object_address'],$d['services'],$d['winter_service'],$vat,$start,$term,$consumer?0:12,$notice,$auto?1:0]);
 $id=(int)$pdo->lastInsertId();gszAudit($pdo,$admin,'contract_created',['contract'=>$id,'property'=>$property,'auto'=>$auto]);return $id;
}
/** Automatik beim Anlegen eines Objekts – darf das Anlegen nie verhindern. */
function gszContractAutoDraft(PDO $pdo,?int $admin,int $property):?int {
 try{if(!gszContractsReady($pdo)||gszSetting($pdo,'contract_auto')!=='1')return null;return gszContractCreate($pdo,$admin,$property,true);}catch(Throwable $e){error_log('GSZ contract auto draft: '.$e->getMessage());return null;}
}
/** Entwürfe für alle Objekte ohne Vertrag. Rückgabe: Anzahl. */
function gszContractCreateMissing(PDO $pdo,int $admin):int {$n=0;foreach($pdo->query('SELECT p.id FROM properties p WHERE NOT EXISTS(SELECT 1 FROM property_contracts c WHERE c.property_id=p.id) ORDER BY p.id LIMIT 500')->fetchAll(PDO::FETCH_COLUMN) as $pid){gszContractCreate($pdo,$admin,(int)$pid);$n++;}return $n;}

function gszContractText(array $in,string $k,int $max,bool $req=false,string $label=''):string {$v=trim(str_replace("\r",'',(string)($in[$k]??'')));if(!mb_check_encoding($v,'UTF-8')||str_contains($v,"\0"))throw new InvalidArgumentException('Ungültige Zeichen.');if(mb_strlen($v)>$max)throw new InvalidArgumentException(($label?:$k).' ist zu lang.');if($req&&$v==='')throw new InvalidArgumentException(($label?:$k).' fehlt.');return $v;}
function gszContractDate(array $in,string $k):?string {$v=trim((string)($in[$k]??''));if($v==='')return null;if(!preg_match('/^\d{4}-\d{2}-\d{2}$/D',$v)||!checkdate((int)substr($v,5,2),(int)substr($v,8,2),(int)substr($v,0,4)))throw new InvalidArgumentException('Datum ungültig.');return $v;}
/** Speichert das Formular. Verbraucher: höchstens 24 Monate Erstlaufzeit, danach unbefristet mit 1 Monat Kündigungsfrist (§ 309 Nr. 9 BGB). */
function gszContractSave(PDO $pdo,int $admin,int $id,array $in):void {
 $c=gszContractGet($pdo,$id);if(!$c)throw new RuntimeException('Vertrag nicht gefunden.');
 if(isset($in['row_version'])&&(int)$in['row_version']!==(int)$c['row_version'])throw new RuntimeException('Der Vertrag wurde inzwischen geändert. Bitte neu laden.');
 $kind=($in['customer_kind']??'')==='consumer'?'consumer':'business';
 $price=trim((string)($in['price']??''));$cents=$price===''?null:financeCents(str_replace(['€',' '],'',$price),'de');if($cents!==null&&$cents<0)throw new InvalidArgumentException('Preis darf nicht negativ sein.');
 $period=isset(GSZ_CONTRACT_PERIOD[$in['price_period']??''])?(string)$in['price_period']:'month';$vat=isset(GSZ_CONTRACT_VAT[$in['vat_mode']??''])?(string)$in['vat_mode']:'zzgl';
 $term=max(1,min(60,(int)($in['term_months']??12)));$renew=max(0,min(24,(int)($in['renew_months']??12)));$notice=max(1,min(12,(int)($in['notice_months']??3)));
 if($kind==='consumer'){$term=min($term,24);$renew=0;$notice=1;if($vat==='zzgl')$vat='inkl';}
 $pdo->prepare('UPDATE property_contracts SET customer_kind=?,client_name=?,client_address=?,object_name=?,object_address=?,services=?,winter_service=?,price_cents=?,price_period=?,vat_mode=?,starts_on=?,term_months=?,renew_months=?,notice_months=?,special_terms=?,notes=?,row_version=row_version+1 WHERE id=?')
  ->execute([$kind,gszContractText($in,'client_name',190,true,'Auftraggeber'),gszContractText($in,'client_address',2000,false),gszContractText($in,'object_name',190,true,'Objekt'),gszContractText($in,'object_address',255),gszContractText($in,'services',6000,true,'Leistungsverzeichnis'),!empty($in['winter_service'])?1:0,$cents,$period,$vat,gszContractDate($in,'starts_on'),$term,$renew,$notice,gszContractText($in,'special_terms',6000),gszContractText($in,'notes',4000),$id]);
 gszAudit($pdo,$admin,'contract_saved',['contract'=>$id]);
}
/** Daten aus Objekt/Kunde/Regelaufgaben erneut übernehmen (nur im Entwurf). */
function gszContractRefresh(PDO $pdo,int $admin,int $id):void {
 $c=gszContractGet($pdo,$id);if(!$c||$c['status']!=='entwurf'||!$c['property_id'])throw new RuntimeException('Nur Entwürfe mit Objekt lassen sich neu übernehmen.');$d=gszContractSourceData($pdo,(int)$c['property_id']);
 $pdo->prepare('UPDATE property_contracts SET customer_id=?,client_name=?,client_address=?,object_name=?,object_address=?,services=?,winter_service=?,row_version=row_version+1 WHERE id=?')->execute([$d['customer_id'],$d['client_name'],$d['client_address'],$d['object_name'],$d['object_address'],$d['services'],$d['winter_service'],$id]);
 gszAudit($pdo,$admin,'contract_refreshed',['contract'=>$id]);
}
/** Statuswechsel: versendet / aktiv (unterschrieben) / gekündigt (mit Ende) / beendet / zurück zum Entwurf. */
function gszContractStatus(PDO $pdo,int $admin,int $id,string $to,array $in=[]):void {
 $c=gszContractGet($pdo,$id);if(!$c)throw new RuntimeException('Vertrag nicht gefunden.');$today=date('Y-m-d');
 $problems=gszContractProblems($c);if(in_array($to,['versendet','aktiv'],true)&&$problems)throw new RuntimeException('Vor dem Versand fehlt noch: '.implode(', ',$problems).'.');
 switch($to){
  case 'versendet':$pdo->prepare("UPDATE property_contracts SET status='versendet',sent_on=COALESCE(sent_on,?),row_version=row_version+1 WHERE id=?")->execute([gszContractDate($in,'date')??$today,$id]);break;
  case 'aktiv':$pdo->prepare("UPDATE property_contracts SET status='aktiv',signed_on=?,sent_on=COALESCE(sent_on,?),cancelled_on=NULL,ends_on=NULL,row_version=row_version+1 WHERE id=?")->execute([gszContractDate($in,'date')??$today,gszContractDate($in,'date')??$today,$id]);break;
  case 'gekuendigt':$end=gszContractDate($in,'ends_on');if(!$end){$per=gszContractPeriod($c,new DateTimeImmutable(gszContractDate($in,'date')??$today));$end=$per['earliest_end'];}
   $pdo->prepare("UPDATE property_contracts SET status='gekuendigt',cancelled_on=?,ends_on=?,row_version=row_version+1 WHERE id=?")->execute([gszContractDate($in,'date')??$today,$end,$id]);break;
  case 'beendet':$pdo->prepare("UPDATE property_contracts SET status='beendet',ends_on=COALESCE(ends_on,?),row_version=row_version+1 WHERE id=?")->execute([$today,$id]);break;
  case 'entwurf':if(!in_array($c['status'],['versendet'],true))throw new RuntimeException('Nur versendete Verträge lassen sich zurück in den Entwurf holen.');$pdo->prepare("UPDATE property_contracts SET status='entwurf',sent_on=NULL,row_version=row_version+1 WHERE id=?")->execute([$id]);break;
  default:throw new RuntimeException('Status unbekannt.');
 }
 gszAudit($pdo,$admin,'contract_status',['contract'=>$id,'from'=>$c['status'],'to'=>$to]);
}
function gszContractDelete(PDO $pdo,int $admin,int $id):void {$c=gszContractGet($pdo,$id);if(!$c||$c['status']!=='entwurf')throw new RuntimeException('Nur Entwürfe können gelöscht werden.');$pdo->prepare('DELETE FROM property_contracts WHERE id=?')->execute([$id]);gszAudit($pdo,$admin,'contract_deleted',['contract'=>$id,'property'=>$c['property_id']]);}
/** Was fehlt noch, bevor der Vertrag raus kann? */
function gszContractProblems(array $c):array {$p=[];if(trim((string)$c['client_name'])==='')$p[]='Auftraggeber';if(trim((string)$c['client_address'])==='')$p[]='Anschrift des Auftraggebers';if($c['price_cents']===null)$p[]='Preis';if(!$c['starts_on'])$p[]='Beginn';if(trim((string)$c['services'])==='')$p[]='Leistungen';return $p;}

/**
 * Laufzeit und Fristen. Unternehmer: Erstlaufzeit, dann Verlängerung um renew_months, Kündigung notice_months vor Ende.
 * Verbraucher: nach der Erstlaufzeit unbefristet, jederzeit mit 1 Monat kündbar.
 */
function gszContractPeriod(array $c,?DateTimeImmutable $today=null):array {
 $today??=new DateTimeImmutable('today');$out=['period_end'=>null,'notice_by'=>null,'open_ended'=>false,'earliest_end'=>$today->modify('+1 month')->format('Y-m-d')];
 if(!$c['starts_on'])return $out;$start=new DateTimeImmutable($c['starts_on']);$notice=max(1,(int)$c['notice_months']);
 $end=$start->modify('+'.(int)$c['term_months'].' months')->modify('-1 day');$renew=(int)$c['renew_months'];
 if($c['customer_kind']==='consumer'||$renew===0){
  if($today->modify('+'.$notice.' months')<=$end->modify('+1 day')){$out['period_end']=$end->format('Y-m-d');$out['notice_by']=$end->modify('+1 day')->modify('-'.$notice.' months')->modify('-1 day')->format('Y-m-d');$out['earliest_end']=$out['period_end'];}
  else{$out['open_ended']=true;$out['earliest_end']=max($end->format('Y-m-d'),$today->modify('+'.$notice.' months')->format('Y-m-d'));}
  return $out;}
 $guard=0;while($end->modify('+1 day')->modify('-'.$notice.' months')<=$today&&$guard++<100)$end=$end->modify('+1 day')->modify('+'.$renew.' months')->modify('-1 day');
 $out['period_end']=$end->format('Y-m-d');$out['notice_by']=$end->modify('+1 day')->modify('-'.$notice.' months')->modify('-1 day')->format('Y-m-d');$out['earliest_end']=$out['period_end'];return $out;
}
/** Hinweise für „Heute“: Kündigungsfrist naht (aktive Verträge), Vertrag endet bald, Entwürfe/Versand liegen lange. */
function gszContractAttention(PDO $pdo,?DateTimeImmutable $today=null):array {
 if(!gszContractsReady($pdo))return [];$today??=new DateTimeImmutable('today');$out=[];
 foreach($pdo->query("SELECT * FROM property_contracts WHERE status IN('aktiv','gekuendigt','versendet') ORDER BY id LIMIT 500")->fetchAll(PDO::FETCH_ASSOC) as $c){$url='vertrag.php?id='.(int)$c['id'];
  if($c['status']==='aktiv'){$p=gszContractPeriod($c,$today);if($p['notice_by']&&$p['notice_by']>=$today->format('Y-m-d')&&$p['notice_by']<=$today->modify('+60 days')->format('Y-m-d'))$out[]=[$url,'Vertrag '.$c['object_name'].': Kündigungsfrist bis '.(new DateTimeImmutable($p['notice_by']))->format('d.m.Y').' – Preis prüfen oder verlängern lassen'];}
  elseif($c['status']==='gekuendigt'&&$c['ends_on']&&$c['ends_on']<=$today->modify('+30 days')->format('Y-m-d'))$out[]=[$url,'Vertrag '.$c['object_name'].' endet am '.(new DateTimeImmutable($c['ends_on']))->format('d.m.Y').' – Schlüssel zurückgeben, Regelaufgaben beenden'];
  elseif($c['status']==='versendet'&&$c['sent_on']&&$c['sent_on']<=$today->modify('-14 days')->format('Y-m-d'))$out[]=[$url,'Vertrag '.$c['object_name'].' seit '.(new DateTimeImmutable($c['sent_on']))->format('d.m.').' nicht unterschrieben zurück – nachfassen'];}
 return array_slice($out,0,8);
}
/** Firmendaten für den Vertragskopf. */
function gszContractSettingsSave(PDO $pdo,int $admin,array $in):void {
 foreach(['company'=>190,'owner_name'=>120,'company_address'=>500,'company_email'=>190,'company_taxid'=>60,'company_phone'=>40] as $k=>$max)if(array_key_exists($k,$in))gszSettingSet($pdo,$k,gszContractText($in,$k,$max));
 if(isset($in['contract_term']))gszSettingSet($pdo,'contract_term',(string)max(1,min(60,(int)$in['contract_term'])));
 if(isset($in['contract_notice']))gszSettingSet($pdo,'contract_notice',(string)max(1,min(12,(int)$in['contract_notice'])));
 if(isset($in['contract_vat'])&&isset(GSZ_CONTRACT_VAT[$in['contract_vat']]))gszSettingSet($pdo,'contract_vat',(string)$in['contract_vat']);
 gszSettingSet($pdo,'contract_auto',!empty($in['contract_auto'])?'1':'0');gszAudit($pdo,$admin,'contract_settings_saved',[]);
}

/** Der Vertragstext als Abschnitte [Überschrift, [Absätze]] – dieselbe Quelle für Vorschau und PDF. */
function gszContractSections(PDO $pdo,array $c):array {
 $b=$c['customer_kind']!=='consumer';$co=gszSetting($pdo,'company');$owner=gszSetting($pdo,'owner_name');
 $price=$c['price_cents']===null?'______ €':moneyDisplay((int)$c['price_cents']);$per=['month'=>'monatlich','visit'=>'je Einsatz','year'=>'jährlich'][$c['price_period']]??'monatlich';
 $start=$c['starts_on']?(new DateTimeImmutable($c['starts_on']))->format('d.m.Y'):'________';$term=(int)$c['term_months'];$notice=(int)$c['notice_months'];$renew=(int)$c['renew_months'];
 $svc=array_values(array_filter(array_map('trim',explode("\n",(string)$c['services']))));$n=0;$s=[];
 $s[]=['§ '.(++$n).' Vertragsgegenstand',['Der Auftragnehmer erbringt für den Auftraggeber Dienstleistungen im Bereich Gebäudeservice an folgendem Objekt: '.trim($c['object_name'].', '.$c['object_address'],', ').'.','Art, Umfang und Turnus der Leistungen ergeben sich aus § '.($n+1).' (Leistungsverzeichnis).']];
 $s[]=['§ '.(++$n).' Leistungsverzeichnis',array_merge(array_map(fn($l)=>'• '.$l,$svc?:['(Leistungen eintragen)']),['Leistungen, die nicht im Leistungsverzeichnis stehen (z. B. Sonderreinigungen, Entrümpelung, Reparaturen), erbringt der Auftragnehmer nur nach gesonderter Beauftragung und Vergütung. Fallen regelmäßige Termine auf einen gesetzlichen Feiertag, werden sie zeitnah nachgeholt.'])];
 if((int)$c['winter_service'])$s[]=['§ '.(++$n).' Winterdienst',['Der Winterdienst umfasst das Räumen von Schnee und das Abstumpfen bei Glätte auf den im Leistungsverzeichnis genannten Flächen in der Zeit vom 1. November bis 31. März im Umfang und zu den Zeiten, die die Straßenreinigungssatzung der Gemeinde vorschreibt.','Der Auftraggeber überträgt dem Auftragnehmer insoweit die Räum- und Streupflicht. Die Pflicht des Auftraggebers, die ordnungsgemäße Durchführung stichprobenartig zu überwachen, bleibt unberührt.','Gestreut werden abstumpfende Mittel; Auftausalz nur, soweit die Satzung es erlaubt. Das Streugut stellt '.($b?'der Auftragnehmer gegen gesonderte Berechnung, sofern nicht anders vereinbart':'der Auftragnehmer, die Kosten sind in der Vergütung enthalten, sofern nicht anders vereinbart').'.']];
 $vatText=['zzgl'=>'zuzüglich der gesetzlichen Umsatzsteuer','inkl'=>'einschließlich der gesetzlichen Umsatzsteuer','none'=>'; gemäß § 19 UStG wird keine Umsatzsteuer berechnet'][$c['vat_mode']]??'';
 $s[]=['§ '.(++$n).' Vergütung und Zahlung',['Die Vergütung beträgt '.$price.' '.$per.($c['vat_mode']==='none'?$vatText:' '.$vatText).'.','Der Auftragnehmer rechnet '.($c['price_period']==='year'?'jährlich im Voraus':'monatlich').' ab. Rechnungen sind innerhalb von 14 Tagen nach Zugang ohne Abzug zahlbar.']];
 if($b)$s[]=['§ '.(++$n).' Preisanpassung',['Ändern sich die Lohnkosten des Auftragnehmers, insbesondere durch den gesetzlichen Mindestlohn oder den Tarifvertrag für das Gebäudereinigerhandwerk, kann der Auftragnehmer die Vergütung im Umfang der nachgewiesenen Kostenänderung anpassen. Die Anpassung wird mindestens 6 Wochen vorher in Textform angekündigt.','Steigt die Vergütung dadurch um mehr als 5 %, kann der Auftraggeber den Vertrag zum Zeitpunkt des Inkrafttretens der Anpassung kündigen.']];
 $lz=$b?['Der Vertrag beginnt am '.$start.' und läuft zunächst '.$term.' Monate.',($renew?'Er verlängert sich jeweils um '.$renew.' Monate, wenn er nicht mit einer Frist von '.$notice.' Monat'.($notice>1?'en':'').' zum Ende der Laufzeit gekündigt wird.':'Er endet mit Ablauf der Laufzeit, ohne dass es einer Kündigung bedarf.'),'Das Recht zur außerordentlichen Kündigung aus wichtigem Grund bleibt unberührt. Kündigungen bedürfen der Textform.']
  :['Der Vertrag beginnt am '.$start.' und läuft zunächst '.$term.' Monate. Er kann mit einer Frist von 1 Monat zum Ende dieser Laufzeit gekündigt werden.','Wird er nicht gekündigt, läuft er auf unbestimmte Zeit weiter und kann dann jederzeit mit einer Frist von 1 Monat gekündigt werden.','Das Recht zur außerordentlichen Kündigung aus wichtigem Grund bleibt unberührt. Kündigungen bedürfen der Textform.'];
 $s[]=['§ '.(++$n).' Laufzeit und Kündigung',$lz];
 $s[]=['§ '.(++$n).' Ausführung',['Der Auftragnehmer führt die Leistungen fachgerecht mit eigenem, geschultem Personal und eigenen Geräten aus'.($b?'; er darf qualifizierte Nachunternehmer einsetzen':'').'. Wasser und Strom für die Leistungen stellt der Auftraggeber am Objekt unentgeltlich zur Verfügung.','Ausführungszeiten werden abgestimmt. Der Auftraggeber sorgt dafür, dass die Flächen zugänglich sind.']];
 $s[]=['§ '.(++$n).' Schlüssel',['Für die Durchführung erhält der Auftragnehmer die nötigen Schlüssel gegen Quittung. Er verwahrt sie sicher und ohne Kennzeichnung der Objektanschrift und gibt sie bei Vertragsende unaufgefordert zurück.','Für den Verlust eines Schlüssels haftet der Auftragnehmer nach den gesetzlichen Vorschriften; ein Verlust wird dem Auftraggeber unverzüglich mitgeteilt.']];
 $s[]=['§ '.(++$n).' Beanstandungen',[$b?'Mängel der Leistung zeigt der Auftraggeber unverzüglich, möglichst innerhalb von 2 Werktagen, in Textform an. Der Auftragnehmer bessert berechtigt beanstandete Leistungen kurzfristig kostenfrei nach.':'Mängel der Leistung teilt der Auftraggeber dem Auftragnehmer bitte möglichst zeitnah mit. Der Auftragnehmer bessert berechtigt beanstandete Leistungen kurzfristig kostenfrei nach. Gesetzliche Rechte des Auftraggebers bleiben unberührt.']];
 $s[]=['§ '.(++$n).' Haftung und Versicherung',['Der Auftragnehmer unterhält eine Betriebshaftpflichtversicherung und weist sie auf Wunsch nach.','Der Auftragnehmer haftet unbeschränkt bei Vorsatz und grober Fahrlässigkeit sowie für Schäden aus der Verletzung von Leben, Körper oder Gesundheit. Bei leichter Fahrlässigkeit haftet er nur für die Verletzung wesentlicher Vertragspflichten, begrenzt auf den vertragstypischen, vorhersehbaren Schaden. Die Haftung nach dem Produkthaftungsgesetz bleibt unberührt.']];
 $s[]=['§ '.(++$n).' Datenschutz',['Die Vertragspartner verarbeiten personenbezogene Daten nur, soweit es zur Durchführung dieses Vertrags erforderlich ist (Art. 6 Abs. 1 lit. b DSGVO). Mitarbeiter des Auftragnehmers sind zur Verschwiegenheit über Angelegenheiten im Objekt verpflichtet.']];
 $sp=trim((string)$c['special_terms']);if($sp!=='')$s[]=['§ '.(++$n).' Besondere Vereinbarungen',array_values(array_filter(array_map('trim',explode("\n",$sp))))];
 $s[]=['§ '.(++$n).' Schlussbestimmungen',array_values(array_filter(['Änderungen und Ergänzungen dieses Vertrags bedürfen der Textform. Mündliche Nebenabreden bestehen nicht.','Sollte eine Bestimmung unwirksam sein, bleibt der Vertrag im Übrigen wirksam.',$b?'Ist der Auftraggeber Kaufmann, juristische Person des öffentlichen Rechts oder öffentlich-rechtliches Sondervermögen, ist Gerichtsstand der Sitz des Auftragnehmers.':null]))];
 return $s;
}
/** Muster-Widerrufsbelehrung für Dienstleistungen (Anlage 1 zu Art. 246a § 1 Abs. 2 EGBGB) – nur bei Verbrauchern, bei Abschluss außerhalb von Geschäftsräumen oder per Fernkommunikation. */
function gszContractWithdrawal(PDO $pdo):array {
 $who=trim(gszSetting($pdo,'company').', '.gszSetting($pdo,'owner_name').', '.str_replace("\n",', ',gszSetting($pdo,'company_address')).', Telefon '.gszSetting($pdo,'company_phone').(gszSetting($pdo,'company_email')!==''?', E-Mail '.gszSetting($pdo,'company_email'):''),', ');
 return [['Widerrufsbelehrung',['Widerrufsrecht','Sie haben das Recht, binnen vierzehn Tagen ohne Angabe von Gründen diesen Vertrag zu widerrufen. Die Widerrufsfrist beträgt vierzehn Tage ab dem Tag des Vertragsabschlusses.','Um Ihr Widerrufsrecht auszuüben, müssen Sie uns ('.$who.') mittels einer eindeutigen Erklärung (z. B. ein mit der Post versandter Brief oder E-Mail) über Ihren Entschluss, diesen Vertrag zu widerrufen, informieren. Sie können dafür das beigefügte Muster-Widerrufsformular verwenden, das jedoch nicht vorgeschrieben ist.','Zur Wahrung der Widerrufsfrist reicht es aus, dass Sie die Mitteilung über die Ausübung des Widerrufsrechts vor Ablauf der Widerrufsfrist absenden.','Folgen des Widerrufs','Wenn Sie diesen Vertrag widerrufen, haben wir Ihnen alle Zahlungen, die wir von Ihnen erhalten haben, unverzüglich und spätestens binnen vierzehn Tagen ab dem Tag zurückzuzahlen, an dem die Mitteilung über Ihren Widerruf dieses Vertrags bei uns eingegangen ist. Für diese Rückzahlung verwenden wir dasselbe Zahlungsmittel, das Sie bei der ursprünglichen Transaktion eingesetzt haben, es sei denn, mit Ihnen wurde ausdrücklich etwas anderes vereinbart; in keinem Fall werden Ihnen wegen dieser Rückzahlung Entgelte berechnet.','Haben Sie verlangt, dass die Dienstleistungen während der Widerrufsfrist beginnen sollen, so haben Sie uns einen angemessenen Betrag zu zahlen, der dem Anteil der bis zu dem Zeitpunkt, zu dem Sie uns von der Ausübung des Widerrufsrechts hinsichtlich dieses Vertrags unterrichten, bereits erbrachten Dienstleistungen im Vergleich zum Gesamtumfang der im Vertrag vorgesehenen Dienstleistungen entspricht.']],
  ['Muster-Widerrufsformular',['(Wenn Sie den Vertrag widerrufen wollen, dann füllen Sie bitte dieses Formular aus und senden Sie es zurück.)','An: '.$who,'Hiermit widerrufe(n) ich/wir (*) den von mir/uns (*) abgeschlossenen Vertrag über die Erbringung der folgenden Dienstleistung (*): ____________________________','Bestellt am (*) / erhalten am (*): ____________','Name des/der Verbraucher(s): ____________________________','Anschrift des/der Verbraucher(s): ____________________________','Unterschrift des/der Verbraucher(s) (nur bei Mitteilung auf Papier): ____________','Datum: ____________','(*) Unzutreffendes streichen.']],
  ['Ausdrücklicher Wunsch auf Beginn vor Ablauf der Widerrufsfrist',['☐ Ich verlange ausdrücklich, dass der Auftragnehmer mit den Leistungen vor Ablauf der Widerrufsfrist beginnt. Mir ist bekannt, dass ich bei einem Widerruf einen angemessenen Betrag für die bis dahin erbrachten Leistungen zahlen muss.','Ort, Datum, Unterschrift Auftraggeber: ____________________________']]];
}
/** PDF des Vertrags (Entwurf mit Wasserzeichen-Hinweis in der Fußzeile). */
function gszContractPdf(PDO $pdo,array $c):string {
 require_once __DIR__.'/service_pdf.php';$no=gszContractNo($c);$draft=$c['status']==='entwurf';
 $p=gszPdfNew($no.($draft?' · ENTWURF':'').' · '.gszSetting($pdo,'company'));$p->AddPage();$green=[47,138,43];$grey=[100,106,112];
 $p->para(5,gszSetting($pdo,'company').' · '.str_replace("\n",' · ',gszSetting($pdo,'company_address')),8,'L',$grey);$p->Ln(6);
 $p->para(9,'Dienstleistungsvertrag Gebäudeservice',18);$p->para(5,'Vertrag Nr. '.$no.($draft?' – Entwurf':''),9,'L',$grey);$p->Ln(4);
 $party=function(string $role,string $name,string $addr,string $extra='')use($p,$grey){$p->para(5,$role,8,'L',$grey);$p->para(5.4,$name,11);if(trim($addr)!=='')$p->para(5,$addr,10);if($extra!=='')$p->para(5,$extra,9,'L',$grey);$p->Ln(3);};
 $party('Auftraggeber',(string)$c['client_name'] ?: '________________________',(string)$c['client_address'],$c['customer_kind']==='consumer'?'(Verbraucher)':'');
 $tax=gszSetting($pdo,'company_taxid');$party('Auftragnehmer',gszSetting($pdo,'company').(gszSetting($pdo,'owner_name')!==''?', Inhaber '.gszSetting($pdo,'owner_name'):''),gszSetting($pdo,'company_address'),trim('Telefon '.gszSetting($pdo,'company_phone').(gszSetting($pdo,'company_email')!==''?' · '.gszSetting($pdo,'company_email'):'').($tax!==''?' · '.$tax:'')));
 $p->para(5,'Objekt: '.trim($c['object_name'].', '.$c['object_address'],', '),10);$p->Ln(3);
 $sections=gszContractSections($pdo,$c);
 $render=function(array $sections)use($p,$green){foreach($sections as [$h,$paras]){if($p->GetY()>250)$p->AddPage();$p->Ln(2);$p->para(6,$h,11,'L',$green);foreach($paras as $t)$p->para(4.9,$t,10);$p->Ln(1);}};
 $render($sections);
 if($p->GetY()>242)$p->AddPage();$p->Ln(8);$y=$p->GetY();$p->SetDrawColor(150,150,150);
 foreach([[18,'Auftraggeber'],[112,'Auftragnehmer']] as [$x,$who]){$p->Line($x,$y+14,$x+80,$y+14);$p->SetXY($x,$y+15);$p->SetFont('DejaVu','',8.5);$p->SetTextColor(100,106,112);$p->Cell(80,4,$p->safe('Ort, Datum, Unterschrift '.$who),0,0);$p->SetTextColor(0,0,0);}
 $p->SetY($y+24);
 if($c['customer_kind']==='consumer'){$p->AddPage();$p->para(5,'Anlage für Verbraucher – nur beifügen, wenn der Vertrag außerhalb der Geschäftsräume oder per Brief/E-Mail/Telefon geschlossen wird.',8,'L',$grey);$p->Ln(2);$render(gszContractWithdrawal($pdo));}
 return $p->Output('S');
}
