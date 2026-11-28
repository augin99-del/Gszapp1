<?php
declare(strict_types=1);
// Ein Vertrag: Daten bearbeiten, Vorschau des Vertragstexts, Status (versendet, unterschrieben, gekündigt), PDF.
require __DIR__.'/bootstrap.php';if(empty($_SESSION['admin_id']))redirect('index.php');
require_once dirname(__DIR__).'/private/ui.php';require_once dirname(__DIR__).'/private/contracts.php';
$admin=(int)$_SESSION['admin_id'];
if(!gszContractsReady($pdo)){gszPageStart('Verträge');echo '<div class="empty"><strong>Update nötig.</strong>Verträge gibt es ab Stand 450 – bitte unter Einstellungen → Update aktualisieren.</div>';gszPageEnd();exit;}
if($_SERVER['REQUEST_METHOD']==='POST'){checkCsrf();$action=(string)($_POST['action']??'');$id=(int)($_POST['id']??0);$go='vertrag.php?id='.$id;
 try{
  if($action==='create'){$id=gszContractCreate($pdo,$admin,(int)($_POST['property_id']??0));$_SESSION['app_notice']='Vertragsentwurf angelegt.';$go='vertrag.php?id='.$id;}
  elseif($action==='save'){gszContractSave($pdo,$admin,$id,$_POST);$_SESSION['app_notice']='Vertrag gespeichert.';}
  elseif($action==='refresh'){gszContractRefresh($pdo,$admin,$id);$_SESSION['app_notice']='Daten aus Objekt, Kunde und regelmäßigen Arbeiten übernommen.';}
  elseif($action==='status'){gszContractStatus($pdo,$admin,$id,(string)($_POST['to']??''),$_POST);$_SESSION['app_notice']=['versendet'=>'Als versendet markiert.','aktiv'=>'Vertrag ist aktiv – Fristen behält die Zentrale im Blick.','gekuendigt'=>'Kündigung eingetragen.','beendet'=>'Vertrag beendet.','entwurf'=>'Zurück im Entwurf.'][(string)$_POST['to']]??'Gespeichert.';}
  elseif($action==='mail'){require_once dirname(__DIR__).'/private/offers.php';$c=gszContractGet($pdo,$id)?:throw new RuntimeException('Vertrag nicht gefunden.');if($m=gszContractProblems($c))throw new RuntimeException('Es fehlt noch: '.implode(', ',$m).'.');
   $to='';if($c['customer_id']){$q=$pdo->prepare('SELECT email FROM customers WHERE id=?');$q->execute([(int)$c['customer_id']]);$to=(string)$q->fetchColumn();}if($to===''&&$c['property_id']){$q=$pdo->prepare('SELECT contact_email FROM properties WHERE id=?');$q->execute([(int)$c['property_id']]);$to=(string)$q->fetchColumn();}
   $hello=$c['customer_kind']==='consumer'?'Guten Tag '.$c['client_name'].',':'Sehr geehrte Damen und Herren,';
   $d=gszDocMailDraft($pdo,filter_var($to,FILTER_VALIDATE_EMAIL)?$to:'','Dienstleistungsvertrag '.gszContractNo($c).' – '.$c['object_name'],$hello."

im Anhang erhalten Sie den Dienstleistungsvertrag für das Objekt ".$c['object_name'].'. Bitte prüfen Sie ihn und senden Sie uns ein unterschriebenes Exemplar zurück (gern eingescannt per Mail).'."

Bei Fragen bin ich gern für Sie da: ".gszSetting($pdo,'company_phone').".

Mit freundlichen Grüßen
".gszSetting($pdo,'owner_name')."
".gszSetting($pdo,'company'),'Vertrag-'.gszContractNo($c).'.pdf',gszContractPdf($pdo,$c));
   gszAudit($pdo,$admin,'contract_mail_draft',['contract'=>$id,'draft'=>$d]);$_SESSION['app_notice']='Mail mit Vertrag im Anhang vorbereitet. Prüfen und senden – danach im Vertrag „Als versendet markieren“.';$go='compose.php?draft='.$d;}
  elseif($action==='delete'){$c=gszContractGet($pdo,$id);gszContractDelete($pdo,$admin,$id);$_SESSION['app_notice']='Entwurf gelöscht.';$go=$c&&$c['property_id']?'property.php?id='.(int)$c['property_id'].'#vertrag':'vertraege.php';}
  else throw new RuntimeException('Aktion unbekannt.');
 }catch(Throwable $e){$_SESSION['app_error']=$e instanceof InvalidArgumentException||$e instanceof RuntimeException?$e->getMessage():'Speichern fehlgeschlagen.';if($e instanceof PDOException)error_log('GSZ contract: '.$e->getMessage());}
 redirect($go);}
$c=gszContractGet($pdo,(int)($_GET['id']??0));
if(!$c){http_response_code(404);gszPageStart('Vertrag nicht gefunden');echo '<div class="empty"><strong>Diesen Vertrag gibt es nicht.</strong><a href="vertraege.php">Alle Verträge</a></div>';gszPageEnd();exit;}
$id=(int)$c['id'];[$sl,$sc]=GSZ_CONTRACT_STATUS[$c['status']];$miss=gszContractProblems($c);$per=gszContractPeriod($c);$draft=$c['status']==='entwurf';$consumer=$c['customer_kind']==='consumer';
$d=fn(?string $v)=>$v?(new DateTimeImmutable($v))->format('d.m.Y'):'';$f=fn(string $action)=>'<input type="hidden" name="csrf" value="'.e(csrf()).'"><input type="hidden" name="action" value="'.e($action).'"><input type="hidden" name="id" value="'.$id.'">';
gszPageStart('Vertrag '.gszContractNo($c),trim($c['object_name'].($c['client_name']!==''?' · '.$c['client_name']:'')));
foreach(['app_error'=>'error','app_notice'=>'notice'] as $k=>$cls)if(isset($_SESSION[$k])){echo '<p class="'.$cls.' flash" role="'.($cls==='error'?'alert':'status').'">'.e($_SESSION[$k]).'</p>';unset($_SESSION[$k]);}?>
<a class="back-link" href="<?=$c['property_id']?'property.php?id='.(int)$c['property_id'].'#vertrag':'vertraege.php'?>">‹ <?=$c['property_id']?'Zum Objekt':'Alle Verträge'?></a>
<div class="contract-head"><span class="pill <?=e($sc)?>"><?=e($sl)?></span><?php if((int)$c['auto_created']&&$draft):?><span class="muted">automatisch beim Anlegen des Objekts erstellt</span><?php endif?>
 <div class="estate-actions"><a class="btn primary" href="vertrag_pdf.php?id=<?=$id?>" target="_blank" rel="noopener">PDF ansehen</a><a class="btn" href="vertrag_pdf.php?id=<?=$id?>&amp;download=1">PDF herunterladen</a><?php if($draft):?><form method="post"><?=$f('mail')?><button class="btn"<?=$miss?' disabled':''?>>Per E-Mail senden</button></form><?php endif?></div></div>
<ol class="steps contract-steps" aria-label="Ablauf"><?php $order=['entwurf'=>1,'versendet'=>2,'aktiv'=>3,'gekuendigt'=>4,'beendet'=>4];$now=$order[$c['status']];foreach([1=>'Entwurf prüfen',2=>'Versendet',3=>'Unterschrieben',4=>'Gekündigt / beendet'] as $n=>$l):?><li class="<?=$n<$now?'done':($n===$now?'now':'')?>"><a href="#status"><b><?=$n?></b><?=e($l)?></a></li><?php endforeach?></ol>
<?php if($draft&&$miss):?><p class="error">Bevor der Vertrag raus kann, fehlt noch: <strong><?=e(implode(', ',$miss))?></strong>.</p><?php endif?>
<?php if($c['status']==='aktiv'&&$per['period_end']):?><p class="notice">Aktiv seit <?=e($d($c['signed_on']))?> · aktuelle Laufzeit bis <strong><?=e($d($per['period_end']))?></strong> · Kündigung möglich bis <strong><?=e($d($per['notice_by']))?></strong>. Die Zentrale erinnert dich 60 Tage vorher auf „Heute“.</p><?php elseif($c['status']==='aktiv'&&$per['open_ended']):?><p class="notice">Aktiv, unbefristet – kündbar mit 1 Monat Frist.</p><?php elseif($c['status']==='gekuendigt'):?><p class="notice">Gekündigt am <?=e($d($c['cancelled_on']))?> – endet am <strong><?=e($d($c['ends_on']))?></strong>.</p><?php endif?>

<form method="post" class="card contract-form" id="daten"><?=$f('save')?><input type="hidden" name="row_version" value="<?=(int)$c['row_version']?>">
<h2>Vertragsdaten</h2>
<fieldset class="kind"><legend>Der Auftraggeber ist …</legend><label class="radio"><input type="radio" name="customer_kind" value="business"<?=$consumer?'':' checked'?>> <span><strong>Unternehmer</strong><small>Hausverwaltung, WEG, Firma, Verein</small></span></label><label class="radio"><input type="radio" name="customer_kind" value="consumer"<?=$consumer?' checked':''?>> <span><strong>Verbraucher</strong><small>Privatperson – kürzere Fristen, Widerrufsbelehrung</small></span></label></fieldset>
<div class="form-grid"><label>Auftraggeber (Name)<input name="client_name" maxlength="190" required value="<?=e($c['client_name'])?>"></label><label>Anschrift des Auftraggebers<textarea name="client_address" maxlength="2000" rows="3" placeholder="Straße Nr.&#10;PLZ Ort"><?=e($c['client_address'])?></textarea></label>
<label>Objekt<input name="object_name" maxlength="190" required value="<?=e($c['object_name'])?>"></label><label>Objektanschrift<input name="object_address" maxlength="255" value="<?=e($c['object_address'])?>"></label></div>
<label>Leistungen (eine pro Zeile)<textarea name="services" maxlength="6000" rows="6" required><?=e((string)$c['services'])?></textarea></label>
<label class="check-label"><input type="checkbox" name="winter_service" value="1"<?=(int)$c['winter_service']?' checked':''?>> Winterdienst gehört dazu (eigener Abschnitt mit Räum- und Streupflicht)</label>
<div class="form-grid three"><label>Preis in €<input name="price" inputmode="decimal" placeholder="z. B. 180,00" value="<?=$c['price_cents']!==null?e(number_format((int)$c['price_cents']/100,2,',','')):''?>"></label><label>Abrechnung<select name="price_period"><?php foreach(GSZ_CONTRACT_PERIOD as $k=>$v):?><option value="<?=$k?>"<?=$c['price_period']===$k?' selected':''?>><?=e($v)?></option><?php endforeach?></select></label><label>Umsatzsteuer<select name="vat_mode"><?php foreach(['zzgl'=>'zzgl. USt (Unternehmer)','inkl'=>'inkl. USt (Verbraucher)','none'=>'keine USt (Kleinunternehmer § 19)'] as $k=>$v):?><option value="<?=$k?>"<?=$c['vat_mode']===$k?' selected':''?>><?=e($v)?></option><?php endforeach?></select></label></div>
<div class="form-grid four"><label>Beginn<input type="date" name="starts_on" value="<?=e((string)$c['starts_on'])?>"></label><label>Erstlaufzeit (Monate)<input type="number" name="term_months" min="1" max="<?=$consumer?24:60?>" value="<?=(int)$c['term_months']?>"></label><label>Verlängerung (Monate)<input type="number" name="renew_months" min="0" max="24" value="<?=(int)$c['renew_months']?>"<?=$consumer?' disabled':''?>></label><label>Kündigungsfrist (Monate)<input type="number" name="notice_months" min="1" max="12" value="<?=(int)$c['notice_months']?>"<?=$consumer?' disabled':''?>></label></div>
<?php if($consumer):?><p class="hint">Verbraucher: höchstens 24 Monate Erstlaufzeit, danach unbefristet und jederzeit mit 1 Monat kündbar (§ 309 Nr. 9 BGB). Das stellt die Zentrale automatisch so ein. Die Widerrufsbelehrung hängt am PDF.</p><?php else:?><p class="hint">0 Monate Verlängerung = Vertrag endet automatisch nach der Erstlaufzeit.</p><?php endif?>
<label>Besondere Vereinbarungen (optional, eine pro Zeile)<textarea name="special_terms" maxlength="6000" rows="3" placeholder="z. B. Reinigung der Fenster im Treppenhaus 2× jährlich inklusive"><?=e((string)$c['special_terms'])?></textarea></label>
<label>Interne Notiz (steht nicht im Vertrag)<textarea name="notes" maxlength="4000" rows="2"><?=e((string)$c['notes'])?></textarea></label>
<div class="estate-actions"><button class="primary">Speichern</button></div></form>
<?php if($draft&&$c['property_id']):?><form method="post" class="inline-form"><?=$f('refresh')?><button class="small">Daten aus Objekt, Kunde und regelmäßigen Arbeiten neu übernehmen</button></form><?php endif?>

<section class="card" id="status"><h2>Status</h2>
<?php if($draft):?><p>Prüfe den Vertrag im PDF, schicke ihn dem Kunden (per Mail oder ausgedruckt) und markiere ihn dann als versendet.</p><form method="post" class="status-form"><?=$f('status')?><input type="hidden" name="to" value="versendet"><label>Versendet am<input type="date" name="date" value="<?=date('Y-m-d')?>"></label><button class="primary"<?=$miss?' disabled':''?>>Als versendet markieren</button></form>
<form method="post" class="status-form"><?=$f('status')?><input type="hidden" name="to" value="aktiv"><label>Schon unterschrieben am<input type="date" name="date" value="<?=date('Y-m-d')?>"></label><button<?=$miss?' disabled':''?>>Direkt als unterschrieben markieren</button></form>
<?php elseif($c['status']==='versendet'):?><p>Versendet am <?=e($d($c['sent_on']))?>. Sobald er unterschrieben zurück ist:</p><form method="post" class="status-form"><?=$f('status')?><input type="hidden" name="to" value="aktiv"><label>Unterschrieben am<input type="date" name="date" value="<?=date('Y-m-d')?>"></label><button class="primary">Unterschrieben – Vertrag aktiv</button></form><form method="post" class="inline-form"><?=$f('status')?><input type="hidden" name="to" value="entwurf"><button class="small ghost">Zurück zum Entwurf (nochmal ändern)</button></form>
<?php elseif($c['status']==='aktiv'):?><p>Unterschrieben am <?=e($d($c['signed_on']))?>.</p><details><summary>Kündigung eintragen</summary><form method="post" class="status-form"><?=$f('status')?><input type="hidden" name="to" value="gekuendigt"><label>Kündigung erhalten/ausgesprochen am<input type="date" name="date" value="<?=date('Y-m-d')?>"></label><label>Vertragsende<input type="date" name="ends_on" value="<?=e($per['earliest_end'])?>"></label><button>Kündigung speichern</button></form><p class="hint">Vorschlag fürs Ende: frühestmöglicher Termin nach Vertrag. Bitte mit dem Kündigungsschreiben abgleichen.</p></details>
<?php elseif($c['status']==='gekuendigt'):?><form method="post" class="status-form"><?=$f('status')?><input type="hidden" name="to" value="beendet"><button>Vertrag ist beendet</button></form><?php endif?>
<?php if($draft):?><details class="danger-zone"><summary>Entwurf löschen</summary><form method="post"><?=$f('delete')?><button class="danger">Entwurf endgültig löschen</button></form></details><?php endif?></section>

<details class="card contract-preview"><summary>Vertragstext ansehen</summary><?php foreach(gszContractSections($pdo,$c) as [$h,$paras]):?><h3><?=e($h)?></h3><?php foreach($paras as $t):?><p><?=e($t)?></p><?php endforeach?><?php endforeach?></details>
<p class="hint">Die Vorlage ist ein Muster und keine Rechtsberatung. Lass sie einmal von einer Anwältin/einem Anwalt oder deiner Innung prüfen, bevor du sie verwendest – danach kannst du sie für alle Objekte nutzen.</p>
<?php gszPageEnd();
