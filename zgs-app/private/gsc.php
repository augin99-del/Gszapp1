<?php
declare(strict_types=1);
/**
 * Google Search Console (nur lesen) über ein Google-Dienstkonto.
 * - Der JSON-Schlüssel des Dienstkontos wird einmal in der Oberfläche hochgeladen und liegt verschlüsselt im
 *   Secret-Vault-Format unter private/secrets/ (gsc.vault + gsc.key, Ordner 0700, Dateien 0600) – nie im Klartext.
 * - Abruf: JWT (RS256) → Zugriffstoken (Bereich webmasters.readonly) → Suchanalyse der letzten 28 Tage und der 28 Tage davor.
 * - Ergebnis als kleine Zusammenfassung in private/secrets/gsc-data.json (keine Datenbankänderung nötig).
 * - Netzwerk nur zu fest hinterlegten Google-Adressen, TLS-Prüfung immer an.
 */
require_once __DIR__.'/secret_vault.php';require_once __DIR__.'/managed_config.php';
const GSC_SCOPE='https://www.googleapis.com/auth/webmasters.readonly';
const GSC_TOKEN_URL='https://oauth2.googleapis.com/token';
const GSC_API='https://www.googleapis.com/webmasters/v3';
function gszGscPaths():array {$d=__DIR__.'/secrets';return ['dir'=>$d,'vault'=>$d.'/gsc.vault','key'=>$d.'/gsc.key','data'=>$d.'/gsc-data.json'];}
function gszGscExists():bool {$p=gszGscPaths();return is_file($p['vault'])&&is_file($p['key']);}
/** Prüft eine Dienstkonto-Schlüsseldatei und liefert die benötigten Felder (ohne sie auszugeben). */
function gszGscParseKey(string $json):array {
 if(strlen($json)>20000)throw new InvalidArgumentException('Die Datei ist zu groß für einen Dienstkonto-Schlüssel.');
 $k=json_decode($json,true);if(!is_array($k)||($k['type']??'')!=='service_account')throw new InvalidArgumentException('Das ist keine Dienstkonto-Schlüsseldatei (JSON) von Google.');
 $email=(string)($k['client_email']??'');$pem=(string)($k['private_key']??'');$uri=(string)($k['token_uri']??GSC_TOKEN_URL);
 if(!preg_match('/^[a-z0-9._-]+@[a-z0-9.-]+\.iam\.gserviceaccount\.com$/D',$email))throw new InvalidArgumentException('In der Datei fehlt die E-Mail-Adresse des Dienstkontos.');
 if($uri!==GSC_TOKEN_URL)throw new InvalidArgumentException('Unerwartete Anmeldeadresse in der Schlüsseldatei.');
 if(!str_contains($pem,'PRIVATE KEY')||!openssl_pkey_get_private($pem))throw new InvalidArgumentException('Der private Schlüssel in der Datei ist nicht lesbar.');
 return ['client_email'=>$email,'private_key'=>$pem,'private_key_id'=>(string)($k['private_key_id']??'')];
}
function gszGscSave(string $json,string $site=''):array {
 $k=gszGscParseKey($json);if($site!==''&&!gszGscValidSite($site))throw new InvalidArgumentException('Website-Adresse ungültig.');
 gszVaultCrypto();$p=gszGscPaths();gszPrivateDirectory($p['dir']);
 if(!is_file($p['key'])){$key=random_bytes(32);try{gszPrivateWrite($p['key'],$key);}finally{sodium_memzero($key);}}
 $key=gszVaultRead($p['key'],32);try{gszPrivateWrite($p['vault'],gszVaultSeal(gszGscPack(['client_email'=>$k['client_email'],'private_key'=>$k['private_key'],'private_key_id'=>$k['private_key_id'],'site'=>$site]),$key));}finally{sodium_memzero($key);}
 clearstatcache();foreach([$p['dir']=>0700,$p['key']=>0600,$p['vault']=>0600] as $f=>$mode)@chmod($f,$mode);
 return ['client_email'=>$k['client_email']];
}
function gszGscPack(array $c):array {return array_filter(['gsc_email'=>(string)$c['client_email'],'gsc_key'=>(string)$c['private_key'],'gsc_key_id'=>(string)($c['private_key_id']??''),'gsc_site'=>(string)($c['site']??'')],fn($v)=>$v!=='');}
function gszGscLoad():?array {if(!gszGscExists())return null;$p=gszGscPaths();$v=gszVaultLoad($p['vault'],$p['key']);if(!isset($v['gsc_key'],$v['gsc_email']))return null;return ['client_email'=>$v['gsc_email'],'private_key'=>$v['gsc_key'],'private_key_id'=>$v['gsc_key_id']??'','site'=>$v['gsc_site']??''];}
function gszGscSetSite(string $site):void {$c=gszGscLoad();if(!$c)throw new RuntimeException('Erst den Schlüssel hochladen.');if(!gszGscValidSite($site))throw new InvalidArgumentException('Website-Adresse ungültig.');$p=gszGscPaths();$key=gszVaultRead($p['key'],32);try{$c['site']=$site;gszPrivateWrite($p['vault'],gszVaultSeal(gszGscPack($c),$key));}finally{sodium_memzero($key);}}
function gszGscDelete():void {$p=gszGscPaths();foreach([$p['vault'],$p['key'],$p['data']] as $f)if(is_file($f))@unlink($f);}
function gszGscValidSite(string $site):bool {return (bool)preg_match('~^(sc-domain:[a-z0-9.-]+|https?://[a-z0-9.-]+(:[0-9]+)?/[^\s]*)$~iD',$site)&&strlen($site)<=200;}

/** HTTP-Aufruf nur zu Google, TLS geprüft. $http kann in Tests ersetzt werden: fn(method,url,headers,body)=>[status,body]. */
function gszGscHttp(string $method,string $url,array $headers,string $body=''):array {
 $host=(string)parse_url($url,PHP_URL_HOST);if(parse_url($url,PHP_URL_SCHEME)!=='https'||!in_array($host,['oauth2.googleapis.com','www.googleapis.com'],true))throw new RuntimeException('Nicht erlaubte Adresse.');
 if(function_exists('curl_init')){$c=curl_init($url);curl_setopt_array($c,[CURLOPT_CUSTOMREQUEST=>$method,CURLOPT_RETURNTRANSFER=>true,CURLOPT_HTTPHEADER=>$headers,CURLOPT_TIMEOUT=>20,CURLOPT_CONNECTTIMEOUT=>8,CURLOPT_SSL_VERIFYPEER=>true,CURLOPT_SSL_VERIFYHOST=>2,CURLOPT_FOLLOWLOCATION=>false,CURLOPT_PROTOCOLS=>CURLPROTO_HTTPS]);if($body!=='')curl_setopt($c,CURLOPT_POSTFIELDS,$body);$res=curl_exec($c);$status=(int)curl_getinfo($c,CURLINFO_RESPONSE_CODE);$err=curl_error($c);curl_close($c);if($res===false)throw new RuntimeException('Google nicht erreichbar'.($err!==''?' ('.mb_substr($err,0,80).')':'').'.');return [$status,(string)$res];}
 $ctx=stream_context_create(['http'=>['method'=>$method,'header'=>implode("\r\n",$headers),'content'=>$body,'timeout'=>20,'ignore_errors'=>true,'follow_location'=>0],'ssl'=>['verify_peer'=>true,'verify_peer_name'=>true]]);$res=@file_get_contents($url,false,$ctx);if($res===false)throw new RuntimeException('Google nicht erreichbar.');$status=0;foreach($http_response_header??[] as $h)if(preg_match('~^HTTP/\S+\s+(\d{3})~',$h,$m))$status=(int)$m[1];return [$status,$res];
}
function gszGscB64(string $s):string {return rtrim(strtr(base64_encode($s),'+/','-_'),'=');}
function gszGscToken(array $cred,?callable $http=null):string {
 $http??='gszGscHttp';$now=time();$head=['alg'=>'RS256','typ'=>'JWT'];if(($cred['private_key_id']??'')!=='')$head['kid']=$cred['private_key_id'];
 $claims=['iss'=>$cred['client_email'],'scope'=>GSC_SCOPE,'aud'=>GSC_TOKEN_URL,'iat'=>$now,'exp'=>$now+3600];
 $unsigned=gszGscB64(json_encode($head)).'.'.gszGscB64(json_encode($claims));
 $pk=openssl_pkey_get_private((string)$cred['private_key']);if(!$pk||!openssl_sign($unsigned,$sig,$pk,OPENSSL_ALGO_SHA256))throw new RuntimeException('Schlüssel konnte nicht verwendet werden.');
 [$status,$body]=$http('POST',GSC_TOKEN_URL,['Content-Type: application/x-www-form-urlencoded'],http_build_query(['grant_type'=>'urn:ietf:params:oauth:grant-type:jwt-bearer','assertion'=>$unsigned.'.'.gszGscB64($sig)]));
 $j=json_decode($body,true);if($status!==200||!is_string($j['access_token']??null))throw new RuntimeException($status===400||$status===401?'Google hat den Schlüssel abgelehnt. Ist das Dienstkonto noch vorhanden und die Search-Console-API aktiviert?':'Anmeldung bei Google fehlgeschlagen (Status '.$status.').');
 return $j['access_token'];
}
function gszGscApi(string $token,string $method,string $path,?array $payload=null,?callable $http=null):array {
 $http??='gszGscHttp';[$status,$body]=$http($method,GSC_API.$path,['Authorization: Bearer '.$token,'Content-Type: application/json','Accept: application/json'],$payload===null?'':json_encode($payload));
 $j=json_decode($body,true);
 if($status===403)throw new RuntimeException('Kein Zugriff: Das Dienstkonto ist in der Search Console nicht als Nutzer eingetragen (Einstellungen → Nutzer und Berechtigungen).');
 if($status===429)throw new RuntimeException('Google meldet zu viele Abfragen. Später erneut versuchen.');
 if($status!==200||!is_array($j))throw new RuntimeException('Google-Antwort nicht verwertbar (Status '.$status.').');return $j;
}
/** Websites, auf die das Dienstkonto Zugriff hat. */
function gszGscSites(array $cred,?callable $http=null):array {
 $token=gszGscToken($cred,$http);$j=gszGscApi($token,'GET','/sites',null,$http);$out=[];foreach(($j['siteEntry']??[]) as $s)if(is_string($s['siteUrl']??null)&&gszGscValidSite($s['siteUrl']))$out[]=$s['siteUrl'];sort($out);return $out;
}
/** Holt 28 Tage (bis vor 2 Tagen, Google liefert mit Verzug) und die 28 Tage davor, je Suchbegriff. */
function gszGscFetch(array $cred,?callable $http=null,?DateTimeImmutable $today=null):array {
 $site=(string)($cred['site']??'');if($site==='')throw new RuntimeException('Bitte zuerst die Website auswählen.');
 $today??=new DateTimeImmutable('today',new DateTimeZone('Europe/Berlin'));$end=$today->modify('-2 days');$start=$end->modify('-27 days');$pEnd=$start->modify('-1 day');$pStart=$pEnd->modify('-27 days');
 $token=gszGscToken($cred,$http);$path='/sites/'.rawurlencode($site).'/searchAnalytics/query';
 $q=fn(DateTimeImmutable $a,DateTimeImmutable $b,array $dims,int $limit)=>gszGscApi($token,'POST',$path,['startDate'=>$a->format('Y-m-d'),'endDate'=>$b->format('Y-m-d'),'dimensions'=>$dims,'rowLimit'=>$limit,'dataState'=>'final'],$http)['rows']??[];
 $now=$q($start,$end,['query'],1000);$prev=$q($pStart,$pEnd,['query'],1000);$days=$q($start,$end,['date'],100);
 $map=fn(array $rows)=>array_column(array_map(fn($r)=>['k'=>(string)($r['keys'][0]??''),'clicks'=>(int)($r['clicks']??0),'impressions'=>(int)($r['impressions']??0),'position'=>round((float)($r['position']??0),1)],$rows),null,'k');
 $cur=$map($now);$old=$map($prev);$queries=[];
 foreach($cur as $k=>$r){if($k==='')continue;$p=$old[$k]['position']??null;$queries[]=['query'=>mb_substr($k,0,200),'clicks'=>$r['clicks'],'impressions'=>$r['impressions'],'position'=>$r['position'],'previous'=>$p,'new'=>$p===null];}
 usort($queries,fn($a,$b)=>[$b['clicks'],$b['impressions']]<=>[$a['clicks'],$a['impressions']]);
 $lost=[];foreach($old as $k=>$r)if($k!==''&&!isset($cur[$k])&&$r['impressions']>=5)$lost[]=['query'=>mb_substr($k,0,200),'impressions'=>$r['impressions'],'position'=>$r['position']];usort($lost,fn($a,$b)=>$b['impressions']<=>$a['impressions']);
 $sum=fn(array $rows,string $f)=>array_sum(array_map(fn($r)=>$r[$f],$rows));
 return ['site'=>$site,'fetched_at'=>gmdate('c'),'from'=>$start->format('Y-m-d'),'to'=>$end->format('Y-m-d'),
  'totals'=>['clicks'=>$sum($cur,'clicks'),'impressions'=>$sum($cur,'impressions'),'clicks_prev'=>$sum($old,'clicks'),'impressions_prev'=>$sum($old,'impressions')],
  'queries'=>array_slice($queries,0,300),'lost'=>array_slice($lost,0,30),
  'days'=>array_map(fn($r)=>['date'=>(string)($r['keys'][0]??''),'clicks'=>(int)($r['clicks']??0),'impressions'=>(int)($r['impressions']??0)],$days)];
}
function gszGscSync(?callable $http=null):array {
 $c=gszGscLoad();if(!$c)throw new RuntimeException('Search Console nicht verbunden.');$d=gszGscFetch($c,$http);$p=gszGscPaths();gszPrivateWrite($p['data'],json_encode($d,JSON_UNESCAPED_UNICODE|JSON_THROW_ON_ERROR));@chmod($p['data'],0600);
 return ['queries'=>count($d['queries']),'clicks'=>$d['totals']['clicks']];
}
function gszGscData():?array {$p=gszGscPaths();if(!is_file($p['data']))return null;$d=json_decode((string)file_get_contents($p['data']),true);return is_array($d)?$d:null;}
