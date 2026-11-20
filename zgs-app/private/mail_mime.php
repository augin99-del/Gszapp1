<?php
declare(strict_types=1);
require_once __DIR__.'/platform.php';
function gszMailHeader(string $s):string {return mb_substr(iconv_mime_decode($s,ICONV_MIME_DECODE_CONTINUE_ON_ERROR,'UTF-8')?:$s,0,1000);}
function gszMailAddresses(string $raw):array {
 if(preg_match('/[\r\n\x00]/',$raw))throw new InvalidArgumentException('Ungültige Empfängerzeile.');if(trim($raw)==='')return [];$out=[];
 foreach(explode(',',$raw) as $email){$email=trim($email);if(!filter_var($email,FILTER_VALIDATE_EMAIL)||strlen($email)>254)throw new InvalidArgumentException('Kommagetrennte einzelne E-Mail-Adressen erforderlich.');$out[strtolower($email)]=$email;}
 if(count($out)>10)throw new InvalidArgumentException('Maximal 10 Empfänger.');return array_values($out);
}
function gszMimeParams(string $value):array {
 $parts=str_getcsv($value,';','"','');$type=strtolower(trim(array_shift($parts)??''));$params=[];foreach($parts as $part)if(str_contains($part,'=')){[$k,$v]=explode('=',$part,2);$params[strtolower(trim($k))]=trim($v," \t\"");}return [$type,$params];
}
function gszMimeHeaders(string $s):array {
 $s=preg_replace('/\r?\n[ \t]+/',' ',$s)??$s;$h=[];foreach(preg_split('/\r?\n/',$s) as $line){if(!str_contains($line,':'))continue;[$k,$v]=explode(':',$line,2);$k=strtolower(trim($k));if(isset($h[$k]))$h[$k].=', '.trim($v);else $h[$k]=trim($v);}return $h;
}
function gszMimeParse(string $raw):array {
 if(strlen($raw)>15*1024*1024||str_contains($raw,"\0"))throw new InvalidArgumentException('MIME-Nachricht zu groß oder ungültig.');$root=preg_split('/\r?\n\r?\n/',$raw,2);$headers=gszMimeHeaders($root[0]);$plain=[];$html=[];$attachments=[];$count=0;
 $walk=function(string $part,int $depth)use(&$walk,&$plain,&$html,&$attachments,&$count):void{
  if($depth>10||++$count>100)throw new RuntimeException('MIME-Verschachtelung/Teilelimit überschritten.');[$head,$body]=array_pad(preg_split('/\r?\n\r?\n/',$part,2),2,'');$h=gszMimeHeaders($head);[$type,$params]=gszMimeParams($h['content-type']??'text/plain');[$disp,$dp]=gszMimeParams($h['content-disposition']??'');
  if(str_starts_with($type,'multipart/')){$boundary=$params['boundary']??'';if($boundary===''||strlen($boundary)>100||preg_match('/[\r\n]/',$boundary))throw new RuntimeException('MIME-Grenze fehlt.');$parts=explode('--'.$boundary,$body);if(count($parts)<3)throw new RuntimeException('Unvollständiges Multipart.');foreach(array_slice($parts,1) as $p){if(str_starts_with($p,'--'))break;$walk(ltrim($p,"\r\n"),$depth+1);}return;}
  $encoding=strtolower($h['content-transfer-encoding']??'');if($encoding==='base64'){$decoded=base64_decode(preg_replace('/\s/','',$body),true);if($decoded===false)throw new RuntimeException('Ungültiges Base64.');$body=$decoded;}elseif($encoding==='quoted-printable')$body=quoted_printable_decode($body);
  $filename=$dp['filename']??$params['name']??'';if(isset($dp['filename*'])){$filename=preg_replace("/^UTF-8''/i",'',$dp['filename*']);$filename=rawurldecode($filename);}
  if($disp==='attachment'||$filename!==''||!in_array($type,['text/plain','text/html'],true)){
   if($body==='')return;if(count($attachments)>=20||strlen($body)>10*1024*1024)throw new RuntimeException('Anhanglimit überschritten.');$attachments[]=['name'=>gszName(gszMailHeader($filename?:'anhang.bin')),'mime'=>$type?:'application/octet-stream','data'=>$body];return;
  }
  $charset=$params['charset']??'UTF-8';if(strcasecmp($charset,'UTF-8')!==0){$converted=@iconv($charset,'UTF-8//IGNORE',$body);if($converted===false)throw new RuntimeException('Unbekannter Zeichensatz.');$body=$converted;}$body=mb_scrub($body,'UTF-8');
  if($type==='text/plain')$plain[]=$body;else $html[]=$body;
 };
 $walk($raw,0);$body=implode("\n",$plain);if($body==='')foreach($html as $s){$s=preg_replace('~<(script|style|iframe|object)\b[^>]*>.*?</\1>~is','',$s)??'';$s=preg_replace('~<(br|/p|/div|/tr)\b[^>]*>~i',"\n",$s)??$s;$body.=html_entity_decode(strip_tags($s),ENT_QUOTES|ENT_HTML5,'UTF-8');}
 return ['headers'=>$headers,'subject'=>gszMailHeader($headers['subject']??'(ohne Betreff)'),'sender'=>gszMailHeader($headers['from']??''),'reply_to'=>gszMailHeader($headers['reply-to']??''),'recipients'=>gszMailHeader(($headers['to']??'').(isset($headers['cc'])?', '.$headers['cc']:'')),'message_id'=>mb_substr($headers['message-id']??'',0,255),'body'=>mb_substr($body,0,200000),'attachments'=>$attachments,'automatic'=>isset($headers['list-unsubscribe'])||strtolower($headers['auto-submitted']??'no')!=='no'||strtolower($headers['precedence']??'')==='bulk','sent_at'=>isset($headers['date'])&&strtotime($headers['date'])!==false?gmdate('Y-m-d H:i:s',strtotime($headers['date'])):null];
}
function gszBuildMail(array $draft,string $from,array $attachments=[]):array {
 if(!filter_var($from,FILTER_VALIDATE_EMAIL)||preg_match('/[\r\n]/',$from))throw new InvalidArgumentException('Absender ungültig.');$to=gszMailAddresses($draft['to_address']);$cc=gszMailAddresses($draft['cc_address']);$bcc=gszMailAddresses($draft['bcc_address']??'');$unique=[];foreach([...$to,...$cc,...$bcc] as $address)$unique[strtolower($address)]=$address;$recipients=array_values($unique);if(!$to||count($recipients)>10)throw new InvalidArgumentException('Mindestens ein und höchstens zehn Empfänger.');$subject=$draft['subject'];if(trim($subject)===''||mb_strlen($subject)>190||preg_match('/[\r\n]/',$subject)||strlen($draft['body'])>150000)throw new InvalidArgumentException('Betreff/Text ungültig.');
 $id=$draft['message_id'];if(!preg_match('/^<[a-f0-9]{32}@gsz\.internal>$/D',$id))throw new RuntimeException('Ungültige Nachrichtenidentität.');
 $attachmentBytes=0;foreach($attachments as $a)$attachmentBytes+=strlen($a['data']);if(count($attachments)>10||$attachmentBytes>10*1024*1024)throw new RuntimeException('Anhang-Gesamtlimit 10 MB.');
 $boundary='gsz_'.bin2hex(random_bytes(16));$h=['Date: '.gmdate('r'),'From: <'.$from.'>','To: '.implode(', ',$to),'Subject: =?UTF-8?B?'.base64_encode($subject).'?=','Message-ID: '.$id,'MIME-Version: 1.0'];if($cc)$h[]='Cc: '.implode(', ',$cc);if(!empty($draft['reply_header'])&&preg_match('/^<[^<>\s\r\n]{1,240}>$/D',$draft['reply_header']))$h[]='In-Reply-To: '.$draft['reply_header'];
 $text="Content-Type: text/plain; charset=UTF-8\r\nContent-Transfer-Encoding: base64\r\n\r\n".chunk_split(base64_encode($draft['body']),76,"\r\n");
 if($attachments){$h[]='Content-Type: multipart/mixed; boundary="'.$boundary.'"';$body='--'.$boundary."\r\n".$text;foreach($attachments as $a){$name=rawurlencode(gszName($a['name']));$body.='--'.$boundary."\r\nContent-Type: application/octet-stream\r\nContent-Disposition: attachment; filename*=UTF-8''".$name."\r\nContent-Transfer-Encoding: base64\r\n\r\n".chunk_split(base64_encode($a['data']),76,"\r\n");}$body.='--'.$boundary."--\r\n";}else{$h[]='Content-Type: text/plain; charset=UTF-8';$h[]='Content-Transfer-Encoding: base64';$body=chunk_split(base64_encode($draft['body']),76,"\r\n");}
 $raw=implode("\r\n",$h)."\r\n\r\n".$body;if(strlen($raw)>15*1024*1024)throw new RuntimeException('Gesamtnachricht maximal 15 MB.');return ['raw'=>$raw,'recipients'=>$recipients];
}
