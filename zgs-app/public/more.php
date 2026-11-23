<?php
declare(strict_types=1);
require __DIR__.'/bootstrap.php';if(empty($_SESSION['admin_id']))redirect('index.php');require_once dirname(__DIR__).'/private/ui.php';
$groups=[
 'Arbeit'=>[['notes.php','Merkliste: To-dos, Rechnungen offen, Einkauf'],['waste.php','Abfallkalender'],['recurring.php','Regelaufgaben'],['appointments.php','Termine'],['route.php','Route'],['worklogs.php','Arbeitsnachweise'],['workers.php','Helfer und Zuordnungen']],
 'Kunden und Unterlagen'=>[['customers.php','Kunden'],['documents.php','Dokumente']],
 'Eigene Immobilien'=>[['estate.php','Häuser, Mieter und Verträge'],['estate_settlements.php','Nebenkostenabrechnung']],
 'Online'=>[['seo.php','Google-Sichtbarkeit (SEO)']],
 'Einstellungen'=>[['settings.php','Einstellungen']],
];
gszPageStart('Mehr');foreach($groups as $title=>$links):?><h2 class="section-title"><?=e($title)?></h2><div class="list"><?php foreach($links as [$href,$label]):?><a href="<?=e($href)?>"><span class="name"><?=e($label)?></span></a><?php endforeach?></div><?php endforeach;gszPageEnd();
