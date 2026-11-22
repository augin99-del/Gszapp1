<?php
declare(strict_types=1);
require dirname(__DIR__).'/private/planning_store.php';
$n=0;function pcheck(bool $b,string $s):void{global $n;$n++;if(!$b)throw new RuntimeException($s);}
$s=['start_time'=>'10:30','target_time'=>'16:00','warning_time'=>'17:00','buffer_minutes'=>10];
function task(int $id,array $extra=[]):array{return array_replace(['key'=>'t'.$id,'id'=>$id,'title'=>'Test','due_date'=>'2026-11-09','done'=>0,'duration_minutes'=>60,'property_id'=>$id,'plan_locked'=>0,'start_time'=>null,'sort_order'=>0,'priority'=>'normal','window_start'=>null,'window_end'=>null,'recurring_job_id'=>null,'scheduled_for'=>null],$extra);}
$a=task(1);$b=task(2);$r=planTimeline([$a,$b],[],$s,'10:30');pcheck($r['rows'][0]['start']===630,'10:30 start');pcheck($r['rows'][1]['start']===700&&$r['end']===760,'buffer and end');pcheck($r['buffer']===10&&$r['minutes']===120,'totals');
$app=['day'=>'2026-11-09','start_time'=>'13:00','end_time'=>'14:30'];$r=planTimeline([task(1,['duration_minutes'=>180])],[$app],$s,'10:30');pcheck($r['rows'][0]['start']===870,'avoid appointment');pcheck(count($r['warnings'])===1,'17 warning');
$r=planTimeline([task(1,['start_time'=>'13:30'])],[$app],$s,'10:30');pcheck(in_array('Konflikt mit Fixtermin.',$r['rows'][0]['warnings'],true),'explicit conflict');
$r=planTimeline([task(1,['duration_minutes'=>null])],[],$s,'10:30');pcheck($r['rows'][0]['end']===null&&count($r['warnings'])===1,'unknown explicit');
foreach([['2026-03-28',1,'2026-03-29'],['2026-10-24',1,'2026-10-25'],['2026-12-31',1,'2027-01-01']] as [$d,$o,$want])pcheck(planDate($d,$o)===$want,'DST/calendar days');
$tasks=[];for($i=1;$i<=14;$i++)$tasks[]=task($i,['recurring_job_id'=>1,'scheduled_for'=>'2026-11-09']);
$p=planWeek($tasks,[],$s,[],'2026-11-09');pcheck($p===planWeek($tasks,[],$s,[],'2026-11-09'),'deterministic');$keys=[];foreach($p['days'] as $d=>$v)foreach($v['rows'] as $row)$keys[]=$row['task']['key'];pcheck(count($keys)===14&&count(array_unique($keys))===14,'all once');pcheck(count($p['days'])===7,'seven days');
$p=planWeek([task(1,['due_date'=>'2026-11-15','plan_locked'=>1])],[],$s,[],'2026-11-09');pcheck(count($p['days']['2026-11-15']['rows'])===1,'Sunday locked');
foreach([4,5,9,10] as $m)pcheck(zgsInSeason(sprintf('2026-%02d-01',$m),5,9)===in_array($m,[5,9]),'season boundary');
pcheck(planDuration(task(1,['title'=>'Fenster reinigen','duration_minutes'=>null]))===90,'window default');pcheck(planDuration(task(1,['title'=>'Fenster reinigen','duration_minutes'=>120]))===120,'saved duration retained');
$r=planTimeline([task(1,['duration_minutes'=>340])],[],$s,'10:30');pcheck(str_contains(implode(' ',$r['warnings']),'Zielende'),'16 warning');
$r=planTimeline([task(1),task(2,['property_id'=>1])],[],$s,'10:30');pcheck($r['buffer']===0,'same object no buffer');
$p=planWeek([task(1,['due_date'=>'2026-09-30','window_end'=>'2026-10-04','season_start_month'=>5,'season_end_month'=>9,'recurring_job_id'=>1])],[],$s,[],'2026-09-28');pcheck(count($p['days']['2026-10-01']['rows'])===0,'no summer work in October');
$p=planWeek([task(1,['window_start'=>'2026-11-10'])],[],$s,[],'2026-11-09','2026-11-09');pcheck(count($p['unplaced'])===1,'day optimization respects window');
echo "Planning unit tests passed ($n assertions).\n";
