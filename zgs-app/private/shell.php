<?php
declare(strict_types=1);
/**
 * Premium-Rahmen (11/2026): Zähler für die Navigation, Benachrichtigungs-Glocke und die globale Suche (⌘K).
 * Jede Quelle ist einzeln abgesichert – fehlt ein Bereich (älterer Datenbankstand), bleibt er einfach leer.
 */

/** Zähler für die Seitenleiste: neue Anfragen, offene Mieter-Meldungen, ungelesene Benachrichtigungen. */
function gszShellCounts(PDO $pdo):array {
 $c=['leads'=>0,'reports'=>0,'bell'=>0];
 try{$c['leads']=(int)$pdo->query("SELECT COUNT(*) FROM leads WHERE status='neu' AND note<>'[gelöscht]'")->fetchColumn();}catch(Throwable $e){}
 try{$c['reports']=(int)$pdo->query("SELECT COUNT(*) FROM defect_reports d LEFT JOIN quick_notes n ON n.id=d.note_id WHERE d.status='neu' OR (d.status='uebernommen' AND n.done_at IS NULL)")->fetchColumn();}catch(Throwable $e){}
 try{$seen=(string)$pdo->query("SELECT setting_value FROM app_settings WHERE setting_key='bell_seen'")->fetchColumn();$q=$pdo->prepare("SELECT COUNT(*) FROM push_events WHERE kind<>'test' AND created_at>? AND created_at>NOW()-INTERVAL 7 DAY");$q->execute([$seen!==''?$seen:'1970-01-01']);$c['bell']=(int)$q->fetchColumn();}catch(Throwable $e){}
 return $c;
}
function gszBellSeen(PDO $pdo):void {try{$pdo->prepare("INSERT INTO app_settings(setting_key,setting_value) VALUES('bell_seen',NOW()) ON DUPLICATE KEY UPDATE setting_value=VALUES(setting_value)")->execute();}catch(Throwable $e){}}

/** Befehle und Seiten, die die Suche ohne Datenbanktreffer anbietet. */
const GSZ_COMMANDS=[
 ['Neuer Kunde mit Objekt','objects.php?neu=kunde#neu','Anlegen','kunde neu neukunde objekt hausverwaltung'],
 ['Neues Objekt','objects.php?neu=objekt#neu','Anlegen','objekt haus neu anlegen'],
 ['Anfrage erfassen','leads.php#neu','Anlegen','anfrage lead telefon whatsapp'],
 ['Schlüsseldienst-Einsatz erfassen','einsaetze.php#neu','Anlegen','einsatz schlüssel tür öffnung umsatz'],
 ['Termin anlegen','appointments.php','Anlegen','termin besichtigung kalender'],
 ['To-do notieren','notes.php#neu','Anlegen','todo notiz merkliste rechnung'],
 ['Heute','index.php','Seite','start cockpit tag'],['Woche planen','planner.php?view=week','Seite','woche plan route'],
 ['Objekte','objects.php','Seite','objekte häuser'],['Kunden','customers.php','Seite','kunden hausverwaltung'],
 ['Anfragen','leads.php','Seite','anfragen leads'],['Mieter-Meldungen','meldungen.php','Seite','meldung schaden mieter'],
 ['Häuser: QR & Portal','haeuser.php','Seite','qr aushang portal'],['Schlüsseldienst','einsaetze.php','Seite','einsätze umsatz'],
 ['Merkliste','notes.php','Seite','todo rechnungen einkauf'],['Abfallkalender','waste.php','Seite','tonnen müll abfuhr'],
 ['Helfer','workers.php','Seite','mitarbeiter helfer app'],['Website-Zahlen & Preisrechner','website.php','Seite','website aufrufe preisrechner'],
 ['Benachrichtigungen','benachrichtigungen.php','Seite','push glätte winterdienst wecker'],['Mail','mail.php','Seite','posteingang'],
 ['Immobilien','immobilien.php','Seite','miete konto häuser haus'],['Mieten & Kontoauszug','estate_bank.php','Seite','kontoauszug bank buchung zuordnen'],['Bauprojekte','projects.php','Seite','bau sanierung'],
 ['Sicherung und Update','update.php','Seite','backup update'],['Einstellungen','settings.php','Seite','einstellungen'],
];
/** Globale Suche. Rückgabe: Liste [title, sub, url, group]. Max. 6 je Gruppe. */
function gszSearch(PDO $pdo,string $q):array {
 $q=trim(mb_substr($q,0,80));$out=[];$norm=mb_strtolower($q);
 foreach(GSZ_COMMANDS as [$t,$u,$g,$k])if($norm===''?$g==='Anlegen':(str_contains(mb_strtolower($t),$norm)||str_contains($k,$norm)))$out[]=['title'=>$t,'sub'=>$g==='Anlegen'?'Neu anlegen':'Seite öffnen','url'=>$u,'group'=>$g==='Anlegen'?'Aktionen':'Seiten'];
 if(mb_strlen($q)<2)return array_slice($out,0,12);
 $like='%'.str_replace(['\\','%','_'],['\\\\','\\%','\\_'],$q).'%';
 $run=function(string $sql,int $n,callable $map,string $group)use($pdo,$like,&$out){try{$s=$pdo->prepare($sql.' LIMIT 6');$s->execute(array_fill(0,$n,$like));foreach($s->fetchAll(PDO::FETCH_ASSOC) as $r)$out[]=$map($r)+['group'=>$group];}catch(Throwable $e){}};
 $run('SELECT p.id,p.name,p.address,c.name cname FROM properties p LEFT JOIN customers c ON c.id=p.customer_id WHERE p.name LIKE ? OR p.address LIKE ? OR c.name LIKE ? ORDER BY p.name',3,fn($r)=>['title'=>$r['name'],'sub'=>trim($r['address'].($r['cname']?' · '.$r['cname']:''),' ·'),'url'=>'property.php?id='.(int)$r['id']],'Objekte');
 $run('SELECT id,name,email,phone FROM customers WHERE name LIKE ? OR email LIKE ? OR phone LIKE ? ORDER BY name',3,fn($r)=>['title'=>$r['name'],'sub'=>trim($r['phone'].' '.$r['email']),'url'=>'customers.php?id='.(int)$r['id']],'Kunden');
 $run("SELECT id,name,company,service,place,status FROM leads WHERE note<>'[gelöscht]' AND (name LIKE ? OR company LIKE ? OR phone LIKE ? OR email LIKE ? OR place LIKE ?) ORDER BY created_at DESC",5,fn($r)=>['title'=>($r['name']?:$r['company'])?:'Anfrage','sub'=>trim(($r['service']?:'Anfrage').' · '.$r['place'].' · '.$r['status'],' ·'),'url'=>'leads.php'.($r['status']==='auftrag'||$r['status']==='abgesagt'?'?status='.$r['status']:'').'#anfrage-'.(int)$r['id']],'Anfragen');
 $run("SELECT t.id,t.title,t.due_date,p.name pname FROM tasks t LEFT JOIN properties p ON p.id=t.property_id WHERE t.done=0 AND (t.title LIKE ? OR p.name LIKE ?) ORDER BY t.due_date IS NULL,t.due_date",2,fn($r)=>['title'=>$r['title'],'sub'=>trim(($r['pname']??'').($r['due_date']?' · '.(new DateTimeImmutable($r['due_date']))->format('d.m.'):''),' ·'),'url'=>'task.php?id='.(int)$r['id']],'Aufgaben');
 $run('SELECT d.id,d.location,d.message,p.name pname FROM defect_reports d JOIN properties p ON p.id=d.property_id WHERE d.message LIKE ? OR d.contact LIKE ? OR p.name LIKE ? ORDER BY d.created_at DESC',3,fn($r)=>['title'=>mb_strimwidth(($r['location']?$r['location'].': ':'').$r['message'],0,70,'…'),'sub'=>$r['pname'],'url'=>'meldungen.php?filter=alle#meldung-'.(int)$r['id']],'Mieter-Meldungen');
 $run("SELECT id,job_date,place,customer,amount_cents FROM lock_jobs WHERE place LIKE ? OR customer LIKE ? OR phone LIKE ? ORDER BY job_date DESC",3,fn($r)=>['title'=>'Einsatz '.$r['place'].($r['customer']?' · '.$r['customer']:''),'sub'=>(new DateTimeImmutable($r['job_date']))->format('d.m.Y').($r['amount_cents']?' · '.number_format($r['amount_cents']/100,0,',','.').' €':''),'url'=>'einsaetze.php?m='.substr($r['job_date'],0,7).'#einsatz-'.(int)$r['id']],'Schlüsseldienst');
 return $out;
}

/** Rechte Spalte auf „Heute“ (Computer): die wichtigsten Zahlen und Listen aus allen Bereichen. */
function gszCockpitRail(PDO $pdo):string {
 $h='';$w=function(string $title,string $link,string $linkText,string $body)use(&$h){$h.='<section class="widget"><div class="w-head"><h2>'.e($title).'</h2><a href="'.e($link).'">'.e($linkText).'</a></div>'.$body.'</section>';};
 $tz=new DateTimeZone('Europe/Berlin');$ago=function(string $ts)use($tz):string{$d=(new DateTimeImmutable($ts.' UTC'))->setTimezone($tz);$m=(int)round((time()-$d->getTimestamp())/60);return $m<60?'vor '.max(1,$m).' Min.':($m<1440?'vor '.intdiv($m,60).' Std.':$d->format('d.m.'));};
 try{$rows=$pdo->query("SELECT id,name,company,service,place,status,created_at FROM leads WHERE note<>'[gelöscht]' AND status IN ('neu','kontaktiert','angebot') ORDER BY FIELD(status,'neu','kontaktiert','angebot'),created_at DESC LIMIT 4")->fetchAll(PDO::FETCH_ASSOC);
  $b='';foreach($rows as $r)$b.='<a class="w-row" href="leads.php#anfrage-'.(int)$r['id'].'"><span class="w-main"><strong>'.e(($r['name']?:$r['company'])?:'Anfrage').'</strong><small>'.e(trim(($r['service']?:'Anfrage').' · '.$r['place'],' ·')).'</small></span><span class="w-side"><span class="st st-'.e($r['status']).'">'.e(['neu'=>'Neu','kontaktiert'=>'Kontaktiert','angebot'=>'Angebot'][$r['status']]).'</span><small>'.e($ago((string)$r['created_at'])).'</small></span></a>';
  $w('Offene Anfragen','leads.php','Alle',$b?:'<p class="w-empty">Keine offenen Anfragen.</p>');}catch(Throwable $e){}
 try{$rows=$pdo->query("SELECT d.id,d.location,d.message,d.status,d.created_at,p.name pname FROM defect_reports d JOIN properties p ON p.id=d.property_id LEFT JOIN quick_notes n ON n.id=d.note_id WHERE d.status='neu' OR (d.status='uebernommen' AND n.done_at IS NULL) ORDER BY d.status='neu' DESC,d.created_at DESC LIMIT 4")->fetchAll(PDO::FETCH_ASSOC);
  $b='';foreach($rows as $r)$b.='<a class="w-row" href="meldungen.php#meldung-'.(int)$r['id'].'"><span class="w-main"><strong>'.e($r['pname']).'</strong><small>'.e(mb_strimwidth(($r['location']?$r['location'].': ':'').$r['message'],0,60,'…')).'</small></span><span class="w-side"><span class="st st-'.($r['status']==='neu'?'neu':'kontaktiert').'">'.($r['status']==='neu'?'Neu':'In Arbeit').'</span><small>'.e($ago((string)$r['created_at'])).'</small></span></a>';
  $w('Mieter-Meldungen','meldungen.php','Alle',$b?:'<p class="w-empty">Keine offenen Meldungen.</p>');}catch(Throwable $e){}
 try{$m=(new DateTimeImmutable('first day of this month',$tz))->format('Y-m-01');$pm=(new DateTimeImmutable('first day of last month',$tz))->format('Y-m-01');
  $q=$pdo->prepare('SELECT COUNT(*) n,COALESCE(SUM(amount_cents),0) c FROM lock_jobs WHERE job_date BETWEEN ? AND LAST_DAY(?)');$q->execute([$m,$m]);$a=$q->fetch(PDO::FETCH_ASSOC);$q->execute([$pm,$pm]);$b2=$q->fetch(PDO::FETCH_ASSOC);
  $diff=(int)$b2['c']?(int)round(((int)$a['c']-(int)$b2['c'])/(int)$b2['c']*100):null;
  $w('Schlüsseldienst · '.['','Januar','Februar','März','April','Mai','Juni','Juli','August','September','Oktober','November','Dezember'][(int)substr($m,5,2)],'einsaetze.php','Details','<div class="w-kpis"><div><strong>'.number_format((int)$a['c']/100,0,',','.').' €</strong><small>Umsatz'.($diff!==null?' · <span class="'.($diff>=0?'up':'down').'">'.($diff>=0?'+':'').$diff.' % zum Vormonat</span>':'').'</small></div><div><strong>'.(int)$a['n'].'</strong><small>Einsätze</small></div></div>');}catch(Throwable $e){}
 try{require_once __DIR__.'/leads.php';$s=gszWebStats($pdo,7);$t=$s['totals'];$k=$t['call']+$t['whatsapp']+$t['form'];
  $w('Website · 7 Tage','website.php','Details','<div class="w-kpis"><div><strong>'.number_format($t['view'],0,',','.').'</strong><small>Aufrufe</small></div><div><strong>'.$k.'</strong><small>Kontakte (Anruf, WhatsApp, Formular)</small></div></div>');}catch(Throwable $e){}
 try{$rows=$pdo->query("SELECT * FROM push_events WHERE kind NOT IN ('test','day') ORDER BY created_at DESC LIMIT 4")->fetchAll(PDO::FETCH_ASSOC);if($rows){require_once __DIR__.'/push.php';
  $b='';foreach($rows as $r)$b.='<a class="w-row" href="'.e($r['url']).'"><span class="w-ico" aria-hidden="true">'.e(GSZ_PUSH_KINDS[$r['kind']][2]??'•').'</span><span class="w-main"><strong>'.e($r['title']).'</strong><small>'.e(mb_strimwidth((string)$r['body'],0,60,'…')).'</small></span><span class="w-side"><small>'.e($ago((string)$r['created_at'])).'</small></span></a>';
  $w('Zuletzt benachrichtigt','benachrichtigungen.php#verlauf','Alle',$b);}}catch(Throwable $e){}
 return $h;
}
