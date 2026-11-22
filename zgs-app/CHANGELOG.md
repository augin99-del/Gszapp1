# Changelog

## 380 – Zentrale verschlankt (20.11.2026)

### Entfernt
- Finanzblock vollständig: Angebote, Rechnungen inkl. Versand/Export/PDF, Offene Posten/Mahnungen, EÜR/Belege,
  wiederkehrende Finanzbuchungen, Finanzexporte, Kasse/Anlagen, Geschäftskonto Sparkasse inkl. Importen,
  Live-Bankanbindung. 24 Tabellen, 34 Programmdateien, zugehörige Navigation, Kacheln, Cron-Job, Einstellungen, Tests.
  Details: docs/ENTFERNT-380.md.

### Neu / geändert
- **Sicherung und Update**: Datenbanksicherung als `.sql.gz` zum Herunterladen; Update 370 → 380 im Browser nur mit
  frischer Sicherung; Rücksicherung per phpMyAdmin oder `private/db_restore_cli.php`.
- **Mietabgleich** für zwei Volksbank-Konten: Konto = Haus, Volksbank-CSV und CAMT automatisch erkannt, überlappende
  Uploads ohne Dubletten, Upload-Protokoll, Monatsübersicht je Haus (vollständig/teilweise/fehlt mit Betrag und Datum),
  Zuordnen mit einem Tipp, Kostenvorschläge mit Bestätigung, Hinweis ab dem 10.
- **Abfall**: ein Block je Objekt, Tag und Richtung; Neuzusammensetzen bei Terminverschiebung (auch automatisch),
  erledigte Blöcke bleiben; Zusammenführung bestehender Einzelaufgaben; ICS/CSV-Export.
- **E-Mail**: Passwort einmal eintragen (verschlüsselter Vault), gilt für Empfang, Versand und Cron; Schutz vor
  wiederholten Fehlanmeldungen; Passwort ändern und Zugang löschen in der Oberfläche.
- **Oberfläche**: ein Stylesheet (`zentrale.css`), sechs Hauptpunkte, Startseite = heute, Blöcke statt Tabellen,
  Objektnamen ohne Adresse, Technik unter Einstellungen, iPhone/iPad/Mac-Layouts. Alte Stylesheets entfernt.
- **Doku** auf neun Dateien konsolidiert, Historie in docs/archiv.
- Release-Kennung `COMPLETE380`, Schema 380 (62 Tabellen).

## 370 und älter
Siehe docs/archiv/CHANGELOG-FINAL.md, docs/archiv/CHANGELOG-ABFALL360.md, docs/archiv/CHANGELOG.md.
