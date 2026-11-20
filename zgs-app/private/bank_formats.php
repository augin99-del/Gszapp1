<?php
declare(strict_types=1);
require_once __DIR__.'/money.php';require_once __DIR__.'/planning_engine.php';
/** Narrow, offline ISO20022 reader; no DTD, entities, XInclude, schema fetches or implicit bookings. */
function bankIban(string $raw):string {
 $s=strtoupper(preg_replace('/\s+/','',$raw));if(!preg_match('/^[A-Z]{2}[0-9]{2}[A-Z0-9]{11,30}$/D',$s))throw new InvalidArgumentException('Gültige Konto-IBAN für den Importabgleich erforderlich.');$n=substr($s,4).substr($s,0,4);$mod=0;foreach(str_split($n) as $c){$digits=ctype_digit($c)?$c:(string)(ord($c)-55);foreach(str_split($digits) as $d)$mod=($mod*10+(int)$d)%97;}if($mod!==1)throw new InvalidArgumentException('IBAN-Prüfsumme ungültig.');return $s;
}
function bankCamtDate(string $raw):string {
 if(zgsValidDate($raw))return $raw;if(!preg_match('/^\d{4}-\d{2}-\d{2}T\d{2}:\d{2}:\d{2}(?:\.\d{1,6})?(?:Z|[+-]\d{2}:\d{2})$/D',$raw))throw new InvalidArgumentException('CAMT-Datum/-Zeitzone ungültig.');$d=DateTimeImmutable::createFromFormat('!Y-m-d',substr($raw,0,10));if(!$d||$d->format('Y-m-d')!==substr($raw,0,10))throw new InvalidArgumentException('CAMT-Datum ungültig.');try{$d=new DateTimeImmutable($raw);$errors=DateTimeImmutable::getLastErrors();if($errors&&($errors['warning_count']||$errors['error_count']))throw new InvalidArgumentException();}catch(Throwable $e){throw new InvalidArgumentException('CAMT-Zeit ungültig.');}return $d->setTimezone(new DateTimeZone('Europe/Berlin'))->format('Y-m-d');
}
function bankParseCamt(string $raw,string $expectedIban,int $maxRows=1000):array {
 if(!class_exists('DOMDocument'))throw new RuntimeException('BLOCKED: PHP-Erweiterung DOM für CAMT fehlt; neutralen CSV-Import verwenden.');
 $expected=bankIban($expectedIban);if(strlen($raw)>2*1024*1024||!mb_check_encoding($raw,'UTF-8')||str_contains($raw,"\0")||preg_match('/<!DOCTYPE|<!ENTITY|<\?xml-stylesheet/i',$raw))throw new InvalidArgumentException('CAMT: UTF-8-XML bis2MB ohne DTD/Entities/Stylesheet erforderlich.');
 if(preg_match('~^(?:\xEF\xBB\xBF)?\s*<\?xml\s[^?]*encoding\s*=\s*[\"\']([^\"\']+)[\"\']~i',$raw,$encoding)&&!in_array(strtoupper($encoding[1]),['UTF-8','UTF8'],true))throw new InvalidArgumentException('CAMT-Deklaration muss UTF-8 sein; keine stillschweigende Zeichensatzumdeutung.');
 $previous=libxml_use_internal_errors(true);try{$dom=new DOMDocument();$dom->resolveExternals=false;$dom->substituteEntities=false;if(!$dom->loadXML($raw,LIBXML_NONET|LIBXML_NOBLANKS))throw new InvalidArgumentException('CAMT-XML ist ungültig.');$root=$dom->documentElement;$ns=$root?->namespaceURI??'';if(!$root||$root->localName!=='Document'||!preg_match('/^urn:iso:std:iso:20022:tech:xsd:camt\.(053|054)\.001\.(02|04|08|10|11)$/D',$ns))throw new InvalidArgumentException('Unterstützt: camt.053/054 .001.02/04/08/10/11, keine andere Nachricht.');$nodes=$dom->getElementsByTagName('*');if($nodes->length>20000)throw new InvalidArgumentException('CAMT-Struktur zu groß.');foreach($nodes as $node){if($node->namespaceURI!==$ns)throw new InvalidArgumentException('Fremder XML-Namensraum gesperrt.');$depth=0;for($p=$node;$p;$p=$p->parentNode)if(++$depth>40)throw new InvalidArgumentException('CAMT-Strukturtiefe überschritten.');}
 $x=new DOMXPath($dom);$x->registerNamespace('c',$ns);$one=static function(string $path,DOMNode $node,bool $required=true)use($x):string{$list=$x->query($path,$node);if($list->length>1||($required&&$list->length!==1))throw new InvalidArgumentException('CAMT-Pflichtfeld fehlt oder ist mehrdeutig.');return $list->length?trim($list->item(0)->textContent):'';};
 $statements=$x->query('/c:Document/c:BkToCstmrStmt/c:Stmt | /c:Document/c:BkToCstmrDbtCdtNtfctn/c:Ntfctn');if(!$statements->length||$statements->length>24)throw new InvalidArgumentException('CAMT benötigt1–24 Kontoauszüge/Benachrichtigungen.');$out=[];
 foreach($statements as $statement){if(bankIban($one('c:Acct/c:Id/c:IBAN',$statement))!==$expected)throw new InvalidArgumentException('CAMT gehört nicht ausschließlich zur ausgewählten bestätigten Konto-IBAN.');$accountCurrency=$one('c:Acct/c:Ccy',$statement,false);if($accountCurrency!==''&&$accountCurrency!=='EUR')throw new InvalidArgumentException('Nur EUR-Kontoauszüge unterstützt.');foreach($x->query('c:Ntry',$statement) as $entry){$bookedStatus=$one('c:Sts/c:Cd | c:Sts[not(*)]',$entry);if($bookedStatus!=='BOOK')throw new InvalidArgumentException('Nur gebuchte CAMT-Umsätze; vorgemerkte/andere Status benötigen Prüfung.');$details=$x->query('c:NtryDtls/c:TxDtls',$entry);if($details->length>1)throw new InvalidArgumentException('CAMT-Sammelbuchung mit mehreren Transaktionen: nicht ungeprüft aufteilen; Einzelumsatzexport/CSV verwenden.');$detail=$details->length?$details->item(0):$entry;$amountNode=$x->query('c:Amt',$entry);if($amountNode->length!==1||$amountNode->item(0)->getAttribute('Ccy')!=='EUR')throw new InvalidArgumentException('Eindeutiger CAMT-EUR-Betrag erforderlich.');$amount=financeCents(trim($amountNode->item(0)->textContent));if($amount<0)throw new InvalidArgumentException('CAMT-Betrag muss positiv sein, Vorzeichen aus CRDT/DBIT.');$direction=$one('c:CdtDbtInd',$entry);if(!in_array($direction,['CRDT','DBIT'],true))throw new InvalidArgumentException('CAMT-Vorzeichen unklar.');if($direction==='DBIT')$amount=-$amount;
 $reference=$one('c:AcctSvcrRef',$entry,false);if($reference===''||in_array(strtoupper($reference),['NOTPROVIDED','NONREF'],true))$reference=$one('c:NtryRef',$entry,false);if($reference===''||in_array(strtoupper($reference),['NOTPROVIDED','NONREF'],true))$reference=$one('c:Refs/c:TxId',$detail,false);if($reference===''||mb_strlen($reference)>190||in_array(strtoupper($reference),['NOTPROVIDED','NONREF'],true))throw new InvalidArgumentException('CAMT benötigt stabile Bankreferenz (AcctSvcrRef/NtryRef/TxId); keine erfundene Zeilen-ID.');
 $booked=bankCamtDate($one('c:BookgDt/c:Dt | c:BookgDt/c:DtTm',$entry));$value=bankCamtDate($one('c:ValDt/c:Dt | c:ValDt/c:DtTm',$entry));$namePath=$direction==='CRDT'?'c:RltdPties/c:Dbtr/c:Nm | c:RltdPties/c:Dbtr/c:Pty/c:Nm':'c:RltdPties/c:Cdtr/c:Nm | c:RltdPties/c:Cdtr/c:Pty/c:Nm';$counterparty=$one($namePath,$detail,false);$purpose=[];foreach($x->query('c:RmtInf/c:Ustrd',$detail) as $text)$purpose[]=trim($text->textContent);if(!$purpose){$extra=$one('c:AddtlNtryInf',$entry,false);if($extra!=='')$purpose[]=$extra;}$purpose=implode(' ',$purpose);$reversal=$one('c:RvslInd',$entry,false);if($reversal!==''&&!in_array($reversal,['true','false','1','0'],true))throw new InvalidArgumentException('CAMT-Stornokennzeichen ungültig.');if(in_array($reversal,['true','1'],true))$purpose='[Storno laut Bank] '.$purpose;
 $row=['external_id'=>$reference,'booked_on'=>$booked,'value_on'=>$value,'amount_cents'=>$amount,'currency'=>'EUR','purpose'=>$purpose,'counterparty'=>$counterparty];if(mb_strlen($purpose)>2000||mb_strlen($counterparty)>500)throw new InvalidArgumentException('CAMT-Umsatztext zu lang.');$row['record_hash']=hash('sha256',json_encode($row,JSON_THROW_ON_ERROR));$row['source_key']=hash('sha256',$reference);if(isset($out[$reference])&&$out[$reference]['record_hash']!==$row['record_hash'])throw new RuntimeException('CAMT-Referenz mit abweichenden Buchungsdaten.');$out[$reference]=$row;if(count($out)>max(1,min(5000,$maxRows)))throw new RuntimeException('CAMT-Zeilenlimit überschritten.');}}
 if(!$out)throw new InvalidArgumentException('Keine gebuchten CAMT-Umsätze.');return array_values($out);
 }finally{libxml_clear_errors();libxml_use_internal_errors($previous);}
}
function bankParseCsv(string $raw,array $map,string $locale='de',string $delimiter=';',int $maxRows=1000):array {
 if(strlen($raw)>2*1024*1024||!mb_check_encoding($raw,'UTF-8')||str_contains($raw,"\0"))throw new InvalidArgumentException('Bankimport: UTF-8, maximal 2 MB, keine Nullbytes.');if(!in_array($delimiter,[';',','],true))throw new InvalidArgumentException('Trennzeichen ungültig.');$raw=preg_replace('/^\xEF\xBB\xBF/','',$raw);$f=fopen('php://temp','w+');fwrite($f,$raw);rewind($f);$header=fgetcsv($f,16384,$delimiter,'"','');if(!$header||count($header)!==count(array_unique($header)))throw new InvalidArgumentException('Bank-CSV benötigt eindeutige Kopfzeile.');foreach(['booked_on','amount','currency','purpose','counterparty','id'] as $key)if(empty($map[$key])||!in_array($map[$key],$header,true))throw new InvalidArgumentException('CSV-Zuordnung fehlt: '.$key);
 $out=[];$ids=[];while(($row=fgetcsv($f,16384,$delimiter,'"',''))!==false){if($row===[null])continue;if(count($row)!==count($header))throw new InvalidArgumentException('CSV-Spaltenzahl abweichend.');$r=array_combine($header,$row);$id=trim($r[$map['id']]);$date=trim($r[$map['booked_on']]);$value=!empty($map['value_on'])?trim($r[$map['value_on']]??''):$date;$currency=trim($r[$map['currency']]);$purpose=trim($r[$map['purpose']]);$counterparty=trim($r[$map['counterparty']]);if($id===''||mb_strlen($id)>190||!zgsValidDate($date)||!zgsValidDate($value)||$currency!=='EUR'||mb_strlen($purpose)>2000||mb_strlen($counterparty)>500)throw new InvalidArgumentException('Bankzeile: stabile ID, ISO-Datum, EUR und Textgrenzen prüfen.');$amount=financeCents($r[$map['amount']],$locale);$item=['external_id'=>$id,'booked_on'=>$date,'value_on'=>$value,'amount_cents'=>$amount,'currency'=>$currency,'purpose'=>$purpose,'counterparty'=>$counterparty];$hash=hash('sha256',json_encode($item,JSON_THROW_ON_ERROR));if(isset($ids[$id])&&$ids[$id]!==$hash)throw new RuntimeException('Gleiche Quellen-ID mit abweichenden Werten.');$ids[$id]=$hash;$item['source_key']=hash('sha256',$id);$item['record_hash']=$hash;$out[$id]=$item;if(count($out)>max(1,min(5000,$maxRows)))throw new RuntimeException('Bankimport-Zeilenlimit.');}fclose($f);if(!$out)throw new InvalidArgumentException('Keine Bankumsätze.');return array_values($out);
}
function bankParseStatement(string $raw,array $map,string $locale,string $delimiter,int $limit,string $format='csv'):array {
 if($format==='camt')return bankParseCamt($raw,(string)($map['expected_iban']??''),$limit);if($format!=='csv')throw new InvalidArgumentException('Unbekanntes Bankformat.');return bankParseCsv($raw,$map,$locale,$delimiter,$limit);
}
/** Bind each confirmed CAMT identity to exactly one of the two estate accounts. */
function bankAssertIbanBinding(PDO $pdo,string $target,int $id,string $hash,bool $lock=false):void {
 if($target!=='estate_bank_accounts'||!preg_match('/^[a-f0-9]{64}$/D',$hash))throw new InvalidArgumentException('Kontoidentität ungültig.');
 $rows=$pdo->query('SELECT id,import_iban_hash FROM estate_bank_accounts ORDER BY id'.($lock?' FOR UPDATE':''))->fetchAll(PDO::FETCH_ASSOC);foreach($rows as $row)if($row['import_iban_hash']&&hash_equals($row['import_iban_hash'],$hash)&&(int)$row['id']!==$id)throw new RuntimeException('Dieser Kontoauszug gehört zum anderen Immobilienkonto. Keine Vermischung der Häuser.');
}
/** Weak cross-format candidates are REVIEW items, never silently discarded or booked. */
function bankPossibleDuplicates(PDO $pdo,string $table,int $account,array $rows,bool $check):array {
 if(!$check||!$rows)return [];if($table!=='estate_bank_transactions')throw new InvalidArgumentException('Umsatztabelle ungültig.');$dates=array_column($rows,'booked_on');$q=$pdo->prepare('SELECT id,source_key,external_id,booked_on,amount_cents,currency FROM '.$table.' WHERE account_id=? AND booked_on BETWEEN ? AND ? ORDER BY id LIMIT 5001');$q->execute([$account,min($dates),max($dates)]);$existing=$q->fetchAll(PDO::FETCH_ASSOC);if(count($existing)>5000)throw new RuntimeException('Dublettenprüfung: Importzeitraum auf weniger als5000Bestandsumsätze begrenzen.');$keys=array_column($existing,null,'source_key');$groups=[];foreach($existing as $e)$groups[$e['booked_on'].':'.$e['amount_cents'].':'.$e['currency']][]=$e;$out=[];foreach($rows as $r){if(isset($keys[$r['source_key']]))continue;foreach($groups[$r['booked_on'].':'.$r['amount_cents'].':'.$r['currency']]??[] as $e){$out[]=['incoming_external'=>$r['external_id'],'existing_id'=>(int)$e['id'],'existing_external'=>$e['external_id']];if(count($out)>2000)throw new RuntimeException('Mehr als2000mögliche Dubletten: Zeitraum eingrenzen.');}}return $out;
}
function bankReviewPossible(array $preview,array $current,bool $acknowledged):void {if($current!==$preview)throw new RuntimeException('Ähnliche Umsätze seit Vorschau geändert. Neue Vorschau erforderlich.');if($current&&!$acknowledged)throw new RuntimeException('Ähnliche Umsätze mit gleicher Buchung/Betrag bei CAMT-/CSV-Wechsel ausdrücklich auf unterschiedliche Bankreferenzen prüfen.');}
/**
 * Volksbank/Raiffeisenbank (Atruvia „VR Banking“) Umsatz-CSV. Robust gegenüber Kopfzeilen-Varianten, Vorspann,
 * Windows-1252, deutschem Datum/Betrag und S/H-Kennzeichen. Kein echter Export lag bei der Entwicklung vor.
 * Da diese CSV keine Bank-Transaktions-ID enthält, wird eine stabile Kennung aus den Buchungsdaten gebildet
 * (inkl. „Saldo nach Buchung“, falls vorhanden) plus laufender Nummer für völlig identische Zeilen desselben Tages.
 * So erkennen überlappende Exporte dieselben Umsätze wieder, ohne echte gleichartige Zahlungen zu verschlucken.
 */
const BANK_VB_COLUMNS=[
 'booked_on'=>['buchungstag','buchungsdatum','buchung'],
 'value_on'=>['valutadatum','valuta','wertstellung','wertstellungsdatum'],
 'amount'=>['betrag','umsatz','betrag (eur)','betrag in eur','betrag eur'],
 'currency'=>['waehrung','währung','whrg','wkz'],
 'counterparty'=>['name zahlungsbeteiligter','empfänger/zahlungspflichtiger','empfaenger/zahlungspflichtiger','auftraggeber/empfänger','auftraggeber/empfaenger','zahlungsempfänger','zahlungspflichtiger','beguenstigter/zahlungspflichtiger','begünstigter/zahlungspflichtiger','name'],
 'counterparty_iban'=>['iban zahlungsbeteiligter','iban','kontonummer/iban','iban/kontonummer'],
 'purpose'=>['verwendungszweck','vorgang/verwendungszweck','zweck'],
 'booking_text'=>['buchungstext','umsatzart','vorgang'],
 'balance'=>['saldo nach buchung','saldo'],
 'own_iban'=>['iban auftragskonto','iban auftraggeberkonto','auftragskonto iban','iban kontoinhaber'],
 'sign'=>['soll/haben','s/h','soll-haben'],
];
function bankVbDate(string $raw):string {
 $raw=trim($raw);if(zgsValidDate($raw))return $raw;if(!preg_match('/^(\d{1,2})\.(\d{1,2})\.(\d{2}|\d{4})$/D',$raw,$m))throw new InvalidArgumentException('Datum im Kontoexport nicht lesbar: '.mb_substr($raw,0,20));
 $y=strlen($m[3])===2?2000+(int)$m[3]:(int)$m[3];$iso=sprintf('%04d-%02d-%02d',$y,(int)$m[2],(int)$m[1]);if(!zgsValidDate($iso))throw new InvalidArgumentException('Ungültiges Datum im Kontoexport.');return $iso;
}
function bankVbAmount(string $raw):int {
 $raw=str_replace(["\u{00A0}",' ','EUR','€'],'',trim($raw));if($raw==='')throw new InvalidArgumentException('Betrag fehlt.');if(preg_match('/^[+-]?\d+(\.\d{1,2})?$/D',$raw)&&!str_contains($raw,','))return financeCents($raw,'iso');return financeCents($raw,'de');
}
function bankVbNorm(string $s):string {return mb_strtolower(trim(preg_replace('/\s+/u',' ',str_replace(["\u{00A0}",'"'],' ',$s))));}
function bankLooksLikeCamt(string $raw):bool {$head=substr(ltrim($raw,"\xEF\xBB\xBF\r\n\t "),0,4096);return str_starts_with($head,'<?xml')||str_contains($head,'<Document')||str_contains($head,'camt.05');}
function bankCamtFirstIban(string $raw):string {if(!preg_match('~<(?:\w+:)?Acct>\s*<(?:\w+:)?Id>\s*<(?:\w+:)?IBAN>\s*([A-Z0-9 ]{15,40})\s*</~',$raw,$m))throw new InvalidArgumentException('Im CAMT-Auszug wurde keine Konto-IBAN gefunden.');return bankIban($m[1]);}
/** @return array{rows:array,own_iban:?string,period:array} */
function bankParseVolksbankCsv(string $raw,int $maxRows=5000):array {
 if(strlen($raw)>4*1024*1024||str_contains($raw,"\0"))throw new InvalidArgumentException('Kontoexport zu groß (maximal 4 MB) oder keine Textdatei.');
 $raw=preg_replace('/^\xEF\xBB\xBF/','',$raw);if(!mb_check_encoding($raw,'UTF-8'))$raw=mb_convert_encoding($raw,'UTF-8','Windows-1252');
 $lines=preg_split('/\r\n|\n|\r/',$raw);$headerAt=null;$delimiter=';';
 foreach(array_slice($lines,0,40,true) as $i=>$line){$low=mb_strtolower($line);if((str_contains($low,'buchungstag')||str_contains($low,'buchungsdatum'))&&(str_contains($low,'betrag')||str_contains($low,'umsatz'))){$headerAt=$i;$delimiter=substr_count($line,';')>=substr_count($line,',')?';':',';break;}}
 if($headerAt===null)throw new InvalidArgumentException('Keine Kopfzeile mit „Buchungstag“ und „Betrag“ gefunden. Bitte den CSV-Umsatzexport der Volksbank verwenden.');
 $f=fopen('php://temp','w+');fwrite($f,implode("\n",array_slice($lines,$headerAt)));rewind($f);$header=array_map('bankVbNorm',fgetcsv($f,0,$delimiter,'"','')?:[]);
 $col=[];foreach(BANK_VB_COLUMNS as $key=>$names){foreach($names as $name){$at=array_search($name,$header,true);if($at!==false){$col[$key]=$at;break;}}}
 foreach(['booked_on','amount'] as $need)if(!isset($col[$need]))throw new InvalidArgumentException('Spalte fehlt im Kontoexport: '.($need==='amount'?'Betrag':'Buchungstag').'.');
 if(isset($col['counterparty'],$col['own_iban'])&&$col['counterparty']===$col['own_iban'])unset($col['own_iban']);
 $out=[];$seen=[];$own=null;
 while(($row=fgetcsv($f,0,$delimiter,'"',''))!==false){if($row===[null]||!array_filter($row,fn($v)=>trim((string)$v)!==''))continue;$get=fn(string $k)=>isset($col[$k])?trim((string)($row[$col[$k]]??'')):'';
  if($get('booked_on')===''||preg_match('/^(summe|saldo|anfangssaldo|endsaldo|kontostand)/i',$get('booked_on')))continue;
  $booked=bankVbDate($get('booked_on'));$value=$get('value_on')!==''?bankVbDate($get('value_on')):$booked;$amount=bankVbAmount($get('amount'));$sign=mb_strtoupper($get('sign'));if($sign==='S'&&$amount>0)$amount=-$amount;
  $currency=$get('currency')!==''?mb_strtoupper($get('currency')):'EUR';if($currency!=='EUR')throw new InvalidArgumentException('Nur EUR-Konten werden unterstützt.');
  if($get('own_iban')!==''){$iban=bankIban($get('own_iban'));if($own!==null&&$own!==$iban)throw new InvalidArgumentException('Die Datei enthält Umsätze mehrerer Konten. Bitte je Konto einzeln exportieren.');$own=$iban;}
  $cpIban='';if($get('counterparty_iban')!==''){try{$cpIban=bankIban($get('counterparty_iban'));}catch(Throwable $e){$cpIban='';}}
  $purpose=mb_substr($get('purpose'),0,2000);$counterparty=mb_substr($get('counterparty'),0,500);
  $identity=json_encode([$own,$booked,$value,$amount,$counterparty,$cpIban,$purpose,$get('booking_text'),$get('balance')],JSON_UNESCAPED_UNICODE|JSON_THROW_ON_ERROR);$seen[$identity]=($seen[$identity]??0)+1;
  $external='vb-'.substr(hash('sha256',$identity),0,40).'-'.$seen[$identity];
  $item=['external_id'=>$external,'booked_on'=>$booked,'value_on'=>$value,'amount_cents'=>$amount,'currency'=>'EUR','purpose'=>$purpose,'counterparty'=>$counterparty];
  $item['record_hash']=hash('sha256',json_encode($item,JSON_THROW_ON_ERROR));$item['source_key']=hash('sha256',$external);$item['counterparty_iban_hash']=$cpIban!==''?hash('sha256',$cpIban):null;$out[]=$item;
  if(count($out)>$maxRows)throw new RuntimeException('Mehr als '.$maxRows.' Umsätze in einer Datei. Bitte kürzeren Zeitraum exportieren.');}
 fclose($f);if(!$out)throw new InvalidArgumentException('Der Kontoexport enthält keine gebuchten Umsätze.');
 $dates=array_column($out,'booked_on');return ['rows'=>$out,'own_iban'=>$own,'period'=>[min($dates),max($dates)]];
}
