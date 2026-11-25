<?php
declare(strict_types=1);
/** Druck-PDFs (Schema 430): Aushang mit QR-Code je Haus, Monatsbericht für Hausverwaltungen, Bewertungskarten. */
require_once __DIR__.'/pdf_document.php';require_once __DIR__.'/qr.php';require_once __DIR__.'/reports.php';require_once __DIR__.'/leads.php';
final class GszServicePdf extends tFPDF {
 public string $footerText='';
 /** Schrift wie GszPdfDocument: DejaVu aus dem Programm, geprüfte Kopie im privaten Cache. */
 public function __construct(){parent::__construct();$cache=__DIR__.'/uploads/pdf_fonts/unifont';if(!is_dir($cache)&&!mkdir($cache,0700,true)&&!is_dir($cache))throw new RuntimeException('PDF-Schriftcache nicht verfügbar.');$font=__DIR__.'/vendor/tfpdf/font/unifont/DejaVuSans.ttf';$copy=$cache.'/DejaVuSans.ttf';if(!is_file($copy)){if(!copy($font,$copy))throw new RuntimeException('PDF-Schrift fehlt.');@chmod($copy,0600);}if(is_link($copy)||hash_file('sha256',$font)!==hash_file('sha256',$copy))throw new RuntimeException('PDF-Schriftintegrität verletzt.');$this->fontpath=dirname($cache).'/';$this->AddFont('DejaVu','','DejaVuSans.ttf',true);$this->SetFont('DejaVu','',10);}
 public function Header(){}
 public function Footer(){if($this->footerText==='')return;$this->SetY(-13);$this->SetFont('DejaVu','',8);$this->SetTextColor(110,120,128);$this->Cell(0,5,$this->safe($this->footerText.' · Seite '.$this->PageNo()),0,0,'C');$this->SetTextColor(0,0,0);}
 /** Ersetzt Zeichen, die die Schrift nicht kennt (z. B. Emojis aus Mieter-Meldungen), durch „?“. */
 public function safe(string $t):string {$t=mb_check_encoding($t,'UTF-8')?$t:'';$cw=$this->CurrentFont['cw']??null;if(!$cw)return $t;$o='';foreach(mb_str_split($t) as $ch){$c=mb_ord($ch,'UTF-8');$o.=($c===10||($c<=65535&&isset($cw[$c*2+1])&&(ord($cw[$c*2])|ord($cw[$c*2+1]))))?$ch:'?';}return $o;}
 public function para(float $h,string $t,int $size=10,string $align='L',array $rgb=[0,0,0]):void {$this->SetFont('DejaVu','',$size);$this->SetTextColor(...$rgb);$this->MultiCell(0,$h,$this->safe($t),0,$align);$this->SetTextColor(0,0,0);}
}
function gszPdfNew(string $footer=''):GszServicePdf {$p=new GszServicePdf();$p->footerText=$footer;$p->SetMargins(18,16,18);$p->SetAutoPageBreak(true,18);$p->SetTitle('Zentrum Gebäudeservice',true);$p->SetCreator('GSZ-Zentrale',true);return $p;}

/** A4-Aushang fürs Treppenhaus: „Schaden melden“ mit großem QR-Code. */
function gszAushangPdf(PDO $pdo,array $prop,string $token):string {
 $url=gszReportUrl($pdo,$token);$company=gszSetting($pdo,'company');$phone=gszSetting($pdo,'company_phone');
 $p=gszPdfNew();$p->SetAutoPageBreak(false);$p->AddPage();
 $p->SetFillColor(11,110,79);$p->Rect(0,0,210,14,'F');$p->SetY(4);$p->SetTextColor(255,255,255);$p->SetFont('DejaVu','',11);$p->Cell(0,6,$p->safe($company.' · Ihr Hausmeisterservice'),0,1,'C');
 $p->SetY(30);$p->para(13,'Schaden oder Mangel im Haus?',28,'C',[20,33,43]);$p->Ln(1);$p->para(9,'Einfach hier melden – in einer Minute.',17,'C',[11,110,79]);
 gszQrPdf($p,$url,52,66,106);
 $p->SetY(176);$p->para(7,'1. QR-Code mit der Handy-Kamera scannen',13,'C');$p->para(7,'2. Kurz beschreiben, gern mit Foto',13,'C');$p->para(7,'3. Absenden – fertig. Wir kümmern uns.',13,'C');
 $p->Ln(4);$p->para(5,'z. B. Licht defekt · Haustür schließt nicht · Wasserfleck · Müllplatz · Schäden im Treppenhaus',10,'C',[91,107,120]);
 $p->SetDrawColor(217,225,231);$p->Line(30,222,180,222);$p->SetY(227);
 $p->para(6,'Haus: '.$prop['name'].($prop['address']!==''?' · '.$prop['address']:''),11,'C');
 if($phone!=='')$p->para(7,'Notfall (Wasserrohrbruch, Sturmschaden): '.$phone,13,'C',[179,38,30]);
 $p->para(5,'Bei Gefahr für Leib und Leben: 112',10,'C',[91,107,120]);
 $p->SetY(266);$p->para(4,'Ohne App, ohne Anmeldung. Ihre Angaben dienen nur der Erledigung der Meldung.',8,'C',[110,120,128]);$p->para(4,$url,7,'C',[150,158,165]);
 return $p->Output('S');
}

/** Bild für das PDF vorbereiten: JPEG/PNG direkt, WebP über GD in ein temporäres JPEG. */
function gszPdfImageFile(string $path,string $mime,array &$tmp):?string {
 if(!is_file($path)||is_link($path))return null;if(in_array($mime,['image/jpeg','image/png'],true))return $path;
 if($mime==='image/webp'&&function_exists('imagecreatefromwebp')){$i=@imagecreatefromwebp($path);if($i){$t=tempnam(sys_get_temp_dir(),'gszimg');if($t&&imagejpeg($i,$t,80)){$tmp[]=$t;return $t;}}}return null;
}
/** Monatsbericht für die Hausverwaltung: erledigte Arbeiten, Zeiten, Fotos, Meldungen, nächste Termine. */
function gszMonthReportPdf(PDO $pdo,array $prop,array $d):string {
 require_once __DIR__.'/photo_storage.php';$company=gszSetting($pdo,'company');$fmt=fn(string $x)=>(new DateTimeImmutable($x))->format('d.m.Y');
 $p=gszPdfNew($company.' · Monatsbericht '.gszMonthName($d['month']).' · '.$prop['name']);$p->AddPage();
 $p->SetFillColor(11,110,79);$p->Rect(0,0,210,10,'F');$p->SetY(16);
 $p->para(6,$company,10,'L',[11,110,79]);$p->para(10,'Monatsbericht '.gszMonthName($d['month']),20);$p->para(6,$prop['name'].($prop['address']!==''?' · '.$prop['address']:''),11,'L',[91,107,120]);$p->Ln(4);
 $h=intdiv($d['minutes'],60);$m=$d['minutes']%60;$open=count(array_filter($d['defects'],fn($x)=>gszDefectState($x+['note_done'=>$x['note_done']??null])!=='erledigt'));
 $by=$p->GetY();$box=function(float $x,string $big,string $small)use($p,$by){$y=$by;$p->SetFillColor(244,247,249);$p->Rect($x,$y,54,20,'F');$p->SetXY($x,$y+2.5);$p->SetFont('DejaVu','',16);$p->Cell(54,8,$p->safe($big),0,2,'C');$p->SetFont('DejaVu','',8.5);$p->SetTextColor(91,107,120);$p->Cell(54,5,$p->safe($small),0,0,'C');$p->SetTextColor(0,0,0);};
 $box(18,(string)$d['visits'],'Einsatztage');$box(78,$d['minutes']?($h.' h '.str_pad((string)$m,2,'0',STR_PAD_LEFT).' min'):'–','dokumentierte Arbeitszeit');$box(138,(string)$open,'offene Meldungen');$p->SetY($by+26);
 $p->para(8,'Erledigte Arbeiten',14);$rows=[];foreach($d['logs'] as $l)$rows[]=[$l['performed_on'],(string)$l['description'],$l['duration_minutes']?((int)$l['duration_minutes']).' min':'',(int)$l['photos']?((int)$l['photos']).' Foto(s)':''];foreach($d['done'] as $t)$rows[]=[(string)$t['day'],(string)$t['title'],'',''];usort($rows,fn($a,$b)=>strcmp($a[0],$b[0]));
 if(!$rows)$p->para(6,'In diesem Monat sind keine erledigten Arbeiten erfasst.',10,'L',[91,107,120]);
 foreach($rows as $r){if($p->GetY()>265)$p->AddPage();$y=$p->GetY();$p->SetFont('DejaVu','',9.5);$p->SetXY(18,$y);$p->Cell(24,6,$fmt($r[0]));$p->SetXY(42,$y);$p->MultiCell(110,6,$p->safe($r[1]),0,'L');$y2=$p->GetY();$p->SetXY(152,$y);$p->SetTextColor(91,107,120);$p->Cell(40,6,$p->safe(trim($r[2].($r[2]&&$r[3]?' · ':'').$r[3])),0,0,'R');$p->SetTextColor(0,0,0);$p->SetY(max($y2,$y+6));$p->SetDrawColor(230,235,239);$p->Line(18,$p->GetY(),192,$p->GetY());}
 $p->Ln(5);$p->para(8,'Meldungen von Mietern',14);
 if(!$d['defects'])$p->para(6,'Keine Meldungen.',10,'L',[91,107,120]);
 foreach($d['defects'] as $x){if($p->GetY()>262)$p->AddPage();$st=GSZ_DEFECT_STATUS[gszDefectState($x)];$p->para(5.5,(new DateTimeImmutable($x['created_at'].' UTC'))->setTimezone(new DateTimeZone('Europe/Berlin'))->format('d.m.Y').' · '.($x['location']!==''?$x['location'].' · ':'').$st,9,'L',[91,107,120]);$p->para(5.5,mb_strimwidth((string)$x['message'],0,400,'…'),10);$p->Ln(1.5);}
 if($d['next']){$p->Ln(4);$p->para(8,'Nächste geplante Termine',14);foreach($d['next'] as $t){if($p->GetY()>268)$p->AddPage();$p->para(5.5,$fmt($t['due_date']).'  '.$t['title'],10);}}
 if($d['photos']){$tmp=[];$p->AddPage();$p->para(8,'Fotos',14);$x=18;$y=$p->GetY()+2;$col=0;$lab=['before'=>'vorher','after'=>'nachher','other'=>'Foto'];
  $dates=array_column($d['logs'],'performed_on','id');
  foreach($d['photos'] as $f){$file=gszPdfImageFile(photoStorageDir().'/'.basename((string)$f['stored_name']),(string)$f['mime_type'],$tmp);if(!$file)continue;
   if($y>215){$p->AddPage();$y=20;}$cx=18+$col*59;$sz=@getimagesize($file);if(!$sz||!$sz[0]||!$sz[1])continue;$k=min(56/$sz[0],42/$sz[1]);$iw=$sz[0]*$k;$ih=$sz[1]*$k;$p->SetFillColor(244,247,249);$p->Rect($cx,$y,56,42,'F');try{$p->Image($file,$cx+(56-$iw)/2,$y+(42-$ih)/2,$iw,$ih,$sz[2]===IMAGETYPE_PNG?'PNG':'JPG');}catch(Throwable $e){continue;}
   $p->SetXY($cx,$y+43);$p->SetFont('DejaVu','',8);$p->SetTextColor(91,107,120);$p->Cell(56,4,$p->safe(($lab[$f['label']]??'Foto').(isset($dates[$f['work_log_id']])?' · '.$fmt($dates[$f['work_log_id']]):'')));$p->SetTextColor(0,0,0);
   if(++$col===3){$col=0;$y+=52;}}
  foreach($tmp as $t)@unlink($t);}
 return $p->Output('S');
}

/** A4-Bogen mit 10 Bewertungskarten (85 × 55 mm, 2 × 5) und Schnittmarken. */
function gszReviewCardsPdf(PDO $pdo):string {
 $url=gszSetting($pdo,'review_url');if(!preg_match('~^https://~',$url))throw new RuntimeException('Bitte zuerst den Google-Bewertungslink unter Anfragen → Einstellungen eintragen.');
 $company=gszSetting($pdo,'company');$phone=gszSetting($pdo,'company_phone');$p=gszPdfNew();$p->SetAutoPageBreak(false);$p->SetMargins(0,0,0);$p->AddPage();
 $w=85;$h=55;$x0=(210-2*$w)/2;$y0=(297-5*$h)/2;
 for($r=0;$r<5;$r++)for($c=0;$c<2;$c++){$x=$x0+$c*$w;$y=$y0+$r*$h;
  $p->SetFillColor(11,110,79);$p->Rect($x,$y,3,$h,'F');
  $p->SetXY($x+7,$y+6);$p->SetFont('DejaVu','',7.5);$p->SetTextColor(11,110,79);$p->Cell(44,4,$p->safe($company),0,2);
  $p->SetXY($x+7,$y+12);$p->SetFont('DejaVu','',12.5);$p->SetTextColor(20,33,43);$p->MultiCell(46,5.6,$p->safe("Zufrieden mit\nunserer Arbeit?"),0,'L');
  $p->SetXY($x+7,$y+25);$p->SetFont('DejaVu','',14);$p->SetTextColor(232,168,0);$p->Cell(44,7,'★★★★★',0,2);
  $p->SetXY($x+7,$y+33);$p->SetFont('DejaVu','',8);$p->SetTextColor(70,80,88);$p->MultiCell(44,3.8,$p->safe("Wir freuen uns über Ihre Google-Bewertung: QR-Code scannen."),0,'L');
  if($phone!==''){$p->SetXY($x+7,$y+$h-8);$p->SetFont('DejaVu','',7.5);$p->SetTextColor(91,107,120);$p->Cell(46,4,$p->safe('Tel. '.$phone.' · 24/7 Schlüsseldienst'),0,0);}
  gszQrPdf($p,$url,$x+$w-33,$y+($h-30)/2,30);$p->SetTextColor(0,0,0);}
 $p->SetDrawColor(160,160,160);$p->SetLineWidth(0.15);
 for($c=0;$c<=2;$c++){$x=$x0+$c*$w;$p->Line($x,$y0-8,$x,$y0-3);$p->Line($x,$y0+5*$h+3,$x,$y0+5*$h+8);}
 for($r=0;$r<=5;$r++){$y=$y0+$r*$h;$p->Line($x0-8,$y,$x0-3,$y);$p->Line($x0+2*$w+3,$y,$x0+2*$w+8,$y);}
 return $p->Output('S');
}
