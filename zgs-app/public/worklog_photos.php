<?php
declare(strict_types=1);
require __DIR__.'/bootstrap.php';
if (empty($_SESSION['admin_id'])) redirect('index.php');
require dirname(__DIR__).'/private/photo_storage.php';
$id=filter_var($_GET['id']??$_POST['id']??null,FILTER_VALIDATE_INT);
if (!$id || $id<1) {http_response_code(404);exit('Arbeitsnachweis nicht gefunden.');}
$st=$pdo->prepare('SELECT w.id,w.description,w.performed_on,p.name property_name FROM work_logs w JOIN properties p ON p.id=w.property_id WHERE w.id=?');
$st->execute([$id]);$log=$st->fetch();
if (!$log) {http_response_code(404);exit('Arbeitsnachweis nicht gefunden.');}
$error='';
if ($_SERVER['REQUEST_METHOD']==='POST') {
    checkCsrf();
    $label=(string)($_POST['label']??'other');
    if (!in_array($label,['before','after','other'],true)) $error='Ungültige Fotokategorie.';
    elseif (!isset($_FILES['photo']) || !is_array($_FILES['photo'])) $error='Bitte ein Foto auswählen.';
    else {
        $path=null;
        try {
            [$mime,$ext,$size,$original]=photoValidate($_FILES['photo']);
            $count=$pdo->prepare('SELECT COUNT(*) FROM work_log_photos WHERE work_log_id=?');$count->execute([$id]);
            if ((int)$count->fetchColumn()>=30) throw new RuntimeException('Maximal 30 Fotos je Nachweis.');
            $name=bin2hex(random_bytes(16)).'.'.$ext;
            $path=photoStorageDir().'/'.$name;
            if (!move_uploaded_file((string)$_FILES['photo']['tmp_name'],$path)) throw new RuntimeException('Foto konnte nicht gespeichert werden.');
            @chmod($path,0600);
            $st=$pdo->prepare('INSERT INTO work_log_photos(work_log_id,stored_name,original_name,mime_type,size_bytes,label) VALUES(?,?,?,?,?,?)');
            $st->execute([$id,$name,$original,$mime,$size,$label]);
            redirect('worklog_photos.php?id='.$id.'&saved=1');
        } catch (Throwable $ex) {
            if ($path && is_file($path)) @unlink($path);
            error_log('ZGS photo upload error: '.$ex->getMessage());
            $error='Upload nicht möglich: '.$ex->getMessage();
        }
    }
}
$st=$pdo->prepare('SELECT id,original_name,label,created_at FROM work_log_photos WHERE work_log_id=? ORDER BY id DESC LIMIT 30');$st->execute([$id]);$photos=$st->fetchAll();
?><!doctype html><html lang="de"><head><meta charset="utf-8"><meta name="viewport" content="width=device-width,initial-scale=1"><meta name="robots" content="noindex,nofollow"><title>Fotos · Arbeitsnachweis</title><link rel="stylesheet" href="zentrale.css"><style>.photos{display:grid;grid-template-columns:repeat(auto-fill,minmax(170px,1fr));gap:12px}.photo{padding:10px;border:1px solid #30414b;border-radius:12px}.photo img{display:block;width:100%;height:180px;object-fit:cover;border-radius:8px}.photo small{display:block;overflow-wrap:anywhere;margin-top:8px}</style></head><body><main><header><div class="brand"><img class="brand-logo" src="assets/gsz-logo-horizontal.webp" alt="Gebäudeservice Zentrum" width="640" height="188"></div><a href="worklogs.php">← Arbeitsnachweise</a></header>
<h1>Fotodokumentation</h1><p><a href="worklog_pdf.php?id=<?=(int)$id?>">📄 Arbeitsnachweis als PDF herunterladen</a></p><p><?=e($log['property_name'])?> · <?=e($log['description'])?> · <?=e($log['performed_on'])?></p>
<?php if($error):?><p class="error" role="alert"><?=e($error)?></p><?php endif;?>
<?php if(isset($_GET['saved'])):?><p role="status">Foto gespeichert.</p><?php endif;?>
<section class="card"><h2>Foto hinzufügen</h2><form method="post" enctype="multipart/form-data"><input type="hidden" name="csrf" value="<?=e(csrf())?>"><input type="hidden" name="id" value="<?=(int)$id?>">
<label>Art<select name="label"><option value="before">Vorher</option><option value="after">Nachher</option><option value="other">Sonstiges</option></select></label>
<label>Bild (JPEG, PNG oder WebP; maximal 8 MB)<input type="file" name="photo" accept="image/jpeg,image/png,image/webp" required></label><button type="submit">Foto hochladen</button></form>
<p class="muted">Maximal 30 Fotos pro Arbeitsnachweis. Bilder werden im privaten App-Verzeichnis gespeichert. GPS- und weitere EXIF-Metadaten können in Originalfotos enthalten sein.</p></section>
<section class="card"><h2>Gespeicherte Fotos (<?=count($photos)?>)</h2><div class="photos">
<?php foreach($photos as $photo):?><div class="photo"><a href="worklog_photo.php?id=<?=(int)$photo['id']?>" target="_blank" rel="noopener"><img loading="lazy" src="worklog_photo.php?id=<?=(int)$photo['id']?>" alt="<?=e($photo['label']==='before'?'Vorher':($photo['label']==='after'?'Nachher':'Dokumentationsfoto'))?>"></a><small><?=e($photo['label']==='before'?'Vorher':($photo['label']==='after'?'Nachher':'Sonstiges'))?> · <?=e($photo['created_at'])?></small></div><?php endforeach;?>
</div><?php if(!$photos):?><p class="muted">Noch keine Fotos hinterlegt.</p><?php endif;?></section></main></body></html>
