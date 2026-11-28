<?php
declare(strict_types=1);
/**
 * Schema 440: Push-Benachrichtigungen (Web Push) ohne Fremddienst und ohne Bibliothek.
 * - Eigene VAPID-Schlüssel (P-256) in private/secrets/push_vapid.pem (0600), nie in der Datenbank.
 * - Verschlüsselung nach RFC 8291 (aes128gcm), Anmeldung beim Push-Dienst nach RFC 8292 (VAPID, ES256).
 * - Inhalte bleiben kurz und ohne Telefonnummern/Adressen; Details sieht man erst nach dem Antippen in der Zentrale.
 */
require_once __DIR__.'/platform.php';require_once __DIR__.'/leads.php';
/** Arten: Schlüssel => [Titel, Beschreibung, Symbol]. Reihenfolge = Anzeige in den Einstellungen. */
const GSZ_PUSH_KINDS=[
 'weather'=>['Winterdienst','Glätte, Glatteis und Schnee (amtliche DWD-Warnung)','❄️'],
 'frost'=>['Frost-Hinweis','Leichter oder strenger Frost (DWD) – ohne Winterdienst-Einsatz','🌡️'],
 'day'=>['Mein Tag','Morgens: Aufgaben, Überfälliges, Termine, offene Rechnungen','☀️'],
 'waste'=>['Tonnen','Abends: welche Tonnen morgen an wie vielen Objekten abgeholt werden','🗑️'],
 'appt'=>['Termine','Erinnerung vor jedem Termin','📅'],
 'leads'=>['Anfragen','Neue Anfrage über das Website-Formular','📩'],
 'reports'=>['Mieter-Meldungen','Neue Meldung per QR-Code aus einem Haus','🛠'],
 'rent'=>['Mieten','Ab dem 6.: Mieten fehlen oder sind unvollständig','🏠'],
 'mail'=>['E-Mail','Neue Mails im Posteingang','✉️'],
 'offers'=>['Angebote & Verträge','Angebot nachfassen, Kündigungsfrist naht, Schlüssel zu lange unterwegs','📨'],
 'system'=>['Systemüberwachung','Mail-Passwort abgelehnt oder Hintergrundaufgabe gestoppt','⚠️'],
 'test'=>['Test','Testnachricht','✅']];

function gszPushReady(PDO $pdo):bool {try{return (bool)$pdo->query("SELECT COUNT(*) FROM app_migrations WHERE version='440_push'")->fetchColumn();}catch(Throwable $e){return false;}}
function gszB64u(string $b):string {return rtrim(strtr(base64_encode($b),'+/','-_'),'=');}
function gszB64uDec(string $s):string {$r=base64_decode(strtr($s,'-_','+/').str_repeat('=',(4-strlen($s)%4)%4),true);if($r===false)throw new RuntimeException('Ungültige Kodierung.');return $r;}

/* ---------- VAPID-Schlüssel ---------- */
function gszPushKeyFile():string {return __DIR__.'/secrets/push_vapid.pem';}
/** Lädt (oder erzeugt einmalig) den privaten VAPID-Schlüssel. */
function gszPushVapidKey():OpenSSLAsymmetricKey {
 $f=gszPushKeyFile();
 if(!is_file($f)){
  $dir=dirname($f);if(!is_dir($dir)&&!@mkdir($dir,0700,true)&&!is_dir($dir))throw new RuntimeException('Schlüsselordner fehlt.');
  $k=openssl_pkey_new(['curve_name'=>'prime256v1','private_key_type'=>OPENSSL_KEYTYPE_EC]);if(!$k||!openssl_pkey_export($k,$pem))throw new RuntimeException('Schlüssel konnte nicht erzeugt werden.');
  $tmp=$f.'.'.bin2hex(random_bytes(4));if(file_put_contents($tmp,$pem,LOCK_EX)===false)throw new RuntimeException('Schlüssel nicht speicherbar.');@chmod($tmp,0600);
  if(!@rename($tmp,$f)){@unlink($tmp);}
 }
 if(is_link($f))throw new RuntimeException('Schlüsseldatei ungültig.');$k=openssl_pkey_get_private((string)file_get_contents($f));if(!$k)throw new RuntimeException('Schlüssel unlesbar.');return $k;
}
/** Öffentlicher Schlüssel als 65 Byte (unkomprimierter Punkt). */
function gszEcRawPublic(OpenSSLAsymmetricKey $k):string {$d=openssl_pkey_get_details($k);if(($d['ec']['curve_name']??'')!=='prime256v1')throw new RuntimeException('Falsche Kurve.');return "\x04".str_pad($d['ec']['x'],32,"\0",STR_PAD_LEFT).str_pad($d['ec']['y'],32,"\0",STR_PAD_LEFT);}
function gszPushPublicKey():string {return gszB64u(gszEcRawPublic(gszPushVapidKey()));}
/** 65-Byte-Punkt → OpenSSL-Schlüssel (SubjectPublicKeyInfo für P-256). */
function gszEcPublicFromRaw(string $raw):OpenSSLAsymmetricKey {
 if(strlen($raw)!==65||$raw[0]!=="\x04")throw new RuntimeException('Ungültiger Geräte-Schlüssel.');
 $der=hex2bin('3059301306072a8648ce3d020106082a8648ce3d030107034200').$raw;
 $k=openssl_pkey_get_public("-----BEGIN PUBLIC KEY-----\n".chunk_split(base64_encode($der),64,"\n")."-----END PUBLIC KEY-----\n");if(!$k)throw new RuntimeException('Ungültiger Geräte-Schlüssel.');return $k;
}
/** DER-ECDSA-Signatur → 64 Byte r||s (JWS ES256). */
function gszDerToJose(string $der):string {
 $o=0;if(ord($der[$o++])!==0x30)throw new RuntimeException('Signatur ungültig.');$len=ord($der[$o++]);if($len&0x80)$o+=$len&0x7f;
 $parts=[];for($i=0;$i<2;$i++){if(ord($der[$o++])!==0x02)throw new RuntimeException('Signatur ungültig.');$l=ord($der[$o++]);$v=substr($der,$o,$l);$o+=$l;$v=ltrim($v,"\0");$parts[]=str_pad($v,32,"\0",STR_PAD_LEFT);}
 return $parts[0].$parts[1];
}
/** VAPID-JWT für den Push-Dienst (aud = Herkunft des Endpunkts). */
function gszPushJwt(string $endpoint,string $subject,?int $now=null):string {
 $u=parse_url($endpoint);if(($u['scheme']??'')!=='https'||empty($u['host']))throw new RuntimeException('Push-Adresse ungültig.');
 $h=gszB64u(json_encode(['typ'=>'JWT','alg'=>'ES256']));$c=gszB64u(json_encode(['aud'=>'https://'.$u['host'].(isset($u['port'])?':'.$u['port']:''),'exp'=>($now??time())+12*3600,'sub'=>$subject],JSON_UNESCAPED_SLASHES));
 if(!openssl_sign($h.'.'.$c,$sig,gszPushVapidKey(),OPENSSL_ALGO_SHA256))throw new RuntimeException('Signatur fehlgeschlagen.');return $h.'.'.$c.'.'.gszB64u(gszDerToJose($sig));
}

/* ---------- Verschlüsselung (RFC 8291 / RFC 8188 aes128gcm) ---------- */
/** @return string vollständiger Request-Body (Header + Chiffrat). Mit $salt/$ephemeral nur für Tests deterministisch. */
function gszPushEncrypt(string $payload,string $uaPublicB64,string $authB64,?string $salt=null,?OpenSSLAsymmetricKey $ephemeral=null):string {
 $uaPub=gszB64uDec($uaPublicB64);$auth=gszB64uDec($authB64);if(strlen($auth)!==16)throw new RuntimeException('Ungültiger Geräte-Schlüssel.');
 if(strlen($payload)>3000)throw new RuntimeException('Nachricht zu lang.');
 $eph=$ephemeral??openssl_pkey_new(['curve_name'=>'prime256v1','private_key_type'=>OPENSSL_KEYTYPE_EC]);$asPub=gszEcRawPublic($eph);
 $shared=openssl_pkey_derive(gszEcPublicFromRaw($uaPub),$eph,32);if($shared===false||strlen($shared)!==32)throw new RuntimeException('Schlüsseltausch fehlgeschlagen.');
 $ikm=hash_hkdf('sha256',$shared,32,"WebPush: info\0".$uaPub.$asPub,$auth);
 $salt??=random_bytes(16);$cek=hash_hkdf('sha256',$ikm,16,"Content-Encoding: aes128gcm\0",$salt);$nonce=hash_hkdf('sha256',$ikm,12,"Content-Encoding: nonce\0",$salt);
 $ct=openssl_encrypt($payload."\x02",'aes-128-gcm',$cek,OPENSSL_RAW_DATA,$nonce,$tag,'',16);if($ct===false)throw new RuntimeException('Verschlüsselung fehlgeschlagen.');
 return $salt.pack('N',4096).chr(65).$asPub.$ct.$tag;
}

/* ---------- Geräte ---------- */
function gszPushSubscribe(PDO $pdo,int $admin,array $sub,string $label=''):int {
 $ep=(string)($sub['endpoint']??'');$p=(string)($sub['keys']['p256dh']??'');$a=(string)($sub['keys']['auth']??'');
 if(!preg_match('~^https://[^\s]{10,990}$~D',$ep)||strlen($ep)>1000)throw new RuntimeException('Push-Adresse ungültig.');
 gszEcPublicFromRaw(gszB64uDec($p));if(strlen(gszB64uDec($a))!==16)throw new RuntimeException('Ungültiger Geräte-Schlüssel.');
 $label=mb_substr(trim(preg_replace('/[\x00-\x1F\x7F]/u','',$label)??''),0,120);
 $pdo->prepare('INSERT INTO push_subscriptions(admin_id,endpoint_hash,endpoint,p256dh,auth,label) VALUES(?,?,?,?,?,?) ON DUPLICATE KEY UPDATE admin_id=VALUES(admin_id),p256dh=VALUES(p256dh),auth=VALUES(auth),label=VALUES(label),fail_count=0,last_error=\'\'')->execute([$admin,hash('sha256',$ep),$ep,$p,$a,$label]);
 $q=$pdo->prepare('SELECT id FROM push_subscriptions WHERE endpoint_hash=?');$q->execute([hash('sha256',$ep)]);$id=(int)$q->fetchColumn();gszAudit($pdo,$admin,'push_subscribed',['id'=>$id]);return $id;
}
function gszPushDevices(PDO $pdo):array {return $pdo->query('SELECT id,label,created_at,last_ok_at,fail_count,last_error FROM push_subscriptions ORDER BY created_at DESC LIMIT 50')->fetchAll(PDO::FETCH_ASSOC);}
function gszPushRemove(PDO $pdo,int $admin,int $id):void {$pdo->prepare('DELETE FROM push_subscriptions WHERE id=?')->execute([$id]);gszAudit($pdo,$admin,'push_removed',['id'=>$id]);}
function gszPushRemoveEndpoint(PDO $pdo,string $endpoint):void {$pdo->prepare('DELETE FROM push_subscriptions WHERE endpoint_hash=?')->execute([hash('sha256',$endpoint)]);}

/* ---------- Senden ---------- */
function gszPushEnabled(PDO $pdo,string $kind):bool {return $kind==='test'||gszSetting($pdo,'push_'.$kind)==='1';}
/**
 * Benachrichtigt alle Geräte – je $key höchstens einmal (Doppelte werden still übersprungen).
 * $sender ist austauschbar (Tests); Standard: echter HTTPS-Aufruf. Rückgabe: Anzahl erfolgreich zugestellt.
 */
function gszPushNotify(PDO $pdo,string $kind,string $key,string $title,string $body,string $url='index.php',?callable $sender=null):int {
 if(!gszPushReady($pdo)||!isset(GSZ_PUSH_KINDS[$kind])||!gszPushEnabled($pdo,$kind))return 0;
 $title=mb_substr($title,0,120);$body=mb_substr($body,0,240);$url=preg_match('/^[a-z_]+\.php(\?[A-Za-z0-9=&_%.\-]*)?(#[a-z0-9-]+)?$/D',$url)?$url:'index.php';
 $ins=$pdo->prepare('INSERT IGNORE INTO push_events(event_key,kind,title,body,url) VALUES(?,?,?,?,?)');$ins->execute([mb_substr($key,0,120),$kind,$title,$body,$url]);if(!$ins->rowCount())return 0;
 $subs=$pdo->query('SELECT * FROM push_subscriptions WHERE fail_count<20')->fetchAll(PDO::FETCH_ASSOC);if(!$subs)return 0;
 $payload=json_encode(['title'=>$title,'body'=>$body,'url'=>$url,'tag'=>$kind.'-'.substr(hash('sha256',$key),0,12)],JSON_UNESCAPED_UNICODE|JSON_UNESCAPED_SLASHES);
 $sender??='gszPushHttp';$subject='mailto:'.(filter_var(gszSetting($pdo,'push_contact'),FILTER_VALIDATE_EMAIL)?gszSetting($pdo,'push_contact'):'info@gebaeudeservice-zentrum.de');$ok=0;
 foreach($subs as $s){
  try{$body2=gszPushEncrypt((string)$payload,(string)$s['p256dh'],(string)$s['auth']);$jwt=gszPushJwt((string)$s['endpoint'],$subject);
   $code=(int)$sender((string)$s['endpoint'],$body2,['Content-Type: application/octet-stream','Content-Encoding: aes128gcm','TTL: 86400','Urgency: '.($kind==='weather'?'high':'normal'),'Authorization: vapid t='.$jwt.', k='.gszPushPublicKey()]);}
  catch(Throwable $e){$code=0;}
  if($code>=200&&$code<300){$ok++;$pdo->prepare('UPDATE push_subscriptions SET last_ok_at=UTC_TIMESTAMP(),fail_count=0,last_error=\'\' WHERE id=?')->execute([$s['id']]);}
  elseif($code===404||$code===410)$pdo->prepare('DELETE FROM push_subscriptions WHERE id=?')->execute([$s['id']]);
  else $pdo->prepare('UPDATE push_subscriptions SET fail_count=fail_count+1,last_error=? WHERE id=?')->execute([$code?'HTTP '.$code:'nicht erreichbar',$s['id']]);
 }
 $pdo->prepare('UPDATE push_events SET delivered=? WHERE event_key=?')->execute([$ok,mb_substr($key,0,120)]);return $ok;
}
/** Echter Versand an den Push-Dienst (Apple, Google, Mozilla …). Rückgabe: HTTP-Status. */
function gszPushHttp(string $endpoint,string $body,array $headers):int {
 if(!function_exists('curl_init'))throw new RuntimeException('cURL fehlt.');
 $c=curl_init($endpoint);curl_setopt_array($c,[CURLOPT_POST=>true,CURLOPT_POSTFIELDS=>$body,CURLOPT_HTTPHEADER=>array_merge($headers,['Content-Length: '.strlen($body)]),CURLOPT_RETURNTRANSFER=>true,CURLOPT_TIMEOUT=>6,CURLOPT_CONNECTTIMEOUT=>4,CURLOPT_PROTOCOLS=>CURLPROTO_HTTPS,CURLOPT_FOLLOWLOCATION=>false]);
 curl_exec($c);$code=(int)curl_getinfo($c,CURLINFO_RESPONSE_CODE);curl_close($c);return $code;
}
function gszPushRecent(PDO $pdo,int $n=10):array {return $pdo->query('SELECT * FROM push_events ORDER BY created_at DESC LIMIT '.max(1,min(50,$n)))->fetchAll(PDO::FETCH_ASSOC);}
