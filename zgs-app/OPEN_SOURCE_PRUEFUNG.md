# Aktueller FINAL-Stand 12.11.2026 / Schema350

Maßgeblich: INSTALLATION-STRATO.md, TESTSTATUS-FINAL.md, MODULE-STATUS.md, CHANGELOG-FINAL.md, INTEGRATIONEN-FINAL.md und CODEX_FORTSETZUNG.md. Neu: täglicheRegeln, integriertesDashboard/Navigation, CAMT/Kontentrennung/Review, OfflineSEO, geschütztesBestandsupdate/Dateiintegrität. Schema350/82Tabellen; keineProduktivinstallation, echteMail/Bank/Cron/Objektkalender weiter nichtnachgewiesen. Aussagenzu330/340nachfolgend sindHistorie. LiveSEO-/GSC-/GBP-Adapter,OCRundvalidierteE-Rechnung nichtalsfertigausgeben.

---

# Ergänzung Version1.0 –11.11.2026

Vorhandene PHPMailer7.2.0/LGPL2.1 und tFPDF unverändert weiterverwendet; eigener SMTP-Adapter jetzt465implizitesTLS sowie587STARTTLS, Zertifikatsprüfung bleibt an. Historische Einschränkung auf587 unten ist überholt. Webklex6.2.0/MIT bleibt ein nicht integrierter Kandidat; zusätzliche Abhängigkeiten/Hostingextensions vor einer Übernahme prüfen. Kein neuer Fremdcode oder monatlicher Dienst für diese Erweiterung eingebaut. Native IMAP im tatsächlichen STRATO-PHP8.4 muss separat vorhanden sein. Öffentliche Quellen/Einrichtung INTEGRATIONEN_V1.md.

---

# Ergänzende Immobilien-/Nebenkostenprüfung, 10.11.2026

| Kandidat | Geprüfte offizielle Quellen | Lizenz/Kompatibilität/Entscheidung |
|---|---|---|
| Mietfuchs | github.com/speedone/mietfuchs, Projektwebsite, Lizenzdatei | Widersprüchliche AGPL-3-/MIT-Angaben; keine sichere identische Versions-/Lizenzbasis, KEIN Code kopiert. Gesamtanwendung statt isolierter PHP-Rechenkern. |
| BitNike/immobilienverwalter | offizielles GitHub-Projekt/Lizenz | AGPL-Gesamtanwendung; kein Code übernommen/keine zweite Datenbank integriert. Separate Hosting-/Abhängigkeiten und Copyleft wären gesondert zu prüfen. |
| Brick/Money | github.com/brick/money, Paketmanifest | MIT, PHP^8.2, zusätzliche brick/math-/Cache-Abhängigkeiten; für vorhandene begrenzte ganzzahlige Centverarbeitung kein Integrationsvorteil, nicht übernommen. |
| Dolibarr | offizielles Projekt/Lizenz | GPL-3-Gesamtanwendung, keine parallele Kunden-/Buchhaltungswahrheit eingeführt. |
| vorhandenes tFPDF | bereits mitgelieferte Lizenz-/Fontdateien | Bestehende Unicode-/Mehrseitenfunktion wiederverwendet, keine zusätzliche PDF-Bibliothek/Runtime. |

Entscheidung: vorhandene GSZ-Komponenten + eigener isolierter Immobilien-/Umlagekern, keine Übernahme fremder Anwendungsfunktionen. mathematische Funktionalität186 Tests, Integration separat. Keine nahaus-Oberfläche/-Quelltexte kopiert. Weitere Details und konkrete Quelllinks ARCHITEKTUR_NEBENKOSTEN_KERN.md. Papierlose Vorgänge/Banking/Mail weiterhin nach eigenem geprüftem Integrationspfad; unbelegte Lizenz-/API-Eigenschaften nicht als freigegeben behandeln.

---

# Bibliotheksprüfung –10.11.2026

Offizielle Packagist-Paketmanifeste gelesen. Metadaten sind keine umfassende Sicherheits-/Hostingabnahme. Es wurden keine laufenden Kosten aktiviert.

| Paket | Geprüfte Version / Datum | Lizenz | PHP/Extensions | Entscheidung |
|---|---|---|---|---|
| phpmailer/phpmailer | v7.2.0 / 2026-11-07 | LGPL-2.1-only | {'php': '>=5.5.0', 'ext-ctype': '*', 'ext-filter': '*', 'ext-hash': '*'} | SMTP-Komponente integriert, lokale TLS-Tests; bestehendes MIME/Entwurfsmodell behalten |
| webklex/php-imap | 6.2.0 / 2025-04-25 | MIT | {'php': '^8.0.2', 'ext-openssl': '*', 'ext-json': '*', 'ext-mbstring': '*', 'ext-iconv': '*', 'ext-libxml': '*', 'ext-zip': '*', 'ext-fileinfo': '*', 'nesbot/carbon': '^2.62.1 / ^3.2.4', 'symfony/http-foundation': '>=2.8.0', 'illuminate/pagination': '>=5.0.0'} | Geeigneter Kandidat ohne native IMAP-Pflicht; Carbon/Illuminate/Symfony und ZIP erst vollständig prüfen, noch nicht integrieren |
| genkgo/camt | 2.10.3 / 2025-08-26 | MIT | {'php': '^8.1', 'ext-dom': '*', 'ext-libxml': '*', 'ext-simplexml': '*', 'moneyphp/money': '^4.6', 'jschaedl/iban-validation': '^2.5'} | Bevorzugter CAMT-Kandidat; DOM/SimpleXML lokal derzeit fehlend, sichere Testumgebung/abhängige Money/IBAN-Lizenzen noch prüfen |
| dompdf/dompdf | v3.1.6 / 2026-07-20 | LGPL-2.1 | {'php': '^7.1  /  /  ^8.0', 'ext-dom': '*', 'ext-mbstring': '*', 'masterminds/html5': '^2.0', 'dompdf/php-font-lib': '^1.0.0', 'dompdf/php-svg-lib': '^1.0.0'} | Nicht zusätzlich übernehmen: vorhandenes tFPDF kann bereits Mehrseiten/Unicode; vermeidet zweiten PDF-Stack |
| phpoffice/phpspreadsheet | 5.11.0 / 2026-11-08 | MIT | {'php': '^8.2', 'ext-ctype': '*', 'ext-dom': '*', 'ext-fileinfo': '*', 'ext-filter': '*', 'ext-gd': '*', 'ext-iconv': '*', 'ext-libxml': '*', 'ext-mbstring': '*', 'ext-simplexml': '*', 'ext-xml': '*', 'ext-xmlreader': '*', 'ext-xmlwriter': '*', 'ext-zip': '*', 'ext-zlib': '*', 'composer/pcre': '^1 /  / ^2 /  / ^3', 'maennchen/zipstream-php': '^2.1  /  /  ^3.0', 'markbaker/complex': '^3.0', 'markbaker/matrix': '^3.0', 'phpoffice/wmf': '^0.2.1', 'psr/simple-cache': '^1.0  /  /  ^2.0  /  /  ^3.0'} | Für CSV unnötig viele Extensions/Abhängigkeiten; XLSX bei tatsächlichem Bedarf gesondert prüfen |
| nemiah/php-fints | 4.1.0 / 2026-05-10 | MIT | {'php': '>=8.0', 'psr/log': '^1 / ^2 / ^3', 'ext-curl': '*', 'ext-mbstring': '*'} | Noch nicht aktivieren: Bankunterstützung, SCA/Auth, Herstellerregistrierung/Bedingungen und Gebühren unklar; keine PIN/TAN anfordern |
| league/csv | 9.29.0 / 2026-10-28 | MIT | {'php': '^8.2.0', 'ext-filter': '*'} | Geeigneter Kandidat; begrenzter neutraler CSV-Workflow bereits geprüft, kein Austausch allein wegen Paketverfügbarkeit |
| smalot/pdfparser | v2.13.1 / 2026-10-13 | LGPL-3.0 | {'php': '>=7.1', 'symfony/polyfill-mbstring': '^1.18', 'ext-zlib': '*', 'ext-iconv': '*'} | Text-PDF-Extraktionskandidat, keine OCR; Parserressourcen/untrusted PDFs und Polyfill-Lizenz erst prüfen |

Kommerzielle Nutzung ist bei den genannten MIT/LGPL-Lizenzen unter deren Bedingungen grundsätzlich möglich; Hinweise/Lizenzdateien erhalten und LGPL-Weitergabevorgaben beachten. Kein fremder Code ohne Lizenzprüfung kopiert.

Weitere vom Nutzer genannte Projekte (Dolibarr, Mietfuchs, Paperless-ngx, Tesseract, n8n, VROOM/ORS) sind noch nicht vollständig anhand ihrer aktuellen offiziellen Lizenzen/Hostinganforderungen geprüft. Keine Integration oder Kostenfreiheit eines laufenden Bank-/Routingdienstes behaupten. Separate Anwendungen nicht mit GSZ-Tabellen vermischen.

PHPMailer: unveränderte SMTP.php aus v7.2.0, Commit f5eb50148734d03bbbb81825c722c1889636d51a, LGPL-2.1-only und Originaldateien enthalten. Eigener Adapter erlaubt nur TLS/587 und PLAIN nach TLS; debug0, keine SMTP-Transkripte. Native Rückfalloption konfigurierbar; Standard phpmailer. Kein Composer auf Produktivserver notwendig. Paketversion/Datum/Anforderungen sind tatsächlich abgefragte Angaben, keine Aussage zu allen zukünftigen Sicherheitsmeldungen.
