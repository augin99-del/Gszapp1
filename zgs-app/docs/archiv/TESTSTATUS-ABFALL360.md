# Testbericht Abfall360 – 12.11.2026

Status: lokal getesteter Installationskandidat, keine Produktiv-/STRATO-Freigabe. PASS bedeutet tatsächlich ausgeführt; synthetische Tests sind kein echter Kundenkalender. Die allgemeinen bisherigen Berichte bleiben historische Nachweise für andere Module.

Umgebung: Linux, PHP8.4.24, PDO/MySQL/mbstring/OpenSSL/fileinfo/DOM/SimpleXML, MariaDB11.8.6 über lokalen Unix-Socket, Chromium/Playwright mit390px und1440px. Kein natives IMAP. Testwerkzeuge außerhalb der ausgelieferten Produktionsruntime; PHP/MariaDB bleiben deren einzige Laufzeitbasis.

| Prüfung | Tatsächliches Ergebnis |
|---|---|
| PHP-Syntax private/public/tests | PASS:171 PHP-Dateien, keine Syntaxfehler |
| Neue Abfallverbindungs-DB-Suite | PASS:93 Assertions auf frischem84Tabellen-Schema; synthetischer Provider |
| Bestehende PHP-/DB-/HTTP-Regression | PASS:33/33 Suiten, darunter Phasen1–3, Planung, Mail, Belege, Finanzen, Immobilien/Nebenkosten, Installation |
| Ergänzende FINAL-Datenbank | PASS:37 Assertions |
| v1 Datenbank / Integrationen | PASS:21 /28 Assertions |
| Historischer330→360 Upgrade | PASS:84 Assertions, alte Datensätze/PDF/Owner erhalten |
| Historischer350→360 Upgrade | PASS:90 Assertions, alte Datensätze/PDF/Owner erhalten |
| Exakt aus maßgeblicher350-ZIP aufgebaute Upgrade-/Restorekopie | PASS:82→84Tabellen, alle ursprünglichen Spalten/Werte der81Geschäftstabellen unverändert;53 synthetische Datensätze,7Bestandsobjekte/Regeln und bestätigte Müllaufgaben erhalten; Migration und rohes additives360-SQL wiederholt |
| Neuer Browserablauf | PASS:50 Prüfungen, Neuanlage mit Service → Discovery → Tonnenauswahl → Vorschau → Bestätigung → Dashboard/Tagesplan → Erledigung → Resync → Deaktivierung; gemeinsames Layout390/1440, CSRF/XSS |
| Bestehende Browserregressionen | PASS:12 Suiten/217 Prüfungen (Planung11, Objekte29, Phase3Import17, Gesamt44, Sicherheit11, Immobilien27, OffenePosten15, Finanzwiederholungen12, Kasse22, Nebenkosten17, Export4, Altoberfläche8) |
| Praxistest Tagesgeschäft | PASS:48 Prüfungen, inklusive Wiederanmeldung/Erledigung/Verschiebung/Beleg |
| Alle-Module-Design / Wiederholung | PASS:234 Prüfungen,390/1440px |
| FINAL-Browser | PASS:26 Prüfungen, einschließlich abgesichertem Webupdate auf360 |
| Installations-Browser | PASS:8 Prüfungen (HTTPS/Login/Sessionrotation/Logout/Downloadschutz); reines HTTP zusätzlich in bestehenden Sicherheitsprüfungen geprüft |
| Aus dem tatsächlich erzeugten Voll-ZIP frisch installiert | PASS:29 Installations-/Systemprüfungen und9 Integritätsprüfungen; alle171 PHP-Dateien aus dem Archiv syntaktisch geprüft |
| Datei-/Releaseintegrität | PASS:9 Assertions, vollständiges WASTE360-Code-/Assetmanifest |
| Backup/Restore aktuelle synthetische Installation | PASS:84Tabellen, logischer Dump232095Bytes identisch,180 private Testdateien identisch,1Dokumentverknüpfung samt Hash und erledigte/verschobene Aufgaben erhalten |
| Amtliche HTTPS-Quellen | PASS:beide kommunalen Verweisseiten und tatsächliche öffentliche JSON-Daten über TLS-validiertes urllib abgerufen;25Orte/59Kalenderarten, GT1106/RH714Straßen |
| Adapter gegen echte Antworten | PASS:GT Diekstraße245Quelltermine, RH Auf der Warte211; echte Behältervarianten korrekt angeboten, allgemeine Veranstaltungstermine nicht als Tonnen interpretiert; keine tatsächlichen Aufgaben in Kundendaten erstellt |
| Direkter nativer PHP-TLS-Abruf hier | BLOCKED:Kalenderverbindung/TLS in dieser Entwicklungs-Netzumgebung nicht bestätigt. Kein Zertifikatscheck deaktiviert. Egress über geprüften Pythontransport funktioniert; das ist ausdrücklich kein Beweis des nativen PHP-Transports auf STRATO |
| Alle realen Bestandsadressen/Behälter | BLOCKED:keine produktive DB benutzt, PLZ/uneinheitliche Ortsangaben ungeklärt; keine automatische Datenkorrektur oder Aktivierung beim Update |
| Echtes STRATO/Cron/Produktivübernahme | NOT TESTED:kein Live-Zugriff, keine Freigabe, keine produktive Migration |

## Reproduzierbare Befehle

Im Projekt, mit PHP-CLI und jeweils eigener leerer lokaler synthetischer Testdatenbank. Der DSN-Schutz lehnt Produktionsnamen/Remote-DSNs ab. Keine echten Zugangsdaten einsetzen.

```sh
rg --files private public tests -g '*.php' | xargs -n1 php -l
GSZ_TEST_DSN='mysql:unix_socket=/lokaler/test/mysql.sock;dbname=gsz_test_waste360' php tests/test_waste_connections.php
GSZ_TEST_DSN='mysql:unix_socket=/lokaler/test/mysql.sock;dbname=gsz_test_final_upgrade350' GSZ_TEST_SOURCE_SCHEMA=350 php tests/test_final_upgrade.php
GSZ_TEST_DSN='mysql:unix_socket=/lokaler/test/mysql.sock;dbname=gsz_test_final_core' php tests/test_final_database.php
php tests/test_final_integrity.php
GSZ_TEST_DSN='mysql:unix_socket=/lokaler/test/mysql.sock;dbname=gsz_test_wastebrowser_demo' php tests/prepare_waste_connections_browser.php
GSZ_TEST_URL=https://127.0.0.1:8494 node tests/test_waste_connections_browser.cjs
GSZ_TEST_URL=https://127.0.0.1:8494 node tests/test_golive_browser.cjs
GSZ_TEST_URL=https://127.0.0.1:8494 node tests/test_v1_browser.cjs
```

Browser-Abfalltest benötigt ein getrenntes lokales HTTPS-Testhosting mit derselben Fixture-Datenbank (GSZ_DB_SOCKET/NAME/USER/PASS nur serverseitig); der mitgelieferte prepare_waste_connections_browser.php erzeugt den synthetischen amtlichen Cache im gesonderten lokalen Fixture, keine echte Quelle: /orte, Straßenliste, Straßendetail, /fraktionen, Termine fürIDs0/8 und8. Bereitstellung und TLS-Proxy in dieser Sitzung durch `/workspace/gsz-tools/prepare_waste360_browser.php` und `start_waste360.py`; diese privaten lokalen Testwerkzeuge sind keine Produktivdateien. Bereits vorhandene prepare_* und Browser-Tests im tests/-Verzeichnis bleiben erhalten. Bestehende Wiederherstellungsprüfung mit GSZ_TEST_DB_SOURCE=gsz_test_golive_waste360 und separatem GSZ_TEST_DB_TARGET, GSZ_TEST_MYSQL/GSZ_TEST_MYSQL_DUMP auf lokale Werkzeuge: `python3 tests/test_installation_restore.py`. Reale Antworten nur außerhalb des Release ausgewertet (`probe_waste_adapter.php`, `probe_waste_adapter_rh.php`).

Ausgeführte gesamte33er-Suite: test_money, test_schema, test_invoice_pdf, test_recurring, test_planning, test_waste_routing, test_mail_core, test_finance_core, test_operations, test_finance_recurrences, test_cash_assets, test_estate_core, test_estate_calculation, test_secret_vault, test_mail_recovery_imap, test_final_core; Datenbank/HTTP: test_phase1_database, test_planning_database, test_phase3_database, test_finance_database, test_business_documents, test_operations_database, test_receivables_database, test_finance_recurrences_database, test_cash_assets_database, test_estate_database, test_estate_settlement_database, test_mail_database, test_mail_recovery_database, test_upgrade_gesamt, test_installation_database, test_asset_classification_database, test_mail_recovery_http. PHP-Dateiendung jeweils .php. Jeder DB-Lauf nutzte ein eigenes bewachtes leeres gsz_test_*-Schema.

## Abnahme der20 Abfallanforderungen

Neuanlage/Discovery/Auswahl/Aufgaben/Dashboard/Planer/Mobil: Browser50. Adress-Eindeutigkeit/Mehrdeutigkeit/Hausnummernbereich/Unvollständigkeit/Ausfall/Leere/Format/Scope/Deaktivierung/Änderung/Idempotenz/Schutz/Datenkorrektur: neue DB93. Quelle und Fraktionen: echte öffentliche Antworten beider Kommunen plus synthetische Adaptertests. Monats-/Jahreswechsel, ICS-DST, unbekannte Fraktion und manueller ICS/CSV-Workflow: bestehende Phase3-/v1-Regressionen. Feiertagsverschiebung bedeutet ausschließlich Übernahme eines tatsächlich gelieferten Datums, keine selbstberechnete Feiertagslogik. Fontänen-Sonderidentität bestehender Phase3-Test; bewusste Service-/Tonnenabwahl neuer93er-Test. Echtes natives STRATO-Netz/Cron sowie konkrete Freigabe aller Bestandsobjekte bleiben offen und werden nicht als PASS ausgegeben.

Während der Entwicklung korrigiert: SQL-NULL bei unveränderten Aufgabennotizen, falscher historischer Test-Tabellencount82, Test-DSN-Namensschutz/Einspalten-Snapshot im lokalen Testharness, nicht eindeutiger Browserselektor wegen versteckter Objektoptionen. Der anfängliche Systemcheck-Browserlauf während noch geändertem Code meldete korrekt Manifestabweichung; nach Manifestregeneration wieder bestanden. Keine verbleibenden FAIL in den abgeschlossenen hier aufgeführten Tests. Paket-/Secretprüfung siehe RELEASE-PRUEFUNG-ABFALL360.md.
