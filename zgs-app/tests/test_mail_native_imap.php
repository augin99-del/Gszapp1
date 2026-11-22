<?php
declare(strict_types=1);
// Nativer PHP-IMAP-Adapter gegen einen lokalen Test-Mailserver (Dovecot auf 127.0.0.1, synthetische Nachrichten).
// Befund vom 22.11.2026 (STRATO): „Abrufen“ meldete immer 0 neue Nachrichten, weil c-client „UID n:*“ nicht kennt.
// Kein echtes Postfach. Läuft nur, wenn ext-imap und GSZ_TEST_IMAP (z. B. 127.0.0.1:10143) gesetzt sind.
$server=getenv('GSZ_TEST_IMAP')?:'';if(!extension_loaded('imap')||!preg_match('/^127\.0\.0\.1:\d+$/D',$server)){echo "Native IMAP SKIP (ext-imap or local GSZ_TEST_IMAP missing).\n";exit(0);}
set_error_handler(static function($s,$m,$f,$l){if(error_reporting()&$s)throw new ErrorException($m,0,$s,$f,$l);return false;});
require dirname(__DIR__).'/private/mail_remote.php';
$n=0;function ni(bool $v,string $s):void{global $n;$n++;if(!$v)throw new RuntimeException($s);}
$prefix='{'.$server.'/imap/notls}';$user='gsz-native-'.bin2hex(random_bytes(4));$imap=imap_open($prefix.'INBOX',$user,'synthetic',0,1,['DISABLE_AUTHENTICATOR'=>'GSSAPI']);ni($imap!==false,'local IMAP login');
$r=new ReflectionClass(GszImapRemote::class);$remote=$r->newInstanceWithoutConstructor();foreach(['imap'=>$imap,'prefix'=>$prefix] as $k=>$v){$p=$r->getProperty($k);$p->setValue($remote,$v);}
ni($remote->fetchAfter('INBOX',0,50)===[],'empty inbox gives no messages and no error');
for($i=1;$i<=3;$i++)imap_append($imap,$prefix.'INBOX',"From: Absender $i <a$i@example.invalid>\r\nSubject: Synthetisch $i\r\nMessage-ID: <s$i@example.invalid>\r\n\r\nText $i\r\n");
$all=$remote->fetchAfter('INBOX',0,50);ni(array_column($all,'uid')===[1,2,3],'first fetch returns all existing messages (was 0 before the fix)');ni(str_contains($all[0]['raw'],'Synthetisch 1'),'raw message content');
ni(array_column($remote->fetchAfter('INBOX',2,50),'uid')===[3],'later fetch only returns newer messages');ni($remote->fetchAfter('INBOX',3,50)===[],'nothing new after the last UID');
ni(count($remote->fetchAfter('INBOX',0,2))===2&&$remote->morePending(),'limit respected, more pending reported');
ni($remote->validity('INBOX')>0,'UIDVALIDITY readable');
echo "Native IMAP PASS ($n assertions; local Dovecot, synthetic messages, no real mailbox).\n";
