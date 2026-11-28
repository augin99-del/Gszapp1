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
function gszRenderBlock(array $t,string $return,string $extra=''):string {
 $done=(bool)$t['done'];$obj=$t['property_name']?:'Ohne Objekt';$when=$t['start_time']?'<span class="when">'.e(substr((string)$t['start_time'],0,5)).'</span>':'';
 $move='';if(!$done){$tomorrow=planDate((string)$t['due_date'],1);$move='<details class="move"><summary>Verschieben</summary><div class="move-sheet"><form method="post" action="planner.php">'.gszBlockForm('move',$t,$return,'<input type="hidden" name="to_date" value="'.e($tomorrow).'">').'<button class="small">Auf morgen</button></form><form method="post" action="planner.php">'.gszBlockForm('move',$t,$return).'<label class="sr-only" for="mv'.(int)$t['id'].'">Neues Datum</label><input id="mv'.(int)$t['id'].'" type="date" name="to_date" value="'.e($tomorrow).'" required><button class="small">Verschieben</button></form></div></details>';}
 $k=mb_strtolower((string)$t['title']);$kind=preg_match('/tonne|müll|abfall|abfuhr|biotonne|papier/u',$k)?'waste':(preg_match('/winter|streu|räum|glätte/u',$k)?'winter':(preg_match('/außen|grün|rasen|hecke|laub|garten|beet/u',$k)?'outdoor':(preg_match('/treppen|reinig|fenster|glas|wisch|putz/u',$k)?'clean':'work')));
 return '<article class="block'.($done?' is-done':'').'" data-kind="'.$kind.'"><div class="what"><a class="obj" href="task.php?id='.(int)$t['id'].'">'.e($obj).'</a><span class="act">'.$when.e($t['title']).'</span>'.$extra.$move.'</div><div class="acts"><form method="post" action="planner.php">'.gszBlockForm('toggle',$t,$return,'<input type="hidden" name="done" value="'.($done?'0':'1').'">').'<button class="check" aria-label="'.e(($done?'Wieder öffnen: ':'Erledigt: ').$obj).'" title="'.($done?'Wieder öffnen':'Erledigt').'">'.gszNavIcon('check').'</button></form></div></article>';
}
function gszRenderDay(PDO $pdo,string $day,string $return):string {
 $b=gszDayBlocks($pdo,$day);$html='';
 if($b['appointments']){$html.='<div class="blocks">';foreach($b['appointments'] as $a)$html.='<article class="block is-appointment"><div class="what"><span class="obj">'.e($a['title']).'</span><span class="act"><span class="when">'.e(substr($a['start_time'],0,5).'–'.substr($a['end_time'],0,5)).'</span>Termin</span></div></article>';$html.='</div>';}
 $take=[];try{require_once __DIR__.'/notes_view.php';$take=gszNotesTakeAlong($pdo,$day,array_column($b['open'],'property_id'));}catch(Throwable $e){}
 if($take){$mat=[];foreach($take as $list)foreach($list as $n)if($n['material']!==''&&!$n['material_done'])$mat[]='<li>'.e($n['material']).' <span class="muted">· '.e($n['property_name']).'</span></li>';
  if($mat)$html.='<section class="card take-material"><h2>'.($day===date('Y-m-d')?'Für heute mitnehmen':'Für diesen Tag mitnehmen').'</h2><ul>'.implode('',$mat).'</ul><a href="notes.php?view=einkauf">Einkaufsliste</a></section>';}
 $pre=[];if($day>date('Y-m-d')){try{require_once __DIR__.'/recurring_preview.php';$pre=zgsUpcomingOccurrences($pdo,$day,$day)[$day]??[];}catch(Throwable $e){}}
 if($b['open']){$html.='<div class="blocks">';$seen=[];foreach($b['open'] as $t){$pid=(int)($t['property_id']??0);$extra='';if($pid&&!empty($take[$pid])&&!isset($seen[$pid])){$seen[$pid]=true;$extra=gszRenderTakeAlong($take[$pid],$return);gszNotesShownAtBlocks(array_column($take[$pid],'id'));}$html.=gszRenderBlock($t,$return,$extra);}$html.='</div>';}
 elseif(!$pre&&($b['done']||!$b['appointments']))$html.='<div class="empty"><strong>'.($b['done']?'Für '.($day===date('Y-m-d')?'heute':'diesen Tag').' ist alles erledigt.':'Nichts geplant.').'</strong>'.($b['done']?'Gute Arbeit.':'Für diesen Tag stehen keine Aufgaben an.').'</div>';
 if($pre){$html.='<h2 class="section-title">Vorgesehen</h2><p class="hint">Regelmäßige Arbeiten. Am Tag selbst werden sie zu Aufgaben zum Abhaken.</p><div class="blocks">';foreach($pre as $u)$html.='<article class="block is-preview"><div class="what"><span class="obj">'.e($u['property_name']?:'Ohne Objekt').'</span><span class="act">'.e($u['title']).($u['duration_minutes']?' · '.(int)$u['duration_minutes'].' Min.':'').'</span></div></article>';$html.='</div>';}
 if($b['done']){$html.='<details class="card"><summary>Erledigt ('.count($b['done']).')</summary><div class="blocks">';foreach($b['done'] as $t)$html.=gszRenderBlock($t,$return);$html.='</div></details>';}
 return $html;
}
