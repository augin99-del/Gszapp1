<?php
declare(strict_types=1);
require __DIR__.'/bootstrap.php';if(empty($_SESSION['admin_id']))redirect('index.php');require_once dirname(__DIR__).'/private/ui.php';
$groups=[
 'Arbeit'=>[['notes.php','Merkliste: To-dos, Einkauf, Erinnerungen'],['waste.php','Abfallkalender'],['recurring.php','Regelaufgaben'],['appointments.php','Termine'],['route.php','Route'],['worklogs.php','Arbeitsnachweise'],['workers.php','Helfer: Zuordnung und Helfer-App']],
 'Kunden und Unterlagen'=>[['leads.php','Anfragen (Website, Telefon, WhatsApp)'],['einsaetze.php','Schlüsseldienst: Einsätze und Umsatz'],['meldungen.php','Mieter-Meldungen (QR-Code im Haus)'],['haeuser.php','Häuser: QR-Meldung und Portal verwalten'],['customers.php','Kunden'],['vertraege.php','Verträge je Objekt (Entwurf, PDF, Fristen)'],['documents.php','Dokumente']],
 'Eigene Immobilien'=>[['projects.php','Bauprojekte (Sanierung)'],['estate.php','Häuser, Mieter und Verträge'],['estate_settlements.php','Nebenkostenabrechnung']],
 'Online'=>[['website.php','Website: Aufrufe, Kontakte, Preisrechner'],['leads.php?bewertung=1','Bewertungen: per WhatsApp anfragen, Karten drucken'],['seo.php','Google-Sichtbarkeit (SEO)']],
 'Einstellungen'=>[['benachrichtigungen.php','Benachrichtigungen, Glättewarnung, Wecker'],['settings.php','Einstellungen']],
];
gszPageStart('Mehr');foreach($groups as $title=>$links):?><h2 class="section-title"><?=e($title)?></h2><div class="list"><?php foreach($links as [$href,$label]):?><a href="<?=e($href)?>"><span class="name"><?=e($label)?></span></a><?php endforeach?></div><?php endforeach;gszPageEnd();
