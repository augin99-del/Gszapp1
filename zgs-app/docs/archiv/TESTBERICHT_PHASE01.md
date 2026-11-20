# Testbericht Phase 0 und 1

09.11.2026, ausschließlich lokal. PHP 8.4.24 / MariaDB 11.8.6 / Chromium über Playwright.

| Prüfung | Ergebnis |
|---|---|
| Ausgangsbestand, PHP ohne geheime config.php | Syntax bestanden; alle drei ursprünglichen Smoke-Tests bestanden |
| Finale Arbeitskopie | 38 PHP-Dateien: Syntax bestanden |
| Bestehende Tests money / invoice_pdf / schema | Alle bestanden; Schema-Test ist allein statisch |
| test_recurring.php | 73 Assertions bestanden: Datum, Monatsanker, Schaltjahr, Sonntag/Jahreswechsel, Saison, Stammdaten, Dauern, Validierung, Umgebungswerte, Versandsperre |
| test_phase1_database.php | 39 Assertions bestanden, 28 Seedregeln; tatsächlicher Import des v192-Bundles und v200 auf MariaDB |
| DB-Bestandserhalt | Alte Objektnotiz und abweichender Regelrhythmus bleiben erhalten; Monatsanker migriert |
| Import | 6 neu/1 übernommen im Fixture; fehlende Zuordnungen, veraltete Vorschau, mehrdeutige Objekte abgewiesen; Wiederholung 0 neu |
| Scheduler | Übergebener Zukunftstag statt Datenbankuhr, Monatsanker über mehrere Aufrufe, begrenzter Rückstand, Saison, verschobene/erledigte Aufgaben, manuell/automatisch ohne Dublette, Gelb Mi/Do |
| Browser | 8 Prüfungen bestanden: anonyme Umleitung, Login, 7er-Vorschau, 7/28-Import, Wiederholung, editierbare Dauer/Rhythmus, CSRF 403, mobile 390px und Desktop 1440px ohne horizontalen Überlauf |
| Backup/Restore | single-transaction-Dump in zweite Testdatenbank importiert; 13 Tabellen, 16 Aufgaben; vollständige sortierte Dumps ohne Kommentare bytegleich (cmp Exit 0) |

Keine produktiven Daten verwendet. Der lokale MariaDB-Server hat skip-networking und nur lokalen Socket. Browser auf Loopback-HTTPS mit synthetischen Zugangsdaten. Keine Mails, Bankaktionen, API-Keys, GitHub-Uploads oder Deployments.

## Nicht als geprüft behauptet

Keine produktive Strukturkopie verfügbar, keine STRATO-Serverversionsprüfung, kein Hosting-/IMAP-/SMTP-Livetest, kein paralleler Belastungstest, keine vollständige Sicherheitspenetration oder Upload-/Datei-Restoreprüfung. Restoretest betraf die Datenbank, nicht ein vollständiges STRATO-System. Saisonverhalten auf Datumsebene und DB geprüft, keine komplette Beispielwoche mit Uhrzeiten (Phase 2). Fachliche Steuerprüfung, GoBD und E-Rechnungen sind offen. PostgreSQL/SQLite werden nicht unterstützt.
