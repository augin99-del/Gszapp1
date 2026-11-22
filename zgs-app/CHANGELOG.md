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
- Mail: Eine Nachricht über 15 MB (oder eine nicht lesbare) blockierte den Abruf aller neueren Mails
  („Nachricht nicht lesbar oder zu groß“). Sie wird jetzt ohne Inhalt mit Absender, Betreff und Hinweis aufs Webmail
  übernommen; der Abruf läuft weiter. Beide Abrufwege gegen lokalen Dovecot getestet.
- Ohne Cronjob: Beim Öffnen von „Heute“ (höchstens stündlich) und „Mail“ (höchstens alle 5 Minuten) laufen die
  Hintergrundaufgaben bzw. der Mailabruf automatisch. `cron_mail.php` als Cron-Einstieg ohne Parameter.
- Mail-Bereich neu gestaltet: deutsche Ordnernamen in fester Reihenfolge, Filter Ungelesen/Wichtig, Vorschauzeile,
  Kennzeichen für Objekt/Aufgabe, Lesen mit klarem Kopf und Aktionen (Antworten, Weiterleiten, Archivieren, Löschen),
  „Aufgabe daraus machen“, iPad quer/Mac zweispaltig, Öffnen markiert als gelesen. Neue Mail: ein Bildschirm,
  „Senden“ in einem Schritt mit Rückfrage, mehrere Anhänge, Alltagssprache statt SMTP-Status. Sicherheitslogik
  (kein doppelter Versand, Prüfung der Gesendet-Kopie) unverändert. Wochenvorschlag-Hinweis nur noch auf „Heute“.
- Abfall-Seite neu gestaltet: neue Termine mit Objekt, Adresse und Tagesliste in deutscher Schreibweise, ein Haken,
  „Übernehmen“ bzw. „Nur speichern, keine Aufgaben“; Hinweise in Alltagssprache statt IDs; nächste Abholungen je Tag
  mit Raus/Rein; Einrichten, Abruf und Dateiimport eingeklappt. Der Hinweis auf „Heute“ öffnet direkt die Prüfung und
  verschwindet, sobald die Vorschau abgelaufen ist. Prüf- und Schutzlogik (wasteApply) unverändert.
- Abfall: „Übernehmen“ bei automatischem Kalender bestätigt auch die Verbindung (Meldung kam sonst immer wieder);
  nur die jüngste offene Vorschau je Kalender wird angezeigt.
- Objekte: „Regelmäßige Arbeiten“ direkt am Objekt (z. B. Treppenhausreinigung wöchentlich) – auch schon beim Anlegen
  eines neuen Objekts; Pausieren/Fortsetzen; nach dem Anlegen öffnet sich die Objektseite.

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
