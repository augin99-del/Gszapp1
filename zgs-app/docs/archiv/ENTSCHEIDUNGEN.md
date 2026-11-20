#12.11.2026 – Abfall360

Bestehende Stores/Parser/Tasks wiederverwenden, keine neue Kalenderwahrheit. Eigener begrenzter Adapter auf tatsächlich amtlich verlinktem Dienst; keine fremde Clientimplementierung kopiert, geprüfte OSS-Alternativen derzeit unnötig. Nur GT/RH automatisch, sonst Import. Nutzer wählt echten Betreuungsumfang. Änderungen an Altaufgaben ausschließlich sicher und ausdrücklich, historische Daten erhalten. Keine neuen Kosten/Live-Aktionen.

# Aktueller FINAL-Stand 12.11.2026 / Schema350

Maßgeblich: INSTALLATION-STRATO.md, TESTSTATUS-FINAL.md, MODULE-STATUS.md, CHANGELOG-FINAL.md, INTEGRATIONEN-FINAL.md und CODEX_FORTSETZUNG.md. Neu: täglicheRegeln, integriertesDashboard/Navigation, CAMT/Kontentrennung/Review, OfflineSEO, geschütztesBestandsupdate/Dateiintegrität. Schema350/82Tabellen; keineProduktivinstallation, echteMail/Bank/Cron/Objektkalender weiter nichtnachgewiesen. Aussagenzu330/340nachfolgend sindHistorie. LiveSEO-/GSC-/GBP-Adapter,OCRundvalidierteE-Rechnung nichtalsfertigausgeben.

---

# Entscheidungen Version1.0

Neuester Nutzerauftrag ersetzt ältere Wochenregeln: flexible Arbeit Mo–Fr, Treppenhaus Mo–Mi. Feste Mülltermine/Sonntag und manuelle Sperren bleiben erhalten. Standard10:30/16:00/17:00, harte Vorschlagskapazität17:00 editierbar. Keine Löschung bestehender Überlast. Quartals-Fenster/Garage im Automodus ab Abschluss, expliziter Kalendermodus bleibt möglich. Keine neue Bibliothek nötig; PHPMailer/tFPDF wiederverwendet, Webklex wegen noch ungeprüfter Abhängigkeiten/Hostingextensions nicht vorschnell eingebaut. Es entstehen keine neuen monatlichen Dienste. Vollständige Abnahme erfordert reale Mail-/Hostingtests.

---

# Release331 – schnelle erste Inbetriebnahme ab v330

- Ausschließlich maßgeblichen vollständigen v330-Checkpoint verwendet, Update gegen gesicherte v300-Basis exakt rekonstruiert und Ausgangs-ZIP separat gesichert.
- Keine neue SQL-Migration/kein Großmodul. Vorhandene Installer-/Migrationsschritte sicher gekapselt, Fresh nur leere DB, kein anonymer Webinstaller. Bereits vorhandene Logins unverändert.
- Authentifizierter read-only Websystemcheck und separater CLI-Check; technische Schema-/Rechte-/Cookieprüfung ohne Secrets oder externe Abfragen. Speicherprobe nur expliziter CLI-Schalter.
- Sichtbarer Tagesplan-Schedulerknopf, korrigierte Texte, Regelaktionen mit PRG und zentraler Dateinamenbereinigung. Keine stillen GET-Mutationen, keine erfundenen Abfuhr-/Fahrtdaten.
- Referenz weiterhin PHP8.4.24/MariaDB11.8.6; Mindestversionprüfung ist keine Abnahme eines unbekannten STRATO-Tarifs. Keine Kosten/Produktion/echten Mail-/Bankaktionen.

---

# Entscheidungen Immobilien/Nebenkosten v330

- Genau zwei neutrale private Kontoslots und separate estate_-Tabellen; keine tatsächlichen Volksbank-/Gebäude-/Vermieterdaten voraussetzen. Onlinezugriff nicht verbunden.
- Vorlage/Freigabe statt stiller Forderungs-/Zahlungserzeugung; komponentenweise Ist-Zahlungen und explizite Perioden. Keine pauschale Sollvorauszahlung in Endabrechnung.
- Ganzzahlige Cent-/Flächen-/Tagesrechnung mit nachprüfbarer Restsummenverteilung; externe Heiz-/CO2-Grundlagen statt erfundener rechtlicher Parameter. Leerer Kostenbestand blockiert.
- Vertrags-/Kosten-/Abrechnungsänderungen nachvollziehbar; finale PDFs/Snapshots erhalten, neue Versionen. Fremde Mietfuchs-Lizenzlage nicht eindeutig, kein Code übernommen. Vorhandenen Unicode-PDF-Stack wiederverwenden.
- Neue Abrechnungssalden vorerst kein automatischer zusätzlicher Mietkonto-Posten; ausdrücklich markierte historische Vorauszahlungen verhindern unbeabsichtigte Doppeladdition. Eigener Saldo-/Ausgleichsworkflow nächstes Paket.
- Kein GoBD-/HeizkostenV-/BetrKV-/Bank-/STRATO-Erfolg ohne reale fachliche/technische Prüfung behaupten.

---

# Technische Entscheidungen – aktueller Auftrag

- Neusten gespeicherten v300-Gesamtstand erhalten; vorherige Pause durch neuen Auftrag aufgehoben, keine Grundmodule erneut schreiben. Ausgangssicherung versioniert erstellt.
- Bibliotheken vor Integration anhand offizieller Paket-/Lizenzangaben prüfen; keine zusätzlichen laufenden Kosten aktivieren. Dolibarr/Paperless/n8n nicht ungeprüft mit bestehenden Tabellen vermischen.
- STRATO-Hosting und tatsächliche Erweiterungen unbekannt: zusätzliche Serverdienste vermeiden; Parser-/Importalternativen offline und begrenzt halten.
- Endlieferung erst nach nachgewiesenen Tests als fertig bezeichnen. Solange interne Entwicklung offen ist, klare Zwischenstände mit Fortsetzungsdateien liefern. Keine GoBD-/Steuer-/E-Rechnungsfreigabe behaupten.


## SMTP-Bibliothek

PHPMailer7.2.0 SMTP-Komponente nach offizieller Manifest-/Lizenzprüfung als Standard hinter GszMailSender integriert; bestehende MIME-/Entwurfs-/Versandbestätigung bleibt erhalten. Native Option als Rückfall verfügbar. Gleiche acht Loopback-TLS-/Fehlerszenarien pro Transport bestanden (16 insgesamt). Testserver ergänzt um ebenfalls gültigen zweistufigen AUTH-PLAIN-Dialog; ursprünglicher Mock kannte nur Initial-Response-Variante. Keine echte Sendung. Native IMAP-/STRATOprüfung offen.


## Optionale verschlüsselte Serverkonfiguration

Vorhandenes PHP-libsodium verwenden statt eigener Kryptografie oder externer Dienste: XChaCha20-Poly1305 AEAD, frische Nonces, feste Associated Data, 32-Byte-Schlüssel. Serverpfade ausschließlich aus Umgebungsvariablen, privat0600 außerhalb public, keine Symlinks; begrenzte JSON-Felder/Größen. Optionaler Vault überschreibt bestehende private Konfiguration; explizite GSZ_-Umgebungswerte bleiben zuletzt maßgeblich. Ohne Vault bleiben bestehende Konten/Konfiguration unverändert. Kein Schutz vor einem bereits kompromittierten Server/Schlüsseldiebstahl behauptet. Schlüssel und verschlüsselte Konfiguration getrennt schützen und sichern. Keine Schlüssel erzeugt außer flüchtigen synthetischen Tests.
