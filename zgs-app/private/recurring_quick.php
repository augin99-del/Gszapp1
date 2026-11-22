<?php
declare(strict_types=1);
/** Regelmäßige Arbeit direkt am Objekt anlegen (z. B. „Treppenhausreinigung · wöchentlich“).
 *  Nutzt dieselbe Regel-Logik wie „Leistungsvorlagen“ (gszSaveRule); fällige Aufgaben entstehen sofort. */
require_once __DIR__.'/recurring_rules.php';require_once __DIR__.'/scheduler.php';
const GSZ_QUICK_FREQ=['weekly'=>'wöchentlich','biweekly'=>'alle 2 Wochen','monthly'=>'monatlich','quarterly'=>'vierteljährlich','daily'=>'täglich'];
const GSZ_QUICK_WORK=['Treppenhausreinigung','Außenanlage pflegen','Fenster reinigen','Tiefgarage reinigen','Grünpflege','Winterdienst','Hausmeisterrundgang'];
function gszQuickRule(PDO $pdo,int $property,array $in):void {
 $title=trim((string)($in['rule_title']??''));$freq=(string)($in['rule_frequency']??'weekly');if(!isset(GSZ_QUICK_FREQ[$freq]))throw new InvalidArgumentException('Rhythmus ungültig.');
 $first=(string)($in['rule_first']??'');if($first==='')$first=(new DateTimeImmutable('today',new DateTimeZone('Europe/Berlin')))->format('Y-m-d');if(!zgsValidDate($first))throw new InvalidArgumentException('Erster Termin ungültig.');
 gszSaveRule($pdo,['title'=>$title,'property_id'=>(string)$property,'frequency'=>$freq,'next_due_date'=>$first,'duration_minutes'=>(string)($in['rule_duration']??''),'notes'=>'','rule_source'=>'manual','service_kind'=>'auto','recurrence_mode'=>'auto','allowed_weekdays_mask'=>'','season_start_month'=>'','season_end_month'=>''],null);
 zgsGenerateDueTasks($pdo,(new DateTimeImmutable('today',new DateTimeZone('Europe/Berlin')))->format('Y-m-d'));
}
