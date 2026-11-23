<?php
declare(strict_types=1);
/** Darstellung der Merkliste. Braucht e() aus bootstrap.php. */
require_once __DIR__.'/notes.php';

function gszNoteProperties(PDO $pdo):array {static $c=null;return $c??=$pdo->query('SELECT id,name FROM properties ORDER BY name LIMIT 2000')->fetchAll(PDO::FETCH_ASSOC);}
function gszNoteHidden(string $csrf,string $action,string $return):string {return '<input type="hidden" name="csrf" value="'.e($csrf).'"><input type="hidden" name="action" value="'.e($action).'"><input type="hidden" name="return" value="'.e($return).'">';}
function gszNotePropertySelect(array $properties,string $selected,bool $auto):string {
 $h='<select name="property_id">'.($auto?'<option value="auto"'.($selected==='auto'?' selected':'').'>Automatisch aus dem Text erkennen</option>':'').'<option value=""'.($selected===''?' selected':'').'>Ohne Objekt</option>';
 foreach($properties as $p)$h.='<option value="'.(int)$p['id'].'"'.((string)$p['id']===$selected?' selected':'').'>'.e($p['name']).'</option>';return $h.'</select>';
}
/** Schnell notieren: ein Feld, Art (To-do / Rechnung offen), Wann-Knöpfe; Objekt, Material, Notiz und Foto optional. */
function gszNoteQuickForm(PDO $pdo,string $csrf,string $return,string $kind='todo',?int $propertyId=null,string $idPrefix='qn'):string {
 $id=fn(string $s)=>e($idPrefix.'-'.$s);
 $h='<form class="quick-note" method="post" action="notes.php" enctype="multipart/form-data">'.gszNoteHidden($csrf,'add',$return)
  .'<div class="seg" role="radiogroup" aria-label="Art"><label><input type="radio" name="kind" value="todo"'.($kind==='todo'?' checked':'').'><span>To-do</span></label><label><input type="radio" name="kind" value="invoice"'.($kind==='invoice'?' checked':'').'><span>Rechnung offen</span></label></div>'
  .'<label for="'.$id('title').'" class="sr-only">Was?</label><input id="'.$id('title').'" name="title" maxlength="190" required autocomplete="off" placeholder="z. B. Loch in der Wand, Fontänenstraße">'
  .'<fieldset class="chips when"><legend class="sr-only">Wann?</legend>';
 foreach([''=>'Irgendwann','today'=>'Heute','tomorrow'=>'Morgen','week'=>'Diese Woche'] as $v=>$l)$h.='<label><input type="radio" name="due" value="'.e($v).'"'.($v===''?' checked':'').'><span>'.e($l).'</span></label>';
 $h.='<label class="date"><span class="sr-only">Datum</span><input type="date" name="due_date" aria-label="Bestimmtes Datum"></label></fieldset>'
  .'<details class="more"><summary>Objekt, Material, Notiz, Foto</summary>'
  .($propertyId?'<input type="hidden" name="property_id" value="'.(int)$propertyId.'">':'<label>Objekt'.gszNotePropertySelect(gszNoteProperties($pdo),'auto',true).'</label>')
  .'<label class="todo-only">Material (kommt auf die Einkaufsliste)<input name="material" maxlength="500" placeholder="z. B. Spachtelmasse, Dübel 8 mm"></label>'
  .'<label>Notiz<input name="note" maxlength="500" placeholder="z. B. 3 Std. + Material"></label>'
  .'<label>Foto<input type="file" name="photo" accept="image/jpeg,image/png,image/webp"></label></details>'
  .'<button class="primary">Merken</button></form>';
 return $h;
}
/** Ein Eintrag als Block mit Abhak-Knopf (ohne Neuladen über zentrale.js). */
function gszRenderNote(PDO $pdo,array $n,string $csrf,string $return,bool $showProperty=true):string {
 $done=(bool)$n['done_at'];$late=gszNoteLate($n);$invoice=$n['kind']==='invoice';
 $meta=[];if($showProperty&&$n['property_id'])$meta[]='<a href="property.php?id='.(int)$n['property_id'].'">'.e((string)$n['property_name']).'</a>';
 $meta[]='<span class="'.($late?'late':'age').'">'.e(gszNoteAge($n)).'</span>';
 $extra='';if($n['material']!=='')$extra.='<span class="note-line">Material: '.e($n['material']).($n['material_done']?' <span class="tag">besorgt</span>':'').'</span>';
 if($n['note']!=='')$extra.='<span class="note-line">'.e($n['note']).'</span>';
 if($n['photo_name'])$extra.='<a class="note-photo" href="note_photo.php?id='.(int)$n['id'].'" target="_blank" rel="noopener"><img src="note_photo.php?id='.(int)$n['id'].'" alt="Foto zu '.e($n['title']).'" loading="lazy" width="72" height="72"></a>';
 $edit='';if(!$done){$f=fn(string $s)=>'ne'.(int)$n['id'].'-'.$s;
  $edit='<details class="move note-edit"><summary>Bearbeiten</summary><div class="move-sheet"><form method="post" action="notes.php" enctype="multipart/form-data">'.gszNoteHidden($csrf,'update',$return).'<input type="hidden" name="id" value="'.(int)$n['id'].'"><input type="hidden" name="row_version" value="'.(int)$n['row_version'].'">'
  .'<label>Text<input name="title" maxlength="190" required value="'.e($n['title']).'"></label>'
  .'<label>Objekt'.gszNotePropertySelect(gszNoteProperties($pdo),(string)($n['property_id']??''),false).'</label>'
  .(!$invoice?'<label>Datum (leer = irgendwann)<input type="date" name="due_date" value="'.e((string)$n['due_date']).'"></label><label>Material<input name="material" maxlength="500" value="'.e($n['material']).'"></label>':'')
  .'<label>'.($invoice?'Notiz (z. B. Stunden, Material)':'Notiz').'<input name="note" maxlength="500" value="'.e($n['note']).'"></label>'
  .'<label>'.($n['photo_name']?'Foto ersetzen':'Foto').'<input type="file" name="photo" accept="image/jpeg,image/png,image/webp"></label>'
  .($n['photo_name']?'<label class="check-label"><input type="checkbox" name="remove_photo" value="1"> Foto entfernen</label>':'')
  .'<button class="small primary">Speichern</button></form>'
  .'<form method="post" action="notes.php" onsubmit="return confirm(\'Eintrag löschen?\')">'.gszNoteHidden($csrf,'delete',$return).'<input type="hidden" name="id" value="'.(int)$n['id'].'"><button class="small danger">Löschen</button></form></div></details>';}
 $label=$invoice?($done?'Wieder offen: ':'Rechnung geschrieben: '):($done?'Wieder öffnen: ':'Erledigt: ');
 return '<article class="block note'.($done?' is-done':'').($late?' is-late':'').($invoice?' is-invoice':'').'" id="notiz-'.(int)$n['id'].'"><div class="what"><span class="obj">'.e($n['title']).'</span><span class="act">'.implode(' · ',$meta).'</span>'.$extra.$edit.'</div>'
  .'<div class="acts"><form method="post" action="notes.php">'.gszNoteHidden($csrf,'note_done',$return).'<input type="hidden" name="id" value="'.(int)$n['id'].'"><input type="hidden" name="row_version" value="'.(int)$n['row_version'].'"><input type="hidden" name="done" value="'.($done?'0':'1').'">'
  .'<button class="check" aria-label="'.e($label.$n['title']).'" title="'.e(trim($label,': ')).'">'.gszNavIcon('check').'</button></form></div></article>';
}
/** Kompakte Liste offener To-dos für „Heute“ und die Objektseite. */
function gszRenderNoteList(PDO $pdo,array $rows,string $csrf,string $return,bool $showProperty=true):string {
 if(!$rows)return '';$h='<div class="blocks notes">';foreach($rows as $n)$h.=gszRenderNote($pdo,$n,$csrf,$return,$showProperty);return $h.'</div>';
}
/** „Mitnehmen“ im Tagesblock: To-dos am selben Objekt, einzeln abhakbar (ohne Neuladen). */
function gszRenderTakeAlong(array $notes,string $return):string {
 $csrf=csrf();$h='<div class="take"><span class="take-label">Mitnehmen</span>';
 foreach($notes as $n){$sub=[];if($n['material']!=='')$sub[]='Material: '.$n['material'].($n['material_done']?' (besorgt)':'');if($n['note']!=='')$sub[]=$n['note'];if(gszNoteLate($n))$sub[]=gszNoteAge($n);
  $h.='<div class="take-item" id="notiz-'.(int)$n['id'].'"><form method="post" action="notes.php">'.gszNoteHidden($csrf,'note_done',$return).'<input type="hidden" name="id" value="'.(int)$n['id'].'"><input type="hidden" name="row_version" value="'.(int)$n['row_version'].'"><input type="hidden" name="done" value="1"><button class="check" aria-label="'.e('Erledigt: '.$n['title']).'" title="Erledigt">'.gszNavIcon('check').'</button></form>'
   .'<span class="take-text"><span class="take-title">'.e($n['title']).'</span>'.($sub?'<span class="take-sub">'.e(implode(' · ',$sub)).'</span>':'').'</span>'.($n['photo_name']?'<a class="note-photo" href="note_photo.php?id='.(int)$n['id'].'" target="_blank" rel="noopener"><img src="note_photo.php?id='.(int)$n['id'].'" alt="Foto" loading="lazy" width="44" height="44"></a>':'').'</div>';}
 return $h.'</div>';
}
