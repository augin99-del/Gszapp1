<?php
declare(strict_types=1);
/**
 * Abfall-Blöcke: genau EINE Aufgabe je Objekt, Tag und Richtung (rausstellen / zurückstellen).
 * Die enthaltenen Tonnenarten bleiben einzeln über waste_task_links → waste_events nachvollziehbar.
 * Erledigte Blöcke werden nie verändert. Alle Funktionen laufen innerhalb der Transaktion des Aufrufers.
 */
require_once __DIR__.'/waste_parser.php';
const WASTE_BLOCK_LABELS=['Restmüll'=>'Restmüll','Bio'=>'Biotonne','Papier'=>'Papiertonne','Gelb'=>'Gelbe Tonne','Windeln'=>'Windeltonne','Grünschnitt'=>'Grünschnitt','Weihnachtsbäume'=>'Weihnachtsbäume'];
function wasteFractionLabel(string $fraction):string {return WASTE_BLOCK_LABELS[$fraction]??(str_starts_with($fraction,'Unklar-')?'Tonne (Art unklar)':$fraction);}
function wasteJoinLabels(array $labels):string {$labels=array_values(array_unique($labels));if(count($labels)<2)return (string)($labels[0]??'');$last=array_pop($labels);return implode(', ',$labels).' und '.$last;}
function wasteSortFractions(array $fractions):array {$order=array_flip(WASTE_FRACTIONS);$fractions=array_values(array_unique($fractions));usort($fractions,fn($a,$b)=>[$order[$a]??99,$a]<=>[$order[$b]??99,$b]);return $fractions;}
function wasteBlockTitle(array $fractions,string $direction):string {
 if(!in_array($direction,['raus','rein'],true))throw new InvalidArgumentException('Richtung ungültig.');$verb=$direction==='raus'?'rausstellen':'zurückstellen';
 $fractions=wasteSortFractions($fractions);if(!$fractions)return 'Tonnen '.$verb;return mb_substr(wasteJoinLabels(array_map('wasteFractionLabel',$fractions)).' '.$verb,0,190);
}
function wasteBlockKey(int $property,string $date,string $direction,int $n=1):string {if($property<1||!zgsValidDate($date)||!in_array($direction,['raus','rein'],true)||$n<1)throw new InvalidArgumentException('Blockschlüssel ungültig.');return 'waste:block:'.$property.':'.$date.':'.$direction.($n>1?':'.$n:'');}
/** Direction of a special weekly rule task (Fontänen Gelbe Tonne) from its stored title. */
function wasteSpecialDirection(string $title):?string {$t=mb_strtolower($title);if(str_contains($t,'raus'))return 'raus';if(str_contains($t,'rein')||str_contains($t,'zurück'))return 'rein';return null;}
/** Fractions of a block: linked events plus the implicit Gelbe Tonne of the special weekly rule occurrence. */
function wasteBlockFractions(PDO $pdo,int $task):array {
 $q=$pdo->prepare('SELECT DISTINCT e.fraction FROM waste_task_links l JOIN waste_events e ON e.id=l.event_id WHERE l.task_id=?');$q->execute([$task]);$f=$q->fetchAll(PDO::FETCH_COLUMN);
 $q=$pdo->prepare("SELECT 1 FROM tasks t JOIN recurring_jobs r ON r.id=t.recurring_job_id WHERE t.id=? AND r.rule_source='special_weekly'");$q->execute([$task]);if($q->fetchColumn())$f[]='Gelb';
 return wasteSortFractions($f);
}
function wasteBlockDirection(PDO $pdo,array $task):?string {
 $q=$pdo->prepare('SELECT DISTINCT direction FROM waste_task_links WHERE task_id=?');$q->execute([(int)$task['id']]);$d=$q->fetchAll(PDO::FETCH_COLUMN);if(count($d)===1)return $d[0];
 if(preg_match('/^waste:(?:block|group):\d+:\d{4}-\d{2}-\d{2}:(raus|rein)/',(string)$task['waste_key'],$m))return $m[1];return $task['recurring_job_id']?wasteSpecialDirection((string)$task['title']):null;
}
function wasteTaskReferenced(PDO $pdo,int $task):bool {
 foreach(['SELECT 1 FROM work_logs WHERE task_id=? LIMIT 1','SELECT 1 FROM mail_messages WHERE task_id=? LIMIT 1','SELECT 1 FROM waste_task_links WHERE task_id=? LIMIT 1'] as $sql){$q=$pdo->prepare($sql);$q->execute([$task]);if($q->fetchColumn())return true;}
 try{$q=$pdo->prepare('SELECT 1 FROM recurring_completions WHERE task_id=? LIMIT 1');$q->execute([$task]);if($q->fetchColumn())return true;}catch(PDOException $e){}
 return false;
}
/** Recompute the title of an open block; delete an open, empty, unreferenced, non-recurring block. Done blocks stay untouched. */
function wasteRebuildBlock(PDO $pdo,int $task):string {
 $q=$pdo->prepare('SELECT * FROM tasks WHERE id=? FOR UPDATE');$q->execute([$task]);$t=$q->fetch(PDO::FETCH_ASSOC);if(!$t)return 'missing';if($t['done'])return 'done';
 $fractions=wasteBlockFractions($pdo,$task);
 if(!$fractions&&!$t['recurring_job_id']&&!wasteTaskReferenced($pdo,$task)&&!$t['notes']){$pdo->prepare('DELETE FROM tasks WHERE id=? AND done=0')->execute([$task]);return 'deleted';}
 $direction=wasteBlockDirection($pdo,$t);if(!$direction)return 'unchanged';$title=wasteBlockTitle($fractions,$direction);
 if($title!==$t['title'])$pdo->prepare('UPDATE tasks SET title=? WHERE id=? AND done=0')->execute([$title,$task]);return 'updated';
}
/** Open block for property/day/direction; reuses the special weekly occurrence, never a done block. */
function wasteFindOrCreateBlock(PDO $pdo,int $property,string $date,string $direction):int {
 $q=$pdo->prepare("SELECT r.* FROM recurring_jobs r JOIN properties p ON p.id=r.property_id WHERE r.property_id=? AND p.seed_key='fontaenen29' AND r.rule_source='special_weekly' AND r.active=1 ORDER BY r.id");$q->execute([$property]);
 foreach($q->fetchAll(PDO::FETCH_ASSOC) as $special){if(wasteSpecialDirection($special['title'])!==$direction||(int)(new DateTimeImmutable($date))->format('N')!==(int)(new DateTimeImmutable((string)$special['next_due_date']?:$date))->format('N'))continue;
  zgsInsertOccurrence($pdo,$special,$date);$s=$pdo->prepare('SELECT id,done,waste_key FROM tasks WHERE recurring_job_id=? AND scheduled_for=? FOR UPDATE');$s->execute([$special['id'],$date]);$occ=$s->fetch(PDO::FETCH_ASSOC);
  if($occ&&!$occ['done']){$key=wasteBlockKey($property,$date,$direction);$s=$pdo->prepare('SELECT id FROM tasks WHERE waste_key=? AND id<>?');$s->execute([$key,$occ['id']]);if(!$s->fetchColumn()){if($occ['waste_key']!==$key)$pdo->prepare('UPDATE tasks SET waste_key=? WHERE id=? AND waste_key IS NULL')->execute([$key,$occ['id']]);return (int)$occ['id'];}}
 }
 for($n=1;$n<=9;$n++){$key=wasteBlockKey($property,$date,$direction,$n);$s=$pdo->prepare('SELECT id,done FROM tasks WHERE waste_key=? FOR UPDATE');$s->execute([$key]);$row=$s->fetch(PDO::FETCH_ASSOC);
  if($row&&!$row['done'])return (int)$row['id'];if($row)continue;
  $pdo->prepare('INSERT INTO tasks(property_id,title,due_date,duration_minutes,waste_key,window_start,window_end) VALUES(?,?,?,15,?,?,?)')->execute([$property,wasteBlockTitle([],$direction),$date,$key,$date,$date]);return (int)$pdo->lastInsertId();}
 throw new RuntimeException('Zu viele Abfallblöcke für diesen Tag.');
}
/** Attach an event to the open block of its day; returns the block id. */
function wasteLinkToBlock(PDO $pdo,int $event,int $property,string $date,string $direction):int {
 $task=wasteFindOrCreateBlock($pdo,$property,$date,$direction);$pdo->prepare('INSERT INTO waste_task_links(event_id,direction,task_id) VALUES(?,?,?)')->execute([$event,$direction,$task]);wasteRebuildBlock($pdo,$task);return $task;
}
/** Detach an event from its block unless the block is already done (then the history stays as it was). */
function wasteUnlinkFromBlock(PDO $pdo,int $event,string $direction):?int {
 $q=$pdo->prepare('SELECT l.task_id,t.done FROM waste_task_links l JOIN tasks t ON t.id=l.task_id WHERE l.event_id=? AND l.direction=? FOR UPDATE');$q->execute([$event,$direction]);$row=$q->fetch(PDO::FETCH_ASSOC);if(!$row||$row['done'])return null;
 $pdo->prepare('DELETE FROM waste_task_links WHERE event_id=? AND direction=?')->execute([$event,$direction]);wasteRebuildBlock($pdo,(int)$row['task_id']);return (int)$row['task_id'];
}
/** Bin types of several blocks at once (for lists, ICS/CSV export): [task_id=>['fractions'=>[], 'direction'=>…]]. */
function wasteBlockInfo(PDO $pdo,array $taskIds):array {
 $ids=array_values(array_unique(array_filter(array_map('intval',$taskIds))));if(!$ids)return [];$in=implode(',',array_fill(0,count($ids),'?'));
 $q=$pdo->prepare("SELECT l.task_id,l.direction,e.fraction FROM waste_task_links l JOIN waste_events e ON e.id=l.event_id WHERE l.task_id IN($in)");$q->execute($ids);$out=[];foreach($q->fetchAll(PDO::FETCH_ASSOC) as $r){$out[(int)$r['task_id']]['direction']=$r['direction'];$out[(int)$r['task_id']]['fractions'][]=$r['fraction'];}
 $q=$pdo->prepare("SELECT t.id,t.title FROM tasks t JOIN recurring_jobs r ON r.id=t.recurring_job_id WHERE t.id IN($in) AND r.rule_source='special_weekly'");$q->execute($ids);foreach($q->fetchAll(PDO::FETCH_ASSOC) as $r){$out[(int)$r['id']]['fractions'][]='Gelb';$out[(int)$r['id']]['direction']??=wasteSpecialDirection($r['title']);}
 foreach($out as &$o){$o['fractions']=wasteSortFractions($o['fractions']??[]);$o['labels']=array_map('wasteFractionLabel',$o['fractions']);}unset($o);return $out;
}
/**
 * Migration/Reparatur: führt offene Einzel-Abfallaufgaben desselben Objekts, Tages und derselben Richtung
 * zu einem Block zusammen. Erledigte Aufgaben bleiben unverändert. Verweise (Arbeitsnachweise, Mails) wandern
 * auf den verbleibenden Block. Rückgabe: Zuordnung alte → neue Aufgaben-ID (wird im Abfallverlauf protokolliert).
 */
function wasteMergeOpenBlocks(PDO $pdo,int $admin=0):array {
 $rows=$pdo->query("SELECT DISTINCT t.id,t.property_id,t.due_date,t.done,t.waste_key,t.recurring_job_id,t.title,t.notes,l.direction FROM tasks t JOIN waste_task_links l ON l.task_id=t.id WHERE t.done=0 AND t.property_id IS NOT NULL AND t.due_date IS NOT NULL ORDER BY t.id")->fetchAll(PDO::FETCH_ASSOC);
 $special=$pdo->query("SELECT t.id,t.property_id,t.due_date,t.title,t.waste_key,t.recurring_job_id,t.notes FROM tasks t JOIN recurring_jobs r ON r.id=t.recurring_job_id WHERE r.rule_source='special_weekly' AND t.done=0 AND t.due_date IS NOT NULL ORDER BY t.id")->fetchAll(PDO::FETCH_ASSOC);
 $groups=[];foreach($rows as $r)$groups[$r['property_id'].'|'.$r['due_date'].'|'.$r['direction']][(int)$r['id']]=$r;
 foreach($special as $s){$d=wasteSpecialDirection($s['title']);$k=$s['property_id'].'|'.$s['due_date'].'|'.$d;if($d&&isset($groups[$k]))$groups[$k][(int)$s['id']]=$s+['direction'=>$d];}
 $map=[];$merged=0;
 foreach($groups as $k=>$tasks){[$property,$date,$direction]=explode('|',$k);ksort($tasks);
  $survivor=null;foreach($tasks as $id=>$t)if($t['recurring_job_id']){$survivor=$id;break;}$survivor??=array_key_first($tasks);
  $key=null;for($n=1;$n<=9&&$key===null;$n++){$candidate=wasteBlockKey((int)$property,$date,$direction,$n);$q=$pdo->prepare('SELECT id FROM tasks WHERE waste_key=?');$q->execute([$candidate]);$holder=(int)$q->fetchColumn();if(!$holder||isset($tasks[$holder])){$key=$candidate;if($holder&&!$tasks[$survivor]['recurring_job_id'])$survivor=$holder;}}
  if($key===null)continue;
  if(count($tasks)===1&&(string)$tasks[$survivor]['waste_key']===$key){wasteRebuildBlock($pdo,$survivor);continue;}
  foreach($tasks as $id=>$t){if($id===$survivor)continue;
   $pdo->prepare('UPDATE waste_task_links SET task_id=? WHERE task_id=?')->execute([$survivor,$id]);
   foreach(['work_logs','mail_messages'] as $table)$pdo->prepare('UPDATE '.$table.' SET task_id=? WHERE task_id=?')->execute([$survivor,$id]);
   if($t['notes']){$pdo->prepare("UPDATE tasks SET notes=TRIM(CONCAT(COALESCE(notes,''),'\n',?)) WHERE id=?")->execute([(string)$t['notes'],$survivor]);}
   $map[$id]=$survivor;if($t['recurring_job_id'])continue;
   $pdo->prepare('UPDATE tasks SET waste_key=NULL WHERE id=?')->execute([$id]);$pdo->prepare('DELETE FROM tasks WHERE id=? AND done=0')->execute([$id]);$merged++;
  }
  $pdo->prepare('UPDATE tasks SET waste_key=? WHERE id=?')->execute([$key,$survivor]);
  wasteRebuildBlock($pdo,$survivor);
 }
 if($map&&$admin>0)$pdo->prepare('INSERT INTO waste_history(admin_id,event_type,detail) VALUES(?,?,?)')->execute([$admin,'blocks_merged',json_encode(['merged_into'=>$map],JSON_THROW_ON_ERROR)]);
 return ['merged'=>$merged,'map'=>$map];
}
