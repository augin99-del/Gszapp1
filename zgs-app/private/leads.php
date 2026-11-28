<?php
declare(strict_types=1);
/**
 * Schema 420: Anfragen (Website-Formular per Mail, Telefon, WhatsApp), Bewertungsanfrage per WhatsApp,
 * Notdienst-Status für die Website und cookiefreie Website-Zahlen (reine Tageszähler, keine Personendaten).
 */
require_once __DIR__.'/platform.php';
const GSZ_LEAD_STATUS=['neu'=>'Neu','kontaktiert'=>'Kontaktiert','angebot'=>'Angebot raus','auftrag'=>'Auftrag','abgesagt'=>'Abgesagt'];
const GSZ_LEAD_SOURCES=['website'=>'Website','telefon'=>'Telefon','whatsapp'=>'WhatsApp','empfehlung'=>'Empfehlung','sonstiges'=>'Sonstiges'];
const GSZ_WEB_EVENTS=['view'=>'Aufrufe','call'=>'Anrufen getippt','whatsapp'=>'WhatsApp getippt','form'=>'Formular gesendet'];
const GSZ_SETTING_DEFAULTS=['review_url'=>'https://share.google/xetYVdtG41vnkgIap','owner_name'=>'Augin Dayan','company'=>'Zentrum Gebäudeservice',
 'website_origin'=>'https://gebaeudeservice-zentrum.de','sos_status'=>'frei','sos_until'=>'','sos_callback'=>'20',
 // 430: Adresse der Zentrale (für QR-Codes), Telefon für Aushang/Karten, Preisrechner Treppenhausreinigung (aus, bis Sätze eingetragen sind)
 'zentrale_url'=>'https://gszintern.de','company_phone'=>'0176 64806595',
 // 440: Push-Benachrichtigungen und DWD-Warnungen (Orte als „Name:Breite,Länge;…“), Winterdienst bei Warnung: ask|auto|off
 'push_leads'=>'1','push_reports'=>'1','push_weather'=>'1','push_frost'=>'0','push_day'=>'1','push_waste'=>'1','push_appt'=>'1','push_rent'=>'1','push_mail'=>'0','push_system'=>'1','push_offers'=>'1','push_day_time'=>'06:30','push_waste_time'=>'18:00','push_mail_last'=>'','push_contact'=>'info@gebaeudeservice-zentrum.de','tick_token'=>'',
 'weather_points'=>'Gütersloh:51.9069,8.3786','winter_properties'=>'','weather_tasks'=>'ask','weather_task_time'=>'06:00','weather_checked'=>'','tick_last'=>'',
 'calc_enabled'=>'0','calc_rate'=>'','calc_base_min'=>'10','calc_floor_min'=>'5','calc_cellar_min'=>'10','calc_elevator_min'=>'5','calc_window_min'=>'3','calc_min_visit'=>'','calc_vat'=>'zzgl','calc_range_pct'=>'10',
 // 450: Verträge je Objekt – Firmendaten für den Vertragskopf, Standardlaufzeit/-frist, Entwurf automatisch beim Anlegen
 'company_address'=>'','company_email'=>'','company_taxid'=>'','contract_term'=>'12','contract_notice'=>'3','contract_vat'=>'zzgl','contract_auto'=>'1',
 // Datensicherung: letzte automatische Sicherung, letzter Download (UTC), Anzahl aufbewahrter automatischer Sicherungen
 'backup_last'=>'','backup_dl_last'=>'','backup_keep'=>'14'];

function gszLeadsReady(PDO $pdo):bool {try{return (bool)$pdo->query("SELECT COUNT(*) FROM app_migrations WHERE version='420_anfragen'")->fetchColumn();}catch(Throwable $e){return false;}}
function gszLText(mixed $v,int $max):string {return mb_substr(trim(preg_replace('/[\x00-\x08\x0B\x0C\x0E-\x1F\x7F]/u','',(string)$v)??''),0,$max);}

/* ---------- Einstellungen ---------- */
function gszSetting(PDO $pdo,string $key):string {
 $q=$pdo->prepare('SELECT setting_value FROM app_settings WHERE setting_key=?');$q->execute([$key]);$v=$q->fetchColumn();
 return $v===false?(GSZ_SETTING_DEFAULTS[$key]??''):(string)$v;
}
function gszSettingSet(PDO $pdo,string $key,string $value):void {
 if(!array_key_exists($key,GSZ_SETTING_DEFAULTS))throw new RuntimeException('Unbekannte Einstellung.');
 $pdo->prepare('INSERT INTO app_settings(setting_key,setting_value) VALUES(?,?) ON DUPLICATE KEY UPDATE setting_value=VALUES(setting_value)')->execute([$key,mb_substr($value,0,2000)]);
}
function gszSettingsSave(PDO $pdo,int $admin,array $in):void {
 $url=trim((string)($in['review_url']??''));if($url!==''&&(!preg_match('~^https://[^\s<>"]+$~D',$url)||strlen($url)>500))throw new RuntimeException('Bewertungslink bitte als https://… einfügen.');
 $origin=rtrim(trim((string)($in['website_origin']??'')),'/');if(!preg_match('~^https://[a-z0-9.-]+$~D',$origin))throw new RuntimeException('Website-Adresse bitte wie https://example.de angeben.');
 gszSettingSet($pdo,'review_url',$url);gszSettingSet($pdo,'website_origin',$origin);gszSettingSet($pdo,'owner_name',gszLText($in['owner_name']??'',120));if(array_key_exists('company_phone',$in)){$ph=gszLText($in['company_phone'],40);if($ph!==''&&!preg_match('/^[0-9+ ()\/-]{5,40}$/D',$ph))throw new RuntimeException('Telefonnummer bitte nur mit Ziffern, Leerzeichen, +, /, -.');gszSettingSet($pdo,'company_phone',$ph);}
 gszAudit($pdo,$admin,'settings_saved',['keys'=>['review_url','website_origin','owner_name']]);
}

/* ---------- Notdienst-Status (für die Website) ---------- */
/** „im Einsatz“ läuft nach der gewählten Zeit automatisch ab und steht dann wieder auf „frei“. */
function gszSosStatus(PDO $pdo,?int $now=null):array {
 $now??=time();$st=gszSetting($pdo,'sos_status');$until=gszSetting($pdo,'sos_until');$cb=max(5,min(180,(int)gszSetting($pdo,'sos_callback')));
 if($st==='einsatz'&&($until===''||strtotime($until.' UTC')<=$now))$st='frei';
 return $st==='einsatz'?['status'=>'einsatz','text'=>'Gerade im Einsatz · Rückruf meist in ca. '.$cb.' Min.','short'=>'Im Einsatz · Rückruf ca. '.$cb.' Min.','until'=>$until]
  :['status'=>'frei','text'=>'Jetzt frei · meist in ca. 30 Min. bei Ihnen','short'=>'Jetzt erreichbar','until'=>''];
}
function gszSosSet(PDO $pdo,int $admin,string $status,int $callback=20,int $hours=2):void {
 if(!in_array($status,['frei','einsatz'],true))throw new RuntimeException('Status unbekannt.');
 gszSettingSet($pdo,'sos_status',$status);gszSettingSet($pdo,'sos_callback',(string)max(5,min(180,$callback)));
 gszSettingSet($pdo,'sos_until',$status==='einsatz'?gmdate('Y-m-d H:i:s',time()+max(1,min(12,$hours))*3600):'');gszAudit($pdo,$admin,'sos_status',['status'=>$status]);
}

/* ---------- Anfragen ---------- */
function gszLeadSave(PDO $pdo,int $admin,array $in,?int $id=null):int {
 $name=gszLText($in['name']??'',190);$phone=gszLText($in['phone']??'',80);$email=gszLText($in['email']??'',190);
 if($name===''&&$phone===''&&$email==='')throw new RuntimeException('Bitte mindestens Name, Telefon oder E-Mail angeben.');
 if($email!==''&&!filter_var($email,FILTER_VALIDATE_EMAIL))throw new RuntimeException('E-Mail-Adresse ungültig.');
 $source=(string)($in['source']??'telefon');if(!isset(GSZ_LEAD_SOURCES[$source]))throw new RuntimeException('Quelle ungültig.');
 $vals=[$source,gszLText($in['service']??'',120),$name,gszLText($in['company']??'',190),$phone,$email,gszLText($in['place']??'',190),gszLText($in['message']??'',5000),gszLText($in['note']??'',1000)];
 if($id===null){$pdo->prepare('INSERT INTO leads(source,service,name,company,phone,email,place,message,note) VALUES(?,?,?,?,?,?,?,?,?)')->execute($vals);$id=(int)$pdo->lastInsertId();gszAudit($pdo,$admin,'lead_added',['id'=>$id,'source'=>$source]);}
 else $pdo->prepare('UPDATE leads SET source=?,service=?,name=?,company=?,phone=?,email=?,place=?,message=?,note=?,row_version=row_version+1 WHERE id=?')->execute([...$vals,$id]);
 return $id;
}
function gszLeadStatus(PDO $pdo,int $admin,int $id,string $status):void {
 if(!isset(GSZ_LEAD_STATUS[$status]))throw new RuntimeException('Status ungültig.');$q=$pdo->prepare('UPDATE leads SET status=?,row_version=row_version+1 WHERE id=?');$q->execute([$status,$id]);
 if(!$q->rowCount()&&!gszLead($pdo,$id))throw new RuntimeException('Anfrage nicht gefunden.');gszAudit($pdo,$admin,'lead_status',['id'=>$id,'status'=>$status]);
}
function gszLeadNote(PDO $pdo,int $id,string $note):void {$pdo->prepare('UPDATE leads SET note=?,row_version=row_version+1 WHERE id=?')->execute([gszLText($note,1000),$id]);}
const GSZ_LEAD_DELETED='[gelöscht]';
function gszLead(PDO $pdo,int $id):?array {$q=$pdo->prepare('SELECT * FROM leads WHERE id=? AND note<>\''.GSZ_LEAD_DELETED.'\'');$q->execute([$id]);$r=$q->fetch(PDO::FETCH_ASSOC);return $r?:null;}
function gszLeads(PDO $pdo,?string $status=null,int $limit=200):array {
 $sql='SELECT * FROM leads WHERE note<>\''.GSZ_LEAD_DELETED.'\''.($status?' AND status=?':" AND status IN ('neu','kontaktiert','angebot')").' ORDER BY FIELD(status,\'neu\',\'kontaktiert\',\'angebot\',\'auftrag\',\'abgesagt\'),created_at DESC,id DESC LIMIT '.max(1,min(500,$limit));
 $q=$pdo->prepare($sql);$q->execute($status?[$status]:[]);return $q->fetchAll(PDO::FETCH_ASSOC);
}
function gszLeadCounts(PDO $pdo):array {$r=array_column($pdo->query("SELECT status,COUNT(*) n FROM leads WHERE note<>'".GSZ_LEAD_DELETED."' GROUP BY status")->fetchAll(PDO::FETCH_ASSOC),'n','status');return array_map('intval',$r)+array_fill_keys(array_keys(GSZ_LEAD_STATUS),0);}
/**
 * Anfrage löschen (z. B. Spam). Eigene Anfragen werden ganz gelöscht. Website-Anfragen behalten nur einen leeren Platzhalter
 * ohne Personendaten, damit dieselbe Formular-Mail beim nächsten Abruf nicht erneut als Anfrage erscheint.
 */
function gszLeadDelete(PDO $pdo,int $admin,int $id):void {
 $l=gszLead($pdo,$id);if(!$l)throw new RuntimeException('Anfrage nicht gefunden.');
 if($l['mail_message_id'])$pdo->prepare("UPDATE leads SET status='abgesagt',service='',object_type='',name='',company='',phone='',email='',place='',message=NULL,source_page='',note=?,property_id=NULL,task_id=NULL,review_requested_at=NULL,row_version=row_version+1 WHERE id=?")->execute([GSZ_LEAD_DELETED,$id]);
 else $pdo->prepare('DELETE FROM leads WHERE id=?')->execute([$id]);
 gszAudit($pdo,$admin,'lead_deleted',['id'=>$id]);
}
/** Aufgabe für heute aus einer Anfrage (Rückruf/Besichtigung); einmalig je Anfrage. */
function gszLeadToTask(PDO $pdo,int $admin,int $id):int {
 $l=gszLead($pdo,$id);if(!$l)throw new RuntimeException('Anfrage nicht gefunden.');if($l['task_id'])return (int)$l['task_id'];
 $title=mb_substr('Anfrage: '.trim(($l['service']?:'Rückruf').' – '.($l['name']?:$l['phone']),' –'),0,190);
 $notes=trim(implode("\n",array_filter([$l['phone']?'Telefon: '.$l['phone']:'',$l['email']?'E-Mail: '.$l['email']:'',$l['place']?'Ort: '.$l['place']:'',$l['message']?:''])));
 $pdo->prepare('INSERT INTO tasks(title,due_date,notes) VALUES(?,CURDATE(),?)')->execute([$title,mb_substr($notes,0,5000)]);$task=(int)$pdo->lastInsertId();
 $pdo->prepare("UPDATE leads SET task_id=?,status=IF(status='neu','kontaktiert',status),row_version=row_version+1 WHERE id=?")->execute([$task,$id]);gszAudit($pdo,$admin,'lead_task',['id'=>$id,'task'=>$task]);return $task;
}

/* ---------- Website-Formular per Mail ---------- */
const GSZ_FORM_LABELS=['Anfrageart'=>'service','Leistung'=>'service','Zielgruppe'=>'','Standortseite'=>'','Objektart'=>'object_type','Bereich'=>'','Unternehmen / Verwaltung'=>'company','Name'=>'name','Telefon'=>'phone','E-Mail'=>'email','PLZ / Ort'=>'place','Nachricht'=>'message','Quellseite'=>'source_page','Seiten-URL'=>''];
/** Zerlegt den Text einer Formular-Mail („Name: …“ je Zeile, Nachricht ggf. mehrzeilig). */
function gszParseFormMail(string $body):?array {
 $body=str_replace(["\r\n","\r"],"\n",$body);if(!str_contains($body,'Neue Anfrage über gebaeudeservice-zentrum.de'))return null;
 $body=preg_split('/\n---\n/',$body)[0];$out=[];$cur=null;
 foreach(explode("\n",$body) as $line){
  if(preg_match('/^([^:\n]{2,40}):\s?(.*)$/u',$line,$m)&&array_key_exists($m[1],GSZ_FORM_LABELS)){$cur=GSZ_FORM_LABELS[$m[1]];if($cur!==''){$out[$cur]=($out[$cur]??'')===''?trim($m[2]):$out[$cur];}continue;}
  if($cur==='message'&&$line!=='')$out['message'].="\n".$line;
 }
 return array_filter($out,fn($v)=>$v!=='')?$out:null;
}
/** Übernimmt neue Formular-Mails als Anfragen (einmalig je Mail). Rückgabe: Anzahl neuer Anfragen. */
function gszLeadsFromMail(PDO $pdo,int $limit=200):int {
 if(!gszLeadsReady($pdo))return 0;
 $q=$pdo->query("SELECT m.id,m.body FROM mail_messages m LEFT JOIN leads l ON l.mail_message_id=m.id WHERE l.id IS NULL AND m.subject LIKE 'Neue Website-Anfrage%' ORDER BY m.id DESC LIMIT ".max(1,min(500,$limit)));$n=0;
 $ins=$pdo->prepare("INSERT IGNORE INTO leads(source,service,object_type,name,company,phone,email,place,message,source_page,mail_message_id) VALUES('website',?,?,?,?,?,?,?,?,?,?)");
 foreach($q->fetchAll(PDO::FETCH_ASSOC) as $m){$f=gszParseFormMail((string)$m['body']);if(!$f)continue;
  $ins->execute([mb_substr($f['service']??'',0,120),mb_substr($f['object_type']??'',0,120),mb_substr($f['name']??'',0,190),mb_substr($f['company']??'',0,190),mb_substr($f['phone']??'',0,80),mb_substr($f['email']??'',0,190),mb_substr($f['place']??'',0,190),mb_substr($f['message']??'',0,5000),mb_substr($f['source_page']??'',0,255),$m['id']]);if($ins->rowCount()){$n++;$lid=(int)$pdo->lastInsertId();try{require_once __DIR__.'/push.php';gszPushNotify($pdo,'leads','lead:'.$lid,'📩 Neue Anfrage'.(($f['service']??'')!==''?': '.$f['service']:''),trim(($f['name']??'').(($f['place']??'')!==''?' · '.$f['place']:''),' ·')?:'Über das Kontaktformular der Website','leads.php#anfrage-'.$lid);}catch(Throwable $e){}}}
 return $n;
}

/* ---------- Bewertung per WhatsApp ---------- */
/** Deutsche Handynummer → internationale Ziffern für wa.me (0176… → 49176…). Leer, wenn unbrauchbar. */
function gszWaNumber(string $phone):string {
 $d=preg_replace('/[^0-9+]/','',$phone)??'';if(str_starts_with($d,'+'))$d=substr($d,1);elseif(str_starts_with($d,'00'))$d=substr($d,2);elseif(str_starts_with($d,'0'))$d='49'.substr($d,1);
 return preg_match('/^[1-9]\d{7,14}$/D',$d)?$d:'';
}
function gszReviewText(PDO $pdo,string $name=''):string {
 $first=trim(preg_split('/\s+/',trim($name))[0]??'');$hello=$first!==''&&mb_strlen($first)<=30?'Hallo '.$first.',':'Hallo,';
 return $hello."\n\nvielen Dank für Ihren Auftrag! Wenn Sie zufrieden waren, freuen wir uns sehr über eine kurze Google-Bewertung – das hilft uns als kleinem Betrieb enorm:\n".gszSetting($pdo,'review_url')."\n\nHerzliche Grüße\n".trim(gszSetting($pdo,'owner_name').', '.gszSetting($pdo,'company'),', ');
}
function gszReviewLink(PDO $pdo,string $phone,string $name=''):string {$n=gszWaNumber($phone);return 'https://wa.me/'.$n.'?text='.rawurlencode(gszReviewText($pdo,$name));}
function gszLeadReviewed(PDO $pdo,int $id):void {$pdo->prepare('UPDATE leads SET review_requested_at=NOW(),row_version=row_version+1 WHERE id=?')->execute([$id]);}

/* ---------- Website-Zahlen ---------- */
function gszWebPage(string $p):?string {$p=strtolower(trim($p));$p=preg_replace('~/index\.html$~','/',$p)??$p;if($p===''||$p[0]!=='/')$p='/'.$p;return preg_match('~^/[a-z0-9/_-]{0,150}$~D',$p)?$p:null;}
function gszWebCount(PDO $pdo,string $page,string $event,?string $day=null):bool {
 $page=gszWebPage($page);if($page===null||!isset(GSZ_WEB_EVENTS[$event]))return false;
 $pdo->prepare('INSERT INTO web_events(day,page,event,hits) VALUES(?,?,?,1) ON DUPLICATE KEY UPDATE hits=LEAST(hits+1,4000000000)')->execute([$day??(new DateTimeImmutable('today',new DateTimeZone('Europe/Berlin')))->format('Y-m-d'),$page,$event]);return true;
}
function gszWebStats(PDO $pdo,int $days=30,?string $today=null):array {
 $today??=(new DateTimeImmutable('today',new DateTimeZone('Europe/Berlin')))->format('Y-m-d');$from=(new DateTimeImmutable($today))->modify('-'.($days-1).' days')->format('Y-m-d');
 $t=$pdo->prepare('SELECT event,SUM(hits) n FROM web_events WHERE day BETWEEN ? AND ? GROUP BY event');$t->execute([$from,$today]);$totals=array_map('intval',array_column($t->fetchAll(PDO::FETCH_ASSOC),'n','event'))+array_fill_keys(array_keys(GSZ_WEB_EVENTS),0);
 $p=$pdo->prepare("SELECT page,SUM(CASE WHEN event='view' THEN hits ELSE 0 END) views,SUM(CASE WHEN event='call' THEN hits ELSE 0 END) calls,SUM(CASE WHEN event='whatsapp' THEN hits ELSE 0 END) whatsapp,SUM(CASE WHEN event='form' THEN hits ELSE 0 END) forms FROM web_events WHERE day BETWEEN ? AND ? GROUP BY page ORDER BY SUM(CASE WHEN event IN ('call','whatsapp','form') THEN hits ELSE 0 END) DESC,SUM(CASE WHEN event='view' THEN hits ELSE 0 END) DESC LIMIT 40");$p->execute([$from,$today]);
 $d=$pdo->prepare("SELECT day,SUM(CASE WHEN event='view' THEN hits ELSE 0 END) views,SUM(CASE WHEN event IN ('call','whatsapp','form') THEN hits ELSE 0 END) contacts FROM web_events WHERE day BETWEEN ? AND ? GROUP BY day ORDER BY day");$d->execute([$from,$today]);
 return ['from'=>$from,'to'=>$today,'totals'=>$totals,'pages'=>$p->fetchAll(PDO::FETCH_ASSOC),'days'=>$d->fetchAll(PDO::FETCH_ASSOC)];
}
