# Phase 3 – Installation, Quellen und Rückfall

Implementiert und lokal getestet, extern nicht als Objektkalender/Routingdienst verifiziert, auf STRATO nicht installiert. Keine Produktionsfreigabe. Ausgangsstand GSZ-Phase02.zip; Update nur gegen diesen Stand anwenden.

## Vorbereitung / Migration

1. Wartungsfenster, Web-Schreibzugriffe und Cron pausieren. Vollständige DB-Sicherung plus Code, private Serverkonfiguration und private/uploads getrennt geschützt außerhalb des Document-Roots sichern. Passwörter nicht als CLI-Argument/Chattext verwenden. Restore in separater Datenbank prüfen.
2. Tatsächliche STRATO-PHP-/MariaDB-Version und eine bereinigte Kopie der produktiven Struktur prüfen. Lokal getestet PHP 8.4.24/MariaDB 11.8.6. Ziel MariaDB 10.6+; PHP 8.1 mindestens, 8.2+ empfohlen, PDO MySQL/mbstring/fileinfo/iconv. Für optionalen HTTPS-Adapter allow_url_fopen und CA-Vertrauen erforderlich. Kein Node/Python für den Produktivserver.
3. Dateien gemäß UPDATE_DATEIEN_PHASE3.md ergänzen/ersetzen. private/config.php, .env, Uploads und andere lokale Serverdateien nicht ersetzen. Nur public/ ist öffentlich; private/, tests/, Archive, Dumps und Dokumentation nicht ins öffentliche Domainziel legen.
4. Phase 2/v210 muss vollständig vorliegen. Auf Strukturkopie `php private/migrate_phase3.php` ausführen. Der Runner prüft Ledger und wesentliche Spalten, führt private/migration_v220.sql aus. Neue Tabellen/Spalte/Index idempotent. Zweiter Lauf lokal getestet. Vorhandene gleichnamige aber inkompatible Tabellen werden durch IF NOT EXISTS nicht repariert: bei Abweichungen stoppen und SHOW CREATE TABLE privat abgleichen. DDL committet automatisch; keine atomare Gesamtmigration behaupten. Teilfehler beheben, Runner erneut ausführen; kein blindes Wiederholen alter v192/v200-Migrationen.
5. Leere Testinstallation: v192-Bundle, v200 einmalig, v210-Runner, danach v220-Runner. setup.php fehlt weiterhin; Testadmin nur über geschützte synthetische Fixtures, niemals Defaultpasswort produktiv nutzen. Keine Rechnungsdatenänderung durch v220.

## Offline-Kalender einrichten

1. Müllkalender öffnen; Objektadresse prüfen. Lippstädter Straße 5 bleibt Rheda-Wiedenbrück entsprechend ausdrücklicher früherer Bestätigung. Neue Tabelle nennt Gütersloh: vor echter Nutzung selbst abgleichen, keine automatische Änderung durch dieses Update.
2. Quelle mit Name/amtlicher Veröffentlichung, Abholadresse, Quelldatum und Gültig-bis speichern. Adresse exakt mit Objektakte abstimmen. Ungeprüft ist Standard.
3. Zulässigen adressbezogenen ICS-Einzelterminexport aus eigenem Kalender verwenden, falls vorhanden. Ein dokumentierter automatischer Tonnenticker-Export ist hier NICHT eingebaut. Alternativ amtliche Termine in CSV übertragen; keine unbekannten Fraktionen raten. Beispiel (synthetisch, nicht als echter Termin verwenden):

```csv
datum;fraktion;id;adresse
2030-01-07;Bezeichnung laut Kalender;stabile-id-a;Teststraße 1, Teststadt
```

4. Dateiformat/Trennzeichen und exakte CSV-Spaltennamen im Formular auswählen. UTF-8, max. 1 MB, 500 Termine. Datum YYYY-MM-DD. Stabile ID erleichtert Änderungsprüfung. Ohne ID entstehen Inhaltsidentitäten; fehlende vorherige Termine werden konservativ als Konflikt gezeigt, auch bei Teildateien.
5. Jede Originalbezeichnung explizit einer Fraktion zuordnen. Unklar ist zulässig, erzeugt keine Aufgaben. Vorschau und Konflikte prüfen. Bestätigen allein importiert ungeprüft. Zusätzliche Checkbox für tatsächliche Quellen-/Terminprüfung und Aufgabenfreigabe erst nach fachlichem Abgleich setzen.
6. Optional gemeinsame Besuche bewusst wählen: 15 Minuten insgesamt pro Objekt/Datum/Richtung; sonst 15 Minuten je Fraktion. Dauer später im Tagesplan editieren. Geänderte/erledigte/gesperrte Besuche werden nicht still erweitert.
7. Konflikte nur ungeprüft unter ausdrücklichem Erhalt alter Daten übernehmen. Alte und neue Termine einzeln prüfen, überholte Termine explizit als veraltet markieren, vorhandene Aufgaben ggf. manuell korrigieren. Markierung veraltet löscht oder erledigt keine Aufgabe. Unbekannte Fraktion durch korrigierte Datei auflösen, unklaren Datensatz nachvollziehbar veraltet markieren.
8. Kalender Woche/Objekt/Quelle/Originaltitel/ID anzeigen. Fehlende oder abgelaufene Quellen sichtbar prüfen. Es gibt keinen automatischen Sync oder erfundene Feiertagskorrektur. Fontänenstraße-Gelb-Wochenregel bleibt separat bestehen; Donnerstagimporte können dieselbe Regel-Aufgabenidentität verwenden. Normale Tonnenroutinen nicht aktivieren.

## Route ohne Provider

Planung → Route optimieren. Reale Startadresse nach Uni oder Zuhause sowie reale Zieladresse Zuhause/Fitnessstudio eingeben, sonst leer lassen und Unsicherheit akzeptieren. Vorschau nimmt den gespeicherten Tagesplan (künftige Wiederholungen vorher über Wochenvorschau übernehmen), gruppiert flexible Tätigkeiten und zeigt geschützte Aufgaben/Blocker. Standard 10:30/16/17 bleibt erhalten; Tagesstart und Puffer in Planungseinstellungen editierbar.

Keine Kilometer oder tatsächlichen Fahrtzeiten im Offline-Modus. Puffer ist eine bewusste Schätzung, bei 0 fehlen Fahrzeitreserven. Vorschau zuerst, dann ausdrückliche Bestätigung; nur flexible Reihenfolge wird gespeichert. Bestätigte aktuelle Route kann in der Tagesansicht genutzt werden. Bei Aufgabenänderung/Erledigen Datenstand veraltet: neu berechnen. Navigation nur durch Klick, nicht während der Fahrt bedienen.

## Optional später: eigener/lizenzierter OSRM-Provider

Standard bleibt aus. Erst geeigneten dedizierten Dienst, Datenschutz, Kosten, Nutzungsbedingungen, OSM-Attribution und Anfragebudget klären und ausdrücklich freigeben. Öffentlich betriebene Demo-Server nicht als produktive Standardlösung verwenden.

Sichere Serverkonfiguration/Umgebung, niemals Schlüssel im Paket:

- routing_enabled bzw. GSZ_ROUTING_ENABLED: erst nach Freigabe true.
- routing_osrm_endpoint bzw. GSZ_ROUTING_OSRM_ENDPOINT: fest konfigurierte HTTPS-Basis des eigenen/vertraglich nutzbaren Dienstes (keine URL aus Formular).
- routing_cache_ttl / GSZ_ROUTING_CACHE_TTL: 60–86400 Sekunden, Standard 3600.
- routing_max_points / GSZ_ROUTING_MAX_POINTS: 2–25; maximal eine Matrix-Anfrage je bewusster Vorschau.

Tatsächliche Koordinaten für Start, Ziel, alle Objekt- und Terminadressen manuell mit Quelle prüfen und über Route → geprüfte Koordinaten speichern. 90-Tage-Qualitätsfrist. Keine automatische Geokodierung. Bei fehlenden Daten, ungültigen Antworten, Timeout oder Dienstfehler komplett offline zurückfallen. Reale Routingwerte in Vorschlägen/aktueller Tagesansicht nach spätestens einer Stunde neu berechnen. Kein produktiver OSRM-Endpunkt in dieser Sitzung konfiguriert/getestet. Lokaler Fake-Provider ausschließlich in Tests.

## Tests reproduzieren

```sh
find public private tests -name '*.php' -print0 | xargs -0 -n1 php -l
php tests/test_money.php
php tests/test_invoice_pdf.php
php tests/test_schema.php
php tests/test_recurring.php
php tests/test_planning.php
php tests/test_waste_routing.php
GSZ_TEST_DSN='mysql:unix_socket=/PFAD/mysql.sock;dbname=gsz_test_p3' php tests/test_phase3_database.php
GSZ_TEST_DSN='mysql:unix_socket=/PFAD/mysql.sock;dbname=gsz_test_p2' php tests/test_planning_database.php
GSZ_TEST_DSN='mysql:unix_socket=/PFAD/mysql.sock;dbname=gsz_test_p1' php tests/test_phase1_database.php
```

Jeder DB-Test eigene leere lokale gsz_test_*-DB, synthetische Daten. Phase-2/3-Tests verwenden lokales root ohne Passwort ausschließlich im isolierten Testserver. Datenbankharness/Defaults niemals produktiv übernehmen. Browser mit lokalem Playwright/Chromium und Loopback-HTTPS-Testinstanz: `GSZ_TEST_URL=https://127.0.0.1:PORT node tests/test_phase3_browser.cjs`. Fixture: frische Phase-3-DB-Tests und synthetischer owner@example.invalid-Admin mit lokalem Testpasswort wie tests/prepare_browser.php. Alte Browsertests auf getrennten frischen Phase-1/Phase-2-Fixtures ausführen. Tests/HTTPS-Harness niemals öffentlich bereitstellen. Harness liegt nicht im Paket; produktiver HTTPS-Zwang wurde nicht gelockert.

## Backup / Rollback

Lokal wurde ein tatsächlicher vollständiger DB-Dump in zweite DB zurückgespielt und mit sortiertem Dump bytegleich verglichen. Das ersetzt keinen STRATO-/Upload-Restoretest.

Rückfall: Schreibzugriffe stoppen, neue Daten sichern, vorherige Phase-2-Dateien zurückspielen. Zusätzliche v220-Tabellen und tasks.waste_key zunächst stehen lassen. Coderollback nimmt bereits bestätigte Aufgaben/Quellen/Reihenfolgen nicht zurück! Für vollständigen Rückfall konsistente DB-/Dateisicherung wiederherstellen und neue Daten gesondert abgleichen. Keine pauschale Löschung alter/neuer Aufgaben oder archivierter Belege. Kein destruktives Downskript. UTC für technische DB-Zeitstempel neu über zentralen Loader gesetzt; historische DATETIME-Zonen vor echtem Rollout prüfen.
