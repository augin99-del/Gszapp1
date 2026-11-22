# Tatsächlicher Teststatus – Komplett370,13.–14.11.2026

**Lokal geprüfter Installationskandidat, keine vollständige Produktivfreigabe.** Linux, PHP8.4.24, MariaDB11.8.6, Chromium/Playwright390/1440px; nur isolierte synthetische Daten. Keine echten Postfach-/Bankanmeldungen oder produktiven Datenänderungen.

| Prüfung | Status / tatsächlich ausgeführt |
|---|---|
| Bestehende PHP-/DB-/HTTP-Regression | PASS33/33 Suiten; `/workspace/gsz-tools/complete370-regression.json` |
| Neue Konfiguration/Sicherheit | PASS22 Assertions, `GSZ_TEST_PHP=… php tests/test_setup_config.php`: wirklicher PHP-Syntaxfehler, verschlüsselte Konfiguration, leere überschreibende Umgebung, getrennte Kandidaten, Besitznachweis, Symlinks, Webroot und Konfigurationsdrift |
| Schemaerkennung/Upgrades | PASS9/9 Szenarien,568 Assertions: leer/192/200/210/220/320/330/350/360→370, über tests/test_setup_schema.php mit eigenen leeren gsz_test_setup_*-Datenbanken und GSZ_TEST_SOURCE_SCHEMA; Daten-/Ownererhalt, Wiederholung, Fremdstrukturen, Ledgerlücken, Sperren und Teilmigrationen |
| Browser-Einrichtung leer | PASS40 Prüfungen: tatsächliche fehlerhafte config.php und leere GSZ_DB_PASS-Überschreibung, private Codeeingabe, CSRF, falsches DB-Passwort, Kandidat ohne Aktivierung, ausdrückliche Migration, neuer Owner, verschlüsselte Speicherung, Rechtekorrektur, Abschluss, Login und Module |
| Browser-Einrichtung vorhandene76Tabellen | PASS40 Prüfungen: Quelle320 erkannt, bestehender Owner erneut bestätigt, gesicherte/restoregeprüfte Testkopie über Browser auf370 aktualisiert, kein Adminreset, lokale Module und Systemcheck erreichbar |
| Bankingadapter | PASS73 DB-Prüfungen in tests/test_banking_connections.php; OAuth-State/Replay/Ownerbindung, AES-GCM, drei Konten, echte RSA-JWT-Signatur gegen Mock, Pagination/Dubletten/Quellkonflikte/Trennung, keine automatischen Buchungen |
| Bankingbrowser | PASS36 Prüfungen390/1440, Anmeldung/CSRF/Callback/gesperrte Livebedienung; keine echte Autorisierung |
| Mailsetup/Fallback | PASS33 Assertions in tests/test_setup_mail.php; gebündelter tatsächlicher IMAP-Protokollparser und Mockantworten |
| Echter lokaler TLS-Mailtest | PASS7 Szenarien, tests/test_setup_mail_loopback.py: gültiges TLS, falsche Anmeldung, Größenlimit, fehlender Body, UIDVALIDITY-Wechsel, falsche CA/Hostname; keine Credentials vor bestätigtem TLS, keine echte Mail |
| Vorhandene Mailtests | PASS Mailcore25/native-Shim9 sowie DB-/HTTP-Mailregressionen in33er-Suite |
| Vollständige PHP-Syntaxprüfung | PASS2116 PHP-Dateien aus Anwendung/Tests und gebündeltem Vendor, keine Syntaxfehler |
| Lizenz-/Abhängigkeiten |37 gepinnte Mailabhängigkeiten, MIT/BSD-2-Clause; Composer-Audit ohne gemeldete Advisories/abandoned zum Abrufzeitpunkt. Vollständiger Vendor gebündelt;1926Vendor-PHP-Dateien syntaktisch geprüft |
| Abfall360 | Vorheriger93DB-/50Browser-Nachweis erhalten; bestehender Parser/Scheduler/Planungs-DB-Regression erneut in33er-Suite. Neue objektbezogene Dienste nicht neu geschrieben |
| Aktuelles vollständiges ZIP | Zusätzliche Prüfung nach Paketbau wird unten protokolliert. Codeintegrität COMPLETE370 und Secret-/CRC-/Pfadscan erforderlich |
| Echte STRATO-Installation | NOT TESTED: keine FTP-/Hosting-/DB-Konfiguration oder PHP-Ausführung auf dem Server verfügbar. Öffentliche Webantwort ist kein Diagnosebeweis für internen Host/PDO/Dateipfad |
| Nativer Abfall-PHP-HTTPS-Abruf auf STRATO | NOT TESTED. Geschützter tatsächlicher PHP-Test im Assistenten vorhanden. Direkte Sockets in dieser Entwicklungsnetzkonfiguration bisher BLOCKED; proxierte amtliche Quellantworten aus Abfall360 wurden wirklich geprüft |
| Echtes STRATO-IMAP/SMTP, Empfang/Versand/Gesendet | NOT TESTED / externe Einrichtung und Versandfreigabe erforderlich. Anmeldungstest allein ist kein Versand-/Empfangsnachweis |
| Echte Bankautorisierung/Bankunterstützung/Kosten | BLOCKED: Anbieterzugang und konkrete Freigaben fehlen. Kein kostenloser umfassender Bankzugriff behauptet |
| Cron auf echtem STRATO | NOT TESTED: Tarif, Ausführungsrechte/-limits und tatsächliche Jobläufe offen; sichere manuelle Aktualisierung vorhanden |
| OCR/E-Rechnung/Steuer-/GoBD-Freigabe | NICHT IMPLEMENTIERT bzw. NICHT ABGENOMMEN im umfassenden gewünschten Umfang. Bestehende manuelle Belege/CSV/PDF und Sperren bleiben nutzbar |

## Reproduktion

Alle bestehenden tests/-Dateien sind enthalten. DB-Tests verweigern fremde/produktive DSNs; pro Lauf eine eigene leere lokale gsz_test_*-DB verwenden. Beispiele:

```
GSZ_TEST_DSN='mysql:unix_socket=/lokaler/test.sock;dbname=gsz_test_setup_upgrade320' GSZ_TEST_SOURCE_SCHEMA=320 php tests/test_setup_schema.php
GSZ_TEST_PHP=/lokales/php php tests/test_setup_config.php
php tests/test_setup_mail.php
GSZ_TEST_PHP=/lokales/php python3 tests/test_setup_mail_loopback.py
GSZ_TEST_DSN='mysql:unix_socket=/lokaler/test.sock;dbname=gsz_test_banking_new' php tests/test_banking_connections.php
```

Neue Browserprüfung `tests/test_setup_browser.cjs` benötigt getrennten lokalen HTTPS-Webserver, eine dedizierte leere oder historische Testdatenbank und0600-Fixturedatei außerhalb der Anwendung. GSZ_TEST_URL muss loopback sein, GSZ_TEST_SETUP_ROOT und GSZ_TEST_SETUP_FIXTURE werden explizit geprüft. Lokale Vorbereitung dieser Sitzung: `/workspace/gsz-tools/start_complete370.py`, `start_complete370_existing.py`; Testausführung mit Node/Playwright und NODE_PATH=/opt/codex/cua_node/lib/node_modules. Diese Werkzeuge sind nur lokal; auf STRATO weder Node/Python noch Composer nötig. Der Paket-Installationstest verwendet dieselbe Prüfung direkt gegen das entpackte vollständige ZIP.

Die33er-Regression umfasst Geld, Schema, PDF, Wiederholungen, Planung, Abfall/Routing, Mail, Finanzen, Operations, Kasse/Anlagen, Immobilien/Nebenkosten, Vault, Migrationen, Installation, Beleg-/Forderungs-/Finanzwiederholungs- und Mail-HTTP-/DB-Tests. Detailausgaben bleiben lokal außerhalb des Pakets, ohne reale Kontodaten.

Während Entwicklung behoben: Setup lud private Hilfsfunktionen zunächst erst nach dem Configloader-Aufruf; nun explizite Bootstrap-Abhängigkeit. Ein bestehender Socket-Testpfad wurde fälschlich durch den Platzhalter-Hostname blockiert; Hostprüfung gilt nun nur für TCP. Nicht eindeutige Testannahme zur Reihenfolge von Owner-/Migrationsfreigaben berichtigt. Neue Vendor-Locale-Dateien mit @ sind im sicheren relativen Integritätsmanifest erlaubt. Laufende Dateiänderungen verursachten erwartungsgemäß einen Integritätsblocker im ersten Installationstest; anschließend Manifest regeneriert und33/33 bestanden. Keine Ausblendung fehlgeschlagener Geschäftstests.

## Abgeschlossene Prüfung des vollständigen Installationspakets

PASS: Das erzeugte GSZ-ZENTRALE-KOMPLETT-STRATO.zip wurde vollständig in einen neuen isolierten Webordner entpackt. Dessen tatsächliche PHP-Dateien wurden über einen lokalen HTTPS-Server ausgeführt. Browser-End-to-End40Prüfungen bestanden; absichtlich defekte Alt-config.php, leere GSZ_DB_PASS und zusätzlich GSZ_MAINTENANCE=true wurden über den Assistenten ohne PHP-Dateibearbeitung behoben. Neue leere DB über GUI auf370/85Tabellen eingerichtet, individueller Owner, Codeentzug, Anmeldung, Systemcheck0Blocker und Module bestätigt. Keine bloße Prüfung der Entwicklungsdateien.

PASS: Die vorhandene76Tabellen-Testkopie320 wurde vor dem Upgrade gedumpt und in eine separate Testdatenbank bytegleich wiederhergestellt. Nach dem grafischen Upgrade blieb der ursprüngliche Ownerhash unverändert; eine erledigte, gesperrte Aufgabe mit Notiz und Versionsstand7 blieb erhalten. Zusätzlich85Tabellen nach Upgrade vollständig gesichert/wiederhergestellt:103289Bytes logischer Dump identisch; verschlüsselte Konfiguration, separater Schlüssel und Lockdatei gemeinsam bytegleich wiederhergestellt. Keine Dumps im Release.

PASS: Archiv-CRC, relative Pfade, keine Symlinks, vollständiger Basiserhalt, historische SQLs unverändert, bekannte Originalgeheimnisse und übliche Schlüsselmuster geprüft. Die ausdrücklich vom Benutzer vorgegebene öffentliche Postfachadresse ist ein erlaubter Konfigurationsvorschlag, kein geheimes Passwort; keine anderen Originalzugänge übernommen. Manifestprüfungen bestätigen2266 ausgelieferte Code-/Asset-/Vendordateien. Abschließende Dokumentations-/Testdateiänderungen verändern diese bereits aus dem ZIP installierten Anwendungsbytes nicht.

Alle Live-Abnahmen bleiben wie oben NOT TESTED/BLOCKED. Insbesondere ist der echte interne STRATO-Datenbankfehler nicht durch Zugriff auf die private Serverkonfiguration nachgewiesen; der Assistent liefert hierfür die geschützte konkrete Diagnose. Es wurde keine produktive Installation vorgenommen.

Abschließend auf dem tatsächlich aus dem ZIP installierten System: `tests/test_v1_browser.cjs` PASS250Prüfungen aller Module390/1440px; `tests/test_golive_browser.cjs` PASS48Praxistests, protokollierter Berliner Geschäftstag14.11.2026. Beide mit dem im Assistenten individuell angelegten synthetischen Inhaberkonto.
