# Aktueller Modulstatus: Abfall360

Siehe [MODULE-STATUS-ABFALL360.md](MODULE-STATUS-ABFALL360.md). Bestehende Module erhalten; neue amtliche Kalenderverbindung nur für geprüfte Kommunen, nativer STRATO-Test noch offen. Folgender350-Bericht ist historisch.

---

# Modulstatus FINAL / Schema 350

„Lokal“ bedeutet synthetische Testumgebung, nicht STRATO-/Fachabnahme. Kein Modul wurde in dieser Sitzung live auf STRATO getestet. Ein PASS gilt ausschließlich für den tatsächlich geprüften Umfang.

| Modul | Vorhanden / lokal | Live getestet | Einrichtung / noch offen |
|---|---|---|---|
| Anmeldung/Sessions/Ratelimit/CSRF | PASS, Praxis/Sicherheit/HTTP | nein | HTTPS/Sessionpfad/Rechte im tatsächlichen Tarif |
| Dashboard/Mein Tag/Navigation | PASS,12Hauptgruppen/28Module,390/1440px | nein | reales i Phone/Safari |
| Kunden/Objekte/Akten/Kontakte/Notizen | PASS, vorhandene Services erhalten | nein | reale Adressen prüfen, insbesondere Fontänen/Lippstädter Ort |
| Aufgaben/Zuordnung/optionale Nachweise | PASS, Erledigen/Verschieben/Verlauf/Persistenz | nein | keine Pflichtfotos, tatsächliche Dauern selbst bestätigen |
| Tages-/Wochenplanung/Termine | PASS, Kapazität/Fixtermine/Sperren/Treppenhaus Mo–Mi | nein | Schätzpuffer bleiben Schätzwerte; unplatzierte Bestandsaufgaben behalten Fälligkeit |
| Wiederholungen | PASS, täglich bis quartalsweise/Saison, tatsächlicher Abschluss als Quartalsanker | nein | vorhandenes altes Quartals-Backlog einzeln prüfen, nicht löschen |
| Müllkalender/ICS/CSV | PASS, Mapping/Review/Idempotenz/Änderungs- und Routinenkonflikt | keine adressgenaue Quelle | amtlichen Objektkalender exportieren/freigeben; unbestätigte Normalroutinen bleiben pausiert |
| Müll-HTTPS-Synchronisation | lokal mit injizierter Quelle PASS | nein | tatsächlichen aus amtlichem UI erzeugten Objektlink konfigurieren; keine erfundene API |
| Routing | vorhandene Offline-Heuristik/Fake-Provider-Regression PASS | nein | kein echter Provider aktiviert, keine km/Echtzeitbehauptung |
| E-Mail/Ordner/Lesen/Suche/Entwürfe/Anhänge/Antworten/Sent-Recovery | Mock/DB/HTTP/IMAP-Shim/TLS PASS | BLOCKED | private Postfachkonfiguration/native IMAP, tatsächlicher Empfang/Versand/Gesendet; Versand gesondert freigeben |
| Dokumente/Fotos/Originale | PASS, Upload/Download/Verknüpfung/Hash/Restore | nein | sicherer privater Speicher/Backup auf STRATO |
| Angebote/Rechnungen/Korrekturen/PDF | bestehender Umfang PASS, Mehrseiten/Unicode/Originalerhalt | nein | Pflichtangaben/Nummern/steuerliche Praxis fachlich abnehmen; Ausstellungsflag bleibt geschützt |
| E-Rechnung | alter XML-Prototyp gesperrt erhalten | NOT TESTED | keine validierte XRechnung/ZUGFe RD-Implementierung; fachliche/technische Weiterentwicklung offen |
| Belege/EÜR/Istversteuerung/Zuordnung/Export | vorhandener begrenzter Umfang PASS | nein | keine vollständige Steuer-/Go BD-/DATEV-Abnahme; Anlagen/Sonderfälle weiterhin prüfen |
| OCR | manuelle Metadaten/Upload funktionieren | nicht eingerichtet | kein verlässlich getesteter automatischer OCR-Dienst integriert |
| Kasse/Anlagen/Finanzvorlagen/Mahnungen | bestehende Logik/DB/UI/Paralleltests PASS | nein | steuerliche Af A-/Sonderfallprüfung, kein automatischer Versand |
| Sparkasse/Bank CSV | neutraler stabiler-ID-CSV-Workflow PASS | BLOCKED online | originale Sparkassen-CSV-Varianten/Zeichensätze/Referenzen nicht mit echter Datei verifiziert; ggf.CAMT nutzen |
| CAMT053/054 | definierter XML-Teilumfang/Dubletten/Kontobindung/UI PASS | echte Bankdatei nicht geprüft | PHP-DOM; EUR/BOOK/stabile Referenz/Einzelumsätze; Sammelbuchungen/MT940 nicht unterstützt |
| Immobilien/Mietverträge/Mietkonto/Leerstand/Kautionen | vorhandene mathematische/DB/UI-Regression PASS | nein | tatsächliche Verträge/Unterlagen selbst prüfen |
| Zwei Volksbank-Konten | CSV und CAMT getrennt, Kontobindung/Paralleltest PASS | BLOCKED online | keine PIN/TAN/API aktiviert; reale Exportprofile prüfen |
| Nebenkosten/Umlage/Vorauszahlungen/Mieterwechsel/PDF/Archiv | vorhandene186Rechenprüfungen plus DB/UI/Restore PASS | keine reale Abrechnung | neutrale geprüfte externe Heizwerte; kein verifiziertes proprietäres ista-Format, rechtliche Abnahme erforderlich |
| SEO offline | Keyword/CSV/Vorschau/GeoGrid/Top3/Verlauf/Wochenvergleich/Export PASS | keine Live-Messung | importierte Quelle ist Nutzerangabe, keine unabhängige Verifikation |
| SEO-Live/GSC/Google-Unternehmensprofil | nicht verbunden | BLOCKED | Live-Provider-/OAuth-Adapter nicht implementiert; Anbieter, Autorisierung, Bedingungen/Kosten zuerst klären |
| Automatisierung | CLI/manueller Owner POST/Laufsperre/Status/Erinnerungen PASS | echter Tarif-Cron BLOCKED | nächster Lauf nur rechnerische Konfiguration, kein Ausführungsbeweis; keine externen Benachrichtigungen |
| Installieren/Updaten/Dateiintegrität | Fresh/Upgrade330/340→350/erneut/Owner-Reauth PASS | nein | separates Staging/Backup/Freigabe, kein öffentlicher Neuinstaller |

Der Ausdruck „FINAL“ in den gewünschten Dateinamen bezeichnet diese Lieferung. Er bestätigt weder vollständige Live-Funktionsfähigkeit noch steuerliche/Go BD-Konformität oder Produktivfreigabe. Offene interne Funktionen sind ausdrücklich als offen benannt, nicht als allein durch Zugangsdaten gelöst ausgegeben.
