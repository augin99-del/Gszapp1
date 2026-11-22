<?php
declare(strict_types=1);if(PHP_SAPI!=='cli'||getenv('GSZ_SYNTHETIC_IMAP')!=='1'){http_response_code(403);exit;}
require dirname(__DIR__).'/private/setup_mail.php';
try{$remote=new GszEngineImapRemote(['mail_enabled'=>true,'mail_host'=>'localhost','mail_port'=>993,'mail_user'=>'synthetic@example.invalid','mail_pass'=>'synthetic-mock-only']);$folders=$remote->folders();$valid=$remote->validity('INBOX');$rows=$remote->fetchAfter('INBOX',0,10);if($remote->validity('INBOX')!==$valid)throw new RuntimeException('Synthetic epoch changed');echo json_encode(['accepted'=>true,'folders'=>count($folders),'validity'=>$valid,'messages'=>count($rows)]);}
catch(Throwable $e){echo json_encode(['accepted'=>false,'safe'=>!str_contains($e->getMessage(),'synthetic-mock-only')]);}
