<?php
declare(strict_types=1);
if(PHP_SAPI!=='cli'){http_response_code(403);exit;}require_once __DIR__.'/config_loader.php';require_once __DIR__.'/platform.php';
try{$r=gszAuditVerify(gszConnectDatabase(gszLoadConfig()));echo json_encode($r),PHP_EOL;exit($r['valid']?0:1);}catch(Throwable $e){fwrite(STDERR,"Audit check failed; schema/configuration required.\n");exit(1);}
