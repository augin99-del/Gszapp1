# Testbericht Version1.0 –11.11.2026

Statusbegriffe: **PASS** tatsächlich ausgeführt/bestanden, **FAIL** tatsächlich fehlgeschlagen, **BLOCKED** fehlende zwingende Voraussetzung, **NOT TESTED** nicht ausgeführt. Lokale Tests sind keine produktive oder steuerliche Abnahme.

## Gemessene Umgebung

Linux isolierter Workspace, PHP8.4.24(64Bit), MariaDB11.8.6 mit Unixsocket/InnoDB/utf8mb4, PDO/mysql/mbstring/iconv/fileinfo/openssl/sodium/session/zlib vorhanden. Native IMAP, DOM/SimpleXML/curl/zip fehlen lokal; IMAP-Aufrufe über isolierten Shim/Mock geprüft. Chromium mit Playwright/Node, Python/OpenSSL/poppler nur lokal. Webtests verwenden eigene HTTPS-Loopback-Proxys, sichere Sessioncookies und synthetische owner@example.invalid/Kunden/Adressen. Keine produktiven Kontodaten. STRATO-PHP8.4.27 ist Nutzerangabe, hier nicht gemessen.

## Ergebnisse der neuen Version

| Prüfung | Ergebnis | Nachweis/Grenze |
|---|---|---|
| PHP-Syntax | PASS |155 PHP-Dateien, kein Syntaxfehler |
| Planungskern v1 | PASS |46 Assertions: deterministische Woche, Kapazität, Mo–Mi-Treppenhäuser, Mo–Fr-Außenanlage, Fixtermin13–14:30, Sonntagssperre, Februar/Schaltjahr, Abschlussmodus, korrekte Prognose |
| Datenbank v1 | PASS |21 Assertions: Abschlussdatum/3Monate, keine Doppelgenerierung, Wiederholung/Bestandsschutz, Vorschau/Replay, row_version/Staleformular, BCC/Audit, parallele Migration/Historienlücke/beschädigte protokollierte Struktur |
| Kalender/Automation/Konfigurationsschutz | PASS |28 Assertions: HTTP/Chunkgrenzen/Redirect/SSRF/TLS-Zielregeln, Review/Dublettenschutz/Änderungskonflikt, neue Versatztage, geprüfte Mapping-Wiederverwendung, Remapping ohne Aufgabenänderung, sichere Quellenfehler, Cron-Sperre/Fehler/Persistenz,0600/Symlink/CLI-Webroot |
| SMTP native und PHPMailer | PASS |23 synthetische TLS-Szenarien auf Loopback587/465, Zertifikat/Authentifizierung/Empfänger/DATA/unklare Annahme/Verbindungsende; kein externer Versand |
| Kern-Praxistag | PASS |48 Browserprüfungen: Login/Kundenobjekt/Notiz/Originaldatei/Regel/Müll/Plan/Fixtermin/Abschließen/Verschieben/Neuanmeldung/Persistenz/390px |
| Gemeinsame Oberfläche | PASS |218 Browserprüfungen:26Module bei390/1440px, einheitlicher Rahmen/Farbpalette, keine horizontalen Dokumentüberläufe, Quartalsaufgabe/Erledigung/Neuplanungsvorschlag |
| Kalender-Review/Mobil/manueller Hintergrundlauf | PASS |10 Browserprüfungen: unbekannt→explizites Remapping, offene/übernommene Vorschau getrennt, erneute Bestätigung ohne Dublette, manueller Vorschlag ohne automatische Übernahme |
| Bestehende PHP-/DB-Regressionen | PASS |30 Testreihen unten, keine übersprungen |
| Bestehende Browserregressionen | PASS |Planung11, Betrieb29, Phase3=17, Gesamt44, Sicherheit11, Immobilien27;139Prüfungen |
| Installation/Upgrade | PASS |Fresh340/78Tabellen; leere/falsche/nichtleereDB, Kontoerhalt, wiederholte Migration. v260→340:40historische Tabellen/Spaltenwerte sowie Original-PDF unverändert |
| Backup/Restore DB+Privatdateien | PASS |78Tabellen,170952Bytes logischer Dump identisch,87private Testdateien hashidentisch,1Dokumentreferenz geprüft, erledigter/verschobener Zustand erhalten |
| PHP-Laufzeitfehler während finalem Kern-/Modulbrowserlauf | PASS |privates Fehlerlog nicht erzeugt; keine JavaScriptfehler im48-Praxistest |
| Echtes IMAP/SMTP/Gesendet/Empfang | BLOCKED |keine aktuelle sichere Mailkonfiguration, native IMAP lokal fehlt; Mock-/TLS-PASS ersetzt keinen Echtnachweis |
| Adressgenaue amtliche Abo-Synchronisierung | BLOCKED |keine tatsächliche freigegebene Objekt-ICS-URL eingerichtet; nur öffentliche Angebotsrecherche, Parser/Import synthetisch geprüft |
| Echter STRATO-Konfigurationsblocker/Cron/Server | BLOCKED |kein sicherer realer Hostingzugriff; Werkzeug implementiert, kein vorgetäuschter Serverfix |
| iPhone/Safari auf echtem Gerät | NOT TESTED |390pxChromium/Touchsimulation ist kein iOS-Gerätenachweis |
| Live-Deployment/echte Mail/Bankaktion | NOT TESTED |absichtlich nicht ausgeführt; keine separate Freigabe |

## Bestehende Testreihen – tatsächlich ausgeführte Befehle

PHP bezeichnet unten `/workspace/gsz-tools/bin/php`; CWD war `/workspace/gsz-inbetriebnahme/zgs-app`. DB-Tests bekommen jeweils eine **neue leere synthetische** lokale Datenbank über GSZ_TEST_DSN; keine Produktivdatenbank/kein Schema zurückgesetzt. Testdateien verweigern ungeeignete Namen bzw. nichtleere Voraussetzungen. Der lokale Sitzungsrunner `/workspace/gsz-tools/run_v1_final_regression.py` führte die folgenden Befehle aus (nicht Laufzeitabhängigkeit des Produkts).

| Befehl | Status | Tatsächliche Ausgabe |
|---|---|---|
| `php tests/test_money.php` | PASS | Money/CSV smoke tests passed |
| `php tests/test_schema.php` | PASS | Schema static checks passed (not a database integration test). |
| `php tests/test_invoice_pdf.php` | PASS | Invoice PDF tests passed (header, embedded font, 80 positions/multiple pages, size and unsupported-glyph limits). |
| `php tests/test_recurring.php` | PASS | Recurring/catalog/config tests passed (73 assertions). |
| `php tests/test_planning.php` | PASS | Planning unit tests passed (24 assertions). |
| `php tests/test_waste_routing.php` | PASS | Waste/routing unit tests passed (32 assertions). |
| `php tests/test_mail_core.php` | PASS | Mail/security unit tests passed (25 assertions). |
| `php tests/test_finance_core.php` | PASS | Finance unit tests passed (18 assertions). |
| `php tests/test_operations.php` | PASS | Operations unit tests passed (18 assertions). |
| `php tests/test_finance_recurrences.php` | PASS | Finance recurrence unit tests passed (8 assertions). |
| `php tests/test_cash_assets.php` | PASS | Cash/assets unit tests passed (16 assertions). |
| `php tests/test_estate_core.php` | PASS | Estate math tests passed (9 assertions). |
| `php tests/test_estate_calculation.php` | PASS | Estate calculation tests passed (186 assertions). |
| `php tests/test_secret_vault.php` | PASS | Secret vault tests passed (14 assertions). |
| `php tests/test_mail_recovery_imap.php` | PASS | Mail recovery native IMAP shim tests passed (9 assertions). |
| `php tests/test_phase1_database.php` | PASS | MariaDB phase 1 integration passed (39 assertions; 28 seeded rules). |
| `php tests/test_planning_database.php` | PASS | Planning database tests passed (10 assertions; 10 tasks). |
| `php tests/test_phase3_database.php` | PASS | Phase 3 database tests passed (21 assertions). |
| `php tests/test_finance_database.php` | PASS | Finance database tests passed (25 assertions). |
| `php tests/test_business_documents.php` | PASS | Business-document database tests passed (26 assertions). |
| `php tests/test_operations_database.php` | PASS | Operations database tests passed (31 assertions). |
| `php tests/test_receivables_database.php` | PASS | Receivables database tests passed (24 assertions). |
| `php tests/test_finance_recurrences_database.php` | PASS | Finance recurrence database tests passed (22 assertions). |
| `php tests/test_cash_assets_database.php` | PASS | Cash/assets database tests passed (39 assertions). |
| `php tests/test_estate_database.php` | PASS | Estate database tests passed (42 assertions). |
| `php tests/test_estate_settlement_database.php` | PASS | Estate settlement database tests passed (32 assertions). |
| `php tests/test_mail_database.php` | PASS | Mail database tests passed (34 assertions). |
| `php tests/test_mail_recovery_database.php` | PASS | Mail recovery database tests passed (51 assertions). |
| `php tests/test_upgrade_gesamt.php` | PASS | Overall upgrade v260→current passed (40 original tables/columns unchanged; PDF unchanged; repeated migration). |
| `php tests/test_installation_database.php` | PASS | Installation/system database tests passed (29 assertions). |

Neue gezielte Befehle:

```sh
php tests/test_v1_planning.php
GSZ_TEST_DSN='mysql:unix_socket=/workspace/gsz-tools/db/mysql.sock;dbname=gsz_test_v1_guardfinal' php tests/test_v1_database.php
GSZ_TEST_DSN='mysql:unix_socket=/workspace/gsz-tools/db/mysql.sock;dbname=gsz_test_v1_calendarfinal' php tests/test_v1_integrations.php
GSZ_TEST_PHP=/workspace/gsz-tools/bin/php python3 tests/test_smtp_loopback.py
GSZ_TEST_URL=https://127.0.0.1:8478 node tests/test_golive_browser.cjs
GSZ_TEST_URL=https://127.0.0.1:8478 node tests/test_v1_browser.cjs
GSZ_TEST_URL=https://127.0.0.1:8478 node tests/test_v1_calendar_browser.cjs
```

Browserfixtures vor Ausführung frisch per CLI-Installer/Erstinhaber erzeugt, Mail-/Send-/Routingflags deaktiviert, eigener privater Sessionpfad, PHP8134 hinter lokaler TLS-Brücke8478. Für Alt-Browserfixtures `php tests/prepare_gesamt_browser.php`, nur bewachte leere synthetische DB; separater PHP8136/TLS8480. `NODE_PATH=/opt/codex/cua_node/lib/node_modules` und `/usr/bin/chromium` waren lokale Werkzeuge. Bestehende Browserbefehle: `node tests/test_planning_browser.cjs`, `test_operations_browser.cjs`, `test_phase3_browser.cjs`, `test_gesamt_browser.cjs`, `test_security_browser.cjs`, `test_estate_browser.cjs`, jeweils GSZ_TEST_URL der separaten Fixture. Keine künstliche Erfolgsausgabe bei fehlenden Voraussetzungen.

Syntaxbefehl pro durch `Path.rglob('*.php')` ermittelter Datei: `php -l DATEI`;155Dateien,Exit0. Restore:

```sh
GSZ_TEST_DB_SOCKET=/workspace/gsz-tools/db/mysql.sock \
GSZ_TEST_DB_SOURCE=gsz_test_golive_v1final \
GSZ_TEST_DB_TARGET=gsz_test_golive_v1finalrestore \
GSZ_TEST_MYSQL=/workspace/gsz-tools/bin/mariadb \
GSZ_TEST_MYSQL_DUMP=/workspace/gsz-tools/bin/mariadb-dump \
python3 tests/test_installation_restore.py
```

Ausgabe `restore:passed,tables:78,logical_dump_bytes:170952,logical_dump:identical,private_files:87,verified_document_links:1,daily_task_state:preserved`. Keine Dumps/Originale im Lieferpaket.

## Gefundene Fehler / Korrekturen und Prüflimits

Behobene reale Ursachen: fehlende harte Vorschlagskapazität; Quartale nur ab Fälligkeit; veraltete Aufgabenformulare nach Planübernahme; CLI-Konfigurationscheck mit leerem DocumentRoot; fehlender SMTP465-Modus; technische Herkunfts-/Diagnoseüberladung; keine sichtbare Wiederzuordnung offener automatischer Kalenderprüfungen. Alle neuen Prüfungen laufen nach Korrektur.

Zwischenzeitliche QA-Fehler werden nicht verschwiegen: lokaler TLS-Proxy zeigte zunächst falschen Loopbackport (Harness korrigiert); NODE_PATH zunächst auf falsches Verzeichnis gesetzt (Playwright nicht gefunden, danach korrekt ausgeführt); Testnavigation innerhalb desselben Hashdokuments liefert keinen neuen Response (Test prüft korrekten Fall); eine Testfixture übernahm absichtlich gelöschte Fraktionszuordnung in eine zweite neue Quelle (Mappingfixture korrigiert, danach28PASS). Beim ersten entpackten Paketcheck war der private CLI-Sessionpfad im Harness noch nicht gesetzt; der Systemcheck meldete zutreffend einen Blocker. Mit einem expliziten privaten0700-Sessionpfad erneut auf frischer Paketkopie geprüft. Die alte Gesamt-Browserassertion auf technische Dashboard-Bankmeldung wurde der ausdrücklich gewünschten Verlagerung angepasst und prüft „Onlinebanking nicht verbunden“ im Bankmodul; Funktionstest nicht entfernt.

Teilvorschläge lassen nicht untergebrachte Bestandsaufgaben mit ursprünglicher Fälligkeit bestehen; Überlast bleibt bis manueller Klärung sichtbar. Kein realer Routing-/Verkehrsnachweis. Native IMAP-Shim und Fake-Provider decken API-Verhalten ab, nicht echte STRATO-/Anbieterinteroperabilität. Keine umfassende Penetrationsprüfung/GoBD-/Steuerfreigabe behauptet. Source-/ZIPvergleich und Secret-/CRC-/Hash-/Patchrekonstruktion sind bestanden (PAKETPRUEFUNG_V1.md). Zusätzlich vollständige ZIP in frisches Verzeichnis entpackt:155PHP-Dateien syntaxgeprüft, Fresh340/78Tabellen, Erstinhaber, wiederholtesUpgrade, Planung46, RechnungspDF80Positionen/Mehrseiten und Systemcheck mit0Blockern/6Hinweisen sowie verweigerte Neuinstallation auf nichtleererDB bestanden. Privater0700Sessionpfad im Harness explizit gesetzt. Nach abschließenden Dokumentationsänderungen ist der gesamte ausführbare Paketbestand bytegleich zu dieser geprüften Extraktion. Lokale Testpasswörter sind offensichtlich synthetische ungültige Beispieldaten; keine ursprünglichen Zugangsdaten übernommen.
