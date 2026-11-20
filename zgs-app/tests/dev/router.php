<?php
// Nur lokale QA: simuliert HTTPS hinter dem PHP-Testserver. Niemals auf STRATO hochladen.
if(PHP_SAPI!=='cli-server')exit(1);
if(getenv('GSZ_DEV_STRICT_HTTPS')!=='1'||($_SERVER['HTTP_X_FORWARDED_PROTO']??'')==='https')$_SERVER['HTTPS']='on';
$path=parse_url($_SERVER['REQUEST_URI'],PHP_URL_PATH)?:'/';
if($path==='/')$path='/index.php';
$file=realpath($_SERVER['DOCUMENT_ROOT'].$path);
if(!$file||!str_starts_with($file,realpath($_SERVER['DOCUMENT_ROOT']).'/'))return false;
if(!str_ends_with($file,'.php'))return false;
$_SERVER['SCRIPT_NAME']=$path;$_SERVER['SCRIPT_FILENAME']=$file;$_SERVER['PHP_SELF']=$path;
chdir(dirname($file));require $file;
