<?php
declare(strict_types=1);
// ImapEngine-Adapter gegen einen lokalen Test-Mailserver (Dovecot, Klartext nur auf 127.0.0.1, ohne den TLS-Zwang des Produktivstreams; synthetische Nachrichten).
// Prüft den Abruf inkl. einer Nachricht über 15 MB. Kein echtes Postfach. Läuft nur mit GSZ_TEST_IMAP=127.0.0.1:<port>.
$server=getenv('GSZ_TEST_IMAP')?:'';if(!preg_match('/^127\.0\.0\.1:(\d+)$/D',$server,$mm)){echo "Engine IMAP SKIP (local GSZ_TEST_IMAP missing).\n";exit(0);}
set_error_handler(static function($s,$m,$f,$l){if(error_reporting()&$s)throw new ErrorException($m,0,$s,$f,$l);return false;});
require dirname(__DIR__).'/private/mail_engine.php';require_once dirname(__DIR__).'/private/mail_mime.php';
$n=0;function ei(bool $v,string $s):void{global $n;$n++;if(!$v)throw new RuntimeException($s);}
final class LocalPlainConnection extends \DirectoryTree\ImapEngine\Connection\ImapConnection {public static int $port=0;public function connect(string $host,?int $port=null,array $options=[]):void{parent::connect('127.0.0.1',self::$port,['timeout'=>10]);}}
LocalPlainConnection::$port=(int)$mm[1];$user='gsz-engine-'.bin2hex(random_bytes(4));
$remote=new GszEngineImapRemote(['mail_enabled'=>true,'mail_host'=>'imap.example.invalid','mail_port'=>993,'mail_user'=>$user,'mail_pass'=>'synthetic'],new LocalPlainConnection(new \DirectoryTree\ImapEngine\Connection\Streams\ImapStream()));
ei($remote->fetchAfter('INBOX',0,50)===[],'empty inbox');
$big="From: Gross <g@example.invalid>\r\nSubject: =?UTF-8?Q?Gro=C3=9Fer_Anhang?=\r\nMIME-Version: 1.0\r\nContent-Type: multipart/mixed; boundary=b\r\n\r\n--b\r\nContent-Type: application/octet-stream\r\nContent-Transfer-Encoding: base64\r\n\r\n".chunk_split(base64_encode(random_bytes(12*1024*1024)))."--b--\r\n";
$plain=new \DirectoryTree\ImapEngine\Connection\ImapConnection(new \DirectoryTree\ImapEngine\Connection\Streams\ImapStream());$plain->connect('127.0.0.1',(int)$mm[1],['timeout'=>10]);$plain->login($user,'synthetic');
$plain->append('INBOX',"From: a@example.invalid\r\nSubject: Eins\r\n\r\nText\r\n");$plain->append('INBOX',$big);$plain->append('INBOX',"From: b@example.invalid\r\nSubject: Danach\r\n\r\nText\r\n");$plain->logout();
$items=$remote->fetchAfter('INBOX',0,50);ei(array_column($items,'uid')===[1,2,3],'all three messages, oversized one does not block');
$m=gszMimeParse($items[1]['raw']);ei(strlen($items[1]['raw'])<4096&&$m['subject']==='Großer Anhang'&&str_contains($m['body'],'Webmail'),'oversized message as small placeholder');
ei(str_contains($items[2]['raw'],'Danach'),'following message complete');
echo "Engine IMAP PASS ($n assertions; local Dovecot, synthetic messages, no real mailbox).\n";
