<?php
declare(strict_types=1);
require_once dirname(__DIR__) . '/private/estate_calculation.php';

$assertions = 0;
function ec(bool $condition, string $label): void
{
    global $assertions;
    $assertions++;
    if (!$condition) throw new RuntimeException($label);
}
function er(callable $run, string $message, string $label): void
{
    try { $run(); } catch (InvalidArgumentException $e) {
        ec(str_contains($e->getMessage(), $message), $label . ': ' . $e->getMessage());
        return;
    }
    ec(false, $label . ': validation was not rejected');
}
function estateFixture(): array
{
    return ['schema_version' => 1, 'currency' => 'EUR', 'as_of' => '2025-03-01',
        'period' => ['from' => '2024-01-01', 'to' => '2024-12-31'],
        'units' => [['id' => 'a', 'area_units' => 6000], ['id' => 'b', 'area_units' => 4000], ['id' => 'c', 'area_units' => 5000]],
        'occupancies' => [
            ['id' => 'old', 'unit_id' => 'a', 'tenant_id' => 'tenant1', 'kind' => 'tenant', 'from' => '2023-06-01', 'to' => '2024-06-30'],
            ['id' => 'new', 'unit_id' => 'a', 'tenant_id' => 'tenant2', 'kind' => 'tenant', 'from' => '2024-08-01', 'to' => '2026-12-31'],
            ['id' => 'b', 'unit_id' => 'b', 'tenant_id' => 'tenant3', 'kind' => 'tenant', 'from' => '2024-01-01', 'to' => '2024-12-31'],
            ['id' => 'owner', 'unit_id' => 'c', 'kind' => 'owner', 'from' => '2024-01-01', 'to' => '2024-12-31'],
        ], 'costs' => [], 'advances' => []];
}
function estateCost(string $id, int $amount, string $method = 'area'): array
{
    return ['id' => $id, 'source_id' => 'invoice:' . $id . ':line1', 'category' => 'operating', 'amount_cents' => $amount,
        'from' => '2024-01-01', 'to' => '2024-12-31', 'apportionable' => true, 'legal_basis' => 'Synthetische vereinbarte Umlage',
        'allocation' => ['method' => $method]];
}
function estateAdvance(string $id, string $oid, int $amount, string $from, string $to): array
{
    return ['id' => $id, 'source_id' => 'bank:allocation:' . $id, 'occupancy_id' => $oid, 'amount_cents' => $amount,
        'status' => 'paid', 'component' => 'operating', 'paid_on' => '2024-10-03', 'allocation_from' => $from, 'allocation_to' => $to];
}
function estateStatements(array $out): array
{
    return array_column($out['tenant_statements'], null, 'occupancy_id');
}
function estateShares(array $line): array
{
    return array_column($line['shares'], 'amount_cents', 'recipient_key');
}
function estateConserved(array $out, string $label): void
{
    ec($out['totals']['cost_cents'] === $out['totals']['tenant_cost_cents'] + $out['totals']['owner_cents'], $label . ' building costs');
    ec($out['totals']['balance_cents'] === $out['totals']['tenant_cost_cents'] - $out['totals']['advance_cents'], $label . ' balances');
    foreach ($out['cost_lines'] as $line) {
        ec($line['amount_cents'] === $line['tenant_cents'] + $line['owner_cents'], $label . ' cost ' . $line['id']);
        if ($line['apportionable']) ec(array_sum(array_column($line['shares'], 'amount_cents')) + $line['co2_owner_cents'] === $line['amount_cents'], $label . ' shares ' . $line['id']);
    }
}

// Realistic complete building: leap year, July vacancy, tenant turnover, own apartment,
// paid partial advances and an actual advance refund. No contractual monthly advance inferred.
$in = estateFixture();
$in['costs'] = [estateCost('insurance', 183000), estateCost('water', 109800), estateCost('waste', 73200, 'unit'),
    array_replace(estateCost('repair', 99900), ['category' => 'maintenance', 'apportionable' => false]),
    array_replace(estateCost('management', 30000), ['category' => 'administration', 'apportionable' => false])];
$in['advances'] = [estateAdvance('p1', 'old', 140000, '2024-01-01', '2024-06-30'),
    estateAdvance('p2', 'new', 40000, '2024-08-01', '2024-12-31'),
    estateAdvance('p3', 'b', 60000, '2024-01-01', '2024-12-31'),
    estateAdvance('p4-refund', 'old', -10000, '2024-01-01', '2024-06-30')];
$out = estateCalculateSettlement($in);
$st = estateStatements($out);
ec($out['period']['days'] === 366, 'leap year is 366 inclusive local calendar days');
ec($out['totals']['cost_cents'] === 495900, 'all source costs retained');
ec($st['old']['cost_cents'] === 70373 && $st['new']['cost_cents'] === 59160 && $st['b']['cost_cents'] === 102480, 'full building expected tenant amounts');
ec($out['totals']['owner_cents'] === 263887 && $out['totals']['excluded_cents'] === 129900, 'owner retains vacancy, own occupancy, repair and administration');
ec($st['old']['advance_cents'] === 130000 && $st['old']['balance_cents'] === -59627, 'actual paid advances less refund, credit balance');
ec($st['new']['balance_cents'] === 19160 && $st['b']['balance_cents'] === 42480, 'underpayment balances');
ec($out['totals']['advance_cents'] === 230000 && $out['totals']['balance_cents'] === 2013, 'full building payment conservation');
ec($st['old']['components']['operating']['balance_cents'] === -59627, 'per-component statements');
estateConserved($out, 'realistic');
$reverse = $in;
foreach (['units', 'occupancies', 'costs', 'advances'] as $field) $reverse[$field] = array_reverse($reverse[$field]);
ec($out === estateCalculateSettlement($reverse), 'list ordering does not alter exact result');
ec(json_decode(json_encode($out, JSON_THROW_ON_ERROR), true, 512, JSON_THROW_ON_ERROR) === $out, 'output survives JSON round trip');
$vacancy = array_column($out['cost_lines'][0]['shares'], null, 'recipient_key')['vacancy:a'];
ec($vacancy['days'] === 31 && $vacancy['date_ranges'] === [['from' => '2024-07-01', 'to' => '2024-07-31']], 'exact uncovered vacancy dates');

// Same physical day cannot be allocated to both departing and arriving tenants.
$bad = $in; $bad['occupancies'][1]['from'] = '2024-06-30';
er(fn() => estateCalculateSettlement($bad), 'überlappende', 'inclusive turnover boundary overlap');
$bad = $in; $bad['occupancies'][1]['from'] = '2024-01-01'; $bad['occupancies'][1]['to'] = '2024-01-31';
er(fn() => estateCalculateSettlement($bad), 'überlappende', 'nested overlapping occupancy');
$bad = $in; $bad['occupancies'][1]['from'] = '2024-02-30';
er(fn() => estateCalculateSettlement($bad), 'ungültiger', 'invalid calendar day');

// Small arbitrary period crosses a year boundary; Feb 29 and DST are calendar days.
$short = estateFixture(); $short['costs'] = [estateCost('days', 300)];
$short['units'] = [['id' => 'u', 'area_units' => 1]];
$short['period'] = ['from' => '2023-12-31', 'to' => '2024-01-02'];
$short['occupancies'] = [['id' => 'x', 'unit_id' => 'u', 'tenant_id' => 't', 'kind' => 'tenant', 'from' => '2024-01-01', 'to' => '2024-01-02']];
$short['costs'][0]['from'] = '2023-12-31'; $short['costs'][0]['to'] = '2024-01-02';
$r = estateCalculateSettlement($short);
ec($r['period']['days'] === 3 && $r['totals']['tenant_cost_cents'] === 200 && $r['totals']['owner_cents'] === 100, 'year boundary with inclusive vacancy');
foreach ([['2024-02-28', '2024-03-01', 3], ['2024-03-30', '2024-04-01', 3], ['2024-10-26', '2024-10-28', 3], ['2023-05-01', '2024-04-30', 366]] as [$a, $b, $d]) {
    ec(estateCalcRange(['from' => $a, 'to' => $b], 'test')[2] === $d, 'calendar days ' . $a);
}
ec(estateCalcDate('2024-03-31', 'test')->getTimezone()->getName() === 'Europe/Berlin', 'timezone explicit independent of PHP default');

// One-cent residual uses stable ID order; credits reverse the exact distribution.
ec(estateCalcAllocate(1, ['b' => 1, 'a' => 1, 'c' => 1]) === ['a' => 1, 'b' => 0, 'c' => 0], 'one cent tie break');
ec(estateCalcAllocate(-2, ['c' => 1, 'b' => 1, 'a' => 1]) === ['a' => -1, 'b' => -1, 'c' => 0], 'negative credit symmetric');
ec(estateCalcAllocate(1, ['a' => 0, 'b' => 1]) === ['a' => 0, 'b' => 1], 'zero weight never receives residue');
$large = estateCalcAllocate(99999999999, ['a' => 3660000000000, 'b' => 3659999999999, 'c' => 1]);
ec(array_sum($large) === 99999999999 && $large['a'] === 50000000000 && $large['b'] === 49999999999 && $large['c'] === 0, 'overflow-safe product beyond PHP_INT_MAX');
for ($a = 0; $a <= 59; $a++) {
    [$q, $r] = estateCalcMulDiv($a, 17, 29);
    ec($q === intdiv($a * 17, 29) && $r === ($a * 17) % 29, 'exact rational arithmetic ' . $a);
}
er(fn() => estateCalcAllocate(1, ['a' => 0]), 'positive', 'empty consumption basis');
er(fn() => estateCalcAllocate(1, ['a' => PHP_INT_MAX]), 'Ganzzahl', 'overflow weight rejected');

$custom = estateFixture(); $custom['costs'] = [estateCost('custom', 36600, 'custom')];
$custom['costs'][0]['allocation'] += ['contract_basis' => 'Vertragsanlage Test', 'unit_weights' => ['a' => 2, 'b' => 1, 'c' => 1]];
$r = estateCalculateSettlement($custom);
ec(estateStatements($r)['old']['cost_cents'] === 9100 && estateStatements($r)['b']['cost_cents'] === 9150, 'contractual shares weighted by occupied inclusive days');
estateConserved($r, 'custom');
$bad = $custom; unset($bad['costs'][0]['allocation']['unit_weights']['c']);
er(fn() => estateCalculateSettlement($bad), 'jede Einheit', 'custom cannot remove owner unit denominator');
$bad = $custom; unset($bad['costs'][0]['allocation']['contract_basis']);
er(fn() => estateCalculateSettlement($bad), 'Grundlage', 'custom requires contractual basis');

// Explicit measured water consumption is never imputed to turnover days.
$measured = estateFixture(); $measured['costs'] = [estateCost('water', 12345, 'consumption')];
$weights = ['occupancy:old' => 100, 'occupancy:new' => 50, 'occupancy:b' => 75, 'occupancy:owner' => 25, 'vacancy:a' => 0];
$measured['costs'][0]['allocation'] += ['source_id' => 'meter-protocol:2024', 'validated' => true,
    'from' => '2024-01-01', 'to' => '2024-12-31', 'weights' => $weights];
$r = estateCalculateSettlement($measured);
ec(estateShares($r['cost_lines'][0]) === ['occupancy:b' => 3704, 'occupancy:new' => 2469, 'occupancy:old' => 4938, 'occupancy:owner' => 1234, 'vacancy:a' => 0], 'actual supplied consumption and largest remainders');
estateConserved($r, 'consumption');
$bad = $measured; unset($bad['costs'][0]['allocation']['weights']['vacancy:a']);
er(fn() => estateCalculateSettlement($bad), 'jeden', 'missing vacancy meter value rejected');
$bad = $measured; $bad['costs'][0]['allocation']['validated'] = false;
er(fn() => estateCalculateSettlement($bad), 'validiert', 'unvalidated readings rejected');
$bad = $measured; $bad['costs'][0]['allocation']['from'] = '2024-02-01';
er(fn() => estateCalculateSettlement($bad), 'Quellenzeitraum', 'meter period mismatch');

// ista/manual external amounts are consumed exactly, including retained owner CO2.
$heat = estateFixture(); $heat['costs'] = [array_replace(estateCost('ista', 300000, 'external'), ['category' => 'heating'])];
$heat['costs'][0]['co2'] = ['decision' => 'external', 'source_id' => 'external-co2:2024', 'approved' => true, 'reason' => 'Externe Testabrechnung Eigentümeranteil', 'owner_cents' => 10000];
$heat['costs'][0]['allocation'] += ['source_id' => 'ista:statement:2024', 'validated' => true, 'from' => '2024-01-01', 'to' => '2024-12-31',
    'amounts_cents' => ['occupancy:old' => 65000, 'occupancy:new' => 55000, 'occupancy:b' => 80000, 'occupancy:owner' => 85000, 'vacancy:a' => 5000]];
$heat['advances'] = [array_replace(estateAdvance('h1', 'old', 90000, '2024-01-01', '2024-06-30'), ['component' => 'heating', 'paid_on' => '2025-02-01'])];
$r = estateCalculateSettlement($heat);
ec($r['totals']['owner_cents'] === 100000 && $r['totals']['tenant_cost_cents'] === 200000, 'exact external heating source and owner CO2');
ec(estateStatements($r)['old']['components']['heating']['balance_cents'] === -25000, 'real later advance belongs to explicit settlement period');
ec(estateShares($r['cost_lines'][0])['occupancy:old'] === 65000, 'external per-tenant amount unchanged');
estateConserved($r, 'external heating');

// Departing tenant paid an operating flat rate; successor has settlement advances.
// Another tenant has inclusive heating. Both retained shares stay in the denominator.
$modes = $in;
$modes['costs'][] = $heat['costs'][0];
foreach ($modes['occupancies'] as &$o) $o['billing_modes'] = ['operating' => 'settlement', 'heating' => 'settlement'];
unset($o);
$modes['occupancies'][0]['billing_modes']['operating'] = 'owner';
$modes['occupancies'][2]['billing_modes']['heating'] = 'owner';
$modes['advances'] = [$in['advances'][1], $in['advances'][2], $heat['advances'][0]];
$r = estateCalculateSettlement($modes);
$ms = estateStatements($r);
ec($ms['old']['components']['operating']['cost_cents'] === 0 && $ms['old']['components']['heating']['cost_cents'] === 65000, 'old flat operating rate is retained, separate heating still settled');
ec($ms['new']['cost_cents'] === 114160 && $ms['new']['components']['operating']['cost_cents'] === 59160, 'successor denominator unchanged by predecessor flat rate');
ec($ms['b']['components']['heating']['cost_cents'] === 0 && $ms['b']['cost_cents'] === 102480, 'inclusive heating never charged again');
ec($r['totals']['tenant_cost_cents'] === 281640 && $r['totals']['owner_cents'] === 514260 && $r['totals']['balance_cents'] === 91640, 'flat/included building balance including turnover and vacancy');
$modeLines = array_column($r['cost_lines'], null, 'id');
$retained = array_column($modeLines['insurance']['shares'], null, 'recipient_key')['occupancy:old'];
ec($retained['kind'] === 'tenant' && $retained['charged_to'] === 'owner' && $retained['amount_cents'] === 36400, 'flat source share remains transparent as tenant occupancy retained by owner');
estateConserved($r, 'flat and inclusive');
$bad = $modes; $bad['advances'][] = $in['advances'][0];
er(fn() => estateCalculateSettlement($bad), 'Pauschal', 'flat rate payment cannot be mistaken for settlement advance');
$bad = $modes; $bad['occupancies'][0]['billing_modes']['operating'] = 'unconfirmed';
er(fn() => estateCalculateSettlement($bad), 'ungeklärte', 'unconfirmed contract mode blocks');
$bad = $modes; unset($bad['occupancies'][0]['billing_modes']['heating']);
er(fn() => estateCalculateSettlement($bad), 'ungeklärte', 'partial contract mode is ambiguous');

$bad = $heat; unset($bad['costs'][0]['co2']);
er(fn() => estateCalculateSettlement($bad), 'CO2', 'unresolved CO2 blocks calculation');
$bad = $heat; $bad['costs'][0]['co2']['approved'] = false;
er(fn() => estateCalculateSettlement($bad), 'bestätigte', 'CO2 external decision requires approval');
$bad = $heat; $bad['costs'][0]['allocation']['amounts_cents']['vacancy:a']++;
er(fn() => estateCalculateSettlement($bad), 'exakt ergeben', 'external heating total mismatch');
$bad = $heat; $bad['costs'][0]['allocation']['method'] = 'area';
er(fn() => estateCalculateSettlement($bad), 'Heizung', 'heating never invented area-only legal formula');

$split = $heat; $split['advances'] = [];
$split['costs'][0]['allocation'] = ['method' => 'heating_split', 'approved' => true, 'source_id' => 'approved-method:2024', 'fixed_bps' => 3000,
    'fixed_basis' => ['method' => 'area'], 'consumption' => ['source_id' => 'meter:heating:2024', 'validated' => true,
        'from' => '2024-01-01', 'to' => '2024-12-31', 'weights' => $weights]];
$r = estateCalculateSettlement($split);
ec($r['cost_lines'][0]['split']['fixed_cents'] === 87000 && $r['cost_lines'][0]['split']['consumption_cents'] === 203000, 'only explicitly approved split applied after external CO2');
ec($r['cost_lines'][0]['co2_owner_cents'] === 10000 && $r['cost_lines'][0]['split']['consumption_shares']['occupancy:old'] === 81200, 'split source computation inspectable');
estateConserved($r, 'approved heating split');
$bad = $split; unset($bad['costs'][0]['allocation']['approved']);
er(fn() => estateCalculateSettlement($bad), 'bestätigter', 'heating split approval missing');
$bad = $split; unset($bad['costs'][0]['allocation']['consumption']['weights']['occupancy:old']);
er(fn() => estateCalculateSettlement($bad), 'jeden', 'missing turnover consumption never guessed');

// Validation matrix: ambiguous money, duplicate originals, missing legal facts and payments.
$simple = estateFixture(); $simple['costs'] = [estateCost('simple', 10000)];
foreach ([
    ['source missing', static function (&$v) { unset($v['costs'][0]['source_id']); }, 'Grundlage'],
    ['legal basis missing', static function (&$v) { unset($v['costs'][0]['legal_basis']); }, 'Grundlage'],
    ['duplicate cost ID', static function (&$v) { $v['costs'][] = $v['costs'][0]; }, 'doppelte'],
    ['duplicate source', static function (&$v) { $v['costs'][] = array_replace($v['costs'][0], ['id' => 'other']); }, 'doppelte'],
    ['cost crosses settlement', static function (&$v) { $v['costs'][0]['from'] = '2023-12-31'; }, 'Abgrenzung'],
    ['cost period reversed', static function (&$v) { $v['costs'][0]['to'] = '2023-12-31'; }, 'vor Beginn'],
    ['maintenance cannot be billed', static function (&$v) { $v['costs'][0]['category'] = 'maintenance'; }, 'Instandhaltung'],
    ['administration cannot be billed', static function (&$v) { $v['costs'][0]['category'] = 'administration'; }, 'Verwaltung'],
    ['amount float forbidden', static function (&$v) { $v['costs'][0]['amount_cents'] = 1.1; }, 'Ganzzahl'],
    ['amount string forbidden', static function (&$v) { $v['costs'][0]['amount_cents'] = '100'; }, 'Ganzzahl'],
    ['amount limit', static function (&$v) { $v['costs'][0]['amount_cents'] = PHP_INT_MAX; }, 'Ganzzahl'],
    ['aggregate limit', static function (&$v) { $v['costs'][0]['amount_cents'] = ESTATE_AMOUNT_LIMIT; $v['costs'][] = estateCost('extra', 1); }, 'Ganzzahl'],
    ['unknown unit', static function (&$v) { $v['occupancies'][0]['unit_id'] = 'missing'; }, 'unbekannte'],
    ['missing area', static function (&$v) { unset($v['units'][1]['area_units']); }, 'Ganzzahl'],
    ['currency', static function (&$v) { $v['currency'] = 'USD'; }, 'currency'],
    ['as of before end', static function (&$v) { $v['as_of'] = '2024-10-01'; }, 'Stichtag'],
] as [$label, $change, $message]) {
    $bad = $simple; $change($bad);
    er(fn() => estateCalculateSettlement($bad), $message, $label);
}
$pay = $simple; $pay['advances'] = [estateAdvance('p', 'old', 20000, '2024-01-01', '2024-06-30')];
foreach ([
    ['planned advances', static function (&$v) { $v['advances'][0]['status'] = 'planned'; }, 'tatsächlich'],
    ['duplicate advance', static function (&$v) { $v['advances'][] = $v['advances'][0]; }, 'doppelte'],
    ['duplicate payment source', static function (&$v) { $v['advances'][] = array_replace($v['advances'][0], ['id' => 'q']); }, 'doppelte'],
    ['missing payment source', static function (&$v) { unset($v['advances'][0]['source_id']); }, 'Grundlage'],
    ['payment after asof', static function (&$v) { $v['advances'][0]['paid_on'] = '2026-01-01'; }, 'Stichtag'],
    ['payment outside attribution', static function (&$v) { $v['advances'][0]['allocation_to'] = '2024-07-01'; }, 'Zahlungszuordnung'],
    ['negative net payments', static function (&$v) { $v['advances'][0]['amount_cents'] = -1; }, 'Netto'],
    ['component reversal isolation', static function (&$v) { $v['advances'][] = array_replace($v['advances'][0], ['id' => 'refund', 'source_id' => 'unique-refund', 'amount_cents' => -1, 'component' => 'heating']); }, 'Netto'],
    ['unknown payment lease', static function (&$v) { $v['advances'][0]['occupancy_id'] = 'owner'; }, 'Mietverhältnis'],
] as [$label, $change, $message]) {
    $bad = $pay; $change($bad);
    er(fn() => estateCalculateSettlement($bad), $message, $label);
}
$credit = $simple; $credit['costs'][0]['amount_cents'] = -10000;
$r = estateCalculateSettlement($credit); estateConserved($r, 'supplier credit');
ec($r['totals']['tenant_cost_cents'] < 0 && $r['totals']['owner_cents'] < 0, 'supplier credit preserves owner portion');
$empty = estateFixture(); $empty['occupancies'] = []; $empty['costs'] = [estateCost('vacant', 10000)];
$r = estateCalculateSettlement($empty);
ec($r['tenant_statements'] === [] && $r['totals']['owner_cents'] === 10000, 'entire building vacant: owner carries all');
estateConserved($r, 'empty building');
// Real database IDs arrive as strings even though PHP coerces numeric map keys.
$numeric = $short;
$numeric['units'][0]['id'] = '1';
$numeric['occupancies'][0]['unit_id'] = '1';
$numeric['occupancies'][0]['id'] = '10';
$r = estateCalculateSettlement($numeric);
ec($r['tenant_statements'][0]['occupancy_id'] === '10' && $r['totals']['owner_cents'] === 100, 'numeric string database identities and vacancy ranges');
echo "Estate calculation tests passed ($assertions assertions).\n";
