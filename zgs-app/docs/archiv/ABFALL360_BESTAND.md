# Nachgewiesener Ausgangsstand und IST/SOLL – 12.11.2026

Basis: GSZ-Zentrale-FINAL-STRATO.zip, SHA256 `ec71a0f3aed9f3f2f4a99033d1888bc4878e9c28cd872119fb943f8dba2ab70c`. Alle318 Ausgangsdateien waren vor der Entwicklung bytegleich zur Arbeitskopie. Unveränderte gesonderte Ausgangssicherung erstellt. Keine produktive Datenbank und keine produktiven Konfigurationen verwendet.

| Bereich | Tatsächlich vorhanden in350 | Ergänzung360 | Grenze |
|---|---|---|---|
| Datenbank | 82Tabellen, vollständige Versionsfolge200–350 | 84Tabellen, Marker360_waste_connections, additive Quelleneigentümer-Spalte | Keine Bestandsdatenmigration/Adresskorrektur beim Update |
| Kalender | waste_sources/events/origins/imports/task_links/history, sicherer ICS/CSV-Import | Dieselben Tabellen für amtliche Onlineantworten | Kein zweites Aufgaben-/Kalendersystem |
| Onlineabruf | Serverseitig einzeln konfigurierte ICS-URLs | Kommunenregister, Providervertrag, amtlicher Abfallnavi-JSON-Adapter GT/RH | Keine bundesweite Erkennung, keine zugesicherte öffentliche API/SLA |
| Objekte | Freitextadresse, Kalender-Vorlauf/-Nachlauf | Explizite strukturierte Anschrift und Leistungsumfang im Verbindungsprofil | Vollständige Anschrift muss vorliegen; widersprüchliche Orte nicht erraten |
| Wiederholungen | Normaltonnen pausiert; Fontänen-Gelb Mi/Do aktiv | Sonderregel-Identität wiederverwendet; bewusste Deaktivierung/Abwahl respektiert | Regeldefinition bleibt unverändert gespeichert |
| Planung/Dashboard | Gemeinsame tasks, Fixtermine, Sperren, Notizen, Erledigung | Neue Kalenderaufgaben laufen über dieselben Sichten; Quellenfehler sichtbar | Keine neue Routenberechnung |
| Automation | Inhaber-POST/CLI, Lock und Status | Tägliche fällige Verbindungen in bestehenden Basislauf integriert | Tatsächlicher STRATO-Cron nicht belegt |
| Andere Module | Login, Mail, Dokumente, Rechnungen, Banking, Immobilien, Nebenkosten, SEO | Erhalten; Regressionen ausgeführt | Keine neue Live-Mail-/Bankanbindung |

Reale vorhandene Tabellen/Spalten wurden über die lokal installierten vollständigen Migrationen und SHOW TABLES/COLUMNS/INDEX geprüft; schema_requirements.php ist der Daten-freie Strukturvertrag. Für360 neue Konfiguration: waste_connections, waste_provider_cache; waste_sources.auto_managed. Verknüpfungen/Ereignisse/Originalidentitäten weiterhin in den bestehenden Tabellen.
