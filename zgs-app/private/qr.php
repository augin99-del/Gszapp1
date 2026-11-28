<?php
declare(strict_types=1);
/** QR-Code (ISO 18004) im Byte-Modus, Fehlerkorrektur M, Versionen 1–10 (bis 213 Byte). Ohne externe Bibliothek.
 *  gszQrMatrix() liefert bool[Zeile][Spalte] (true = dunkel), ohne Ruhezone. */
const GSZ_QR_M=[1=>[10,1,16,0,0],2=>[16,1,28,0,0],3=>[26,1,44,0,0],4=>[18,2,32,0,0],5=>[24,2,43,0,0],6=>[16,4,27,0,0],7=>[18,4,31,0,0],8=>[22,2,38,2,39],9=>[22,3,36,2,37],10=>[26,4,43,1,44]];
const GSZ_QR_ALIGN=[1=>[],2=>[6,18],3=>[6,22],4=>[6,26],5=>[6,30],6=>[6,34],7=>[6,22,38],8=>[6,24,42],9=>[6,26,46],10=>[6,28,50]];
function gszQrGf():array {static $t=null;if($t)return $t;$exp=array_fill(0,512,0);$log=array_fill(0,256,0);$x=1;for($i=0;$i<255;$i++){$exp[$i]=$x;$log[$x]=$i;$x<<=1;if($x&0x100)$x^=0x11D;}for($i=255;$i<512;$i++)$exp[$i]=$exp[$i-255];return $t=[$exp,$log];}
function gszQrEcc(array $data,int $n):array {
 [$exp,$log]=gszQrGf();$g=[1];for($i=0;$i<$n;$i++){$ng=array_fill(0,count($g)+1,0);foreach($g as $j=>$c){$ng[$j]^=$c;if($c)$ng[$j+1]^=$exp[$log[$c]+$i];}$g=$ng;}
 $r=array_fill(0,$n,0);foreach($data as $b){$f=$b^$r[0];array_shift($r);$r[]=0;if($f)for($j=0;$j<$n;$j++)if($g[$j+1])$r[$j]^=$exp[$log[$f]+$log[$g[$j+1]]];}return $r;
}
function gszQrMatrix(string $text,?int $mask=null):array {
 $bytes=array_values(unpack('C*',$text)?:[]);$len=count($bytes);$ver=0;
 foreach(GSZ_QR_M as $v=>$s){$cap=$s[1]*$s[2]+$s[3]*$s[4];if(4+($v<10?8:16)+8*$len<=8*$cap){$ver=$v;break;}}
 if(!$ver)throw new InvalidArgumentException('Text für QR-Code zu lang.');
 [$ecn,$b1,$d1,$b2,$d2]=GSZ_QR_M[$ver];$cap=$b1*$d1+$b2*$d2;
 $bits='0100'.str_pad(decbin($len),$ver<10?8:16,'0',STR_PAD_LEFT);foreach($bytes as $b)$bits.=str_pad(decbin($b),8,'0',STR_PAD_LEFT);
 $bits.=str_repeat('0',min(4,8*$cap-strlen($bits)));$bits.=str_repeat('0',(8-strlen($bits)%8)%8);
 $data=array_map('bindec',str_split($bits,8));for($i=0;count($data)<$cap;$i++)$data[]=$i%2?0x11:0xEC;
 $blocks=[];$o=0;for($i=0;$i<$b1+$b2;$i++){$n=$i<$b1?$d1:$d2;$d=array_slice($data,$o,$n);$o+=$n;$blocks[]=[$d,gszQrEcc($d,$ecn)];}
 $cw=[];for($i=0;$i<max($d1,$d2);$i++)foreach($blocks as $b)if(isset($b[0][$i]))$cw[]=$b[0][$i];for($i=0;$i<$ecn;$i++)foreach($blocks as $b)$cw[]=$b[1][$i];
 $size=17+4*$ver;$m=array_fill(0,$size,array_fill(0,$size,false));$fn=$m;
 $set=function(int $x,int $y,bool $v)use(&$m,&$fn){$m[$y][$x]=$v;$fn[$y][$x]=true;};
 for($i=0;$i<$size;$i++){$set(6,$i,$i%2===0);$set($i,6,$i%2===0);}
 foreach([[3,3],[$size-4,3],[3,$size-4]] as [$cx,$cy])for($dy=-4;$dy<=4;$dy++)for($dx=-4;$dx<=4;$dx++){$x=$cx+$dx;$y=$cy+$dy;if($x<0||$y<0||$x>=$size||$y>=$size)continue;$d=max(abs($dx),abs($dy));$set($x,$y,$d!==2&&$d!==4);}
 $al=GSZ_QR_ALIGN[$ver];$k=count($al);for($i=0;$i<$k;$i++)for($j=0;$j<$k;$j++){if(($i===0&&$j===0)||($i===0&&$j===$k-1)||($i===$k-1&&$j===0))continue;for($dy=-2;$dy<=2;$dy++)for($dx=-2;$dx<=2;$dx++)$set($al[$i]+$dx,$al[$j]+$dy,max(abs($dx),abs($dy))!==1);}
 $format=function(int $mk)use(&$set,$size){$d=$mk;$r=$d;for($i=0;$i<10;$i++)$r=($r<<1)^(($r>>9)*0x537);$b=(($d<<10)|$r)^0x5412;$bit=fn($i)=>(($b>>$i)&1)===1;
  for($i=0;$i<=5;$i++)$set(8,$i,$bit($i));$set(8,7,$bit(6));$set(8,8,$bit(7));$set(7,8,$bit(8));for($i=9;$i<15;$i++)$set(14-$i,8,$bit($i));
  for($i=0;$i<8;$i++)$set($size-1-$i,8,$bit($i));for($i=8;$i<15;$i++)$set(8,$size-15+$i,$bit($i));$set(8,$size-8,true);};
 $format(0);
 if($ver>=7){$r=$ver;for($i=0;$i<12;$i++)$r=($r<<1)^(($r>>11)*0x1F25);$b=($ver<<12)|$r;for($i=0;$i<18;$i++){$v=(($b>>$i)&1)===1;$a=$size-11+$i%3;$c=intdiv($i,3);$set($a,$c,$v);$set($c,$a,$v);}}
 $i=0;$total=count($cw)*8;for($right=$size-1;$right>=1;$right-=2){if($right===6)$right=5;for($vert=0;$vert<$size;$vert++)for($j=0;$j<2;$j++){$x=$right-$j;$up=(($right+1)&2)===0;$y=$up?$size-1-$vert:$vert;if(!$fn[$y][$x]&&$i<$total){$m[$y][$x]=(($cw[$i>>3]>>(7-($i&7)))&1)===1;$i++;}}}
 $masks=[fn($x,$y)=>($x+$y)%2===0,fn($x,$y)=>$y%2===0,fn($x,$y)=>$x%3===0,fn($x,$y)=>($x+$y)%3===0,fn($x,$y)=>(intdiv($x,3)+intdiv($y,2))%2===0,fn($x,$y)=>$x*$y%2+$x*$y%3===0,fn($x,$y)=>($x*$y%2+$x*$y%3)%2===0,fn($x,$y)=>(($x+$y)%2+$x*$y%3)%2===0];
 $apply=function(array $base,int $mk)use($masks,$fn,$size){for($y=0;$y<$size;$y++)for($x=0;$x<$size;$x++)if(!$fn[$y][$x]&&$masks[$mk]($x,$y))$base[$y][$x]=!$base[$y][$x];return $base;};
 $best=null;$bestScore=PHP_INT_MAX;foreach($mask===null?range(0,7):[$mask] as $mk){$mm=$m;$fm=$fn;$format($mk);$cand=$apply($m,$mk);$s=$mask===null?gszQrPenalty($cand):0;if($s<$bestScore){$bestScore=$s;$best=$cand;}$m=$mm;$fn=$fm;}
 return $best;
}
function gszQrPenalty(array $m):int {
 $n=count($m);$p=0;$dark=0;
 for($y=0;$y<$n;$y++){$row=implode('',array_map(fn($b)=>$b?'1':'0',$m[$y]));$col='';for($x=0;$x<$n;$x++)$col.=$m[$x][$y]?'1':'0';$dark+=substr_count($row,'1');
  foreach([$row,$col] as $l){preg_match_all('/0{5,}|1{5,}/',$l,$r);foreach($r[0] as $run)$p+=strlen($run)-2;$p+=40*(preg_match_all('/(?=10111010000|00001011101)/',$l));}}
 for($y=0;$y<$n-1;$y++)for($x=0;$x<$n-1;$x++){$c=$m[$y][$x];if($m[$y][$x+1]===$c&&$m[$y+1][$x]===$c&&$m[$y+1][$x+1]===$c)$p+=3;}
 return $p+10*intdiv(abs($dark*20-$n*$n*10),$n*$n);
}
/** SVG mit 4 Modulen Ruhezone; skaliert frei (viewBox). */
function gszQrSvg(string $text,string $color='#000'):string {
 $m=gszQrMatrix($text);$n=count($m);$d='';foreach($m as $y=>$row)foreach($row as $x=>$v)if($v)$d.='M'.($x+4).' '.($y+4).'h1v1h-1z';
 return '<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 '.($n+8).' '.($n+8).'" shape-rendering="crispEdges" role="img" aria-label="QR-Code"><rect width="100%" height="100%" fill="#fff"/><path fill="'.htmlspecialchars($color,ENT_QUOTES).'" d="'.$d.'"/></svg>';
}
/** Zeichnet den QR-Code in ein tFPDF-Dokument (x,y = linke obere Ecke inkl. Ruhezone, $w = Kantenlänge in mm). */
function gszQrPdf(object $pdf,string $text,float $x,float $y,float $w):void {
 $m=gszQrMatrix($text);$n=count($m);$u=$w/($n+8);$pdf->SetFillColor(255,255,255);$pdf->Rect($x,$y,$w,$w,'F');$pdf->SetFillColor(0,0,0);
 foreach($m as $r=>$row){$c=0;while($c<$n){if(!$row[$c]){$c++;continue;}$s=$c;while($c<$n&&$row[$c])$c++;$pdf->Rect($x+($s+4)*$u,$y+($r+4)*$u,($c-$s)*$u+0.01,$u+0.01,'F');}}
}
