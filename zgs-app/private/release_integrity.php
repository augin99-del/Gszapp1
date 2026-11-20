<?php
declare(strict_types=1);
/** Transfer consistency check, not a substitute for a trusted/signed source. Reads code only. */
function gszReleaseIntegrity(?string $root=null):array {
 $root??=dirname(__DIR__);$root=realpath($root);if(!$root)throw new RuntimeException('Releaseverzeichnis fehlt.');$manifest=$root.'/private/release_integrity.json';if(!is_file($manifest)||is_link($manifest)||filesize($manifest)>2*1024*1024)return ['status'=>'FAIL','missing'=>['private/release_integrity.json'],'changed'=>[]];
 $data=json_decode(file_get_contents($manifest),true,32,JSON_THROW_ON_ERROR);if(($data['release']??'')!=='COMPLETE370'||!is_array($data['files']??null)||!$data['files']||count($data['files'])>8192)throw new RuntimeException('Ungültiges Release-Dateimanifest.');$missing=[];$changed=[];
 foreach($data['files'] as $relative=>$hash){$parts=explode('/',$relative);if(!preg_match('~^(private|public)/[a-zA-Z0-9_@./-]+$~D',$relative)||in_array('..',$parts,true)||!preg_match('/^[a-f0-9]{64}$/D',(string)$hash)||in_array($relative,['private/config.php','private/release_integrity.json'],true)||str_starts_with($relative,'private/uploads/')||str_starts_with($relative,'private/secrets/'))throw new RuntimeException('Unsicherer Manifestpfad.');$path=$root.'/'.$relative;$real=realpath($path);if(!$real||!is_file($path))$missing[]=$relative;elseif(is_link($path)||!str_starts_with($real,$root.'/')||!hash_equals($hash,hash_file('sha256',$path)))$changed[]=$relative;}
 return ['status'=>$missing||$changed?'FAIL':'PASS','missing'=>$missing,'changed'=>$changed];
}
