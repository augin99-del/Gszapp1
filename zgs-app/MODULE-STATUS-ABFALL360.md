# Modulstatus Abfall360

| Modul | Implementiert | Lokal geprüft | Live / Einrichtung |
|---|---|---|---|
| Offizieller Abfallnavi GT/RH | Adress-/Quellzuordnung, Varianten, gesicherter Abruf, Cache | DB93/Browser50 und echte Quellantworten | Nativer PHP-STRATO-Abruf BLOCKED bis dort geprüft |
| Weitere Kommunen | Adaptervertrag/Register, manueller Importweg | Unbekannte Kommune/kein Remoteaufruf getestet | Weitere Adapter benötigen amtlichen Nachweis und Tests |
| Tonnen/Tasks/Änderungen | Scope,15Minuten, vorhandene Tagesoffsets, optionale Uhrzeiten, Vorschau/Bestätigung, explizite sichere Datumsänderung |93DB-Prüfungen, bestehende Phase3-Regression | Reale Objekt-/Behälterfreigaben fehlen |
| Automation | Bestehende CLI/Owner-POST mit Tagesfälligkeit, Retry, Limits/Status | Registry/Batch/Idempotenz geprüft | STRATO-Cron und Laufzeitlimits NOT TESTED |
| Dashboard/Objekte/Tagesplanung | Integration über bestehende tasks, Warnungen/Quellenzustand | Neue50 + Praxis48 + Design234 + alte Browser | Keine produktiven Daten benutzt |
| Mail/Dokumente/Rechnungen/Finanzen/Bank/Immobilien/Nebenkosten/SEO | Bestehender350-Funktionsumfang erhalten | Bestehende33PHP/DB/HTTP- und12Browserregressionen, FINAL37/26 | Vorherige Live-/fachliche Grenzen bleiben bestehen; keine neuen Onlinekonten angebunden |
| Migration/Update/Sicherheit |360 additiv, Integritäts-/Versionsschutz, keine Konfiguration im Paket |330/350Upgrade, Originaldatenerhalt, Backup/Restore, CSRF/XSS | Produktive Installation/Freigabe nicht erfolgt |
