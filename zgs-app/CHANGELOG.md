#12.11.2026 – Abfallautomatik360

Siehe CHANGELOG-ABFALL360.md und UPDATE-DATEIEN-ABFALL360.md. Provider GT/RH, Tonnenauswahl, gesicherte Synchronisierung, explizite Datumsänderung, bestehende Tasks/Regeln erhalten. Keine Produktivänderung.

# Aktueller FINAL-Stand 12.11.2026 / Schema350

Maßgeblich: INSTALLATION-STRATO.md, TESTSTATUS-FINAL.md, MODULE-STATUS.md, CHANGELOG-FINAL.md, INTEGRATIONEN-FINAL.md und CODEX_FORTSETZUNG.md. Neu: täglicheRegeln, integriertesDashboard/Navigation, CAMT/Kontentrennung/Review, OfflineSEO, geschütztesBestandsupdate/Dateiintegrität. Schema350/82Tabellen; keineProduktivinstallation, echteMail/Bank/Cron/Objektkalender weiter nichtnachgewiesen. Aussagenzu330/340nachfolgend sindHistorie. LiveSEO-/GSC-/GBP-Adapter,OCRundvalidierteE-Rechnung nichtalsfertigausgeben.

---

# Version1.0 STRATO-Kandidat –11.11.2026

- Additive Migration340: Abschlussereignisse/Laufzustand und Planungs-/Regel-/BCC-/Objektversatzfelder; keine Umschreibung historischer Belege/Aufgaben. Migrationen überspringen protokollierte Schritte.
- Kapazitätsbasierte Mo–Fr-Woche, Treppenhaus Mo–Mi, Konflikte statt stiller Überplanung; geschützte Aufgaben erhalten. Fenster/Tiefgarage alle3Kalendermonate ab tatsächlichem Abschluss (editierbar).
- Unverbindliche automatische Wochenvorschläge nach Änderungen; neue Bestätigung nötig. Plan-/Routenübernahme aktualisiert optimistische Aufgabenversionen.
- Sicherer optionaler HTTPS-ICS-Abruf/Review/Remapping; Raus-/Reinversatz jeObjekt für neue Aufgaben. Keine neue API/Abfuhrdaten erfunden.
- Private externe Konfiguration und Rechtecheck/Reparatur-CLI; SMTP465TLS mit vorhandener PHPMailer-Komponente, BCC und sichere Maildiagnostik.
- Gemeinsames vorhandenes Dashboardlayout für HTML-Module, reduzierte Tageszentrale/26Module, Prognose von aktueller Zeit getrennt; keine technischen Herkunfts-IDs in normalen Aufgabenkarten.
- Exklusive Hintergrundläufe/Fehlerstatus/manueller Ersatz; umfassende lokale Regression. Kein Produktivdeployment, keine echten Mails, extern weiterhin nicht vollständig abgenommen.

---

# Installationsstand331, 11.11.2026 – Schema330 unverändert

- Bestehende Migrationen sicherer CLI-Fresh-Installer mit Leer-/Version-/Zeichensatz-/Sperrprüfung, geschütztem separatem erstem Adminsetup und kontrolliertem Upgradeaufruf.
- Read-only Hosting-/PHP-/DB-/Schema-/Rechte-/Session-/Dateisystemcheck, nur nach Login im Web; explizite flüchtige CLI-Speicherprobe, keine Secretwerte/externe Abfragen.
- Fällige Regelaufgaben direkt im Tagesplan aktualisieren, GET-/Crontexte korrigiert, Regelaktionen PRG. Bestehende Vorschau/Freigabe/Sperren beibehalten.
- Fehlerhafte Dateinamenbereinigung im Dokument-/Fotoupload durch vorhandene zentrale Funktion ersetzt; Uploadfehlercode ohne Exceptiondetails. Mobile Schrift-/Touchgröße verbessert, keine Designneuentwicklung.
- Praxistag mit Browserneustart, Installation/Sicherheit/Regression/Restore geprüft. Alle bestehenden Buchhaltungs-/Mail-/Immobilienmodule erhalten; keine neuen Onlineintegrationen, keine zusätzliche SQL-Migration, kein Deployment.

---

# Checkpoint v330, 10.11.2026 – neue Pakete310/320/330

- Immobilien: eigene Stammdaten/Verträge/Konditionen, Sollvorschau, Teilzahlungen/Kaution/Gegenzeilen, private Finanzübersicht und sichere Originalablage.
- Zwei getrennte Volksbank-Kontoslots mit neutralem CSV, Dubletten-/Quellenkonfliktprüfung, explizitem Matching und eigener Umbuchungsprüfung; keine Onlinebankaktionen.
- Nebenkosten: additive Migration320, Quellen-/Kostenregister, rechtlich zu prüfende Umlagen, reale Vorauszahlungen, Schaltjahr/Mieterwechsel/Leerstand/Eigennutzung, Messdienst-/CO2-Werte, Vorschau/Freigabe, unveränderliche Versionen und Einzel-PDF, Beraterarchiv mit Hashes.
- Schutz vor überlappenden endgültigen Perioden, gebäudeübergreifender Quellen-Doppelbelastung und rückwirkendem Wechsel bestätigter Kostenvereinbarungen.
- Mail330: tatsächlich akzeptierte SMTP-Originalbytes und UIDVALIDITY-/Message-ID-geprüfte Gesendet-Recovery ohne erneuten SMTP-Versand.
- Tests, Restore-Nachweis, Installations-/Bedienungs-/Fortsetzungsdokumentation ergänzt. Bestehende Geschäftsrechnungen/Planung/Objektidentitäten erhalten; kein Deployment.

---

# Immobilien-Grundlage v310 und Mail-Stabilisierung v330

- Getrennte Gebäude/Wohnungen/Mieter/Verträge und wirksame Mietkonditionen.
- Monatsvorschau/Bestätigung, tatsächliche Zahlungen, Komponenten/Teilzahlungen/Kaution und Gegenzeilen.
- Zwei private Volksbank-Kontoslots, neutraler CSV-Import, Quellenkonflikte/Dubletten und bestätigbare Umbuchungen.
- Eigenes Immobilienarchiv/CSV-Arbeitsdatenexporte, Auth/CSRF/Versionen/Audit.
- Nebenkosten-Mathematikkern mit186 Tests; noch keine fertige PDF-/DB-Abrechnung.
- Sent-Kopie nach SMTP-Annahme sicher prüfen/wiederherstellen, keine SMTP-Wiederholung;51 Recoverytests.
- Keine Live-/Kosten-/Bank-/Mailaktion, keine fremde unklar lizenzierte Funktion kopiert.

# Wiederaufnahme: geprüfte Gesamtintegration + SMTP/Vault

- Offene v300-Gesamtregression/Restore nachgeholt.
- PHPMailer7.2.0 SMTP-Komponente lizenzgeprüft integriert; native Rückfalloption, gemeinsame TLS-Fehlersuite16 bestanden.
- Optionaler libsodium-Secret-Vault mit geschützten Serverdateien/CLI,14 Tests; vorhandene Konfiguration bleibt ohne Aktivierung kompatibel.
- Neue Fortsetzungs-/Entscheidungs-/Kosten-/Bibliotheksdokumentation und versionierter Checkpoint.
- Immobilien/Nebenkosten und externe Bank-/Mailintegrationen ausdrücklich noch nicht vollständig umgesetzt.

# Sicherung bei Nutzerstopp – 10.11.2026

- v280: offene Posten, bestätigbare Zahlungserinnerungen und archiviertes PDF, keine Gebühren/Sendung.
- v290: wiederkehrende Rechnungsentwürfe/Ausgaben-Prüfaufgaben mit idempotenter Herkunft und Vorschau.
- v300: Kassenbewegungsregister und manuell geprüfte Anlagenpläne; Anlagenzahlungsprüfung im Arbeitsbericht.
- Native SMTP/TLS lokal gegen Testserver geprüft; keine Echtanbindung aktiviert.
- Neuer Plan angekündigt: Entwicklung pausiert, Zwischenstand und offene kombinierte Tests dokumentiert.

# P1-Betriebsworkflow v270 – 10.11.2026

- Kundenpflege/Archivierung, Objekt-Kundenzuordnung, optionale organisatorische Mitarbeiter-/Subunternehmerzuordnung.
- Versionierte Aufgabenänderungen/Erledigung/Notizen, nachvollziehbare Historie ohne Pflichtfoto/-nachweis.
- Editierbare und stornierbare Fixtermine, lokale Erinnerungen und DST-Validierung.
- Keine Aufgabenmutation durch Dashboard-GET; Wiederholungen per ausdrücklichem CSRF-POST oder eingerichtetem Cron.
- Additive v270, neue lokale Unit-/MariaDB-/Mobiltests. Keine Kunden-/Rechnungs-/Regelüberschreibung durch Migration.

# Ergänzung 10.11.2026 – gesicherte Wiederaufnahme

- Dateidifferenz zur Phase-3-Basis erfasst und Zwischenstand-ZIP gesichert.
- Additive v260: PDF-Originale neuer Ausstellungen, Hash-/Größenprüfung, byteidentische Ausgabe/Export/Versandanhänge. Keine historische Nachbefüllung, kein stiller Ersatz bei Integritätsfehlern.
- Dokument-Integrationstests von14 auf26 Assertions erweitert; frische synthetische Gesamttestfixture zum Reproduzieren ergänzt.
- Alte SMTP-Fehlerausgabe im Rechnungscontroller auf generischen Fehlercode ohne Serverantwort/Empfängerdaten reduziert; mehrseitige Ausgabe korrekt beschriftet.

# Gesamtstand ab Phase 3 – 09.11.2026

- Additive v230/v240/v250 für Mail/Audit, Bank/Belege/Zahlungen und Angebote/Referenzbelege; Bestandsdaten geschützt.
- Dashboard/Objektverknüpfungen, klassischer lokaler Mailclient mit Mock-geprüften Adaptern, sichere Versandzustände und Anhänge.
- Neutraler Bank-CSV-Import, centgenaue bestätigte Zuordnungen/Belegkorrekturen, Arbeits-EÜR und Original-/Prüflistenexport.
- Bestehende Rechnungsbasis um Angebote/Übernahme und technischen Vollstorno erweitert; mehrseitiges Unicode-PDF mit tFPDF/DejaVu (Lizenzen enthalten).
- Schutzflags für Rechnungsausstellung/unvalidiertes XML, Login-/Session-/Download-/Wartungs-/Audit-Härtung und neue CLI-Werkzeuge.
- Neue Unit-, Datenbank- und Browserprüfungen; Phasen 1–3 regressionsgeprüft. Keine externen Konten verbunden und kein Live-Deployment.
- Bewusste Basistestanpassungen: Mehrseiten-PDF ersetzt alte Ablehnung langer Rechnungen; nur neue Fenster-/Tiefgaragen-Seeds 90 statt 120 Minuten gemäß aktuellem Auftrag. Keine bestehenden Dauern überschrieben.
- Grenzen und verbleibende Codearbeiten: ENTWICKLUNGSREST.md. Dieser Stand schließt den Gesamtauftrag nicht vollständig ab.

---

# Änderungen – v2.0.0-dev, Phase 0/1

09.11.2026. Kein Deployment.

Bestandsfunktionen wiederverwendet: Objektakte, Regelverwaltung, Scheduler, CSRF/Session und Aufgabenstruktur. Neue Importvorschau mit Bestandsschutz, sieben Stammdatenobjekte, 28 Regeln, editierbare Dauern/Saisons, stabile Monatsanker und gemeinsame Aufgabenidentität. Konfigurationswerte aus Serverumgebung und zusätzliche SMTP-Versandsperre.

Migration: private/migration_v200.sql (einmalig auf vollständiger v192-Struktur). Installation/Risiken/Rollback: INSTALLATION_PHASE1.md. Tests: TESTBERICHT_PHASE01.md.

## Geänderte oder ergänzte Dateien

- `.gitignore`
- `AUDIT.md`
- `FORTSCHRITT.md`
- `IMPLEMENTIERUNGSPLAN.md`
- `INSTALLATION_PHASE1.md`
- `TESTBERICHT_PHASE01.md`
- `private/config.example.php`
- `private/config_loader.php`
- `private/cron_scheduler.php`
- `private/migration_v200.sql`
- `private/phase1_catalog.php`
- `private/phase1_import.php`
- `private/recurring_rules.php`
- `private/scheduler.php`
- `private/smtp.php`
- `public/bootstrap.php`
- `public/index.php`
- `public/object_import.php`
- `public/property.php`
- `public/recurring.php`
- `tests/prepare_browser.php`
- `tests/test_browser.cjs`
- `tests/test_phase1_database.php`
- `tests/test_recurring.php`
- `CHANGELOG.md`

Nicht übernommen: geheime private/config.php und beide eingebetteten ZIPs. Keine Bestandsdateien für Rechnungen/Belege gelöscht.

## v2.1.0-dev – Phase 2

Wochen-/Tagesplanung mit Vorschau und expliziter Übernahme, virtuelle Wiederholungen, manuelle Sperren/Fenster/Notizen, Fixtermine und sichtbare geschätzte Puffer. Migration v210 additiv/wiederanlaufbar. Keine Phase-1-Regeln oder Rechnungsdaten ersetzt. Lokale Tests und Grenzen in TESTBERICHT_PHASE02.md. Exakte Dateien/Hashes im Update-Manifest UPDATE_DATEIEN.md. Keine Produktion verändert.

## v2.2.0-dev – Phase 3

Offline-Müllkalender (ICS/CSV), explizite Zuordnung/Fachfreigabe, Quellen/Status/Originalidentitäten, Konflikte statt stiller Verschiebung, Vortag-/Abfuhrtag-Aufgaben, bewusste 15-Minuten-Bündelung, Gelb-Sonderregel wiederverwendet. Dashboard-/Tageswidget und eigene Wochenansicht. Offline-Routengruppierung, austauschbarer deaktivierter OSRM-Matrixadapter, geprüfte Koordinaten, Budget/Cache/Fallback, Start-/Zieladressen, Fahrabschnitte und bestätigbare Reihenfolge. Aktuelle bestätigte Route im Tagesplan; technische DB-Zeitstempel zentral UTC. Migration v220 additiv. Keine Rechnungs-/Dokumenten-/Loginfachlogik geändert. Genaues Update-Manifest UPDATE_DATEIEN_PHASE3.md; Installation/Rollback und geprüfte Grenzen in Phase-3-Berichten. Kein Deployment.
