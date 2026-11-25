<?php
declare(strict_types=1);
/**
 * Schema 430: Schlüsseldienst-Einsätze schnell erfassen (Ort, Art, Betrag, Zahlung, Kanal) und den Umsatz je Monat, Ort und Kanal sehen.
 * Rechnungen entstehen weiter in Lexware: Bei Zahlung „Rechnung“ landet nur eine Erinnerung in der Merkliste („Rechnung offen“).
 */
require_once __DIR__.'/platform.php';require_once __DIR__.'/leads.php';
const GSZ_LOCK_KINDS=['zugefallen'=>'Tür zugefallen','abgeschlossen'=>'Tür abgeschlossen','abgebrochen'=>'Schlüssel abgebrochen','schloss'=>'Schloss getauscht','zylinder'=>'Zylinder getauscht','sonstiges'=>'Sonstiges'];
const GSZ_LOCK_PAYMENTS=['bar'=>'Bar','karte'=>'Karte','ueberweisung'=>'Überweisung','rechnung'=>'Rechnung','offen'=>'Noch offen'];
const GSZ_LOCK_CHANNELS=['telefon'=>'Anruf','whatsapp'=>'WhatsApp','website'=>'Website','google'=>'Google','empfehlung'=>'Empfehlung','sonstiges'=>'Sonstiges'];
const GSZ_LOCK_PLACES=['Gütersloh','Rheda-Wiedenbrück','Verl','Harsewinkel','Herzebrock-Clarholz','Steinhagen','Halle (Westf.)','Rietberg','Schloß Holte-Stukenbrock','Versmold','Werther','Borgholzhausen','Langenberg','Bielefeld'];

/** „85“, „85,50“, „1.200,00“ → Cent. */
function gszEuroCents(string $v):int {
 $v=str_replace([' ','€'],'',trim($v));if($v==='')return 0;if(preg_match('/^\d{1,3}(\.\d{3})+(,\d{1,2})?$/D',$v))$v=str_replace('.','',$v);$v=str_replace(',','.',$v);
 if(!preg_match('/^\d{1,6}(\.\d{1,2})?$/D',$v))throw new RuntimeException('Betrag bitte wie 120 oder 120,50 eingeben.');return (int)round((float)$v*100);
}
function gszEuro(int $c):string {return number_format($c/100,$c%100?2:0,',','.').' €';}
function gszLockData(array $in,?string $today=null):array {
 $today??=(new DateTimeImmutable('today',new DateTimeZone('Europe/Berlin')))->format('Y-m-d');
 $date=(string)($in['job_date']??$today);$d=DateTimeImmutable::createFromFormat('!Y-m-d',$date);if(!$d||$d->format('Y-m-d')!==$date||$date>$today)throw new RuntimeException('Datum ungültig.');
 $time=trim((string)($in['job_time']??''));if($time!==''&&!preg_match('/^([01]\d|2[0-3]):[0-5]\d$/D',$time))throw new RuntimeException('Uhrzeit ungültig.');
 $kind=(string)($in['kind']??'zugefallen');$pay=(string)($in['payment']??'bar');$ch=(string)($in['channel']??'telefon');
 if(!isset(GSZ_LOCK_KINDS[$kind])||!isset(GSZ_LOCK_PAYMENTS[$pay])||!isset(GSZ_LOCK_CHANNELS[$ch]))throw new RuntimeException('Auswahl ungültig.');
 $place=gszLText($in['place']??'',120);if($place==='')throw new RuntimeException('Bitte den Ort angeben.');
 return ['job_date'=>$date,'job_time'=>$time!==''?$time.':00':null,'place'=>$place,'kind'=>$kind,'amount_cents'=>gszEuroCents((string)($in['amount']??'')),'payment'=>$pay,'channel'=>$ch,
  'customer'=>gszLText($in['customer']??'',190),'phone'=>gszLText($in['phone']??'',80),'note'=>gszLText($in['note']??'',1000)];
}
function gszLockJob(PDO $pdo,int $id):?array {$q=$pdo->prepare('SELECT j.*,n.done_at invoice_done FROM lock_jobs j LEFT JOIN quick_notes n ON n.id=j.note_id WHERE j.id=?');$q->execute([$id]);return $q->fetch(PDO::FETCH_ASSOC)?:null;}
/** Speichert einen Einsatz; bei „Rechnung“ einmalig eine Erinnerung in der Merkliste. $id=0 → neu. */
function gszLockSave(PDO $pdo,int $admin,array $in,int $id=0):int {
 $d=gszLockData($in);$lead=(int)($in['lead_id']??0)?:null;
 if($lead){$q=$pdo->prepare('SELECT id FROM leads WHERE id=?');$q->execute([$lead]);if(!$q->fetchColumn())$lead=null;}
 $pdo->beginTransaction();try{
  if($id){$old=gszLockJob($pdo,$id);if(!$old)throw new RuntimeException('Einsatz nicht gefunden.');$set=implode(',',array_map(fn($k)=>"$k=?",array_keys($d)));$pdo->prepare("UPDATE lock_jobs SET $set,row_version=row_version+1 WHERE id=?")->execute([...array_values($d),$id]);}
  else{$pdo->prepare('INSERT INTO lock_jobs('.implode(',',array_keys($d)).',lead_id) VALUES('.implode(',',array_fill(0,count($d)+1,'?')).')')->execute([...array_values($d),$lead]);$id=(int)$pdo->lastInsertId();
   if($lead)$pdo->prepare("UPDATE leads SET status='auftrag' WHERE id=? AND status IN ('neu','kontaktiert','angebot')")->execute([$lead]);}
  $j=gszLockJob($pdo,$id);
  if($d['payment']==='rechnung'&&!$j['note_id']){
   $title=mb_substr('Schlüsseldienst '.(new DateTimeImmutable($d['job_date']))->format('d.m.').' · '.$d['place'].($d['customer']!==''?' · '.$d['customer']:'').($d['amount_cents']?' · '.gszEuro($d['amount_cents']):''),0,190);
   $pdo->prepare("INSERT INTO quick_notes(kind,title,note) VALUES('invoice',?,?)")->execute([$title,mb_substr(trim(GSZ_LOCK_KINDS[$d['kind']].($d['phone']!==''?' · Tel. '.$d['phone']:'')),0,500)]);
   $pdo->prepare('UPDATE lock_jobs SET note_id=? WHERE id=?')->execute([(int)$pdo->lastInsertId(),$id]);}
  gszAudit($pdo,$admin,'lock_job_saved',['id'=>$id,'payment'=>$d['payment']]);$pdo->commit();return $id;
 }catch(Throwable $e){if($pdo->inTransaction())$pdo->rollBack();throw $e;}
}
function gszLockDelete(PDO $pdo,int $admin,int $id):void {$pdo->prepare('DELETE FROM lock_jobs WHERE id=?')->execute([$id]);gszAudit($pdo,$admin,'lock_job_deleted',['id'=>$id]);}
function gszLockReviewed(PDO $pdo,int $id):void {$pdo->prepare('UPDATE lock_jobs SET review_requested_at=UTC_TIMESTAMP() WHERE id=?')->execute([$id]);}
function gszLockJobs(PDO $pdo,string $month):array {$q=$pdo->prepare('SELECT j.*,n.done_at invoice_done FROM lock_jobs j LEFT JOIN quick_notes n ON n.id=j.note_id WHERE j.job_date BETWEEN ? AND LAST_DAY(?) ORDER BY j.job_date DESC,j.job_time DESC,j.id DESC');$q->execute([$month.'-01',$month.'-01']);return $q->fetchAll(PDO::FETCH_ASSOC);}
/** Kennzahlen eines Monats und der 12 Monate davor (für die Balken). Beträge in Cent. */
function gszLockStats(PDO $pdo,string $month):array {
 $jobs=gszLockJobs($pdo,$month);$sum=fn(array $r)=>array_sum(array_column($r,'amount_cents'));$group=function(string $k)use($jobs){$o=[];foreach($jobs as $j){$o[$j[$k]]??=['n'=>0,'c'=>0];$o[$j[$k]]['n']++;$o[$j[$k]]['c']+=(int)$j['amount_cents'];}uasort($o,fn($a,$b)=>$b['c']<=>$a['c']?:$b['n']<=>$a['n']);return $o;};
 $start=(new DateTimeImmutable($month.'-01'))->modify('-11 months')->format('Y-m-d');
 $q=$pdo->prepare("SELECT DATE_FORMAT(job_date,'%Y-%m') m,COUNT(*) n,SUM(amount_cents) c FROM lock_jobs WHERE job_date BETWEEN ? AND LAST_DAY(?) GROUP BY m ORDER BY m");$q->execute([$start,$month.'-01']);$hist=[];foreach($q->fetchAll(PDO::FETCH_ASSOC) as $r)$hist[$r['m']]=['n'=>(int)$r['n'],'c'=>(int)$r['c']];
 $months=[];for($i=11;$i>=0;$i--){$m=(new DateTimeImmutable($month.'-01'))->modify("-$i months")->format('Y-m');$months[$m]=$hist[$m]??['n'=>0,'c'=>0];}
 $open=$pdo->query("SELECT COUNT(*) n,COALESCE(SUM(j.amount_cents),0) c FROM lock_jobs j LEFT JOIN quick_notes n ON n.id=j.note_id WHERE j.payment='offen' OR (j.payment='rechnung' AND (n.id IS NULL OR n.done_at IS NULL))")->fetch(PDO::FETCH_ASSOC);
 $night=count(array_filter($jobs,fn($j)=>$j['job_time']!==null&&($j['job_time']>='22:00:00'||$j['job_time']<'06:00:00')));$weekend=count(array_filter($jobs,fn($j)=>(int)(new DateTimeImmutable($j['job_date']))->format('N')>=6));
 return ['jobs'=>$jobs,'count'=>count($jobs),'total'=>$sum($jobs),'avg'=>$jobs?intdiv($sum($jobs),count($jobs)):0,'places'=>$group('place'),'channels'=>$group('channel'),'kinds'=>$group('kind'),'payments'=>$group('payment'),'months'=>$months,'open'=>['n'=>(int)$open['n'],'c'=>(int)$open['c']],'night'=>$night,'weekend'=>$weekend];
}
