GSZ-ZENTRALE FINAL / SCHEMA350 – 12.11.2026

Aktueller Einstieg: INSTALLATION-STRATO.md, TESTSTATUS-FINAL.md, MODULE-STATUS.md,
CHANGELOG-FINAL.md und INTEGRATIONEN-FINAL.md. Vollpaket und Update sind bereinigt;
keine tatsächlichen Zugangsdaten oder privaten Originale enthalten.

Schema350 hat82Tabellen. Bestehende330/340-Datenbanken nur nach verifizierter
Sicherung auf Testkopie über den geprüften Upgrade-Runner aktualisieren.
Fresh verweigert nichtleere Datenbanken. Keine alten SQL-/Setupanleitungen
nachfolgend erneut auf Bestandsdaten ausführen. Kein ungeschützter Webinstaller.

Das Dashboard erzeugt keine Aufgaben allein durch Öffnen. Expliziter sicherer
Owner-POST oder tatsächlich eingerichteter CLI-Cron erzeugt Aufgaben und einen
unbestätigten Wochenvorschlag. Produktivdeployment und echte Testmails separat
freigeben. Die tatsächliche STRATO-/Postfachabnahme steht aus.

Die folgenden älteren Inhalte bleiben ausschließlich als Historie erhalten.

---------------- HISTORIE ----------------

AKTUELLER INSTALLATIONSSTAND331 / SCHEMA330 –11.11.2026

Zuerst START_HIER.md, GO_LIVE_STATUS.md und INSTALLATION_KURZ_STRATO.md lesen.
Ab v330 keine neue SQL-Migration. Kein Produktivdeployment freigegeben.
Nachstehender alter README-Inhalt ist historische Dokumentation; nicht als neue Installationsreihenfolge verwenden.

ZGS APP v0.5 – AUTOMATISCHE AUFGABENPLANUNG

NEU: Woechentliche, zweiwoechentliche, monatliche und vierteljaehrliche Leistungen bekommen einen Starttermin. Beim Aufruf des Dashboards durch den angemeldeten Administrator werden alle faelligen Aufgaben automatisch erzeugt (bis 24 Rueckstandstermine je Leistung und Aufruf). Eindeutiger Datenbankschluessel verhindert doppelte Aufgaben pro Leistung/Termin. Saisonale Leistungen bleiben manuell.

WICHTIG: Das ist eine automatische Planung BEIM OEFFNEN DES DASHBOARDS, kein rund um die Uhr laufender Hintergrunddienst. Optional kann private/cron_scheduler.php per PHP-CLI-Cronjob auf STRATO ausgefuehrt werden, FALLS der Tarif das erlaubt. Datei niemals im oeffentlichen Webverzeichnis ablegen.

UPDATE von v0.4:
1. HTTPS aktivieren und Backup von Datenbank und Dateien erstellen.
2. private/migration_v05.sql einmalig importieren (nicht schema.sql auf bestehender DB erneut ausfuehren).
3. public/index.php, public/recurring.php, private/scheduler.php und optional private/cron_scheduler.php hochladen. Bestehende private/config.php NICHT ueberschreiben.
4. Bei vorhandenen Leistungsvorlagen unter Wiederkehrende Leistungen den ersten/naechsten Termin festlegen.
5. Dashboard oeffnen, Aufgaben pruefen, Seite erneut laden und kontrollieren, dass keine Dubletten entstehen.

NEUINSTALLATION:
1. HTTPS einrichten, dann das bisherige Setup der v0.4 README beachten.
2. private/schema.sql fuer die Neuinstallation verwenden (enthaelt die v0.5-Erweiterung am Ende).
3. private/config.example.php als private/config.php anlegen und Zugangsdaten nur auf STRATO eintragen.
4. setup.php ausschliesslich ueber HTTPS nutzen und direkt danach LOESCHEN.

HINWEISE:
- Die erste Monatsplanung mit einem 29./30./31. als Starttag kann je nach Monatslaenge auf den letzten Kalendertag fallen; der urspruengliche Tag wird pro Generierung beibehalten.
- Wenn man den Termin manuell zuruecksetzt, koennen alte bereits generierte Termine erneut durchlaufen werden; durch UNIQUE-Key entstehen keine Duplikate.
- Kein automatisches Backup, keine 2FA, kein vollstaendiger serverseitiger Brute-Force-Schutz, kein GoBD-Archiv. Vor echten Kundendaten Security-Review und Backup-Konzept noetig.
- Keine Aenderung an der Hauptwebsite oder an bestehenden SSL-Zertifikaten vornehmen.

VERSION 0.6 – TAGES- UND WOCHENPLANUNG
-------------------------------------
NEU: public/planner.php. Tages- und Wochenansicht (Montag bis Sonntag),
Navigation zwischen Tagen/Wochen, Aufgaben anlegen, erledigen und auf
andere Tage verschieben, Objektverknüpfung, Tagesfortschritt und
Hinweis auf überfällige/undatierte Aufgaben.
Im Dashboard ist ein Link zur Planung ergänzt.

Upgrade von v0.5:
- Nach gesichertem Backup nur public/planner.php neu hochladen und
  public/index.php durch diese Version ersetzen.
- Keine zusätzliche SQL-Migration erforderlich.
- Voraussetzung: v0.5 mit allen Migrationen und funktionsfähigem HTTPS.
- Nicht auf HTTP mit echten Daten verwenden.
- Die Planung ist eine Aufgabenübersicht, keine Uhrzeit-/Routenplanung.
- Der Scheduler wird beim Aufruf ausgeführt, nicht automatisch im Hintergrund.
- Noch kein Integrationstest auf STRATO durchgeführt.

VERSION 0.7 – EINSATZPLANUNG
---------------------------
NEU: Uhrzeit, Dauer, Prioritaet und manuelle Stopp-Reihenfolge pro Aufgabe.
Aufgaben werden in der Tages-/Wochenplanung nach Stoppnummer, Uhrzeit
und Prioritaet angezeigt. Pro Aufgabe lassen sich diese Angaben bearbeiten.

Upgrade v0.6 -> v0.7:
1. HTTPS auf gszintern.de aktivieren und testen.
2. Dateien und Datenbank sichern.
3. private/migration_v07.sql EINMALIG auf der Datenbank ausfuehren.
4. public/planner.php und public/index.php ersetzen.
5. Test mit Beispieldaten durchfuehren.

Neuinstallation: schema.sql (inkl. seiner v0.5-ALTER-Anweisungen)
ausfuehren, danach migration_v07.sql. Nicht erneut Migrationen aus
v0.3–v0.5 ausfuehren, wenn schema.sql sie bereits enthaelt.
Keine automatische Routenberechnung oder Fahrtzeiten.
Noch kein STRATO-Livetest; PHP-Syntax geprueft.

VERSION 0.7.1 – KORREKTUR / PRUEFPAKET
-----------------------------------
- public/assets.css ergaenzt: Stylesheet der Einsatzplanung vorhanden.
- Eingabepruefung fuer Dauer und Reihenfolge korrigiert.
- PHP-Syntaxcheck fuer alle Dateien wiederholt.
- Bei NEUINSTALLATION: private/schema.sql, dann private/migration_v07.sql.
- Bei UPGRADE von v0.6: NUR private/migration_v07.sql, nicht schema.sql.
- Bei UPGRADE von frueheren Versionen: zuerst fehlende Migrationen
  in Versionsreihenfolge, ohne doppelte Ausfuehrung.
- Keine produktive Freigabe: Datenbank- und Browsertests stehen aus.

VERSION 0.8 – ZGS MAIL (LESE-PROTOTYP)
--------------------------------------
Neue Datei public/mail.php, Link im Dashboard, optionale Mailparameter in
private/config.example.php. KEIN Versand und KEIN vollstaendiger Mailclient.
IMAP ueber TLS mit Zertifikatspruefung, read-only, max. 30 Nachrichten,
Text-only, HTML-Mails und Anhaenge werden nicht gerendert.

Voraussetzungen: HTTPS, PHP-IMAP-Erweiterung auf STRATO, gueltige IMAP-
Zugangsdaten. Ob PHP-IMAP im STRATO Hosting Basic verfuegbar ist, muss
getestet werden. Keine Passwoerter in Chat oder oeffentliche Dateien.
Bei bestehender Installation config.php im privaten Verzeichnis um
mail_host, mail_port, mail_user, mail_pass ergaenzen.
Keine neue SQL-Migration fuer das Mailmodul. Migration v0.7 weiterhin
erforderlich fuer Einsatzplanung. Kein Live-Test erfolgt.

Sicherheitsgrenzen: nur Leseansicht, kein Volltext-Index, keine
Anhänge, kein Versand. IMAP-Daten bleiben beim Mailprovider.

VERSION 0.9 – ZGS MAIL: VERFASSEN, ANTWORTEN, WEITERLEITEN
--------------------------------------------------------
Neue Dateien:
- public/compose.php
- private/smtp.php
Aenderungen: public/mail.php, public/index.php, private/config.example.php

SMTP nutzt STARTTLS (Port 587) mit Zertifikatspruefung und AUTH PLAIN
nach erfolgreichem TLS-Handshake. Keine unverschluesselte Anmeldung.
Mailserver und Zugangsdaten in private/config.php eintragen:
  'smtp_host' => 'SERVERNAME_VON_STRATO',
  'smtp_port' => 587,
  'mail_from' => 'DEINE_POSTFACHADRESSE',
Die vorhandenen mail_user und mail_pass werden fuer SMTP verwendet.
Keine Passwoerter im Chat teilen.

Ein Empfaenger, reiner Text, max. 150 KB, 30 Sekunden Abstand.
Antworten und Weiterleiten uebernehmen nur Empfaenger/Betreff,
NICHT den Originaltext. Der Versand wird erst als erfolgreich gemeldet,
wenn der SMTP-Server die Nachricht akzeptiert (250). Keine Kopie im
Gesendet-Ordner und kein Zustellnachweis. Keine Anhänge, CC/BCC.
Wenn der Server nach DATA abbricht, kann die Nachricht trotz Fehler
zugestellt worden sein; vor erneutem Versand pruefen.

WICHTIG: Noch kein Test mit STRATO SMTP, keine Garantie dass SMTP
aus diesem Hosting-Paket heraus erreichbar ist. Vor Echtdaten HTTPS,
Mailserver-Erreichbarkeit und Zertifikatskette pruefen. Test-E-Mail
zunaechst an eigenes Postfach senden. Bestehende Installation vorher
sichern; private/config.php niemals ueberschreiben.

VERSION 1.0 – MAILORDNER UND SUCHE
---------------------------------
- Ordnerliste vom IMAP-Server, einschliesslich Gesendet, Entwuerfe,
  Papierkorb, falls vom Server bereitgestellt.
- Postfach-Ordner aus serverseitig gelieferten Namen, nur lesend.
- Serverseitige Volltextsuche im gewaehlten Ordner (IMAP SEARCH TEXT).
- Paginierung mit bis zu 30 Nachrichten pro Seite.
- Nachrichtenansicht je Ordner, ohne Statusaenderungen.
- Antworten/Weiterleiten weiterhin nur fuer INBOX.
- Noch keine Entwurfsspeicherung, Loeschen, Verschieben, Anhänge,
  automatische Gesendet-Kopie oder lokale Mail-Archivierung.
- KEINE SQL-Migration fuer v1.0 erforderlich.
Upgrade von v0.9: Nach Backup nur public/mail.php und public/index.php
ersetzen. private/config.php nicht ueberschreiben.
WICHTIG: Ordner und Suche muessen auf STRATO getestet werden.

GSZ APP 1.1–1.5: RECHNUNGSMODUL (ENTWICKLUNGSVERSION)
======================================================
WICHTIG: KEINE PRODUKTIVFREIGABE. Bitte noch keine rechtsverbindlichen
Rechnungen mit dieser Version ausstellen oder versenden.

Umfang:
1.1 Entwuerfe und Positionen, Netto/USt/Brutto-Rechnung.
1.2 Transaktionale Jahres-Rechnungsnummern und einfache PDF-Vorschau.
1.3 PDF-Anhang per STARTTLS-SMTP an genau einen Empfaenger,
    SMTP-Annahme wird protokolliert, keine Zustellgarantie.
1.4 Status bezahlt/storniert, Ereignisprotokoll (nur Basisprotokoll).
1.5 CSV-Export und experimenteller UBL-XML-Struktur-Export.

INSTALLATION / UPDATE
- Vorher komplette Datenbank und private/config.php sichern.
- Auf vorhandener v1.0 einmalig private/migration_v11_v15.sql ausfuehren.
- Bei Neuinstallation: zuerst schema.sql, dann migration_v07.sql,
  danach migration_v11_v15.sql. Die anderen alten Migrationen nicht
  zusaetzlich ausfuehren, wenn schema.sql sie schon enthaelt.
- Neue Schluessel aus private/config.example.php in bestehende
  private/config.php uebernehmen; diese Datei NICHT ueberschreiben.
- Nur per HTTPS aufrufbar, private/ muss ausserhalb des Document-Roots
  bleiben. Datenbankzugang und E-Mail-Passwort nicht weitergeben.
- Invoices: public/invoices.php. Export: public/invoice_csv.php.
- Keine vollstaendige Datenbank-/Browser-/STRATO-Integration getestet.

VOR PRODUKTIVEINSATZ ZWINGEND NOCH OFFEN
- Rechtsverbindliche Rechnungen: UStG-Pflichtfelder, Steuersachverhalte,
  Reverse Charge, Kleinunternehmer, Leistungszeitraum, Teil-/Abschlags-
  rechnungen, Rabatte, Rundungsregeln und vollstaendige Adressen pruefen.
- PDF: Die eingebaute Minimal-PDF ist nur eine Vorschau; einseitig,
  ohne eingebettete Schriftarten, kein PDF/A, lange Rechnungen werden
  abgeschnitten. Nicht als finale Rechnung verwenden.
- E-Rechnung: XML ist nur ein technischer UBL-Entwurf und ist NICHT
  EN-16931-/XRechnung-konform oder validiert. Kein ZUGFeRD. Nicht an
  Auftraggeber als E-Rechnung versenden. Fuer E-Rechnungen weiter
  gepruefte Software (z. B. Lexware) verwenden.
- Storno markiert lediglich Status. Es entsteht KEIN Stornobeleg und
  KEINE Gutschrift/Korrekturrechnung. Rechtliche Stornofunktion fehlt.
- Archivierung: Kein revisionssicheres, GoBD-geprueftes Archiv,
  keine versionierte unveraenderbare Speicherung, kein Backup-Konzept.
- Steuerberaterexport: CSV ist nur Rohdatenexport, KEIN DATEV-Export.
- Zahlungen: Nur manueller Status, kein Bankabgleich/Teilzahlungen.
- E-Mail: Keine Ablage im Gesendet-Ordner, kein Bounce-Handling.
- Zugriff: Nur ein Admin; keine Rollen/Freigabeprozesse.
- Vor produktivem Betrieb professionelle technische und steuerliche
  Pruefung und Tests mit echten STRATO-Diensten erforderlich.

VERSION 1.6 – ARBEITSNACHWEISE (ENTWICKLUNG)
---------------------------------------------
Neue Seite: public/worklogs.php. Dokumentation von Datum, Objekt,
optional zugeordneter Aufgabe, Minuten, Arbeit und Besonderheiten.
Anzeige der letzten 200 Eintraege, Objektfilter und Stunden-Zwischensumme.
Neue SQL-Migration: private/migration_v16.sql. Einmalig nach v1.5
ausfuehren; vorab Backup. Fuer neue Installation: schema.sql,
migration_v07.sql, migration_v11_v15.sql, migration_v16.sql.
Die bisherigen Tabellen werden nicht geloescht oder geaendert.
Keine Nachweis-Korrektur, Unterschrift, Foto, PDF oder Zeiterfassung
mit Start/Stop. Keine GoBD-/Revisionssicherheits-Zusage.
Die PHP-Syntax ist geprueft, aber kein STRATO-/MariaDB-Livetest erfolgt.

VERSION 1.7 – FOTODOKUMENTATION
--------------------------------
Run private/migration_v17.sql ONCE after backing up database.
New: public/worklog_photos.php, public/worklog_photo.php, private/photo_storage.php.
Changed: public/worklogs.php, public/index.php.
Uploads stored in private/uploads/worklog_photos; never point domain there.
Ensure PHP uploads enabled and upload_max_filesize/post_max_size allow 8MB.
Private directory must be writable by PHP and must not be web-accessible.
Photos served only after admin session check. Allowed JPEG/PNG/WebP, 8MB max,
12k pixels per dimension, 30 per work log. No deletions or PDF exports yet.
Photos are kept as uploaded: EXIF location metadata may remain. No antivirus
or image re-encoding; do not allow untrusted users to upload.
No real STRATO or MariaDB integration test has been performed.

VERSION 1.8 – PDF-ARBEITSNACHWEISE
----------------------------------
Neue Dateien: public/worklog_pdf.php, private/worklog_pdf.php.
PDF mit Objekt, Anschrift, Datum, Dauer, Tätigkeit, Notizen und Fotos.
Seitenumbrüche für Text und Bilder. Geschützter Download nach Anmeldung.
JPEG direkt; PNG/WebP nur bei installierter PHP-GD-Erweiterung, sonst
Hinweis im PDF. Kein SQL-Upgrade erforderlich.
WICHTIG: PDF ist ein einfacher Tätigkeitsbericht, keine rechtssichere
Unterschrift, kein Prüf- oder Abnahmeprotokoll. Vor echtem Einsatz
auf STRATO testen, auch mit mehrseitigen Berichten und Fotos.

VERSION 1.9 – ZENTRALES DOKUMENTENARCHIV
---------------------------------------
Neue Dateien: public/documents.php, public/document_download.php,
private/document_storage.php, private/migration_v19.sql.
Neue SQL-Migration nach v1.8 einmalig ausfuehren: migration_v19.sql.
Gespeichert werden Dokumente ausserhalb public/ in private/uploads/documents.
Unterstuetzte Formate: PDF, JPEG, PNG, WebP, TXT (max. 10 MB).
Admin-Login, CSRF, zufaellige Dateinamen, MIME-Pruefung, SHA256-Pruefung
beim Download, erzwungener Download statt Browser-Rendering.
Suche nach Titel, Dateiname und Notizen; Filter nach Kategorie;
optionale Verknuepfung mit Kunde und Objekt.
Keine Loesch-/Bearbeitungsfunktion und kein automatischer E-Mail-Import.
ACHTUNG: Archiv ist nicht nachweislich GoBD-konform. Backups, Audit-Logs,
Retention, Virenscan, sichere Deployment-Pruefung und fachliche Abnahme fehlen.
Vor produktiver Nutzung HTTPS, MariaDB und Upload-Sicherheit testen.
Bestehende private/config.php nie ueberschreiben.
