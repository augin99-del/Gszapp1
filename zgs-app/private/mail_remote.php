<?php
declare(strict_types=1);
require_once __DIR__.'/mail_mime.php';
interface GszMailRemote {
 public function folders():array;
 public function validity(string $folder):int;
 public function fetchAfter(string $folder,int $uid,int $limit):array;
 public function seen(string $folder,int $uid,bool $seen):void;
 public function move(string $folder,int $uid,string $target):void;
 public function append(string $folder,string $raw):void;
}
interface GszMailFlagRemote {public function flags(string $folder,array $uids):array;}
// Optional capability: exact Message-ID matches, including deleted matches, in one UID epoch.
// A failed/incomplete search MUST throw; an empty result means a completed search.
interface GszMailSearchRemote {public function findMessageId(string $folder,string $messageId):array;}
const GSZ_MAIL_MAX_BYTES=15*1024*1024;
/** Ersatz für eine Nachricht, die nicht vollständig übernommen werden kann (zu groß oder nicht lesbar):
 *  Absender, Empfänger, Betreff und Datum bleiben sichtbar, der Inhalt wird im Webmail geöffnet. Der Abruf läuft weiter. */
function gszMailPlaceholder(string $header,int $size=0):string {
 $header=substr($header,0,262144);$unfolded=preg_replace('/\r?\n[ \t]+/',' ',$header);$keep=[];
 foreach(preg_split('/\r?\n/',(string)$unfolded) as $line)if(preg_match('/^(From|To|Cc|Reply-To|Subject|Date|Message-ID):/i',$line))$keep[]=$line;
 $text=$size>GSZ_MAIL_MAX_BYTES?'Diese Nachricht ist '.number_format($size/1048576,1,',','.').' MB groß (Grenze 15 MB). Sie wurde ohne Inhalt und Anhänge übernommen. Bitte im STRATO-Webmail öffnen.':'Diese Nachricht konnte nicht gelesen werden. Sie wurde ohne Inhalt übernommen. Bitte im STRATO-Webmail öffnen.';
 return implode("\r\n",$keep)."\r\nMIME-Version: 1.0\r\nContent-Type: text/plain; charset=utf-8\r\nContent-Transfer-Encoding: 8bit\r\n\r\n".$text."\r\n";
}
final class GszImapRemote implements GszMailRemote,GszMailFlagRemote,GszMailSearchRemote {
 private $imap;private string $prefix;private bool $more=false;
 public function morePending():bool{return $this->more;}
 public function __construct(array $config){
  if(!filter_var($config['mail_enabled']??false,FILTER_VALIDATE_BOOLEAN))throw new RuntimeException('E-Mail nicht verbunden: serverseitig deaktiviert.');
  if(!function_exists('imap_open'))throw new RuntimeException('PHP-IMAP fehlt.');$host=(string)$config['mail_host'];if(!preg_match('/^[a-z0-9.-]+$/iD',$host)||(int)$config['mail_port']!==993)throw new RuntimeException('IMAP benötigt festen TLS-Host und Port 993.');
  imap_timeout(IMAP_OPENTIMEOUT,10);imap_timeout(IMAP_READTIMEOUT,15);imap_timeout(IMAP_WRITETIMEOUT,15);$this->prefix='{'.$host.':993/imap/ssl/validate-cert}';$this->imap=@imap_open($this->prefix.'INBOX',$config['mail_user'],$config['mail_pass'],0,1,['DISABLE_AUTHENTICATOR'=>'GSSAPI']);if(!$this->imap){$error=function_exists('imap_last_error')?(string)imap_last_error():'';if(function_exists('imap_errors'))imap_errors();if(function_exists('imap_alerts'))imap_alerts();$reason=preg_match('/auth|login|password/i',$error)?'IMAP-Anmeldung fehlgeschlagen. Postfach und sichere Zugangsdaten prüfen.':(preg_match('/ssl|tls|certificate/i',$error)?'IMAP-TLS/Zertifikat nicht bestätigt.':(preg_match('/timed? ?out|timeout/i',$error)?'IMAP-Zeitüberschreitung.':'IMAP-Server nicht erreichbar oder Verbindung abgelehnt.'));throw new RuntimeException($reason);}
 }
 public function __destruct(){if($this->imap)@imap_close($this->imap);if(function_exists('imap_errors'))imap_errors();if(function_exists('imap_alerts'))imap_alerts();}
 public function folders():array {$boxes=@imap_list($this->imap,$this->prefix,'*');if($boxes===false)throw new RuntimeException('IMAP-Ordner nicht lesbar.');$out=[];foreach($boxes as $b)if(str_starts_with($b,$this->prefix)){$name=substr($b,strlen($this->prefix));if($name!==''&&strlen($name)<=190&&!str_contains($name,"\0"))$out[]=$name;}return $out;}
 private function select(string $folder):void {if(!in_array($folder,$this->folders(),true))throw new RuntimeException('Ordner nicht vom Server bestätigt.');if(!@imap_reopen($this->imap,$this->prefix.$folder))throw new RuntimeException('Ordner nicht geöffnet.');}
 public function validity(string $folder):int {$this->select($folder);$s=@imap_status($this->imap,$this->prefix.$folder,SA_UIDVALIDITY);if(!$s||empty($s->uidvalidity))throw new RuntimeException('UIDVALIDITY fehlt.');return (int)$s->uidvalidity;}
 // PHP-IMAP (c-client) kennt kein Suchkriterium „UID n:*“; deshalb alle UIDs holen und lokal filtern.
 // false bedeutet bei imap_search sowohl „keine Treffer“ als auch „Fehler“ – nur ein leerer Fehlerspeicher ist ein leerer Ordner.
 private function allUids():array {if(function_exists('imap_errors'))imap_errors();$uids=@imap_search($this->imap,'ALL',SE_UID);if($uids===false){$error=function_exists('imap_errors')?(imap_errors()?:[]):[];if($error)throw new RuntimeException('Nachrichtenliste nicht lesbar.');return [];}return array_map('intval',$uids);}
 public function fetchAfter(string $folder,int $uid,int $limit):array {
  $this->more=false;$batchBytes=0;$this->select($folder);$uids=$this->allUids();sort($uids,SORT_NUMERIC);$this->more=count(array_filter($uids,fn($u)=>$u>$uid))>$limit;$out=[];
  foreach(array_slice(array_values(array_filter($uids,fn($u)=>$u>$uid)),0,min(100,$limit)) as $u){$o=@imap_fetch_overview($this->imap,(string)$u,FT_UID);if(!$o)continue; // inzwischen gelöscht: überspringen statt den ganzen Abruf zu blockieren
   $size=(int)($o[0]->size??0);$flags=['uid'=>(int)$u,'seen'=>(bool)($o[0]->seen??false),'deleted'=>(bool)($o[0]->deleted??false)];$header=@imap_fetchheader($this->imap,$u,FT_UID);if($header===false)throw new RuntimeException('Nachrichtenabruf fehlgeschlagen.');
   if($size>GSZ_MAIL_MAX_BYTES){$out[]=$flags+['raw'=>gszMailPlaceholder($header,$size)];continue;}
   if($out&&$batchBytes+$size>20*1024*1024){$this->more=true;break;}$batchBytes+=$size;$body=@imap_body($this->imap,$u,FT_UID|FT_PEEK);if($body===false)throw new RuntimeException('Nachrichtenabruf fehlgeschlagen.');$out[]=$flags+['raw'=>$header."\r\n".$body];}return $out;
 }
 public function flags(string $folder,array $uids):array {
  $this->select($folder);$uids=array_values(array_filter(array_map('intval',array_slice($uids,0,100)),fn($id)=>$id>0));if(!$uids)return [];$o=@imap_fetch_overview($this->imap,implode(',',$uids),FT_UID);if($o===false)throw new RuntimeException('Flag-Abruf fehlgeschlagen.');$result=[];foreach($uids as $uid)$result[$uid]=['seen'=>false,'missing'=>true];foreach($o as $row)$result[(int)$row->uid]=['seen'=>(bool)($row->seen??false),'missing'=>(bool)($row->deleted??false)];return $result;
 }
 public function findMessageId(string $folder,string $messageId):array {
  if(!preg_match('/^<[a-f0-9]{32}@gsz\.internal>$/D',$messageId))throw new InvalidArgumentException('Ungültige Nachrichtenidentität.');
  $this->select($folder);
  $mailbox = $this->prefix.$folder;
  $flags = SA_UIDVALIDITY | SA_MESSAGES | SA_UIDNEXT;
  $before = @imap_status($this->imap, $mailbox, $flags);
  if (!$before || !isset($before->messages) || empty($before->uidvalidity) || empty($before->uidnext)) {
   throw new RuntimeException('Stand des Gesendet-Ordners nicht bestätigt.');
  }
  $valid = (int)$before->uidvalidity;
  $count = (int)$before->messages;
  $candidates = [];
  if ($count > 0) {
   imap_errors();
   $matches = @imap_search($this->imap, 'HEADER Message-ID "'.$messageId.'"', SE_UID);
   if (imap_errors()) {
    if ($count > 100) throw new RuntimeException('Identitätssuche nicht bestätigt. Ordner im Webmail prüfen.');
    $matches = @imap_search($this->imap, 'ALL', SE_UID);
    if (imap_errors() || !is_array($matches) || count(array_unique($matches)) !== $count) {
     throw new RuntimeException('Identitätssuche nicht bestätigt.');
    }
   }
   $candidates = $matches ?: [];
  }
  if(count($candidates)>100)throw new RuntimeException('Identitätssuche überschreitet das Prüflimit.');$uids=[];$deleted=[];
  foreach($candidates as $uid){$header=@imap_fetchheader($this->imap,(int)$uid,FT_UID);$overview=@imap_fetch_overview($this->imap,(string)$uid,FT_UID);if($header===false||strlen($header)>262144||!$overview||count($overview)!==1||(int)($overview[0]->uid??0)!==(int)$uid)throw new RuntimeException('Identität nicht vollständig prüfbar.');$headers=gszMimeHeaders($header);$identity=$headers['message-id']??'';if($identity!==$messageId){if(str_contains($identity,$messageId))throw new RuntimeException('Mehrdeutiger Message-ID-Header.');continue;}if(!empty($overview[0]->deleted))$deleted[]=(int)$uid;else $uids[]=(int)$uid;}
  $after = @imap_status($this->imap, $mailbox, $flags);
  if (!$after || !isset($after->uidvalidity, $after->messages, $after->uidnext)
      || (int)$after->uidvalidity !== $valid || (int)$after->messages !== $count
      || (int)$after->uidnext !== (int)$before->uidnext) {
   throw new RuntimeException('Gesendet-Ordner während Identitätssuche geändert. Erneut prüfen.');
  }
  sort($uids,SORT_NUMERIC);sort($deleted,SORT_NUMERIC);return ['uidvalidity'=>$valid,'uids'=>$uids,'deleted_uids'=>$deleted];
 }
 public function seen(string $folder,int $uid,bool $seen):void {$this->select($folder);$ok=$seen?@imap_setflag_full($this->imap,(string)$uid,'\\Seen',ST_UID):@imap_clearflag_full($this->imap,(string)$uid,'\\Seen',ST_UID);if(!$ok)throw new RuntimeException('Gelesenstatus nicht bestätigt.');}
 public function move(string $folder,int $uid,string $target):void {
  $this->select($folder);if(!in_array($target,$this->folders(),true))throw new RuntimeException('Zielordner unbekannt.');
  // No EXPUNGE: it could permanently remove unrelated messages flagged by another client.
  if(!@imap_mail_move($this->imap,(string)$uid,$target,CP_UID))throw new RuntimeException('Verschieben nicht bestätigt.');
 }
 public function append(string $folder,string $raw):void {$this->select($folder);if(!@imap_append($this->imap,$this->prefix.$folder,$raw,'\\Seen'))throw new RuntimeException('Gesendet-Kopie nicht bestätigt.');}
}
interface GszMailSender {public function send(array $recipients,string $raw):void;}
class GszMailSendException extends RuntimeException {public function __construct(string $message,public bool $uncertain){parent::__construct($message);}}
final class GszNativeSmtpSender implements GszMailSender {
 public function __construct(private array $config){}
 public function send(array $recipients,string $raw):void {
  $c=$this->config;if(!filter_var($c['allow_mail_send']??false,FILTER_VALIDATE_BOOLEAN))throw new GszMailSendException('Versand serverseitig gesperrt.',false);
  $host=(string)$c['smtp_host'];$from=(string)$c['mail_from'];if(!preg_match('/^[a-z0-9.-]+$/iD',$host)||(int)$c['smtp_port']!==587||!filter_var($from,FILTER_VALIDATE_EMAIL)||preg_match('/[\r\n]/',$from)||empty($c['mail_user'])||empty($c['mail_pass']))throw new GszMailSendException('SMTP-Konfiguration unvollständig.',false);
  foreach($recipients as $r)if(!filter_var($r,FILTER_VALIDATE_EMAIL)||preg_match('/[\r\n]/',$r))throw new GszMailSendException('Empfänger ungültig.',false);
  $ctx=stream_context_create(['ssl'=>['verify_peer'=>true,'verify_peer_name'=>true,'peer_name'=>$host,'allow_self_signed'=>false]]);$socket=@stream_socket_client('tcp://'.$host.':587',$errno,$errstr,10,STREAM_CLIENT_CONNECT,$ctx);if(!$socket)throw new GszMailSendException('SMTP nicht erreichbar.',false);stream_set_timeout($socket,15);$data=false;$accepted=false;
  $read=static function(array $expected)use($socket):string{$all='';for($i=0;$i<60;$i++){$line=fgets($socket,8192);if($line===false||!preg_match('/^(\d{3})([ -])/',$line,$m))throw new RuntimeException('SMTP-Antwort fehlt.');$all.=$line;if($m[2]===' '){if(!in_array((int)$m[1],$expected,true))throw new RuntimeException('SMTP hat Vorgang abgelehnt.');return $all;}}throw new RuntimeException('SMTP-Antwortlimit.');};
  $write=static function(string $s)use($socket):void {for($off=0;$off<strlen($s);){$n=fwrite($socket,substr($s,$off,8192));if($n===false||$n===0)throw new RuntimeException('SMTP-Schreibfehler.');$off+=$n;}};
  $command=static function(string $s,array $expected)use($write,$read):string{$write($s."\r\n");return $read($expected);};
  try{$read([220]);$hello=$command('EHLO gsz.internal',[250]);if(!preg_match('/^250[ -]STARTTLS\b/mi',$hello))throw new RuntimeException('STARTTLS fehlt.');$command('STARTTLS',[220]);if(@stream_socket_enable_crypto($socket,true,STREAM_CRYPTO_METHOD_TLSv1_2_CLIENT)!==true)throw new RuntimeException('TLS fehlgeschlagen.');$hello=$command('EHLO gsz.internal',[250]);if(!preg_match('/^250[ -]AUTH\b[^\r\n]*\bPLAIN\b/mi',$hello))throw new RuntimeException('SMTP AUTH PLAIN fehlt.');$command('AUTH PLAIN '.base64_encode("\0".$c['mail_user']."\0".$c['mail_pass']),[235]);$command('MAIL FROM:<'.$from.'>',[250]);foreach($recipients as $r)$command('RCPT TO:<'.$r.'>',[250,251]);$command('DATA',[354]);$data=true;$raw=preg_replace('/\r?\n/',"\r\n",$raw);$raw=preg_replace('/(?m)^\./','..',$raw);$write(rtrim($raw,"\r\n")."\r\n.\r\n");$read([250]);$accepted=true;try{$command('QUIT',[221]);}catch(Throwable $e){} }
  catch(Throwable $e){if(!$accepted)throw new GszMailSendException('SMTP-Annahme nicht bestätigt.',$data);}finally{fclose($socket);}
 }
}

require_once __DIR__.'/smtp_phpmailer.php';
final class GszSmtpSender implements GszMailSender {
 public function __construct(private array $config){}
 public function send(array $recipients,string $raw):void {
  $transport=(string)($this->config['smtp_transport']??'phpmailer');
  if(!in_array($transport,['phpmailer','native'],true))throw new GszMailSendException('SMTP-Transport unbekannt.',false);
  require_once __DIR__.'/mail_credentials.php';gszMailAuthGuard();
  $sender=$transport==='native'?new GszNativeSmtpSender($this->config):new GszPhpMailerSender($this->config);
  try{$sender->send($recipients,$raw);}catch(Throwable $e){gszMailAuthResult($e,'smtp');throw $e;}gszMailAuthResult(null,'smtp');
 }
}
