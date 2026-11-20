# Gesamtauftrag – IST/SOLL und Umsetzungsfolge

Basis exakt GSZ-Phase03.zip SHA256 139d1a4d291f5c55bae3d7a151e7ca53cce9e0dd9e2decffd21df1629383e815, 99 Einträge sicher entpackt. 25 Tabellen bis v220. Kein produktiver Schemaexport. Keine Änderung an sieben Objekten, Wiederholungen oder historischen Belegen durch neue Migrationen.

| Paket | Tatsächlicher IST-Code | SOLL / Abhängigkeiten |
|---|---|---|
| A/B Sicherheit und Dashboard | HTTPS, CSRF, prepared statements, sichere Cookies; Loginbremse nur Session; Basisdashboard, Objektakte | dauerhafte Bremse, Zeitlimits, Featureflags/Wartung, Audit, Statusintegration |
| C Kalender/Route | Offline ICS/CSV, Fachfreigabe, Konflikte, Herkunft, Provider deaktiviert; begrenzte Planung | erhalten, Regression; echte Quellen/Koordinaten ohne Freigabe nicht abrufen |
| D Mail | IMAP read-only Ordner/Suche, fehlerhafte ältere Pagination, SMTP einzelner Text/PDF, keine lokale UIDVALIDITY | lokaler Cache/UID-Herkunft, MIME/Anhänge, Ordneraktionen, Entwürfe/Antworten/Signatur, Testadapter und Versandzustände |
| E Bank | keine Tabellen/Importe/Anbindung | explizites neutrales CSV-Schema, Vorschau/Bestätigung/Dubletten; keine Sparkassen-API/PIN/TAN; CAMT/MT940 nur nach Formatprüfung |
| F Buchhaltung | Rechnungsentwurf, Nummernsequenz, manuell bezahlt/storniert, einseitiger PDF-Prototyp, Dokumente mit Hash | Belegentwürfe/Beträge/Bestätigung, Zahlungsteilbeträge, Zuordnungsvorschläge, Arbeits-EÜR/Export; Sonderfälle dürfen nicht falsch automatisch buchen |
| G Betrieb | Scheduler nur Aufgaben; lokale Tests/Restore, kein STRATO-Test | ergänzende CLI, Hinweise/Fehler ohne sensible Inhalte, Staging-/Betriebsanleitung |

Prioritäten: Regression → A/B → D mit Mocks → E/F sichere Import-/Belegbasis → integrierte Tests/Restore/Pakete. Vollständige Rechnungs-/Angebots-/Korrektur-/E-Rechnungsfachlichkeit ist gesondert nachzuweisen. Kein fertiger Lexware-Ersatz allein aus Oberfläche ableiten. Ein getesteter Zwischenstand wird ausdrücklich so bezeichnet, falls offene Entwicklungsarbeit bleibt.
