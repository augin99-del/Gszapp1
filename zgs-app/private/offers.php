<?php
declare(strict_types=1);
/**
 * Schema 460: Angebote. Aus einer Anfrage (oder frei) entsteht ein Angebot mit Positionen und PDF.
 * Nach dem Versand erinnert die Zentrale nach 3 und 7 Tagen ans Nachfassen.
 * Bei „angenommen“ entstehen Kunde, Objekt und Vertragsentwurf (mit dem Angebotspreis) automatisch.
 */
require_once __DIR__.'/contracts.php';

const GSZ_OFFER_STATUS=['entwurf'=>['Entwurf','wait'],'versendet'=>['Versendet','part'],'angenommen'=>['Angenommen','ok'],'abgelehnt'=>['Abgelehnt','bad']];
const GSZ_OFFER_PER=['month'=>'monatlich','visit'=>'je Einsatz','once'=>'einmalig','year'=>'jährlich','hour'=>'je Stunde'];
const GSZ_OFFER_MAX_ITEMS=10;

function gszOffersReady(PDO $pdo):bool {try{return (bool)$pdo->query("SELECT COUNT(*) FROM app_migrations WHERE version='460_angebote'")->fetchColumn();}catch(Throwable $e){return false;}}
function gszOfferNo(array $o):string {return 'A-'.substr((string)($o['created_at']??date('Y')),0,4).'-'.str_pad((string)(int)$o['id'],4,'0',STR_PAD_LEFT);}
function gszOfferGet(PDO $pdo,int $id):?array {$q=$pdo->prepare('SELECT * FROM offers WHERE id=?');$q->execute([$id]);return $q->fetch(PDO::FETCH_ASSOC)?:null;}
function gszOfferItems(array $o):array {$i=json_decode((string)($o['items_json']??'[]'),true);return is_array($i)?array_values(array_filter($i,fn($x)=>is_array($x)&&trim((string)($x['t']??''))!=='')):[];}
/** Summen je Abrechnungsart, z. B. ['month'=>18900,'once'=>9500]. Positionen ohne Preis zählen nicht. */
function gszOfferTotals(array $o):array {$t=[];foreach(gszOfferItems($o) as $i)if(isset($i['p'])&&$i['p']!==null){$per=isset(GSZ_OFFER_PER[$i['per']??''])?$i['per']:'month';$t[$per]=($t[$per]??0)+(int)$i['p'];}return $t;}
function gszOfferTotalText(array $o):string {$t=gszOfferTotals($o);return $t?implode(' + ',array_map(fn($k,$v)=>moneyDisplay($v).' '.GSZ_OFFER_PER[$k],array_keys($t),$t)):'–';}
function gszOfferSalutation(array $o):string {$n=trim((string)$o['client_name']);return $o['customer_kind']==='consumer'&&$n!==''?'Guten Tag '.$n.',':'Sehr geehrte Damen und Herren,';}
function gszOfferDefaultIntro(array $o):string {$obj=trim((string)($o['object_name']?:$o['object_address']));return 'vielen Dank für Ihre Anfrage. Gerne bieten wir Ihnen die folgenden Leistungen'.($obj!==''?' für das Objekt '.$obj:'').' an. Alle Arbeiten führen wir mit eigenem, geschultem Personal zuverlässig und termingerecht aus.';}

/** Neues Angebot – optional aus einer Anfrage (übernimmt Name, Firma, Kontakt, Ort, Leistung; Anfrage → Status „Angebot“). */
function gszOfferCreate(PDO $pdo,int $admin,?int $leadId=null,?int $propertyId=null):int {
 $d=['lead_id'=>null,'customer_id'=>null,'property_id'=>null,'client_name'=>'','client_address'=>'','client_email'=>'','client_phone'=>'','object_name'=>'','object_address'=>'','kind'=>'business','items'=>[]];
 if($leadId){$l=gszLead($pdo,$leadId);if(!$l)throw new RuntimeException('Anfrage nicht gefunden.');
  $q=$pdo->prepare("SELECT id FROM offers WHERE lead_id=? AND status IN('entwurf','versendet') ORDER BY id DESC LIMIT 1");$q->execute([$leadId]);if($ex=$q->fetchColumn())return (int)$ex;
  $name=trim((string)($l['company']?:$l['name']));$d=array_merge($d,['lead_id'=>$leadId,'client_name'=>$name,'client_email'=>(string)$l['email'],'client_phone'=>(string)$l['phone'],'object_address'=>(string)$l['place'],'object_name'=>trim((string)$l['object_type'])!==''?trim($l['object_type'].' '.$l['place']):(string)$l['place'],'kind'=>trim((string)$l['company'])!==''?gszContractGuessKind((string)$l['company']):($name!==''?gszContractGuessKind($name):'business')]);
  if(trim((string)$l['service'])!=='')$d['items'][]=['t'=>trim((string)$l['service']),'p'=>null,'per'=>'month'];
  if($l['property_id'])$propertyId=(int)$l['property_id'];}
 if($propertyId){$src=gszContractSourceData($pdo,$propertyId);$d=array_merge($d,['property_id'=>$propertyId,'customer_id'=>$src['customer_id'],'client_name'=>$d['client_name']?:$src['client_name'],'client_address'=>$src['client_address'],'object_name'=>$src['object_name'],'object_address'=>$src['object_address'],'kind'=>$src['client_name']!==''?$src['customer_kind']:$d['kind']]);
  if(!$d['items'])foreach(array_filter(explode("\n",$src['services'])) as $line)$d['items'][]=['t'=>$line,'p'=>null,'per'=>'month'];}
 if(!$d['items'])$d['items'][]=['t'=>'','p'=>null,'per'=>'month'];
 $vat=$d['kind']==='consumer'?'inkl':(in_array(gszSetting($pdo,'contract_vat'),['zzgl','inkl','none'],true)?gszSetting($pdo,'contract_vat'):'zzgl');
 $pdo->prepare('INSERT INTO offers(lead_id,customer_id,property_id,customer_kind,client_name,client_address,client_email,client_phone,object_name,object_address,items_json,vat_mode,valid_until,starts_on) VALUES(?,?,?,?,?,?,?,?,?,?,?,?,?,?)')
  ->execute([$d['lead_id'],$d['customer_id'],$d['property_id'],$d['kind'],mb_substr($d['client_name'],0,190),$d['client_address'],mb_substr($d['client_email'],0,190),mb_substr($d['client_phone'],0,80),mb_substr($d['object_name'],0,190),mb_substr($d['object_address'],0,255),json_encode($d['items'],JSON_UNESCAPED_UNICODE),$vat,(new DateTimeImmutable('+30 days'))->format('Y-m-d'),(new DateTimeImmutable('first day of next month'))->format('Y-m-d')]);
 $id=(int)$pdo->lastInsertId();if($leadId)try{gszLeadStatus($pdo,$admin,$leadId,'angebot');}catch(Throwable $e){}
 gszAudit($pdo,$admin,'offer_created',['offer'=>$id,'lead'=>$leadId,'property'=>$propertyId]);return $id;
}
/** Positionen aus dem Formular: items[i][t|p|per]. */
function gszOfferParseItems(array $rows):array {
 $out=[];foreach(array_slice(array_values($rows),0,GSZ_OFFER_MAX_ITEMS) as $r){if(!is_array($r))continue;$t=gszContractText($r,'t',300);if($t==='')continue;
  $raw=trim(str_replace(['€',' '],'',(string)($r['p']??'')));$p=$raw===''?null:financeCents($raw,'de');if($p!==null&&$p<0)throw new InvalidArgumentException('Preis darf nicht negativ sein.');
  $out[]=['t'=>$t,'p'=>$p,'per'=>isset(GSZ_OFFER_PER[$r['per']??''])?(string)$r['per']:'month'];}
 return $out;
}
function gszOfferSave(PDO $pdo,int $admin,int $id,array $in):void {
 $o=gszOfferGet($pdo,$id);if(!$o)throw new RuntimeException('Angebot nicht gefunden.');if(!in_array($o['status'],['entwurf','versendet'],true))throw new RuntimeException('Entschiedene Angebote lassen sich nicht mehr ändern.');
 if(isset($in['row_version'])&&(int)$in['row_version']!==(int)$o['row_version'])throw new RuntimeException('Das Angebot wurde inzwischen geändert. Bitte neu laden.');
 $items=gszOfferParseItems((array)($in['items']??[]));if(!$items)throw new InvalidArgumentException('Mindestens eine Leistung eintragen.');
 $email=trim((string)($in['client_email']??''));if($email!==''&&!filter_var($email,FILTER_VALIDATE_EMAIL))throw new InvalidArgumentException('E-Mail-Adresse ungültig.');
 $kind=($in['customer_kind']??'')==='consumer'?'consumer':'business';$vat=isset(GSZ_CONTRACT_VAT[$in['vat_mode']??''])?(string)$in['vat_mode']:'zzgl';if($kind==='consumer'&&$vat==='zzgl')$vat='inkl';
 $pdo->prepare('UPDATE offers SET customer_kind=?,client_name=?,client_address=?,client_email=?,client_phone=?,object_name=?,object_address=?,intro=?,items_json=?,vat_mode=?,valid_until=?,starts_on=?,notes=?,row_version=row_version+1 WHERE id=?')
  ->execute([$kind,gszContractText($in,'client_name',190,true,'Kunde'),gszContractText($in,'client_address',2000),$email,gszContractText($in,'client_phone',80),gszContractText($in,'object_name',190),gszContractText($in,'object_address',255),gszContractText($in,'intro',3000),json_encode($items,JSON_UNESCAPED_UNICODE),$vat,gszContractDate($in,'valid_until'),gszContractDate($in,'starts_on'),gszContractText($in,'notes',4000),$id]);
 gszAudit($pdo,$admin,'offer_saved',['offer'=>$id]);
}
function gszOfferProblems(array $o):array {$p=[];if(trim((string)$o['client_name'])==='')$p[]='Kunde';$items=gszOfferItems($o);if(!$items)$p[]='Leistungen';elseif(!gszOfferTotals($o))$p[]='Preis';if(trim((string)$o['object_name'].(string)$o['object_address'])==='')$p[]='Objekt';return $p;}

/**
 * Statuswechsel. versendet → Nachfassen in 3 Tagen; nachgefasst → nächste Erinnerung am 7. Tag, danach keine mehr;
 * angenommen → Kunde/Objekt/Vertragsentwurf; abgelehnt → Anfrage „abgesagt“.
 */
function gszOfferStatus(PDO $pdo,int $admin,int $id,string $to,array $in=[],?DateTimeImmutable $today=null):array {
 $o=gszOfferGet($pdo,$id);if(!$o)throw new RuntimeException('Angebot nicht gefunden.');$today??=new DateTimeImmutable('today');$d=$today->format('Y-m-d');$res=[];
 switch($to){
  case 'versendet':if($p=gszOfferProblems($o))throw new RuntimeException('Vor dem Versand fehlt noch: '.implode(', ',$p).'.');if($o['status']!=='entwurf')throw new RuntimeException('Nur Entwürfe können versendet werden.');
   $sent=gszContractDate($in,'date')??$d;$pdo->prepare("UPDATE offers SET status='versendet',sent_on=?,followup_on=?,followups=0,row_version=row_version+1 WHERE id=?")->execute([$sent,(new DateTimeImmutable($sent))->modify('+3 days')->format('Y-m-d'),$id]);break;
  case 'nachgefasst':if($o['status']!=='versendet')throw new RuntimeException('Nur versendete Angebote.');$n=(int)$o['followups']+1;$next=$n===1&&$o['sent_on']?max($d,(new DateTimeImmutable($o['sent_on']))->modify('+7 days')->format('Y-m-d')):null;if($next===$d)$next=$today->modify('+4 days')->format('Y-m-d');
   $note=gszContractText($in,'note',300);$pdo->prepare('UPDATE offers SET followups=?,followup_on=?,notes=CONCAT(COALESCE(notes,\'\'),?),row_version=row_version+1 WHERE id=?')->execute([$n,$n>=2?null:$next,"\n".$today->format('d.m.').' nachgefasst'.($note!==''?': '.$note:''),$id]);break;
  case 'entwurf':if($o['status']!=='versendet')throw new RuntimeException('Nur versendete Angebote.');$pdo->prepare("UPDATE offers SET status='entwurf',sent_on=NULL,followup_on=NULL,followups=0,row_version=row_version+1 WHERE id=?")->execute([$id]);break;
  case 'abgelehnt':if(!in_array($o['status'],['entwurf','versendet'],true))throw new RuntimeException('Schon entschieden.');$pdo->prepare("UPDATE offers SET status='abgelehnt',decided_on=?,decision_note=?,followup_on=NULL,row_version=row_version+1 WHERE id=?")->execute([$d,gszContractText($in,'note',500),$id]);
   if($o['lead_id'])try{gszLeadStatus($pdo,$admin,(int)$o['lead_id'],'abgesagt');}catch(Throwable $e){}break;
  case 'angenommen':if(!in_array($o['status'],['entwurf','versendet'],true))throw new RuntimeException('Schon entschieden.');if($p=gszOfferProblems($o))throw new RuntimeException('Es fehlt noch: '.implode(', ',$p).'.');$res=gszOfferAccept($pdo,$admin,$o,$d);break;
  default:throw new RuntimeException('Status unbekannt.');
 }
 gszAudit($pdo,$admin,'offer_status',['offer'=>$id,'from'=>$o['status'],'to'=>$to]);return $res;
}
/** Annahme: Kunde und Objekt (falls noch nicht vorhanden), Vertragsentwurf mit Leistungen und Preis, Anfrage → Auftrag. */
function gszOfferAccept(PDO $pdo,int $admin,array $o,string $day):array {
 $pdo->beginTransaction();try{
  $cid=$o['customer_id']?(int)$o['customer_id']:null;$pid=$o['property_id']?(int)$o['property_id']:null;$newC=false;$newP=false;
  if(!$cid&&!$pid){$pdo->prepare('INSERT INTO customers(name,email,phone,billing_address,notes) VALUES(?,?,?,?,?)')->execute([$o['client_name'],$o['client_email'],$o['client_phone'],$o['client_address'],'Aus Angebot '.gszOfferNo($o)]);$cid=(int)$pdo->lastInsertId();$newC=true;}
  if(!$pid){$pdo->prepare('INSERT INTO properties(customer_id,name,address,contact_phone,contact_email) VALUES(?,?,?,?,?)')->execute([$cid,mb_substr(trim((string)($o['object_name']?:$o['object_address']?:$o['client_name'])),0,190),mb_substr((string)$o['object_address'],0,255),mb_substr((string)$o['client_phone'],0,80),mb_substr((string)$o['client_email'],0,190)]);$pid=(int)$pdo->lastInsertId();$newP=true;}
  elseif(!$cid){$cid=(int)$pdo->query('SELECT customer_id FROM properties WHERE id='.(int)$pid)->fetchColumn()?:null;}
  $pdo->commit();}catch(Throwable $e){if($pdo->inTransaction())$pdo->rollBack();throw $e;}
 $existing=gszContractForProperty($pdo,$pid);$ctId=gszContractCreate($pdo,$admin,$pid);$ct=gszContractGet($pdo,$ctId);
 if($ct['status']==='entwurf'){$items=gszOfferItems($o);$t=gszOfferTotals($o);$per=isset($t['month'])?'month':(isset($t['year'])?'year':(isset($t['visit'])?'visit':'month'));
  $services=implode("\n",array_map(fn($i)=>$i['t'].(isset($i['p'])&&$i['p']!==null&&($i['per']??'month')!=='month'?' ('.moneyDisplay((int)$i['p']).' '.GSZ_OFFER_PER[$i['per']??'month'].')':''),$items));
  $consumer=$o['customer_kind']==='consumer';
  $pdo->prepare('UPDATE property_contracts SET customer_kind=?,client_name=?,client_address=?,object_name=?,object_address=?,services=?,price_cents=?,price_period=?,vat_mode=?,starts_on=COALESCE(?,starts_on),term_months=IF(?,LEAST(term_months,24),term_months),renew_months=IF(?,0,renew_months),notice_months=IF(?,1,notice_months),special_terms=CONCAT(COALESCE(special_terms,\'\'),?),row_version=row_version+1 WHERE id=?')
   ->execute([$o['customer_kind'],$o['client_name'],$o['client_address'],$o['object_name']?:$ct['object_name'],$o['object_address']?:$ct['object_address'],$services,$t[$per]??null,$per,$o['vat_mode'],$o['starts_on'],$consumer?1:0,$consumer?1:0,$consumer?1:0,(trim((string)$ct['special_terms'])!==''?"\n":'').'Grundlage: Angebot '.gszOfferNo($o).' vom '.(new DateTimeImmutable($o['sent_on']?:$day))->format('d.m.Y').'.',$ctId]);}
 $pdo->prepare("UPDATE offers SET status='angenommen',decided_on=?,customer_id=?,property_id=?,contract_id=?,followup_on=NULL,row_version=row_version+1 WHERE id=?")->execute([$day,$cid,$pid,$ctId,(int)$o['id']]);
 if($o['lead_id']){$pdo->prepare('UPDATE leads SET property_id=COALESCE(property_id,?),row_version=row_version+1 WHERE id=?')->execute([$pid,(int)$o['lead_id']]);try{gszLeadStatus($pdo,$admin,(int)$o['lead_id'],'auftrag');}catch(Throwable $e){}}
 return ['customer_id'=>$cid,'property_id'=>$pid,'contract_id'=>$ctId,'new_customer'=>$newC,'new_property'=>$newP,'contract_existed'=>(bool)$existing];
}
function gszOfferDelete(PDO $pdo,int $admin,int $id):void {$o=gszOfferGet($pdo,$id);if(!$o||$o['status']!=='entwurf')throw new RuntimeException('Nur Entwürfe können gelöscht werden.');$pdo->prepare('DELETE FROM offers WHERE id=?')->execute([$id]);gszAudit($pdo,$admin,'offer_deleted',['offer'=>$id]);}
/** Fällige Nachfass-Erinnerungen (für Heute und Push). */
function gszOfferAttention(PDO $pdo,?DateTimeImmutable $today=null):array {
 if(!gszOffersReady($pdo))return [];$today??=new DateTimeImmutable('today');$q=$pdo->prepare("SELECT * FROM offers WHERE status='versendet' AND followup_on IS NOT NULL AND followup_on<=? ORDER BY followup_on LIMIT 20");$q->execute([$today->format('Y-m-d')]);$out=[];
 foreach($q->fetchAll(PDO::FETCH_ASSOC) as $o)$out[]=['angebot.php?id='.(int)$o['id'],'Angebot '.gszOfferNo($o).' an '.$o['client_name'].' nachfassen (versendet am '.(new DateTimeImmutable((string)$o['sent_on']))->format('d.m.').')'];
 $q=$pdo->prepare("SELECT * FROM offers WHERE status='versendet' AND followup_on IS NULL AND valid_until<? ORDER BY valid_until LIMIT 10");$q->execute([$today->format('Y-m-d')]);
 foreach($q->fetchAll(PDO::FETCH_ASSOC) as $o)$out[]=['angebot.php?id='.(int)$o['id'],'Angebot '.gszOfferNo($o).' an '.$o['client_name'].' ist abgelaufen – angenommen oder abgelehnt?'];
 return $out;
}
/** PDF des Angebots. */
function gszOfferPdf(PDO $pdo,array $o):string {
 require_once __DIR__.'/service_pdf.php';$no=gszOfferNo($o);$p=gszPdfNew($no.' · '.gszSetting($pdo,'company'));$p->AddPage();$green=[47,138,43];$grey=[100,106,112];
 $p->para(5,gszSetting($pdo,'company').' · '.str_replace("\n",' · ',gszSetting($pdo,'company_address')),8,'L',$grey);$p->Ln(8);
 $y=$p->GetY();$p->para(5.2,(string)$o['client_name'],11);if(trim((string)$o['client_address'])!=='')$p->para(5,(string)$o['client_address'],10);
 $p->SetXY(130,$y);$p->SetFont('DejaVu','',9);$p->SetTextColor(...$grey);$meta=['Angebot Nr.'=>$no,'Datum'=>(new DateTimeImmutable((string)($o['sent_on']?:'today')))->format('d.m.Y'),'Gültig bis'=>$o['valid_until']?(new DateTimeImmutable($o['valid_until']))->format('d.m.Y'):'–'];
 foreach($meta as $k=>$v){$p->SetX(130);$p->Cell(28,5,$p->safe($k),0,0);$p->SetTextColor(0,0,0);$p->Cell(34,5,$p->safe($v),0,1,'R');$p->SetTextColor(...$grey);}$p->SetTextColor(0,0,0);
 $p->SetY(max($p->GetY(),$y+26)+6);$p->para(8,'Angebot'.(trim((string)$o['object_name'])!==''?' – '.$o['object_name']:''),16);if(trim((string)$o['object_address'])!==''&&$o['object_address']!==$o['object_name'])$p->para(5,'Objekt: '.$o['object_address'],9,'L',$grey);$p->Ln(3);
 $p->para(5,gszOfferSalutation($o),10);$p->Ln(1);$p->para(5,trim((string)$o['intro'])!==''?(string)$o['intro']:gszOfferDefaultIntro($o),10);$p->Ln(4);
 // Tabelle
 $p->SetFillColor(234,244,232);$p->SetFont('DejaVu','',9);$p->Cell(10,7,'Pos.',0,0,'L',true);$p->Cell(102,7,'Leistung',0,0,'L',true);$p->Cell(30,7,'Abrechnung',0,0,'L',true);$p->Cell(32,7,'Preis',0,1,'R',true);
 $n=0;foreach(gszOfferItems($o) as $i){$n++;$x=$p->GetX();$y0=$p->GetY();if($y0>255){$p->AddPage();$y0=$p->GetY();}$p->SetFont('DejaVu','',9.5);$p->SetXY($x+10,$y0+1.5);$p->MultiCell(100,4.6,$p->safe((string)$i['t']),0,'L');$h=max(8,$p->GetY()-$y0+1.5);
  $p->SetXY($x,$y0+1.5);$p->Cell(10,4.6,(string)$n,0,0);$p->SetXY($x+112,$y0+1.5);$p->Cell(30,4.6,$p->safe(GSZ_OFFER_PER[$i['per']??'month']??''),0,0);$p->Cell(32,4.6,$p->safe(isset($i['p'])&&$i['p']!==null?moneyDisplay((int)$i['p']):'nach Aufwand'),0,0,'R');
  $p->SetDrawColor(226,226,222);$p->Line($x,$y0+$h,$x+174,$y0+$h);$p->SetY($y0+$h);}
 $p->Ln(3);foreach(gszOfferTotals($o) as $per=>$sum){$p->SetFont('DejaVu','',10.5);$p->Cell(142,6,$p->safe('Summe '.GSZ_OFFER_PER[$per]),0,0,'R');$p->Cell(32,6,$p->safe(moneyDisplay($sum)),0,1,'R');}
 $p->para(5,['zzgl'=>'Alle Preise zuzüglich der gesetzlichen Umsatzsteuer.','inkl'=>'Alle Preise einschließlich der gesetzlichen Umsatzsteuer.','none'=>'Gemäß § 19 UStG wird keine Umsatzsteuer berechnet.'][$o['vat_mode']]??'',9,'R',$grey);$p->Ln(5);
 $start=$o['starts_on']?(new DateTimeImmutable($o['starts_on']))->format('d.m.Y'):'';
 $p->para(5,'Leistungsbeginn'.($start!==''?' ab '.$start:' nach Absprache').'. Regelmäßige Leistungen schließen wir mit einem Dienstleistungsvertrag ab; Laufzeit und Kündigungsfrist stehen dort. Zusätzliche Arbeiten führen wir nur nach Ihrer Freigabe aus.',10);$p->Ln(2);
 $p->para(5,'Wir freuen uns auf Ihre Rückmeldung – gern auch telefonisch unter '.gszSetting($pdo,'company_phone').'.',10);$p->Ln(3);$p->para(5,'Mit freundlichen Grüßen',10);$p->para(5,gszSetting($pdo,'owner_name').' · '.gszSetting($pdo,'company'),10);
 if($p->GetY()>235)$p->AddPage();$p->Ln(12);$y=$p->GetY();$p->SetDrawColor(150,150,150);$p->para(5,'Auftragserteilung: Hiermit beauftrage ich die angebotenen Leistungen zu den genannten Konditionen.',9,'L',$grey);$y=$p->GetY()+12;$p->Line(18,$y,98,$y);$p->SetXY(18,$y+1);$p->SetFont('DejaVu','',8.5);$p->SetTextColor(...$grey);$p->Cell(80,4,$p->safe('Ort, Datum, Unterschrift Auftraggeber'),0,0);$p->SetTextColor(0,0,0);
 return $p->Output('S');
}
/** Mail-Entwurf mit PDF im Anhang (wird in „Neue Mail“ geprüft und gesendet). Rückgabe: Entwurfs-ID. */
function gszDocMailDraft(PDO $pdo,string $to,string $subject,string $body,string $filename,string $pdf):int {
 require_once __DIR__.'/mail_service.php';if($to!==''&&!filter_var($to,FILTER_VALIDATE_EMAIL))throw new InvalidArgumentException('E-Mail-Adresse des Empfängers ungültig.');
 $sig='';try{require_once __DIR__.'/config_loader.php';$sig=(string)(gszLoadConfig()['mail_signature']??'');}catch(Throwable $e){}
 $id=gszMailSaveDraft($pdo,['to_address'=>$to,'subject'=>$subject,'body'=>$body.($sig!==''?"\n\n".$sig:'')]);gszMailDraftAttachment($pdo,$id,$filename,$pdf);return $id;
}
function gszOfferFileName(array $o):string {return 'Angebot-'.gszOfferNo($o).'.pdf';}
function gszOfferMailText(PDO $pdo,array $o):string {return gszOfferSalutation($o)."\n\nvielen Dank für Ihre Anfrage. Im Anhang finden Sie unser Angebot ".gszOfferNo($o).'.'.($o['valid_until']?' Es gilt bis '.(new DateTimeImmutable($o['valid_until']))->format('d.m.Y').'.':'')."\n\nWenn Sie einverstanden sind, genügt eine kurze Antwort auf diese Mail oder das unterschriebene Angebot zurück. Für Fragen bin ich gern telefonisch für Sie da: ".gszSetting($pdo,'company_phone').".\n\nMit freundlichen Grüßen\n".gszSetting($pdo,'owner_name')."\n".gszSetting($pdo,'company');}
