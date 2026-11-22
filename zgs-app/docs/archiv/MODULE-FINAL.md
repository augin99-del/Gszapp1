# Module Komplett370

| Modul | Implementiert und lokal geprüft | Noch offen / Live |
|---|---|---|
| Sichere Browser-Einrichtung | Ohne DB-Bootstrap, zufälliger privater Besitzcode, serverseitiger Kandidat, Ownerprüfung, GUI-Migration, eigener Admin, Abschluss/Reparaturweg | Echtes STRATO, Domainaliases, Rechte und Laufzeitlimits |
| Konfiguration/Startdiagnose | Verschlüsselte JSON-Konfiguration statt PHP-Editieren, Quellenpriorität, sichere DNS/Auth/Pfad/Parse/Treiberfehler, Wartungsabschluss | Tatsächlicher ursprünglicher Serverfehler nicht eindeutig bewiesen |
| Kunden/Objekte/Aufgaben/Planung | Vorhandene Module erhalten, Treppenhaus Mo–Mi, Außen/Fenster/Tiefgarage Mo–Fr, Abschlussintervalle/Locks/Fixtermine | Reale Objekt-/Zeitdaten und Praxistag auf STRATO abnehmen |
| Abfall360 | Offizielle GT/RH-Adapter, Tonnenauswahl, Taskintegration, Konfliktentscheidung, ICS/CSV und Automation | Echte vollständige Adressen/Behälter; nativer STRATO-Abruf/Cron; weitere Kommunen nur geprüfte Adapter |
| STRATO-Mail | PHPMailer plus gebündelter IMAPEngine-Fallback, grafische Einrichtung, Verbindungstest, vorhandene Ordner/Suche/Anhänge/Entwürfe/Antworten/Weiterleitung und Versandbestätigung | Kein echter Login/Empfang/Versand belegt. Kein vollständiger CREATE/RENAME/DELETE-Ordnereditor; fehlendes UID MOVE führt zu sicherem Fehler |
| Geschäft/Sparkasse +2Volksbank | Drei strikt getrennte Verbindungen, optionaler echter AIS-Adapter, Redirect/Callback/Kontenauswahl, manuelle Abrufe und bestehender Importkern; CSV/CAMT erhalten | Anbieterregistrierung, tatsächliche Bankabdeckung, Kosten-/Vertragsfreigabe und echte Autorisierung fehlen |
| Buchhaltung/Rechnungen | Bestehende Angebote/Rechnungen/PDF, Zahlung/OP, EÜR-Arbeitsgrundlage, Istversteuerung, Kasse/Anlagen, Belege, Korrekturen/Exporte erhalten; Rechnungssteller/Freigabe grafisch | Fachliche Freigabe, Sonderfälle/GoBD/E-Rechnung/OCR nicht umfassend abgenommen; keine automatische steuerliche Konformität |
| Eigene Immobilien | Gebäude/Wohnungen/Mieter/Verträge/Kaution/Forderungen/Zahlungen/Dokumente/Nebenkosten/ista-Import/PDF-Historie erhalten | Echte Daten und fachlich/rechtliche Abnahme realer Abrechnung; kein automatischer Versand |
| Dokumente/OCR | Sichere vorhandene Upload-/Download-/Archiv-/Zuordnungswege erhalten | Automatische zuverlässige Bon-/Rechnungsauslesung auf STRATO nicht implementiert; manuelle Metadaten nutzbar |
| SEO | Vorhandener CSV-/Keyword-/GeoGrid-/Historien-/Exportworkflow erhalten | Keine Google-OAuth-/Live-Rankingquelle aktiviert, keine erfundenen Messungen |
| Routing | Vorhandene Gebietsheuristik/Puffer und getrennte Providergrenze bis25Punkte erhalten | Kein echter Routingprovider autorisiert; keine echten Kilometer/Ersparnisse behauptet |
| Automation/Backup/Systemstatus | Vorhandene geschützte manuelle/CLI-Jobs, Locks, Status, Datenexport und Restoretests | Tatsächlicher STRATO-Cron und operative Backups vor Ort einrichten |

Echte Konten, Postfachpasswörter und API-Schlüssel sind nicht im Paket. Nicht angeschlossene Dienste blockieren den lokalen Kernbetrieb nicht. Das Paket ist keine pauschale Freigabe aller gewünschten Fach-/Livefunktionen.
