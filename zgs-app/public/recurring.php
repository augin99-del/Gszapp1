<?php
declare(strict_types=1);
require __DIR__.'/bootstrap.php';
if (empty($_SESSION['admin_id'])) redirect('index.php');
require_once dirname(__DIR__).'/private/recurring_rules.php';
$error=$_SESSION['recurring_error']??''; $notice=$_SESSION['recurring_notice']??''; $previous=$_SESSION['recurring_input']??[]; unset($_SESSION['recurring_error'],$_SESSION['recurring_notice'],$_SESSION['recurring_input']);
if ($_SERVER['REQUEST_METHOD']==='POST') {
    checkCsrf();
    try {
        $action=(string)($_POST['action']??''); $id=(int)($_POST['id']??0);
        if ($action==='save') { gszSaveRule($pdo,$_POST,$id?:null); redirect('recurring.php'); }
        elseif ($action==='toggle') {
            $s=$pdo->prepare("UPDATE recurring_jobs SET active=1-active WHERE id=? AND rule_source <> 'routine_unverified'"); $s->execute([$id]);
            if (!$s->rowCount()) throw new RuntimeException('Unbestätigte Tonnenroutine: erst Kalender abgleichen und Regel bearbeiten.');
            redirect('recurring.php');
        } elseif ($action==='generate') {
            $created=zgsGenerateManualTask($pdo,$id,date('Y-m-d'));
            $_SESSION['recurring_notice']=$created?'Aufgabe erstellt.':'Aufgabe für diesen Termin existiert bereits.';
        } else throw new InvalidArgumentException('Aktion unbekannt.');
    } catch (InvalidArgumentException|RuntimeException $ex) {
        $_SESSION['recurring_error']=$ex instanceof PDOException?'Speichern fehlgeschlagen. Datenbankmigration prüfen.':$ex->getMessage();
        if($action==='save')foreach(['service_kind','recurrence_mode','allowed_weekdays_mask','title','notes','property_id','frequency','duration_minutes','next_due_date','season_start_month','season_end_month','rule_source'] as $key)if(isset($_POST[$key])&&is_string($_POST[$key]))$_SESSION['recurring_input'][$key]=mb_substr($_POST[$key],0,$key==='notes'?2000:190);
    }
    redirect('recurring.php'.($id>0?'?edit='.$id:''));
}
$jobs=$pdo->query('SELECT r.*,p.name AS property_name FROM recurring_jobs r LEFT JOIN properties p ON p.id=r.property_id ORDER BY r.active DESC,r.id DESC LIMIT 500')->fetchAll();
$properties=$pdo->query('SELECT id,name FROM properties ORDER BY name')->fetchAll();
$edit=null;
foreach ($jobs as $j) if ((int)$j['id']===(int)($_GET['edit']??0)) $edit=$j;
$form=$edit??['id'=>0,'title'=>'','property_id'=>null,'frequency'=>'weekly','notes'=>'','duration_minutes'=>null,'next_due_date'=>date('Y-m-d'),'season_start_month'=>null,'season_end_month'=>null,'rule_source'=>'manual'];
if ($error && $previous) $form=array_merge($form,array_intersect_key($previous,$form));
$freq=['daily'=>'Täglich','weekly'=>'Wöchentlich','biweekly'=>'Alle 2 Wochen','monthly'=>'Monatlich','quarterly'=>'Vierteljährlich','seasonal'=>'Manuell / nach Bedarf'];
$sources=['manual'=>'Manuelle Regel','routine_unverified'=>'Bisherige Tonnenroutine (ungeprüft, pausiert)','special_weekly'=>'Unabhängige wöchentliche Sonderregel'];
?><!doctype html><html lang="de"><head><meta charset="utf-8"><meta name="viewport" content="width=device-width,initial-scale=1"><meta name="robots" content="noindex,nofollow"><title>Wiederkehrende Leistungen · GSZ</title><link rel="stylesheet" href="zentrale.css"></head><body><main>
<header><a href="index.php">← Zentrale</a><a href="object_import.php">Bestandsobjekte importieren</a></header><h1>Wiederkehrende Leistungen</h1>
<p>Fällige Regeln erzeugen Aufgaben per eingerichtetem Cron oder über „Fällige Regelaufgaben aktualisieren“. Eine Planvorschau kann kommende Regelaufgaben vorschlagen; erst Bestätigen speichert sie. Termine und Dauern sind Schätzwerte und editierbar. Keine Foto- oder Nachweispflicht.</p>
<p>Normale Tonnenroutinen erst nach Abgleich mit dem echten Abfallkalender bestätigen. Geprüfte ICS-/CSV-Dateien unter Müllkalender importieren. Saisonfenster begrenzen Termine; der Rhythmus läuft rechnerisch auch außerhalb weiter.</p>
<?php if($error):?><p class="error" role="alert"><?=e($error)?></p><?php endif?><?php if($notice):?><p role="status"><?=e($notice)?></p><?php endif?>
<div class="grid"><section class="card"><h2><?=$edit?'Leistung bearbeiten':'Neue Leistung'?></h2><form method="post">
<input type="hidden" name="csrf" value="<?=e(csrf())?>"><input type="hidden" name="action" value="save"><input type="hidden" name="id" value="<?=(int)$form['id']?>">
<label>Leistung<input name="title" maxlength="190" value="<?=e((string)$form['title'])?>" required></label>
<label>Objekt<select name="property_id"><option value="">Ohne Objekt</option><?php foreach($properties as $p):?><option value="<?=(int)$p['id']?>" <?=(int)$form['property_id']===(int)$p['id']?'selected':''?>><?=e($p['name'])?></option><?php endforeach?></select></label>
<label>Rhythmus<select name="frequency"><?php foreach($freq as $k=>$v):?><option value="<?=e($k)?>" <?=$form['frequency']===$k?'selected':''?>><?=e($v)?></option><?php endforeach?></select></label>
<label>Leistungsart<select name="service_kind"><?php foreach(['auto'=>'Aus Bezeichnung erkennen','general'=>'Allgemein','stairwell'=>'Treppenhaus (Mo–Mi)','outdoor'=>'Außenanlage (Mo–Fr)','window'=>'Fenster','garage'=>'Tiefgarage','waste'=>'Tonnendienst'] as $key=>$label):?><option value="<?=$key?>" <?=($form['service_kind']??'auto')===$key?'selected':''?>><?=e($label)?></option><?php endforeach?></select></label><label>Wiederholungsart<select name="recurrence_mode"><?php foreach(['auto'=>'Standard: Fenster/Tiefgarage quartalsweise ab Erledigung','calendar'=>'Festes Kalenderintervall','completion'=>'Ab tatsächlicher Erledigung'] as $key=>$label):?><option value="<?=$key?>" <?=($form['recurrence_mode']??'auto')===$key?'selected':''?>><?=e($label)?></option><?php endforeach?></select></label><label>Zusätzliche erlaubte Wochentage<select name="allowed_weekdays_mask"><option value="">Standard gemäß Leistungsart</option><?php foreach([7=>'Montag–Mittwoch',31=>'Montag–Freitag',127=>'Alle Tage (Tonnentermine/Fixierungen bleiben maßgeblich)'] as $mask=>$label):?><option value="<?=$mask?>" <?=($form['allowed_weekdays_mask']??null)===$mask?'selected':''?>><?=e($label)?></option><?php endforeach?></select></label><label>Dauer in Minuten (leer = noch zu schätzen)<input type="number" name="duration_minutes" min="1" max="1440" value="<?=e((string)$form['duration_minutes'])?>"></label>
<label>Nächster Termin (Startvorschlag; bei Bedarf leer)<input type="date" name="next_due_date" value="<?=e($form['next_due_date'])?>"></label>
<label>Saison von Monat (leer = ganzjährig)<input type="number" name="season_start_month" min="1" max="12" value="<?=e((string)$form['season_start_month'])?>"></label><label>Bis Monat (einschließlich; z.B. 10 bis 4 über Jahreswechsel)<input type="number" name="season_end_month" min="1" max="12" value="<?=e((string)$form['season_end_month'])?>"></label>
<label>Regelquelle<select name="rule_source"><?php foreach($sources as $k=>$v):?><option value="<?=e($k)?>" <?=$form['rule_source']===$k?'selected':''?>><?=e($v)?></option><?php endforeach?></select></label>
<label><input type="checkbox" name="confirm_routine" value="1"> Ich habe Fraktion und reale Abfuhrtermine geprüft und bestätige die Umstellung einer bisherigen Tonnenroutine.</label>
<label>Notizen<textarea name="notes" maxlength="2000"><?=e($form['notes'])?></textarea></label><button>Leistung speichern</button><?php if($edit):?><a href="recurring.php">Abbrechen</a><?php endif?></form></section>
<section class="card"><h2>Gespeicherte Leistungen · <?=count($jobs)?> (max. 500)</h2><?php foreach($jobs as $j):?><div class="item"><strong><?=e($j['title'])?></strong><p><?=e($j['property_name']??'Ohne Objekt')?> · <?=e($freq[$j['frequency']])?> · <?=$j['active']?'Aktiv':'Pausiert'?> · <?=e((string)($j['duration_minutes']??'offen'))?> Min.</p><p><?=gszRecurrenceMode($j)==='completion'?'Wiederholung ab Erledigung':'Festes Kalenderintervall'?><?php if(!empty($j['last_completed_on'])):?> · Zuletzt erledigt <?=e($j['last_completed_on'])?><?php endif?></p><p>Nächster Termin: <?=e($j['next_due_date']??'manuell / nicht gesetzt')?> · <?=e($sources[$j['rule_source']])?></p><?php if($j['season_start_month']):?><p>Saison: <?=(int)$j['season_start_month']?> bis <?=(int)$j['season_end_month']?> (einschließlich)</p><?php endif?><p><?=nl2br(e($j['notes']))?></p><a href="recurring.php?edit=<?=(int)$j['id']?>">Bearbeiten</a>
<div class="actions"><?php foreach(['toggle'=>$j['active']?'Pausieren':'Aktivieren','generate'=>'Aufgabe für heute'] as $action=>$label):?><form method="post"><input type="hidden" name="csrf" value="<?=e(csrf())?>"><input type="hidden" name="id" value="<?=(int)$j['id']?>"><input type="hidden" name="action" value="<?=e($action)?>"><button class="ghost" <?=$j['rule_source']==='routine_unverified'?'disabled':''?>><?=e($label)?></button></form><?php endforeach?></div></div><?php endforeach?></section></div></main></body></html>
