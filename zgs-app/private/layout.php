<?php
declare(strict_types=1);
/** Gemeinsamer Rahmen für alle angemeldeten HTML-Seiten. Downloads, JSON, PDFs und die Anmeldung werden nie umhüllt. */
const GSZ_NAV=[
 ['heute','Heute','index.php'],['woche','Woche','planner.php?view=week'],['objekte','Objekte','objects.php'],
 ['immobilien','Immobilien','estate_bank.php'],['mail','Mail','mail.php'],['mehr','Mehr','more.php'],
];
/** Welche Seite gehört zu welchem Hauptpunkt. Alles nicht Genannte liegt unter „Mehr“. */
function gszNavSection(string $page):string {
 return match(true){
  in_array($page,['index.php','notes.php'],true)=>'heute',
  in_array($page,['planner.php','route.php','appointments.php','task.php'],true)=>'woche',
  in_array($page,['objects.php','property.php','waste_setup.php','object_import.php'],true)=>'objekte',
  str_starts_with($page,'estate')=>'immobilien',
  in_array($page,['mail.php','compose.php'],true)=>'mail',
  default=>'mehr'};
}
function gszNavIcon(string $name):string {
 $paths=[
  'heute'=>'M12 3v2 M12 19v2 M4.2 4.2l1.4 1.4 M18.4 18.4l1.4 1.4 M3 12h2 M19 12h2 M4.2 19.8l1.4-1.4 M18.4 5.6l1.4-1.4 M16 12a4 4 0 1 1-8 0 4 4 0 0 1 8 0z',
  'woche'=>'M8 3v4 M16 3v4 M3 10h18 M5 5h14a2 2 0 0 1 2 2v12a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2V7a2 2 0 0 1 2-2z M7 14h2 M11 14h2 M15 14h2 M7 17h2 M11 17h2',
  'objekte'=>'M3 21h18 M5 21V6l7-3v18 M12 9h7v12 M8 9h1 M8 13h1 M8 17h1 M15 13h1 M15 17h1',
  'immobilien'=>'M3 11l9-7 9 7 M5 10v10h14V10 M10 20v-5h4v5',
  'mail'=>'M3 6h18v12H3z M3 7l9 6 9-6',
  'mehr'=>'M5 12h.01 M12 12h.01 M19 12h.01',
  'check'=>'M5 12.5l4.5 4.5L19 7.5',
 ];
 $w=$name==='mehr'?'3':'1.7';return '<svg aria-hidden="true" focusable="false" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="'.$w.'" stroke-linecap="round" stroke-linejoin="round"><path d="'.$paths[$name].'"/></svg>';
}
/** Kleine Zähler nur dort, wo sie eine Entscheidung auslösen (ungelesene Mails). */
/** Datenbankverbindung für den Seitenrahmen. Der Rahmen wird erst beim Seitenende gebaut, wenn globale Variablen schon aufgeräumt sind. */
function gszLayoutPdo(?PDO $set=null):?PDO {static $pdo=null;if($set)$pdo=$set;return $pdo;}
function gszNavBadges():array {
 $pdo=gszLayoutPdo();$out=[];if(!$pdo instanceof PDO)return $out;
 try{$n=(int)$pdo->query("SELECT COUNT(*) FROM mail_messages m JOIN mail_folders f ON f.id=m.folder_id WHERE m.active=1 AND m.seen=0 AND f.name='INBOX'")->fetchColumn();if($n)$out['mail']=$n;}catch(Throwable $e){}
 return $out;
}
function gszLayoutDocument(string $html,string $page,string $csrf,?array $responseHeaders=null):string {
 foreach($responseHeaders??headers_list() as $header){if(stripos($header,'Content-Disposition:')===0)return $html;if(preg_match('~^Content-Type:\s*([^;]+)~i',$header,$type)&&!in_array(strtolower(trim($type[1])),['text/html','application/xhtml+xml'],true))return $html;}
 if(!preg_match('~<html\b~i',$html)||!preg_match('~<main\b[^>]*>(.*)</main>~is',$html,$match))return $html;
 preg_match('~<title>(.*?)</title>~is',$html,$t);$title=trim(preg_replace('/\s*·\s*GSZ.*$/u','',html_entity_decode(strip_tags($t[1]??'GSZ-Zentrale'),ENT_QUOTES,'UTF-8')))?:'GSZ-Zentrale';
 $content=preg_replace('~<header\b[^>]*>.*?</header>~is','',$match[1],1)??$match[1];
 $section=gszNavSection($page);$badges=gszNavBadges();$side='';$tabs='';
 foreach(GSZ_NAV as [$key,$label,$url]){$cur=$key===$section?' aria-current="page"':'';$badge=isset($badges[$key])?'<span class="badge" aria-label="'.(int)$badges[$key].' neu">'.(int)$badges[$key].'</span>':'';
  $side.='<a href="'.e($url).'"'.$cur.'>'.gszNavIcon($key).'<span>'.e($label).'</span>'.$badge.'</a>';$tabs.='<a href="'.e($url).'"'.$cur.'>'.gszNavIcon($key).'<span>'.e($label).'</span>'.$badge.'</a>';}
 $banner='';if(!empty($_SESSION['planning_proposal_token'])&&$page==='index.php')$banner='<p class="notice flash"><a href="planner.php?view=week&amp;proposal='.e($_SESSION['planning_proposal_token']).'">Neuer Wochenvorschlag bereit – ansehen</a></p>';
 // Meldungen, die eine Seite nicht selbst gezeigt hat (z. B. nach „+ Notieren“ von einer beliebigen Seite)
 foreach(['app_error'=>'error','app_notice'=>'notice'] as $k=>$cls)if(isset($_SESSION[$k])){$banner.='<p class="'.$cls.' flash" role="'.($cls==='error'?'alert':'status').'">'.e((string)$_SESSION[$k]).'</p>';unset($_SESSION[$k]);}
 $quick=gszLayoutQuickNote($page,$csrf);
 $logout='<form class="logout" method="post" action="index.php"><input type="hidden" name="csrf" value="'.e($csrf).'"><input type="hidden" name="action" value="logout"><button class="logout-button">Abmelden</button></form>';
 return '<!doctype html><html lang="de"><head><meta charset="utf-8"><meta name="viewport" content="width=device-width,initial-scale=1,viewport-fit=cover"><meta name="robots" content="noindex,nofollow"><meta name="theme-color" content="#ffffff"><meta name="apple-mobile-web-app-capable" content="yes"><meta name="apple-mobile-web-app-title" content="GSZ"><title>'.e($title).' · GSZ</title><link rel="icon" href="assets/favicon-32.png"><link rel="apple-touch-icon" href="assets/apple-touch-icon.png"><link rel="stylesheet" href="zentrale.css?v=391"><script src="zentrale.js?v=391" defer></script></head><body>'
  .'<div class="shell"><aside class="side" aria-label="Hauptnavigation"><a class="side-brand" href="index.php" aria-label="Zur Startseite"><img src="assets/gsz-logo-horizontal.webp" alt="Gebäudeservice Zentrum" width="200" height="59"></a><nav class="side-nav">'.$side.'</nav><div class="side-foot">'.$logout.'</div></aside>'
  .'<div class="main"><header class="top"><a class="top-mark" href="index.php" aria-label="Zur Startseite"><img src="assets/apple-touch-icon.png" alt="" width="34" height="34"></a><span class="top-title">'.e($title).'</span>'.($quick!==''?'<a class="top-add" href="notes.php#neu" data-quick-note aria-label="Schnell notieren"><svg aria-hidden="true" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.4" stroke-linecap="round"><path d="M12 5v14M5 12h14"/></svg>Notieren</a>':'').$logout.'</header><main class="content" id="inhalt">'.$banner.$content.'</main></div></div>'
  .$quick.'<nav class="tabbar" aria-label="Hauptnavigation">'.$tabs.'</nav></body></html>';
}
/** „+“ unten rechts: schnell ein To-do oder „Rechnung offen“ notieren, ohne die Seite zu verlassen (Dialog; ohne JavaScript Link zur Merkliste). */
function gszLayoutQuickNote(string $page,string $csrf):string {
 $pdo=gszLayoutPdo();if(!$pdo instanceof PDO||in_array($page,['update.php','setup.php'],true))return '';
 try{require_once __DIR__.'/notes_view.php';$uri=basename((string)parse_url((string)($_SERVER['REQUEST_URI']??''),PHP_URL_PATH));$qs=(string)parse_url((string)($_SERVER['REQUEST_URI']??''),PHP_URL_QUERY);$return=gszNoteReturn($uri.($qs!==''?'?'.$qs:''));
  $propertyId=$page==='property.php'&&ctype_digit((string)($_GET['id']??''))?(int)$_GET['id']:null;
  return '<a class="fab" href="notes.php#neu" data-quick-note aria-label="Schnell notieren" title="Schnell notieren"><svg aria-hidden="true" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round"><path d="M12 5v14M5 12h14"/></svg></a>'
   .'<dialog class="quick-dialog" id="quick-note" aria-labelledby="quick-note-title"><div class="quick-head"><h2 id="quick-note-title">Schnell notieren</h2><button type="button" class="link" data-close aria-label="Schließen">Schließen</button></div><div class="quick-body"></div><p class="hint"><a href="notes.php">Zur Merkliste</a></p></dialog>'
   .'<template id="quick-note-form">'.gszNoteQuickForm($pdo,$csrf,$return,'todo',$propertyId,'qd').'</template>';
 }catch(Throwable $e){return '';}
}
