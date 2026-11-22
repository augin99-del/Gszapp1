<?php
declare(strict_types=1);
/** Optional server-side vault. Standard libsodium AEAD; never accept paths from HTTP. */
function gszVaultKeys(array $values):array {
 $allowed=['db_pass','mail_pass','setup_key','db_user','mail_user'];if(!$values||array_is_list($values))throw new RuntimeException('Ungültige Geheimniskonfiguration.');
 foreach($values as $key=>$value)if(!in_array($key,$allowed,true)||!is_string($value)||strlen($value)>8192)throw new RuntimeException('Nicht unterstützter Geheimnisschlüssel/Wert.');return $values;
}
function gszVaultCrypto():void {if(!function_exists('sodium_crypto_aead_xchacha20poly1305_ietf_encrypt'))throw new RuntimeException('libsodium für verschlüsselte Konfiguration erforderlich.');}
function gszVaultRead(string $path,int $maximum):string {
 if($path===''||$path[0]!=='/'||is_link($path)||!is_file($path))throw new RuntimeException('Privater Konfigurationspfad ungültig.');$real=realpath($path);$public=realpath(dirname(__DIR__).'/public');if(!$real||($public&&str_starts_with($real,$public.'/'))||(fileperms($path)&0077)!==0||filesize($path)>$maximum)throw new RuntimeException('Konfiguration muss privat, begrenzt und außerhalb des Webroots liegen.');$bytes=file_get_contents($path);if($bytes===false||strlen($bytes)>$maximum)throw new RuntimeException('Konfiguration nicht lesbar.');return $bytes;
}
function gszVaultSeal(array $values,string $key):string {
 gszVaultCrypto();if(strlen($key)!==32)throw new RuntimeException('Konfigurationsschlüssel ungültig.');$plain=json_encode(gszVaultKeys($values),JSON_THROW_ON_ERROR);$nonce=random_bytes(24);try{$cipher=sodium_crypto_aead_xchacha20poly1305_ietf_encrypt($plain,'GSZ-vault-v1',$nonce,$key);return json_encode(['version'=>1,'nonce'=>base64_encode($nonce),'ciphertext'=>base64_encode($cipher)],JSON_THROW_ON_ERROR);}finally{sodium_memzero($plain);}
}
function gszVaultOpen(string $sealed,string $key):array {
 gszVaultCrypto();if(strlen($key)!==32||strlen($sealed)>70000)throw new RuntimeException('Konfigurationsgröße/Schlüssel ungültig.');try{$record=json_decode($sealed,true,8,JSON_THROW_ON_ERROR);}catch(Throwable $e){throw new RuntimeException('Verschlüsselte Konfiguration ungültig.');}
 if(!is_array($record))throw new RuntimeException('Verschlüsselte Konfiguration ungültig.');
 $nonce=base64_decode((string)($record['nonce']??''),true);$cipher=base64_decode((string)($record['ciphertext']??''),true);if(($record['version']??null)!==1||$nonce===false||strlen($nonce)!==24||$cipher===false||strlen($cipher)<16)throw new RuntimeException('Verschlüsselte Konfiguration ungültig.');$plain=sodium_crypto_aead_xchacha20poly1305_ietf_decrypt($cipher,'GSZ-vault-v1',$nonce,$key);if($plain===false)throw new RuntimeException('Authentizitätsprüfung der Konfiguration fehlgeschlagen.');try{$values=json_decode($plain,true,8,JSON_THROW_ON_ERROR);if(!is_array($values))throw new RuntimeException('Ungültige Konfiguration.');return gszVaultKeys($values);}finally{sodium_memzero($plain);}
}
function gszVaultLoad(string $file,string $keyFile):array {$key=gszVaultRead($keyFile,32);try{return gszVaultOpen(gszVaultRead($file,70000),$key);}finally{sodium_memzero($key);}}
