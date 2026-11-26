<?php
declare(strict_types=1);
// Schema 440: Web Push (RFC 8291/8292) und DWD-Warnungen für den Winterdienst. Synthetische Daten, kein echter Netzversand.
set_error_handler(static function($s,$m,$f,$l){if(error_reporting()&$s)throw new ErrorException($m,0,$s,$f,$l);return false;});
require dirname(__DIR__).'/private/install_core.php';require dirname(__DIR__).'/private/web_update.php';require_once dirname(__DIR__).'/private/weather.php';require_once dirname(__DIR__).'/private/reports.php';
$dsn=getenv('GSZ_TEST_DSN')?:'';if(!preg_match('/^mysql:unix_socket=[^;]+;dbname=gsz_test_push_[a-z0-9_]+$/D',$dsn))throw new RuntimeException('Empty local test DB required.');
$p=new PDO($dsn,'root','',[PDO::ATTR_ERRMODE=>PDO::ERRMODE_EXCEPTION,PDO::ATTR_DEFAULT_FETCH_MODE=>PDO::FETCH_ASSOC,PDO::ATTR_EMULATE_PREPARES=>false]);if($p->query('SHOW TABLES')->fetchAll())throw new RuntimeException('Empty DB required.');
$n=0;function pu(bool $v,string $s):void{global $n;$n++;if(!$v)throw new RuntimeException($s);}
function puThrows(callable $f,string $s):void{try{$f();pu(false,$s);}catch(RuntimeException $e){pu(true,$s);}}
$r=gszInstallFresh($p);pu($r['schema']==='440'&&$r['tables']===83&&gszPushReady($p)&&gszSchemaCurrent($p),'fresh install 440, 83 tables');
$p->exec("INSERT INTO admins(email,password_hash) VALUES('owner@example.invalid','x')");
// Simuliertes Handy: eigener Schlüssel + Entschlüsselung wie ein Browser (RFC 8291)
$ua=openssl_pkey_new(['curve_name'=>'prime256v1','private_key_type'=>OPENSSL_KEYTYPE_EC]);$uaPub=gszEcRawPublic($ua);$auth=random_bytes(16);
$decrypt=function(string $body)use($ua,$uaPub,$auth):string{$salt=substr($body,0,16);$idlen=ord($body[20]);$as=substr($body,21,$idlen);$ct=substr($body,21+$idlen);
 $shared=openssl_pkey_derive(gszEcPublicFromRaw($as),$ua,32);$ikm=hash_hkdf('sha256',$shared,32,"WebPush: info\0".$uaPub.$as,$auth);
 $cek=hash_hkdf('sha256',$ikm,16,"Content-Encoding: aes128gcm\0",$salt);$nonce=hash_hkdf('sha256',$ikm,12,"Content-Encoding: nonce\0",$salt);
 $pt=openssl_decrypt(substr($ct,0,-16),'aes-128-gcm',$cek,OPENSSL_RAW_DATA,$nonce,substr($ct,-16));if($pt===false)throw new RuntimeException('decrypt');return rtrim($pt,"\x02");};
$sub=['endpoint'=>'https://push.example.invalid/send/abc123','keys'=>['p256dh'=>gszB64u($uaPub),'auth'=>gszB64u($auth)]];
puThrows(fn()=>gszPushSubscribe($p,1,['endpoint'=>'http://x.invalid/a','keys'=>$sub['keys']]),'http endpoint refused');puThrows(fn()=>gszPushSubscribe($p,1,['endpoint'=>$sub['endpoint'],'keys'=>['p256dh'=>'abc','auth'=>'x']]),'bad keys refused');
$sid=gszPushSubscribe($p,1,$sub,"iPhone · Safari\n");pu(gszPushSubscribe($p,1,$sub,'iPhone')===$sid&&count(gszPushDevices($p))===1,'same device registered once');
$vk=gszPushPublicKey();pu(is_file(gszPushKeyFile())&&(fileperms(gszPushKeyFile())&0777)===0600&&strlen(gszB64uDec($vk))===65&&gszPushPublicKey()===$vk,'VAPID key stored privately (0600)');
$calls=[];$sender=function(string $ep,string $body,array $h)use(&$calls){$calls[]=[$ep,$body,$h];return 201;};
pu(gszPushNotify($p,'test','t1','✅ Test','Hallo äöü','benachrichtigungen.php',$sender)===1&&count($calls)===1,'test push delivered');
[$ep,$body,$h]=$calls[0];$msg=json_decode($decrypt($body),true);pu($ep===$sub['endpoint']&&$msg['title']==='✅ Test'&&$msg['body']==='Hallo äöü'&&$msg['url']==='benachrichtigungen.php','payload decrypts on the device');
pu(in_array('Content-Encoding: aes128gcm',$h,true)&&in_array('TTL: 86400',$h,true),'standard headers');
$authz=array_values(array_filter($h,fn($x)=>str_starts_with($x,'Authorization: vapid t=')))[0]??'';preg_match('/t=([^,]+), k=(.+)$/',$authz,$mm);[$jh,$jc,$js]=explode('.',$mm[1]);
$raw=gszB64uDec($js);$int=fn($b)=>(ord(ltrim($b,"\0")[0]??"\0")&0x80?"\0":'').ltrim($b,"\0");$r1=$int(substr($raw,0,32));$s1=$int(substr($raw,32));$der="\x30".chr(4+strlen($r1)+strlen($s1))."\x02".chr(strlen($r1)).$r1."\x02".chr(strlen($s1)).$s1;
pu(openssl_verify($jh.'.'.$jc,$der,gszEcPublicFromRaw(gszB64uDec($mm[2])),OPENSSL_ALGO_SHA256)===1&&json_decode(gszB64uDec($jc),true)['aud']==='https://push.example.invalid','VAPID JWT valid for the push service');
pu(gszPushNotify($p,'test','t1','x','y','index.php',$sender)===0&&count($calls)===1,'same event never twice');
gszSettingSet($p,'push_leads','0');pu(gszPushNotify($p,'leads','l1','x','y','index.php',$sender)===0,'kind switched off');gszSettingSet($p,'push_leads','1');
pu(gszPushNotify($p,'test','t2','x','y','javascript:alert(1)',$sender)===1&&json_decode($decrypt(end($calls)[1]),true)['url']==='index.php','unsafe target URL replaced');
pu(gszPushNotify($p,'test','t3','x','y','index.php',fn()=>500)===0&&(int)gszPushDevices($p)[0]['fail_count']===1,'temporary error counted');
pu(gszPushNotify($p,'test','t4','x','y','index.php',fn()=>410)===0&&!gszPushDevices($p),'expired device (410) removed');
gszPushSubscribe($p,1,$sub,'iPhone');
// Anfragen und Mieter-Meldungen lösen Push aus (hier ohne echten Versand: Gerät auf „fehlerhaft“ gestellt)
$p->exec('UPDATE push_subscriptions SET fail_count=20');
$p->exec("INSERT INTO mail_folders(name) VALUES('INBOX')");$p->prepare("INSERT INTO mail_messages(folder_id,uidvalidity,remote_uid,message_id,sender,recipients,subject,sent_at,body) VALUES(1,1,1,'<a@x.invalid>','a@x.invalid','b@x.invalid','Neue Website-Anfrage – Treppenhausreinigung',NOW(),?)")->execute(["Neue Anfrage über gebaeudeservice-zentrum.de\n\nLeistung: Treppenhausreinigung\nName: Erika Muster\nPLZ / Ort: 00000 Musterstadt\nNachricht: Test"]);
pu(gszLeadsFromMail($p)===1,'lead imported');$ev=$p->query("SELECT * FROM push_events WHERE kind='leads'")->fetch();pu($ev&&$ev['title']==='📩 Neue Anfrage: Treppenhausreinigung'&&str_contains($ev['body'],'Erika Muster')&&str_starts_with($ev['url'],'leads.php#anfrage-'),'new lead → push event');
$p->exec("INSERT INTO properties(name,address) VALUES('Musterweg 1','Musterweg 1'),('Ohne Winterdienst','x')");$t=gszLinkRenew($p,1,1,'report');gszDefectSubmit($p,gszLinkProperty($p,'report',$t),['kind'=>'Licht defekt','location'=>'Keller']);
pu((bool)$p->query("SELECT COUNT(*) FROM push_events WHERE kind='reports' AND title='🛠 Mieter-Meldung: Musterweg 1' AND body='Keller: Licht defekt'")->fetchColumn(),'new tenant report → push event');
$p->exec('UPDATE push_subscriptions SET fail_count=0');
// DWD-Warnungen (Format wie api.brightsky.dev)
$future=fn(int $h)=>gmdate('Y-m-d\TH:i:s+00:00',time()+$h*3600);
$alerts=['alerts'=>[
 ['id'=>1,'alert_id'=>'dwd-frost-1','status'=>'actual','event_code'=>22,'event_de'=>'FROST','headline_de'=>'Amtliche WARNUNG vor FROST','description_de'=>'Leichter Frost bis -5 °C.','severity'=>'minor','onset'=>$future(1),'expires'=>$future(20)],
 ['id'=>2,'alert_id'=>'dwd-glaette-1','status'=>'actual','event_code'=>84,'event_de'=>'GLÄTTE','headline_de'=>'Amtliche WARNUNG vor GLÄTTE','description_de'=>'Es tritt Glätte durch überfrierende Nässe auf.','severity'=>'minor','onset'=>$future(10),'expires'=>$future(18)],
 ['id'=>3,'alert_id'=>'dwd-alt','status'=>'actual','event_code'=>71,'event_de'=>'SCHNEEFALL','headline_de'=>'alt','description_de'=>'','severity'=>'moderate','onset'=>$future(-30),'expires'=>$future(-26)],
 ['id'=>4,'alert_id'=>'dwd-test','status'=>'test','event_code'=>84,'event_de'=>'GLÄTTE']],
 'location'=>['name'=>'Stadt Gütersloh','name_short'=>'Gütersloh']];
$urls=[];$http=function(string $u)use(&$urls,$alerts){$urls[]=$u;return json_encode($alerts);};
pu(gszWeatherKind('MARKANTE GLÄTTE')===2&&gszWeatherKind('GLATTEIS')===2&&gszWeatherKind('LEICHTER SCHNEEFALL')===2&&gszWeatherKind('FROST')===1&&gszWeatherKind('STURMBÖEN')===0,'event classification');
pu(count(gszWeatherPoints($p))===1&&gszWeatherPoints($p)[0]['name']==='Gütersloh','default place Gütersloh');
$calls=[];$tick=gszWeatherTick($p,true,$http,$sender);pu($tick['checked']&&$tick['new']===3&&str_contains($urls[0],'lat=51.9069&lon=8.3786'),'alerts fetched for the place (test alerts ignored)');
pu($tick['pushed']===1&&count($calls)===1&&str_contains(json_decode($decrypt($calls[0][1]),true)['title'],'Glätte – Gütersloh'),'only the new ice warning is pushed (not frost, not expired)');
pu(gszWeatherTick($p,false,$http,$sender)['checked']===false,'at most every 15 minutes');pu(gszWeatherTick($p,true,$http,$sender)['pushed']===0&&count($calls)===1,'same warning pushed only once');
$act=gszWeatherActive($p);pu(count($act)===2&&$act[0]['alert_id']==='dwd-glaette-1'&&$act[1]['event']==='FROST','active list: ice first, frost as hint, expired hidden');
pu(!gszWinterProperties($p),'no winter service objects yet');pu(gszWeatherPlan($p,1,'dwd-glaette-1')===0,'planning without winter objects creates nothing');puThrows(fn()=>gszWeatherPlan($p,1,'dwd-frost-1'),'frost does not plan winter service');
$p->exec("INSERT INTO recurring_jobs(property_id,title,frequency,active,next_due_date) VALUES(1,'Winterdienst','seasonal',1,CURDATE())");
pu(count(gszWinterProperties($p))===1,'object with rule „Winterdienst“ recognised');$p->exec("UPDATE weather_alerts SET planned_at=NULL");
pu(gszWeatherPlan($p,1,'dwd-glaette-1')===1&&gszWeatherPlan($p,1,'dwd-glaette-1')===0,'winter service task planned once');
$task=$p->query("SELECT * FROM tasks WHERE title LIKE 'Winterdienst%'")->fetch();pu((int)$task['property_id']===1&&$task['priority']==='urgent'&&$task['start_time']==='06:00:00'&&$task['due_date']===gszWeatherDay(gszWeatherAlert($p,'dwd-glaette-1'))&&str_contains($task['notes'],'DWD'),'urgent task at 06:00 on the warning day with DWD text');
// Winterdienst per Haken (ohne Regelaufgabe)
pu(gszWinterSave($p,1,['2','999','x'])===1&&count(gszWinterProperties($p))===2,'tick list: object 2 added, unknown ids ignored, rule object kept');
$p->exec("UPDATE weather_alerts SET planned_at=NULL");pu(gszWeatherPlan($p,1,'dwd-glaette-1')===1&&(int)$p->query("SELECT COUNT(*) FROM tasks WHERE property_id=2 AND title LIKE 'Winterdienst%'")->fetchColumn()===1,'ticked object planned too');
gszWinterSave($p,1,[]);pu(count(gszWinterProperties($p))===1,'untick removes it again');
// Automatisch einplanen bei neuer Warnung
gszSettingSet($p,'weather_tasks','auto');$alerts['alerts'][]=['id'=>5,'alert_id'=>'dwd-schnee-2','status'=>'actual','event_code'=>71,'event_de'=>'SCHNEEFALL','headline_de'=>'Amtliche WARNUNG vor SCHNEEFALL','description_de'=>'5 bis 10 cm.','onset'=>$future(30),'expires'=>$future(40)];
$http2=fn(string $u)=>json_encode($alerts);$tk=gszWeatherTick($p,true,$http2,$sender);pu($tk['planned']>=1&&$tk['pushed']===1&&str_contains(json_decode($decrypt(end($calls)[1]),true)['body'],'eingeplant'),'auto mode plans and says so in the push');
pu(gszWeatherTick($p,true,fn()=>null,$sender)['new']===0&&count(gszWeatherActive($p))===3,'service down: nothing lost');
gszSettingSet($p,'weather_points','Rheda:51.84,8.30;kaputt;Gütersloh:51.9,8.38');pu(count(gszWeatherPoints($p))===2,'several places, invalid lines ignored');
// Geplante Nachrichten (Mein Tag, Tonnen, Termine, Mail, System, Frost) – feste Uhrzeiten
require_once dirname(__DIR__).'/private/push_digest.php';$tz=new DateTimeZone('Europe/Berlin');$d=(new DateTimeImmutable('today',$tz));$day=$d->format('Y-m-d');$tm=$d->modify('+1 day')->format('Y-m-d');
$p->exec('DELETE FROM push_subscriptions');gszPushSubscribe($p,1,$sub,'iPhone');$calls=[];$last=function()use(&$calls,$decrypt){$c=end($calls);return $c?json_decode($decrypt($c[1]),true):[];};
$p->prepare("INSERT INTO tasks(property_id,title,due_date) VALUES(1,'A',?),(1,'B',?),(1,'Alt',?)")->execute([$day,$day,$d->modify('-2 day')->format('Y-m-d')]);
$p->prepare("INSERT INTO planning_appointments(title,day,start_time,end_time,address,reminder_minutes) VALUES('Besichtigung WEG',?,'10:00:00','11:00:00','Musterweg 1',30)")->execute([$day]);
$p->exec("INSERT INTO quick_notes(kind,title) VALUES('invoice','Rechnung X')");
pu(gszPushDigests($p,$d->setTime(5,50),$sender)===[],'nothing before 06:30');
$k=gszPushDigests($p,$d->setTime(6,45),$sender);pu(in_array('day:'.$day,$k,true)&&str_contains($last()['body'],'Aufgaben')&&str_contains($last()['body'],'1 von Vortagen offen')&&str_contains($last()['body'],'Termin 10:00 Besichtigung WEG')&&str_contains($last()['body'],'1 Rechnung offen'),'morning summary: '.($last()['body']??''));
pu(gszPushDigests($p,$d->setTime(7,15),$sender)===[],'morning summary only once');
pu(in_array('appt:'.$p->query('SELECT id FROM planning_appointments')->fetchColumn().':'.$day,gszPushDigests($p,$d->setTime(9,35),$sender),true)&&str_starts_with($last()['title'],'📅 10:00 Besichtigung WEG'),'appointment reminder 30 min before');

try{$p->prepare("INSERT INTO waste_events(property_id,collection_date,fraction,event_hash,status,address) VALUES(1,?,'gelb',?,'geprueft',''),(2,?,'gelb',?,'geprueft',''),(1,?,'papier',?,'geprueft','')")->execute([$tm,hash('sha256','h1'),$tm,hash('sha256','h2'),$tm,hash('sha256','h3')]);$wasteOk=true;}catch(Throwable $e){$wasteOk=false;fwrite(STDERR,'waste insert: '.$e->getMessage()."\n");}
if($wasteOk){$k=gszPushDigests($p,$d->setTime(18,10),$sender);pu(in_array('waste:'.$tm,$k,true)&&$last()['body']==='Gelbe Tonne (2 Objekte) · Papier (1 Objekt)','evening bins: '.($last()['body']??''));}
gszSettingSet($p,'push_frost','1');$p->exec("UPDATE weather_alerts SET planned_at=planned_at");$k=gszPushDigests($p,$d->setTime(12,0),$sender);pu((bool)array_filter($k,fn($x)=>str_starts_with($x,'frost:dwd-frost-1')),'frost hint when switched on');
gszSettingSet($p,'push_day','0');pu(!array_filter(gszPushDigests($p,$d->modify('+1 day')->setTime(6,40),$sender),fn($x)=>str_starts_with($x,'day:')),'switched-off kind stays quiet');
$p->exec("INSERT INTO automation_state(job_key,status,last_finished,last_error_code) VALUES('mail','BLOCKED',UTC_TIMESTAMP(),'GSZ_MAIL_FAILED') ON DUPLICATE KEY UPDATE status='BLOCKED',last_finished=UTC_TIMESTAMP(),last_error_code='GSZ_MAIL_FAILED'");
pu((bool)array_filter(gszPushDigests($p,$d->setTime(13,0),$sender),fn($x)=>str_starts_with($x,'system:job:mail')),'system warning for a stopped background job');
gszSettingSet($p,'push_mail','1');$p->prepare("INSERT INTO mail_messages(folder_id,uidvalidity,remote_uid,message_id,sender,recipients,subject,sent_at,body) VALUES(1,1,50,'<m50@x.invalid>','a@x.invalid','b@x.invalid','Hallo',NOW(),'x')")->execute();
gszPushDigests($p,$d->setTime(13,5),$sender);$before=count($calls);$p->prepare("INSERT INTO mail_messages(folder_id,uidvalidity,remote_uid,message_id,sender,recipients,subject,sent_at,body) VALUES(1,1,51,'<m51@x.invalid>','a@x.invalid','b@x.invalid','Neu',NOW(),'x'),(1,1,52,'<m52@x.invalid>','a@x.invalid','b@x.invalid','Neu2',NOW(),'x')")->execute();
gszPushDigests($p,$d->setTime(13,10),$sender);pu(count($calls)===$before+1&&$last()['title']==='✉️ 2 neue Mails','new mails summarised (no flood on first run)');
echo "Push PASS ($n checks; RFC 8291 encryption, VAPID, dedupe, DWD warnings, winter service).\n";
