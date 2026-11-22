<?php
declare(strict_types=1);
if(PHP_SAPI!=='cli'){http_response_code(403);exit;}require __DIR__.'/secret_vault.php';
function vaultWriteNew(string $path,string $bytes):void {
 $parent=realpath(dirname($path));$public=realpath(dirname(__DIR__).'/public');if($path===''||$path[0]!=='/'||!$parent||($public&&($parent===$public||str_starts_with($parent,$public.'/')))||file_exists($path)||is_link($path))throw new RuntimeException('Privaten neuen Zielpfad verwenden.');$mask=umask(0077);try{$handle=fopen($path,'x');if(!$handle)throw new RuntimeException('Datei nicht angelegt.');try{if(fwrite($handle,$bytes)!==strlen($bytes))throw new RuntimeException('Datei unvollständig.');}finally{fclose($handle);}}finally{umask($mask);}
}
try{
 gszVaultCrypto();$command=$argv[1]??'';
 if($command==='init'&&count($argv)===3){vaultWriteNew($argv[2],random_bytes(32));echo "Privater Schlüssel angelegt. Nicht in Chat/Repository/Paket übernehmen.\n";}
 elseif($command==='encrypt'&&count($argv)===4){$raw=stream_get_contents(STDIN,65537);if(strlen($raw)>65536)throw new RuntimeException('Eingabe zu groß.');$values=json_decode($raw,true,8,JSON_THROW_ON_ERROR);sodium_memzero($raw);if(!is_array($values))throw new RuntimeException('JSON-Objekt erforderlich.');$key=gszVaultRead($argv[2],32);try{$bytes=gszVaultSeal($values,$key);vaultWriteNew($argv[3],$bytes);}finally{sodium_memzero($key);}echo "Verschlüsselte Konfiguration angelegt. Keine Klartextausgabe.\n";}
 else throw new RuntimeException('Aufruf: init KEYDATEI oder encrypt KEYDATEI VAULTDATEI; JSON ausschließlich über geschütztes stdin.');
}catch(Throwable $e){fwrite(STDERR,"Vault-Vorgang fehlgeschlagen. Aufruf, private Pfade/Rechte und Eingabe prüfen. Keine Dateien überschrieben.\n");exit(1);}
