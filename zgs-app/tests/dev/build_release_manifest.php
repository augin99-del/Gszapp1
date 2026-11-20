<?php
declare(strict_types=1);
// Erzeugt private/release_integrity.json (SHA-256 aller ausgelieferten Programmdateien). Nach jeder Codeänderung ausführen.
if(PHP_SAPI!=='cli')exit(1);$root=realpath(dirname(__DIR__,2));$files=[];
foreach(['private','public'] as $top){$it=new RecursiveIteratorIterator(new RecursiveDirectoryIterator($root.'/'.$top,FilesystemIterator::SKIP_DOTS));foreach($it as $f){if(!$f->isFile()||$f->isLink())continue;$rel=substr($f->getPathname(),strlen($root)+1);
 if(in_array($rel,['private/config.php','private/release_integrity.json'],true)||preg_match('~^private/(uploads|secrets|server-config|backups)/~',$rel))continue;
 if(!preg_match('~^(private|public)/[a-zA-Z0-9_@./-]+$~D',$rel))throw new RuntimeException('Unzulässiger Dateiname: '.$rel);$files[$rel]=hash_file('sha256',$f->getPathname());}}
ksort($files,SORT_STRING);file_put_contents($root.'/private/release_integrity.json',json_encode(['release'=>'COMPLETE380','files'=>$files],JSON_PRETTY_PRINT|JSON_UNESCAPED_SLASHES)."\n");echo count($files)," files\n";
