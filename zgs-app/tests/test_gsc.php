<?php
declare(strict_types=1);
// Google Search Console: Schlüssel verschlüsselt ablegen, JWT-Anmeldung, Websites, Suchanalyse, Trend, Fehlermeldungen.
// Läuft in einer temporären Kopie von private/ (nie im echten Vault). Google wird vollständig nachgestellt:
// eigens erzeugter RSA-Testschlüssel, synthetisches Dienstkonto, keine Netzwerkverbindung.
$root=sys_get_temp_dir().'/gsz-gsc-'.bin2hex(random_bytes(8));mkdir($root.'/private',0700,true);foreach(glob(dirname(__DIR__).'/private/*.php') as $f)copy($f,$root.'/private/'.basename($f));
$code=<<<'CODE'
<?php
declare(strict_types=1);set_error_handler(static function($s,$m,$f,$l){if(error_reporting()&$s)throw new ErrorException($m,0,$s,$f,$l);return false;});
require __DIR__.'/private/gsc.php';
$n=0;function gc(bool $v,string $s):void{global $n;$n++;if(!$v)throw new RuntimeException($s);}function gx(callable $f,string $needle,string $s):void{$m='';try{$f();}catch(Throwable $e){$m=$e->getMessage();}gc($needle!==''&&str_contains($m,$needle),$s.' ['.$m.']');}
$pk=openssl_pkey_new(['private_key_bits'=>2048,'private_key_type'=>OPENSSL_KEYTYPE_RSA]);openssl_pkey_export($pk,$pem);$pub=openssl_pkey_get_details($pk)['key'];
$email='zentrale@synthetic-project.iam.gserviceaccount.com';$json=json_encode(['type'=>'service_account','project_id'=>'synthetic-project','private_key_id'=>'synthetic-kid','private_key'=>$pem,'client_email'=>$email,'token_uri'=>'https://oauth2.googleapis.com/token']);
gx(fn()=>gszGscParseKey('{"type":"authorized_user"}'),'keine Dienstkonto','other Google JSON refused');gx(fn()=>gszGscParseKey(str_replace('oauth2.googleapis.com','evil.example',$json)),'Anmeldeadresse','foreign token URL refused');
// Google nachgestellt
$mode='ok';$calls=[];$b64d=fn($s)=>base64_decode(strtr($s,'-_','+/'));
$day=fn(int $back)=>(new DateTimeImmutable('today',new DateTimeZone('Europe/Berlin')))->modify("-$back days")->format('Y-m-d');
$http=function(string $method,string $url,array $headers,string $body='')use(&$mode,&$calls,$pub,$b64d,$email,$day){$calls[]=$method.' '.$url;
 if($url==='https://oauth2.googleapis.com/token'){parse_str($body,$f);[$h,$c,$s]=explode('.',$f['assertion']);$claims=json_decode($b64d($c),true);$ok=openssl_verify($h.'.'.$c,$b64d($s),$pub,OPENSSL_ALGO_SHA256)===1&&$claims['iss']===$email&&$claims['scope']==='https://www.googleapis.com/auth/webmasters.readonly'&&$claims['aud']==='https://oauth2.googleapis.com/token'&&$f['grant_type']==='urn:ietf:params:oauth:grant-type:jwt-bearer';
  if($mode==='badkey'||!$ok)return [400,'{"error":"invalid_grant"}'];return [200,'{"access_token":"synthetic-token","expires_in":3600}'];}
 if(!in_array('Authorization: Bearer synthetic-token',$headers,true))return [401,'{}'];if($mode==='noaccess')return [403,'{"error":{"code":403}}'];
 if($url==='https://www.googleapis.com/webmasters/v3/sites')return [200,json_encode(['siteEntry'=>[['siteUrl'=>'sc-domain:example.invalid','permissionLevel'=>'siteRestrictedUser']]])];
 if($url==='https://www.googleapis.com/webmasters/v3/sites/'.rawurlencode('sc-domain:example.invalid').'/searchAnalytics/query'){$q=json_decode($body,true);$recent=$q['endDate']===$day(2);
  if($q['dimensions']===['date'])return [200,json_encode(['rows'=>[['keys'=>[$day(3)],'clicks'=>2,'impressions'=>40],['keys'=>[$day(2)],'clicks'=>3,'impressions'=>50]]])];
  return [200,json_encode(['rows'=>$recent?[['keys'=>['gebäudereinigung gütersloh'],'clicks'=>12,'impressions'=>300,'position'=>4.2],['keys'=>['hausmeisterservice rheda'],'clicks'=>3,'impressions'=>90,'position'=>9.8],['keys'=>['treppenhausreinigung'],'clicks'=>0,'impressions'=>20,'position'=>15.0]]:[['keys'=>['gebäudereinigung gütersloh'],'clicks'=>8,'impressions'=>250,'position'=>6.0],['keys'=>['hausmeisterservice rheda'],'clicks'=>4,'impressions'=>80,'position'=>8.1],['keys'=>['winterdienst gütersloh'],'clicks'=>1,'impressions'=>30,'position'=>11.0]]])];}
 return [404,'{}'];};
// Speichern: verschlüsselt, getrennter Schlüssel, enge Rechte
gszGscSave($json);$p=gszGscPaths();clearstatcache();gc(is_file($p['vault'])&&is_file($p['key'])&&(fileperms($p['dir'])&0777)===0700&&(fileperms($p['vault'])&0777)===0600&&(fileperms($p['key'])&0777)===0600,'vault 0600 in 0700 folder');
$raw=file_get_contents($p['vault']);gc(!str_contains($raw,'PRIVATE KEY')&&!str_contains($raw,$email),'no clear text in the vault file');$c=gszGscLoad();gc($c['client_email']===$email&&str_contains($c['private_key'],'PRIVATE KEY'),'access loads again');
gc(gszGscSites($c,$http)===['sc-domain:example.invalid'],'websites listed via signed JWT login');gx(fn()=>gszGscFetch($c,$http),'Website auswählen','no fetch without chosen website');
gszGscSetSite('sc-domain:example.invalid');$c=gszGscLoad();$d=gszGscFetch($c,$http);
gc($d['totals']['clicks']===15&&$d['totals']['clicks_prev']===13&&$d['totals']['impressions']===410,'totals for both periods');
$q=array_column($d['queries'],null,'query');gc($d['queries'][0]['query']==='gebäudereinigung gütersloh'&&$q['gebäudereinigung gütersloh']['position']===4.2&&$q['gebäudereinigung gütersloh']['previous']===6.0,'top query with position and previous position');
gc($q['treppenhausreinigung']['new']===true&&$d['lost'][0]['query']==='winterdienst gütersloh','new and lost queries recognised');gc($d['from']===$day(29)&&$d['to']===$day(2)&&count($d['days'])===2,'28 days ending two days ago');
// Synchronisieren schreibt nur Ergebnisse, nie Zugangsdaten
$r=gszGscSync($http);$data=file_get_contents($p['data']);gc($r['queries']===3&&!str_contains($data,'PRIVATE KEY')&&!str_contains($data,$email)&&(fileperms($p['data'])&0777)===0600,'stored summary contains no credentials');gc(gszGscData()['site']==='sc-domain:example.invalid','summary readable for the page');
// Fehler verständlich
$mode='noaccess';gx(fn()=>gszGscSites($c,$http),'Nutzer eingetragen','missing Search Console permission explained');$mode='badkey';gx(fn()=>gszGscSites($c,$http),'Schlüssel abgelehnt','rejected key explained');$mode='ok';
gx(fn()=>gszGscHttp('GET','https://evil.example/x',[]),'Nicht erlaubte Adresse','only Google hosts allowed');gx(fn()=>gszGscHttp('GET','http://www.googleapis.com/x',[]),'Nicht erlaubte Adresse','plain http refused');
gx(fn()=>gszGscSetSite('javascript:alert(1)'),'ungültig','website value validated');
gszGscDelete();clearstatcache();gc(!gszGscExists()&&!is_file($p['data']),'delete removes key, vault and data');
echo "Search Console PASS ($n assertions; synthetic service account and Google responses, no network).\n";
CODE;
file_put_contents($root.'/run.php',$code);passthru(escapeshellarg(PHP_BINARY).' '.escapeshellarg($root.'/run.php'),$rc);exec('rm -rf '.escapeshellarg($root));exit($rc);
