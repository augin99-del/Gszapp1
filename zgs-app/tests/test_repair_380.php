<?php
declare(strict_types=1);
// Nachstellung des STRATO-Befunds vom 22.11.2026: Tabelle estate_bank_transactions hat den Standardzeichensatz utf8mb3,
// die mit 380 ergänzte Spalte erbte ihn. (1) Neue Updates legen die Spalten jetzt ausdrücklich in utf8mb4 an.
// (2) Bereits aktualisierte Datenbanken lassen sich über den geschützten Angleichen-Weg reparieren.
set_error_handler(static function($s,$m,$f,$l){if(error_reporting()&$s)throw new ErrorException($m,0,$s,$f,$l);return false;});
require dirname(__DIR__).'/private/install_core.php';require dirname(__DIR__).'/private/web_update.php';
$dsn=getenv('GSZ_TEST_DSN')?:'';if(!preg_match('/^mysql:unix_socket=[^;]+;dbname=gsz_test_final_[a-z0-9_]+$/D',$dsn))throw new RuntimeException('Empty local test DB required.');
$p=new PDO($dsn,'root','',[PDO::ATTR_ERRMODE=>PDO::ERRMODE_EXCEPTION,PDO::ATTR_DEFAULT_FETCH_MODE=>PDO::FETCH_ASSOC,PDO::ATTR_EMULATE_PREPARES=>false]);if($p->query('SHOW TABLES')->fetchAll())throw new RuntimeException('Empty DB required.');
$n=0;function rp(bool $v,string $s):void{global $n;$n++;if(!$v)throw new RuntimeException($s);}function rb(callable $f,string $s):void{$x=false;try{$f();}catch(Throwable $e){$x=true;}rp($x,$s);}
$cs=fn(string $t,string $c)=>$p->query("SELECT CHARACTER_SET_NAME FROM information_schema.COLUMNS WHERE TABLE_SCHEMA=DATABASE() AND TABLE_NAME='$t' AND COLUMN_NAME='$c'")->fetchColumn();
// 370-Bestand, dessen Tabellen den Standardzeichensatz utf8mb3 haben (Spalten selbst utf8mb4, wie bei STRATO beobachtet)
$p->exec(file_get_contents(dirname(__DIR__).'/private/fresh_install_v192.sql'));$p->exec(file_get_contents(dirname(__DIR__).'/private/migration_v200.sql'));gszMigratePhase2($p);gszMigratePhase3($p);
foreach([230,240,250,260,270,280,290,300,310,320,330,340,350,360,370] as $v)$p->exec(file_get_contents(dirname(__DIR__).'/private/migration_v'.$v.'.sql'));
$pass='Synthetic-Repair-Only!';$p->prepare('INSERT INTO admins(email,password_hash) VALUES(?,?)')->execute(['owner@example.invalid',password_hash($pass,PASSWORD_DEFAULT)]);
foreach(['estate_bank_transactions','estate_tenants'] as $t)$p->exec("ALTER TABLE $t DEFAULT CHARACTER SET utf8mb3");
rp(gszSetupInspect($p)['status']==='upgrade','370 with utf8mb3 table defaults is still a recognised 370');
gszSetupMigrate($p);rp($cs('estate_bank_transactions','counterparty_iban_hash')==='utf8mb4'&&$cs('estate_tenants','iban_hash')==='utf8mb4'&&$cs('estate_tenants','payer_names')==='utf8mb4','new update creates the 380 columns explicitly in utf8mb4');
rp(gszSetupInspect($p)['status']==='current','update reaches a clean 380 despite utf8mb3 table defaults');
// Zustand wie live: Spalte nachträglich in utf8mb3
$p->exec('ALTER TABLE estate_bank_transactions MODIFY counterparty_iban_hash CHAR(64) CHARACTER SET utf8mb3 NULL');$p->exec("INSERT INTO estate_bank_transactions(account_id,source_key,record_hash,external_id,booked_on,value_on,amount_cents,currency,purpose,counterparty,counterparty_iban_hash) VALUES(1,'".str_repeat('a',64)."','".str_repeat('b',64)."','x','2026-11-01','2026-11-01',100,'EUR','Synthetic','Synthetic','".str_repeat('c',64)."')");
$i=gszSetupInspect($p);rp($i['code']==='GSZ_SCHEMA_STRUCTURE_MISMATCH'&&gszRepairable380($i),'live situation recognised as the known repairable difference');
rb(fn()=>gszWebRepair380($p,1,$pass,['backup_kept'=>1,'backup_sha256'=>str_repeat('0',64)],'127.0.0.7'),'repair needs a real app backup');
$b=gszDatabaseBackup($p,'test');try{
 rb(fn()=>gszWebRepair380($p,1,'wrong',['backup_kept'=>1,'backup_sha256'=>$b['sha256']],'127.0.0.7'),'repair needs the owner password');
 rb(fn()=>gszWebRepair380($p,1,$pass,['backup_sha256'=>$b['sha256']],'127.0.0.7'),'repair needs the download confirmation');
 $r=gszWebRepair380($p,1,$pass,['backup_kept'=>1,'backup_sha256'=>$b['sha256']],'127.0.0.7');rp($r['status']==='current'&&$r['fixed']===['estate_bank_transactions.counterparty_iban_hash'],'repair fixes exactly the one column');
 rp($p->query('SELECT counterparty_iban_hash FROM estate_bank_transactions')->fetchColumn()===str_repeat('c',64),'stored values unchanged');
 rp(gszRepair380($p)===[],'repair is repeatable without changes');
 $p->exec('ALTER TABLE tasks ADD COLUMN synthetic_extra INT NULL');rp(!gszRepairable380(gszSetupInspect($p)),'unknown differences are not offered for automatic repair');
}finally{@unlink(gszBackupDir().'/'.$b['file']);@unlink(gszBackupDir().'/'.$b['file'].'.json');}
echo "Repair 380 PASS ($n assertions; utf8mb3 table defaults as observed on STRATO).\n";
