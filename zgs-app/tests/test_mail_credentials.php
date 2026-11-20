<?php
declare(strict_types=1);
// Teil D: Postfach-Passwort einmal eintragen. Läuft in einer temporären Kopie von private/ (nie im echten Vault).
// Kein echtes Postfach: Anmeldefehler werden synthetisch erzeugt; es wird keine Netzwerkverbindung aufgebaut.
$root=sys_get_temp_dir().'/gsz-mail-cred-'.bin2hex(random_bytes(8));mkdir($root.'/private',0700,true);mkdir($root.'/public',0700);
foreach(glob(dirname(__DIR__).'/private/*.php') as $f)copy($f,$root.'/private/'.basename($f));symlink(dirname(__DIR__).'/private/vendor',$root.'/private/vendor');
$code=<<<'CODE'
<?php
declare(strict_types=1);set_error_handler(static function($s,$m,$f,$l){if(error_reporting()&$s)throw new ErrorException($m,0,$s,$f,$l);return false;});
$_SERVER['DOCUMENT_ROOT']=__DIR__.'/public';require __DIR__.'/private/config_loader.php';require_once __DIR__.'/private/mail_engine.php';require_once __DIR__.'/private/mail_credentials.php';require_once __DIR__.'/private/setup_mail.php';
$n=0;function mc(bool $v,string $s):void{global $n;$n++;if(!$v)throw new RuntimeException($s);}function md(callable $f,string $s,string $class='Throwable'):void{$x=false;try{$f();}catch(Throwable $e){$x=$e instanceof $class;}mc($x,$s);}
$secret='Synthetic-Mailbox-Secret-'.bin2hex(random_bytes(6));
// 1. Einmal eintragen über die grafische Einrichtung (gleicher Code wie connections.php/setup.php).
gszSetupMailSave(['mail_user'=>'postfach@example.invalid','mail_pass'=>$secret,'mail_enabled'=>'1','mail_signature'=>''],gszLoadConfig());
$p=gszMailVaultPaths();mc(is_file($p['vault'])&&is_file($p['key']),'vault and separate key file created');
clearstatcache();mc((fileperms(__DIR__.'/private/secrets')&0777)===0700&&(fileperms($p['vault'])&0777)===0600&&(fileperms($p['key'])&0777)===0600,'restrictive permissions 0700/0600');
mc(!str_starts_with(realpath($p['vault']),realpath(__DIR__.'/public')),'vault outside the web root');
mc(!str_contains(file_get_contents($p['vault']),$secret)&&!str_contains(file_get_contents($p['vault']),'postfach@'),'no clear text in vault file');
$managed=@file_get_contents(__DIR__.'/private/server-config/managed.json')?:'';mc(!str_contains($managed,$secret),'not in managed config');$plainManaged=json_encode(gszManagedLoad());mc(!str_contains($plainManaged,$secret)&&!array_key_exists('mail_pass',gszManagedLoad()),'managed config holds no mail password even after decryption');
// 2. Jeder neue Request / neue Session / Cron lädt den Zugang ohne erneute Eingabe.
$c=gszLoadConfig();mc($c['mail_pass']===$secret&&$c['mail_user']==='postfach@example.invalid'&&$c['mail_credential_source']==='vault'&&filter_var($c['mail_enabled'],FILTER_VALIDATE_BOOLEAN),'config for IMAP/SMTP/cron comes from the vault');
$out=shell_exec(escapeshellarg(PHP_BINARY).' -r '.escapeshellarg('require "'.__DIR__.'/private/config_loader.php"; $c=gszLoadConfig(); echo hash("sha256",$c["mail_pass"]);'));mc($out===hash('sha256',$secret),'separate CLI process (cron) reads the same stored access');
// 3. Falsches/geändertes Passwort: eine Ablehnung stoppt alle weiteren automatischen Versuche ohne Netzwerkzugriff.
gszMailAuthResult(new RuntimeException('IMAP-Anmeldung nicht bestätigt. Postfachzugang prüfen.'),'imap');mc(gszMailAuthBlockedNow(),'auth rejection recorded');
$cfg=$c;$cfg['mail_host']='imap.invalid';$cfg['smtp_host']='smtp.invalid';
md(fn()=>gszMailRemote($cfg),'IMAP sync blocked before any connection',GszMailAuthBlocked::class);
md(fn()=>(new GszSmtpSender($cfg+['allow_mail_send'=>true]))->send(['x@example.invalid'],"Subject: x\r\n\r\nx"),'SMTP send blocked before any connection',GszMailAuthBlocked::class);
require_once __DIR__.'/private/mail_service.php';md(fn()=>gszMailSync(new PDO('sqlite::memory:'),gszMailRemote($cfg),'INBOX'),'cron mail job blocked as well',GszMailAuthBlocked::class);
$msg='';try{gszMailRemote($cfg);}catch(GszMailAuthBlocked $e){$msg=$e->getMessage();}mc(str_contains($msg,'neu eintragen')&&!str_contains($msg,$secret),'clear message with the way to re-enter, no secret');
gszMailAuthResult(new RuntimeException('IMAP-TLS/Zertifikat nicht bestätigt.'),'imap');mc(gszMailAuthBlockedNow(),'TLS problem does not unlock');
gszMailAuthGuard(true);mc(true,'explicit owner test is allowed exactly when asked');
// 4. Passwort ändern hebt die Sperre auf.
$new='Synthetic-Changed-'.bin2hex(random_bytes(6));gszSetupMailSave(['mail_user'=>'postfach@example.invalid','mail_pass'=>$new,'mail_enabled'=>'1'],gszLoadConfig());mc(!gszMailAuthBlockedNow()&&gszLoadConfig()['mail_pass']===$new,'new password stored, block lifted');
gszSetupMailSave(['mail_user'=>'postfach@example.invalid','mail_pass'=>'','mail_enabled'=>'1'],gszLoadConfig());mc(gszLoadConfig()['mail_pass']===$new,'empty password field keeps the stored one');
md(fn()=>gszMailCredentialSave('postfach@example.invalid',"bad\nvalue"),'control characters refused');
// 5. Altbestand: Passwort aus älterer Konfiguration wird einmal in den Vault übernommen.
gszMailCredentialDelete();mc(!gszMailCredentialExists()&&!filter_var(gszLoadConfig()['mail_enabled'],FILTER_VALIDATE_BOOLEAN),'delete removes access and disables mail');mc(gszLoadConfig()['mail_pass']==='','no password left after delete');
mc(gszMailCredentialMigrate(['mail_user'=>'alt@example.invalid','mail_pass'=>'Synthetic-Legacy'])&&gszMailCredentialLoad()['mail_pass']==='Synthetic-Legacy','legacy password moved into the vault');
gszMailCredentialDelete();
echo "Mail credentials PASS ($n assertions; synthetic, no real mailbox contacted).\n";
CODE;
file_put_contents($root.'/run.php',$code);passthru(escapeshellarg(PHP_BINARY).' '.escapeshellarg($root.'/run.php'),$rc);
exec('rm -rf '.escapeshellarg($root));exit($rc);
