# Tatsächliche Inbetriebnahmeprüfung – 11.11.2026

## Basis und Abgrenzung

Maßgeblich GSZ-Checkpoint-v330-Immobilien-Nebenkosten.zip, SHA256 b75f6f196f39f5829fdb8cc9188558a5e7ddd3a6529fdb014db1c644d9a25a89. Kontroll-Update GSZ-Immobilien-Update-ab-v300.zip, SHA256 0cf4e661fb8d3f34b913fd049a03a87d81ab58b514474a3f3ccf85f3dc656dfb. Beide CRC/Hash/ZIP-Slip/Symlink-/Dateigrößenprüfung bestanden. Gesicherte v300-Basis plus Patch ergibt exakt245 Dateien des vollständigen v330. Ausgangsarchiv unverändert zusätzlich gesichert. Entwicklung ausschließlich isoliert unter /workspace/gsz-inbetriebnahme/zgs-app; alter Entwicklungsbaum erhalten.

Umgebung PHP8.4.24 mit PDO-MySQL/mbstring/iconv/fileinfo/zlib/OpenSSL/Sessions, MariaDB11.8.6/InnoDB/utf8mb4, Europe/Berlin, Node/Playwright/system Chromium. Lokale UNIX-Socket-DBs nur gsz_test_*, HTTPS-Testproxy nur Loopback, Testzertifikat. Keine echten Mieter/Mails/Kontoumsätze; Synthetikdaten und separate lokale DB-Benutzer. Kein Produktivzugriff, kein externer Mail-/Bank-/kostenpflichtiger API-Aufruf. Lokale Testwerkzeuge Node/Python sind keine Produktionsanforderung.

## Behobene Hindernisse

Sicherer CLI-Fresh-Installer und read-only Systemcheck fehlten; ergänzt unter Wiederverwendung der Originalmigrationen. Fresh prüft leere DB, MariaDB/utf8mb4 und serialisiert Einrichtung; kein Zugang/Objekt wird still erfunden. Falscher Hilfetext zur stillen GET-Aufgabenerzeugung korrigiert, Aktualisierung direkt im Tagesplan ergänzt. Regelaktionen jetzt mit PRG/Fehlerzustand. Dateinamenregel im Dokument-/Fotoupload erzeugte PHP-Warnung „Unknown modifier“; durch vorhandene gszName-Funktion ersetzt. Originalname/Bytes im HTTP-Test geprüft. Fehlerlog dieses Uploads nun fester Code ohne Exception-/Geschäftstext. Mobile Felder16px und Schaltflächen44px ohne Designumbau. Keine neue SQL-Migration.

## Neue automatisierte Prüfungen

| Prüfung | Ergebnis |
|---|---|
| test_installation_database.php |29 Assertions bestanden, neueste leere gsz_test_install_golive_permissions_final |
| test_golive_browser.cjs |48 Checks bestanden, frisch installierte gsz_test_golive_journey6 |
| test_installation_smoke.cjs |9 Checks bestanden, einschließlich Klartext-HTTP403 und Logout |
| PHP-Syntax |142 ausgelieferte Quelldateien bestanden; Laufzeit-Fontcache ausgeschlossen |
| test_installation_restore.py |76 Tabellen, logischer Dump120525Bytes identisch,46 private Dateien identisch,1 Original-Dokumentreferenz und erledigte/verschobene Aufgaben erhalten |

Die Installationssuite prüft reale Spalten/Ledger/Identitätsschlüssel, InnoDB/Zeichensatz, fehlenden Zugang, bestehende Konten/Daten unverändert, konkurrierenden Installer, falschen Zeichensatz vor DDL, leere/nicht leere DB, doppelte Migration, falsche Webwurzel, fehlende Secrets ohne Ausgabe, fehlenden Dublettenschlüssel, Speicherprobe und indirekte Rollen-/nur lesende Rechte. SHOW GRANTS bleibt nur intern; keine Konto-/Granttexte ausgegeben. Rollen-/Einzeltabellenrechte werden im Zweifel als unbestätigt angezeigt, nicht erfunden.

Praxis-Browser: HTTPS/Login/Sessioncookie, sieben Objekte aus bestehendem idempotentem Import (Regelbeginn für diese normale Testwoche bewusst sieben Tage später), neuer Kunde und achtes Objekt, Objektnotiz/XSS-Escaping, unverändertes Originaldokument mit richtigem Dateinamen, neue75Minuten-Reinigung, zweimalige Aktualisierung ohne Dublette, bestätigter synthetischer CSV-Papiertermin,15Minuten raus am Vortag/rein am Abfuhrtag, Fixtermin13:00–14:30, zusätzliche spontane120Minuten-Aufgabe, Vorschau/Bestätigung,10:30-Start und kein Terminüberlapp, Abschluss ohne Foto, manuelle Verschiebung/Notiz/Sperre, erneute Optimierung, echter Browserprozessneustart und neue Anmeldung. Danach Abschluss/Verschiebung/Notiz/Abfuhr erhalten. CSRF, ungültige Dauer mit303, private Pfade, tatsächlicher Websystemcheck0Blocker und390/1440px geprüft. Screenshot lokal visuell geprüft; keine physische iOS-/Safariabnahme behauptet.

Smoke: HTTPS-Domainwurzel öffnet Login, mobile Schrift/Touchgröße, Session-ID vor/nach Login verschieden, Systemchecklink auch mobil sichtbar, actual Web-PHP-Prüfung, Logout sperrt Plan/Download, unveränderte App verweigert rohe HTTP-Anfrage vor DB-Zugriff. Kein TLS-Zertifikatvertrauen eines echten Hosts geprüft.

Restore: kopierte vollständige private Testablage inklusive Fontcache/Loginbremse, anschließend separate neue DB gsz_test_golive_journey6_restore. Sortierter logischer SQL-Dump vor/nach byteidentisch, alle kopierten Dateihashes identisch, Dokumentmetadaten/Original und Aufgabenzustände zusätzlich geprüft. Kein SQL-Dump/Testupload im Lieferpaket. Dies beweist lokale Wiederherstellung, keine echte Hosting-Sicherung.

## Bestehende Regressionen

Alle15 bestehenden Logik-/Smoke-Suiten bestanden: Money, statisches Schema, Unicode-Mehrseiten-Rechnungs-PDF, Recurring73, Planung24, Abfall/Routing32, Mail25, Finanz18, Operations18, Finanzwiederholungen8, Kasse/Anlagen16, Immobilienmathematik9, Nebenkosten186, Vault14, nativer IMAP-Shim9.

Alle14 vorhandenen DB-/Upgrade-Suiten auf separaten leeren Test-DBs bestanden: Phase1=39, Planung10, Phase3=21, Finanz25, Geschäftsdokumente26, Operations31, OffenePosten24, Finanzwiederholungen22, Kasse/Anlagen39, Immobilien42, Nebenkosten32, Mail34, Recovery51, Upgrade mit40 ursprünglichen Tabellen/Spalten und archivierter Rechnungs-PDF unverändert + wiederholte Migration. Neuer Installationstest gesondert29. Datenbanken des aktuellen Sammellaufs gsz_test_*golive_release_0…14, neue letzte Installationssuite gesondert wie oben.

Bestehende Browser vollständig bestanden: Planung11, Operations29, Phase3=17, Gesamt44, Sicherheit11, Immobilien27. Gesamtsuite mit synthetischem Absender/Rechnungsaussteller, echtem Versand weiterhin false. Keine Bank-/Mail-Echtintegration. Ältere Spezialtests wie SMTP-TLS-Loopback/andere Rechnungs-/Estate-Browser nicht alle erneut ausgeführt, da Mail/Bank/Abrechnungskern unverändert; historische Ergebnisse separat im bisherigen TESTSTATUS.md.

## Fehler während Prüfung transparent eingeordnet

Erste Harnessläufe korrigiert: falsche Buttonbezeichnung, zugeklapptes Objektformular, relativ falsch verschachtelter Testselektor, URL-/API-Antworttyp und PHP-Entwicklungsserver-Fallback auf index statt404. Danach ganze Praxissuite auf neuer leerer Fixture erfolgreich. Falscher lokaler Sitzungsordner wurde vom neuen Check korrekt blockiert, private Testablage eingerichtet. Negativtest zur Webwurzel deckte anfänglich zu schwache CLI-Warnung statt Blockade auf; korrigiert. Für fehlenden Unique-Key zunächst separate FK-Stützindex nötig, Test darauf korrigiert. Reale Dateinamenwarnung behoben. Extremüberladung960Minuten führte korrekt zur Mitternachts-Übernahmesperre, nicht zu stiller Übernahme; normale Testwoche ausdrücklich entlastet, Hinweis auf Wochenvorschau verbessert. Bestehender Gesamtbrowser zunächst ohne nötigen synthetischen Absender/Aussteller gestartet; Fixturekonfiguration ergänzt, dieselbe unveränderte44er-Suite danach vollständig bestanden. Keine App-Fehler durch Überspringen von Tests verdeckt.

## Reproduzierbare Befehle

Im Projektverzeichnis. Je DB-Test eine neue LEERE lokale Testdatenbank mit dem vom Test verlangten Präfix anlegen; obige Namen bereits befüllt. Keine Tests auf Produktion.

```sh
/workspace/gsz-tools/bin/php tests/test_recurring.php
/workspace/gsz-tools/bin/php tests/test_planning.php
/workspace/gsz-tools/bin/php tests/test_waste_routing.php
GSZ_TEST_DSN='mysql:unix_socket=/workspace/gsz-tools/db/mysql.sock;dbname=gsz_test_install_NEU' /workspace/gsz-tools/bin/php tests/test_installation_database.php
GSZ_TEST_URL=https://127.0.0.1:8464 node tests/test_golive_browser.cjs
GSZ_TEST_URL=https://127.0.0.1:8464 GSZ_TEST_HTTP_URL=http://127.0.0.1:8124 node tests/test_installation_smoke.cjs
GSZ_TEST_URL=https://127.0.0.1:8466 node tests/test_gesamt_browser.cjs
GSZ_TEST_URL=https://127.0.0.1:8466 node tests/test_security_browser.cjs
GSZ_TEST_DB_SOCKET=/workspace/gsz-tools/db/mysql.sock GSZ_TEST_DB_SOURCE=gsz_test_golive_journey6 GSZ_TEST_DB_TARGET=gsz_test_golive_NEUER_RESTORE GSZ_TEST_MYSQL=/workspace/gsz-tools/bin/mariadb GSZ_TEST_MYSQL_DUMP=/workspace/gsz-tools/bin/mariadb-dump python3 tests/test_installation_restore.py
php private/system_check_cli.php --web-root=/ABSOLUT/zgs-app/public --probe-storage --json
```

Browserfixture für Praxis: neue leere gsz_test_golive_*-DB, tatsächlichen neuen CLI-Installer und admin_setup mit synthetischem owner@example.invalid/Local-Test-Only-2026! verwenden. Keine bestehenden Objekte/Arbeitsdaten vorseedeln. Test beginnt selbst mit sieben Objekten aus der UI. Kern-/Gesamtregression: tests/prepare_gesamt_browser.php auf eigener leerer DB,35 synthetische Mailtexte,7Objekte, syn. Aussteller/Absender konfigurieren; tatsächlichen Versand false belassen. PHP-Server nur Loopback, private Sessions, TLS-Testproxy; HTTP-Gegentest separater Server ohne HTTPS-Simulation. Lokaler Testharness außerhalb Lieferquelle, keine Ports/Hostdaten für Produktion hartcodiert.

## Grenzen / Freigabe

Keine echte STRATO-Installation/PHP-/MariaDB-Tarifprüfung, keine physische Safari-/iPhoneprüfung, keine realen Kalender/Bank/Mailkonten, kein Produktivschema-/fachlicher GoBD-/Steuer-/E-Rechnungsnachweis. Alle automatisch erkannten lokalen Kernblocker behoben; reale Hostingprüfung und Freigaben bleiben in GO_LIVE_STATUS.md. Vollständiges Paket und v330-Patch mit Pfad-/CRC-/Secret-/Dateimanifest-/Rekonstruktionsprüfung erzeugen; kein Live-Deployment.

## Abschließende Paketprüfung

Vollständiges Paket262 Dateien, minimaler Patch39 neue/geänderte Dateien gegenüber exakt v330, keine Löschungen. Alle SQL-Dateien byteidentisch zur Basis, keine neue Migration. CRC-/Pfad-/Symlink-/Ausschluss-/bekannte-Secret-/Tokenmusterprüfung bestanden; Konfiguration, .env, Uploads, Vault/Schlüssel, Dumps und Sicherungsarchive fehlen im Lieferpaket. V330 plus Update rekonstruiert exakt den vollständigen Stand. Separates vollständiges SHA256-Dateimanifest und ZIP-Prüfsummen mitgeliefert.

Frisch erzeugtes ZIP separat nach /workspace/gsz-tools/package-install-v331 entpackt und vollständiges Dateimanifest verifiziert. Alle142 enthaltenen PHP-Quellen syntaxgeprüft; Installations-/Systemcheck-DB-Suite aus der entpackten Anwendung auf neuer leerer gsz_test_install_packaged_v331 mit29 Assertions bestanden. Danach ausschließlich diese Dokumentation ergänzt, Anwendungscode unverändert; Pakete/Manifeste abschließend erneut erzeugt und auf Übereinstimmung geprüft. Keine Behauptung einer STRATO-Installation.
