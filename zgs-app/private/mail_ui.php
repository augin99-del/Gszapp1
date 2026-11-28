<?php
declare(strict_types=1);
/** Darstellung für den Mail-Bereich: deutsche Ordnernamen, feste Reihenfolge, Vorschau, Datumsangaben. Keine Netzwerkzugriffe. */
const GSZ_MAIL_ROLES=[
 'inbox'=>['Posteingang',['inbox']],
 'sent'=>['Gesendet',['sent','sent items','sent messages','sent mail','gesendet','gesendete objekte','gesendete elemente']],
 'drafts'=>['Entwürfe',['drafts','draft','entwürfe','entwuerfe']],
 'archive'=>['Archiv',['archive','archives','archiv']],
 'spam'=>['Spam',['spam','junk','junk e-mail','junk-e-mail','unerwünscht']],
 'trash'=>['Papierkorb',['trash','deleted items','deleted messages','papierkorb','gelöschte objekte','gelöschte elemente']],
];
function gszMailFolderRole(string $name):string {
 $tail=mb_strtolower(preg_replace('~^.*[/.]~','',$name));foreach(GSZ_MAIL_ROLES as $role=>[,$aliases])if(in_array($tail,$aliases,true))return $role;return '';
}
function gszMailFolderLabel(string $name):string {$role=gszMailFolderRole($name);return $role?GSZ_MAIL_ROLES[$role][0]:preg_replace('~^.*[/.]~','',$name);}
/** Ordner in fester Reihenfolge (Posteingang, Gesendet, Entwürfe, Archiv, Spam, Papierkorb, dann alle übrigen alphabetisch). */
function gszMailFolderSort(array $folders):array {
 $order=array_flip(array_keys(GSZ_MAIL_ROLES));usort($folders,function($a,$b)use($order){$ra=gszMailFolderRole($a['name']);$rb=gszMailFolderRole($b['name']);$oa=$ra!==''?$order[$ra]:99;$ob=$rb!==''?$order[$rb]:99;return $oa<=>$ob?:strcasecmp($a['name'],$b['name']);});return $folders;
}
function gszMailFolderByRole(array $folders,string $role):?string {foreach($folders as $f)if(gszMailFolderRole($f['name'])===$role)return $f['name'];return null;}
/**
 * Mailtext sicher als HTML: alles wird escaped, nur erkannte Adressen werden zu Links.
 * http(s) und www. öffnen in neuem Tab (ohne Referrer), E-Mail-Adressen öffnen „Neue Mail“ in der Zentrale.
 * Lange Adressen werden für die Anzeige gekürzt; der volle Link steht im Tooltip.
 */
function gszMailLinkify(string $text):string {
 $re='~(?<url>\b(?:https?://|www\.)[^\s<>"\'()\[\]{}|\\^`]+)|(?<mail>\b[A-Za-z0-9._%+-]{1,64}@[A-Za-z0-9-]+(?:\.[A-Za-z0-9-]+)*\.[A-Za-z]{2,24}\b)~u';
 $out='';$pos=0;if(!preg_match_all($re,$text,$all,PREG_SET_ORDER|PREG_OFFSET_CAPTURE))return htmlspecialchars($text,ENT_QUOTES|ENT_SUBSTITUTE,'UTF-8');
 foreach($all as $m){[$hit,$at]=$m[0];if($at<$pos)continue;$out.=htmlspecialchars(substr($text,$pos,$at-$pos),ENT_QUOTES|ENT_SUBSTITUTE,'UTF-8');
  if(isset($m['url'])&&$m['url'][1]>=0&&$m['url'][0]!==''){$u=$hit;$trail='';while($u!==''&&strpbrk(substr($u,-1),'.,;:!?')!==false){$trail=substr($u,-1).$trail;$u=substr($u,0,-1);}
   $href=preg_match('~^https?://~i',$u)?$u:'https://'.$u;$show=preg_replace('~^https?://(www\.)?~i','',$u);if(mb_strlen($show)>60)$show=mb_substr($show,0,45).'…'.mb_substr($show,-10);
   $out.='<a href="'.htmlspecialchars($href,ENT_QUOTES|ENT_SUBSTITUTE,'UTF-8').'" target="_blank" rel="noopener noreferrer nofollow" title="'.htmlspecialchars($href,ENT_QUOTES|ENT_SUBSTITUTE,'UTF-8').'">'.htmlspecialchars($show,ENT_QUOTES|ENT_SUBSTITUTE,'UTF-8').'</a>'.htmlspecialchars($trail,ENT_QUOTES,'UTF-8');}
  else $out.='<a href="compose.php?to='.rawurlencode($hit).'">'.htmlspecialchars($hit,ENT_QUOTES|ENT_SUBSTITUTE,'UTF-8').'</a>';
  $pos=$at+strlen($hit);}
 return $out.htmlspecialchars(substr($text,$pos),ENT_QUOTES|ENT_SUBSTITUTE,'UTF-8');
}
function gszMailPreview(string $body,int $length=140):string {
 $lines=[];foreach(preg_split('/\r?\n/',$body) as $line){$t=trim($line);if($t===''||str_starts_with($t,'>'))continue;if(preg_match('/^(Am|On) .{4,200}(schrieb|wrote).{0,40}:$/u',$t))break;$lines[]=$t;if(mb_strlen(implode(' ',$lines))>$length)break;}
 $text=preg_replace('/\s+/u',' ',implode(' ',$lines));return mb_strlen($text)>$length?rtrim(mb_substr($text,0,$length-1)).'…':$text;
}
function gszMailName(string $from):string {$n=trim(preg_replace('/\s*<[^>]*>\s*/','',$from),"\" \t");return $n!==''?$n:$from;}
function gszMailAddress(string $from):string {return preg_match('/<([^>]+)>/',$from,$m)?$m[1]:(filter_var(trim($from),FILTER_VALIDATE_EMAIL)?trim($from):'');}
function gszMailWhen(?string $utc,bool $long=false):string {
 if(!$utc)return '';$d=(new DateTimeImmutable($utc.' UTC'))->setTimezone(new DateTimeZone('Europe/Berlin'));$today=new DateTimeImmutable('today',new DateTimeZone('Europe/Berlin'));$days=['','Mo','Di','Mi','Do','Fr','Sa','So'];
 if($long)return $days[(int)$d->format('N')].', '.$d->format('d.m.Y').', '.$d->format('H:i').' Uhr';
 if($d>=$today)return $d->format('H:i');if($d>=$today->modify('-1 day'))return 'Gestern';if($d>=$today->modify('-6 days'))return $days[(int)$d->format('N')];return $d->format($d->format('Y')===$today->format('Y')?'d.m.':'d.m.y');
}
/** Versandstand in Alltagssprache (statt draft/accepted/…). */
function gszMailDraftState(array $d):string {
 return match((string)$d['status']){'draft'=>'Entwurf','accepted'=>!empty($d['sent_copy'])?'Gesendet':'Gesendet · Kopie in „Gesendet“ fehlt','rejected'=>'Nicht gesendet – vom Mailserver abgelehnt','sending','uncertain'=>'Unklar, ob gesendet – bitte prüfen',default=>(string)$d['status']};
}
