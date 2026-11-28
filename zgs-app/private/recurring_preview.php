<?php
declare(strict_types=1);
/**
 * Vorgesehene Termine regelmäßiger Arbeiten (z. B. „Treppenhausreinigung · wöchentlich“) für kommende Tage – nur zur Anzeige.
 * Als echte Aufgabe entstehen sie wie bisher erst am Fälligkeitstag (zgsGenerateDueTasks). So bleibt nichts verwaist,
 * wenn eine Regel geändert oder pausiert wird. Rückgabe: [Datum => [['job_id','title','property_id','property_name','duration_minutes'] …]].
 */
require_once __DIR__.'/scheduler.php';require_once __DIR__.'/recurrence_policy.php';
function zgsUpcomingOccurrences(PDO $pdo,string $from,string $to,?string $today=null):array {
 $today??=(new DateTimeImmutable('today',new DateTimeZone('Europe/Berlin')))->format('Y-m-d');
 $start=max($from,(new DateTimeImmutable($today))->modify('+1 day')->format('Y-m-d'));if($start>$to)return [];
 $paused=zgsWasteSpecialPolicies($pdo);$out=[];
 $jobs=$pdo->query("SELECT j.*,p.name property_name FROM recurring_jobs j LEFT JOIN properties p ON p.id=j.property_id WHERE j.active=1 AND j.rule_source<>'routine_unverified' AND j.frequency<>'seasonal' AND j.next_due_date IS NOT NULL ORDER BY j.id LIMIT 500")->fetchAll(PDO::FETCH_ASSOC);
 $exists=$pdo->prepare('SELECT 1 FROM tasks WHERE recurring_job_id=? AND scheduled_for=?');$open=$pdo->prepare('SELECT COUNT(*) FROM tasks WHERE recurring_job_id=? AND done=0');
 foreach($jobs as $job){
  if(zgsWasteSpecialBlocked($job,$paused))continue;
  $season=fn(string $d)=>zgsInSeason($d,$job['season_start_month']===null?null:(int)$job['season_start_month'],$job['season_end_month']===null?null:(int)$job['season_end_month']);
  $item=['job_id'=>(int)$job['id'],'title'=>(string)$job['title'],'property_id'=>$job['property_id']===null?null:(int)$job['property_id'],'property_name'=>(string)($job['property_name']??''),'duration_minutes'=>$job['duration_minutes']===null?null:(int)$job['duration_minutes']];
  if(gszRecurrenceMode($job)==='completion'){
   $open->execute([$job['id']]);$d=(string)$job['next_due_date'];if(!(int)$open->fetchColumn()&&$d>=$start&&$d<=$to&&$season($d))$out[$d][]=$item;continue;}
  $d=(string)$job['next_due_date'];$anchor=(int)($job['anchor_day']??substr($d,8,2));
  for($i=0;$i<400&&$d<=$to;$i++){
   if($d>=$start&&$season($d)){$exists->execute([$job['id'],$d]);if(!$exists->fetchColumn())$out[$d][]=$item;}
   $d=zgsAdvanceDate($d,(string)$job['frequency'],$anchor);}
 }
 ksort($out);foreach($out as &$l)usort($l,fn($a,$b)=>[$a['property_name'],$a['title']]<=>[$b['property_name'],$b['title']]);unset($l);return $out;
}
