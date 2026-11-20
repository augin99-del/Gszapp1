# Drittbibliotheken / reproduzierbare Herkunft

Mehrseitige PDF-Erzeugung: tFPDF 1.34, Setasign-Spiegel der offiziellen Releases, Commit `69b06444b929e0d54ffbb5efcadfe97ecbad831b`.

Quelle: https://github.com/Setasign/tFPDF/tree/69b06444b929e0d54ffbb5efcadfe97ecbad831b

Unverändert mitgeliefert: tfpdf.php, font/unifont/ttfonts.php, DejaVuSans.ttf, DejaVu-Lizenz, README-Dateien und composer.json. Lizenz LGPL-2.1 laut Paketmetadaten; Original-Copyrightheader erhalten. Vollständiger Lizenztext unter private/vendor/tfpdf/LICENSE-LGPL-2.1.txt (Quelle https://www.gnu.org/licenses/old-licenses/lgpl-2.1.txt). Schriftlizenz separat private/vendor/tfpdf/font/unifont/DejaVu_LICENSE.txt.

Eigene Anbindung in private/invoice_pdf.php getrennt von unverändertem Bibliothekscode. Keine Composer-/Node-/Python-Laufzeit auf STRATO nötig. PHP mbstring/zlib erforderlich. Cache und Schriftkopie werden ausschließlich unter private/uploads/pdf_fonts erzeugt und sind nicht Teil des Lieferpakets. Beispiel-PDFs, Beispieladressen, VCS-Dateien und unnötige Schriftvarianten des Upstream-Pakets nicht übernommen. Es wird kein externer Dienst bei PDF-Erzeugung aufgerufen. Eine signierte Supply-Chain-/CVE-Abnahme wird nicht behauptet.


## PHPMailer SMTP7.2.0

Offizielle Quelle https://github.com/PHPMailer/PHPMailer, Commit f5eb50148734d03bbbb81825c722c1889636d51a. Unveränderte src/SMTP.php, LICENSE (LGPL-2.1-only), composer.json und README.md unter private/vendor/phpmailer. Download-ZIP SHA256177d83c8413387c785eabc2b76205d80c1888cce0d1669f5335dce69066f8bec. Kein Composer/Node/Python auf Produktion erforderlich. Eigener dünner Adapter unter private/smtp_phpmailer.php; Änderungen außerhalb der Bibliothek. Debug deaktiviert, Lizenzhinweise erhalten. OPEN_SOURCE_PRUEFUNG.md enthält Alternativen und Grenzen.
