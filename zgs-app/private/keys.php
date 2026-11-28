<?php
declare(strict_types=1);
/**
 * Schema 460: Schlüsselverwaltung. Je Objekt: welche Schlüssel, wo sie liegen, wer sie gerade hat.
 * Jede Bewegung (erhalten, ausgegeben, zurück, an Kunden zurückgegeben, verloren, wiedergefunden) steht im Verlauf.
 * Übergabe-Quittung als PDF (vom Kunden erhalten / an Kunden zurückgegeben / an Mitarbeiter ausgegeben).
 * Auf Schlüsseln wird bewusst keine Objektanschrift vermerkt – nur eine interne Nummer.
 */
require_once __DIR__.'/leads.php';
const GSZ_KEY_STATE=['da'=>['Im Büro','ok'],'ausgegeben'=>['Unterwegs','part'],'zurueckgegeben'=>['An Kunden zurück','wait'],'verloren'=>['Verloren','bad']];
const GSZ_KEY_ACTION=['erhalten'=>'Vom Kunden erhalten','ausgegeben'=>'Ausgegeben an','zurueck'=>'Zurück ins Büro','an_kunden'=>'An Kunden zurückgegeben','verloren'=>'Als verloren gemeldet','gefunden'=>'Wiedergefunden'];

function gszKeysReady(PDO $pdo):bool {try{return (bool)$pdo->query("SELECT COUNT(*) FROM app_migrations WHERE version='460_angebote'")->fetchColumn();}catch(Throwable $e){return false;}}
function gszKeyGet(PDO $pdo,int $id):?array {$q=$pdo->prepare('SELECT k.*,p.name property_name,p.address property_address FROM key_items k LEFT JOIN properties p ON p.id=k.property_id WHERE k.id=?');$q->execute([$id]);return $q->fetch(PDO::FETCH_ASSOC)?:null;}
function gszKeysForProperty(PDO $pdo,int $property,bool $withReturned=false):array {$q=$pdo->prepare('SELECT * FROM key_items WHERE property_id=?'.($withReturned?'':" AND state<>'zurueckgegeben'").' ORDER BY state=\'zurueckgegeben\',label,id');$q->execute([$property]);return $q->fetchAll(PDO::FETCH_ASSOC);}
function gszKeyText(array $in,string $k,int $max,bool $req=false,string $label=''):string {$v=trim(preg_replace('/[\x00-\x08\x0B\x0C\x0E-\x1F\x7F]/u','',(string)($in[$k]??''))??'');if(mb_strlen($v)>$max)throw new InvalidArgumentException(($label?:$k).' ist zu lang.');if($req&&$v==='')throw new InvalidArgumentException(($label?:$k).' fehlt.');return $v;}
/** Nächste freie interne Nummer S-001, S-002 … */
function gszKeyNextNo(PDO $pdo):string {$max=0;foreach($pdo->query("SELECT key_no FROM key_items WHERE key_no LIKE 'S-%'")->fetchAll(PDO::FETCH_COLUMN) as $n)if(preg_match('/^S-(\d+)$/',(string)$n,$m))$max=max($max,(int)$m[1]);return 'S-'.str_pad((string)($max+1),3,'0',STR_PAD_LEFT);}
function gszKeyMove(PDO $pdo,?int $admin,int $key,string $action,string $person='',string $note=''):void {$pdo->prepare('INSERT INTO key_moves(key_id,action,person,note,admin_id) VALUES(?,?,?,?,?)')->execute([$key,$action,mb_substr($person,0,190),mb_substr($note,0,500),$admin]);}
/** Neuer Schlüssel (vom Kunden erhalten). */
function gszKeyAdd(PDO $pdo,int $admin,array $in):int {
 $prop=(int)($in['property_id']??0);if($prop){$q=$pdo->prepare('SELECT COUNT(*) FROM properties WHERE id=?');$q->execute([$prop]);if(!(int)$q->fetchColumn())throw new RuntimeException('Objekt nicht gefunden.');}
 $label=gszKeyText($in,'label',190,true,'Bezeichnung');$no=gszKeyText($in,'key_no',60);if($no==='')$no=gszKeyNextNo($pdo);
 $q=$pdo->prepare("SELECT COUNT(*) FROM key_items WHERE key_no=? AND state<>'zurueckgegeben'");$q->execute([$no]);if((int)$q->fetchColumn())throw new RuntimeException('Die Nummer '.$no.' ist schon vergeben.');
 $copies=max(1,min(50,(int)($in['copies']??1)));$from=gszKeyText($in,'from',190);$recv=gszContractDateSafe($in,'received_on')??date('Y-m-d');
 $pdo->prepare('INSERT INTO key_items(property_id,label,key_no,copies,storage,received_on,notes) VALUES(?,?,?,?,?,?,?)')->execute([$prop?:null,$label,$no,$copies,gszKeyText($in,'storage',190),$recv,gszKeyText($in,'notes',1000)]);
 $id=(int)$pdo->lastInsertId();gszKeyMove($pdo,$admin,$id,'erhalten',$from,$copies>1?$copies.' Stück':'');gszAudit($pdo,$admin,'key_added',['key'=>$id,'property'=>$prop]);return $id;
}
function gszContractDateSafe(array $in,string $k):?string {$v=trim((string)($in[$k]??''));if($v==='')return null;if(!preg_match('/^\d{4}-\d{2}-\d{2}$/D',$v)||!checkdate((int)substr($v,5,2),(int)substr($v,8,2),(int)substr($v,0,4)))throw new InvalidArgumentException('Datum ungültig.');return $v;}
function gszKeyUpdate(PDO $pdo,int $admin,int $id,array $in):void {
 $k=gszKeyGet($pdo,$id);if(!$k)throw new RuntimeException('Schlüssel nicht gefunden.');$no=gszKeyText($in,'key_no',60,true,'Nummer');
 $q=$pdo->prepare("SELECT COUNT(*) FROM key_items WHERE key_no=? AND id<>? AND state<>'zurueckgegeben'");$q->execute([$no,$id]);if((int)$q->fetchColumn())throw new RuntimeException('Die Nummer '.$no.' ist schon vergeben.');
 $pdo->prepare('UPDATE key_items SET label=?,key_no=?,copies=?,storage=?,notes=?,row_version=row_version+1 WHERE id=?')->execute([gszKeyText($in,'label',190,true,'Bezeichnung'),$no,max(1,min(50,(int)($in['copies']??1))),gszKeyText($in,'storage',190),gszKeyText($in,'notes',1000),$id]);gszAudit($pdo,$admin,'key_updated',['key'=>$id]);
}
/** Bewegung: ausgegeben (an Person), zurueck, an_kunden, verloren, gefunden. */
function gszKeyAction(PDO $pdo,int $admin,int $id,string $action,array $in=[]):void {
 $k=gszKeyGet($pdo,$id);if(!$k)throw new RuntimeException('Schlüssel nicht gefunden.');$person=gszKeyText($in,'person',190);$note=gszKeyText($in,'note',500);
 $allowed=['ausgegeben'=>['da'],'zurueck'=>['ausgegeben'],'an_kunden'=>['da','ausgegeben'],'verloren'=>['da','ausgegeben'],'gefunden'=>['verloren']];
 if(!isset($allowed[$action]))throw new RuntimeException('Aktion unbekannt.');if(!in_array($k['state'],$allowed[$action],true))throw new RuntimeException('Das geht bei „'.GSZ_KEY_STATE[$k['state']][0].'“ nicht.');
 if($action==='ausgegeben'&&$person==='')throw new InvalidArgumentException('An wen wird der Schlüssel ausgegeben?');
 [$state,$holder]=match($action){'ausgegeben'=>['ausgegeben',$person],'zurueck','gefunden'=>['da',''],'an_kunden'=>['zurueckgegeben',''],'verloren'=>['verloren',$k['holder']]};
 $pdo->prepare('UPDATE key_items SET state=?,holder=?,holder_since=IF(?=\'\',NULL,NOW()),row_version=row_version+1 WHERE id=?')->execute([$state,$holder,$holder,$id]);
 gszKeyMove($pdo,$admin,$id,$action,$action==='zurueck'?(string)$k['holder']:$person,$note);gszAudit($pdo,$admin,'key_'.$action,['key'=>$id]);
}
function gszKeyMoves(PDO $pdo,int $id,int $limit=30):array {$q=$pdo->prepare('SELECT * FROM key_moves WHERE key_id=? ORDER BY moved_at DESC,id DESC LIMIT '.max(1,min(200,$limit)));$q->execute([$id]);return $q->fetchAll(PDO::FETCH_ASSOC);}
/** Liste mit Filter: alle aktiven | unterwegs | verloren | zurueckgegeben; Suche in Bezeichnung, Nummer, Objekt, Person. */
function gszKeysList(PDO $pdo,string $filter='',string $search=''):array {
 $w=match($filter){'unterwegs'=>"k.state='ausgegeben'",'verloren'=>"k.state='verloren'",'zurueck'=>"k.state='zurueckgegeben'",default=>"k.state<>'zurueckgegeben'"};$args=[];
 if($search!==''){$w.=' AND (k.label LIKE ? OR k.key_no LIKE ? OR p.name LIKE ? OR k.holder LIKE ? OR k.storage LIKE ?)';$s='%'.$search.'%';$args=[$s,$s,$s,$s,$s];}
 $q=$pdo->prepare('SELECT k.*,p.name property_name FROM key_items k LEFT JOIN properties p ON p.id=k.property_id WHERE '.$w.' ORDER BY p.name IS NULL,p.name,k.label,k.id LIMIT 1000');$q->execute($args);return $q->fetchAll(PDO::FETCH_ASSOC);
}
function gszKeyCounts(PDO $pdo):array {$c=['da'=>0,'ausgegeben'=>0,'zurueckgegeben'=>0,'verloren'=>0];foreach($pdo->query('SELECT state,COUNT(*) n FROM key_items GROUP BY state')->fetchAll(PDO::FETCH_ASSOC) as $r)$c[$r['state']]=(int)$r['n'];return $c;}
/** Hinweise: Schlüssel seit über 2 Tagen unterwegs; verlorene; Vertrag beendet/gekündigt aber Schlüssel noch da. */
function gszKeyAttention(PDO $pdo,?DateTimeImmutable $now=null):array {
 if(!gszKeysReady($pdo))return [];$now??=new DateTimeImmutable('now');$out=[];
 $q=$pdo->prepare("SELECT k.*,p.name property_name FROM key_items k LEFT JOIN properties p ON p.id=k.property_id WHERE k.state='ausgegeben' AND k.holder_since<? ORDER BY k.holder_since LIMIT 10");$q->execute([$now->modify('-2 days')->format('Y-m-d H:i:s')]);
 foreach($q->fetchAll(PDO::FETCH_ASSOC) as $k)$out[]=['schluessel.php?f=unterwegs#k'.(int)$k['id'],'Schlüssel '.$k['key_no'].' ('.($k['property_name']??$k['label']).') seit '.(new DateTimeImmutable((string)$k['holder_since']))->format('d.m.').' bei '.$k['holder']];
 $n=(int)$pdo->query("SELECT COUNT(*) FROM key_items WHERE state='verloren'")->fetchColumn();if($n)$out[]=['schluessel.php?f=verloren',$n===1?'1 Schlüssel als verloren gemeldet – Kunden informieren':$n.' Schlüssel als verloren gemeldet – Kunden informieren'];
 try{foreach($pdo->query("SELECT DISTINCT p.id,p.name FROM property_contracts c JOIN properties p ON p.id=c.property_id JOIN key_items k ON k.property_id=p.id AND k.state IN('da','ausgegeben') WHERE c.status='beendet' OR (c.status='gekuendigt' AND c.ends_on<=CURDATE()+INTERVAL 14 DAY) LIMIT 10")->fetchAll(PDO::FETCH_ASSOC) as $p)$out[]=['property.php?id='.(int)$p['id'].'#schluessel','Vertrag '.$p['name'].' endet – Schlüssel an den Kunden zurückgeben'];}catch(Throwable $e){}
 return $out;
}
/** Namen für die Auswahl „Ausgegeben an“: aktive Mitarbeiter. */
function gszKeyPeople(PDO $pdo):array {try{return $pdo->query('SELECT name FROM operations_workers WHERE active=1 ORDER BY name')->fetchAll(PDO::FETCH_COLUMN);}catch(Throwable $e){return [];}}
/**
 * Quittung als PDF. $kind: erhalten (Kunde → wir), an_kunden (wir → Kunde), ausgegeben (Büro → Mitarbeiter).
 * $keys: Liste von key_items-Zeilen (eines Objekts).
 */
function gszKeyReceiptPdf(PDO $pdo,array $keys,string $kind,string $person=''):string {
 require_once __DIR__.'/service_pdf.php';if(!$keys)throw new RuntimeException('Keine Schlüssel ausgewählt.');$prop=null;
 if($keys[0]['property_id']){$q=$pdo->prepare('SELECT p.*,c.name customer_name FROM properties p LEFT JOIN customers c ON c.id=p.customer_id WHERE p.id=?');$q->execute([(int)$keys[0]['property_id']]);$prop=$q->fetch(PDO::FETCH_ASSOC)?:null;}
 $co=gszSetting($pdo,'company');$title=['erhalten'=>'Schlüsselübergabe – Empfangsbestätigung','an_kunden'=>'Schlüsselrückgabe – Empfangsbestätigung','ausgegeben'=>'Schlüsselausgabe an Mitarbeiter'][$kind]??'Schlüsselquittung';
 [$giver,$taker]=match($kind){'erhalten'=>[$person!==''?$person:($prop['customer_name']??'Auftraggeber'),$co],'an_kunden'=>[$co,$person!==''?$person:($prop['customer_name']??'Auftraggeber')],default=>[$co.' (Büro)',$person!==''?$person:'Mitarbeiter']};
 $p=gszPdfNew($co.' · Schlüsselquittung');$p->AddPage();$grey=[100,106,112];$green=[47,138,43];
 $p->para(5,$co.' · '.str_replace("\n",' · ',gszSetting($pdo,'company_address')),8,'L',$grey);$p->Ln(8);$p->para(8,$title,16);$p->para(5,'Datum: '.(new DateTimeImmutable('today'))->format('d.m.Y'),10,'L',$grey);$p->Ln(4);
 if($prop){$p->para(5,'Objekt',8,'L',$grey);$p->para(5.4,trim($prop['name'].', '.$prop['address'],', '),11);$p->Ln(3);}
 $p->para(5,'Übergeben von',8,'L',$grey);$p->para(5.4,$giver,11);$p->Ln(2);$p->para(5,'Empfangen von',8,'L',$grey);$p->para(5.4,$taker,11);$p->Ln(5);
 $p->SetFillColor(234,244,232);$p->SetFont('DejaVu','',9);$p->Cell(28,7,'Nummer',0,0,'L',true);$p->Cell(110,7,'Schlüssel',0,0,'L',true);$p->Cell(36,7,'Anzahl',0,1,'R',true);
 $sum=0;foreach($keys as $k){$sum+=(int)$k['copies'];$p->SetFont('DejaVu','',10);$p->Cell(28,7,$p->safe((string)$k['key_no']),'B',0);$p->Cell(110,7,$p->safe((string)$k['label']),'B',0);$p->Cell(36,7,(string)(int)$k['copies'],'B',1,'R');}
 $p->Cell(138,7,'Summe',0,0,'R');$p->Cell(36,7,(string)$sum,0,1,'R');$p->Ln(5);
 $text=match($kind){'erhalten'=>'Der Auftragnehmer bestätigt den Empfang der aufgeführten Schlüssel. Er verwahrt sie sicher und ohne Kennzeichnung der Objektanschrift und gibt sie bei Vertragsende unaufgefordert zurück. Ein Verlust wird unverzüglich gemeldet.','an_kunden'=>'Der Auftraggeber bestätigt den Empfang der aufgeführten Schlüssel. Damit sind alle in dieser Quittung genannten Schlüssel zurückgegeben.',default=>'Der Mitarbeiter bestätigt den Empfang der aufgeführten Schlüssel. Er bewahrt sie sicher auf, gibt sie nicht an Dritte weiter und bringt sie nach dem Einsatz unverzüglich ins Büro zurück. Ein Verlust ist sofort zu melden.'};
 $p->para(5,$text,10);$p->Ln(16);$y=$p->GetY();$p->SetDrawColor(150,150,150);
 foreach([[18,'Übergeben'],[112,'Empfangen']] as [$x,$who]){$p->Line($x,$y,$x+80,$y);$p->SetXY($x,$y+1);$p->SetFont('DejaVu','',8.5);$p->SetTextColor(...$grey);$p->Cell(80,4,$p->safe('Ort, Datum, Unterschrift – '.$who),0,0);$p->SetTextColor(0,0,0);}
 return $p->Output('S');
}
