<?php
declare(strict_types=1);
/**
 * Automatische Datensicherung (11/2026): einmal täglich über den Wecker (tick.php), ab 2 Uhr nachts deutscher Zeit.
 * Bewahrt die letzten N automatischen Sicherungen auf (Einstellung backup_keep, Standard 14), manuelle und
 * Update-Sicherungen bleiben 90 Tage (höchstens 10). Erinnert monatlich ans Herunterladen auf Mac/iPhone.
 * Gesichert wird die komplette Datenbank (alle Tabellen) – nicht die hochgeladenen Dateien (Fotos, PDFs).
 */
require_once __DIR__.'/db_backup.php';require_once __DIR__.'/leads.php';
function gszBackupTz():DateTimeZone {return new DateTimeZone('Europe/Berlin');}
/** Läuft höchstens einmal je Kalendertag (deutsche Zeit), frühestens ab 02:00. Rückgabe: Metadaten oder null. */
function gszBackupAutoRun(PDO $pdo,?DateTimeImmutable $now=null,bool $force=false):?array {
 $now=($now??new DateTimeImmutable('now'))->setTimezone(gszBackupTz());$last=gszSetting($pdo,'backup_last');
 if(!$force){if((int)$now->format('G')<2)return null;if($last!==''&&(new DateTimeImmutable($last.' UTC'))->setTimezone(gszBackupTz())->format('Y-m-d')===$now->format('Y-m-d'))return null;}
 $m=gszDatabaseBackup($pdo,'automatisch');gszSettingSet($pdo,'backup_last',gmdate('Y-m-d H:i:s'));gszAudit($pdo,null,'backup_created',['file'=>$m['file'],'sha256'=>$m['sha256'],'auto'=>true]);
 gszBackupPrune($pdo);return $m;
}
/** Löscht alte Sicherungen nach der Aufbewahrungsregel. Rückgabe: Anzahl gelöschter Dateien. */
function gszBackupPrune(PDO $pdo,?int $keep=null):int {
 $keep??=max(3,min(60,(int)gszSetting($pdo,'backup_keep')?:14));$dir=gszBackupDir();$all=gszBackupList(1000);$auto=0;$other=0;$n=0;
 foreach($all as $m){$isAuto=($m['reason']??'')==='automatisch';$old=strtotime((string)$m['created_at'])<time()-90*86400;
  $drop=$isAuto?(++$auto>$keep):(++$other>10||$old);
  if($drop){foreach([$dir.'/'.$m['file'],$dir.'/'.$m['file'].'.json'] as $f)if(is_file($f)&&!is_link($f)&&@unlink($f))$n++;}}
 return intdiv($n,2);
}
/** Zustand für Anzeige und Hinweise. */
function gszBackupStatus(PDO $pdo):array {
 $list=[];try{$list=gszBackupList(100);}catch(Throwable $e){}
 $last=$list[0]??null;$lastAuto=null;foreach($list as $m)if(($m['reason']??'')==='automatisch'){$lastAuto=$m;break;}
 $dl=gszSetting($pdo,'backup_dl_last');$bytes=array_sum(array_map(fn($m)=>(int)($m['bytes']??0),$list));
 return ['list'=>$list,'last'=>$last,'last_auto'=>$lastAuto,'age_h'=>$last?(int)floor((time()-strtotime((string)$last['created_at']))/3600):null,'download_last'=>$dl!==''?$dl:null,'download_age_d'=>$dl!==''?(int)floor((time()-strtotime($dl.' UTC'))/86400):null,'bytes'=>$bytes,'wecker'=>gszSetting($pdo,'tick_last')];
}
/** Hinweise für „Heute“: keine Sicherung seit 3 Tagen (Wecker läuft nicht?) bzw. 30 Tage nicht heruntergeladen. */
function gszBackupAttention(PDO $pdo):array {
 $s=gszBackupStatus($pdo);$out=[];
 if($s['age_h']===null||$s['age_h']>72)$out[]=['sicherung.php',$s['age_h']===null?'Noch keine Datensicherung – jetzt sichern':'Keine Datensicherung seit '.intdiv($s['age_h'],24).' Tagen – Wecker prüfen'];
 elseif($s['download_age_d']===null||$s['download_age_d']>=30)$out[]=['sicherung.php','Datensicherung auf Mac/iPhone herunterladen'.($s['download_age_d']!==null?' (zuletzt vor '.$s['download_age_d'].' Tagen)':'')];
 return $out;
}
function gszBackupLabel(array $m):string {return ['automatisch'=>'automatisch','manuell'=>'von Hand','vor-update'=>'vor Update','cli-update'=>'vor Update'][$m['reason']??'']??(string)($m['reason']??'');}
