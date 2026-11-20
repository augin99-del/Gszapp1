<?php
declare(strict_types=1);
// Native adapter shim: synthetic PHP IMAP functions, no sockets and no external mailbox.
if(extension_loaded('imap'))throw new RuntimeException('Run this synthetic adapter test with a PHP CLI without ext-imap.');
foreach(['IMAP_OPENTIMEOUT'=>1,'IMAP_READTIMEOUT'=>2,'IMAP_WRITETIMEOUT'=>3,'SA_UIDVALIDITY'=>1,'SA_MESSAGES'=>2,'SA_UIDNEXT'=>4,'SE_UID'=>1,'FT_UID'=>1] as $key=>$value)if(!defined($key))define($key,$value);
$shimFile=getenv('GSZ_RECOVERY_SHIM_STATE')?:'';$GLOBALS['recoveryImap']=$shimFile!==''?json_decode(file_get_contents($shimFile),true,512,JSON_THROW_ON_ERROR):['valid'=>77,'messages'=>[],'events'=>[],'errors'=>[]];
function recoveryShimSave():void{$path=getenv('GSZ_RECOVERY_SHIM_STATE');if($path)file_put_contents($path,json_encode($GLOBALS['recoveryImap'],JSON_THROW_ON_ERROR));}
function imap_timeout(int $kind,int $seconds):bool{return true;}
function imap_open(string $folder,string $user,string $pass,int $options,int $retries,array $params):object{if(!str_contains($folder,'synthetic.invalid:993/')||$user!=='synthetic')throw new RuntimeException('Shim only accepts synthetic.invalid');$GLOBALS['recoveryImap']['events'][]='open';recoveryShimSave();return new stdClass();}
function imap_close(object $imap):bool{return true;}
function imap_list(object $imap,string $prefix,string $pattern):array{return [$prefix.'INBOX',$prefix.'Sent'];}
function imap_reopen(object $imap,string $folder):bool{return true;}
function imap_status(object $imap,string $folder,int $flags):object{$s=&$GLOBALS['recoveryImap'];$s['status_calls']=($s['status_calls']??0)+1;if(($s['flip_at']??0)===$s['status_calls'])$s['valid']++;recoveryShimSave();return (object)['uidvalidity'=>$s['valid'],'messages'=>count($s['messages']),'uidnext'=>max([0,...array_keys($s['messages'])])+1];}
function imap_errors():array|false{$errors=$GLOBALS['recoveryImap']['errors']??[];$GLOBALS['recoveryImap']['errors']=[];return $errors?:false;}
function imap_search(object $imap,string $criteria,int $flags):array|false{$s=&$GLOBALS['recoveryImap'];$s['events'][]='search';$s['criteria']=$criteria;if(($s['search_failure']??false)||(($s['header_failure']??false)&&str_starts_with($criteria,'HEADER '))){$s['errors']=['Synthetic search error'];recoveryShimSave();return false;}recoveryShimSave();return array_map('intval',array_keys($s['messages']))?:false;}
function imap_fetchheader(object $imap,int $uid,int $flags):string|false{$c=$GLOBALS['recoveryImap']['messages'][$uid]??null;return $c['header']??($c?explode("\r\n\r\n",$c['raw'],2)[0]."\r\n":false);}
function imap_fetch_overview(object $imap,string $uid,int $flags):array|false{$c=$GLOBALS['recoveryImap']['messages'][(int)$uid]??null;return $c?[(object)['uid'=>(int)$uid,'deleted'=>$c['deleted']??false]]:false;}
function imap_append(object $imap,string $folder,string $raw,string $flags):bool{$s=&$GLOBALS['recoveryImap'];$s['events'][]='append';if(!($s['fail_before']??false)){$uid=100+count($s['messages']);$s['messages'][$uid]=['raw'=>$raw,'deleted'=>false];}recoveryShimSave();return !($s['fail_before']??false)&&!($s['fail_after']??false);}
if(defined('GSZ_RECOVERY_SHIM_ONLY'))return;
require dirname(__DIR__).'/private/mail_remote.php';
$n=0;function ic(bool $ok,string $message):void{global $n;$n++;if(!$ok)throw new RuntimeException($message);}function ir(callable $f,string $message):void{try{$f();}catch(Throwable $e){ic(true,$message);return;}ic(false,$message);}
$remote=new GszImapRemote(['mail_enabled'=>true,'mail_host'=>'synthetic.invalid','mail_port'=>993,'mail_user'=>'synthetic','mail_pass'=>'synthetic']);$id='<'.str_repeat('a',32).'@gsz.internal>';
$s=&$GLOBALS['recoveryImap'];$s['messages']=[1=>['header'=>'Message-ID: '.$id."\r\n"],2=>['header'=>"Message-ID: <unrelated@gsz.internal>\r\n"],3=>['header'=>'Message-ID: '.$id."\r\n",'deleted'=>true]];$found=$remote->findMessageId('Sent',$id);ic($found===['uidvalidity'=>77,'uids'=>[1],'deleted_uids'=>[3]],'exact native match, ignores unrelated and preserves deleted identity');ic($s['criteria']==='HEADER Message-ID "'.$id.'"','IMAP query uses exact safe identity');$s['messages']=[];$s['search_failure']=true;ic($remote->findMessageId('Sent',$id)['uids']===[],'empty mailbox verified without search');
$s['messages']=[1=>['header'=>'Message-ID: '.$id."\r\n"]];ir(fn()=>$remote->findMessageId('Sent',$id),'failed false search never means absence');
$s['search_failure']=false;$s['header_failure']=true;ic($remote->findMessageId('Sent',$id)['uids']===[1]&&$s['criteria']==='ALL','fallback verifies all headers when HEADER search fails');
$s['header_failure']=false;$s['flip_at']=$s['status_calls']+2;ir(fn()=>$remote->findMessageId('Sent',$id),'native UID epoch change blocks result');$s['flip_at']=0;
$s['messages']=[1=>['header'=>'Message-ID: '.$id."\r\nMessage-ID: ".$id."\r\n"]];ir(fn()=>$remote->findMessageId('Sent',$id),'multiple Message-ID headers are ambiguous');$s['messages']=[];for($i=1;$i<=101;$i++)$s['messages'][$i]=['header'=>'Message-ID: '.$id."\r\n"];ir(fn()=>$remote->findMessageId('Sent',$id),'candidate limit blocks incomplete search');ir(fn()=>$remote->findMessageId('Unknown',$id),'unknown destination refused');ir(fn()=>$remote->findMessageId('Sent',$id."\r\nALL"),'search injection refused');
echo "Mail recovery native IMAP shim tests passed ($n assertions).\n";
