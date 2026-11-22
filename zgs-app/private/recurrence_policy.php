<?php
declare(strict_types=1);
function gszV1Ready(PDO $pdo):bool {return (bool)$pdo->query("SELECT COUNT(*) FROM app_migrations WHERE version='340_v1'")->fetchColumn();}
function gszServiceKind(array $row):string {
 $kind=(string)($row['service_kind']??'auto');if(in_array($kind,['stairwell','outdoor','window','garage','waste','general'],true))return $kind;
 $s=mb_strtolower((string)($row['title']??''));return match(true){str_contains($s,'treppenhaus')=>'stairwell',str_contains($s,'fenster')=>'window',str_contains($s,'tiefgarage')=>'garage',str_contains($s,'tonne')=>'waste',str_contains($s,'außenanlage')||str_contains($s,'aussenanlage')||str_contains($s,'grünpflege')=>'outdoor',default=>'general'};
}
function gszRecurrenceMode(array $row):string {
 if(!array_key_exists('recurrence_mode',$row))return 'calendar';
 if(in_array($row['recurrence_mode']??'',['calendar','completion'],true))return $row['recurrence_mode'];
 return in_array(gszServiceKind($row),['window','garage'],true)&&($row['frequency']??'')==='quarterly'?'completion':'calendar';
}
function gszCompletionSchedule(PDO $pdo,array $task,string $day,int $version):void {
 if(!$task['recurring_job_id']||!gszV1Ready($pdo))return;
 $s=$pdo->prepare('SELECT * FROM recurring_jobs WHERE id=? FOR UPDATE');$s->execute([$task['recurring_job_id']]);$job=$s->fetch();if(!$job||gszRecurrenceMode($job)!=='completion')return;
 $next=zgsAdvanceDate($day,$job['frequency'],(int)substr($day,8,2));
 $pdo->prepare('INSERT INTO recurring_completions(task_id,job_id,task_version,completed_on,next_due_date) VALUES(?,?,?,?,?)')->execute([$task['id'],$job['id'],$version,$day,$next]);
 if(!$job['last_completed_on']||$day>=$job['last_completed_on'])$pdo->prepare('UPDATE recurring_jobs SET last_completed_on=?,completion_task_id=?,next_due_date=?,anchor_day=? WHERE id=?')->execute([$day,$task['id'],$next,(int)substr($day,8,2),$job['id']]);
}
function gszWeekdayAllowed(array $task,array $settings,string $day):bool {
 if(!array_key_exists('workdays_mask',$settings))return true; // phase2 historical fixtures
 $mask=(int)$settings['workdays_mask'];if(gszServiceKind($task)==='stairwell')$mask&=7;
 if(isset($task['allowed_weekdays_mask']))$mask&=(int)$task['allowed_weekdays_mask'];
 return (bool)($mask&(1<<((int)(new DateTimeImmutable($day))->format('N')-1)));
}
