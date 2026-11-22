<?php
declare(strict_types=1);
require_once __DIR__.'/planning_store.php';require_once __DIR__.'/routing.php';
function routeSettings(PDO $pdo):array {$r=$pdo->query('SELECT * FROM planning_settings WHERE id=1')->fetch();return $r;}
function routeMatrix(PDO $pdo,array $addresses,?GszRoutingProvider $provider,int $ttl=3600,int $maxPoints=25):array {
 if(!$provider)return [null,[],['Kein Routingprovider aktiviert.']];$addresses=array_values(array_unique(array_filter($addresses)));if(count($addresses)>min(25,max(2,$maxPoints)))return [null,[],['Routinglimit: maximal 25 Adressen. Offline-Fallback.']];
 $locs=$pdo->query('SELECT * FROM routing_locations ORDER BY location_key')->fetchAll();$points=[];$indices=[];
 foreach($addresses as $a){$found=null;foreach($locs as $l)if($l['address']===$a&&$l['quality']==='manuell_geprueft'&&$l['latitude']!==null&&strtotime($l['checked_at'].' UTC')>=time()-90*86400)$found=$l;if(!$found)return [null,[],['Geprüfte Koordinaten fehlen/veraltet: '.$a]];$indices[$a]=count($points);$points[]=$found;}
 if(count($points)<2)return [null,[],['Mindestens zwei geprüfte Routingpunkte erforderlich.']];
 $key=hash('sha256',json_encode([$provider->id(),$points]));$q=$pdo->prepare('SELECT payload FROM routing_cache WHERE cache_key=? AND expires_at>NOW()');$q->execute([$key]);$cached=$q->fetchColumn();
 try{$m=$cached?json_decode($cached,true,512,JSON_THROW_ON_ERROR):$provider->matrix($points);$m=routeValidateMatrix($m,count($points));if(strtotime($m['timestamp'])<time()-min(86400,max(60,$ttl)))throw new RuntimeException('Veraltete Routingwerte.');if(!$cached){$expires=gmdate('Y-m-d H:i:s',time()+min(86400,max(60,$ttl)));$pdo->prepare('INSERT INTO routing_cache(cache_key,payload,expires_at) VALUES(?,?,?) ON DUPLICATE KEY UPDATE payload=VALUES(payload),expires_at=VALUES(expires_at)')->execute([$key,json_encode($m,JSON_THROW_ON_ERROR),$expires]);}return [$m,$indices,[]];}
 catch(Throwable $e){return [null,[],['Routingprovider fehlgeschlagen; Offline-Fallback ohne erfundene Distanzen.']];}
}
function routeSnapshot(PDO $pdo):string {return hash('sha256',planFingerprint(planLoad($pdo)).json_encode($pdo->query('SELECT * FROM routing_locations ORDER BY location_key')->fetchAll()));}
function routeSaveProposal(PDO $pdo,int $admin,string $day,?GszRoutingProvider $provider=null,int $ttl=3600,int $maxPoints=25):string {
 if(!zgsValidDate($day))throw new InvalidArgumentException('Ungültiger Routentag.');$data=planLoad($pdo);$tasks=array_values(array_filter($data['tasks'],fn($t)=>$t['due_date']===$day&&!$t['done']));usort($tasks,fn($a,$b)=>[(int)($a['sort_order']?:$a['planned_order']),$a['start_time']??'99:99',$a['id']]<=>[(int)($b['sort_order']?:$b['planned_order']),$b['start_time']??'99:99',$b['id']]);$apps=array_values(array_filter($data['appointments'],fn($a)=>$a['day']===$day));$settings=$data['settings'];$addresses=[$settings['route_start_address']??'',$settings['route_end_address']??''];foreach($tasks as $t)$addresses[]=$t['address']??'';foreach($apps as $a)$addresses[]=$a['address'];
 // Incomplete route (unknown start/end/task/appointment) falls back for the whole tour.
 $incomplete=in_array('',array_merge([$settings['route_start_address']??'',$settings['route_end_address']??''],array_map(fn($t)=>$t['address']??'',$tasks),array_column($apps,'address')),true);
 [$matrix,$indices,$warnings]=$incomplete?[null,[],['Start/Ziel oder eine Besuchsadresse fehlt; vollständiger Offline-Modus.']]:routeMatrix($pdo,$addresses,$provider,$ttl,$maxPoints);
 $starts=array_column($data['starts'],'start_time','day');$route=routeDay($tasks,$apps,$settings,$starts[$day]??$settings['start_time'],$matrix,$indices);$route['warnings']=array_merge($route['warnings'],$warnings);
 $token=bin2hex(random_bytes(32));$pdo->prepare('INSERT INTO planning_proposals(token,admin_id,fingerprint,payload) VALUES(?,?,?,?)')->execute([$token,$admin,routeSnapshot($pdo),json_encode(['kind'=>'route','day'=>$day,'route'=>$route],JSON_THROW_ON_ERROR)]);return $token;
}
function routeApply(PDO $pdo,int $admin,string $token):void {
 $pdo->beginTransaction();try{$q=$pdo->prepare('SELECT * FROM planning_proposals WHERE token=? AND admin_id=? FOR UPDATE');$q->execute([$token,$admin]);$p=$q->fetch();if(!$p)throw new RuntimeException('Route nicht gefunden.');if($p['applied_at']){$pdo->commit();return;}if(strtotime($p['created_at'].' UTC')<time()-7200)throw new RuntimeException('Route abgelaufen.');foreach(['planning_settings','planning_days','planning_appointments','properties','recurring_jobs','tasks','routing_locations'] as $t)$pdo->query('SELECT * FROM '.$t.' FOR UPDATE')->fetchAll();if(!hash_equals($p['fingerprint'],routeSnapshot($pdo)))throw new RuntimeException('Bestand geändert. Route neu berechnen.');$payload=json_decode($p['payload'],true,512,JSON_THROW_ON_ERROR);if(($payload['kind']??'')!=='route')throw new RuntimeException('Kein Routenvorschlag.');if($payload['route']['timestamp']!==null&&strtotime($payload['route']['timestamp'])<time()-3600)throw new RuntimeException('Routingwerte älter als eine Stunde. Neu berechnen.');
 $versionSql=opsReady($pdo)?',row_version=row_version+1':'';
 foreach($payload['route']['timeline']['rows'] as $i=>$r){$t=$r['task'];if($r['end']!==null&&$r['end']>1439)throw new RuntimeException('Route über Mitternacht.');if(!planFixed($t)&&(int)($t['planned_order']??0)!==$i+1)$pdo->prepare('UPDATE tasks SET planned_order=?'.$versionSql.' WHERE id=? AND done=0')->execute([$i+1,$t['id']]);}
 $pdo->prepare('UPDATE planning_proposals SET applied_at=NOW(),fingerprint=? WHERE token=?')->execute([routeSnapshot($pdo),$token]);$pdo->commit();}catch(Throwable $e){if($pdo->inTransaction())$pdo->rollBack();throw $e;}
}
function routeConfirmedDay(PDO $pdo,string $day):?array {
 $q=$pdo->prepare("SELECT * FROM planning_proposals WHERE applied_at IS NOT NULL AND JSON_UNQUOTE(JSON_EXTRACT(payload,'$.kind'))='route' AND JSON_UNQUOTE(JSON_EXTRACT(payload,'$.day'))=? ORDER BY applied_at DESC,created_at DESC LIMIT 1");$q->execute([$day]);$p=$q->fetch();if(!$p)return null;
 if(!hash_equals($p['fingerprint'],routeSnapshot($pdo)))return ['stale'=>true];$v=json_decode($p['payload'],true,512,JSON_THROW_ON_ERROR);$route=$v['route'];if($route['timestamp']!==null&&strtotime($route['timestamp'])<time()-3600)return ['stale'=>true];return ['stale'=>false,'route'=>$route,'token'=>$p['token']];
}
