<?php
declare(strict_types=1);
require_once __DIR__.'/install_core.php';
/** Metadata only. No application rows, temporary tables, DDL or permission probes. */
function gszSetupSchemaMetadata(PDO $pdo):array {
 $schema=$pdo->quote((string)$pdo->query('SELECT DATABASE()')->fetchColumn());$tables=[];foreach($pdo->query("SELECT TABLE_NAME,ENGINE,TABLE_TYPE FROM information_schema.TABLES WHERE TABLE_SCHEMA=".$schema." ORDER BY TABLE_NAME")->fetchAll(PDO::FETCH_ASSOC) as $r){if(count($tables)>=256)throw new RuntimeException('GSZ_SCHEMA_TABLE_LIMIT');$tables[$r['TABLE_NAME']]=['engine'=>$r['ENGINE'],'kind'=>$r['TABLE_TYPE'],'columns'=>[],'unique'=>[],'foreign'=>[]];}
 $normalizeType=static fn(string $s):string=>preg_replace('/\b(tinyint|smallint|mediumint|bigint|int|integer)\(\d+\)/','$1',strtolower($s))??strtolower($s);
 foreach($pdo->query("SELECT TABLE_NAME,COLUMN_NAME,COLUMN_TYPE,IS_NULLABLE,COLUMN_DEFAULT,EXTRA,CHARACTER_SET_NAME FROM information_schema.COLUMNS WHERE TABLE_SCHEMA=".$schema." ORDER BY TABLE_NAME,ORDINAL_POSITION")->fetchAll(PDO::FETCH_ASSOC) as $r){$default=$r['COLUMN_DEFAULT'];if(is_string($default)){if(strlen($default)>=2&&$default[0]==="'"&&str_ends_with($default,"'"))$default=substr($default,1,-1);if(in_array(strtolower($default),['current_timestamp()','current_timestamp'],true))$default='current_timestamp';}$extra=str_replace('default_generated','',strtolower($r['EXTRA']));$extra=str_replace('current_timestamp()','current_timestamp',$extra);$tables[$r['TABLE_NAME']]['columns'][$r['COLUMN_NAME']]=[$normalizeType($r['COLUMN_TYPE']),$r['IS_NULLABLE']==='YES',$default,trim($extra),$r['CHARACTER_SET_NAME']];}
 $indexes=[];foreach($pdo->query("SELECT TABLE_NAME,INDEX_NAME,SEQ_IN_INDEX,COLUMN_NAME,SUB_PART FROM information_schema.STATISTICS WHERE TABLE_SCHEMA=".$schema." AND NON_UNIQUE=0 ORDER BY TABLE_NAME,INDEX_NAME,SEQ_IN_INDEX")->fetchAll(PDO::FETCH_ASSOC) as $r)$indexes[$r['TABLE_NAME']][$r['INDEX_NAME']][]=$r['COLUMN_NAME'].($r['SUB_PART']!==null?'('.$r['SUB_PART'].')':'');foreach($indexes as $table=>$groups){foreach($groups as $cols)$tables[$table]['unique'][]=implode(',',$cols);sort($tables[$table]['unique']);}
 $foreign=[];$rules=[];foreach($pdo->query("SELECT TABLE_NAME,CONSTRAINT_NAME,UPDATE_RULE,DELETE_RULE FROM information_schema.REFERENTIAL_CONSTRAINTS WHERE CONSTRAINT_SCHEMA=".$schema."")->fetchAll(PDO::FETCH_ASSOC) as $r)$rules[$r['TABLE_NAME']][$r['CONSTRAINT_NAME']]=[$r['UPDATE_RULE'],$r['DELETE_RULE']];
 foreach($pdo->query("SELECT TABLE_NAME,CONSTRAINT_NAME,ORDINAL_POSITION,COLUMN_NAME,REFERENCED_TABLE_NAME,REFERENCED_COLUMN_NAME FROM information_schema.KEY_COLUMN_USAGE WHERE TABLE_SCHEMA=".$schema." AND CONSTRAINT_SCHEMA=".$schema." AND REFERENCED_TABLE_NAME IS NOT NULL ORDER BY TABLE_NAME,CONSTRAINT_NAME,ORDINAL_POSITION")->fetchAll(PDO::FETCH_ASSOC) as $r){$rule=$rules[$r['TABLE_NAME']][$r['CONSTRAINT_NAME']]??null;if(!$rule)throw new RuntimeException('GSZ_SCHEMA_FOREIGN_METADATA');$foreign[$r['TABLE_NAME']][$r['CONSTRAINT_NAME']][]=$r['COLUMN_NAME'].'>'.$r['REFERENCED_TABLE_NAME'].'.'.$r['REFERENCED_COLUMN_NAME'].':'.$rule[0].':'.$rule[1];}foreach($foreign as $table=>$groups){foreach($groups as $cols)$tables[$table]['foreign'][]=implode(',',$cols);sort($tables[$table]['foreign']);}
 foreach($tables as &$t)ksort($t['columns']);unset($t);ksort($tables);return $tables;
}
/** Exact known historical contracts, derived from versioned SQL on empty local schemas. */
function gszSetupSchemaContracts():array {
 $path=__DIR__.'/setup_schema_contracts.json';if(!is_file($path)||is_link($path)||filesize($path)>4*1024*1024)throw new RuntimeException('GSZ_SCHEMA_CONTRACT_MISSING');$c=json_decode(file_get_contents($path),true,64,JSON_THROW_ON_ERROR);if(!is_array($c)||!isset($c['192'],$c['360']))throw new RuntimeException('GSZ_SCHEMA_CONTRACT_INVALID');return $c;
}
/** Never infer a version from table counts or silently repair marker/structure disagreements. */
function gszSetupInspect(PDO $pdo):array {
 $contracts=gszSetupSchemaContracts();$versions=array_map('intval',array_keys($contracts));sort($versions);$target=max($versions);$out=['status'=>'blocked','code'=>'GSZ_SCHEMA_UNKNOWN','version'=>null,'target'=>$target,'tables'=>0,'can_migrate'=>false,'steps'=>[],'blockers'=>[],'schema_fingerprint'=>''];try{$metadata=gszSetupSchemaMetadata($pdo);}catch(RuntimeException $e){if($e->getMessage()==='GSZ_SCHEMA_FOREIGN_METADATA'){$out['code']='GSZ_SCHEMA_METADATA_PRIVILEGES';$out['tables']=count($pdo->query('SHOW FULL TABLES')->fetchAll());return $out;}if($e->getMessage()==='GSZ_SCHEMA_TABLE_LIMIT'){$out['code']=$e->getMessage();return $out;}throw $e;}$out['tables']=count($metadata);
 $server=(string)$pdo->query('SELECT VERSION()')->fetchColumn();preg_match('/(?:5\.5\.5-)?(\d+\.\d+\.\d+)/',$server,$m);if(!str_contains(strtolower($server),'mariadb')||!isset($m[1])||version_compare($m[1],'10.6.0','<')){$out['code']='GSZ_SCHEMA_DATABASE_ENGINE';return $out;}
 if((string)$pdo->query('SELECT @@character_set_database')->fetchColumn()!=='utf8mb4'){$out['code']='GSZ_SCHEMA_DATABASE_CHARSET';return $out;}
 $markers=[];if(isset($metadata['app_migrations'])){if(!isset($metadata['app_migrations']['columns']['version'])){$out['code']='GSZ_SCHEMA_LEDGER_DAMAGED';return $out;}$markers=$pdo->query('SELECT version FROM app_migrations ORDER BY version')->fetchAll(PDO::FETCH_COLUMN);}
 $out['schema_fingerprint']=hash('sha256',json_encode([$metadata,$markers],JSON_THROW_ON_ERROR));
 if(!$metadata)return array_replace($out,['status'=>'empty','code'=>'GSZ_SCHEMA_EMPTY','version'=>0,'can_migrate'=>true,'steps'=>$versions]);
 $markersByVersion=[];foreach($contracts as $v=>$c)if(isset($c['marker']))$markersByVersion[(int)$v]=$c['marker'];$seen=[];foreach($markers as $marker){$version=array_search($marker,$markersByVersion,true);if($version===false){$out['code']=(int)$marker>$target?'GSZ_SCHEMA_NEWER':'GSZ_SCHEMA_UNKNOWN_MARKER';return $out;}$seen[]=$version;}
 if(isset($metadata['app_migrations'])&&!$seen){$out['code']='GSZ_SCHEMA_LEDGER_EMPTY';return $out;}
 $version=$seen?max($seen):192;$out['version']=$version;$expectedMarkers=array_filter($markersByVersion,fn($v)=>$v<=$version,ARRAY_FILTER_USE_KEY);if(count($seen)!==count($expectedMarkers)){$out['code']='GSZ_SCHEMA_LEDGER_GAP';return $out;}
 $expected=$contracts[(string)$version]['tables'];$latest=$contracts[(string)$target]['tables'];
 foreach($expected as $table=>$spec){if(!isset($metadata[$table])){$out['blockers'][]=$table.':missing';continue;}$actual=$metadata[$table];if($actual['kind']!=='BASE TABLE'||$actual['engine']!=='InnoDB')$out['blockers'][]=$table.':engine';foreach($spec['columns'] as $column=>$shape)if(!isset($actual['columns'][$column])||$actual['columns'][$column]!==$shape)$out['blockers'][]=$table.'.'.$column.':shape';foreach($spec['unique'] as $key)if(!in_array($key,$actual['unique'],true))$out['blockers'][]=$table.':identity_key';foreach($spec['foreign'] as $fk)if(!in_array($fk,$actual['foreign'],true))$out['blockers'][]=$table.':foreign_key';
  foreach($actual['columns'] as $column=>$shape)if(!isset($spec['columns'][$column]))$out['blockers'][]=$table.':unrecorded_column';
 }
 foreach($latest as $table=>$spec)if(!isset($expected[$table])&&isset($metadata[$table]))$out['blockers'][]=$table.':unrecorded_table';
 if($out['blockers']){$out['code']='GSZ_SCHEMA_STRUCTURE_MISMATCH';$out['blockers']=array_values(array_unique($out['blockers']));return $out;}
 $out['steps']=array_values(array_filter($versions,fn($v)=>$v>$version));$out['status']=$version===$target?'current':'upgrade';$out['code']=$version===$target?'GSZ_SCHEMA_CURRENT':'GSZ_SCHEMA_UPGRADE_AVAILABLE';$out['can_migrate']=true;return $out;
}
/** Caller must authenticate owner/local bootstrap, verify backup evidence and obtain explicit consent. */
function gszSetupMigrate(PDO $pdo):array {
 $database=(string)$pdo->query('SELECT DATABASE()')->fetchColumn();if($database==='')throw new RuntimeException('GSZ_SCHEMA_NO_DATABASE');$locks=['gsz_install_'.substr(hash('sha256',$database),0,32),'gsz_upgrade_'.substr(hash('sha256',$database),0,32)];$held=[];
 try{foreach($locks as $name){$q=$pdo->prepare('SELECT GET_LOCK(?,0)');$q->execute([$name]);if((int)$q->fetchColumn()!==1)throw new RuntimeException('GSZ_SCHEMA_MIGRATION_BUSY');$held[]=$name;}$before=gszSetupInspect($pdo);if(!$before['can_migrate'])throw new RuntimeException($before['code']);if($before['status']==='current')return $before;
  if($before['status']==='empty')gszInstallFresh($pdo);else{
   if($before['version']===192)$pdo->exec(file_get_contents(__DIR__.'/migration_v200.sql'));
   if($before['version']<210)gszMigratePhase2($pdo);
   if($before['version']<220)gszMigratePhase3($pdo);
   gszMigrateGesamt($pdo);
  }
  $after=gszSetupInspect($pdo);if($after['status']!=='current')throw new RuntimeException('GSZ_SCHEMA_MIGRATION_INCOMPLETE');return $after;
 }finally{foreach(array_reverse($held) as $name){$q=$pdo->prepare('SELECT RELEASE_LOCK(?)');$q->execute([$name]);}}
}
/** Safe owner-facing explanations; no PDO text, credentials, SQL or connection strings. */
function gszSetupSchemaMessage(string $code):string {return [
 'GSZ_SCHEMA_EMPTY'=>'Die Datenbank ist vollständig leer. Eine Neuinstallation ist möglich; ein Inhaberzugang wird erst im nächsten Schritt eingerichtet.',
 'GSZ_SCHEMA_UPGRADE_AVAILABLE'=>'Ein bekannter Altbestand wurde anhand von Versionsnachweisen und tatsächlicher Struktur erkannt. Vor dem Update müssen Sicherung und Wiederherstellung bestätigt werden.',
 'GSZ_SCHEMA_CURRENT'=>'Die vorhandene Struktur passt vollständig zum aktuellen Migrationsstand. Es ist keine Neuinstallation nötig.',
 'GSZ_SCHEMA_NEWER'=>'Die Datenbank enthält einen neueren Versionsstand als dieses Paket. Kein Downgrade und keine Änderung ausgeführt.',
 'GSZ_SCHEMA_UNKNOWN_MARKER'=>'Die Versionshistorie enthält einen unbekannten Eintrag. Die Datenbank wird nicht automatisch verändert. Passenden Quellstand oder geprüfte Sicherung verwenden.',
 'GSZ_SCHEMA_LEDGER_EMPTY'=>'Die Versionstabelle ist leer, obwohl Tabellen vorhanden sind. Mögliche Teilinstallation: keine Neuinstallation auf diesen Bestand ausführen.',
 'GSZ_SCHEMA_LEDGER_DAMAGED'=>'Die Versionstabelle ist strukturell beschädigt. Die Verbindung funktioniert, aber eine sichere automatische Migration ist nicht möglich.',
 'GSZ_SCHEMA_LEDGER_GAP'=>'In der Versionshistorie fehlen frühere Schritte. Tabellenanzahl allein genügt nicht; historische Migrationen werden nicht blind wiederholt.',
 'GSZ_SCHEMA_STRUCTURE_MISMATCH'=>'Die tatsächlichen Tabellen, Spaltentypen oder Schlüssel passen nicht zur gespeicherten Version. Eine Teilmigration oder individuelle Strukturänderung muss zuerst geprüft oder aus der verifizierten Sicherung zurückgesetzt werden.',
 'GSZ_SCHEMA_METADATA_PRIVILEGES'=>'Die Verbindung funktioniert. Dieser Datenbanknutzer kann notwendige Fremdschlüssel-Metadaten nicht vollständig lesen. Für die Einrichtung sind vollständige Rechte auf genau diese Anwendungsdatenbank erforderlich.',
 'GSZ_SCHEMA_DATABASE_ENGINE'=>'Diese Anwendung benötigt für den getesteten Installationsweg MariaDB ab10.6. Der erkannte Datenbankserver ist dafür nicht freigegeben.',
 'GSZ_SCHEMA_DATABASE_CHARSET'=>'Die Datenbank verwendet als Standard nicht utf8mb4. Eine geeignete Testdatenbank mit dieser Zeichenkodierung verwenden und vorhandene Daten nur über eine geprüfte Sicherung übernehmen.',
 'GSZ_SCHEMA_TABLE_LIMIT'=>'Diese Datenbank enthält mehr Tabellen als für die sichere Einrichtung vorgesehen. Keine automatische Änderung ausgeführt.',
 'GSZ_SCHEMA_MIGRATION_BUSY'=>'Eine andere Installation oder Migration läuft bereits. Nicht parallel erneut starten; später den gesicherten Status prüfen.',
 'GSZ_SCHEMA_MIGRATION_INCOMPLETE'=>'Die Zielstruktur wurde nicht vollständig erreicht. DDL ist nicht vollständig rückrollbar. Nicht blind erneut starten: Teilstand prüfen oder verifizierte Sicherung wiederherstellen.',
 'GSZ_SCHEMA_UNKNOWN'=>'Kein sicher unterstützter Ausgangsstand erkannt. Keine Neuinstallation auf vorhandenen Daten ausführen.'
 ][$code]??'Der Datenbankstatus muss geprüft werden; keine ungeprüfte Strukturänderung ausführen.';}
