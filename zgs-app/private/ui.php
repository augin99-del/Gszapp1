<?php
declare(strict_types=1);
/** Einheitlicher Seitenkopf für einfache Seiten; der Rahmen (Navigation) kommt aus layout.php. */
function gszPageStart(string $title,string $sub=''):void {echo '<!doctype html><html lang="de"><head><meta charset="utf-8"><meta name="viewport" content="width=device-width,initial-scale=1"><meta name="robots" content="noindex,nofollow"><title>'.e($title).' · GSZ</title><link rel="stylesheet" href="zentrale.css?v=380"></head><body><main><div class="page-head"><div><h1>'.e($title).'</h1>'.($sub!==''?'<p class="sub">'.e($sub).'</p>':'').'</div></div>';if(isset($_SESSION['app_error'])){echo '<p class="error flash" role="alert">'.e($_SESSION['app_error']).'</p>';unset($_SESSION['app_error']);}if(isset($_SESSION['app_notice'])){echo '<p class="notice flash" role="status">'.e($_SESSION['app_notice']).'</p>';unset($_SESSION['app_notice']);}}
function gszPageEnd():void{echo '</main></body></html>';}
function gszFields(string $action):void{echo '<input type="hidden" name="csrf" value="'.e(csrf()).'"><input type="hidden" name="action" value="'.e($action).'">';}
function gszUiError(Throwable $e):void{$_SESSION['app_error']=$e instanceof PDOException?'Das hat nicht geklappt (Datenbank). Bitte später erneut versuchen oder unter Einstellungen den Systemstatus öffnen.':$e->getMessage();}
