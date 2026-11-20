<?php
declare(strict_types=1);
/** Tagesansicht als ruhige Blockliste: Objektname groß, Tätigkeit klein, ein Tipp erledigt, ein Tipp verschiebt. */
require_once __DIR__.'/planning_engine.php';require_once __DIR__.'/operations.php';
const GSZ_WEEKDAYS=['','Montag','Dienstag','Mittwoch','Donnerstag','Freitag','Samstag','Sonntag'];
const GSZ_MONTHS=['','Januar','Februar','März','April','Mai','Juni','Juli','August','September','Oktober','November','Dezember'];
function gszDayLabel(string $day,bool $withYear=false):string {$d=new DateTimeImmutable($day);return GSZ_WEEKDAYS[(int)$d->format('N')].', '.(int)$d->format('j').'. '.GSZ_MONTHS[(int)$d->format('n')].($withYear?' '.$d->format('Y'):'');}
function gszRelativeDay(string $day,?string $today=null):string {$today??=date('Y-m-d');return match(true){$day===$today=>'Heute',$day===planDate($today,1)=>'Morgen',$day===planDate($today,-1)=>'Gestern',default=>gszDayLabel($day)};}
/** Blöcke eines Tages in Planungsreihenfolge (gleiche Reihenfolge wie die bisherige Tagesplanung). */
function gszDayBlocks(PDO $pdo,string $day):array {
 $q=$pdo->prepare('SELECT t.*,p.name property_name FROM tasks t LEFT JOIN properties p ON p.id=t.property_id WHERE t.due_date=? ORDER BY t.done,(t.sort_order=0),t.sort_order,t.planned_order,t.start_time IS NULL,t.start_time,t.id LIMIT 300');$q->execute([$day]);$tasks=$q->fetchAll(PDO::FETCH_ASSOC);
 $a=$pdo->prepare('SELECT * FROM planning_appointments WHERE day=?'.(opsReady($pdo)?' AND cancelled_at IS NULL':'').' ORDER BY start_time,id');$a->execute([$day]);$apps=$a->fetchAll(PDO::FETCH_ASSOC);
 $open=array_values(array_filter($tasks,fn($t)=>!$t['done']));$done=array_values(array_filter($tasks,fn($t)=>$t['done']));return ['open'=>$open,'done'=>$done,'appointments'=>$apps];
}
function gszBlockForm(string $action,array $t,string $return,string $extra=''):string {
 return '<input type="hidden" name="csrf" value="'.e(csrf()).'"><input type="hidden" name="action" value="'.e($action).'"><input type="hidden" name="task_id" value="'.(int)$t['id'].'"><input type="hidden" name="row_version" value="'.(int)($t['row_version']??0).'"><input type="hidden" name="return" value="'.e($return).'">'.$extra;
}
function gszRenderBlock(array $t,string $return):string {
 $done=(bool)$t['done'];$obj=$t['property_name']?:'Ohne Objekt';$when=$t['start_time']?'<span class="when">'.e(substr((string)$t['start_time'],0,5)).'</span>':'';
 $move='';if(!$done){$tomorrow=planDate((string)$t['due_date'],1);$move='<details class="move"><summary>Verschieben</summary><div class="move-sheet"><form method="post" action="planner.php">'.gszBlockForm('move',$t,$return,'<input type="hidden" name="to_date" value="'.e($tomorrow).'">').'<button class="small">Auf morgen</button></form><form method="post" action="planner.php">'.gszBlockForm('move',$t,$return).'<label class="sr-only" for="mv'.(int)$t['id'].'">Neues Datum</label><input id="mv'.(int)$t['id'].'" type="date" name="to_date" value="'.e($tomorrow).'" required><button class="small">Verschieben</button></form></div></details>';}
 return '<article class="block'.($done?' is-done':'').'"><div class="what"><a class="obj" href="task.php?id='.(int)$t['id'].'">'.e($obj).'</a><span class="act">'.$when.e($t['title']).'</span>'.$move.'</div><div class="acts"><form method="post" action="planner.php">'.gszBlockForm('toggle',$t,$return,'<input type="hidden" name="done" value="'.($done?'0':'1').'">').'<button class="check" aria-label="'.e(($done?'Wieder öffnen: ':'Erledigt: ').$obj).'" title="'.($done?'Wieder öffnen':'Erledigt').'">'.gszNavIcon('check').'</button></form></div></article>';
}
function gszRenderDay(PDO $pdo,string $day,string $return):string {
 $b=gszDayBlocks($pdo,$day);$html='';
 if($b['appointments']){$html.='<div class="blocks">';foreach($b['appointments'] as $a)$html.='<article class="block is-appointment"><div class="what"><span class="obj">'.e($a['title']).'</span><span class="act"><span class="when">'.e(substr($a['start_time'],0,5).'–'.substr($a['end_time'],0,5)).'</span>Termin</span></div></article>';$html.='</div>';}
 if($b['open']){$html.='<div class="blocks">';foreach($b['open'] as $t)$html.=gszRenderBlock($t,$return);$html.='</div>';}
 elseif($b['done']||!$b['appointments'])$html.='<div class="empty"><strong>'.($b['done']?'Für '.($day===date('Y-m-d')?'heute':'diesen Tag').' ist alles erledigt.':'Nichts geplant.').'</strong>'.($b['done']?'Gute Arbeit.':'Für diesen Tag stehen keine Aufgaben an.').'</div>';
 if($b['done']){$html.='<details class="card"><summary>Erledigt ('.count($b['done']).')</summary><div class="blocks">';foreach($b['done'] as $t)$html.=gszRenderBlock($t,$return);$html.='</div></details>';}
 return $html;
}
