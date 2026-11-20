<?php
declare(strict_types=1);
if(PHP_SAPI!=='cli'){http_response_code(403);exit;}
require_once __DIR__.'/config_loader.php';require_once __DIR__.'/system_check.php';
try{$config=gszLoadConfig();}catch(Throwable $e){fwrite(STDERR,"Konfiguration nicht sicher ladbar; private Server-/Vaultkonfiguration prüfen.\n");exit(2);}
$pdo=null;try{$pdo=gszConnectDatabase($config);}catch(Throwable $e){}
$context=[];foreach(array_slice($argv,1) as $arg){if(str_starts_with($arg,'--web-root='))$context['web_root']=substr($arg,11);elseif(!in_array($arg,['--json','--probe-storage'],true)){fwrite(STDERR,"Aufruf: php private/system_check_cli.php [--json] [--web-root=/ABSOLUT/zgs-app/public] [--probe-storage]\n");exit(2);}}
$result=gszSystemCheck($config,$pdo,$context);
if(in_array('--probe-storage',$argv,true)){$ok=gszStorageProbe();$result['checks'][]=['id'=>'storage_probe','label'=>'Expliziter privater Schreib-/Lese-/Löschtest','status'=>$ok?'ok':'block','detail'=>'Zufällige Testdatei verwendet; keine Geschäftsdaten verändert.'];if(!$ok){$result['blocks']++;$result['ready_for_local_installation_test']=false;}}
if(in_array('--json',$argv,true))echo json_encode($result,JSON_PRETTY_PRINT|JSON_UNESCAPED_UNICODE|JSON_THROW_ON_ERROR),"\n";
else{foreach($result['checks'] as $row)echo strtoupper($row['status']).' '.$row['label'].': '.$row['detail']."\n";echo 'Blocker: '.$result['blocks'].'; Hinweise: '.$result['warnings'].". Keine Produktionsfreigabe.\n";}
exit($result['blocks']?2:0);
