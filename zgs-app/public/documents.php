<?php
declare(strict_types=1);
require __DIR__.'/bootstrap.php';
if (empty($_SESSION['admin_id'])) redirect('index.php');
require_once dirname(__DIR__).'/private/document_storage.php';
$error='';$categories=['invoice'=>'Rechnung','worklog'=>'Arbeitsnachweis','contract'=>'Vertrag','correspondence'=>'Korrespondenz','other'=>'Sonstiges'];
$customers=$pdo->query('SELECT id,name FROM customers ORDER BY name LIMIT 2000')->fetchAll();
$properties=$pdo->query('SELECT id,name,customer_id FROM properties ORDER BY name LIMIT 2000')->fetchAll();
$customerIds=array_fill_keys(array_column($customers,'id'),true);
$propertyMap=[];foreach($properties as $prop)$propertyMap[(int)$prop['id']]=$prop;
if ($_SERVER['REQUEST_METHOD']==='POST') {
 checkCsrf();
 $title=trim((string)($_POST['title']??''));$notes=trim((string)($_POST['notes']??''));$category=(string)($_POST['category']??'other');
 $cid=(int)($_POST['customer_id']??0);$pid=(int)($_POST['property_id']??0);
 try {
  if ($title===''||mb_strlen($title)>190||mb_strlen($notes)>5000||!isset($categories[$category])) throw new RuntimeException('Titel, Kategorie oder Notizen ungültig.');
  if (($cid && !isset($customerIds[$cid]))||($pid && !isset($propertyMap[$pid]))) throw new RuntimeException('Kunde oder Objekt nicht vorhanden.');
  if ($cid && $pid && (int)$propertyMap[$pid]['customer_id']!==$cid) throw new RuntimeException('Objekt gehört nicht zum ausgewählten Kunden.');
  [$mime,$size,$original]=documentCheckUpload($_FILES['document']??[]);
  $stored=bin2hex(random_bytes(16));$dir=documentStorageDir();$path=$dir.'/'.$stored;
  if (!move_uploaded_file((string)$_FILES['document']['tmp_name'],$path)) throw new RuntimeException('Datei konnte nicht gespeichert werden.');
  @chmod($path,0600);
  try {
   $hash=hash_file('sha256',$path);
   $stmt=$pdo->prepare('INSERT INTO documents(customer_id,property_id,category,title,original_name,stored_name,mime_type,size_bytes,sha256,notes) VALUES(?,?,?,?,?,?,?,?,?,?)');
   $stmt->execute([$cid?:null,$pid?:null,$category,$title,$original,$stored,$mime,$size,$hash,$notes?:null]);
  } catch(Throwable $ex) { @unlink($path);throw $ex; }
  redirect('documents.php?saved=1');
 } catch(Throwable $ex) {error_log('GSZ_DOCUMENT_UPLOAD_FAILED');$error=$ex instanceof PDOException?'Datenbankfehler beim Speichern.':$ex->getMessage();}
}
$q=trim((string)($_GET['q']??''));if(mb_strlen($q)>100)$q=mb_substr($q,0,100);
$filter=(string)($_GET['category']??'');if(!isset($categories[$filter]))$filter='';
$sql='SELECT d.*,c.name AS customer_name,p.name AS property_name FROM documents d LEFT JOIN customers c ON c.id=d.customer_id LEFT JOIN properties p ON p.id=d.property_id WHERE 1=1';$params=[];
if($filter!==''){$sql.=' AND d.category=?';$params[]=$filter;}
if($q!==''){$sql.=' AND (d.title LIKE ? OR d.original_name LIKE ? OR d.notes LIKE ?)';$term='%'.$q.'%';array_push($params,$term,$term,$term);}
$sql.=' ORDER BY d.id DESC LIMIT 200';$stmt=$pdo->prepare($sql);$stmt->execute($params);$documents=$stmt->fetchAll();
?><!doctype html><html lang="de"><head><meta charset="utf-8"><meta name="viewport" content="width=device-width,initial-scale=1"><meta name="robots" content="noindex,nofollow"><title>Dokumentenarchiv · GSZ</title><link rel="stylesheet" href="assets.css"><style>.meta{font-size:13px;color:#a6b4c0}.doc{padding:14px 0;border-bottom:1px solid #30414b;overflow-wrap:anywhere}</style></head><body><main><header><div class="brand"><img class="brand-logo" src="assets/gsz-logo-horizontal.webp" alt="Gebäudeservice Zentrum" width="640" height="188"></div><a href="index.php">← Dashboard</a></header><h1>Dokumentenarchiv</h1><p class="muted">Geschützter Dokumentenspeicher für Rechnungen, Arbeitsnachweise, Verträge und Korrespondenz.</p>
<?php if($error):?><p class="error" role="alert"><?=e($error)?></p><?php endif;?><?php if(isset($_GET['saved'])):?><p role="status">Dokument gespeichert.</p><?php endif;?>
<div class="grid"><section class="card"><h2>Dokument hochladen</h2><form method="post" enctype="multipart/form-data"><input type="hidden" name="csrf" value="<?=e(csrf())?>"><label>Titel<input name="title" maxlength="190" required value="<?=e((string)($_POST['title']??''))?>"></label><label>Kategorie<select name="category"><?php foreach($categories as $key=>$label):?><option value="<?=e($key)?>"><?=e($label)?></option><?php endforeach;?></select></label><label>Kunde (optional)<select name="customer_id"><option value="">Kein Kunde</option><?php foreach($customers as $c):?><option value="<?=(int)$c['id']?>"><?=e($c['name'])?></option><?php endforeach;?></select></label><label>Objekt (optional)<select name="property_id"><option value="">Kein Objekt</option><?php foreach($properties as $p):?><option value="<?=(int)$p['id']?>"><?=e($p['name'])?></option><?php endforeach;?></select></label><label>Notizen<textarea name="notes" maxlength="5000" rows="3"></textarea></label><label>Datei (PDF, JPG, PNG, WebP oder TXT; max. 10 MB)<input type="file" name="document" accept=".pdf,.jpg,.jpeg,.png,.webp,.txt" required></label><button>Dokument sicher speichern</button></form></section>
<section class="card"><h2>Gespeicherte Dokumente</h2><form method="get"><label>Suche<input name="q" value="<?=e($q)?>" maxlength="100" placeholder="Titel, Dateiname, Notizen"></label><label>Kategorie<select name="category"><option value="">Alle Kategorien</option><?php foreach($categories as $key=>$label):?><option value="<?=e($key)?>" <?=$filter===$key?'selected':''?>><?=e($label)?></option><?php endforeach;?></select></label><button>Filtern</button></form><p class="meta">Bis zu 200 neueste Treffer.</p>
<?php if(!$documents):?><p>Keine Dokumente vorhanden.</p><?php endif;?><?php foreach($documents as $d):?><div class="doc"><strong><?=e($d['title'])?></strong> · <a href="document_download.php?id=<?=(int)$d['id']?>">⬇ Herunterladen</a><div class="meta"><?=e($categories[$d['category']]??'Sonstiges')?> · <?=e($d['original_name'])?> · <?=number_format((int)$d['size_bytes']/1024,0,',','.')?> KB · <?=e($d['created_at'])?></div><?php if($d['customer_name']||$d['property_name']):?><div class="meta"><?=e($d['customer_name'])?> <?=e($d['property_name'])?></div><?php endif;?></div><?php endforeach;?></section></div>
<p class="muted"><small>Dokumente können hier bewusst nicht gelöscht oder überschrieben werden. Dies ist noch kein GoBD-zertifiziertes bzw. nachgewiesen GoBD-konformes Archiv. Backups, Zugriffsprotokolle, Aufbewahrungsregeln und revisionssichere Ablage fehlen.</small></p></main></body></html>
