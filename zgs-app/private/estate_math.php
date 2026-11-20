<?php
declare(strict_types=1);
require_once __DIR__.'/money.php';require_once __DIR__.'/planning_engine.php';
function estateMonth(string $month):string {if(preg_match('/^\d{4}-\d{2}$/D',$month))$month.='-01';if(!zgsValidDate($month)||substr($month,8)!=='01')throw new InvalidArgumentException('Kalendermonat JJJJ-MM erforderlich.');return $month;}
function estateDays(string $from,string $to):int {if(!zgsValidDate($from)||!zgsValidDate($to)||$from>$to)throw new InvalidArgumentException('Zeitraum ungültig.');return (int)(new DateTimeImmutable($from,new DateTimeZone('Europe/Berlin')))->diff(new DateTimeImmutable($to,new DateTimeZone('Europe/Berlin')))->days+1;}
function estateRentMath(array $lease,array $terms,string $month):array {
 $month=estateMonth($month);$last=(new DateTimeImmutable($month))->format('Y-m-t');$from=max($month,$lease['starts_on']);$to=min($last,$lease['ends_on']?:$last);if($from>$to||$lease['occupancy_kind']!=='tenant')return [];

 usort($terms,fn($a,$b)=>strcmp($a['valid_from'],$b['valid_from']));$valid=[];foreach($terms as $i=>$term){$end=isset($terms[$i+1])?planDate($terms[$i+1]['valid_from'],-1):$last;$a=max($from,$term['valid_from']);$b=min($to,$end);if($a<=$b)$valid[]=['days'=>estateDays($a,$b),'term'=>$term];}
 if($lease['proration']!=='calendar_days'&&($from!==$month||$to!==$last||count($valid)!==1))throw new RuntimeException('Teilmonat/Konditionswechsel benötigt vereinbarte Tagesaufteilung oder manuelle Forderung.');
 $covered=array_sum(array_column($valid,'days'));if($covered!==estateDays($from,$to))throw new RuntimeException('Mietkonditionen decken den Zeitraum nicht vollständig.');
 $den=estateDays($month,$last);$amounts=[];foreach(['rent','operating','heating'] as $component){$numerator=0;foreach($valid as $s){$c=(int)$s['term'][$component.'_cents'];if($c<0||$c>99999999999)throw new RuntimeException('Mietbetrag außerhalb der Grenzen.');$numerator+=$c*$s['days'];}$amounts[$component.'_cents']=intdiv($numerator+intdiv($den,2),$den);}
 return $amounts+['month'=>$month,'due_on'=>max($from,substr($month,0,8).str_pad((string)$lease['due_day'],2,'0',STR_PAD_LEFT)),'covered_days'=>$covered,'month_days'=>$den];
}
function estateComponent(string $component):string {if(!in_array($component,['rent','operating','heating','deposit'],true))throw new InvalidArgumentException('Zuordnungskomponente ungültig.');return $component;}
