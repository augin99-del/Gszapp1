<?php
declare(strict_types=1);
/**
 * Postfachzugang einmal eintragen: Passwort (und Postfachadresse) liegen verschlüsselt im vorhandenen Secret-Vault-Format
 * (libsodium XChaCha20-Poly1305, gszVaultSeal/gszVaultOpen) unter private/secrets/ – außerhalb des Webroots, Ordner 0700,
 * Dateien 0600, Schlüssel getrennt von den Daten. Derselbe Zugang gilt für IMAP, SMTP und den Cron-Abruf.
 *
 * Schutzschaltung: Lehnt der Mailserver die Anmeldung ab, werden ALLE weiteren automatischen Anmeldeversuche
 * (Seitenaufrufe, Senden, Cron) ohne Netzwerkzugriff gestoppt, bis das Passwort neu eingetragen oder bewusst
 * ein einzelner Test ausgelöst wird. So entstehen keine Fehlversuchsserien, die das Postfach sperren könnten.
 */
require_once __DIR__.'/secret_vault.php';require_once __DIR__.'/managed_config.php';
final class GszMailAuthBlocked extends RuntimeException {}
function gszMailSecretsDir():string {$dir=__DIR__.'/secrets';gszPrivateDirectory($dir);return $dir;}
function gszMailVaultPaths():array {$d=__DIR__.'/secrets';return ['vault'=>$d.'/mail.vault','key'=>$d.'/mail.key','state'=>$d.'/mail-auth.json'];}
function gszMailCredentialExists():bool {$p=gszMailVaultPaths();return is_file($p['vault'])&&is_file($p['key']);}
/** Returns ['mail_user'=>…, 'mail_pass'=>…] or null. Never logs or prints the value. */
function gszMailCredentialLoad():?array {
 if(!gszMailCredentialExists())return null;$p=gszMailVaultPaths();$v=gszVaultLoad($p['vault'],$p['key']);if(!isset($v['mail_pass']))return null;return ['mail_user'=>(string)($v['mail_user']??''),'mail_pass'=>(string)$v['mail_pass']];
}
function gszMailCredentialSave(string $user,string $password):void {
 if(!filter_var($user,FILTER_VALIDATE_EMAIL)||preg_match('/[\r\n]/',$user))throw new InvalidArgumentException('Bitte die vollständige Postfachadresse eintragen.');
 if($password===''||strlen($password)>1024||preg_match('/[\x00-\x1f\x7f]/',$password))throw new InvalidArgumentException('Bitte das Postfach-Passwort eintragen.');
 gszVaultCrypto();$dir=gszMailSecretsDir();$p=gszMailVaultPaths();
 if(!is_file($p['key'])){$key=random_bytes(32);try{gszPrivateWrite($p['key'],$key);}finally{sodium_memzero($key);}}
 $key=gszVaultRead($p['key'],32);try{gszPrivateWrite($p['vault'],gszVaultSeal(['mail_user'=>$user,'mail_pass'=>$password],$key));}finally{sodium_memzero($key);}
 clearstatcache();foreach([$dir=>0700,$p['key']=>0600,$p['vault']=>0600] as $f=>$mode)@chmod($f,$mode);
 gszMailAuthReset();try{gszManagedRemove(['mail_pass']);}catch(Throwable $e){}
}
/** Zugang vollständig löschen: Vault, Schlüssel und Status; Postfach wird deaktiviert. */
function gszMailCredentialDelete():void {
 $p=gszMailVaultPaths();foreach([$p['vault'],$p['key'],$p['state']] as $f)if(is_file($f)&&!is_link($f))@unlink($f);
 try{gszManagedRemove(['mail_pass']);gszManagedSave(['mail_enabled'=>false,'allow_mail_send'=>false]);}catch(Throwable $e){}
}
function gszMailAuthState():array {$p=gszMailVaultPaths();if(!is_file($p['state']))return ['status'=>'unknown'];$s=json_decode((string)@file_get_contents($p['state']),true);return is_array($s)?$s:['status'=>'unknown'];}
function gszMailAuthWrite(array $state):void {gszMailSecretsDir();gszPrivateWrite(gszMailVaultPaths()['state'],json_encode($state,JSON_THROW_ON_ERROR));}
function gszMailAuthReset():void {$p=gszMailVaultPaths();if(is_file($p['state']))@unlink($p['state']);}
function gszMailAuthBlockedNow():bool {return (gszMailAuthState()['status']??'')==='failed';}
/** Call before every remote login. $explicitRetry: the owner pressed „Verbindung prüfen“ (one deliberate attempt). */
function gszMailAuthGuard(bool $explicitRetry=false):void {
 if($explicitRetry)return;if(gszMailAuthBlockedNow())throw new GszMailAuthBlocked('Das Mail-Passwort wurde vom Postfach abgelehnt. Bitte unter „E-Mail einrichten“ neu eintragen.');
}
function gszMailIsAuthError(Throwable $e):bool {return (bool)preg_match('/auth|anmeld|login|password|passwort|credential|zugang/i',$e->getMessage())&&!preg_match('/tls|ssl|zertifikat|certificate/i',$e->getMessage());}
function gszMailAuthResult(?Throwable $e,string $protocol):void {
 try{if($e===null){if((gszMailAuthState()['status']??'')!=='ok')gszMailAuthWrite(['status'=>'ok','at'=>gmdate('c'),'protocol'=>$protocol]);return;}
  if(gszMailIsAuthError($e))gszMailAuthWrite(['status'=>'failed','at'=>gmdate('c'),'protocol'=>$protocol]);}catch(Throwable $ignored){}
}
/** Move a password stored by older versions (managed config or config.php) into the vault once. */
function gszMailCredentialMigrate(array $config):bool {
 if(gszMailCredentialExists()||empty($config['mail_pass'])||empty($config['mail_user']))return false;gszMailCredentialSave((string)$config['mail_user'],(string)$config['mail_pass']);return true;
}
