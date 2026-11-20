<?php
declare(strict_types=1);
require_once __DIR__.'/scheduler.php';
function gszValidateRule(array $input): array {
    $title=trim((string)($input['title']??'')); $notes=trim((string)($input['notes']??''));
    $frequency=(string)($input['frequency']??'weekly'); $next=(string)($input['next_due_date']??'');
    $source=(string)($input['rule_source']??'manual');
    $duration=filter_var($input['duration_minutes']??'',FILTER_VALIDATE_INT);
    $startRaw=(string)($input['season_start_month']??''); $endRaw=(string)($input['season_end_month']??'');
    $start=$startRaw===''?null:filter_var($startRaw,FILTER_VALIDATE_INT);
    $end=$endRaw===''?null:filter_var($endRaw,FILTER_VALIDATE_INT);
    if ($title==='' || mb_strlen($title)>190 || mb_strlen($notes)>2000 || !in_array($frequency,['daily','weekly','biweekly','monthly','quarterly','seasonal'],true) || !in_array($source,['manual','routine_unverified','special_weekly'],true)) throw new InvalidArgumentException('Leistung, Rhythmus oder Quelle ungültig.');
    if (($input['duration_minutes']??'')==='') $duration=null;
    if ($duration===false || ($duration!==null && ($duration<1 || $duration>1440))) throw new InvalidArgumentException('Dauer: 1–1440 Minuten oder leer (noch zu schätzen).');
    if ($next!=='' && !zgsValidDate($next)) throw new InvalidArgumentException('Ungültiges Datum.');
    if ($start!==null || $end!==null) {
        if (!is_int($start) || !is_int($end)) throw new InvalidArgumentException('Beide Saisonmonate angeben.');
        zgsInSeason('2026-01-01',$start,$end);
    }
    return ['title'=>$title,'notes'=>$notes,'frequency'=>$frequency,'next_due_date'=>$frequency==='seasonal'?null:($next?:null),'duration_minutes'=>$duration,'season_start_month'=>$start,'season_end_month'=>$end,'rule_source'=>$source];
}
function gszSaveRule(PDO $pdo, array $input, ?int $id=null): void {
    $v=gszValidateRule($input); $v1=gszV1Ready($pdo);$policy=[];if($v1){$kind=(string)($input['service_kind']??'auto');$mode=(string)($input['recurrence_mode']??'auto');if(!in_array($kind,['auto','general','stairwell','outdoor','window','garage','waste'],true)||!in_array($mode,['auto','calendar','completion'],true))throw new InvalidArgumentException('Leistungsart/Wiederholungsart ungültig.');if($mode==='completion'&&!in_array($v['frequency'],['monthly','quarterly'],true))throw new InvalidArgumentException('Erledigungsbasierter Rhythmus: monatlich oder quartalsweise.');$maskRaw=(string)($input['allowed_weekdays_mask']??'');$mask=$maskRaw===''?null:filter_var($maskRaw,FILTER_VALIDATE_INT);if($mask!==null&&($mask===false||$mask<1||$mask>127))throw new InvalidArgumentException('Wochentagsmaske1–127 oder leer.');$policy=[$kind,$mode,$mask];} $propertyRaw=(string)($input['property_id']??'');
    $propertyId=$propertyRaw===''?null:filter_var($propertyRaw,FILTER_VALIDATE_INT);
    if ($propertyId!==null) {
        if ($propertyId===false || $propertyId<1) throw new InvalidArgumentException('Ungültiges Objekt.');
        $s=$pdo->prepare('SELECT id FROM properties WHERE id=?'); $s->execute([$propertyId]);
        if (!$s->fetchColumn()) throw new InvalidArgumentException('Objekt nicht gefunden.');
    }
    $pdo->beginTransaction();
    try {
        $old=null;
        if ($id!==null) {
            $s=$pdo->prepare('SELECT * FROM recurring_jobs WHERE id=? FOR UPDATE');$s->execute([$id]);$old=$s->fetch(PDO::FETCH_ASSOC);
            if (!$old) throw new InvalidArgumentException('Leistung nicht gefunden.');
            if ($old['seed_key'] && (int)($old['property_id']??0)!==(int)($propertyId??0)) throw new InvalidArgumentException('Importierte Leistung bleibt mit ihrem Objekt verknüpft.');
            if ($old['rule_source']==='routine_unverified' && $v['rule_source']!=='routine_unverified' && empty($input['confirm_routine'])) throw new InvalidArgumentException('Abgleich der Tonnenroutine ausdrücklich bestätigen.');
        }
        $anchor=$v['next_due_date']?(int)substr($v['next_due_date'],8,2):null;
        if ($old && $old['next_due_date']===$v['next_due_date'] && $old['frequency']===$v['frequency']) $anchor=$old['anchor_day'];
        $args=[$propertyId,$v['title'],$v['frequency'],$v['notes'],$v['next_due_date'],$v['duration_minutes'],$anchor,$v['season_start_month'],$v['season_end_month'],$v['rule_source']];
        if ($id===null) $pdo->prepare('INSERT INTO recurring_jobs(property_id,title,frequency,notes,next_due_date,duration_minutes,anchor_day,season_start_month,season_end_month,rule_source,active) VALUES(?,?,?,?,?,?,?,?,?,?,?)')->execute([...$args,$v['rule_source']==='routine_unverified'?0:1]);
        else $pdo->prepare('UPDATE recurring_jobs SET property_id=?,title=?,frequency=?,notes=?,next_due_date=?,duration_minutes=?,anchor_day=?,season_start_month=?,season_end_month=?,rule_source=?,active=IF(?=\'routine_unverified\',0,active) WHERE id=?')->execute([...$args,$v['rule_source'],$id]);
        if($v1){if($old)foreach(['service_kind','recurrence_mode','allowed_weekdays_mask'] as $i=>$field)if(!array_key_exists($field,$input))$policy[$i]=$old[$field];$ruleId=$id??(int)$pdo->lastInsertId();$pdo->prepare('UPDATE recurring_jobs SET service_kind=?,recurrence_mode=?,allowed_weekdays_mask=? WHERE id=?')->execute([...$policy,$ruleId]);}
        $pdo->commit();
    } catch (Throwable $e) { if ($pdo->inTransaction()) $pdo->rollBack(); throw $e; }
}
