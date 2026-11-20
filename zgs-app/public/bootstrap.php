<?php
declare(strict_types=1);
require_once dirname(__DIR__).'/private/http_security.php';
require_once dirname(__DIR__).'/private/config_loader.php';
try {
 $config=gszLoadConfig();
 if(filter_var($config['maintenance']??false,FILTER_VALIDATE_BOOLEAN)){http_response_code(503);exit('Wartungsmodus. Bitte später erneut versuchen.');}
 $pdo=gszConnectDatabase($config);
} catch (Throwable $e) { http_response_code(503);$problem=gszSafeError($e);echo '<!doctype html><html lang="de"><meta charset="utf-8"><meta name="viewport" content="width=device-width,initial-scale=1"><title>GSZ Einrichtung erforderlich</title><link rel="stylesheet" href="assets.css"><main><section class="card"><h1>GSZ-Zentrale starten</h1><p>'.htmlspecialchars($problem['message'],ENT_QUOTES|ENT_SUBSTITUTE,'UTF-8').'</p><p>Diagnose: '.htmlspecialchars($problem['code'],ENT_QUOTES,'UTF-8').'</p><p><a href="setup.php">Geschützte Einrichtung / Verbindung prüfen</a></p><p>Keine Zugangsdaten im Chat oder in öffentlichen Diagnose-Dateien eingeben. Anleitung: START-HIER.md aus dem Installationspaket.</p></section></main></html>';exit; }
require_once dirname(__DIR__).'/private/platform.php';
if(!empty($_SESSION['admin_id'])){
 $now=time();$idle=max(300,min(28800,(int)($config['session_idle_seconds']??1800)));
 if(gszSessionExpired($_SESSION,$now,$idle)){$_SESSION=[];session_regenerate_id(true);}
 else {$_SESSION['last_activity']=$now;$_SESSION['login_at']??=$now;}
}
require_once dirname(__DIR__).'/private/platform.php';
if(filter_var($config['audit_enabled']??true,FILTER_VALIDATE_BOOLEAN))gszRegisterRequestAudit($pdo);
function e(?string $s): string {return htmlspecialchars($s ?? '', ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');}
function csrf(): string { if(empty($_SESSION['csrf'])) $_SESSION['csrf']=bin2hex(random_bytes(32)); return $_SESSION['csrf']; }
function checkCsrf(): void { if(!hash_equals(csrf(), (string)($_POST['csrf'] ?? ''))) {http_response_code(403);exit('Ungueltige Anfrage.');} }
function redirect(string $url='index.php'): never {header('Location: '.$url, true, 303);exit;}

// Shared frame for authenticated HTML pages only; exports keep their exact bytes.
if(!empty($_SESSION['admin_id'])){
 require_once dirname(__DIR__).'/private/layout.php';$page=basename($_SERVER['SCRIPT_NAME']??'');
 $htmlPages=['index.php','planner.php','recurring.php','customers.php','property.php','appointments.php','task.php','object_import.php','workers.php','worklogs.php','worklog_photos.php','mail.php','compose.php','documents.php','invoices.php','invoice.php','offers.php','accounting.php','bank.php','receivables.php','finance_recurrences.php','cash_assets.php','estate.php','estate_bank.php','estate_settlements.php','route.php','waste.php','waste_setup.php','settings.php','connections.php','bank_connections.php','system_check.php','seo.php','update.php'];
 if(in_array($page,$htmlPages,true)){$layoutCsrf=csrf();ob_start(static fn(string $html):string=>empty($_SESSION['admin_id'])?$html:gszLayoutDocument($html,$page,$layoutCsrf));}
}
