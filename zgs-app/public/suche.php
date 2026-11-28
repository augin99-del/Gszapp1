<?php
declare(strict_types=1);
// Globale Suche (⌘K): JSON für das Such-Fenster, ohne JavaScript als normale Ergebnisseite.
require __DIR__.'/bootstrap.php';
$json=str_contains((string)($_SERVER['HTTP_ACCEPT']??''),'application/json');
if(empty($_SESSION['admin_id'])){if($json){http_response_code(403);header('Content-Type: application/json');echo '{"results":[]}';exit;}redirect('index.php');}
require_once dirname(__DIR__).'/private/shell.php';
$q=(string)($_GET['q']??'');$res=gszSearch($pdo,$q);
if($json){header('Content-Type: application/json; charset=utf-8');header('Cache-Control: no-store');echo json_encode(['q'=>$q,'results'=>$res],JSON_UNESCAPED_UNICODE|JSON_UNESCAPED_SLASHES);exit;}
require_once dirname(__DIR__).'/private/ui.php';gszPageStart('Suche');?>
<form class="search big-search" method="get" role="search"><label class="sr-only" for="sq">Suchen</label><input id="sq" type="search" name="q" value="<?=e($q)?>" placeholder="Objekt, Kunde, Anfrage, Aufgabe, Telefonnummer …" autofocus></form>
<?php $groups=[];foreach($res as $r)$groups[$r['group']][]=$r;if(!$groups):?><div class="empty"><strong>Nichts gefunden.</strong>Anders schreiben oder weniger Buchstaben versuchen.</div><?php endif;
foreach($groups as $g=>$items):?><h2 class="section-title"><?=e($g)?></h2><div class="list"><?php foreach($items as $r):?><a href="<?=e($r['url'])?>"><span class="name"><?=e($r['title'])?><?php if($r['sub']!==''):?><span class="sub"><?=e($r['sub'])?></span><?php endif?></span></a><?php endforeach?></div><?php endforeach;gszPageEnd();
