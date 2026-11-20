<?php
declare(strict_types=1);
/**
 * Logical database backup for STRATO shared hosting (no mysqldump required).
 * Writes a consistent InnoDB snapshot as gzip-compressed SQL into private/backups (outside the web root).
 * One statement per line; the file can be imported with phpMyAdmin or restored with private/db_restore_cli.php.
 */
const GSZ_BACKUP_TABLES_CREATED_AFTER_370=['estate_bank_uploads'];
function gszBackupDir():string {
 require_once __DIR__.'/managed_config.php';$dir=__DIR__.'/backups';gszPrivateDirectory($dir);return realpath($dir)?:throw new RuntimeException('GSZ_BACKUP_DIR');
}
function gszBackupColumnTypes(PDO $pdo,string $table):array {
 $q=$pdo->prepare('SELECT COLUMN_NAME,DATA_TYPE FROM information_schema.COLUMNS WHERE TABLE_SCHEMA=DATABASE() AND TABLE_NAME=? ORDER BY ORDINAL_POSITION');$q->execute([$table]);return array_column($q->fetchAll(PDO::FETCH_ASSOC),'DATA_TYPE','COLUMN_NAME');
}
function gszBackupValue(PDO $pdo,mixed $value,string $type):string {
 if($value===null)return 'NULL';if(in_array($type,['blob','tinyblob','mediumblob','longblob','binary','varbinary'],true))return $value===''?"''":'0x'.bin2hex((string)$value);
 return str_replace(["\r","\n"],['\\r','\\n'],$pdo->quote((string)$value));
}
/** Returns metadata; the SHA-256 refers to the exact .sql.gz file the owner can download. */
function gszDatabaseBackup(PDO $pdo,string $reason='manual'):array {
 if(!function_exists('gzopen'))throw new RuntimeException('GSZ_BACKUP_ZLIB');if(!preg_match('/^[a-z0-9_-]{1,40}$/D',$reason))throw new InvalidArgumentException('GSZ_BACKUP_REASON');
 $dir=gszBackupDir();$stamp=gmdate('Ymd-His');$name='gsz-sicherung-'.$stamp.'-'.bin2hex(random_bytes(4)).'.sql.gz';$tmp=$dir.'/.'.$name.'.part';
 $tables=$pdo->query("SELECT TABLE_NAME FROM information_schema.TABLES WHERE TABLE_SCHEMA=DATABASE() AND TABLE_TYPE='BASE TABLE' ORDER BY TABLE_NAME")->fetchAll(PDO::FETCH_COLUMN);if(!$tables)throw new RuntimeException('GSZ_BACKUP_EMPTY');
 $markers=in_array('app_migrations',$tables,true)?$pdo->query('SELECT version FROM app_migrations ORDER BY version')->fetchAll(PDO::FETCH_COLUMN):[];
 $gz=gzopen($tmp,'wb6');if(!$gz)throw new RuntimeException('GSZ_BACKUP_WRITE');@chmod($tmp,0600);$rows=[];
 $write=static function(string $line)use($gz):void{if(gzwrite($gz,$line."\n")===false)throw new RuntimeException('GSZ_BACKUP_WRITE');};
 try{
  $pdo->exec('SET SESSION TRANSACTION ISOLATION LEVEL REPEATABLE READ');$pdo->exec('START TRANSACTION WITH CONSISTENT SNAPSHOT');
  $write('-- GSZ-Zentrale Datenbanksicherung '.gmdate('Y-m-d H:i:s').' UTC; Grund: '.$reason.'; Versionen: '.implode(',',$markers));
  $write('-- Wiederherstellen: phpMyAdmin > Importieren (diese .sql.gz-Datei) ODER php private/db_restore_cli.php <datei> --bestaetigt');
  $write('SET NAMES utf8mb4;');$write('SET FOREIGN_KEY_CHECKS=0;');$write("SET SQL_MODE='NO_AUTO_VALUE_ON_ZERO';");
  foreach(GSZ_BACKUP_TABLES_CREATED_AFTER_370 as $later)if(!in_array($later,$tables,true))$write('DROP TABLE IF EXISTS `'.$later.'`;');
  foreach($tables as $table){
   if(!preg_match('/^[A-Za-z0-9_]{1,64}$/D',$table))throw new RuntimeException('GSZ_BACKUP_TABLE_NAME');
   $create=$pdo->query('SHOW CREATE TABLE `'.$table.'`')->fetch(PDO::FETCH_NUM)[1];$write('DROP TABLE IF EXISTS `'.$table.'`;');$write(str_replace(["\r","\n"],' ',$create).';');
   $types=gszBackupColumnTypes($pdo,$table);$cols='`'.implode('`,`',array_keys($types)).'`';$count=0;$batch=[];
   $q=$pdo->query('SELECT * FROM `'.$table.'`');while(($r=$q->fetch(PDO::FETCH_ASSOC))!==false){$vals=[];foreach($types as $c=>$t)$vals[]=gszBackupValue($pdo,$r[$c]??null,$t);$batch[]='('.implode(',',$vals).')';$count++;if(count($batch)>=200){$write('INSERT INTO `'.$table.'` ('.$cols.') VALUES '.implode(',',$batch).';');$batch=[];}}
   if($batch)$write('INSERT INTO `'.$table.'` ('.$cols.') VALUES '.implode(',',$batch).';');$rows[$table]=$count;
  }
  $write('SET FOREIGN_KEY_CHECKS=1;');$write('-- Ende der Sicherung ('.count($tables).' Tabellen)');
  $pdo->exec('COMMIT');
 }catch(Throwable $e){if($pdo->inTransaction())$pdo->rollBack();else{try{$pdo->exec('ROLLBACK');}catch(Throwable $ignored){}}gzclose($gz);@unlink($tmp);throw $e;}
 if(!gzclose($gz)||!rename($tmp,$dir.'/'.$name)){@unlink($tmp);throw new RuntimeException('GSZ_BACKUP_WRITE');}
 $sha=hash_file('sha256',$dir.'/'.$name);$meta=['file'=>$name,'sha256'=>$sha,'bytes'=>filesize($dir.'/'.$name),'tables'=>count($tables),'rows'=>$rows,'markers'=>$markers,'created_at'=>gmdate('c'),'reason'=>$reason];
 gszPrivateWrite($dir.'/'.$name.'.json',json_encode($meta,JSON_THROW_ON_ERROR|JSON_PRETTY_PRINT));return $meta;
}
function gszBackupList(int $limit=10):array {
 $dir=gszBackupDir();$out=[];foreach(glob($dir.'/gsz-sicherung-*.sql.gz.json')?:[] as $f){if(is_link($f))continue;$m=json_decode((string)file_get_contents($f),true);if(is_array($m)&&isset($m['file'],$m['sha256'])&&is_file($dir.'/'.$m['file']))$out[]=$m;}
 usort($out,fn($a,$b)=>strcmp($b['created_at'],$a['created_at']));return array_slice($out,0,$limit);
}
/** Path of a listed backup; never accepts arbitrary paths. */
function gszBackupPath(string $file):string {
 if(!preg_match('/^gsz-sicherung-\d{8}-\d{6}-[a-f0-9]{8}\.sql\.gz$/D',$file))throw new InvalidArgumentException('GSZ_BACKUP_NAME');$p=gszBackupDir().'/'.$file;if(is_link($p)||!is_file($p))throw new RuntimeException('GSZ_BACKUP_MISSING');return $p;
}
/** A migration may only start with an intact app backup created within the last $maxAge seconds. */
function gszRecentBackup(string $sha256,int $maxAge=7200):array {
 if(!preg_match('/^[a-f0-9]{64}$/D',$sha256))throw new RuntimeException('Bitte zuerst „Sicherung jetzt erstellen“ ausführen.');
 foreach(gszBackupList(50) as $m)if(hash_equals($m['sha256'],$sha256)){if(strtotime($m['created_at'])<time()-$maxAge)throw new RuntimeException('Die Sicherung ist älter als zwei Stunden. Bitte neu sichern.');if(!hash_equals($sha256,hash_file('sha256',gszBackupPath($m['file']))))throw new RuntimeException('Sicherungsdatei verändert. Bitte neu sichern.');return $m;}
 throw new RuntimeException('Keine passende Sicherung gefunden. Bitte zuerst sichern.');
}
/** Full replacement restore (CLI only). Drops every current table first, then replays the dump. */
function gszDatabaseRestore(PDO $pdo,string $path):array {
 if(PHP_SAPI!=='cli')throw new RuntimeException('GSZ_RESTORE_CLI_ONLY');$gz=gzopen($path,'rb');if(!$gz)throw new RuntimeException('GSZ_RESTORE_READ');
 $first=gzgets($gz,4096);if(!is_string($first)||!str_starts_with($first,'-- GSZ-Zentrale Datenbanksicherung'))throw new RuntimeException('GSZ_RESTORE_FORMAT');
 $pdo->exec('SET FOREIGN_KEY_CHECKS=0');$n=0;
 try{foreach($pdo->query("SELECT TABLE_NAME FROM information_schema.TABLES WHERE TABLE_SCHEMA=DATABASE() AND TABLE_TYPE='BASE TABLE'")->fetchAll(PDO::FETCH_COLUMN) as $t)$pdo->exec('DROP TABLE `'.str_replace('`','',$t).'`');
  while(($line=gzgets($gz,64*1024*1024))!==false){$line=rtrim($line,"\n");if($line===''||str_starts_with($line,'-- '))continue;if(!str_ends_with($line,';'))throw new RuntimeException('GSZ_RESTORE_FORMAT');$pdo->exec($line);$n++;}
 }finally{gzclose($gz);$pdo->exec('SET FOREIGN_KEY_CHECKS=1');}
 return ['statements'=>$n,'tables'=>count($pdo->query('SHOW TABLES')->fetchAll())];
}
