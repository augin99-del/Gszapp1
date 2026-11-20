# Testbericht – integrierte Immobilien/Nebenkosten v320, Gesamtcheckpoint v330

Stand10.11.2026. Nur synthetische lokale Daten, PHP8.4.24 (CLI-Wrapper /workspace/gsz-tools/bin/php), MariaDB11.8.6/InnoDB/utf8mb4, Europe/Berlin. Browser Playwright/Chromium, Loopback-HTTPS. Kein echter Mieter/Bankkonto, keine STRATO-/Produktivverbindung, keine echte Mail, keine bezahlte API.

## Implementiert und tatsächlich lokal geprüft

| Prüfung | Ergebnis |
|---|---|
| Immobilien-Monats-/Cent-/Datumslogik |9 Assertions bestanden |
| Immobilien-MariaDB, neueste leere gsz_test_estate_checkpoint_verified_0 |42 Assertions bestanden |
| Reiner Nebenkostenkern |186 Assertions bestanden |
| Integrierte Abrechnung, neueste leere gsz_test_estate_checkpoint_pdf_final |32 Assertions bestanden |
| Immobilien-Browser, neue vollständige Fixture |27 Checks bestanden,390/1440px |
| Abrechnung-Browser, neue vollständige Fixture |17 Checks bestanden,390/1440px |
| Bestehender Gesamtbrowser im neuen integrierten Schema |44 Checks bestanden |
| Eigentümerexport zusätzliche HTTP-Prüfung |4 Checks: anonyme Sperre, tatsächlicher Link, ZIP-Download, erzwungener Anhang |
| Eigentümerexport Inhalt |8 Dateien, CRC und sämtliche SHA-Manifest-Einträge stimmen |
| Zwei gleichzeitige getrennte PHP/PDO-Zahlungszuordnungen |Eine zulässige Zuordnung übernommen, konkurrierende Überzuordnung verworfen; verbleibender Zahlungspool korrekt |
| Syntax aller ausgelieferten PHP-Quellen |135 Dateien bestanden; zwei generierte Laufzeit-Fontcachedateien zusätzlich vorhanden, nicht ausgeliefert |
| Deutsche PDF-Beträge/-Schlüssel und Unicode-Mehrseiten |pdftotext: Nachzahlung200,00, tatsächliche900,00 Vorauszahlung;2 Seiten, Textprüfung bestanden; erste Seite zusätzlich gerendert/visuell geprüft |
| DB-/Dateirestore |76 Tabellen, logischer Dump271534 Bytes vor/nach identisch,10 restaurierte Beleg-/PDFreferenzen Größe+SHA identisch |
| Vault-Regression |14 Assertions bestanden |

Immobilien-DB prüft Trennung von Geschäfts-Sparkassendaten, Überschneidungen, stale forms, versionierte Konditionen/Sollbestätigung, Kaution, Teilzahlungen/Erstattungen/Gegenzeilen, Schutz rückwirkender geprüfter Kostenvereinbarungen, idempotenten CSV-Import/Quellenänderung, zwei Konten, Umbuchungen und tatsächliche Finanzübersicht. Neue Tabellen überschreiben keine Geschäftsdaten.

Abrechnungs-DB prüft echte Zahlung statt Soll, getrennte Heiz-/Betriebskosten, Eigentümeranteile, extern belegte Heizwerte/CO2, fehlendes Kostenregister, tatsächliche Vermieteradresse vor Finale, Quellen-/Empfängerkonflikte, gemischte Mieterwechsel/Vakanz, keine Vorauszahlungsübertragung auf Nachmieter, getrennte Vertrags-PDFs, Stale-Vorschauen, explizite Versionsfreigabe, unveränderte alte Snapshots/PDFs, gebäudeübergreifende Belegdubletten, Sperre überlappender verschiedener endgültiger Perioden, Schutz befüllter Daten bei wiederholter Migration und Auditkette. Owner-ZIP enthält unveränderte Quellen und getrennte PDFs, keine öffentliche Dateifreigabe.

Browser prüfen echte Controller/Formulare/Anmeldung, anonyme Sperre/CSRF, PRG, XSS-Ausgabe, Originaldownload/Traversal, Vorschau-Freigabe/Replay, zwei getrennte Konten, externe CSV-Heizwerte und mobile Überläufe. Die erste Immobilien-Browsersuite scheiterte an einem mehrdeutigen Testselektor (Dateiinput mit Button-Rolle); konkretisiert und gesamte Suite auf frischer DB erfolgreich wiederholt. Bei erster integrierter DB-Ausführung fehlenden Closure-Wert entdeckt/behoben; strikter Fehlerhandler und neue leere DB danach erfolgreich. Fehler nicht übersprungen. Der zusätzliche Export-Browserharness übergab zuerst ein URL-Objekt an eine API, die eine Zeichenkette erwartet; auf toString() korrigiert und alle4 Checks erfolgreich wiederholt.

## Bestehende Regressionen im neuen Schema

Jeweils neue separate leere lokale Testdatenbanken verwendet:

- Phase1 DB39 (28 Regeln), Phase2 PlanungDB10, Phase3 Abfall/RoutingDB21 bestanden.
- FinanzDB25, GeschäftsdokumenteDB26, MailDB34, RecoveryDB51 bestanden.
- Upgrade von vollständigem v260 auf aktuellen Runner: alle40 ursprünglichen Tabellen/Spalten und vorhandene Rechnungs-PDF unverändert; zweiter Migrationslauf bestanden.
- Isolierte bestehende Logiksuiten Money/Schema/PDF/Recurring73/Planung24/Waste-Routing32/Mail25/Finanz18/Operations18/Finanzwiederholungen8/Kasse-Anlagen16 bestanden; neue Komponenten wurden nicht durch Auslassen alter Tests freigegeben.
- Gesendet-Recovery nativer IMAP-Shim9, echter lokaler HTTP16 und Native/PHPMailer SMTP-TLS-Loopback16 bestanden. Details TESTBERICHT_MAIL_RECOVERY.md. Keine externe Zustellung.

Frühere v300-Prüfungen bleiben historisch im TESTSTATUS.md; nicht jede ältere Spezialbrowseransicht wurde wegen reiner Estate-Erweiterung erneut getestet. Native IMAP fehlt lokal, Shims sind kein STRATO-Nachweis. Kein pauschaler Gesamt-Sicherheits-/Last-/Fachabnahmestatus.

## Reproduzierbare Befehle

Im Projektverzeichnis; php muss passende getestete Extensions enthalten. Leere synthetische DB je DB-Test selbst vorher anlegen, Tests verweigern befüllte/anders benannte Datenbanken. Lokales Testroot ohne Passwort nur isolierter Testdienst.

```sh
/workspace/gsz-tools/bin/php tests/test_estate_core.php
/workspace/gsz-tools/bin/php tests/test_estate_calculation.php
GSZ_TEST_DSN='mysql:unix_socket=/workspace/gsz-tools/db/mysql.sock;dbname=gsz_test_estate_checkpoint_verified_0' /workspace/gsz-tools/bin/php tests/test_estate_database.php
GSZ_TEST_DSN='mysql:unix_socket=/workspace/gsz-tools/db/mysql.sock;dbname=gsz_test_estate_checkpoint_pdf_final' GSZ_TEST_ESTATE_PDF=/workspace/gsz-tools/estate-statement-test.pdf GSZ_TEST_ESTATE_EXPORT=/workspace/gsz-tools/estate-test-export.zip /workspace/gsz-tools/bin/php tests/test_estate_settlement_database.php
GSZ_TEST_URL=https://127.0.0.1:8460 node tests/test_estate_browser.cjs
GSZ_TEST_URL=https://127.0.0.1:8461 node tests/test_estate_settlement_browser.cjs
# Nach befüllter Abrechnungs-Browserfixture:
GSZ_TEST_URL=https://127.0.0.1:8461 node tests/test_estate_export_browser.cjs
GSZ_TEST_URL=https://127.0.0.1:8460 node tests/test_gesamt_browser.cjs
GSZ_TEST_DSN='mysql:unix_socket=/workspace/gsz-tools/db/mysql.sock;dbname=gsz_test_estate_final_0' GSZ_TEST_PHP=/workspace/gsz-tools/bin/php node tests/test_estate_concurrency.cjs
GSZ_TEST_DB_SOCKET=/workspace/gsz-tools/db/mysql.sock GSZ_TEST_DB_SOURCE=gsz_test_estate_checkpoint_pdf_final GSZ_TEST_DB_TARGET=gsz_test_estate_checkpoint_restored GSZ_TEST_MYSQL=/workspace/gsz-tools/bin/mariadb GSZ_TEST_MYSQL_DUMP=/workspace/gsz-tools/bin/mariadb-dump python3 tests/test_estate_backup_restore.py
pdftotext /workspace/gsz-tools/estate-statement-test.pdf /workspace/gsz-tools/estate-statement-test.txt
```

Die hier genannten DB-Namen sind bereits befüllt: bei Wiederholung neue passende Namen wählen. Browser: eigene frische DB pro Suite, tests/prepare_estate_browser.php bzw. prepare_estate_settlement_browser.php ausführen, App per HTTPS-Testproxy und privaten Sessions mit ausschließlich synthetischen GSZ_TEST-Zugangsdaten bereitstellen. Node/Python/Chromium sind nur lokale Testwerkzeuge, keine Produktionsanforderungen. Konkurrenztest verlangt exakt die unveränderte 70000-Cent-Zahlungsfixture aus der Kern-DB-Suite und verändert diese synthetisch.

## Noch nicht verifiziert / Grenzen

Keine echten Volksbank-/Sparkassenexporte oder Online-/PSD2-Anbindung, keine offizielle ista-API/Formatparser, kein OCR, keine native STRATO-Installation/IMAP. Heizkosten-/CO2-/Vertrags-/Fristenprüfung durch fachkundige Stelle erforderlich. Keine Behauptung gesetzeskonformer/revisionssicherer vollständiger Abrechnung oder GoBD-Abnahme. Keine automatische steuerliche Übernahme privater Ergebnisse in die Unternehmens-EÜR.

Abrechnungssalden noch kein eigener Zahlungs-/Forderungsausgleichsworkflow. Historische Sollvorauszahlungen nicht zum finalen Saldo addieren. Kaution ohne Zins-/Verwahrungsrechtsprüfung. Komplexe Vertragsnachträge und Ausgaben-Reklassifizierungen offene Entwicklungsarbeit. Kostenabgrenzung und Datenvollständigkeit ausdrücklich prüfen; leeres Kostenregister blockiert. Finale Quellen-/PDForiginale technisch schreibgeschützt durch Anwendung, Serveradmin/DB-Admin könnte Dateien/Daten manipulieren; Hash-/Auditkontrolle ist keine externe Zeitstempel-/WORM-Garantie.

Paketdateiliste/Hashmanifest und Prüfsummen separat mitgeliefert; keine Uploads, Schlüssel, config.php, .env, Dumps oder Sicherungsarchive im Anwendungspaket. Kein Live-Deployment durchgeführt.

## Abschließende Lieferprüfung

Bereinigtes vollständiges ZIP separat nach /workspace/gsz-tools/package-smoke-v330 entpackt: alle135 enthaltenen PHP-Quellen syntaxgeprüft, Estate-Mathematik9/Kern186, bestehender Unicode-Mehrseiten-Rechnungstest und Vault14 bestanden; integrierter Abrechnungs-DB-Test aus dieser entpackten Anwendung auf neuer leerer gsz_test_estate_package_smoke ebenfalls32 bestanden. Nach diesem Code-Abnahmelauf ausschließlich Dokumentation/Dateimanifeste aktualisiert und Archive erneut erzeugt.

Archivpfade/keine Symlinks, CRC, gesperrte Laufzeit-/Config-/Schlüssel-/Dump-/Backupdateien, bekannte ursprüngliche Secretwerte und Tokenmuster geprüft. Vollständiges Paket245 Dateien;65 geänderte/neue Dateien gegenüber gesicherter v300-Basis, keine Löschungen. Rekonstruktion v300+Update sowie Phase03+zugehöriges Update exakt gleich vollständigem Paket. Vollständiges separates SHA-256-Dateimanifest und ZIP-Prüfsummen beigefügt. Das Paket enthält keine Testuploads oder Testdatenbankinhalte.
