<?php
declare(strict_types=1);
function gszConfigPath():string {
 $external=getenv('GSZ_CONFIG_FILE');$path=$external!==false&&$external!==''?$external:__DIR__.'/config.php';
 if(!str_starts_with($path,'/')||str_contains($path,"\0")||is_link($path))throw new RuntimeException('GSZ_CONFIG_PRIVATE');
 return $path;
}
function gszConfigSecurity(string $path):array {
 $exists=is_file($path);$real=$exists?realpath($path):false;$public=realpath(dirname(__DIR__).'/public');$documentRoot=(string)($_SERVER['DOCUMENT_ROOT']??'');$doc=$documentRoot!==''?realpath($documentRoot):false;$safe=$exists&&!is_link($path)&&$real!==false;
 foreach(array_filter([$public,$doc]) as $root)if($real&&($real===$root||str_starts_with($real,rtrim($root,'/').'/')))$safe=false;
 $mode=$exists?(fileperms($path)&0777):null;
 return ['exists'=>$exists,'safe_location'=>$safe,'permissions'=>$mode,'safe'=>$safe&&is_readable($path)&&($mode&0007)===0&&($mode&0022)===0];
}
