<?php
declare(strict_types=1);
require_once __DIR__.'/mail_engine.php';require_once __DIR__.'/mail_credentials.php';
/** Validated settings only; caller performs protected atomic storage outside the webroot. */
function gszSetupMailValidate(array $input,array $existing=[]):array {
 $text=static function(string $key,int $max,string $fallback='')use($input):string {$v=$input[$key]??$fallback;if(!is_string($v)||strlen($v)>$max||str_contains($v,"\0")||!mb_check_encoding($v,'UTF-8'))throw new InvalidArgumentException('E-Mail-Einrichtung: Eingabe ungültig.');return $v;};
 $enabled=filter_var($input['mail_enabled']??false,FILTER_VALIDATE_BOOLEAN,FILTER_NULL_ON_FAILURE);if($enabled===null)throw new InvalidArgumentException('E-Mail-Aktivierung ungültig.');
 $user=trim($text('mail_user',190,'info@gebaeudeservice-zentrum.de'));$from=trim($text('mail_from',190,$user));if(!filter_var($user,FILTER_VALIDATE_EMAIL)||!filter_var($from,FILTER_VALIDATE_EMAIL)||preg_match('/[\r\n]/',$user.$from))throw new InvalidArgumentException('Vollständige Postfach- und Absenderadresse erforderlich.');
 $password=$text('mail_pass',8192);if($password==='')$password=(string)($existing['mail_pass']??'');if(($enabled&&$password==='')||preg_match('/[\x00-\x1f\x7f]/',$password))throw new InvalidArgumentException('Postfachpasswort fehlt oder ist ungültig.');
 $signature=$text('mail_signature',10000,(string)($existing['mail_signature']??''));$out=['mail_enabled'=>$enabled,'mail_host'=>'imap.strato.de','mail_port'=>993,'mail_user'=>$user,'mail_pass'=>$password,'mail_from'=>$from,'smtp_host'=>'smtp.strato.de','smtp_port'=>465,'smtp_transport'=>'phpmailer','mail_adapter'=>'auto','mail_signature'=>$signature,'allow_mail_send'=>false];
 foreach(['mail_sent_folder','mail_trash_folder','mail_archive_folder'] as $k){$v=$text($k,190,(string)($existing[$k]??''));if(preg_match('/[\x00-\x1f\x7f]/',$v))throw new InvalidArgumentException('E-Mail-Ordnername ungültig.');$out[$k]=$v;}
 return $out;
}
function gszSetupMailError(Throwable $e,string $protocol):array {
 $s=$e->getMessage();$suffix=preg_match('/ssl|tls|cert|zertifikat/i',$s)?'TLS':(preg_match('/auth|anmeld|password|login|zugang/i',$s)?'AUTH':(preg_match('/timed? ?out|timeout|zeitüberschreitung/i',$s)?'TIMEOUT':'CONNECTION'));
 $messages=['AUTH'=>'Anmeldung nicht bestätigt. Vollständige Postfachadresse und das Postfachpasswort prüfen.','TLS'=>'Verschlüsselung/Zertifikatsprüfung nicht bestätigt. Hosting-Zertifikatsspeicher prüfen; TLS-Prüfung nicht deaktivieren.','TIMEOUT'=>'Zeitüberschreitung. Ausgehende Mailverbindungen und Hostinglimits prüfen.','CONNECTION'=>'Verbindung oder Postfachantwort nicht bestätigt. Host/Port, Postfachaktivität und Hostingvoraussetzungen prüfen.'];
 return ['status'=>'BLOCKED','error_code'=>'GSZ_'.$protocol.'_'.$suffix,'message'=>$messages[$suffix]];
}
/** TLS/authentication probe only. Never executes MAIL FROM, RCPT or DATA, never sends a message. */
function gszSetupMailSmtpProbe(array $config):void {
 if(!filter_var($config['mail_enabled']??false,FILTER_VALIDATE_BOOLEAN))throw new RuntimeException('E-Mail deaktiviert.');
 if(($config['smtp_host']??'')!=='smtp.strato.de'||(int)($config['smtp_port']??0)!==465||empty($config['mail_user'])||empty($config['mail_pass']))throw new RuntimeException('SMTP-Konfiguration unvollständig.');
 $smtp=new \PHPMailer\PHPMailer\SMTP();$smtp->do_debug=0;$smtp->Timeout=10;$smtp->Timelimit=10;
 try{if(!$smtp->connect('ssl://smtp.strato.de',465,10,['ssl'=>['verify_peer'=>true,'verify_peer_name'=>true,'peer_name'=>'smtp.strato.de','allow_self_signed'=>false]]))throw new RuntimeException('SMTP-TLS-Verbindung nicht bestätigt.');if(!$smtp->hello('gsz.internal'))throw new RuntimeException('SMTP-Verbindung nicht bestätigt.');if(!$smtp->authenticate((string)$config['mail_user'],(string)$config['mail_pass'],'PLAIN'))throw new RuntimeException('SMTP-Anmeldung nicht bestätigt.');try{$smtp->quit(true);}catch(Throwable $ignored){}
 }finally{$smtp->close();}
}
/** Caller must use owner/setup authorization + CSRF POST. No config values or vendor exception text returned. */
function gszSetupMailProbe(array $config,?callable $imapFactory=null,?callable $smtpProbe=null):array {
 $out=['status'=>'BLOCKED','imap'=>['status'=>'NOT TESTED'],'smtp'=>['status'=>'NOT TESTED'],'folders'=>[],'recommended_folders'=>['mail_sent_folder'=>'','mail_trash_folder'=>'','mail_archive_folder'=>''],'message_sent'=>false];
 if(!filter_var($config['mail_enabled']??false,FILTER_VALIDATE_BOOLEAN)){$out['imap']=['status'=>'BLOCKED','error_code'=>'GSZ_MAIL_DISABLED','message'=>'E-Mail ist nicht verbunden. Einrichtung bei Bedarf aktivieren.'];return $out;}
 try{$remote=$imapFactory?$imapFactory($config):gszMailRemote($config,true);if(!$remote instanceof GszMailRemote)throw new RuntimeException('IMAP-Adapter ungültig.');$folders=$remote->folders();if(!in_array('INBOX',$folders,true)||$remote->validity('INBOX')<1)throw new RuntimeException('IMAP-Posteingang nicht bestätigt.');$out['imap']=['status'=>'PASS','adapter'=>$remote instanceof GszEngineImapRemote?'imapengine':'native-or-test','folder_count'=>count($folders)];$out['folders']=$folders;
  $names=['mail_sent_folder'=>['sent','sent items','sent messages','gesendet','gesendete elemente','gesendete objekte'],'mail_trash_folder'=>['trash','deleted items','deleted messages','papierkorb','gelöschte elemente','gelöschte objekte'],'mail_archive_folder'=>['archive','archives','archiv']];foreach($names as $key=>$aliases)foreach($folders as $folder){$tail=mb_strtolower(preg_replace('~^.*[/.]~','',$folder));if(in_array($tail,$aliases,true)){$out['recommended_folders'][$key]=$folder;break;}}
 }catch(Throwable $e){$out['imap']=gszSetupMailError($e,'IMAP');}
 try{($smtpProbe??'gszSetupMailSmtpProbe')($config);$out['smtp']=['status'=>'PASS','authentication_only'=>true];if(!$smtpProbe)gszMailAuthResult(null,'smtp');}catch(Throwable $e){$out['smtp']=gszSetupMailError($e,'SMTP');if(!$smtpProbe)gszMailAuthResult($e,'smtp');}
 $out['status']=$out['imap']['status']==='PASS'&&$out['smtp']['status']==='PASS'?'PASS':'BLOCKED';return $out;
}
/** Saves the mailbox settings: password and address into the secret vault, everything else into the managed config. */
function gszSetupMailSave(array $input,array $config):array {
 $values=gszSetupMailValidate($input,$config);$password=(string)$values['mail_pass'];unset($values['mail_pass']);
 if($password!=='')gszMailCredentialSave((string)$values['mail_user'],$password);
 if(array_key_exists('allow_mail_send',$values)&&!empty($config['allow_mail_send'])&&(string)($config['mail_user']??'')===(string)$values['mail_user'])$values['allow_mail_send']=true;
 gszManagedSave($values);return $values;
}
