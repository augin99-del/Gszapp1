<?php
declare(strict_types=1);
require_once __DIR__.'/planning_engine.php';
interface GszRoutingProvider {public function id():string;public function matrix(array $points):array;}
final class GszOsrmProvider implements GszRoutingProvider {
 public function __construct(private string $endpoint) {if(!preg_match('~^https://[a-z0-9.-]+(?::[0-9]+)?(?:/[a-z0-9_-]+)*$~iD',$endpoint))throw new InvalidArgumentException('Routing-Endpunkt muss fest konfigurierte HTTPS-Adresse sein.');}
 public function id():string{return 'OSRM '.parse_url($this->endpoint,PHP_URL_HOST);}
 public function matrix(array $points):array {
  if(count($points)>25)throw new RuntimeException('Maximal 25 Routingpunkte.');$coords=[];foreach($points as $p)$coords[]=$p['longitude'].','.$p['latitude'];
  $url=$this->endpoint.'/table/v1/driving/'.implode(';',$coords).'?annotations=duration,distance';
  $ctx=stream_context_create(['http'=>['timeout'=>5,'follow_location'=>0,'max_redirects'=>0,'header'=>"Accept: application/json\r\nUser-Agent: GSZ-internal-routing/3\r\n"],'ssl'=>['verify_peer'=>true,'verify_peer_name'=>true]]);
  $f=@fopen($url,'rb',false,$ctx);if(!$f)throw new RuntimeException('Routingdienst nicht erreichbar.');$raw=stream_get_contents($f,1048577);fclose($f);if(strlen($raw)>1048576)throw new RuntimeException('Routingantwort zu groß.');$r=json_decode($raw,true,512,JSON_THROW_ON_ERROR);if(($r['code']??'')!=='Ok')throw new RuntimeException('Routingantwort nicht nutzbar.');return ['durations'=>$r['durations']??[],'distances'=>$r['distances']??[],'source'=>$this->id(),'timestamp'=>gmdate('c')];
 }
}
function routeValidateMatrix(array $m,int $n):array {
 if(empty($m['source'])||empty($m['timestamp'])||strtotime($m['timestamp'])===false)throw new RuntimeException('Routingquelle/Zeitstempel fehlen.');
 foreach(['durations','distances'] as $k){if(count($m[$k]??[])!==$n)throw new RuntimeException('Routingmatrix unvollständig.');foreach($m[$k] as $row){if(count($row)!==$n)throw new RuntimeException('Routingmatrix unvollständig.');foreach($row as $v)if(!is_numeric($v)||!is_finite((float)$v)||$v<0||$v>10000000)throw new RuntimeException('Routingmatrix enthält unbekannte/ungültige Strecke.');}}
 return $m;
}
/** Sort only flexible runs between protected anchors. No mutation, no network. */
function routeOrder(array $tasks,?array $matrix,array $indices,?int $startIndex):array {
 $result=[];$run=[];$last=$startIndex;
 $flush=static function()use(&$run,&$result,&$last,$matrix,$indices):void{
  while($run){usort($run,static function($a,$b)use($last,$matrix,$indices){$ia=$indices[$a['address']??'']??null;$ib=$indices[$b['address']??'']??null;$ca=$matrix!==null&&$last!==null&&$ia!==null?$matrix['durations'][$last][$ia]:0;$cb=$matrix!==null&&$last!==null&&$ib!==null?$matrix['durations'][$last][$ib]:0;return [$ca,planArea($a),(int)($a['planned_order']??0),(int)$a['id']]<=>[$cb,planArea($b),(int)($b['planned_order']??0),(int)$b['id']];});$t=array_shift($run);$result[]=$t;$last=$indices[$t['address']??'']??$last;}
 };
 foreach($tasks as $t){if($t['done'])continue;if(planFixed($t)){$flush();$result[]=$t;$last=$indices[$t['address']??'']??$last;}else $run[]=$t;}$flush();return $result;
}
function routeDay(array $tasks,array $appointments,array $settings,string $startTime,?array $matrix,array $indices):array {
 $warnings=['Keine Echtzeit-Verkehrslage. Anfahrten zu Terminen ohne vollständige Routingdaten unsicher.'];$start=$settings['route_start_address']??'';$end=$settings['route_end_address']??'';
 $ordered=routeOrder($tasks,$matrix,$indices,$indices[$start]??null);$legs=[];$last=$start;$meters=0;$seconds=0;
 foreach($ordered as &$t){$to=$t['address']??'';$real=$matrix!==null&&isset($indices[$last],$indices[$to]);
  if($real){$sec=(float)$matrix['durations'][$indices[$last]][$indices[$to]];$met=(float)$matrix['distances'][$indices[$last]][$indices[$to]];$t['route_minutes']=(int)ceil($sec/60);$seconds+=$sec;$meters+=$met;}
  else{$sec=null;$met=null;$t['route_minutes']=$last!==''&&$last!==$to?(int)$settings['buffer_minutes']:0;}
  $legs[]=['from'=>$last,'to'=>$to,'seconds'=>$sec,'meters'=>$met,'estimated_minutes'=>$real?null:($last!==''&&$last!==$to?(int)$settings['buffer_minutes']:0)];$last=$to;
 }unset($t);
 $blocks=$appointments;
 if($matrix!==null){foreach($blocks as &$a){$idx=$indices[$a['address']??'']??null;if($idx!==null){$incoming=0;$outgoing=0;foreach($indices as $i){$incoming=max($incoming,(int)ceil($matrix['durations'][$i][$idx]/60));$outgoing=max($outgoing,(int)ceil($matrix['durations'][$idx][$i]/60));}$a['start_time']=planTime(max(0,planMinute($a['start_time'])-$incoming));$a['end_time']=planTime(min(1439,planMinute($a['end_time'])+$outgoing));$warnings[]='Fixtermin inklusive konservativer maximaler An-/Abfahrtsreserve aus Routingmatrix blockiert.';}}unset($a);}
 $timeline=planTimeline($ordered,$blocks,$settings,$startTime);
 if($end!==''){$real=$matrix!==null&&isset($indices[$last],$indices[$end]);$sec=$real?(float)$matrix['durations'][$indices[$last]][$indices[$end]]:null;$met=$real?(float)$matrix['distances'][$indices[$last]][$indices[$end]]:null;$estimate=$last!==$end?(int)$settings['buffer_minutes']:0;$timeline['end']+=$real?(int)ceil($sec/60):$estimate;$seconds+=$sec??0;$meters+=$met??0;$legs[]=['from'=>$last,'to'=>$end,'seconds'=>$sec,'meters'=>$met,'estimated_minutes'=>$real?null:$estimate];}
 if($matrix!==null&&$appointments){
  // Reconstruct chronological travel including every appointment address. The
  // timeline already reserves conservative matrix-based approach/departure slots.
  $visits=[];foreach($timeline['rows'] as $row)if($row['start']!==null)$visits[]=['at'=>$row['start'],'address'=>$row['task']['address']];
  foreach($appointments as $a)$visits[]=['at'=>planMinute($a['start_time']),'address'=>$a['address']];
  usort($visits,fn($a,$b)=>[$a['at'],$a['address']]<=>[$b['at'],$b['address']]);
  if($end!=='')$visits[]=['at'=>PHP_INT_MAX,'address'=>$end];$legs=[];$meters=0;$seconds=0;$previous=$start;
  foreach($visits as $visit){$to=$visit['address'];$sec=(float)$matrix['durations'][$indices[$previous]][$indices[$to]];$met=(float)$matrix['distances'][$indices[$previous]][$indices[$to]];$legs[]=['from'=>$previous,'to'=>$to,'seconds'=>$sec,'meters'=>$met,'estimated_minutes'=>null];$meters+=$met;$seconds+=$sec;$previous=$to;}
  $warnings[]='Fahrabschnitte enthalten Fixtermine; Endzeit bleibt konservativ mit maximaler An-/Abfahrtsreserve.';
 }
 if($timeline['end']>planMinute($settings['warning_time']))$timeline['warnings'][]='Warnschwelle nach Heim-/Zielfahrt überschritten.';elseif($timeline['end']>planMinute($settings['target_time']))$timeline['warnings'][]='Zielende nach Zielfahrt überschritten.';
 return ['timeline'=>$timeline,'legs'=>$legs,'mode'=>$matrix===null?'Offline-Heuristik / Schätzpuffer':'Routinggestützte Heuristik','source'=>$matrix['source']??null,'timestamp'=>$matrix['timestamp']??null,'meters'=>$matrix===null?null:$meters,'seconds'=>$matrix===null?null:$seconds,'warnings'=>$warnings];
}
