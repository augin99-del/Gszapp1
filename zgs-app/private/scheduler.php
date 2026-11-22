<?php
declare(strict_types=1);
require_once __DIR__.'/recurrence_policy.php';
/** Existing recurrence engine, extended with durations, stable anchors and seasons. */
function zgsValidDate(string $date): bool {
    $d = DateTimeImmutable::createFromFormat('!Y-m-d', $date, new DateTimeZone('Europe/Berlin'));
    return $d !== false && $d->format('Y-m-d') === $date;
}
function zgsAdvanceDate(string $date, string $frequency, int $dayOfMonth): string {
    if (!zgsValidDate($date) || $dayOfMonth < 1 || $dayOfMonth > 31) throw new InvalidArgumentException('Ungültiges Datum oder Monatsanker.');
    $d = new DateTimeImmutable($date, new DateTimeZone('Europe/Berlin'));
    if ($frequency === 'daily') return $d->modify('+1 day')->format('Y-m-d');
    if ($frequency === 'weekly') return $d->modify('+7 days')->format('Y-m-d');
    if ($frequency === 'biweekly') return $d->modify('+14 days')->format('Y-m-d');
    if ($frequency === 'monthly' || $frequency === 'quarterly') {
        $first = $d->modify('first day of this month')->modify('+'.($frequency === 'monthly' ? 1 : 3).' months');
        return $first->setDate((int)$first->format('Y'), (int)$first->format('m'), min($dayOfMonth, (int)$first->format('t')))->format('Y-m-d');
    }
    throw new InvalidArgumentException('Kein automatisch planbarer Rhythmus.');
}
function zgsInSeason(string $date, ?int $start, ?int $end): bool {
    if ($start === null && $end === null) return true;
    if ($start === null || $end === null || $start < 1 || $end < 1 || $start > 12 || $end > 12) throw new InvalidArgumentException('Ungültiges Saisonfenster.');
    $m = (int)substr($date, 5, 2);
    return $start <= $end ? $m >= $start && $m <= $end : $m >= $start || $m <= $end;
}
/** Unique occurrence remains stable even when due_date is moved or the task completed. */
function zgsInsertOccurrence(PDO $pdo, array $job, string $date): int {
    $ins = $pdo->prepare('INSERT INTO tasks(property_id,title,due_date,recurring_job_id,scheduled_for,duration_minutes) VALUES(?,?,?,?,?,?)');
    try {
        $ins->execute([$job['property_id'], $job['title'], $date, $job['id'], $date, $job['duration_minutes'] ?? null]);
        return 1;
    } catch (PDOException $e) {
        // Unlike INSERT IGNORE, only an actual duplicate occurrence is accepted.
        if ((int)($e->errorInfo[1] ?? 0) !== 1062) throw $e;
        $check = $pdo->prepare('SELECT id FROM tasks WHERE recurring_job_id=? AND scheduled_for=?');
        $check->execute([$job['id'], $date]);
        if (!$check->fetchColumn()) throw $e;
        return 0;
    }
}
/** Explicit new waste-service choices may pause generation without rewriting legacy rules. */
function zgsWasteSpecialPolicies(PDO $pdo):array {
 if(!(int)$pdo->query("SELECT COUNT(*) FROM app_migrations WHERE version='360_waste_connections'")->fetchColumn())return [];
 $paused=[];foreach($pdo->query('SELECT property_id,enabled,bins_json,source_id FROM waste_connections LIMIT 5001')->fetchAll(PDO::FETCH_ASSOC) as $c){$bins=json_decode($c['bins_json'],true,32,JSON_THROW_ON_ERROR);if(!$c['enabled']||(($bins||$c['source_id'])&&!in_array('Gelb',array_column($bins,'canonical'),true)))$paused[(int)$c['property_id']]=true;}return $paused;
}
function zgsWasteSpecialBlocked(array $job,array $paused):bool {return ($paused[(int)($job['property_id']??0)]??false)&&($job['rule_source']??'')==='special_weekly'&&in_array($job['title']??'',['Gelbe Tonne rausstellen','Gelbe Tonne reinstellen'],true);}
function zgsGenerateDueTasks(PDO $pdo, string $today, int $maxPerJob = 24): array {
    if (!zgsValidDate($today) || $maxPerJob < 1 || $maxPerJob > 366) throw new InvalidArgumentException('Ungültiger Planungstag oder Limit.');
    $created = 0; $pending = 0;$wastePaused=zgsWasteSpecialPolicies($pdo);
    $sel = $pdo->prepare("SELECT id FROM recurring_jobs WHERE active=1 AND rule_source <> 'routine_unverified' AND frequency <> 'seasonal' AND next_due_date IS NOT NULL AND next_due_date <= ? ORDER BY id LIMIT 500");
    $sel->execute([$today]);
    foreach ($sel->fetchAll(PDO::FETCH_COLUMN) as $id) {
        $pdo->beginTransaction();
        try {
            $stmt = $pdo->prepare('SELECT * FROM recurring_jobs WHERE id=? FOR UPDATE');
            $stmt->execute([(int)$id]); $job = $stmt->fetch(PDO::FETCH_ASSOC);
            if (!$job || zgsWasteSpecialBlocked($job,$wastePaused) || !(int)$job['active'] || $job['rule_source'] === 'routine_unverified' || !$job['next_due_date'] || $job['frequency'] === 'seasonal') { $pdo->commit(); continue; }
            if (gszRecurrenceMode($job)==='completion') {
                $open=$pdo->prepare('SELECT COUNT(*) FROM tasks WHERE recurring_job_id=? AND done=0');$open->execute([$job['id']]);
                if(!(int)$open->fetchColumn()&&zgsInSeason($job['next_due_date'],$job['season_start_month']===null?null:(int)$job['season_start_month'],$job['season_end_month']===null?null:(int)$job['season_end_month'])){$created+=zgsInsertOccurrence($pdo,$job,$job['next_due_date']);$pdo->prepare('UPDATE recurring_jobs SET last_generated=? WHERE id=?')->execute([$job['next_due_date'],$job['id']]);}
                $pdo->commit();continue;
            }
            $date = $job['next_due_date']; $count = 0; $last = $job['last_generated'];
            $anchor = (int)($job['anchor_day'] ?? substr($date, 8, 2));
            while ($date <= $today && $count < $maxPerJob) {
                if (zgsInSeason($date, isset($job['season_start_month']) ? (int)$job['season_start_month'] : null, isset($job['season_end_month']) ? (int)$job['season_end_month'] : null)) {
                    $created += zgsInsertOccurrence($pdo, $job, $date);
                    $last = $date;
                }
                $date = zgsAdvanceDate($date, $job['frequency'], $anchor);
                $count++;
            }
            if ($date <= $today) $pending++;
            $up = $pdo->prepare('UPDATE recurring_jobs SET next_due_date=?,last_generated=?,anchor_day=? WHERE id=?');
            $up->execute([$date, $last, $anchor, $job['id']]);
            $pdo->commit();
        } catch (Throwable $e) { if ($pdo->inTransaction()) $pdo->rollBack(); throw $e; }
    }
    return ['created' => $created, 'backlog_jobs' => $pending];
}
function zgsGenerateManualTask(PDO $pdo, int $id, string $today): int {
    if (!zgsValidDate($today)) throw new InvalidArgumentException('Ungültiges Datum.');
    $pdo->beginTransaction();
    try {
        $st = $pdo->prepare('SELECT * FROM recurring_jobs WHERE id=? FOR UPDATE');
        $st->execute([$id]); $job = $st->fetch(PDO::FETCH_ASSOC);
        if (!$job || zgsWasteSpecialBlocked($job,zgsWasteSpecialPolicies($pdo)) || !(int)$job['active']) throw new RuntimeException('Leistung nicht aktiv.');
        if ($job['rule_source'] === 'routine_unverified') throw new RuntimeException('Tonnenroutine zuerst mit Abfuhrkalender abgleichen und als manuelle Regel bestätigen.');
        if (!zgsInSeason($today, $job['season_start_month'] === null ? null : (int)$job['season_start_month'], $job['season_end_month'] === null ? null : (int)$job['season_end_month'])) throw new RuntimeException('Außerhalb des Saisonfensters.');
        if ($job['frequency'] !== 'seasonal' && $job['next_due_date'] !== $today) throw new RuntimeException('Für automatische Regeln zuerst den nächsten Termin auf heute setzen.');
        if(gszRecurrenceMode($job)==='completion'){$pending=$pdo->prepare('SELECT COUNT(*) FROM tasks WHERE recurring_job_id=? AND done=0');$pending->execute([$id]);if((int)$pending->fetchColumn()){$pdo->commit();return 0;}}
        $created = zgsInsertOccurrence($pdo, $job, $today);
        $pdo->prepare('UPDATE recurring_jobs SET last_generated=? WHERE id=?')->execute([$today, $id]);
        $pdo->commit();
        return $created;
    } catch (Throwable $e) { if ($pdo->inTransaction()) $pdo->rollBack(); throw $e; }
}
