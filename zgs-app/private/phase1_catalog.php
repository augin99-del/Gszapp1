<?php
declare(strict_types=1);
require_once __DIR__.'/scheduler.php';
function gszPhase1Catalog(string $start): array {
    if (!zgsValidDate($start)) throw new InvalidArgumentException('Ungültiger Starttermin.');
    $nextDay = static function(int $weekday) use ($start): string {
        $d = new DateTimeImmutable($start, new DateTimeZone('Europe/Berlin'));
        return $d->modify('+'.(($weekday - (int)$d->format('N') + 7) % 7).' days')->format('Y-m-d');
    };
    $specs = [
        ['froebel19','Fröbelstraße 19','Gütersloh',1,[['Treppenhaus reinigen','weekly',75],['Außenanlage pflegen','monthly',90],['Winterdienst','seasonal',null],['Kleinreparaturen','seasonal',null]]],
        ['fontaenen29','Fontänenstraße 29–31','Rheda-Wiedenbrück',1,[['Außenanlage pflegen','biweekly',120]]],
        ['warte8','Auf der Warte 8–10','Rheda-Wiedenbrück',2,[['Treppenhaus reinigen','weekly',75],['Fenster reinigen','quarterly',90],['Tiefgarage reinigen','quarterly',90],['Winterdienst','seasonal',null]]],
        ['lippstaedter5','Lippstädter Straße 5','Rheda-Wiedenbrück',2,[]],
        ['heiligen2','Heiligen Häuschenweg 2–4','Rheda-Wiedenbrück',4,[['Treppenhaus reinigen','weekly',75],['Außenanlage Mai–September','biweekly',120,5,9],['Außenanlage Oktober–April','monthly',120,10,4]]],
        ['diek3','Diekstraße 3','Gütersloh',7,[]],
        ['berliner45','Berliner Straße 45','Gütersloh',2,[]],
    ];
    $out = [];
    foreach ($specs as [$key,$street,$city,$weekday,$services]) {
        $rules = [];
        foreach ([['Tonnen rausstellen',$weekday],['Tonnen reinstellen',$weekday === 7 ? 1 : $weekday+1]] as $i => [$title,$day]) {
            $rules[] = ['key'=>$key.'-routine-'.$i,'title'=>$title,'frequency'=>'weekly','duration_minutes'=>15,'next_due_date'=>$nextDay($day),'active'=>0,'rule_source'=>'routine_unverified','notes'=>'Bisherige Arbeitsroutine, kein bestätigter Abfuhrkalender. Vor Aktivierung Fraktionen und tatsächliche Abfuhrdaten einschließlich Feiertagen abgleichen.'];
        }
        if ($key === 'fontaenen29') foreach ([['Gelbe Tonne rausstellen',3],['Gelbe Tonne reinstellen',4]] as $i => [$title,$day]) {
            $rules[] = ['key'=>$key.'-gelb-'.$i,'title'=>$title,'frequency'=>'weekly','duration_minutes'=>15,'next_due_date'=>$nextDay($day),'active'=>1,'rule_source'=>'special_weekly','notes'=>'Unabhängige wöchentliche Sonderregel laut Inhaber; kein Abfallkalenderimport.'];
        }
        foreach ($services as $i => $s) {
            $rules[] = ['key'=>$key.'-service-'.$i,'title'=>$s[0],'frequency'=>$s[1],'duration_minutes'=>$s[2],'next_due_date'=>$s[1]==='seasonal'?null:$start,'active'=>1,'rule_source'=>'manual','season_start_month'=>$s[3]??null,'season_end_month'=>$s[4]??null,'notes'=>$s[1]==='seasonal'?'Nur bei Bedarf manuell planen; keine Wetterautomatik.':($s[1]==='quarterly'?'Zeitspanne 60–120 Minuten; aktueller Planungsstandard 90, editierbar.':'Starttermin ist ein editierbarer Vorschlag.').(isset($s[3])?' Saisonfenster beachten; monatlicher Winterrhythmus ist ein Vorschlag.':'')];
        }
        $out[] = ['key'=>$key,'name'=>$street,'street'=>$street,'city'=>$city,'address'=>$street.', '.$city,'notes'=>$key==='lippstaedter5'?'Ort Rheda-Wiedenbrück am 09.11.2026 ausdrücklich vom Inhaber bestätigt.':'Stammdaten laut Masterauftrag; keine Kundenzuordnung erfunden.','rules'=>$rules];
    }
    return $out;
}
function gszNormalize(string $value): string {
    $value = mb_strtolower($value,'UTF-8');
    $value = str_replace(['ß','ä','ö','ü','–','—','‑','−'],['ss','ae','oe','ue','-','-','-','-'],$value);
    $value = preg_replace('/str\.(?=\s|\d|$)/u','strasse',$value) ?? $value;
    return preg_replace('/[^a-z0-9]/','',$value) ?? '';
}
