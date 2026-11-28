<?php
declare(strict_types=1);
// Automatische Datensicherung: einmal je Tag ab 2 Uhr, Aufbewahrung, Hinweise. Synthetische Daten; räumt die eigenen Dateien wieder weg.
set_error_handler(static function($s,$m,$f,$l){if(error_reporting()&$s)throw new ErrorException($m,0,$s,$f,$l);return false;});
require dirname(__DIR__).'/private/install_core.php';require_once dirname(__DIR__).'/private/backup_auto.php';
$dsn=getenv('GSZ_TEST_DSN')?:'';if(!preg_match('/^mysql:unix_socket=[^;]+;dbname=gsz_test_backupauto_[a-z0-9_]+$/D',$dsn))throw new RuntimeException('Empty local test DB required.');
$p=new PDO($dsn,'root','',[PDO::ATTR_ERRMODE=>PDO::ERRMODE_EXCEPTION,PDO::ATTR_DEFAULT_FETCH_MODE=>PDO::FETCH_ASSOC,PDO::ATTR_EMULATE_PREPARES=>false]);if($p->query('SHOW TABLES')->fetchAll())throw new RuntimeException('Empty DB required.');
$n=0;function ba(bool $v,string $s):void{global $n;$n++;if(!$v)throw new RuntimeException($s);}
$dir=gszBackupDir();$before=glob($dir.'/gsz-sicherung-*')?:[];$created=[];
try{
 gszInstallFresh($p);$p->exec("INSERT INTO customers(name) VALUES('Kunde Sicherung')");
 $tz=new DateTimeZone('Europe/Berlin');
 ba(gszBackupAutoRun($p,new DateTimeImmutable('2026-11-28 01:30',$tz))===null,'not before 2 am');
 $m=gszBackupAutoRun($p,new DateTimeImmutable('2026-11-28 02:15',$tz));ba(is_array($m)&&$m['reason']==='automatisch'&&is_file($dir.'/'.$m['file']),'first nightly backup');$created[]=$m['file'];
 $gz=gzopen($dir.'/'.$m['file'],'rb');$sql='';while(!gzeof($gz))$sql.=gzread($gz,65536);gzclose($gz);ba(str_contains($sql,'Kunde Sicherung')&&str_contains($sql,'CREATE TABLE `property_contracts`'),'contains data and all tables');
 ba(substr((string)decoct(fileperms($dir.'/'.$m['file'])&0777),-3)==='600','private file mode');
 gszSettingSet($p,'backup_last',gmdate('Y-m-d H:i:s'));ba(gszBackupAutoRun($p,new DateTimeImmutable('now',$tz))===null||((int)(new DateTimeImmutable('now',$tz))->format('G'))<2,'only once per day');
 // Aufbewahrung: 5 automatische, behalte 3
 for($i=0;$i<4;$i++){$x=gszBackupAutoRun($p,null,true);$created[]=$x['file'];usleep(1100000);}
 $autos=fn()=>count(array_filter(gszBackupList(1000),fn($m)=>$m['reason']==='automatisch'&&in_array($m['file'],$created,true)));
 ba($autos()===5,'five auto backups');$del=gszBackupPrune($p,3);ba($autos()===3&&$del>=2,'prune keeps newest 3 ('.$del.')');
 // Status/Hinweise
 $s=gszBackupStatus($p);ba($s['last']&&$s['age_h']===0&&$s['download_last']===null,'status');
 $att=gszBackupAttention($p);ba(count($att)===1&&str_contains($att[0][1],'herunterladen'),'download reminder when never downloaded');
 gszSettingSet($p,'backup_dl_last',gmdate('Y-m-d H:i:s'));ba(gszBackupAttention($p)===[],'no hint after download');
 gszSettingSet($p,'backup_dl_last',gmdate('Y-m-d H:i:s',time()-31*86400));ba(str_contains(gszBackupAttention($p)[0][1]??'','vor 31 Tagen'),'reminder after 30 days');
 ba(gszBackupLabel(['reason'=>'automatisch'])==='automatisch'&&gszBackupLabel(['reason'=>'vor-update'])==='vor Update','labels');
 ba((int)$p->query("SELECT COUNT(*) FROM app_audit WHERE action='backup_created'")->fetchColumn()>=5,'audited');
}finally{foreach(glob($dir.'/gsz-sicherung-*')?:[] as $f)if(!in_array($f,$before,true))@unlink($f);}
echo "Backup auto PASS ($n checks; synthetic data, local files removed).\n";
