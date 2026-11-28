<?php
declare(strict_types=1);
// Mails: Links aus HTML-Mails bleiben erhalten und werden in der Anzeige anklickbar – ohne fremdes HTML/Script. Ohne Datenbank.
set_error_handler(static function($s,$m,$f,$l){if(error_reporting()&$s)throw new ErrorException($m,0,$s,$f,$l);return false;});
require dirname(__DIR__).'/private/mail_mime.php';require dirname(__DIR__).'/private/mail_ui.php';
$n=0;function ml(bool $v,string $s):void{global $n;$n++;if(!$v)throw new RuntimeException($s);}
$raw="From: Portal <noreply@example.invalid>\r\nSubject: Rechnung\r\nContent-Type: text/html; charset=UTF-8\r\n\r\n<p>Ihre Rechnung: <a href=\"https://portal.example.invalid/r?id=7&amp;x=1\">Hier klicken</a></p><p><a href=\"https://www.example.invalid/\">www.example.invalid</a> <a href=\"mailto:info@example.invalid?subject=x\">Schreiben Sie uns</a> <a href=\"javascript:alert(1)\">böse</a> <a href='https://t.example.invalid/x'><img src=x></a><script>alert(2)</script></p>";
$b=gszMimeParse($raw)['body'];
ml(str_contains($b,'Hier klicken (https://portal.example.invalid/r?id=7&x=1)'),'html link target kept');
ml(str_contains($b,'https://www.example.invalid/')&&!str_contains($b,'www.example.invalid (https'),'link text = url not duplicated');
ml(str_contains($b,'Schreiben Sie uns (info@example.invalid)'),'mailto kept as address');
ml(!str_contains($b,'javascript')&&str_contains($b,'böse'),'javascript link dropped, text kept');ml(str_contains($b,'https://t.example.invalid/x'),'image link becomes url');ml(!str_contains($b,'alert(2)'),'script removed');
$plain="From: a@example.invalid\r\nSubject: x\r\nContent-Type: text/plain; charset=UTF-8\r\n\r\nLink: <https://example.invalid/a> ok";ml(str_contains(gszMimeParse($plain)['body'],'<https://example.invalid/a>'),'plain text unchanged');
$h=gszMailLinkify("Siehe https://www.example.invalid/pfad?a=1&b=2. Oder www.test.invalid, Mail an info@example.invalid! <script>alert(1)</script> (https://a.example.invalid)");
ml(str_contains($h,'<a href="https://www.example.invalid/pfad?a=1&amp;b=2" target="_blank" rel="noopener noreferrer nofollow"'),'https link, trailing dot outside');ml(str_contains($h,'</a>. Oder'),'trailing period not in link');
ml(str_contains($h,'href="https://www.test.invalid"'),'www gets https');ml(str_contains($h,'href="compose.php?to=info%40example.invalid"'),'email opens compose');
ml(str_contains($h,'&lt;script&gt;')&&!str_contains($h,'<script>'),'html escaped');ml(str_contains($h,'(<a href="https://a.example.invalid"')&&str_contains($h,'</a>)'),'parentheses outside link');
ml(!str_contains(gszMailLinkify('javascript:alert(1) data:text/html,x'),'<a '),'no links for other schemes');
$long=gszMailLinkify('https://example.invalid/'.str_repeat('a',200));ml(str_contains($long,'…')&&str_contains($long,'title="https://example.invalid/'.str_repeat('a',200).'"'),'long url shortened, full in title');
ml(gszMailLinkify('Kein Link hier & da')==='Kein Link hier &amp; da','plain escaped');
echo "Mail links PASS ($n checks).\n";
