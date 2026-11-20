<?php
declare(strict_types=1);
/** Explicit HTML route registry: never wrap downloads, JSON, PDFs or unauthenticated login. */
function gszModules():array {return [
 ['Heute / Dashboard','index.php'],['Tagesplanung','planner.php'],['Wochenplanung','planner.php?view=week'],['Aufgaben','index.php#aufgaben'],['Kunden','customers.php'],['Objekte','index.php#objekte'],['Termine','appointments.php'],['Regelaufgaben / Leistungen','recurring.php'],['Müllkalender','waste.php'],['Route','route.php'],['Arbeitsnachweise','worklogs.php'],['Zuordnungen','workers.php'],['E-Mail','mail.php'],['Dokumente','documents.php'],['Dienste einrichten','connections.php'],['Meine Immobilien','estate.php'],['Volksbank-Konten','estate_bank.php'],['Nebenkosten','estate_settlements.php'],['Einstellungen','settings.php'],['SEO-Monitor','seo.php'],['Geschütztes Update','update.php'],['Systemcheck','system_check.php']];}
function gszModuleGrid():string {$s='<section class="module-grid">';foreach(gszModules() as [$title,$url])$s.='<a class="surface module-link" href="'.e($url).'">'.e($title).'</a>';return $s.'</section>';}
function gszNavIcon(string $name): string
{
    $paths = [
        'dashboard' => 'M3 3h7v7H3z M14 3h7v7h-7z M3 14h7v7H3z M14 14h7v7h-7z',
        'today' => 'M8 3v4 M16 3v4 M3 10h18 M5 5h14a2 2 0 0 1 2 2v12a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2V7a2 2 0 0 1 2-2z M8 15l2 2 5-5',
        'planning' => 'M9 6h12 M9 12h12 M9 18h12 M3 6l1 1 2-2 M3 12l1 1 2-2 M3 18l1 1 2-2',
        'customers' => 'M16 21v-2a4 4 0 0 0-4-4H6a4 4 0 0 0-4 4v2 M16 3a4 4 0 0 1 0 8 M22 21v-2a4 4 0 0 0-3-4 M13 7a4 4 0 1 1-8 0 4 4 0 0 1 8 0z',
        'waste' => 'M3 6h18 M9 6V4h6v2 M6 6l1 14h10l1-14 M10 10v6 M14 10v6',
        'mail' => 'M3 5h18v14H3z M3 6l9 7 9-7',
        'invoices' => 'M6 3h9l4 4v14H6z M15 3v5h4 M9 12h7 M9 16h7',
        'accounting' => 'M5 3h14v18H5z M8 7h8 M8 12h1 M15 12h1 M8 16h1 M15 16h1',
        'documents' => 'M3 6h7l2 2h9v12H3z M3 10h18',
        'property' => 'M3 21h18 M5 21V6l9-3v18 M14 10h5v11 M8 9h2 M8 13h2 M8 17h2',
        'seo' => 'M3 3v18h18 M7 15l4-4 3 2 6-7 M16 6h4v4',
        'settings' => 'M4 7h16 M4 12h16 M4 17h16 M8 5v4 M16 10v4 M10 15v4',
    ];
    return '<svg class="nav-icon" aria-hidden="true" focusable="false" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.6" stroke-linecap="round" stroke-linejoin="round"><path d="'.$paths[$name].'"/></svg>';
}

function gszLayoutDocument(string $html,string $page,string $csrf,?array $responseHeaders=null):string {
 foreach($responseHeaders??headers_list() as $header){if(stripos($header,'Content-Disposition:')===0)return $html;if(preg_match('~^Content-Type:\s*([^;]+)~i',$header,$type)&&!in_array(strtolower(trim($type[1])),['text/html','application/xhtml+xml'],true))return $html;}
 if(!preg_match('~<html\b~i',$html)||!preg_match('~<main\b[^>]*>(.*)</main>~is',$html,$match))return $html;
 preg_match('~<title>(.*?)</title>~is',$html,$title);$title=html_entity_decode(strip_tags($title[1]??'GSZ-Zentrale'),ENT_QUOTES,'UTF-8');$content=$match[1];$content=preg_replace('~<header\b[^>]*>.*?</header>~is','',$content,1);
 $styles='';preg_match_all('~<(?:link\b[^>]*rel=["\']stylesheet["\'][^>]*>|style\b[^>]*>.*?</style>)~is',$html,$matches);foreach($matches[0] as $style)if(!str_contains($style,'gsz-dashboard.css'))$styles.=$style;
 $groups = [
    ['dashboard', 'Dashboard', 'index.php', []],
    ['today', 'Mein Tag', 'planner.php', []],
    ['planning', 'Aufgaben und Planung', 'planner.php?view=week', [
        ['Aufgaben', 'index.php#aufgaben'], ['Regelaufgaben', 'recurring.php'],
        ['Termine', 'appointments.php'], ['Route', 'route.php'],
        ['Zuordnungen', 'workers.php'], ['Nachweise', 'worklogs.php'],
    ]],
    ['customers', 'Kunden und Objekte', 'customers.php', [
        ['Objekte', 'index.php#objekte'], ['Objektimport', 'object_import.php'],
    ]],
    ['waste', 'Mülltonnenservice', 'waste.php', []],
    ['mail', 'E-Mail', 'mail.php', []],
    ['documents', 'Dokumente', 'documents.php', []],
    ['property', 'Meine Immobilien', 'estate.php', [
        ['Volksbank', 'estate_bank.php'], ['Nebenkosten', 'estate_settlements.php'],
    ]],
    ['seo', 'SEO-Monitor', 'seo.php', []],
    ['settings', 'Einstellungen und Systemstatus', 'settings.php', [
        ['Systemcheck', 'system_check.php'], ['Dienste einrichten', 'connections.php'],
        ['Geschütztes Bestandsupdate', 'update.php'],
    ]],
 ];
 $groupStartLabels = [
    'planning' => 'Wochenplanung', 'customers' => 'Kunden',
    'invoices' => 'Rechnungen', 'accounting' => 'Übersicht Buchhaltung',
    'property' => 'Immobilienübersicht', 'settings' => 'Einstellungen',
 ];
 $currentUrl = $page === 'planner.php' && ($_GET['view'] ?? '') === 'week' ? 'planner.php?view=week' : $page;
 $nav = '';
 foreach ($groups as [$icon, $label, $url, $children]) {
    $title = gszNavIcon($icon).'<span class="nav-label">'.e($label).'</span>';
    if (!$children) {
        $current = $url === $currentUrl || ($icon === 'waste' && $page === 'waste_setup.php');
        $nav .= '<a class="nav-link nav-main'.($current ? ' active" aria-current="page' : '').'" href="'.e($url).'">'.$title.'</a>';
        continue;
    }
    $children = [[$groupStartLabels[$icon], $url], ...$children];
    $active = in_array($currentUrl, array_column($children, 1), true) || ($icon === 'customers' && $page === 'property.php');
    $nav .= '<details class="nav-group'.($active ? ' active' : '').'"'.($active ? ' open' : '').'><summary class="nav-main">'.$title.'</summary>';
    foreach ($children as [$text, $href]) {
        $current = $href === $currentUrl;
        $nav .= '<a class="nav-link'.($current ? ' active" aria-current="page' : '').'" href="'.e($href).'">'.e($text).'</a>';
    }
    $nav .= '</details>';
 }
 $banner='';if(!empty($_SESSION['planning_proposal_token'])&&$page!=='planner.php')$banner='<p class="notice"><a href="planner.php?view=week&amp;proposal='.e($_SESSION['planning_proposal_token']).'">Aktualisierten Wochenvorschlag prüfen</a> – Änderungen erst nach Bestätigung.</p>';
 $heading=$page==='index.php'?'<span id="top-location">Heute</span>':'<span>'.e($title).'</span>';
 return '<!doctype html><html lang="de"><head><meta charset="utf-8"><meta name="viewport" content="width=device-width,initial-scale=1,viewport-fit=cover"><meta name="robots" content="noindex,nofollow"><title>'.e($title).'</title>'.$styles.'<link rel="stylesheet" href="gsz-dashboard.css?v=350"><link rel="stylesheet" href="app-unified.css?v=370-nav"></head><body><div class="app-shell"><aside class="sidebar"><a class="side-brand" href="index.php" aria-label="Zentrale"><img src="assets/gsz-logo-horizontal.webp" alt="Gebäudeservice Zentrum"></a><nav class="side-nav" aria-label="Alle Module">'.$nav.'</nav></aside><div class="workspace"><header class="topbar"><div class="top-left"><a href="index.php">GSZ</a>'.$heading.'</div><form class="logout" method="post" action="index.php"><input type="hidden" name="csrf" value="'.e($csrf).'"><input type="hidden" name="action" value="logout"><button class="logout-button">Abmelden</button></form></header><main class="content">'.$banner.$content.'</main></div></div><nav class="mobile-nav" aria-label="Mobile Navigation"><a href="index.php">Heute</a><a href="index.php#objekte">Objekte</a><a href="planner.php">Planung</a><a href="index.php#aufgaben">Aufgaben</a><a href="index.php#module">Alle Module</a></nav><script src="gsz-dashboard.js?v=350" defer></script></body></html>';
}
