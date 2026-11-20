# Aktueller FINAL-Stand 12.11.2026 / Schema350

Maßgeblich: INSTALLATION-STRATO.md, TESTSTATUS-FINAL.md, MODULE-STATUS.md, CHANGELOG-FINAL.md, INTEGRATIONEN-FINAL.md und CODEX_FORTSETZUNG.md. Neu: täglicheRegeln, integriertesDashboard/Navigation, CAMT/Kontentrennung/Review, OfflineSEO, geschütztesBestandsupdate/Dateiintegrität. Schema350/82Tabellen; keineProduktivinstallation, echteMail/Bank/Cron/Objektkalender weiter nichtnachgewiesen. Aussagenzu330/340nachfolgend sindHistorie. LiveSEO-/GSC-/GBP-Adapter,OCRundvalidierteE-Rechnung nichtalsfertigausgeben.

---

# Aktueller Stand Version1.0 / Schema340

Maßgebliche aktuelle Einrichtung: DEPLOYMENT.md, CRONJOBS.md, ROLLBACK.md, TESTSTATUS.md, INTEGRATIONEN_V1.md und OFFENE_PUNKTE.md. Schema330/76Tabellen in nachfolgender Historie gilt nur für ältere Auslieferungen; jetzt additive340/78Tabellen. Keine echte STRATO-/Mail-/Produktivfreigabe. Frühere Aussagen nicht als Beleg aktueller externer Einrichtung verwenden.

---

# Maßgeblich für die erste Inbetriebnahme: Release331 / Schema330

Neuer enger Auftrag: Kernbetrieb jetzt stabilisieren, keine weiteren Großmodule. Einfache aktuelle Anleitung INSTALLATION_KURZ_STRATO.md, Systemcheck private/system_check_cli.php bzw. nach Anmeldung public/system_check.php. Sicherer Fresh-CLI-Helfer private/install.php --fresh verweigert nicht leere Datenbanken. Für v330→331 keine SQL-Migration; bestehende Additivmigrationen unverändert. Alle Module erhalten. Tatsächliche Hosting-/iPhone-/Fachprüfung und Produktionsfreigabe weiterhin offen. Alte Entwicklungsprioritäten/Anleitungen darunter sind historisch.

---

# Aktuelle Installation/Update – Gesamtcheckpoint v330, noch keine Produktion

Dieser geprüfte lokale Entwicklungsstand ergänzt v300 um310 Immobilien,320 Nebenkosten und330 Mail-Recovery. Ältere Anweisungen unten sind Historie; die Pause ist durch neuen Auftrag aufgehoben. Keine Installation auf gszintern.de erfolgt. Erst Staging und tatsächliche PHP-/MariaDB-/Extensionprüfung, fachliche Abnahme und gesonderte Produktionsfreigabe. PHP8.4.24/MariaDB11.8.6 lokal geprüft; eine ältere STRATO-MariaDB-Version ist damit nicht nachgewiesen.

1. Gesicherten Quellstand, Migrationsledger und reale Tabellen/Spalten inventarisieren. Vollständige private DB-Sicherung und geschützte Dateien inklusive private/uploads/estate, invoices, Belegen, Konfiguration und separaten Vault-Schlüsseln konsistent sichern. Sicherungen niemals im Webroot/öffentlichen ZIP ablegen. Neue Vorgänge während Sicherung/Migration ausschließen; Wartungsmodus in Staging prüfen. Keine historischen Migrationen blind einzeln erneut ausführen.
2. Nur bereinigtes Gesamtpaket oder exakt passendes Update entpacken. Update ab v300: Basis GSZ-Checkpoint-v300-vor-Immobilien.zip, SHA im UPDATE_DATEIEN_AB_V300.md prüfen; für Phase03 das separate Update-ab-Phase03 verwenden. Datei-/Hashmanifest vergleichen. Konfiguration/Uploads nicht überschreiben oder aus Paketen erwarten. DocumentRoot ausschließlich public; private-Verzeichnis und Schlüssel unzugänglich von außen.
3. Für getesteten vollständigen v220/v300-Strukturstand benutzt der vorhandene CLI-Runner `php private/migrate_gesamt.php` dieselbe sichere Serverkonfiguration. Reihenfolge230→240→250→260→270→280→290→300→310→320→330. Vorprüfung verlangt220/spalten; fehlende Voraussetzungen erst auf Kopie klären. Kein Webmigrationsendpunkt. Installation aus leerem Schema nur nach bestehenden getesteten Phase1-/2-/3-Installationsschritten bis220, danach Runner; keine Prod-DB mit Installationsschema überschreiben.
4. Additiv/idempotent auf lokal frischer und Upgrade-Kopie getestet. v31017/v3205 neue estate_-Tabellen, Gesamt76; v320 zwei optionale Periodenspalten/Index; v330 zusätzliche MIME-/Recoveryspalten an mail_drafts. Bestehende Geschäftstabellen nicht destruktiv ändern. MariaDB-DDL ist nicht gesamttransaktional; Abbruch kann Teilzustand hinterlassen. Ledger/SHOW CREATE TABLE prüfen, Fehlerursache beheben und nur denselben geprüften Runner kontrolliert wiederholen. Nicht durch falschen manuell gesetzten Versionsmarker umgehen.
5. Dateiablage private/uploads/estate mit minimalen Serverrechten schreibbar machen, außerhalb Webroot; Fontcache vorhandenen PDF-Mechanismus beachten. Keine Symlinks/öffentlich erreichbare Originale. Speicher-/memory_limit/upload_max_filesize/post_max_size für10MB-Originale/20MB-Export und PDF prüfen. Genau zwei leere neutrale Kontoaliases werden angelegt; keine echten privaten Objekte/Mieter/IBANs automatisch importiert. Betriebszeiten/7GSZ-Objekte unverändert.
6. Staging-Login/CSRF/Planung/Dokumente/Rechnungen prüfen; Immobilien an synthetischen Daten wie ANLEITUNG_MEINE_IMMOBILIEN.md testen. Wiederholte Migration/Scheduler/Importe, Schutz erledigter Aufgaben, zwei Konten und PDForiginale prüfen. TESTBERICHT_NEBENKOSTEN.md enthält reproduzierbare Tests und lokalen DB-/Dateirestore. Keine Testfixture auf echte Datenbank anwenden.
7. Fachliche Vertrags-/BetrKV-/HeizkostenV-/CO2-/Fristenprüfung durchführen. Salden noch kein automatisch neuer Mietkonto-Posten; keine alten Sollvorauszahlungen doppelt addieren. Onlinebanking/ista/STRATO-Mail bleiben nicht verbunden; allow_mail_send=false und deaktivierte externe Provider beibehalten.

Rollback: Vor neuen Vorgängen vollständige konsistente Code+DB+Datei-Sicherung wiederherstellen, Vault/Serverkonfiguration sicher beibehalten. Nach neuen Buchungen/Mails/Abrechnungen vorher diese Vorgänge und Originale separat sichern/abgleichen; bloßer alter Code würde neue Daten ausblenden. Kein DROP-/Lösch-Downskript, keine Original-/Mieterhistorie löschen. Ein bereits akzeptierter SMTP-Versand kann nicht durch DB-Restore rückgängig gemacht werden; vor jeder späteren Wiederaufnahme Postfachzustand klären, niemals blind erneut senden. Produktionsausführung erfordert weiterhin gesonderte Freigabe.

---

## Immobilien-Grundlage v310 / Mail-Recovery v330

Neue private estate_-Tabellen, von GSZ/Sparkasse getrennt. Genau zwei unverbundene Volksbank-Importslots werden ohne reale Kontodaten angelegt. Keine bestehende GSZ-Datenzeile überschrieben. Eigene private Originale unter private/uploads/estate mit Datenbank gemeinsam sichern. Nach geprüfter v300-Struktur Runner ergänzt310 und330. Bestandssicherung, Staging, Konflikt-/Reentrytests und komplette spätere Hostingabnahme erforderlich; kein destruktiver Rückbau/Löschen privater Originale. Details TESTBERICHT_IMMOBILIEN.md und TESTBERICHT_MAIL_RECOVERY.md.

Nebenkostenkern ist bislang isoliert mathematisch getestet; finale PDF-/Snapshotintegration folgt als v320 und ist in diesem Teilpaket nicht als fertig ausgewiesen. Online-VB/STRATO weiterhin nicht verbunden.

Hinweis: Vault und Master-Schlüssel außerhalb der Projekt-/Paketarbeitskopie speichern, nicht nur außerhalb public. Bei manueller Auslieferung beide explizit ausschließen.

## Neue optionale sichere Konfiguration / SMTP-Bibliothek

SMTP verwendet jetzt die geprüfte PHPMailer-SMTP-Komponente7.2.0; `smtp_transport=phpmailer`, optional native als Rückfall. Kein Composer im Webbetrieb nötig. `allow_mail_send=false` bleibt Standard. OPEN_SOURCE_PRUEFUNG.md/Lizenzdateien beachten; keine STRATO-Liveprüfung behauptet.

Optionale verschlüsselte Geheimnisse mit vorhandenem PHP-libsodium: `GSZ_SECRETS_FILE` und `GSZ_SECRETS_KEY_FILE` gemeinsam als absolute private Serverpfade außerhalb public konfigurieren. Beide Dateien0600, keine Symlinks. `php private/secret_vault_cli.php init /PRIVATER_PFAD/key` erzeugt einen Schlüssel, verweigert vorhandene Dateien. `php private/secret_vault_cli.php encrypt /PRIVATER_PFAD/key /PRIVATER_PFAD/vault` liest ein JSON-Objekt ausschließlich über geschütztes stdin, niemals Passwörter als CLI-Argument/Chat. Erlaubt db_pass/mail_pass/setup_key/db_user/mail_user. Privaten Schlüssel separat sichern, nie in Git/ZIP. Kein decrypt-Kommando mit Klartextausgabe. Rotation über neue Dateien/prüfbare Konfiguration, nicht überschreibend improvisieren. Alte Konfiguration ohne Vault funktioniert weiter; explizite GSZ_-Umgebungswerte überschreiben den Vault. libsodium auf STRATO vor Ort prüfen.

## Gesicherter Entwicklungsstand v300 – noch nicht installieren/freigeben

Auf Nutzerwunsch pausiert. Letzte Gesamtintegration noch nicht vollständig regressions-/restoregeprüft. TESTBERICHT_SICHERUNGSSTAND.md lesen und neuen Plan abwarten. Aktueller Runner private/migrate_gesamt.php ergänzt nach vollständiger v220 die Schritte230,240,250,260,270,280,290,300. Nur auf isolierter Kopie prüfen, keine alten Schritte blind erneut einspielen. Neue Tabellen gesamt54; tatsächliche Hostingstruktur vorher vergleichen. Konfiguration und Uploads erhalten, insbesondere private/uploads/invoices sowie Belegoriginale gemeinsam mit DB sichern.

Coderollback allein ist nach neuen Vorgängen kein vollständiger fachlicher Rollback: alte Versionen ignorieren z.B. soft stornierte Termine und könnten diese wieder als Blocker anzeigen. Konsistente DB-/Dateisicherung und Abgleich neuer Vorgänge erforderlich. Kein destruktives Downskript/keine historische Beleglöschung.

Historische Anleitung des zuvor geprüften Stands folgt:

# Kontrollierte STRATO-Vorbereitung – kein Live-Deployment ausgeführt

Dieser Stand ist für lokale Prüfung/Staging vorbereitet, nicht produktiv freigegeben und kein abschließend fachlich vollständiger Lexware-Ersatz. Zuerst ARCHITEKTUR_GESAMTSYSTEM.md, TESTBERICHT_GESAMT.md, ENTWICKLUNGSREST.md und INTEGRATIONEN.md lesen. Alte Installations-Kurztexte sind historische Hinweise; nicht blind auf Produktion anwenden.

## STRATO-Matrix

| Punkt | Hier geprüft | Vor Ort zu prüfen |
|---|---|---|
| PHP | 8.4.24 CLI/Entwicklungsserver | Tarifversion, empfohlen unterstütztes 8.2+, Syntax mindestens 8.1; richtige CLI-/Webversion identisch prüfen |
| Datenbank | MariaDB 11.8.6, InnoDB/utf8mb4 | Echte Version (Ziel 10.6+), Rechte, Limits, Schema/Zeitzone/Backupzugriff |
| Extensions | PDO MySQL, mbstring, iconv, fileinfo, zlib für PDF | Tatsächliche Web-Extensions; PHP-IMAP ggf. nicht in PHP 8.4/Tarif enthalten |
| IMAP/SMTP | Mocks, keine echten Konten | TLS-Zertifikatskette, Hostnamen/993/587, ausgehende Ports, Authmethoden, Nachrichten-/Versand-/Postfachlimits |
| Cron/SSH | Lokale CLI-Skripte | Tarif erlaubt SSH/PHP-CLI/Cron? Laufzeit/Intervall/Prozesslimits und sicherer PHP-Binarypfad |
| Uploads | Lokale MIME-/Größengrenzen | upload_max_filesize ≥10M, post_max_size passend ≥16M, Webserver-Requestlimits; ggf. kleinere Tariflimits deutlich dokumentieren |
| Arbeitsspeicher | Kleine/beschränkte Testfälle | Empfohlen mindestens 128M, praktisch 256M für Exporte/PDF/MIME; harte Limits nicht als Lasttest verstehen |
| Dateien | private/ außerhalb public/, private Dateien/Hashes geprüft | Echte Domain muss nur auf public/ zeigen, private/tests/vendor/logs/Archive niemals öffentlich; .htaccess/HTTPS testen |
| Zertifikate/HTTP | App erzwingt HTTPS, Tests über lokale TLS-Brücke | Beschreibbares privates session.save_path, echte HTTPS-Weiterleitung/HSTS, keine ungeprüften Forwarded-Header übernehmen; Proxykonfiguration explizit prüfen |
| PDF | tFPDF/DejaVu mehrseitig/Unicodefont lokal | Schriftcache beschreibbar nur unter private/uploads/pdf_fonts, aktuelle Schrift-/Pflichtfeld-/Layoutprüfung auf Hosting |

## Sicherheitskopie und Staging

1. Keine Änderungen an gszintern.de ohne gesonderte Freigabe. Separates Staging-Verzeichnis und separate Datenbank wählen; keine produktiven Postfächer/API-Keys im Staging verwenden.
2. Konsistente DB-Sicherung mit geschützter MySQL-Optionsdatei und `mariadb-dump --single-transaction` erstellen. Passwort niemals als CLI-Argument/Chattext. Gesamten Code, private Serverkonfiguration und Uploads separat geschützt/verschlüsselt sichern. Sicherungen niemals im Document-Root ablegen.
3. DB-Sicherung zunächst in unabhängige Testdatenbank zurückspielen, Datensätze/Hashes prüfen. Dateioriginale/Uploadreferenzen ebenfalls separat wiederherstellen und stichprobenhaft Hash/Download prüfen. Lokal wurde DB-Restore bewiesen, kein vollständiger STRATO-Desastertest.
4. Bereinigte Kopie der tatsächlichen Produktionsstruktur gegen v200/v210/v220 vergleichen. Versionsledger allein ist kein Strukturbeweis. Bei Teilmigration/abweichenden Typen/Indizes stoppen und differenzieren; keine historischen Migrationen erneut einspielen.

## Update genau ab GSZ-Phase03.zip

1. Wartungsfenster, Cron und Schreibzugriffe pausieren. Bestehende Konfiguration behalten; optional vor Update `GSZ_MAINTENANCE=true` für Webverkehr. Auf einer bislang älteren Installation wirkt das neue Flag erst nach Einspielen des neuen Bootstrap, daher zusätzlich Webserver-Wartung verwenden.
2. Nur Dateien aus UPDATE_DATEIEN_GESAMT.md ergänzen/ersetzen. private/config.php, .env, Uploads oder andere serverseitige Daten nicht ersetzen. Das Paket enthält keine solchen Dateien.
3. Sichere Konfiguration aus vorhandenem private/config.php oder GSZ_-Hostingumgebung; Beispiel enthält keine Secrets. Alle Schlüssel mit GSZ_<GROSSBUCHSTABEN> überschreibbar. Keine echten Werte in Chat, Git oder Paket.
4. Auf Staging mit vollständiger v220 ausführen: `php private/migrate_gesamt.php`. Reihenfolge v230 Mail/Sicherheit → v240 Finanzbasis → v250 Dokumentarten/Angebote → v260 PDF-Originale neuer Ausstellungen → v270 Betriebsworkflow. Der Runner verlangt vollständige wesentliche Phase-3-Spalten und alle fünf Dateien; kein stilles Auslassen. Neue Tabellen/Spalten/Indizes wiederanlaufbar. v260 ergänzt ausschließlich nullable Archivspalten ohne historischen Backfill. private/uploads/invoices muss privat beschreibbar und zusammen mit der Datenbank gesichert sein. Einträge werden nach Ausführung geprüft.
5. MariaDB-DDL committet automatisch; keine atomare Gesamtmigration. Bei Teilfehler Schema/Rechte privat prüfen, Ursache beheben, denselben Runner erneut starten. IF NOT EXISTS repariert keine gleichnamigen falsch definierten Tabellen. Schema-/Ledgerchecks nicht als vollständiges Migrationsframework missverstehen.
6. Neuinstallation ausschließlich leere Testdatenbank: fresh_install_v192.sql einmal, migration_v200.sql einmal, migrate_phase2.php, migrate_phase3.php, migrate_gesamt.php. Nicht zusätzlich alte Einzelmigrationen ausführen. 42 Tabellen nach v270.
7. Vorhandenen Admin erhalten. Für wirklich leere Admin-Tabelle gibt es ausschließlich CLI `private/admin_setup.php`: GSZ_SETUP_EMAIL sicher serverseitig setzen und Passwort über geschütztes stdin (14–200 Zeichen) übergeben, niemals als Argument oder im Chat. Beispiel in interaktiver Bash: `read -r -s GSZ_SETUP_PASSWORD`, anschließend `printf '%s\n' "$GSZ_SETUP_PASSWORD" | GSZ_SETUP_EMAIL='ADMIN_ADRESSE_AUF_DEM_SERVER' php private/admin_setup.php`, danach `unset GSZ_SETUP_PASSWORD`. Kein Web-setup.php anlegen. Script verweigert existierende Admins und überschreibt nichts. Bei Tarif ohne CLI sichere alternative Einrichtung gesondert vorbereiten, nicht improvisieren.
8. Wartung auf Staging lösen, anmelden, sieben Objekte/Regeln prüfen. Keine Normaltonnen aktivieren. Neue Fenster-/Tiefgaragen-Seeds 90 Minuten, vorhandene 120-Minuten-Werte bleiben erhalten. Dashboard, Planung, Quellen, Route und Originaldownloads prüfen.

## Standardmäßig gesperrte Funktionen

- `mail_enabled=false`: keine IMAP-Verbindung. GET zeigt lokalen Cache, nie automatischen Liveabruf.
- `allow_mail_send=false`: keine SMTP-Sendung. Zusätzlich ist im UI eine Bestätigung erforderlich. Erst ausdrücklich freigegebenes Testkonto konfigurieren, nie bloß wegen dieser Anleitung produktiv einschalten.
- `allow_invoice_issue=false`: verbindliche Rechnungsausstellung und Stornobelegcontroller gesperrt bis technischer/fachlicher Freigabe. Entwürfe/Angebote/Prüfung nutzbar. Alte pauschale Bezahlt-/Stornomarker wurden durch nachvollziehbare Zahlungs-/Referenzbelegaktionen ersetzt; Altwerte bleiben bei Migration unverändert.
- `allow_unvalidated_xml=false`: alter unvalidierter UBL-Prototyp bleibt gesperrt, kein E-Rechnungsnachweis.
- `routing_enabled=false`: Offline-Heuristik bleibt Standard. Keine öffentliche Demo automatisch anschließen.
- Onlinebanking/OCR/KI/Push nicht angebunden. Keine Bankzugangsdaten konfigurieren oder erfragen.

Session-Idle standardmäßig 1800s, maximal acht Stunden; Session nach Update/Timeout ggf. neu anmelden. Dauerhafte Zugangsbremse nutzt private/uploads/security, Dateien außerhalb des Webroots. Audit standardmäßig aktiv; CLI `php private/audit_check.php`. Eine gültige Hashkette ist kein GoBD-/WORM-Nachweis.

## Arbeitsabläufe prüfen

Mail: lokaler Ordnercache und Entwürfe, separate Synchronisation, echte Serverordner wählen. 1–100 neue Mails je Batch, 20 MB/Batch, 15 MB/Mail; Flags der neuesten 100 Cacheeinträge. SMTP erst nach Freigabe; accepted bedeutet Serverannahme, nicht Zustellung. Unklaren Versand zuerst im Postfach prüfen, über dokumentierte manuelle Prüfentscheidung auflösen, niemals blind wiederholen. Anhänge bleiben Downloads, kein Virenscan zugesagt.

Bank: neutrales UTF-8-CSV, feste Quellen-ID, ISO-Datum, EUR, explizite Feld-/Betragsformate. Kein Sparkassenprofil behaupten. Vorschau allein erzeugt keine Umsätze. Jede Zuordnung als eigener bestätigter Schritt, keine Überweisung. CAMT/MT940 werden nicht als unterstützt ausgewiesen.

Belege: Original hochladen/aus Mail explizit übernehmen, Metadaten und einheitliche Steuer prüfen, erst dann nummerieren/sperren. Zahlung separat erfassen; steuerliche Sonderfälle blockieren/manuell prüfen. Begründete Korrekturketten/Gegenzeilen statt Löschung. Steuerberater-Arbeitspaket mit Originalen/Prüflisten zeitlich begrenzen (20 MB/200 Dateien), kein DATEV/ELSTER.

Angebote/Rechnungen: gemeinsame Datenbasis, Angebotsübernahme idempotent. Mehrseitige PDF-Prüfung durchführen, nicht unterstützte Zeichen brechen sicher ab. Storno erzeugt eigenen technischen Referenzbeleg, keine Erstattung. Fachprüfung und E-Rechnungsvorgaben vor Freigabe gesondert abnehmen.

## Cron

`php private/cron_gesamt.php` erzeugt ausschließlich begrenzt fällige Aufgaben. Seit v270 erzeugt Dashboard-GET keine Aufgaben mehr; alternativ ausdrücklicher CSRF-POST „Fällige Regelaufgaben aktualisieren“. Optional `--mail-sync` erst mit freigegebenem Postfach/Hostingkonfiguration. Nie SMTP-Sendung aus Cron. Wartungsmodus stoppt den Lauf. Ausgabe nur Zähler/technischer Fehlercode, keine Kontodaten/Mailinhalte. STRATO-Intervall, PHP-Pfad und Fehlerbenachrichtigung vor Ort prüfen. Keine Lösch-/Retentionautomation aktiv.

## Abnahme-Gate vor Produktion

- Tatsächliche Strukturkopie, wiederholte Migration und vollständiger DB-/Dateirestore auf Staging erfolgreich.
- Tarife/Extensions/HTTPS/Dateirechte/Cron/Timeouts vor Ort geprüft.
- Kontrolliertes Mailkonto, native IMAP-/SMTP-/Ordner-/Fehler-/Retrytests einschließlich Gesendet ohne Duplikate bestanden.
- Bankformat mit bereinigten echten Beispielen, Belegzuordnung/Teilzahlung/Korrektur/Export fachlich geprüft.
- Steuerberater-/Rechnungs-/E-Rechnungs-/Aufbewahrungs-/Datenschutzabnahme; ENTWICKLUNGSREST.md berücksichtigen.
- Gesonderte ausdrückliche Produktionsfreigabe des Nutzers. Diese Sitzung erteilt sie nicht.

## Rollback

Schreibzugriffe/Cron stoppen, neue Daten sichern. Vorherige Phase-3-Dateien zurückspielen, Zusatzspalten/-tabellen zunächst behalten; kein destruktives Downskript. Bereits bestätigte Zahlungen, Dokumente, Mailzustände oder Aufgabenänderungen werden durch Coderollback nicht rückgängig. Vollständiger Rückfall nur aus konsistentem DB-/Dateibackup mit Abgleich inzwischen entstandener Daten. Alte Belege/Originale nie pauschal löschen. Produktion bleibt ohne separate Freigabe unangetastet.
