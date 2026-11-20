<?php
declare(strict_types=1);
if(PHP_SAPI!=='cli'||getenv('GSZ_SYNTHETIC_SMTP')!=='1'){fwrite(STDERR,"Use isolated Python loopback test harness only.\n");exit(2);}
require dirname(__DIR__).'/private/mail_remote.php';
// Fixed loopback and .invalid recipients: no app config, account or real mail is used.
$config=['allow_mail_send'=>true,'smtp_transport'=>getenv('GSZ_SYNTHETIC_SMTP_TRANSPORT')?:'native','smtp_host'=>'127.0.0.1','smtp_port'=>(getenv('GSZ_SYNTHETIC_SMTP_PORT')==='465'?465:587),'mail_from'=>'owner@example.invalid','mail_user'=>'synthetic-test-user','mail_pass'=>'synthetic-mock-only'];
try{(new GszSmtpSender($config))->send(['customer@example.invalid'],"From: owner@example.invalid\r\nTo: customer@example.invalid\r\nSubject: Synthetic loopback test\r\n\r\n.line\r\n");echo json_encode(['accepted'=>true]),PHP_EOL;}catch(GszMailSendException $e){echo json_encode(['accepted'=>false,'uncertain'=>$e->uncertain]),PHP_EOL;}
