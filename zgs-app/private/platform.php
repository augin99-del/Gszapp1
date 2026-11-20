<?php
declare(strict_types=1);
function gszAudit(PDO $pdo,?int $admin,string $action,array $detail=[]):void {
 $own=!$pdo->inTransaction();if($own)$pdo->beginTransaction();try{$pdo->query('SELECT id FROM app_mutex WHERE id=1 FOR UPDATE')->fetch();$prev=$pdo->query('SELECT entry_hash FROM app_audit ORDER BY id DESC LIMIT 1 FOR UPDATE')->fetchColumn()?:str_repeat('0',64);$json=json_encode($detail,JSON_UNESCAPED_UNICODE|JSON_THROW_ON_ERROR);$hash=hash('sha256',$prev.'|'.$admin.'|'.$action.'|'.$json);$pdo->prepare('INSERT INTO app_audit(admin_id,action,detail,previous_hash,entry_hash) VALUES(?,?,?,?,?)')->execute([$admin,$action,$json,$prev,$hash]);if($own)$pdo->commit();}catch(Throwable $e){if($own&&$pdo->inTransaction())$pdo->rollBack();throw $e;}
}
/** Persisted local limiter; stores hashes/counts only, works before schema migrations. */
function gszLoginAttempt(string $email,string $ip,bool $reset=false,?string $directory=null):bool {
 $directory??=__DIR__.'/uploads/security';if(!is_dir($directory)&&!mkdir($directory,0700,true)&&!is_dir($directory))throw new RuntimeException('Zugangsbremse nicht verfügbar.');
 $key=hash('sha256',mb_strtolower(trim($email)).'|'.$ip);$f=fopen($directory.'/'.$key,'c+');if(!$f||!flock($f,LOCK_EX))throw new RuntimeException('Zugangsbremse nicht verfügbar.');try{$row=json_decode(stream_get_contents($f),true)?:['since'=>time(),'count'=>0];if($reset||time()-(int)$row['since']>=900)$row=['since'=>time(),'count'=>0];$allowed=$row['count']<5;if(!$reset)$row['count']++;ftruncate($f,0);rewind($f);fwrite($f,json_encode($row));fflush($f);@chmod($directory.'/'.$key,0600);return $allowed;}finally{flock($f,LOCK_UN);fclose($f);}
}
function gszSafeStorage(string $area):string {
 if(!in_array($area,['mail','estate'],true))throw new InvalidArgumentException('Speicherbereich ungültig.');$p=__DIR__.'/uploads/'.$area;if(!is_dir($p)&&!mkdir($p,0700,true)&&!is_dir($p))throw new RuntimeException('Privater Speicher nicht verfügbar.');return realpath($p)?:throw new RuntimeException('Speicher fehlt.');
}
function gszStoreBytes(string $area,string $bytes):array {
 if(strlen($bytes)<1||strlen($bytes)>10*1024*1024)throw new InvalidArgumentException('Datei muss 1 Byte bis 10 MB groß sein.');$hash=hash('sha256',$bytes);$path=gszSafeStorage($area).'/'.$hash;if(is_link($path))throw new RuntimeException('Ungültiger Speicherpfad.');if(!is_file($path)){$f=fopen($path,'x');if(!$f)throw new RuntimeException('Speicherfehler.');try{if(fwrite($f,$bytes)!==strlen($bytes))throw new RuntimeException('Datei unvollständig.');}finally{fclose($f);}@chmod($path,0600);}if(hash_file('sha256',$path)!==$hash)throw new RuntimeException('Dateiintegrität fehlgeschlagen.');return ['stored_name'=>$hash,'sha256'=>$hash,'size_bytes'=>strlen($bytes)];
}
function gszReadBytes(string $area,string $name,string $hash):string {
 if(!preg_match('/^[a-f0-9]{64}$/D',$name)||!hash_equals($name,$hash))throw new RuntimeException('Ungültiger Speicherverweis.');$p=gszSafeStorage($area).'/'.$name;if(is_link($p)||!is_file($p))throw new RuntimeException('Datei fehlt.');$s=file_get_contents($p);if(!hash_equals($hash,hash('sha256',$s)))throw new RuntimeException('Integritätsprüfung fehlgeschlagen.');return $s;
}
function gszName(string $name):string{return mb_substr(preg_replace('~[\x00-\x1F\x7F/\\\\]+~u','_',basename(str_replace('\\','/',$name)))?:'datei',0,200);}
function gszAuditVerify(PDO $pdo):array {
 $q=$pdo->query('SELECT * FROM app_audit ORDER BY id');$previous=str_repeat('0',64);$count=0;while($r=$q->fetch()){$count++;$hash=hash('sha256',$previous.'|'.$r['admin_id'].'|'.$r['action'].'|'.$r['detail']);if(!hash_equals($previous,$r['previous_hash'])||!hash_equals($hash,$r['entry_hash']))return ['valid'=>false,'entries'=>$count];$previous=$r['entry_hash'];}return ['valid'=>true,'entries'=>$count,'last_hash'=>$previous];
}
function gszRegisterRequestAudit(PDO $pdo):void {
 if(($_SERVER['REQUEST_METHOD']??'')!=='POST')return;
 register_shutdown_function(static function()use($pdo):void{
  if(empty($_SESSION['admin_id'])||http_response_code()!==303)return;
  try{$q=$pdo->query("SELECT COUNT(*) FROM app_migrations WHERE version='230_mail_security'");if(!(int)$q->fetchColumn())return;$action=(string)($_POST['action']??'post');if(!preg_match('/^[a-z_]{1,40}$/D',$action))$action='post';$detail=['page'=>basename((string)($_SERVER['SCRIPT_NAME']??''))];foreach(['id','task_id','property_id','event_id'] as $key)if(isset($_POST[$key])&&ctype_digit((string)$_POST[$key]))$detail[$key]=(int)$_POST[$key];gszAudit($pdo,(int)$_SESSION['admin_id'],'request_'.$action,$detail);}catch(Throwable $e){error_log('GSZ_AUDIT_UNAVAILABLE');}
 });
}
function gszSessionExpired(array $session,int $now,int $idle):bool {return $now-(int)($session['last_activity']??$now)>max(300,min(28800,$idle))||$now-(int)($session['login_at']??$now)>28800;}
