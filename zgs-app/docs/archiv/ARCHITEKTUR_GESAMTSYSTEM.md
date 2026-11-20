# Erweiterung v330 – privater Fachbereich

22 eigene estate_-Tabellen aus Migrationen310/320, insgesamt76 Tabellen im lokalen Vollschema. Immobilien fachlich/finanziell getrennt von GSZ/Sparkasse, gemeinsamer Inhaberlogin/Navigation und technische Basis. private/estate_calculation.php reiner integerbasierter Rechenkern; Store/Bank/Originale/Abrechnungsservice getrennt von public-Controllern. ARCHITEKTUR_IMMOBILIEN.md und ARCHITEKTUR_NEBENKOSTEN_KERN.md beschreiben die tatsächlichen Schnittstellen. v330-Mail-Recovery nutzt vorhandenen Mailcache, keine zweite Mailwahrheit und keine automatische SMTP-Wiederholung. Detaillierte Grenzen im aktuellen Fortsetzungsdokument.

---

# GSZ-Gesamtstand – Architektur und nachgewiesener Umfang

Weiterentwicklung exakt aus GSZ-Phase03.zip (SHA256 139d1a4d291f5c55bae3d7a151e7ca53cce9e0dd9e2decffd21df1629383e815), kein Neubau. Arbeitskopie /workspace/gsz-gesamt/zgs-app. Ziel ist ein kontrolliert prüfbarer Entwicklungs-/Stagingstand. Keine fachliche Vollabnahme, keine Produktivfreigabe, noch kein vollständig abgeschlossener Gesamtauftrag.

## Status je Arbeitspaket

| Paket | Implementiert / lokal geprüft | Offen / Grenze |
|---|---|---|
| A | Sichere Archivaufnahme, IST/SOLL, v230/v240/v250/v260 additiv, Runner, Updatevergleich | Echte Produktivstruktur/STRATO-Matrix fehlt |
| B | Tagesdashboard, Prognose, Termine, Mail-/Bank-/Beleghinweise; Objekt-Dokumente/Mail/Zahlungen | Objektkalkulation bleibt einfache bestätigte Zuordnung; keine Vollkostenrechnung |
| C | Phase-3-Import-/Routenkern erhalten, Regression; Warnungen/Status integriert | Keine neue Tonnenticker-/Bank-/Routing-Liveverbindung; bestehende Mengenlimits |
| D | Lokaler klassischer Mailbestand, Ordner/Suche/30er-Seiten, UIDVALIDITY, Flags, Anhänge, Antworten/Allen/Weiterleiten, lokale Entwürfe/Signatur/CC, SMTP-Zustände, Gesendet-Kopie, manuelle Unklar-Auflösung, Erinnerungsregeln/Zuordnungen | Native PHP-IMAP/TLS nicht gegen echten Server geprüft, Entwürfe nicht serverseitig gespiegelt, große Postfächer begrenzt, keine vollständige MIME-/S/MIME-Engine |
| E | Neutraler CSV-Import mit Feld-/Zahlenformatwahl, Vorschau, Konflikten, stabilen IDs; read-only Interface ohne Zahlungsfunktion | Kein verifiziertes Sparkassenexportprofil, kein CAMT/MT940 und keine PSD2-/FinTS-Synchronisierung |
| F | Originalarchiv/Belegentwürfe, Metadaten/Nummern/Korrekturketten, Zahlungsteilbeträge/Erstattungen, bestätigte Bankzuordnung, Arbeits-EÜR/USt, CSV und Original-ZIP; Angebote und technische Vollstornos in bestehendem Rechnungsmodell, mehrseitiges Schrift-PDF | Steuerliche Vollständigkeit, Anlagen/Abschreibungen, gemischte Steuersätze, Reverse Charge/Anzahlungen, Teilstornos, DATEV/E-Rechnung/GoBD offen |
| G | CLI-Aufgabenlauf und optionale begrenzte IMAP-Synchronisation; mobil geprüfte Kernseiten | Kein laufender STRATO-Cron eingerichtet, keine Push-/KI-Dienste |
| Sicherheit/Betrieb | Dauerhafte Zugangsbremse, Sessionlimits, Wartung, hashverkettetes Audit, private Dateien/Integritätsprüfungen, CLI-Erstadmin, Restore | Kein Penetrations-/Lasttest, kein revisionssicheres Archiv, keine externe Verankerung des Audit-Hashes |

## Schichten und Datenmodell

Bestehende PHP-Controller/Bootstrap und PDO/MariaDB bleiben. public/ ist einziger Document-Root. Services in private/ kapseln Mail-MIME/Adapter/Zustände, Bank-CSV, Geld-/Beleg-/Zahlungslogik und Exporte. Keine Node-/Python-Laufzeit produktiv. Datenbank nach Migration: 40 Tabellen statt 25. Historische Werte nicht durch Migration umgeschrieben.

- v230: app_audit/app_mutex und Mailordner/-nachrichten/-anhänge/-entwürfe/-regeln. Keine Passwortspeicherung in DB.
- v240: Bankkontobezeichnungen, Vorschläge, Umsätze; Belege/Belegsequenz und Zahlungszuordnungen. Geld in BIGINT-Cent, EUR explizit.
- v260: drei nullable PDF-Archivspalten an invoices, kein Backfill/keine historische Änderung. private/uploads/invoices enthält SHA-256-adressierte PDF-Originale; Ausgabe prüft Bytes und Länge. Fehlendes/beschädigtes Archiv stoppt die Ausgabe. Dateispeicher und DB-Commit sind nicht gemeinsam atomar: unreferenzierte Hashdateien bleiben für spätere sichere Referenzprüfung erhalten.
- v250: Dokumentart/Referenzen ergänzen invoices; offer_sequences. Angebote, Rechnungen, Stornoreferenzbelege verwenden dieselben invoices/invoice_items, keine zweite Rechnungstabelle.
- Vorhandene tasks, recurring_jobs, planning_* und waste_* bleiben maßgeblich. Bestehende Dauern unverändert; ausschließlich neue Fenster-/Tiefgaragen-Seedregeln jetzt 90 statt 120 Minuten gemäß aktuellem Auftrag.

## Mail

GET liest ausschließlich lokalen Cache, keine IMAP-Verbindung beim Dashboard-/Ordneröffnen. Explizite Synchronisation oder freigegebener CLI-Lauf verwendet GszMailRemote. Native Implementierung verlangt mail_enabled, PHP-IMAP, festen TLS-Host/993 und Zertifikatsprüfung. Nur serverseitig gelieferte Ordnernamen. Batch 1–100, standardmäßig 50 Nachrichten, 20 MB pro Batch, max. 15 MB pro Nachricht. Flags der neuesten 100 aktiven Cacheeinträge je Sync. Ältere Flagänderungen brauchen spätere Erweiterung. Größere/defekte Nachrichten können Fortschritt blockieren und verlangen Bedienereingriff; Fehler und letzter UID werden nicht als Erfolg kaschiert.

Identität (folder_id,UIDVALIDITY,UID) eindeutig. UIDVALIDITY-Wechsel archiviert alte Cachezeilen inaktiv, löscht keine Mail/Zuordnung. Cursor wird nach erfolgreicher Transaktion fortgeschrieben. Dateien contentadressiert in private/uploads/mail, Hash beim Lesen. Auf Rollback ggf. unreferenzierte Bytes bleiben privat liegen; kein automatisches gefährliches Löschen.

MIME: begrenzte verschachtelte Multipart-/Transferkodierung, UTF-8/Charset-Konversion, HTML ausschließlich als entschärfter Plaintext (keine Remote-Bilder/Skripte), Anhänge erzwungener Download. Kein Virenscan-/PDF-Inhaltssicherheitsversprechen. Anhänge max. 10 MB je Datei, Entwürfe zusammen max. 10 MB/10 Dateien. Sonderfälle wie verschlüsselte Nachrichten bleiben externe Anhänge/manuell zu prüfen.

GszMailSender getrennt von IMAP. Neuer SMTP-Adapter: STARTTLS/587, Zertifikatsprüfung, AUTH nach TLS, Envelopevalidierung, CC, stabile Message-ID, SMTP-Annahme erst nach 250. Entwurf zuerst dauerhaft sending; nach unklarer DATA-Phase uncertain statt automatischem Retry. Nach Annahme Fehler bei QUIT oder Gesendet-Kopie dürfen keinen Neuversand auslösen. SMTP angenommen ist keine Zustellung. Gesendet nur bei konfiguriertem echtem Ordner; sonst Kopie nicht bestätigt. Manuelle Postfachprüfung kann sending/uncertain mit Prüfnotiz in draft oder manual_confirmed überführen, versendet selbst nichts. Serverdraft-Mirroring, Bounce-/DSN-Verarbeitung und mehr Transporttests sind Entwicklungsrest, keine behauptete Livefähigkeit.

24h-Hinweise nur INBOX, ausdrücklich wichtig, nicht automatisch/bulk/List-Mail, aktiv/unbeantwortet. Regeln wirken auf neue Syncnachrichten; manuelle Korrektur jederzeit möglich. Nachrichten können Objekt/Kunde/Aufgabe/Rechnung referenzieren. Anhänge erst nach eigener Aktion als Belegentwurf, niemals automatisch buchen.

## Bank und Finanzen

Onlinebankadapter besitzt nur transactions()/balance(); der mitgelieferte nicht verbundene Adapter wirft klare Fehler. Keine PIN/TAN, keine Transfers, kein Banking-Endpunkt. Import verwendet neutrales UTF-8-CSV, explizite Spalten, ISO-Datum, EUR, deutsches oder ISO-Zahlenformat. Max. 2 MB, standardmäßig 1000 Zeilen (serverseitig bis 5000). Stabile Quellen-ID Pflicht: ohne solche IDs keine zuverlässige Unterscheidung identischer legitimer Umsätze behaupten. Wiederholung gleicht Datensatzhash ab; abweichende Werte derselben ID stoppen, keine stillen Änderungen. Dateihash und Prüfhistorie erhalten. Keine automatische Storno-/Erstattungserkennung als Buchung: Vorzeichen/Text bleiben Originaldaten und müssen zugeordnet werden.

Belegoriginale nutzen vorhandenes documents-Archiv, MIME/Größen-/Bildgrenzen, Originalbytes/Hash, Zufallsname und authentifizierten Download. Gleicher Hash erzeugt keinen zweiten Belegentwurf. Metadaten manuell; OCR nicht angebunden. Erst explizite fachliche Bestätigung vergibt B-Jahr-Nummer und sperrt Werte. Korrekturentwurf referenziert unverändertes Original und Vorgänger; vorher Zahlungszuordnungen zurücknehmen, keine heimliche Umbuchung. Entwurfs-Version schützt vor veralteter Bearbeitung.

Matching liefert nachvollziehbaren Prüfwert, keine Wahrscheinlichkeit: Vorzeichen, Betrag, Datum, Name. Nie automatische Zuordnung. Zahlungsbestätigung ist eigene Aktion mit Request-Key, Transaktion, Sperren und Audit. Ein Umsatz momentan vollständig einem Beleg/einer Rechnung; mehrere Umsätze pro Rechnung = Teilzahlungen. Sammelaufteilungen noch nicht implementiert. Positive Cent = Eingang, negative = Ausgabe. Refunds dürfen nur bereits erfasste Zahlung mindern, keine Über-/Unterdeckung. Kumulative Steuerverteilung verhindert Rundungsdrift. Gemischte Rechnungssteuersätze werden für automatische Teilzuordnung abgewiesen. Eingangsbelege benötigen einheitlich 0/7/19; Sonderfälle nicht als falsche Einheitssteuer bestätigen.

Zuordnungsfehler durch Gegenzeile am ursprünglichen Zuordnungsdatum mit Grund, niemals DELETE. Bankallokation wird dabei technisch freigegeben; Originalreferenz erhalten. Echte Erstattung ist dagegen eine neue tatsächlich datierte Zahlungsbewegung. Arbeits-EÜR aggregiert bestätigte Zahlungszuordnungen nach Einnahme-/Ausgabenart, sodass Korrekturen keine fiktiven Umsätze aufblasen. USt-/Vorsteuer-Arbeitssaldo ausschließlich aus diesen Zahlungen. Fehlende Altzahlungen, Anlagen/Abschreibungen und Sonderfälle fehlen bewusst, kein fertiger Jahresabschluss. paid_on ist fachlicher Zahlungstag; invoices.paid_at wird bei Vollzahlung als tatsächlicher Zeitpunkt der Statusbestätigung gespeichert, keine erfundene Uhrzeit des Bankeingangs.

Angebote mit eigener ANG-Sequenz, Freigabe/PDF und einmaliger Übernahme in Rechnungsentwurf. Technischer Vollstorno erhält eigene GSZ-Nummer, negative Originalbeträge und Referenz; Originalpositionen erhalten. Keine Rücküberweisung. Bereits gezahlte Rechnung kann erst als tatsächliche Erstattung in der Zahlungsbasis rückgeführt werden. Teilstorno/komplexe Korrekturen nicht implementiert. Verbindliche Rechnungsausstellung/Storno zusätzlich serverseitig bis Freigabe gesperrt. Unvalidierter UBL-Prototyp standardmäßig ebenfalls gesperrt.

PDF mit tFPDF/DejaVu, eingebetteter Schrift, Textumbruch und Seiten; keine stille Abschneidung/Zeichenersetzung. Nicht unterstützte Glyphen brechen sicher ab, Nummerntransaktion rollt zurück. Kein PDF/A-/EN16931-Nachweis. Neue Ausstellungen speichern unveränderte PDF-Bytes mit Hash; historische Dokumente ohne Snapshot werden ausdrücklich technisch neu erzeugt. Abhängigkeiten/Lizenzen in ABHAENGIGKEITEN.md.

Steuerberater-Arbeitspaket: geprüfte Zahlungs-/Beleg-/ungeklärte Umsatz-CSV, zugehörige Originale mit Hash, technisch erzeugte Rechnungs-PDFs, SHA256-Prüfliste. Max. 20 MB/200 Dateien, höchstens 150 Original-/Rechnungsreferenzen; Zeitraum verkleinern statt Speicherüberlauf. Kein DATEV, keine automatische USt-Voranmeldung.

## Sicherheit und Betrieb

Bootstrap behält HTTPS/CSRF/Sessioncookies. Ergänzt Idle 30min (konfigurierbar), Maximum 8h, dauerhafte gehashte Loginbremse fünf Versuche/15min pro E-Mail/IP (kein verteilter WAF-Ersatz), Wartung, kein Fehlerdisplay/keine Exceptionargumente. Private Upload-/Cacheverzeichnisse 0700, Dateien 0600 soweit Hosting erlaubt. Keine neuen Secrets im Code.

Audit verkettet Hashes über Metadaten/Referenzen, app_mutex serialisiert. Neue Fachdienste protokollieren konkrete Aktionen. Legacy-POST-Redirects zusätzlich als Request-Ereignis, ausdrücklich kein Beweis, dass jede fachliche Teilaktion erfolgreich war (auch Fehler-PRG möglich). CLI-Auditprüfung. Wer DB vollständig kontrolliert, kann Hashkette neu berechnen; keine WORM-/GoBD-Garantie.

Config.example/GSZ_-Umgebung für Wartung, Freigaben, Session-/Importlimits, Ordner/Signatur und Integrationen. Keine automatische Cloud-/KI-/Push-Verbindung. CLI-Erstadmin nur auf leerer Admin-Tabelle mit geschütztem stdin; kein Websetup. Cron sendet keine Mails; optionaler --mail-sync benötigt zusätzliche Konfiguration. STRATO-Prozess-/Cron-/IMAP-Verfügbarkeit vor Ort prüfen. Vollständige Tests/Restarbeiten separat dokumentiert.
