<?php
declare(strict_types=1);
require_once __DIR__.'/platform.php';
/** Keep photos outside the public document root. */
function photoStorageDir(): string {
    $dir=__DIR__.'/uploads/worklog_photos';
    if (!is_dir($dir) && !@mkdir($dir,0700,true) && !is_dir($dir)) throw new RuntimeException('Fotoverzeichnis kann nicht erstellt werden.');
    $real=realpath($dir);
    if (!$real) throw new RuntimeException('Fotoverzeichnis fehlt.');
    return $real;
}
function photoValidate(array $file): array {
    if (($file['error']??UPLOAD_ERR_NO_FILE)!==UPLOAD_ERR_OK || !is_uploaded_file((string)($file['tmp_name']??''))) throw new RuntimeException('Foto-Upload fehlgeschlagen.');
    $size=(int)($file['size']??0);
    if ($size<1 || $size>8*1024*1024) throw new RuntimeException('Foto muss kleiner als 8 MB sein.');
    $tmp=(string)$file['tmp_name'];
    $info=@getimagesize($tmp);
    if (!$info || ($info[0]??0)<1 || ($info[1]??0)<1 || ($info[0]??0)>12000 || ($info[1]??0)>12000) throw new RuntimeException('Ungültige Bilddatei oder zu große Bildabmessungen.');
    $mime=(new finfo(FILEINFO_MIME_TYPE))->file($tmp);
    $formats=['image/jpeg'=>'jpg','image/png'=>'png','image/webp'=>'webp'];
    if (!isset($formats[$mime]) || ($info['mime']??'')!==$mime) throw new RuntimeException('Nur JPEG, PNG und WebP erlaubt.');
    $name=trim((string)($file['name']??'Foto'));
    $name=gszName($name);
    return [$mime,$formats[$mime],$size,$name?:'Foto'];
}
