# Changelog

## 380 – Korrekturen nach der Umstellung auf STRATO (22.11.2026)

- Update-Seite: genaue Strukturdiagnose; geschütztes „Angleichen“ für die Spalten, die STRATO im älteren Zeichensatz
  utf8mb3 angelegt hatte (Inhalte unverändert). Neue Updates legen diese Spalten ausdrücklich in utf8mb4 an.
- Update-Seite: Bestätigungsfeld wurde als runder Erledigt-Knopf dargestellt – behoben.
- Mail: „Verbindung testen“ erkennt den Gesendet-Ordner (z. B. „Sent Items“, „Gesendete Objekte“) und ersetzt einen
  gespeicherten Ordner, den es im Postfach nicht gibt. Fehlt eine Gesendet-Kopie, wird der Grund angezeigt.
- Mail: „Abrufen“ meldete mit der PHP-IMAP-Erweiterung immer 0 neue Nachrichten, weil diese das Suchkriterium
  „UID n:*“ nicht kennt. Jetzt werden alle UIDs gelesen und lokal gefiltert; Fehler werden nicht mehr als leerer Ordner
  gewertet. Test gegen einen lokalen Mailserver (Dovecot, synthetische Nachrichten), nicht gegen das echte Postfach.

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
