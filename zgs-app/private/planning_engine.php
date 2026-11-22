<?php
declare(strict_types=1);
require_once __DIR__.'/scheduler.php';
function planMinute(string $time):int {if(!preg_match('/^(\d{2}):([0-5]\d)(?::00)?$/D',$time,$m)||(int)$m[1]>23)throw new InvalidArgumentException('Ungültige Uhrzeit.');return (int)$m[1]*60+(int)$m[2];}
function planTime(int $min):string{return sprintf('%02d:%02d',intdiv($min,60),$min%60);}
function planDate(string $date,int $offset=0):string {if(!zgsValidDate($date))throw new InvalidArgumentException('Ungültiges Datum.');return (new DateTimeImmutable($date,new DateTimeZone('Europe/Berlin')))->modify(($offset>=0?'+':'').$offset.' days')->format('Y-m-d');}
function planFixed(array $t):bool{return !empty($t['waste_key'])||!empty($t['plan_locked'])||!empty($t['start_time'])||(int)($t['sort_order']??0)>0||($t['rule_source']??'')==='special_weekly'||preg_match('/tonne|tonnen/iu',$t['title'])===1||(!empty($t['scheduled_for'])&&$t['due_date']!==$t['scheduled_for']&&(int)($t['planned_order']??0)===0);}
function planDuration(array $t):?int {
 if((int)($t['duration_minutes']??0)>0)return (int)$t['duration_minutes'];
 $title=mb_strtolower($t['title']);
 if(str_contains($title,'tonne'))return 15;
 if(str_contains($title,'treppenhaus'))return 75;
 if(str_contains($title,'fenster')||str_contains($title,'tiefgarage'))return 90;
 if(str_contains($title,'außenanlage'))return ($t['property_seed']??'')==='froebel19'?90:120;
 return null;
}
/** Stable local-address grouping, no distance or routing claims. Replaceable strategy. */
function planArea(array $t):string {return mb_strtolower(trim((string)($t['address']??'')));}
function planCity(array $t):string {$parts=explode(',',planArea($t));return trim(preg_replace('/^\d{5}\s*/','',end($parts)));}
function planTimeline(array $tasks,array $appointments,array $settings,string $start):array {
 $cursor=planMinute($start);$occupied=[];$warnings=[];$rows=[];$minutes=0;$bufferTotal=0;$unknown=false;$last=null;
 foreach($appointments as $a){$s=planMinute($a['start_time']);$e=planMinute($a['end_time']);foreach($occupied as [$x,$y])if($s<$y&&$e>$x)$warnings[]='Fixtermine überschneiden sich.';$occupied[]=[$s,$e];}
 foreach($tasks as $t)if(empty($t['done'])&&!empty($t['start_time'])&&planDuration($t)!==null)$occupied[]=[planMinute($t['start_time']),planMinute($t['start_time'])+planDuration($t)];
 foreach($tasks as $t){
  if(!empty($t['done'])){$rows[]=['task'=>$t,'start'=>null,'end'=>null,'buffer'=>0,'duration'=>planDuration($t),'warnings'=>['Bereits erledigt; kein neuer Arbeitsblock.']];continue;}
  $duration=planDuration($t);$w=[];
  if((!empty($t['window_start'])&&$t['due_date']<$t['window_start'])||(!empty($t['window_end'])&&$t['due_date']>$t['window_end']))$w[]='Manueller Termin außerhalb des Fälligkeitsfensters.';$b=$last!==null&&$last!==($t['property_id']??null)?(int)$settings['buffer_minutes']:0;
  if(isset($t['route_minutes']))$b=(int)$t['route_minutes'];
  $s=!empty($t['start_time'])?planMinute($t['start_time']):$cursor+$b;
  if($duration===null){$unknown=true;$w[]='Dauer unbestimmt: kein belastbares Tagesende.';$e=null;}
  else{
   if(empty($t['start_time'])){do{$again=false;foreach($occupied as [$x,$y])if($s<$y&&$s+$duration>$x){$s=$y;$again=true;break;}}while($again);}
   $e=$s+$duration;
   if(!empty($t['start_time'])){if($s<$cursor+$b)$w[]='Fixierte Startzeit überschneidet Ablauf oder Fahrtpuffer.';foreach($appointments as $a)if($s<planMinute($a['end_time'])&&$e>planMinute($a['start_time']))$w[]='Konflikt mit Fixtermin.';}
   if($e>1439)$w[]='Über Mitternacht: nicht automatisch übernehmbar.';
   $cursor=max($cursor,$e);$minutes+=$duration;
  }
  $bufferTotal+=$b;$last=$t['property_id']??'without-object';$rows[]=['task'=>$t,'start'=>$s,'end'=>$e,'buffer'=>$b,'duration'=>$duration,'warnings'=>$w];
 }
 foreach($appointments as $a)$cursor=max($cursor,planMinute($a['end_time']));
 if($cursor>planMinute($settings['warning_time']))$warnings[]='Geplantes Arbeitsende '.planTime($cursor).' Uhr – '.($cursor-planMinute($settings['warning_time'])).' Minuten über gewünschtem Feierabend '.substr($settings['warning_time'],0,5).' Uhr.';
 elseif($cursor>planMinute($settings['target_time']))$warnings[]='Zielende '.substr($settings['target_time'],0,5).' Uhr: geplantes Arbeitsende '.planTime($cursor).' Uhr, '.($cursor-planMinute($settings['target_time'])).' Minuten darüber.';
 if($unknown)$warnings[]='Tagesende unvollständig: unbestimmte Arbeitsdauer.';
 return ['rows'=>$rows,'end'=>$cursor,'minutes'=>$minutes,'buffer'=>$bufferTotal,'warnings'=>array_values(array_unique($warnings))];
}
function planWeek(array $tasks,array $appointments,array $settings,array $starts,string $monday,?string $onlyDay=null):array {
 $days=[];for($i=0;$i<7;$i++)$days[planDate($monday,$i)]=[];$end=planDate($monday,6);$unplaced=[];$warnings=[];
 usort($tasks,static function($a,$b){$r=['urgent'=>0,'high'=>1,'normal'=>2,'low'=>3];return [planFixed($a)?0:1,$a['due_date']??'',(int)($a['sort_order']??0),$a['start_time']??'99:99',$r[$a['priority']??'normal'],planArea($a),(string)$a['key']]<=>[planFixed($b)?0:1,$b['due_date']??'',(int)($b['sort_order']??0),$b['start_time']??'99:99',$r[$b['priority']??'normal'],planArea($b),(string)$b['key']];});
 foreach($tasks as $t){
  $date=$t['due_date'];if(!$date){$unplaced[]=$t;$warnings[]='Aufgaben ohne Termin benötigen eine manuelle Zuordnung.';continue;}
  if($date<$monday&&!$t['done'])$warnings[]='Überfällige Aufgaben vorhanden; ursprüngliche Fälligkeit bleibt in der Vorschau sichtbar.';
  if($t['done']||planFixed($t)||($onlyDay!==null&&$date!==$onlyDay)){$chosen=$date;}
  else{
   $earliest=$date;if(isset($settings['workdays_mask'])&&!empty($t['recurring_job_id'])&&gszServiceKind($t)==='stairwell')$earliest=(new DateTimeImmutable($date))->modify('monday this week')->format('Y-m-d');$lo=max($monday,$t['window_start']?:$earliest);$hi=min($end,$t['window_end']?:planDate($date,!empty($t['recurring_job_id'])?6:0));
   if($onlyDay!==null){$lo=max($lo,$onlyDay);$hi=min($hi,$onlyDay);}
   $chosen=null;$best=null;
   for($d=$lo;$d<=$hi;$d=planDate($d,1)){
    if(!isset($days[$d])||!gszWeekdayAllowed($t,$settings,$d)||(!empty($settings['planning_from'])&&$d<$settings['planning_from']))continue;
    if(!zgsInSeason($d,isset($t['season_start_month'])?(int)$t['season_start_month']:null,isset($t['season_end_month'])?(int)$t['season_end_month']:null))continue;
    $candidate=planTimeline([...$days[$d],$t],array_values(array_filter($appointments,fn($a)=>$a['day']===$d)),$settings,$starts[$d]??$settings['start_time']);
    if(isset($settings['capacity_end_time'])&&($candidate['end']>planMinute($settings['capacity_end_time'])||planDuration($t)===null))continue;
    $last=end($days[$d]);$score=[max(0,$candidate['end']-planMinute($settings['target_time'])),$last&&($last['property_id']??null)===($t['property_id']??null)?0:1,$last&&planCity($last)===planCity($t)?0:1,$candidate['minutes'],$d];
    if($best===null||$score<$best){$best=$score;$chosen=$d;}
   }
  }
  if($chosen!==null&&isset($days[$chosen]))$days[$chosen][]=$t;else{$unplaced[]=$t;$warnings[]='Planungskonflikt: keine ausreichende Kapazität an zulässigen Tagen im Fälligkeitsfenster: '.$t['title'];}
 }
 $result=[];foreach($days as $d=>$list){$dailyAppointments=array_values(array_filter($appointments,fn($a)=>$a['day']===$d));$result[$d]=planTimeline($list,$dailyAppointments,$settings,$starts[$d]??$settings['start_time']);$result[$d]['appointments']=$dailyAppointments;if(isset($settings['workdays_mask']))foreach($result[$d]['rows'] as &$row)if(!$row['task']['done']&&gszServiceKind($row['task'])!=='waste'&&!gszWeekdayAllowed($row['task'],$settings,$d))$row['warnings'][]='Geschützter Termin außerhalb zulässiger Regeltage. Bewusste manuelle Prüfung nötig.';unset($row);if(isset($settings['capacity_end_time'])&&$result[$d]['end']>planMinute($settings['capacity_end_time']))$result[$d]['warnings'][]='Geschützte Aufgaben/Termine übersteigen die Tageskapazität. Manuell prüfen; keine automatische Verschiebung.';}
 return ['days'=>$result,'unplaced'=>$unplaced,'warnings'=>array_values(array_unique($warnings))];
}
