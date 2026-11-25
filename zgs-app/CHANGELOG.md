# Changelog

## 420 – Anfragen, Notdienst-Status, Website-Zahlen (25.11.2026)

- Migration 420 (`leads`, `web_events`, `app_settings`; additiv, Marker `420_anfragen`).
- „Anfragen“ (Mail-Bereich / Mehr): Formular-Mails der Website („Neue Website-Anfrage …“) werden beim Öffnen von
  Heute/Anfragen automatisch übernommen (einmalig je Mail); Status Neu → Kontaktiert → Angebot → Auftrag/Abgesagt,
  Anrufen/WhatsApp/Mail mit einem Tipp, „Als Aufgabe für heute“, Notiz, Anfragen selbst erfassen.
- Bewertung per WhatsApp: bei Aufträgen ein Tipp → WhatsApp mit fertigem Text und Google-Bewertungslink; auch ohne Anfrage.
- Heute: „N neue Anfragen“ und Notdienst-Schalter (Jetzt frei / Im Einsatz mit Rückrufzeit, läuft nach 2 h ab).
- Öffentlich für die Website: `web_status.php` (Status, CORS nur für die eigene Website) und `web_track.php`
  (cookiefreie Tageszähler: Seite, Aufruf/Anrufen/WhatsApp/Formular – keine IP, keine Kennung).
- „Website“ (Mehr → Online): Aufrufe, Kontakt-Tipps und Seiten, die Kontakte bringen (7/30/90 Tage).
- Neue Mail aus einer Anfrage mit vorausgefülltem Empfänger.
- Tests: `test_leads.php`, `test_leads_browser.cjs`.

## 410 – Bauprojekte (24.11.2026)

- Migration 410 (`projects`, `project_contractors`, `project_trades`, `project_files`, `project_events`, `project_costs`,
  `project_items`; rein additiv, Marker `410_projekte`). Ein Update spielt fehlende Stufen 390/400 mit ein.
- Mehr → „Bauprojekte“ (Navigation: Immobilien): Projekt je Haus mit Start, Fertigstellung, Budget; typische Gewerke einer
  Sanierung per Klick. Je Projekt: Übersicht (Fortschritt, Kosten gegen Budget, offen zu zahlen, Mängel, Bauzeitenplan,
  nächste Termine, Gewährleistung), Gewerke (Handwerker, Zeitraum, Budget, Status mit einem Tipp, Gewährleistung bis),
  Termine (erscheinen automatisch in Woche/Heute; Löschen sagt den Termin ab), Kosten (Angebote/Rechnungen mit PDF,
  bezahlt/angenommen – nur Übersicht, Buchhaltung bleibt in Lexware), Mängel & To-dos je Wohnung mit Foto (abhaken ohne
  Neuladen), Fotos & Dokumente (vorher/während/nachher), Handwerker-Liste für alle Projekte.
- „Heute“ erinnert an bald fällige Handwerkerrechnungen, überfällige Mängel und Gewährleistungen, die in 60 Tagen enden.
- Woche/Tag: regelmäßige Arbeiten (z. B. Treppenhausreinigung) erscheinen vorab als „vorgesehen“.
- Tests: `test_projects.php`, `test_projects_browser.cjs`, `test_recurring_preview.php`.

## 400 – Ferienwohnung: Airbnb- und Booking-Kalender planen die Reinigung (23.11.2026)

- Migration 400 (`stay_units`, `stay_calendars`, `stay_cleanings`; rein additiv, Marker `400_ferienwohnung`).
  Ein Update spielt 390 und 400 zusammen ein, falls 390 noch fehlt.
- Objektseite → „Ferienwohnung“: Check-out/Check-in/Dauer (Standard 10:00, 15:00, 60 Min.) und die iCal-Export-Links
  von Airbnb und Booking.com eintragen. Links werden verschlüsselt gespeichert (Schlüssel in `private/secrets/stay.key`),
  nur Host und letzte 4 Zeichen werden angezeigt. Abruf nur von exakt bekannten Airbnb-/Booking-Hosts per HTTPS.
- Reinigung am Abreisetag um Check-out-Zeit, fest an diesem Tag (auch am Wochenende). Kommt am selben Tag ein neuer
  Gast: dringend, „neue Gäste 15:00“. Dieselbe Buchung in beiden Kalendern zählt einmal. Airbnb: nur „Reserved“,
  gesperrte Tage nicht. Booking.com: alle Einträge (der Export unterscheidet Buchung und Sperre nicht).
- Storno/Verschiebung: offene künftige Reinigung wird entfernt bzw. angepasst; erledigte und von Hand gelöschte
  bleiben, wie sie sind. Schlägt ein Abruf fehl, gilt der letzte bekannte Stand (es wird nichts gelöscht).
- Abruf automatisch beim Öffnen von „Heute“ (höchstens stündlich) oder per „Jetzt abrufen“; Systemstatus-Zeile
  „Ferienwohnung-Kalender“. Gespeichert werden nur An-/Abreisedaten, keine Gastnamen, Telefonnummern oder Codes.
- Eigene Reinigungen fragen beim Abhaken nicht „Rechnung nötig?“.
- Tests: `test_stays.php`, `test_stays_browser.cjs` (synthetische Kalender, kein Airbnb-/Booking-Kontakt).

## 390 – Merkliste: To-dos und „Rechnungen offen“ (23.11.2026)

- Neue Tabelle `quick_notes` (Migration 390, rein additiv, Marker `390_notizen`). Einspielen über „Sicherung und Update“
  (Sicherung + Passwort, wie bei 380). Alle vorhandenen Daten bleiben unverändert.
- „+ Notieren“ (Handy oben in der Kopfzeile, Computer unten rechts): To-do oder „Rechnung offen“ in einem Feld notieren.
  Das Objekt wird aus dem Text erkannt (auch mit kleinen Tippfehlern und „Str.“); bei zwei Objekten in derselben
  Straße entscheidet die Hausnummer, sonst wird nicht geraten. Wann: Irgendwann, Heute, Morgen, Diese Woche oder Datum.
  Optional Material, Notiz-Zeile und Foto.
- To-dos erscheinen auf „Heute“ (überfällig, heute, ohne Datum) und auf der Objektseite; Abhaken ohne Neuladen.
- „Rechnungen offen“: reine Erinnerung, die Rechnung selbst entsteht weiter in Lexware. „Heute“ zeigt
  „N Rechnungen noch schreiben“. Nach dem Abhaken einer einmaligen Arbeit (To-do oder Aufgabe im Tagesplan) fragt die
  Zentrale „Rechnung nötig?“ – ein Tipp auf „Rechnung merken“ trägt sie ein (je Arbeit nur einmal). Regel- und
  Abfallaufgaben fragen nicht.
- Einkaufsliste: Material offener To-dos, abhaken = besorgt.
- Erledigte Einträge bleiben 30 Tage sichtbar und werden danach nur ausgeblendet.
- Behoben: Die Zahl ungelesener Mails im Menü wurde nie angezeigt (Seitenrahmen hatte keine Datenbankverbindung mehr).
- Auf Tour mitnehmen: Offene To-dos stehen im Tagesblock des nächsten Einsatzes am selben Objekt („Mitnehmen“,
  einzeln abhakbar). Oben auf dem Tag „Für heute mitnehmen“ mit dem nötigen Material. In der Woche steht jedes To-do
  nur beim ersten passenden Einsatz („+ … mitnehmen“). To-dos mit Datum erst ab diesem Datum.
- Tests: `test_notes.php` (Migration 380→390, Objekterkennung, Listen), `test_notes_browser.cjs`.

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
- Objektseite neu gestaltet: Schnellzugriff (Route, Anrufen, Aufgabe, Arbeitsnachweise), Hinweise, „Anstehend“ als
  Blöcke mit Tag (Abhaken bleibt auf der Seite), regelmäßige Arbeiten, nächste Abholung, Objektdaten eingeklappt,
  „Zuletzt erledigt“ und Änderungen in Alltagssprache (deutsche Zeit statt UTC/Rohdaten).
- Abhaken ohne Neuladen: Der Haken sitzt sofort, die Seite bleibt an ihrer Stelle, kurze Rückmeldung
  „… erledigt – Rückgängig“ (6 Sekunden). Ohne JavaScript oder bei Unklarheit wird wie bisher normal gesendet; der
  Server prüft unverändert Anmeldung, CSRF und Bearbeitungsstand. Systemstatus zeigt, ob alle Programmdateien da sind.
- Google Search Console angebunden (kostenlos, nur lesen): Dienstkonto-Schlüssel verschlüsselt im Vault, täglicher
  Abruf beim Öffnen; Seite „Google-Sichtbarkeit“ mit Klicks, Anzeigen, Suchbegriffen, Ø-Position und Trend, neuen und
  verlorenen Begriffen. CSV-Rangmessungen bleiben eingeklappt erhalten. Systemstatus: „Google Search Console“ statt
  „SEO-Onlineabruf“; fehlgeschlagene Abfallabrufe nennen Objekt und Grund.

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
