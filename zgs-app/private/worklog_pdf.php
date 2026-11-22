<?php
declare(strict_types=1);
/** Dependency-free PDF report, pagination and optional image embedding. */
function worklogReportPdf(array $log, array $photos, string $storageDir): string {
    $objects=[]; $add=static function(string $body) use (&$objects): int {$objects[]=$body;return count($objects);};
    $catalog=$add('');$pagesRoot=$add('');$font=$add('<< /Type /Font /Subtype /Type1 /BaseFont /Helvetica >>');
    $pages=[];$commands='';$y=790;$pageImages=[];
    $latin=static function(string $text): string {return iconv('UTF-8','Windows-1252//TRANSLIT//IGNORE',$text)?:'';};
    $literal=static function(string $s) use ($latin): string {return str_replace(['\\','(',')',"\r","\n"],['\\\\','\\(','\\)',' ',' '],$latin($s));};
    $flush=static function() use (&$commands,&$pages,&$objects,&$pageImages,$add,$font): void {
        if ($commands==='') return;
        $stream=$add('<< /Length '.strlen($commands)." >>\nstream\n".$commands."endstream");
        $resources='<< /Font << /F1 '.$font.' 0 R >>';
        if($pageImages){$resources.=' /XObject <<';foreach($pageImages as $key=>$ref)$resources.=' /'.$key.' '.$ref.' 0 R';$resources.=' >>';}
        $resources.=' >>';
        $page=$add('<< /Type /Page /Parent '.$GLOBALS['unused_worklog_root'].' 0 R /MediaBox [0 0 595 842] /Resources '.$resources.' /Contents '.$stream.' 0 R >>');
        $pages[]=$page;$commands='';$pageImages=[];
    };
    // The page tree object is allocated first, so references are stable.
    $GLOBALS['unused_worklog_root']=$pagesRoot;
    $newPage=static function() use (&$y,&$commands,$flush): void { $flush();$y=790;};
    $text=static function(string $value,int $size=11,bool $bold=false) use (&$commands,&$y,$literal,$newPage): void {
        $max=$size>=15?56:90;
        $value=str_replace(["\r\n","\r"],"\n",$value);
        foreach(explode("\n",$value) as $para){
            $words=preg_split('/\s+/u',trim($para))?:[];$line='';
            foreach($words as $word){
                if($word==='')continue;
                while(mb_strlen($word)>$max){
                    $chunk=mb_substr($word,0,$max);$word=mb_substr($word,$max);
                    if($line!==''){if($y<65)$newPage();$commands.='BT /F1 '.$size.' Tf 48 '.$y.' Td ('.$literal($line).") Tj ET\n";$y-=($size+6);$line='';}
                    if($y<65)$newPage();$commands.='BT /F1 '.$size.' Tf 48 '.$y.' Td ('.$literal($chunk).") Tj ET\n";$y-=($size+6);
                }
                if($word==='')continue;
                if(mb_strlen($line.' '.$word)>$max){if($y<65)$newPage();$commands.='BT /F1 '.$size.' Tf 48 '.$y.' Td ('.$literal($line).") Tj ET\n";$y-=($size+6);$line='';}
                $line.=($line===''?'':' ').$word;
            }
            if($y<65)$newPage();
            $commands.='BT /F1 '.$size.' Tf 48 '.$y.' Td ('.$literal($line).") Tj ET\n";$y-=($size+6);
        }
        if($bold)$y-=5;
    };
    $text('ZGS | ARBEITSNACHWEIS',18,true);
    $text('Nachweis Nr. '.(int)$log['id'].' | Erstellt: '.date('d.m.Y H:i'),10);
    $y-=12;
    $text('Objekt: '.(string)$log['property_name'],12,true);
    $text('Anschrift: '.(string)$log['property_address']);
    $text('Ausgefuehrt am: '.(string)$log['performed_on']);
    $text('Arbeitszeit: '.(int)$log['duration_minutes'].' Minuten');
    if(!empty($log['task_title']))$text('Aufgabe: '.(string)$log['task_title']);
    $y-=12;$text('Taetigkeit',14,true);$text((string)$log['description']);
    if(trim((string)($log['notes']??''))!==''){$y-=8;$text('Besonderheiten',14,true);$text((string)$log['notes']);}
    if($photos){$y-=12;$text('Fotodokumentation',14,true);}
    $labels=['before'=>'Vorher','after'=>'Nachher','other'=>'Sonstiges'];
    foreach($photos as $photo){
        $path=$storageDir.'/'.basename((string)$photo['stored_name']);
        if(!is_file($path)){$text('Foto fehlt (Nr. '.(int)$photo['id'].').');continue;}
        $raw=@file_get_contents($path);
        if($raw===false || strlen($raw)>8*1024*1024){$text('Foto konnte nicht gelesen werden.');continue;}
        $info=@getimagesizefromstring($raw);
        if(!$info){$text('Fotoformat nicht lesbar.');continue;}
        $jpeg=null;
        if(($info['mime']??'')==='image/jpeg')$jpeg=$raw;
        elseif(function_exists('imagecreatefromstring')&&function_exists('imagejpeg')){
            $im=@imagecreatefromstring($raw);
            if($im!==false){
                $canvas=imagecreatetruecolor(imagesx($im),imagesy($im));
                $white=imagecolorallocate($canvas,255,255,255);imagefill($canvas,0,0,$white);
                imagecopy($canvas,$im,0,0,0,0,imagesx($im),imagesy($im));
                ob_start();imagejpeg($canvas,null,82);$jpeg=ob_get_clean();
                imagedestroy($canvas);imagedestroy($im);
            }
        }
        if(!$jpeg){$text('Foto '.(int)$photo['id'].': PNG/WebP benötigt die PHP-GD-Erweiterung.');continue;}
        $width=(int)$info[0];$height=(int)$info[1];
        $scale=min(490/$width,360/$height,1.5);$drawW=round($width*$scale,2);$drawH=round($height*$scale,2);
        if($y-$drawH-40<55)$newPage();
        $text(($labels[$photo['label']]??'Foto').' | '.(string)$photo['created_at'],10);
        $ref=$add('<< /Type /XObject /Subtype /Image /Width '.$width.' /Height '.$height.' /ColorSpace /DeviceRGB /BitsPerComponent 8 /Filter /DCTDecode /Length '.strlen($jpeg)." >>\nstream\n".$jpeg."\nendstream");
        $key='Im'.count($pageImages);$pageImages[$key]=$ref;
        $y-=$drawH;
        $commands.="q $drawW 0 0 $drawH 48 $y cm /$key Do Q\n";
        $y-=25;
    }
    $flush();
    unset($GLOBALS['unused_worklog_root']);
    $objects[$catalog-1]='<< /Type /Catalog /Pages '.$pagesRoot.' 0 R >>';
    $objects[$pagesRoot-1]='<< /Type /Pages /Kids ['.implode(' ',array_map(static fn($n)=>$n.' 0 R',$pages)).'] /Count '.count($pages).' >>';
    $pdf="%PDF-1.4\n%\xE2\xE3\xCF\xD3\n";$offsets=[0];
    foreach($objects as $i=>$obj){$offsets[$i+1]=strlen($pdf);$pdf.=($i+1)." 0 obj\n".$obj."\nendobj\n";}
    $xref=strlen($pdf);$pdf.='xref' ."\n0 ".(count($objects)+1)."\n0000000000 65535 f \n";
    for($i=1;$i<=count($objects);$i++)$pdf.=sprintf('%010d 00000 n ', $offsets[$i])."\n";
    $pdf.='trailer' ."\n<< /Size ".(count($objects)+1).' /Root '.$catalog." 0 R >>\nstartxref\n".$xref."\n%%EOF";
    return $pdf;
}
