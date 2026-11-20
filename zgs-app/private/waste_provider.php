<?php
declare(strict_types=1);
require_once __DIR__.'/waste_sync.php';
/** Only municipal links verified in QUELLEN_ABFALL.md. Not a Germany-wide directory. */
function wasteProviderRegistry():array {return [
 'gutersloh'=>['city'=>'Gütersloh','provider'=>'regioit_krwaf','evidence'=>'https://www.guetersloh.de/de/rathaus/fachbereiche-und-einrichtungen/umweltschutz/abfallberatung/abfallnavi.php'],
 'rhedawiedenbruck'=>['city'=>'Rheda-Wiedenbrück','provider'=>'regioit_krwaf','evidence'=>'https://www.rheda-wiedenbrueck.de/umwelt-bauen-verkehr/umwelt-klima/umwelt-abfall/abfall-muellabfuhr/']
 ];}
function wasteAddressKey(string $s):string {$s=mb_strtolower(trim($s));$s=strtr($s,['ä'=>'a','ö'=>'o','ü'=>'u','ß'=>'ss']);return preg_replace('/[^a-z0-9]/u','',$s)??'';}
function wasteHouseKey(string $s):string {return str_replace(['–','—',' '],['-','-',''],mb_strtolower(trim($s)));}
function wasteStreetKey(string $s):string {$s=mb_strtolower($s);$s=preg_replace('/str(?:aße|asse|\.)/u','strasse',$s)??$s;return wasteAddressKey($s);}
function wasteAddress(array $in,string $legacy=''):array {
 $a=[];foreach(['street'=>140,'house_number'=>30,'postal_code'=>5,'city'=>100,'district'=>100] as $k=>$max){$v=trim((string)($in[$k]??''));if(mb_strlen($v)>$max||preg_match('/[\x00-\x1f\x7f]/u',$v))throw new InvalidArgumentException('Adressfeld ungültig.');$a[$k]=$v;}
 if(!implode('',$a)&&preg_match('/^(.+?)\s+(\d[\d\pL\s–\/-]*),?\s*,\s*(\d{5})\s+(.+)$/uD',trim($legacy),$m))$a=array_combine(array_keys($a),[trim($m[1]),trim($m[2]),$m[3],trim($m[4]),'']);
 if($a['postal_code']!==''&&!preg_match('/^\d{5}$/D',$a['postal_code']))throw new InvalidArgumentException('PLZ muss fünfstellig sein.');
 if($a['house_number']!==''&&!preg_match('/^\d+[\pL]?(?:\s*[-–\/]\s*\d+[\pL]?)?$/uD',$a['house_number']))throw new InvalidArgumentException('Hausnummer bitte eindeutig angeben.');return $a;
}
function wasteAddressComplete(array $a):bool {foreach(['street','house_number','postal_code','city'] as $k)if(empty($a[$k]))return false;return true;}
interface GszWasteProvider {public function discover(array $address,?string $choice=null):array;public function calendar(array $selection,array $bins):array;}
/** Bounded HTTPS public-client adapter; no borrowed vendor code, authentication or scraping. */
final class GszRegioWasteProvider implements GszWasteProvider {
 public const HOST='krwaf-abfallapp.regioit.de';public const BASE='https://'.self::HOST.'/abfall-app-krwaf/rest';
 private int $requests=0;private ?float $started=null;
 public function __construct(private ?PDO $pdo=null,private mixed $fetch=null,private bool $refresh=false){}
 private function get(string $path):array {
  if(!preg_match('~^/(?:orte|fraktionen|strassen|hausnummern)(?:/[0-9]+)?(?:/(?:strassen|termine|fraktionen))?(?:\?[a-z0-9=&]+)?$~D',$path))throw new RuntimeException('Providerpfad ungültig.');$key=hash('sha256',self::BASE.$path);
  // Directory cache 24h, calendars 1h. Manual refresh only bypasses calendars.
  if($this->pdo){$fresh=$this->refresh&&str_contains($path,'/termine')?' AND expires_at>UTC_TIMESTAMP()+INTERVAL 3300 SECOND':'';$q=$this->pdo->prepare('SELECT payload FROM waste_provider_cache WHERE cache_key=? AND expires_at>UTC_TIMESTAMP()'.$fresh);$q->execute([$key]);if($raw=$q->fetchColumn())return json_decode($raw,true,32,JSON_THROW_ON_ERROR);}
  $this->started??=microtime(true);if(microtime(true)-$this->started>15)throw new RuntimeException('Provider-Zeitbudget erreicht.');if(++$this->requests>8)throw new RuntimeException('Anfragebudget erreicht.');$raw=($this->fetch??fn($url,$host)=>wasteFetchRemote($url,$host,'json'))(self::BASE.$path,self::HOST);if(strlen($raw)>1048576)throw new RuntimeException('Providerantwort zu groß.');$data=json_decode(wasteInput($raw),true,32,JSON_THROW_ON_ERROR);if(!is_array($data)||count($data)>5000)throw new RuntimeException('Providerformat geändert.');
  if($this->pdo){$ttl=str_contains($path,'/termine')?3600:86400;$this->pdo->prepare('INSERT INTO waste_provider_cache(cache_key,payload,expires_at) VALUES(?,?,DATE_ADD(UTC_TIMESTAMP(),INTERVAL ? SECOND)) ON DUPLICATE KEY UPDATE payload=VALUES(payload),expires_at=VALUES(expires_at)')->execute([$key,$raw,$ttl]);$this->pdo->exec('DELETE FROM waste_provider_cache WHERE expires_at<UTC_TIMESTAMP()-INTERVAL 2 DAY LIMIT 100');}return $data;
 }
 public static function fraction(string $label):?string {
  // Explicit, reviewed labels only. Container capacities/frequencies remain separate selectable variants.
  $s=mb_strtolower(trim($label));if(preg_match('/^restabfall(?: (?:2-wöchentlich|4-wöchentlich|1,1 cbm(?: 2-wöchentlich)?))?$/uD',$s))return 'Restmüll';
  if(in_array($s,['bioabfall','bioabfall 2-wöchentlich','bioabfall saisontonne','saison-biotonne','komposttonne'],true))return 'Bio';
  if(in_array($s,['papiertonne','papiertonne 4-wöchentlich'],true))return 'Papier';if(in_array($s,['gelbe tonne','gelbe tonne 1,1 cbm 2-wöchentlich','gelber sack'],true))return 'Gelb';return ['windeltonne'=>'Windeln','abfuhr baum- und strauchschnitt'=>'Grünschnitt','weihnachtsbaum'=>'Weihnachtsbäume'][$s]??null;
 }
 public function discover(array $a,?string $choice=null):array {
  if(!wasteAddressComplete($a))return ['status'=>'confirmation','message'=>'Vollständige Adresse erforderlich.','candidates'=>[]];$registry=wasteProviderRegistry()[wasteAddressKey($a['city'])]??null;if(!$registry)return ['status'=>'manual','message'=>'Kommune noch ohne geprüften Adapter. Geführten Import verwenden.','candidates'=>[]];
  $towns=array_values(array_filter($this->get('/orte'),fn($r)=>isset($r['id'],$r['name'])&&wasteAddressKey($r['name'])===wasteAddressKey($registry['city'])));if(count($towns)!==1)throw new RuntimeException('Kommunenzuordnung nicht eindeutig.');$town=$towns[0];$year=(int)(new DateTimeImmutable('now',new DateTimeZone('Europe/Berlin')))->format('Y');$streets=$this->get('/orte/'.(int)$town['id'].'/strassen?jahr='.$year);$candidates=[];$exact=[];
  foreach($streets as $s){if(!isset($s['id'],$s['name'],$s['staticId'])||(!empty($s['plz'])&&$s['plz']!==$a['postal_code']))continue;if($a['district']!==''&&!empty($s['ortsteilName'])&&$s['ortsteilName']!=='default'&&wasteAddressKey($s['ortsteilName'])!==wasteAddressKey($a['district']))continue;$expires=$s['gueltigBis']??null;if($expires!==null&&(!is_string($expires)||!zgsValidDate($expires)))throw new RuntimeException('Kalendergültigkeit unklar.');if($expires!==null&&$expires<date('Y-m-d'))continue;$match=wasteStreetKey($s['name'])===wasteStreetKey($a['street']);if(!$match&&!str_starts_with(wasteStreetKey($s['name']),wasteStreetKey($a['street'])))continue;
   $item=['key'=>'s'.(int)$s['id'],'street'=>$s,'town'=>$town,'evidence'=>$registry['evidence'],'level'=>'street'];$candidates[]=$item;if($match)$exact[]=$item;
  }
  if(count($candidates)>30)throw new RuntimeException('Zu viele Straßenkandidaten; Adresse präzisieren.');$selected=null;if($choice!==null){foreach($candidates as $c)if($c['key']===$choice)$selected=$c;}elseif(count($exact)===1)$selected=$exact[0];
  if(!$selected)return ['status'=>'confirmation','message'=>$candidates?'Straße/Bezirk ausdrücklich auswählen.':'Straße/PLZ nicht eindeutig gefunden. Adresse prüfen oder manuell importieren.','candidates'=>$candidates];
  $detail=$this->get('/strassen/'.(int)$selected['street']['id']);if(!isset($detail['id'])||(int)$detail['id']!==(int)$selected['street']['id'])throw new RuntimeException('Straßenformat geändert.');$houses=$detail['hausNrList']??null;if(!is_array($houses))throw new RuntimeException('Hausnummernzuordnung nicht verfügbar.');
  if($houses){$matches=array_values(array_filter($houses,fn($h)=>isset($h['hausNr'],$h['id'])&&wasteHouseKey((string)$h['hausNr'])===wasteHouseKey($a['house_number'])));if(count($matches)!==1)return ['status'=>'confirmation','message'=>'Hausnummer/Abfuhrbezirk nicht eindeutig. Keine Termine übernommen; geführte Quellenzuordnung erforderlich.','candidates'=>$candidates];$selected['house']=$matches[0];$selected['level']='house';}
  $fractions=isset($selected['house'])?$this->get('/hausnummern/'.(int)$selected['house']['id'].'/fraktionen'):$this->get('/fraktionen');if(count($fractions)>100)throw new RuntimeException('Zu viele Behältervarianten.');$selected['postcode_verified']=!empty($selected['street']['plz']);$selected['fraction_catalog']=$fractions;$rows=$this->calendar($selected,$fractions);$used=array_column($rows,'provider_fraction');$offered=[];$unmapped=[];
  foreach($fractions as $f)if(isset($f['id'],$f['name'])&&in_array((int)$f['id'],$used,true)){$canonical=self::fraction($f['name']);$v=['id'=>(int)$f['id'],'name'=>$f['name'],'canonical'=>$canonical];if($canonical)$offered[]=$v;else$unmapped[]=$f['name'];}
  if(!$offered)throw new RuntimeException('Keine künftigen zuordenbaren Tonnenleerungen vorhanden.');return ['status'=>'confirmation','message'=>'Adresse zugeordnet. Betreute Behältervarianten und Leistungsumfang bestätigen.','selection'=>$selected,'offered'=>$offered,'unmapped'=>$unmapped,'candidates'=>$candidates];
 }
 public function calendar(array $selection,array $bins):array {
  if(!$bins||count($bins)>100)throw new RuntimeException('Tonnenauswahl fehlt.');$query=[];$labels=[];foreach($bins as $b){if(!isset($b['id'],$b['name'])||!is_int($b['id'])||$b['id']<0)throw new RuntimeException('Fraktionskennung ungültig.');$query[]='fraktion='.$b['id'];$labels[$b['id']]=$b['name'];}
  $entity=isset($selection['house'])?'hausnummern':'strassen';$id=(int)($selection['house']['id']??$selection['street']['id']??0);if(!$id)throw new RuntimeException('Adresszuordnung fehlt.');$events=$this->get('/'.$entity.'/'.$id.'/termine?'.implode('&',$query));$rows=[];$today=(new DateTimeImmutable('today',new DateTimeZone('Europe/Berlin')))->format('Y-m-d');$limit=planDate($today,550);
  foreach($events as $e){$fid=$e['bezirk']['fraktionId']??null;$date=$e['datum']??'';if(!is_int($fid)||!isset($labels[$fid])||!isset($e['id'])||!is_int($e['id'])||!zgsValidDate($date))throw new RuntimeException('Termin-/Fraktionsformat geändert.');if($date<$today||$date>$limit||(!empty($selection['street']['gueltigBis'])&&$date>$selection['street']['gueltigBis']))continue;$rows[]=['date'=>$date,'label'=>$labels[$fid],'uid'=>'regioit:'.$e['id'].':'.$fid,'address'=>'','original_start'=>$date,'calendar'=>'Abfallnavi KRWAF','provider_fraction'=>$fid,'district'=>(string)($e['bezirk']['name']??'')];if(count($rows)>500)throw new RuntimeException('Mehr als 500 Termine; Auswahl eingrenzen.');}
  usort($rows,fn($a,$b)=>[$a['date'],$a['uid']]<=>[$b['date'],$b['uid']]);return $rows;
 }
}
