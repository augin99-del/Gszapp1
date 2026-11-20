## Aktueller Nutzerstopp 10.11.2026

Spätere Ergänzungen v270–v300 sind gesichert; aktuelle Teiltestergebnisse und offene Gesamtprüfungen TESTBERICHT_SICHERUNGSSTAND.md. Historische Erfolgs-/Dateizahlen unten gelten für den damaligen Stand und sind keine Gesamtabnahme des neuesten Pakets. Auf neuen Plan warten.

# Testbericht Gesamtstand – 09.11.2026

## Aussagegrenze

Implementierter, lokal getesteter Zwischenstand ab exakt GSZ-Phase03.zip; der Gesamtauftrag ist noch nicht vollständig abgeschlossen. Kein Live-Deployment, kein Zugriff auf gszintern.de, keine echten E-Mails, Bankaktionen, bezahlten APIs oder produktiven Daten. Externe Komponenten und Steuerkonformität nicht durch Mocks bewiesen. Interne Restarbeiten: ENTWICKLUNGSREST.md; externe Freigaben: OFFENE_PUNKTE.md.

## Umgebung und Quellbasis

Linux /workspace; PHP 8.4.24 mit PDO MySQL, mbstring, iconv, fileinfo, zlib; MariaDB 11.8.6 ausschließlich lokale Testdatenbanken über UNIX-Socket, Server ohne TCP. PHP-IMAP nicht installiert. Native IMAP-/SMTP-TLS-Strecken deshalb nicht erfolgreich nachgewiesen. Node/Playwright mit /usr/bin/chromium ausschließlich lokale Testwerkzeuge, keine Produktionsabhängigkeit. Browser HTTPS über lokale TLS-Brücke, PHP-Entwicklungsserver nur Loopback. Python nur für lokale Paket-/Restore-/Harnessprüfungen.

Quelle GSZ-Phase03.zip SHA-256: `139d1a4d291f5c55bae3d7a151e7ca53cce9e0dd9e2decffd21df1629383e815`. Sicher entpackt, keine Traversal-/Symlink-Einträge. 25 ursprüngliche Tabellen untersucht; Gesamtstand 40 Tabellen. Keine Geheimnisse aus config.php oder verschachtelten Altsicherungen übernommen.

## Ausgeführte isolierte PHP-Tests

Im Projektverzeichnis; `PHP=/workspace/gsz-tools/bin/php` war das tatsächlich verwendete lokale Binary. Auf einer eigenen Testmaschine entsprechend `php` ersetzen.

```sh
$PHP tests/test_money.php
$PHP tests/test_schema.php
$PHP tests/test_invoice_pdf.php
$PHP tests/test_recurring.php
$PHP tests/test_planning.php
$PHP tests/test_waste_routing.php
$PHP tests/test_mail_core.php
$PHP tests/test_finance_core.php
find public private tests -path private/uploads -prune -o -name '*.php' -print0 | xargs -0 -n1 "$PHP" -l
```

Ergebnisse: Money/CSV und statische Schemaprüfung bestanden; PDF-Prüfung mit 80 Positionen/Mehrseiten/Font und Grenzen bestanden; Wiederholungen 73, Planung 24, Abfall/Routing 32, Mail/Sicherheit 25, Finanzkern 18 Assertions bestanden. Syntax aller ausgelieferten PHP-Dateien bestanden (finale Anzahl im Paketprüfbericht). Keine statische Schemaprüfung als DB-Integration ausgegeben.

Bewusste Änderungen an alten Erwartungen: lange PDFs werden nun mehrseitig unterstützt statt abgewiesen. Neue Fenster-/Tiefgaragen-Seeds verwenden gemäß aktuellem Auftrag 90 Minuten; bestehende Daten/abweichende editierte Werte werden nicht migriert. Tests spiegeln diese fachliche Änderung, kein Überspringen alter Fehler.

## MariaDB-Integration und Wiederanlauf

Für jeden Lauf eine separate LEERE Datenbank `gsz_test_*` anlegen. Tests verweigern nichtleere oder nichtlokale DSNs. Beispiel des tatsächlich ausgeführten Musters (je Test anderer Name):

```sh
GSZ_TEST_DSN='mysql:unix_socket=/workspace/gsz-tools/db/mysql.sock;dbname=gsz_test_handover_mail' "$PHP" tests/test_mail_database.php
GSZ_TEST_DSN='mysql:unix_socket=/workspace/gsz-tools/db/mysql.sock;dbname=gsz_test_handover_finance' "$PHP" tests/test_finance_database.php
GSZ_TEST_DSN='mysql:unix_socket=/workspace/gsz-tools/db/mysql.sock;dbname=gsz_test_handover_documents' "$PHP" tests/test_business_documents.php
GSZ_TEST_DSN='mysql:unix_socket=/workspace/gsz-tools/db/mysql.sock;dbname=gsz_test_handover_p1' "$PHP" tests/test_phase1_database.php
GSZ_TEST_DSN='mysql:unix_socket=/workspace/gsz-tools/db/mysql.sock;dbname=gsz_test_handover_p2' "$PHP" tests/test_planning_database.php
GSZ_TEST_DSN='mysql:unix_socket=/workspace/gsz-tools/db/mysql.sock;dbname=gsz_test_handover_p3' "$PHP" tests/test_phase3_database.php
```

- Mail: **34 Assertions bestanden**. UIDVALIDITY-Wechsel, Duplikate, wiederholte Sync, Flagrefresh, Move/same-folder-Schutz, Entwurfs-/Anhangsgrenzen, SMTP-Fehler und unklarer Ausgang, keine automatische Wiederholung, manuelle Prüfentscheidung, Auditkette und Migration auf gefülltem Schema. SMTP/IMAP mit Fakeklassen, kein reales Postfach.
- Finanzen: **25 Assertions bestanden**. Importvorschau/Bestätigung/Duplikate/Konflikte, archiviertes Original, Bestätigung/Korrekturketten, Zahlung/Teilzahlung/Erstattung/Gegenzeile, Cent-/USt-Summen, Arbeits-EÜR, Export und befüllte Migrationswiederholung.
- Angebote/Rechnungen: **14 Assertions bestanden**. Angebotsnummern/Freeze, idempotente Übernahme in vorhandene Rechnungsbasis, technischer Referenz-Vollstorno und unveränderte ursprüngliche Positionen.
- Phase 1: **39**, Phase 2: **10**, Phase 3: **21 Assertions bestanden**. Sieben Objekte/Regeln, Vorschau/Bestätigung, Sperren/Erledigt, verifizierte Kalendertermine/Jahreswechsel/Mehrfraktionen/Konflikte, Sonderregel und Offline-/Fake-Routing erhalten.

Zusätzlicher Upgrade-Test: dump einer lokalen v220-Testdatenbank in unabhängige Kopie, alle bisherigen Spalten/Zeilen erfasst, Gesamtmigration zweimal ausgeführt und verglichen: **alle 25 ursprünglichen Tabellen unverändert**, ausgenommen erwartete neue Migrationseinträge. Das ist eine Kopie der mitgelieferten Phase-3-Struktur, keine Kopie unbekannter Produktionsstruktur.

## Backup/Restore

Tatsächlicher lokaler Lauf von `gsz_test_handover_finance` nach frisch leerem `gsz_test_handover_restore`:

```sh
mariadb-dump --socket=/workspace/gsz-tools/db/mysql.sock -uroot --single-transaction --skip-comments --skip-dump-date --order-by-primary --skip-extended-insert --skip-add-locks --skip-disable-keys gsz_test_handover_finance > GESCHUETZTER_TESTPFAD.sql
mariadb --socket=/workspace/gsz-tools/db/mysql.sock -uroot gsz_test_handover_restore < GESCHUETZTER_TESTPFAD.sql
```

Erneuter Dump mit identischen Optionen bytegleich: **48.620 Bytes, 40 Tabellen**. Auditkette nach Restore gültig, 13 Ereignisse. Das lokale Rootkonto ohne Passwort gehört nur zum isolierten synthetischen Server. Auf Hosting sichere Optionsdatei verwenden, niemals dieses Authentifizierungsmuster übernehmen. Dumps/Belegdaten/Dateicaches sind nicht im Paket. Kein vollständiger Hosting-/Dateisystem-Desastertest behauptet.

## Browser, Mobilität, Sicherheit

Tatsächlich erneut ausgeführt:

```sh
GSZ_TEST_URL=https://127.0.0.1:8451 node tests/test_gesamt_browser.cjs
GSZ_TEST_URL=https://127.0.0.1:8451 GSZ_MAINTENANCE_URL=https://127.0.0.1:8455 node tests/test_security_browser.cjs
```

**42 Gesamtprüfungen + 12 Sicherheitsprüfungen bestanden.** Cachepaginierung 30/5, escaped Mailtext, Entwurf/gesperrter Versand, CSV-Vorschau/Übernahme, Originalbeleg, Angebot/Übernahme/Ausstellungssperre, fehlender CSRF 403, 11 Kernansichten bei 390px und 1440px ohne horizontalen Overflow. Sicherheitsprüfungen: unautorisierte Exporte/Downloads, SQL-Injection-Login, PHP als Bild abgewiesen, Traversalversuch, Download attachment/nosniff und byteidentisches Original, Wartung503. Kein vollständiger Penetrationstest.

Bisherige Browserregressionen mit aktuellem Quellstand bestanden: `test_browser.cjs` 8, `test_planning_browser.cjs` 11, `test_phase3_browser.cjs` 17 Prüfungen (lokale HTTPS-URLs 8452/8453/8454 mit getrennten synthetischen Datenbanken). Keine externen Karten/Mails aufgerufen.

Reproduktion Gesamtbrowser: leere lokale gsz_test_*-DB mit `tests/prepare_gesamt_browser.php` und GSZ_TEST_DSN vorbereiten. Synthetischer Login owner@example.invalid / Local-Test-Only-2026!, 35 lokale Nachrichten und sieben Objekte; niemals produktiv einsetzen. Webserver auf diese Testdatenbank konfigurieren, GSZ_MAIL_FROM=owner@example.invalid, synthetische Rechnungsstellerdaten setzen, alle Netzwerk-/Versand-/Ausstellungsflags false lassen. Zweite Instanz für Wartung mit GSZ_MAINTENANCE=true. PHP braucht einen existierenden beschreibbaren privaten session.save_path. Nach Umgebungserneuerung fehlte das Standardverzeichnis; im lokalen Harness mit -d session.save_path=/workspace/gsz-tools/browser/sessions-nov10 korrigiert und die zunächst fehlgeschlagene CSRF-/Loginprüfung wiederholt. Lokales HTTPS (eigene TLS-Brücke oder Staging-Webserver) notwendig; Produktionsbootstrap wird nicht für HTTP abgeschwächt. Chromium und Playwright lokal bereitstellen; Browsertests schreiben ausschließlich synthetische Fixtures. Tests nicht gegen Produktivdomain starten. TLS-Brücke/Testzertifikate sind bewusst keine Lieferdateien.

## Weitere gezielte Prüfungen

- CLI-Adminersteinrichtung auf leerer Admin-Tabelle erfolgreich; wiederholt verweigert ohne Änderung. Passwort nur via stdin; kein Websetup.
- Mehrseitiger PDF-Test mit pdftotext: alle 80 Positionen, Umlaute und Endsumme erhalten; 10 Seiten. Erste Seite gerendert und visuell geprüft. Keine formale PDF-A-/Rechnungs-/E-Rechnungsvalidierung.
- Belegexport mit Python zipfile geöffnet, CRC und sämtliche SHA-256-Einträge geprüft (7 Einträge im Testexport). Kein DATEV-Nachweis.
- Paketprüfung getrennt in PAKETPRUEFUNG_GESAMT.md; exakter Delta-/Hashnachweis in UPDATE_DATEIEN_GESAMT.md.

## Grenzen und Fehlerbehandlung

Bei der Umsetzung gefundene Fehler (u. a. unpassende UI-Testselektoren, fehlender synthetischer Absender im Testharness, Cent-/Refund-/Reentryfälle) wurden vor Wiederholung korrigiert; keine fehlgeschlagenen Tests übersprungen. Sandboxzugriff auf lokalen UNIX-Socket brauchte Netzwerkfreigabe; danach regulär bestanden. Kein Kontakt zu Produktion erforderlich.

Nicht geprüft/noch offen: tatsächliches STRATO, native TLS/IMAP/SMTP mit kontrolliertem Server, große Postfächer/Dateimengen/Parallelität, reale Sparkassenformate/CAMT/MT940, vollständige Steuer-/Konten-/E-Rechnungsfallmatrix, unveränderlicher Rechnungssnapshot, Malwareprüfung, vollständige Accessibility/Datenschutz-/GoBD-Abnahme. Externe Integrationen bleiben ausdrücklich nicht verbunden. Interner Entwicklungsrest kann ohne Zugangsdaten weiterbearbeitet werden und wird nicht als externer Blocker ausgegeben.


## Tatsächliche Fortsetzung am 10.11.2026

Nach Umgebungserneuerung wurden zuerst erhaltene Dateien geprüft und gesichert; Dienste mussten neu gestartet werden. Die alten Testaussagen oben sind dokumentierte Ergebnisse vom 09.11., keine Behauptung weiterlaufender Prozesse. Alle neuen Läufe verwenden ausschließlich synthetische lokale Daten.

Erneut ausgeführt: alle acht Unit-Suiten aus obigem Befehlsblock bestanden; Syntax **82 PHP-Dateien bestanden**. Neue leere Datenbanken gsz_test_nov10_mail / finance / documents mit den gleichnamigen Testskripten: **34 / 25 / 26 Assertions bestanden**. Dokumenttests ergänzen archivierte Angebots-/Storno-PDFs, byteidentische Wiederausgabe trotz abweichender Darstellungsfelder, Transaktionspflicht, Wiederholung, Ablehnung von Entwurfsarchivierung, Manipulations-/Fehlendprüfung ohne Regeneration, Rollback und unveränderte historische Daten bei wiederholter Migration. Neue Migration v260 fügt nur nullable Archivspalten an invoices hinzu, Anzahl40 Tabellen unverändert.

`GSZ_TEST_DSN='mysql:unix_socket=/workspace/gsz-tools/db/mysql.sock;dbname=gsz_test_nov10_browser' php tests/prepare_gesamt_browser.php` auf leerem Schema bestanden: sieben Objekte/35 synthetische Cachemails. Gesamtbrowser auf dieser frischen Fixture mit gleicher lokaler HTTPS-URL erneut ausgeführt; Endergebnis im Paketprüfbericht. Keine Kontoverbindung. Genaues Delta-/Hashmanifest und ZIP-Scan folgen als separate Dateien; keine Vollfertigstellung behauptet.


Abschließender v260-Restore am 10.11.: `gsz_test_nov10_documents` per oben genannten Dumpoptionen nach `gsz_test_nov10_restore` gesichert/wiederhergestellt: logische Dumps bytegleich **45.439 Bytes**; Audit gültig **8 Einträge**. Datenbankbeweis umfasst keine komplette Hosting-/Dateisystemwiederherstellung.

Die neue Browserfixture benötigte zusätzlich einen synthetischen Kunden für Angebote; zunächst fehlte er, worauf der Angebotstest korrekt scheiterte. Fixture ergänzt und auf einer weiteren leeren Datenbank `gsz_test_nov10_browser2` neu aufgebaut. Keine Produktivdaten repariert und keine Tests übersprungen. Gesamtbrowser um zwei Prüfungen auf herunterladbares PDF und byteidentische Wiederholung erweitert; finale Ergebnisse im Paketbericht.

Finaler Abschlusslauf: Gesamtbrowser **44** und Sicherheitsbrowser **12** Prüfungen bestanden. Ausgelieferte PHP-Dateien: **82**, Syntax bestanden. Aktueller Gesamtstand bleibt ein getesteter Zwischenstand mit dokumentiertem Entwicklungsrest.
