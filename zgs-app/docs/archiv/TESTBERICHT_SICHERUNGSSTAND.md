# Tatsächlicher Sicherungsstand bei Nutzerstopp – 10.11.2026

Dies ist eine Sicherung, keine abgeschlossene Gesamtabnahme. User verlangt neuen Plan abzuwarten. Weiterentwicklung und Parallelbearbeitung angehalten; nur Dokumentation und Paketierung beendet.

## Nachgewiesene Teiltests

PHP8.4.24/MariaDB11.8.6, synthetische lokale Daten, UNIX-Socket; Playwright/Chromium über HTTPS-Loopback. Kein Livekonto/Deployment.

- `php tests/test_operations.php`:18; `GSZ_TEST_DSN=...gsz_test_p1_operations php tests/test_operations_database.php`:31; `GSZ_TEST_URL=https://127.0.0.1:8456 node tests/test_operations_browser.cjs`:29 bestanden.
- v270-Regression: Phase1/2/3 Datenbank39/10/21; Mail34, Finanz25, Dokument26; Gesamtbrowser44 und Syntax89 PHP-Dateien damals bestanden. Spätere Dateien sind dadurch nicht pauschal mitgeprüft.
- `GSZ_TEST_DSN=...gsz_test_receivables_final php tests/test_receivables_database.php`:24 bestanden. `GSZ_TEST_URL=https://127.0.0.1:8457 node tests/test_receivables_browser.cjs`:15 bestanden.
- `php tests/test_finance_recurrences.php`:8; DB gsz_test_finance_recurrences `test_finance_recurrences_database.php`:22; Browser8457 `test_finance_recurrences_browser.cjs`:12 bestanden.
- `GSZ_TEST_PHP=/workspace/gsz-tools/bin/php python3 tests/test_smtp_loopback.py`:8 native SMTP-/TLS-Szenarien bestanden. Nur127.0.0.1:587, temporäre Test-CA/Schlüssel automatisch entfernt, keine externe Zustellung. Native IMAP/STRATO weiterhin ungeprüft.
- `GSZ_TEST_DSN=...gsz_test_asset_classification3 php tests/test_asset_classification_database.php`:5 bestanden. Erste Testläufe hatten fehlendes require bzw. einen strikten Typvergleich gegen PDO-SUM-String; Testfixture korrigiert und auf neuer leerer DB vollständig wiederholt, keine Prüfung übersprungen.
- Kasse/Anlagen, durch Parallelbearbeitung tatsächlich gemeldet: `test_cash_assets.php`16; `test_cash_assets_database.php`39; `test_cash_assets_browser.cjs`21; zusätzlicher Zwei-Prozess-Konkurrenztest (`test_cash_assets_concurrency.cjs`) bestanden, nur eine konkurrierende Entnahme akzeptiert. Der letzte erweiterte Browserlauf mit zusätzlicher CSV-Assertion ist beim Stopp nicht abschließend protokolliert; keine22 Prüfungen behaupten.
- Upgrade-Zwischenprüfung v260→v280:40 ursprüngliche Tabellen/Spalten unverändert, PDF unverändert, Migration zweimal bestanden. Dieser ältere Lauf deckt spätere v290/v300 nicht vollständig ab.

Alle PHP-Befehle verwendeten /workspace/gsz-tools/bin/php. Vollständiges DSN-Muster: `mysql:unix_socket=/workspace/gsz-tools/db/mysql.sock;dbname=gsz_test_NAME`; neue DB je Test, root ohne Passwort ausschließlich im isolierten synthetischen Testserver. Tests nicht auf Produktion starten. Python/Node/OpenSSL nur lokale QA-Werkzeuge, keine zusätzliche Produktionsruntime.

## Noch offen

Kombinierte Regression/Syntax des allerletzten integrierten Stands, endgültiger Cashbrowserlauf, erneute vollständige Migrations-/Restore-/Paket-Smokeabnahme nach v300. Aktueller Runner enthält acht neue Gesamtmigrationen230–300. Belegoriginale/Hashdateien außerhalb des Webroots gemeinsam mit Datenbank sichern; Liefer-ZIP enthält keine Laufzeitdaten.

Buchhaltung bleibt fachlicher Arbeitsstand: Kassenregister verknüpft vorhandene Finanzzahlungen, manuelle Anlagenpläne werden nicht als steuerlich richtige AfA gebucht. Anlagenbezug führt zur ausdrücklichen Warnung in der Arbeitsauswertung; kein fertiger Lexware-Ersatz/GoBD-Nachweis. Wiederkehrende Rechnungen nur Entwürfe, Ausgaben nur Prüfaufgaben. Zahlungserinnerungen ohne Gebühren oder automatischen Versand. Immobilienverwaltung/Volksbank/Nebenkosten nicht implementiert.
