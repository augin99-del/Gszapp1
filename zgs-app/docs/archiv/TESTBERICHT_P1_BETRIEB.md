# P1-Betriebsworkflow – tatsächliche Ergebnisse 10.11.2026

Basis: zuletzt gesicherter Gesamtstand v260 ab GSZ-Phase03.zip. Lokale Umgebung PHP8.4.24/MariaDB11.8.6, nur synthetische gsz_test_*-Datenbanken über UNIX-Socket; Chromium/Playwright/HTTPS-Loopback. Kein externer Kontozugriff/Deployment.

Befehle im Projekt, PHP hier /workspace/gsz-tools/bin/php:

```sh
php tests/test_operations.php
GSZ_TEST_DSN='mysql:unix_socket=/workspace/gsz-tools/db/mysql.sock;dbname=gsz_test_p1_operations' php tests/test_operations_database.php
GSZ_TEST_URL=https://127.0.0.1:8456 node tests/test_operations_browser.cjs
```

18 Unitprüfungen: Validierung/Email/Version, Terminreihenfolge, Sommer-/Winterzeit, nicht existente/mehrdeutige Zeitumstellungs-Uhrzeiten und lokale Erinnerung. 31 DBprüfungen: historische Spalten/Zeilen erhalten, keine erfundene Alt-Erledigungszeit, Kundenänderung/Archivierung/Reaktivierung, Objektzuordnung/Fehlerrollback, Mitarbeiterzuordnung, Notiz/Status/Versionskonflikte/Idempotenz, keine Foto-/Nachweispflicht, Fixterminblockade13–14:30, Vorschlagsveraltung, Storno ohne Löschung, Regel-/Taskerhalt nach Migration und Auditkette. 29 Browserprüfungen: Kunden/Objekt/Zuordnung/Notiz/Erledigung/Terminänderung/-storno, XSS, CSRF, veraltete Formulare, vier neue Ansichten bei390/1440px. Alle bestanden.

Regressionen mit jeweils separater leerer DB: test_phase1_database.php39, test_planning_database.php10, test_phase3_database.php21, test_finance_database.php25, test_mail_database.php34, test_business_documents.php26 bestanden; Namen gsz_test_ops_reg1/reg2/reg3/finance/mail/documents. Recurring73, Planning24, Waste/Routing32 ebenfalls bestanden.

Schema v270 additiv: customers Rechnungsadresse/Version/Archiv; properties Version; tasks Zuweisung/Version/Erledigungszeit; appointments Notiz/Erinnerung/Version/Storno; operations_workers und operations_events, damit42 Tabellen. Kein destruktives Downskript. Migration wie bisher über migrate_gesamt.php nach vollständiger v220; historische Gesamtmigrationen nicht blind einspielen. Rollback: konsistentes Code-/DB-/Dateibackup, Zusatzspalten zunächst erhalten.

Grenzen: Planung bleibt für den Inhaber, keine Mehrpersonen-Kapazitätsoptimierung; Historie ist kein WORM/GoBD-Nachweis. Lokale Erinnerung nur beim Öffnen der App, kein Push/Mail. Kalenderdaten/Feiertage weiterhin nur aus freigegebenen Quellen. Chronologische Konflikte werden angezeigt, keine externen Fahrzeiten erfunden. Konfiguration/Originaluploads nie ausliefern.

Abschließend: Gesamtbrowser44 mit neuer v270-Fixture und Syntax aller89 PHP-Dateien bestanden.
