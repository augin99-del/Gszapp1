<?php
declare(strict_types=1);
require_once __DIR__.'/phase1_catalog.php';
/** Pure preview; no writes. Conservative matching, conflicts require manual correction. */
function gszImportPreview(PDO $pdo, string $start): array {
    $properties = $pdo->query('SELECT * FROM properties ORDER BY id')->fetchAll(PDO::FETCH_ASSOC);
    $jobs = $pdo->query('SELECT * FROM recurring_jobs ORDER BY id')->fetchAll(PDO::FETCH_ASSOC);
    $rows = []; $errors = [];
    foreach (gszPhase1Catalog($start) as $spec) {
        $matches = []; $street = gszNormalize($spec['street']); $address = gszNormalize($spec['address']);
        foreach ($properties as $p) {
            $pa = gszNormalize(preg_replace('/\b[0-9]{5}\b/u', '', $p['address']) ?? $p['address']); $pn = gszNormalize($p['name']);
            if ($p['seed_key'] === $spec['key'] || $pa === $address || ($pn === $street && str_contains($pa, gszNormalize($spec['city'])))) $matches[] = $p;
            elseif ($pn === $street || $pa === $street || str_starts_with($pa, $street)) $errors[] = 'Objektzuordnung prüfen: '.$spec['name'].' (Bestand #'.$p['id'].').';
        }
        if (count($matches)>1) $errors[] = 'Mehrdeutiges Objekt: '.$spec['name'];
        $p = count($matches)===1 ? $matches[0] : null;
        if ($p && $p['seed_key'] !== null && $p['seed_key'] !== $spec['key']) $errors[] = 'Abweichende Importidentität bei '.$spec['name'];
        $existing = array_values(array_filter($jobs, static fn(array $j): bool => $p && (int)$j['property_id']===(int)$p['id']));
        $rules = [];
        foreach ($spec['rules'] as $rule) {
            $found = array_values(array_filter($existing, static fn(array $j): bool => $j['seed_key'] === $rule['key'] || gszNormalize($j['title']) === gszNormalize($rule['title'])));
            // A key attached to a different property must never silently be reused.
            foreach ($jobs as $j) if ($j['seed_key'] === $rule['key'] && (!$p || (int)$j['property_id'] !== (int)$p['id'])) $errors[] = 'Regelidentität am falschen Objekt: '.$rule['key'];
            if (count($found)>1) $errors[] = 'Mehrdeutige Leistung: '.$spec['name'].' / '.$rule['title'];
            $match = count($found)===1 ? $found[0] : null;
            if ($match && $match['seed_key'] !== null && $match['seed_key'] !== $rule['key']) $errors[] = 'Regelidentität kollidiert: '.$rule['key'];
            $rules[] = ['spec'=>$rule,'match'=>$match,'needs_review'=>!$match && count($existing)>0];
        }
        $rows[] = ['spec'=>$spec,'match'=>$p,'existing_rules'=>$existing,'rules'=>$rules];
    }
    return ['rows'=>$rows,'errors'=>array_values(array_unique($errors)), 'fingerprint'=>hash('sha256',json_encode([$start,$properties,$jobs],JSON_THROW_ON_ERROR))];
}
function gszApplyImport(PDO $pdo, string $start, string $fingerprint, array $choices=[]): array {
    // All imports serialize even before new property rows exist.
    if ((int)$pdo->query("SELECT GET_LOCK('gsz_phase1_seed', 5)")->fetchColumn() !== 1) throw new RuntimeException('Import läuft bereits.');
    try {
        $pdo->beginTransaction();
        $pdo->query('SELECT id FROM properties ORDER BY id FOR UPDATE')->fetchAll();
        $pdo->query('SELECT id FROM recurring_jobs ORDER BY id FOR UPDATE')->fetchAll();
        $preview = gszImportPreview($pdo,$start);
        if (!hash_equals($preview['fingerprint'],$fingerprint)) throw new RuntimeException('Daten wurden seit der Vorschau geändert. Vorschau neu laden.');
        if ($preview['errors']) throw new RuntimeException(implode(' ', $preview['errors']));
        $result = ['properties_created'=>0,'rules_created'=>0,'properties_reused'=>0,'rules_reused'=>0]; $used=[];
        foreach ($preview['rows'] as $row) {
            $spec=$row['spec']; $p=$row['match'];
            if (!$p) {
                $pdo->prepare('INSERT INTO properties(name,address,notes,seed_key) VALUES(?,?,?,?)')->execute([$spec['name'],$spec['address'],$spec['notes'],$spec['key']]);
                $propertyId=(int)$pdo->lastInsertId(); $result['properties_created']++;
            } else {
                $propertyId=(int)$p['id'];
                $pdo->prepare('UPDATE properties SET seed_key=? WHERE id=? AND seed_key IS NULL')->execute([$spec['key'],$propertyId]);
                $result['properties_reused']++;
            }
            foreach ($row['rules'] as $r) {
                $rule=$r['spec']; $match=$r['match'];
                if ($r['needs_review']) {
                    $choice=(string)($choices[$rule['key']]??'');
                    if ($choice==='') throw new RuntimeException('Bestehende Leistungen zuordnen oder neue Anlage bewusst auswählen.');
                    if ($choice!=='new') {
                        $valid=array_values(array_filter($row['existing_rules'], static fn(array $j): bool => (string)$j['id']===$choice && ($j['seed_key']===null || $j['seed_key']===$rule['key'])));
                        if(count($valid)!==1) throw new RuntimeException('Ungültige Leistungszuordnung.');
                        $match=$valid[0];
                    }
                }
                if ($match) {
                    if(isset($used[$match['id']])) throw new RuntimeException('Eine Bestandsleistung wurde mehrfach zugeordnet.');
                    $used[$match['id']]=true;
                    $pdo->prepare('UPDATE recurring_jobs SET seed_key=? WHERE id=? AND seed_key IS NULL')->execute([$rule['key'],$match['id']]);
                    $result['rules_reused']++;
                } else {
                    $pdo->prepare('INSERT INTO recurring_jobs(property_id,title,frequency,notes,active,next_due_date,seed_key,duration_minutes,anchor_day,season_start_month,season_end_month,rule_source) VALUES(?,?,?,?,?,?,?,?,?,?,?,?)')->execute([$propertyId,$rule['title'],$rule['frequency'],$rule['notes'],$rule['active'],$rule['next_due_date'],$rule['key'],$rule['duration_minutes'],$rule['next_due_date']?(int)substr($rule['next_due_date'],8,2):null,$rule['season_start_month']??null,$rule['season_end_month']??null,$rule['rule_source']]);
                    $result['rules_created']++;
                }
            }
        }
        $pdo->commit(); return $result;
    } catch (Throwable $e) { if ($pdo->inTransaction()) $pdo->rollBack(); throw $e; }
    finally { $pdo->query("SELECT RELEASE_LOCK('gsz_phase1_seed')"); }
}
