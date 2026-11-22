<?php
declare(strict_types=1);
require_once __DIR__.'/config_loader.php';require_once __DIR__.'/managed_config.php';require_once __DIR__.'/connection_diagnostics.php';
function gszSetupDirectory():string {return __DIR__.'/runtime-setup';}
function gszSetupLocked():bool {return is_file(gszSetupDirectory().'/complete.json');}
function gszSetupInitialize():void {
 gszPrivateDirectory(gszSetupDirectory());if(gszSetupLocked())return;$code=gszSetupDirectory().'/access-code.txt';if(is_link(gszSetupDirectory().'/state.lock'))throw new RuntimeException('GSZ_PRIVATE_PATH');$lock=fopen(gszSetupDirectory().'/state.lock','c+b');if(!$lock||!flock($lock,LOCK_EX))throw new RuntimeException('GSZ_PRIVATE_WRITE');@chmod(gszSetupDirectory().'/state.lock',0600);try{if(!file_exists($code))gszPrivateWrite($code,bin2hex(random_bytes(32))."\n");}finally{flock($lock,LOCK_UN);fclose($lock);}
}
function gszSetupAuthorized():bool {return !gszSetupLocked()&&!empty($_SESSION['setup_authorized'])&&(int)$_SESSION['setup_authorized']>time()-1800;}
function gszSetupRate(string $group):void {
 $path=gszSetupDirectory().'/rate-'.$group.'.json';if(is_link($path))throw new RuntimeException('GSZ_PRIVATE_PATH');$f=fopen($path,'c+b');if(!$f||!flock($f,LOCK_EX))throw new RuntimeException('GSZ_PRIVATE_WRITE');@chmod($path,0600);try{$r=json_decode(stream_get_contents($f)?:'{}',true)?:[];if(($r['until']??0)<time())$r=['until'=>time()+900,'count'=>0];if(++$r['count']>12)throw new RuntimeException('Zu viele Versuche. Bitte15Minuten warten.');rewind($f);ftruncate($f,0);fwrite($f,json_encode($r));fflush($f);}finally{flock($f,LOCK_UN);fclose($f);}
}
function gszSetupAuthorize(string $code):void {
 gszSetupRate('pair');$expected=trim(gszPrivateRead(gszSetupDirectory().'/access-code.txt',100));if(!preg_match('/^[a-f0-9]{64}$/D',$code)||!hash_equals($expected,$code))throw new RuntimeException('Einrichtungscode nicht bestätigt. Ausschließlich die private Datei im STRATO-Dateimanager verwenden.');session_regenerate_id(true);$_SESSION=['setup_authorized'=>time(),'setup_csrf'=>bin2hex(random_bytes(32))];
}
function gszSetupCandidatePath():string {return gszSetupDirectory().'/candidate-'.hash('sha256',session_id()).'.json';}
function gszSetupCandidateSave(array $config):void {
 if(!gszSetupAuthorized())throw new RuntimeException('Einrichtungsanmeldung erforderlich.');$keyPath=gszSetupDirectory().'/candidate.key';$keyLock=fopen(gszSetupDirectory().'/state.lock','c+b');if(!$keyLock||!flock($keyLock,LOCK_EX))throw new RuntimeException('GSZ_PRIVATE_WRITE');try{if(!file_exists($keyPath))gszPrivateWrite($keyPath,random_bytes(32));}finally{flock($keyLock,LOCK_UN);fclose($keyLock);}$key=gszPrivateRead($keyPath,32);if(strlen($key)!==32)throw new RuntimeException('GSZ_MANAGED_CONFIG');$iv=random_bytes(12);$tag='';$encrypted=openssl_encrypt(json_encode($config,JSON_THROW_ON_ERROR),'aes-256-gcm',$key,OPENSSL_RAW_DATA,$iv,$tag,'GSZ-setup-candidate');if($encrypted===false)throw new RuntimeException('GSZ_MANAGED_CONFIG');gszPrivateWrite(gszSetupCandidatePath(),json_encode(['iv'=>base64_encode($iv),'tag'=>base64_encode($tag),'ciphertext'=>base64_encode($encrypted)],JSON_THROW_ON_ERROR));unset($_SESSION['setup_owner'],$_SESSION['setup_migrated']);
}
function gszSetupCandidate():array {
 if(!gszSetupAuthorized()||!is_file(gszSetupCandidatePath()))throw new RuntimeException('Verbindung zuerst testen.');$r=json_decode(gszPrivateRead(gszSetupCandidatePath()),true,16,JSON_THROW_ON_ERROR);$plain=openssl_decrypt(base64_decode($r['ciphertext'],true),'aes-256-gcm',gszPrivateRead(gszSetupDirectory().'/candidate.key',32),OPENSSL_RAW_DATA,base64_decode($r['iv'],true),base64_decode($r['tag'],true),'GSZ-setup-candidate');if($plain===false)throw new RuntimeException('GSZ_MANAGED_CONFIG');return json_decode($plain,true,16,JSON_THROW_ON_ERROR);
}
function gszSetupOwner(PDO $pdo,string $email,string $password):void {
 gszSetupRate('owner');$q=$pdo->prepare('SELECT id,password_hash FROM admins WHERE email=?');$q->execute([$email]);$r=$q->fetch();if(!$r||strlen($password)>200||!password_verify($password,$r['password_hash']))throw new RuntimeException('Bestehende Inhaberanmeldung nicht bestätigt. Kein vorhandenes Konto wurde verändert.');$_SESSION['setup_owner']=['id'=>(int)$r['id'],'until'=>time()+900,'candidate'=>hash('sha256',json_encode(gszSetupCandidate()))];
}
function gszSetupRequireOwner(PDO $pdo):void {
 $hasAdmins=(int)$pdo->query('SELECT COUNT(*) FROM admins')->fetchColumn();if($hasAdmins&&((int)($_SESSION['setup_owner']['until']??0)<time()||!hash_equals((string)($_SESSION['setup_owner']['candidate']??''),hash('sha256',json_encode(gszSetupCandidate())))))throw new RuntimeException('Vorhandenes Inhaberpasswort vor Migration/Übernahme erneut bestätigen.');
}
function gszSetupRequireActive():void {
 $candidate=gszSetupCandidate();$active=gszLoadConfig();$a=[];$b=[];foreach(['db_host','db_port','db_socket','db_name','db_user','db_pass'] as $key){$a[$key]=(string)($candidate[$key]??($key==='db_port'?3306:''));$b[$key]=(string)($active[$key]??($key==='db_port'?3306:''));}if(!hash_equals(hash('sha256',json_encode($a)),hash('sha256',json_encode($b))))throw new RuntimeException('Eine andere Datenbankkonfiguration wurde inzwischen übernommen. Eigene Verbindung erneut prüfen und ausdrücklich übernehmen.');
}
function gszSetupCreateAdmin(PDO $pdo,string $email,string $password,string $repeat):void {
 if(!filter_var($email,FILTER_VALIDATE_EMAIL)||strlen($email)>190||strlen($password)<14||strlen($password)>200||!hash_equals($password,$repeat))throw new RuntimeException('Gültige E-Mail und gleiches individuelles Passwort mit14–200Zeichen erforderlich.');$pdo->beginTransaction();try{if(!$pdo->query('SELECT id FROM app_mutex WHERE id=1 FOR UPDATE')->fetch())throw new RuntimeException('System-Sperrdatensatz fehlt. Datenbanksicherung/Import prüfen.');if((int)$pdo->query('SELECT COUNT(*) FROM admins')->fetchColumn())throw new RuntimeException('Vorhandene Inhaber werden nicht überschrieben.');$pdo->prepare('INSERT INTO admins(email,password_hash) VALUES(?,?)')->execute([$email,password_hash($password,PASSWORD_DEFAULT)]);$pdo->commit();gszSetupOwner($pdo,$email,$password);}catch(Throwable $e){if($pdo->inTransaction())$pdo->rollBack();throw $e;}
}
function gszSetupFinish(PDO $pdo):void {
 gszSetupRequireActive();gszSetupRequireOwner($pdo);if(empty($_SESSION['setup_migrated'])||!(int)$pdo->query('SELECT COUNT(*) FROM admins')->fetchColumn())throw new RuntimeException('Schema und Inhaberanmeldung abschließen.');require_once __DIR__.'/system_check.php';gszManagedSave(['maintenance'=>false]);$report=gszSystemCheck(gszLoadConfig(),$pdo);if($report['blocks'])throw new RuntimeException('Systemprüfung meldet noch Blocker. Erst beheben.');gszPrivateWrite(gszSetupDirectory().'/complete.json',json_encode(['completed_at'=>gmdate('c'),'schema'=>'370','live_strato_verified'=>false],JSON_THROW_ON_ERROR));@unlink(gszSetupDirectory().'/access-code.txt');foreach(glob(gszSetupDirectory().'/candidate-*.json')?:[] as $f)if(is_file($f)&&!is_link($f))@unlink($f);$_SESSION=[];session_regenerate_id(true);
}
