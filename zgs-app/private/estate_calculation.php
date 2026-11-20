<?php
declare(strict_types=1);

/** Pure settlement arithmetic. No database, clock, network, HTML or legal inference. */
const ESTATE_AMOUNT_LIMIT = 99999999999;
const ESTATE_WEIGHT_LIMIT = 1000000000;

function estateCalcInvalid(string $path, string $reason): never
{
    throw new InvalidArgumentException($path . ': ' . $reason);
}

function estateCalcId(mixed $value, string $path): string
{
    if (!is_string($value) || !preg_match('/^[A-Za-z0-9_.-]{1,80}$/D', $value)) {
        estateCalcInvalid($path, 'stabile ID mit 1–80 ASCII-Zeichen erforderlich');
    }
    return $value;
}

function estateCalcText(mixed $value, string $path): string
{
    if (!is_string($value) || trim($value) === '' || strlen($value) > 2000 || str_contains($value, "\0")) {
        estateCalcInvalid($path, 'belegte, nicht leere Grundlage erforderlich (maximal 2000 Bytes)');
    }
    return $value;
}

function estateCalcInt(mixed $value, string $path, int $min, int $max): int
{
    if (!is_int($value) || $value < $min || $value > $max) {
        estateCalcInvalid($path, 'Ganzzahl außerhalb des zulässigen Bereichs');
    }
    return $value;
}

function estateCalcDate(mixed $value, string $path): DateTimeImmutable
{
    if (!is_string($value) || !preg_match('/^[1-9][0-9]{3}-[0-9]{2}-[0-9]{2}$/D', $value)) {
        estateCalcInvalid($path, 'ISO-Kalendertag erforderlich');
    }
    $date = DateTimeImmutable::createFromFormat('!Y-m-d', $value, new DateTimeZone('Europe/Berlin'));
    if (!$date || $date->format('Y-m-d') !== $value) {
        estateCalcInvalid($path, 'ungültiger Kalendertag');
    }
    return $date;
}

function estateCalcRange(array $row, string $path): array
{
    $from = estateCalcDate($row['from'] ?? null, $path . '.from');
    $to = estateCalcDate($row['to'] ?? null, $path . '.to');
    if ($from > $to) {
        estateCalcInvalid($path, 'Zeitraumende liegt vor Beginn');
    }
    return [$from->format('Y-m-d'), $to->format('Y-m-d'), (int)$from->diff($to)->days + 1];
}

function estateCalcList(mixed $value, string $path, int $limit): array
{
    if (!is_array($value) || !array_is_list($value) || count($value) > $limit) {
        estateCalcInvalid($path, 'Liste erforderlich, Limit ' . $limit);
    }
    foreach ($value as $row) {
        if (!is_array($row)) {
            estateCalcInvalid($path, 'Datensatz erforderlich');
        }
    }
    return $value;
}

function estateCalcAdd(int $a, int $b, string $path): int
{
    // Both operands are already bounded; their sum fits a 64-bit int before validation.
    return estateCalcInt($a + $b, $path, -ESTATE_AMOUNT_LIMIT, ESTATE_AMOUNT_LIMIT);
}

/** Exact a*b/d and remainder without forming a*b; 0 <= b <= d <= 4e15. */
function estateCalcMulDiv(int $a, int $b, int $d): array
{
    $bits = [];
    while ($a > 0) {
        $bits[] = $a % 2;
        $a = intdiv($a, 2);
    }
    $quotient = 0;
    $remainder = 0;
    foreach (array_reverse($bits) as $bit) {
        $next = $remainder * 2 + ($bit ? $b : 0);
        $quotient = $quotient * 2 + intdiv($next, $d);
        $remainder = $next % $d;
    }
    return [$quotient, $remainder];
}

/** Signed, conservative largest-remainder allocation; ties use binary recipient ID order. */
function estateCalcAllocate(int $amount, array $weights): array
{
    estateCalcInt($amount, 'allocation.amount', -ESTATE_AMOUNT_LIMIT, ESTATE_AMOUNT_LIMIT);
    $total = 0;
    foreach ($weights as $key => $weight) {
        if (!is_string($key)) estateCalcInvalid('allocation.weights', 'Textschlüssel erforderlich');
        $total += estateCalcInt($weight, 'allocation.weight', 0, 3660 * ESTATE_WEIGHT_LIMIT);
        if ($total > 4000000000000000) estateCalcInvalid('allocation.weights', 'Gewichtssumme überschreitet Grenze');
    }
    if ($total === 0) estateCalcInvalid('allocation.weights', 'positive belegte Verteilbasis erforderlich');
    ksort($weights, SORT_STRING);
    $result = [];
    $remainders = [];
    $allocated = 0;
    foreach ($weights as $key => $weight) {
        [$floor, $remainder] = estateCalcMulDiv(abs($amount), $weight, $total);
        $result[$key] = $floor;
        $remainders[] = ['key' => $key, 'remainder' => $remainder];
        $allocated += $floor;
    }
    usort($remainders, static fn(array $a, array $b): int => ($b['remainder'] <=> $a['remainder']) ?: strcmp($a['key'], $b['key']));
    $remaining = abs($amount) - $allocated;
    for ($i = 0; $i < $remaining; $i++) $result[$remainders[$i]['key']]++;
    if ($amount < 0) foreach ($result as &$value) $value = -$value;
    unset($value);
    return $result;
}

/** Every unit stays in the denominator; uncovered calendar days belong to its owner. */
function estateCalcRecipients(array $units, array $occupancies, string $from, string $to): array
{
    $days = estateCalcRange(['from' => $from, 'to' => $to], 'cost.period')[2];
    $used = array_fill_keys(array_keys($units), 0);
    $recipients = [];
    foreach ($occupancies as $o) {
        $a = max($from, $o['from']);
        $b = min($to, $o['to']);
        if ($a > $b) continue;
        $occupiedDays = estateCalcRange(['from' => $a, 'to' => $b], 'occupancy.overlap')[2];
        $used[$o['unit_id']] += $occupiedDays;
        $recipients['occupancy:' . $o['id']] = [
            'unit_id' => $o['unit_id'], 'occupancy_id' => $o['id'], 'kind' => $o['kind'],
            'tenant_id' => $o['tenant_id'] ?? null, 'from' => $a, 'to' => $b, 'days' => $occupiedDays,
            'billing_modes' => $o['billing_modes'] ?? ['operating' => 'settlement', 'heating' => 'settlement'],
        ];
    }
    foreach ($units as $id => $unit) {
        if ($used[$id] < $days) {
            $occupied = array_values(array_filter($recipients, static fn(array $r): bool => $r['unit_id'] === (string)$id));
            usort($occupied, static fn(array $a, array $b): int => strcmp($a['from'], $b['from']));
            $cursor = $from;
            $ranges = [];
            foreach ($occupied as $r) {
                if ($cursor < $r['from']) $ranges[] = ['from' => $cursor, 'to' => estateCalcDate($r['from'], 'vacancy')->modify('-1 day')->format('Y-m-d')];
                $cursor = $r['to'] === $to ? null : estateCalcDate($r['to'], 'vacancy')->modify('+1 day')->format('Y-m-d');
            }
            if ($cursor !== null && $cursor <= $to) $ranges[] = ['from' => $cursor, 'to' => $to];
            $recipients['vacancy:' . $id] = ['unit_id' => (string)$id, 'occupancy_id' => null,
                'kind' => 'vacancy', 'tenant_id' => null, 'from' => $ranges[0]['from'], 'to' => $ranges[count($ranges) - 1]['to'],
                'date_ranges' => $ranges, 'days' => $days - $used[$id]];
        }
    }
    ksort($recipients, SORT_STRING);
    return $recipients;
}

function estateCalcUnitWeights(array $basis, array $units, array $recipients, string $path): array
{
    $method = $basis['method'] ?? null;
    if (!in_array($method, ['area', 'unit', 'custom'], true)) estateCalcInvalid($path, 'area, unit oder custom erforderlich');
    if ($method === 'custom') {
        estateCalcText($basis['contract_basis'] ?? null, $path . '.contract_basis');
        $supplied = $basis['unit_weights'] ?? null;
        if (!is_array($supplied) || count($supplied) !== count($units)) estateCalcInvalid($path, 'Gewichte für jede Einheit erforderlich');
        foreach ($supplied as $id => $weight) if (!isset($units[$id])) estateCalcInvalid($path, 'unbekannte Einheit');
    }
    $weights = [];
    foreach ($recipients as $key => $recipient) {
        $id = $recipient['unit_id'];
        $weight = match ($method) {
            'area' => estateCalcInt($units[$id]['area_units'] ?? null, $path . '.area_units.' . $id, 1, ESTATE_WEIGHT_LIMIT),
            'unit' => 1,
            'custom' => estateCalcInt($supplied[$id] ?? null, $path . '.unit_weights.' . $id, 0, ESTATE_WEIGHT_LIMIT),
        };
        $weights[$key] = $weight * $recipient['days'];
    }
    return $weights;
}

/** Supplied consumption or exact external amounts must explicitly cover all recipients. */
function estateCalcExplicit(array $basis, array $recipients, array $cost, string $field, int $max): array
{
    $path = 'cost.' . $cost['id'] . '.allocation';
    estateCalcText($basis['source_id'] ?? null, $path . '.source_id');
    if (($basis['validated'] ?? null) !== true) estateCalcInvalid($path, 'extern geprüfte Werte müssen explizit validiert sein');
    if (($basis['from'] ?? null) !== $cost['from'] || ($basis['to'] ?? null) !== $cost['to']) {
        estateCalcInvalid($path, 'Quellenzeitraum muss Kostenzeitraum exakt entsprechen');
    }
    $values = $basis[$field] ?? null;
    if (!is_array($values) || count($values) !== count($recipients)) estateCalcInvalid($path, 'expliziter Wert für jeden Mieter-/Eigentümer-/Leerstandsanteil erforderlich');
    foreach ($values as $key => $value) if (!isset($recipients[$key])) estateCalcInvalid($path, 'unbekannter oder zeitlich unpassender Empfänger');
    $result = [];
    foreach ($recipients as $key => $recipient) $result[$key] = estateCalcInt($values[$key] ?? null, $path . '.' . $key, $field === 'amounts_cents' ? -$max : 0, $max);
    return $result;
}

/** See docs/archiv/ARCHITEKTUR_NEBENKOSTEN_KERN.md for the versioned JSON-compatible contract. */
function estateCalculateSettlement(array $input): array
{
    if (PHP_INT_SIZE < 8) estateCalcInvalid('runtime', '64-Bit-PHP erforderlich');
    if (($input['schema_version'] ?? null) !== 1 || ($input['currency'] ?? null) !== 'EUR') estateCalcInvalid('input', 'schema_version=1 und currency=EUR erforderlich');
    if (!is_array($input['period'] ?? null)) estateCalcInvalid('period', 'Abrechnungszeitraum erforderlich');
    [$from, $to, $days] = estateCalcRange($input['period'], 'period');
    if ($days > 3660) estateCalcInvalid('period', 'maximal 3660 inklusive Kalendertage');
    $asOf = estateCalcDate($input['as_of'] ?? null, 'as_of')->format('Y-m-d');
    if ($asOf < $to) estateCalcInvalid('as_of', 'Stichtag darf nicht vor Abrechnungsende liegen');
    $units = [];
    foreach (estateCalcList($input['units'] ?? null, 'units', 1000) as $unit) {
        $id = estateCalcId($unit['id'] ?? null, 'units.id');
        if (isset($units[$id])) estateCalcInvalid('units.id', 'doppelte Einheit');
        if (array_key_exists('area_units', $unit)) estateCalcInt($unit['area_units'], 'units.area_units', 1, ESTATE_WEIGHT_LIMIT);
        $units[$id] = $unit;
    }
    if (!$units) estateCalcInvalid('units', 'mindestens eine Einheit erforderlich');
    ksort($units, SORT_STRING);
    $occupancies = [];
    $byUnit = [];
    foreach (estateCalcList($input['occupancies'] ?? null, 'occupancies', 5000) as $o) {
        $id = estateCalcId($o['id'] ?? null, 'occupancies.id');
        $unitId = estateCalcId($o['unit_id'] ?? null, 'occupancies.unit_id');
        if (isset($occupancies[$id]) || !isset($units[$unitId])) estateCalcInvalid('occupancies', 'doppelte ID oder unbekannte Einheit');
        if (!in_array($o['kind'] ?? null, ['tenant', 'owner'], true)) estateCalcInvalid('occupancies.kind', 'tenant oder owner erforderlich');
        if ($o['kind'] === 'tenant') estateCalcId($o['tenant_id'] ?? null, 'occupancies.tenant_id');
        if (array_key_exists('billing_modes', $o)) {
            if (!is_array($o['billing_modes'])) estateCalcInvalid('occupancies.billing_modes', 'explizite Vertragsmodi erforderlich');
            foreach (['operating', 'heating'] as $component) {
                if (!in_array($o['billing_modes'][$component] ?? null, ['settlement', 'owner'], true)) estateCalcInvalid('occupancies.billing_modes.' . $component, 'settlement oder owner erforderlich; ungeklärte Verträge blockieren');
            }
        }
        estateCalcRange($o, 'occupancy.' . $id);
        $occupancies[$id] = $o;
        $byUnit[$unitId][] = $o;
    }
    foreach ($byUnit as $list) {
        usort($list, static fn(array $a, array $b): int => strcmp($a['from'], $b['from']));
        $end = null;
        foreach ($list as $o) {
            if ($end !== null && $o['from'] <= $end) estateCalcInvalid('occupancies', 'überlappende Belegung derselben Einheit (Grenztage inklusive)');
            $end = $o['to'];
        }
    }
    ksort($occupancies, SORT_STRING);
    $statements = [];
    foreach ($occupancies as $id => $o) {
        if ($o['kind'] === 'tenant' && $o['from'] <= $to && $o['to'] >= $from) {
            $statements[$id] = ['occupancy_id' => (string)$id, 'tenant_id' => $o['tenant_id'], 'unit_id' => $o['unit_id'],
                'from' => max($from, $o['from']), 'to' => min($to, $o['to']), 'cost_cents' => 0, 'advance_cents' => 0,
                'balance_cents' => 0, 'cost_items' => [], 'advance_ids' => [],
                'billing_modes' => $o['billing_modes'] ?? ['operating' => 'settlement', 'heating' => 'settlement'],
                'components' => ['operating' => ['cost_cents' => 0, 'advance_cents' => 0, 'balance_cents' => 0],
                    'heating' => ['cost_cents' => 0, 'advance_cents' => 0, 'balance_cents' => 0]]];
        }
    }
    $costs = [];
    $sourceIds = [];
    foreach (estateCalcList($input['costs'] ?? null, 'costs', 2000) as $cost) {
        $id = estateCalcId($cost['id'] ?? null, 'costs.id');
        $source = estateCalcText($cost['source_id'] ?? null, 'cost.' . $id . '.source_id');
        if (isset($costs[$id]) || isset($sourceIds[$source])) estateCalcInvalid('costs', 'doppelte Kosten-ID oder doppelte Quellenposition');
        $sourceIds[$source] = true;
        estateCalcRange($cost, 'cost.' . $id);
        if ($cost['from'] < $from || $cost['to'] > $to) estateCalcInvalid('cost.' . $id, 'Kostenzeitraum nicht vollständig im Abrechnungszeitraum; explizite Abgrenzung erforderlich');
        estateCalcInt($cost['amount_cents'] ?? null, 'cost.' . $id . '.amount_cents', -ESTATE_AMOUNT_LIMIT, ESTATE_AMOUNT_LIMIT);
        if (!in_array($cost['category'] ?? null, ['operating', 'heating', 'maintenance', 'administration', 'other_non_apportionable'], true)) estateCalcInvalid('cost.' . $id . '.category', 'unbekannte Kostenart');
        if (!is_bool($cost['apportionable'] ?? null)) estateCalcInvalid('cost.' . $id, 'Umlageentscheidung fehlt');
        if (in_array($cost['category'], ['maintenance', 'administration', 'other_non_apportionable'], true) && $cost['apportionable']) estateCalcInvalid('cost.' . $id, 'Instandhaltung/Verwaltung/nicht umlagefähige Positionen bleiben beim Eigentümer');
        $costs[$id] = $cost;
    }
    ksort($costs, SORT_STRING);
    $lines = [];
    $total = $owner = $excluded = $tenantTotal = 0;
    foreach ($costs as $id => $cost) {
        $amount = $cost['amount_cents'];
        $total = estateCalcAdd($total, $amount, 'totals.cost_cents');
        $line = ['id' => (string)$id, 'source_id' => $cost['source_id'], 'category' => $cost['category'], 'from' => $cost['from'], 'to' => $cost['to'],
            'amount_cents' => $amount, 'apportionable' => $cost['apportionable'], 'owner_cents' => 0, 'tenant_cents' => 0,
            'excluded_cents' => 0, 'co2_owner_cents' => 0, 'allocation' => null, 'shares' => []];
        if (!$cost['apportionable']) {
            $line['owner_cents'] = $line['excluded_cents'] = $amount;
            $excluded = estateCalcAdd($excluded, $amount, 'totals.excluded_cents');
        } else {
            estateCalcText($cost['legal_basis'] ?? null, 'cost.' . $id . '.legal_basis');
            $basis = $cost['allocation'] ?? null;
            if (!is_array($basis)) estateCalcInvalid('cost.' . $id, 'Verteilbasis fehlt');
            $method = $basis['method'] ?? null;
            $recipients = estateCalcRecipients($units, $occupancies, $cost['from'], $cost['to']);
            $distributable = $amount;
            if ($cost['category'] === 'heating') {
                if (!in_array($method, ['external', 'heating_split'], true)) estateCalcInvalid('cost.' . $id, 'Heizung erfordert externe Beträge oder belegten freigegebenen Verbrauchssplit');
                $co2 = $cost['co2'] ?? null;
                if (!is_array($co2) || !in_array($co2['decision'] ?? null, ['external', 'not_applicable'], true)) estateCalcInvalid('cost.' . $id . '.co2', 'ungeklärte CO2-Verteilung');
                estateCalcText($co2['source_id'] ?? null, 'co2.source_id');
                estateCalcText($co2['reason'] ?? null, 'co2.reason');
                if (($co2['approved'] ?? null) !== true) estateCalcInvalid('co2', 'explizit bestätigte externe Entscheidung erforderlich');
                $co2Owner = estateCalcInt($co2['owner_cents'] ?? null, 'co2.owner_cents', -ESTATE_AMOUNT_LIMIT, ESTATE_AMOUNT_LIMIT);
                if (($co2['decision'] === 'not_applicable' && $co2Owner !== 0) || abs($co2Owner) > abs($amount) || ($co2Owner !== 0 && ($co2Owner > 0) !== ($amount > 0))) estateCalcInvalid('co2.owner_cents', 'CO2-Eigentümeranteil unvereinbar mit Kosten');
                $line['co2_owner_cents'] = $co2Owner;
                $line['owner_cents'] = $co2Owner;
                $distributable -= $co2Owner;
                $line['co2'] = $co2;
            }
            $weights = [];
            if (in_array($method, ['area', 'unit', 'custom'], true)) {
                $weights = estateCalcUnitWeights($basis, $units, $recipients, 'cost.' . $id . '.allocation');
                $shares = estateCalcAllocate($distributable, $weights);
            } elseif ($method === 'consumption') {
                $weights = estateCalcExplicit($basis, $recipients, $cost, 'weights', ESTATE_WEIGHT_LIMIT);
                $shares = estateCalcAllocate($distributable, $weights);
            } elseif ($method === 'external') {
                $shares = estateCalcExplicit($basis, $recipients, $cost, 'amounts_cents', ESTATE_AMOUNT_LIMIT);
                $sum = 0;
                foreach ($shares as $share) {
                    if ($share !== 0 && ($share > 0) !== ($distributable > 0)) estateCalcInvalid('cost.' . $id, 'externe Anteile müssen das Vorzeichen der verteilbaren Kosten haben');
                    $sum = estateCalcAdd($sum, $share, 'external.sum');
                }
                if ($sum !== $distributable) estateCalcInvalid('cost.' . $id, 'externe Beträge müssen Kosten nach CO2-Eigentümeranteil exakt ergeben');
            } elseif ($method === 'heating_split' && $cost['category'] === 'heating') {
                if (($basis['approved'] ?? null) !== true) estateCalcInvalid('heating_split', 'fachlich bestätigter Split erforderlich');
                estateCalcText($basis['source_id'] ?? null, 'heating_split.source_id');
                $fixedBps = estateCalcInt($basis['fixed_bps'] ?? null, 'heating_split.fixed_bps', 0, 10000);
                if (!is_array($basis['fixed_basis'] ?? null) || !is_array($basis['consumption'] ?? null)) estateCalcInvalid('heating_split', 'Grund- und Verbrauchsgrundlage erforderlich');
                $fixedWeights = estateCalcUnitWeights($basis['fixed_basis'], $units, $recipients, 'heating_split.fixed_basis');
                $consumptionWeights = estateCalcExplicit($basis['consumption'], $recipients, $cost, 'weights', ESTATE_WEIGHT_LIMIT);
                $parts = estateCalcAllocate($distributable, ['fixed' => $fixedBps, 'consumption' => 10000 - $fixedBps]);
                $fixedShares = estateCalcAllocate($parts['fixed'], $fixedWeights);
                $consumptionShares = estateCalcAllocate($parts['consumption'], $consumptionWeights);
                $shares = [];
                foreach ($recipients as $key => $recipient) $shares[$key] = $fixedShares[$key] + $consumptionShares[$key];
                $line['split'] = ['fixed_cents' => $parts['fixed'], 'consumption_cents' => $parts['consumption'],
                    'fixed_weights' => $fixedWeights, 'consumption_weights' => $consumptionWeights,
                    'fixed_shares' => $fixedShares, 'consumption_shares' => $consumptionShares];
            } else {
                estateCalcInvalid('cost.' . $id . '.allocation', 'unbekannte oder unzulässige Methode');
            }
            $line['allocation'] = $basis;
            $line['legal_basis'] = $cost['legal_basis'];
            foreach ($recipients as $key => $recipient) {
                $share = $shares[$key];
                $component = $cost['category'] === 'heating' ? 'heating' : 'operating';
                $chargeTenant = $recipient['kind'] === 'tenant' && $recipient['billing_modes'][$component] === 'settlement';
                $line['shares'][] = ['recipient_key' => $key] + $recipient + ['weight' => $weights[$key] ?? null, 'amount_cents' => $share,
                    'charged_to' => $chargeTenant ? 'tenant' : 'owner'];
                if ($chargeTenant) {
                    $oid = $recipient['occupancy_id'];
                    $statements[$oid]['cost_cents'] = estateCalcAdd($statements[$oid]['cost_cents'], $share, 'tenant.cost_cents');
                    $statements[$oid]['components'][$component]['cost_cents'] = estateCalcAdd($statements[$oid]['components'][$component]['cost_cents'], $share, 'tenant.component.cost_cents');
                    $statements[$oid]['cost_items'][] = ['cost_id' => (string)$id, 'amount_cents' => $share];
                    $line['tenant_cents'] = estateCalcAdd($line['tenant_cents'], $share, 'line.tenant_cents');
                } else {
                    $line['owner_cents'] = estateCalcAdd($line['owner_cents'], $share, 'line.owner_cents');
                }
            }
        }
        $owner = estateCalcAdd($owner, $line['owner_cents'], 'totals.owner_cents');
        $tenantTotal = estateCalcAdd($tenantTotal, $line['tenant_cents'], 'totals.tenant_cost_cents');
        $lines[] = $line;
    }
    $advances = [];
    $advanceSources = [];
    foreach (estateCalcList($input['advances'] ?? null, 'advances', 10000) as $advance) {
        $id = estateCalcId($advance['id'] ?? null, 'advances.id');
        $source = estateCalcText($advance['source_id'] ?? null, 'advances.source_id');
        $oid = estateCalcId($advance['occupancy_id'] ?? null, 'advances.occupancy_id');
        if (isset($advances[$id]) || isset($advanceSources[$source])) estateCalcInvalid('advances', 'doppelte Vorauszahlung oder Quellenposition');
        if (!isset($statements[$oid])) estateCalcInvalid('advances', 'Vorauszahlung ohne passendes Mietverhältnis');
        if (($advance['status'] ?? null) !== 'paid') estateCalcInvalid('advances', 'nur tatsächlich bezahlte, belegte Vorauszahlungen zulässig');
        if (!in_array($advance['component'] ?? null, ['operating', 'heating'], true)) estateCalcInvalid('advances.component', 'operating oder heating erforderlich');
        if ($statements[$oid]['billing_modes'][$advance['component']] !== 'settlement') estateCalcInvalid('advances.component', 'Pauschal-/Inklusivzahlungen sind keine abzurechnenden Vorauszahlungen');
        $paidOn = estateCalcDate($advance['paid_on'] ?? null, 'advances.paid_on')->format('Y-m-d');
        if ($paidOn > $asOf) estateCalcInvalid('advances', 'Zahlung liegt nach dem Stichtag');
        [$af, $at] = estateCalcRange(['from' => $advance['allocation_from'] ?? null, 'to' => $advance['allocation_to'] ?? null], 'advances.allocation');
        if ($af < $statements[$oid]['from'] || $at > $statements[$oid]['to']) estateCalcInvalid('advances', 'Zahlungszuordnung muss vollständig im Miet-/Abrechnungszeitraum liegen');
        $value = estateCalcInt($advance['amount_cents'] ?? null, 'advances.amount_cents', -ESTATE_AMOUNT_LIMIT, ESTATE_AMOUNT_LIMIT);
        if ($value === 0) estateCalcInvalid('advances', 'Nullzahlung ist kein Zahlungsnachweis');
        $advances[$id] = $advance;
        $advanceSources[$source] = true;
    }
    ksort($advances, SORT_STRING);
    $advanceTotal = $balanceTotal = 0;
    foreach ($advances as $id => $advance) {
        $oid = $advance['occupancy_id'];
        $statements[$oid]['advance_cents'] = estateCalcAdd($statements[$oid]['advance_cents'], $advance['amount_cents'], 'tenant.advance_cents');
        $component = $advance['component'];
        $statements[$oid]['components'][$component]['advance_cents'] = estateCalcAdd($statements[$oid]['components'][$component]['advance_cents'], $advance['amount_cents'], 'tenant.component.advance_cents');
        $statements[$oid]['advance_ids'][] = (string)$id;
        $advanceTotal = estateCalcAdd($advanceTotal, $advance['amount_cents'], 'totals.advance_cents');
    }
    foreach ($statements as &$statement) {
        foreach ($statement['components'] as &$component) {
            if ($component['advance_cents'] < 0) estateCalcInvalid('advances', 'Netto-Vorauszahlungen je Mietverhältnis/Komponente dürfen nicht negativ sein');
            $component['balance_cents'] = estateCalcAdd($component['cost_cents'], -$component['advance_cents'], 'tenant.component.balance_cents');
        }
        unset($component);
        $statement['balance_cents'] = estateCalcAdd($statement['cost_cents'], -$statement['advance_cents'], 'tenant.balance_cents');
        $balanceTotal = estateCalcAdd($balanceTotal, $statement['balance_cents'], 'totals.balance_cents');
    }
    unset($statement);
    return ['schema_version' => 1, 'currency' => 'EUR', 'timezone' => 'Europe/Berlin', 'period' => ['from' => $from, 'to' => $to, 'days' => $days],
        'as_of' => $asOf, 'cost_lines' => $lines, 'tenant_statements' => array_values($statements), 'advances' => array_values($advances),
        'totals' => ['cost_cents' => $total, 'tenant_cost_cents' => $tenantTotal, 'owner_cents' => $owner,
            'excluded_cents' => $excluded, 'advance_cents' => $advanceTotal, 'balance_cents' => $balanceTotal],
        'rounding' => 'largest_remainder_then_binary_recipient_id'];
}
