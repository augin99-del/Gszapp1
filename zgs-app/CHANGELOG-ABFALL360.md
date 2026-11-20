# Änderungen Schema360 – 12.11.2026

Ausgang unveränderte FINAL350-Voll-ZIP (SHA256 siehe ABFALL360_BESTAND.md). Keine Dateien entfernt, historische SQLs unverändert, keine Produktion angefasst.

- Öffentlicher amtlich verlinkter Abfallnavi-Adapter für GT/RH mit erweiterbarem Providervertrag/Register, Adressnormalisierung, eindeutigen Bezirken, echter Variantenliste, streng begrenztem TLS-/JSON-Abruf und Cache.
- Eigene bewusste Auswahl betreuter Tonnen, Einrichtung direkt nach optional aktivierter Neuanlage und aus Objektakte; klare Statusanzeigen, Quelle, Aktualisierung und offene Vorschau.
- Bestehende Kalender-/Aufgaben-/Import-/Historientabellen weiterverwendet. Automatische konfliktfreie Folgeimporte nach ausdrücklicher Erstfreigabe; stabile Identitäten, keine stillen Änderungen an alten/geschützten Aufgaben.
- Neue explizite Einzelentscheidung für eindeutig verschobene offizielle UID: nur zukünftig/unbearbeitet, atomar und protokolliert. Andere Konflikte bleiben zur Prüfung.
- Service-/Tonnenabwahl respektiert, alte Aufgaben bleiben erhalten; Fontänen-Sonderregeldefinition und Wiederholungsidentität erhalten. Keine Aktivierung unverifizierter Normalroutinen.
- Tägliche Objekt-Synchronisierung in bestehender Automation; Quellenfehler/Einrichtungsbedarf auf Dashboard, gesicherter manueller Weg ohne Cron.
- Additive Migration360: zwei technische Profil-/Cachetabellen und auto_managed-Spalte an waste_sources. Neue Version im vorhandenen Fresh-/Upgrade-/Owner-Webupdate/Systemcheck/Codeintegritätsvertrag. CLI-Downgrade auf unbekannte neuere Schema-Version abgewiesen.
- Neue93er-DB-/50er-Browsersuite; Altregressionen, exakter350Upgrade, sichere Wiederholung, Backup/Restore, Quell-/Lizenzprüfung, Installations- und Fortsetzungsdokumentation.

Exakte geänderte/neue Dateien samt Basis-/Zielhash: UPDATE-DATEIEN-ABFALL360.md. Keine zusätzlichen Bibliotheken, laufenden API-Kosten, Bank-/Mailaktionen oder Änderungen an Buchhaltungslogik.
