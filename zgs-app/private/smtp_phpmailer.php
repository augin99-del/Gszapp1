<?php
declare(strict_types=1);
require_once __DIR__.'/vendor/phpmailer/src/SMTP.php';
/** No debug transcripts. Preserve uncertainty after DATA; never retry automatically. */
final class GszTrackedPhpSmtp extends \PHPMailer\PHPMailer\SMTP {
 public bool $bodyStarted=false;
 protected function sendCommand($command,$commandstring,$expect){$ok=parent::sendCommand($command,$commandstring,$expect);if($command==='DATA'&&$ok)$this->bodyStarted=true;return $ok;}
 public function client_send($data,$command=''){
  $length=strlen($data);$sent=0;while($sent<$length){$n=parent::client_send(substr($data,$sent),$command);if($n===false||$n===0)throw new RuntimeException('SMTP-Schreibfehler.');$sent+=$n;}return $sent;
 }
}
final class GszPhpMailerSender implements GszMailSender {
 public function __construct(private array $config){}
 public function send(array $recipients,string $raw):void {
  $c=$this->config;if(!filter_var($c['allow_mail_send']??false,FILTER_VALIDATE_BOOLEAN))throw new GszMailSendException('Versand serverseitig gesperrt.',false);
  $host=(string)($c['smtp_host']??'');$from=(string)($c['mail_from']??'');
  if(!preg_match('/^[a-z0-9.-]+$/iD',$host)||!in_array((int)($c['smtp_port']??0),[465,587],true)||!filter_var($from,FILTER_VALIDATE_EMAIL)||preg_match('/[\r\n]/',$from)||empty($c['mail_user'])||empty($c['mail_pass']))throw new GszMailSendException('SMTP-Konfiguration unvollständig.',false);
  if(!$recipients||count($recipients)>10||strlen($raw)>15*1024*1024)throw new GszMailSendException('SMTP-Nachricht/Empfänger ungültig.',false);
  foreach($recipients as $address)if(!filter_var($address,FILTER_VALIDATE_EMAIL)||preg_match('/[\r\n]/',$address))throw new GszMailSendException('Empfänger ungültig.',false);
  $smtp=new GszTrackedPhpSmtp();$smtp->do_debug=0;$smtp->Timeout=15;$smtp->Timelimit=15;$accepted=false;$stage='Verbindung';
  $options=['ssl'=>['verify_peer'=>true,'verify_peer_name'=>true,'peer_name'=>$host,'allow_self_signed'=>false]];
  try{
   if(!$smtp->connect(((int)$c['smtp_port']===465?'ssl://':'').$host,(int)$c['smtp_port'],10,$options)||!$smtp->hello('gsz.internal'))throw new RuntimeException();$stage='TLS';if((int)$c['smtp_port']===587&&(!$smtp->getServerExt('STARTTLS')||!$smtp->startTLS()||!$smtp->hello('gsz.internal')))throw new RuntimeException();$stage='Anmeldung';if(!$smtp->authenticate((string)$c['mail_user'],(string)$c['mail_pass'],'PLAIN'))throw new RuntimeException();
   if(!$smtp->mail($from))throw new RuntimeException('Absender nicht bestätigt.');foreach($recipients as $address)if(!$smtp->recipient($address))throw new RuntimeException('Empfänger nicht bestätigt.');
   if(!$smtp->data($raw))throw new RuntimeException('DATA-Annahme nicht bestätigt.');$accepted=true;
   try{$smtp->quit(true);}catch(Throwable $e){} // accepted DATA must not cause resend on QUIT failure.
  }catch(Throwable $e){if(!$accepted)throw new GszMailSendException('SMTP-Annahme nicht bestätigt: '.$stage.'.',$smtp->bodyStarted);}
  finally{$smtp->close();}
 }
}
