<?php
declare(strict_types=1);
require __DIR__.'/bootstrap.php';
if (empty($_SESSION['admin_id'])) redirect('index.php');
require_once dirname(__DIR__).'/private/phase1_import.php';
$start=(string)($_POST['start']??$_GET['start']??date('Y-m-d')); $error=''; $result=null; $preview=null;
try {
    if (!zgsValidDate($start)) throw new InvalidArgumentException('Gültigen Starttermin wählen.');
    if ($_SERVER['REQUEST_METHOD']==='POST') {
        checkCsrf();
        if (($_POST['confirm']??'')!=='yes') throw new InvalidArgumentException('Importvorschau ausdrücklich bestätigen.');
        $result=gszApplyImport($pdo,$start,(string)($_POST['fingerprint']??''),is_array($_POST['choices']??null)?$_POST['choices']:[]);
    }
    $preview=gszImportPreview($pdo,$start);
} catch (PDOException $ex) { $error='Datenbank nicht bereit oder Import fehlgeschlagen. Migration v200 prüfen; keine Zugangsdaten in Fehlermeldungen übernehmen.'; }
catch (InvalidArgumentException|RuntimeException $ex) { $error=$ex->getMessage(); }
?><!doctype html><html lang="de"><head><meta charset="utf-8"><meta name="viewport" content="width=device-width,initial-scale=1"><meta name="robots" content="noindex,nofollow"><title>Bestandsobjekte · GSZ</title><link rel="stylesheet" href="zentrale.css"></head><body><main>
<header><a href="index.php#objekte">← Objekte</a><a href="recurring.php">Wiederkehrende Leistungen</a></header><h1>Bestandsobjekte prüfen und importieren</h1>
<p>Sieben Objekte aus dem Masterauftrag. Lippstädter Straße 5: Ort Rheda-Wiedenbrück am 09.11.2026 vom Inhaber bestätigt. Kundenzuordnungen bleiben offen.</p>
<p>Bestehende Angaben werden beibehalten; nur die Importidentität wird ergänzt. Wiederholter Import legt keine weiteren Datensätze an. Bei abweichenden Bestandsleistungen ist eine Zuordnung erforderlich. Flexible Starttermine sind Vorschläge; Tonnenroutinen bleiben bis zum Kalenderabgleich pausiert. Gelbe Tonne Fontänenstraße: wöchentliche Sonderregel Mittwoch/Donnerstag.</p>
<form method="get"><label>Start für neue Regeln<input type="date" name="start" value="<?=e($start)?>" required></label><button>Vorschau aktualisieren</button></form>
<?php if($error):?><p class="error" role="alert"><?=e($error)?></p><p><a href="object_import.php?start=<?=e(rawurlencode($start))?>">Vorschau neu laden</a></p><?php endif?>
<?php if($result):?><p role="status">Import abgeschlossen: <?=(int)$result['properties_created']?> Objekte und <?=(int)$result['rules_created']?> Regeln neu; <?=(int)$result['properties_reused']?> Objekte und <?=(int)$result['rules_reused']?> Regeln wiederverwendet. Bestandswerte unverändert.</p><?php endif?>
<?php if($preview):?><?php foreach($preview['errors'] as $err):?><p class="error"><?=e($err)?></p><?php endforeach?>
<form method="post"><input type="hidden" name="csrf" value="<?=e(csrf())?>"><input type="hidden" name="start" value="<?=e($start)?>"><input type="hidden" name="fingerprint" value="<?=e($preview['fingerprint'])?>">
<?php foreach($preview['rows'] as $row):?><section class="card"><h2><?=e($row['spec']['address'])?></h2><p><?=$row['match']?'Bestand #'.(int)$row['match']['id'].' wiederverwenden; Angaben bleiben erhalten.':'Neues Objekt'?></p>
<?php foreach($row['rules'] as $r): $s=$r['spec'];?><div class="item"><strong><?=e($s['title'])?></strong>
<?php if($r['match']):?><p>Bestandsleistung #<?=(int)$r['match']['id']?> bleibt erhalten: <?=e($r['match']['title'])?> · <?=e($r['match']['frequency'])?> · <?=e((string)($r['match']['duration_minutes']??'offen'))?> Min. · <?=$r['match']['active']?'aktiv':'pausiert'?> · <?=e($r['match']['next_due_date']??'kein Termin')?>. Vorschlagswerte werden nicht übernommen; nach Import bei Bedarf bearbeiten.</p>
<?php else:?><p>Vorschlag: <?=e($s['frequency'])?> · <?=e((string)($s['duration_minutes']??'noch zu schätzen'))?> Min. · <?=e($s['next_due_date']??'nur manuell')?> · <?=$s['active']?'aktiv':'pausiert'?><?php if(isset($s['season_start_month'])):?> · Saison <?=(int)$s['season_start_month']?>–<?=(int)$s['season_end_month']?><?php endif?></p><p><?=e($s['notes'])?></p><?php endif?>
<?php if($r['needs_review']):?><label>Vorhandene Leistung zuordnen oder Neuanlage bestätigen<select name="choices[<?=e($s['key'])?>]" required><option value="">Bitte prüfen</option><option value="new">Neue Leistung ausdrücklich anlegen</option><?php foreach($row['existing_rules'] as $j): if($j['seed_key']!==null)continue;?><option value="<?=(int)$j['id']?>">#<?=(int)$j['id']?> <?=e($j['title'])?> · <?=e($j['frequency'])?> · <?=e((string)($j['duration_minutes']??'offen'))?> Min.</option><?php endforeach?></select></label><?php endif?></div><?php endforeach?></section><?php endforeach?>
<label><input type="checkbox" name="confirm" value="yes" required> Vorschau und Zuordnungen geprüft. Neue Stammdaten/Regeln in dieser Installation speichern.</label><button <?=$preview['errors']?'disabled':''?>>Geprüfte Objekte und Regeln importieren</button></form><?php endif?></main></body></html>
