<?php
declare(strict_types=1);
require_once __DIR__.'/platform.php';
function documentStorageDir(): string {
    $path=__DIR__.'/uploads/documents';
    if (!is_dir($path) && !@mkdir($path,0700,true) && !is_dir($path)) throw new RuntimeException('Dokumentenspeicher nicht verfügbar.');
    $real=realpath($path);
    if (!$real) throw new RuntimeException('Dokumentenspeicher nicht verfügbar.');
    return $real;
}
function documentCheckUpload(array $file): array {
    if (($file['error']??UPLOAD_ERR_NO_FILE)!==UPLOAD_ERR_OK || !is_uploaded_file((string)($file['tmp_name']??''))) throw new RuntimeException('Upload fehlgeschlagen.');
    $size=(int)($file['size']??0);
    if ($size<1 || $size>10*1024*1024) throw new RuntimeException('Dateigröße muss zwischen 1 Byte und 10 MB liegen.');
    $tmp=(string)$file['tmp_name'];
    $mime=(new finfo(FILEINFO_MIME_TYPE))->file($tmp);
    $allowed=['application/pdf'=>'pdf','image/jpeg'=>'jpg','image/png'=>'png','image/webp'=>'webp','text/plain'=>'txt'];
    if (!isset($allowed[$mime])) throw new RuntimeException('Nur PDF, JPEG, PNG, WebP oder TXT erlaubt.');
    if ($mime==='application/pdf' && file_get_contents($tmp,false,null,0,5)!=='%PDF-') throw new RuntimeException('Ungültiger PDF-Dateikopf.');
    if (str_starts_with($mime,'image/')) {
        $info=@getimagesize($tmp);
        if (!$info || ($info['mime']??'')!==$mime || $info[0]>12000 || $info[1]>12000) throw new RuntimeException('Ungültiges Bild.');
    }
    $original=trim((string)($file['name']??'Dokument'));
    $original=gszName($original);
    return [$mime,$size,$original?:'Dokument'];
}
