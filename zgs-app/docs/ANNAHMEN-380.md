# Annahmen und Entscheidungen (380)

Fakten stehen in Testbericht und MODULE-STATUS. Hier steht, was angenommen oder selbst entschieden wurde.

## Vorgegeben (Auftrag)
- Lexware Office übernimmt Buchhaltung, Angebote, Rechnungen; keine Schnittstelle.
- Finanzmodule enthielten nur Testdaten → Löschen der Tabellen ist zulässig.
- **Markierte Annahme aus dem Auftrag:** Live-Bankschnittstelle (bank_connections.php, bank_callback.php, Enable-Banking-Adapter,
  Tabelle `bank_connections`, Schlüssellogik) wurde ebenfalls entfernt. Mietabgleich nur per Datei-Upload.

## Eigene Annahmen / Entscheidungen
1. **Eine Migration 380 für alles.** Sie entfernt nur Finanztabellen und ergänzt zusätzlich (additiv) die Strukturen für
   Teil B (`estate_bank_accounts.building_id`, `estate_tenants.payer_names/iban_hash`,
   `estate_bank_transactions.counterparty_iban_hash`, Tabelle `estate_bank_uploads`) sowie die Datenzusammenführung für Teil C.
   Grund: ein Update, ein Versionsstand, ein Rollback-Punkt.
2. **Fremdschlüssel `mail_messages.invoice_id`** musste gelöst werden, um `invoices` zu löschen. Die Spalte bleibt (Mail-Tabelle
   sonst unverändert), ihr Inhalt wird nicht mehr benutzt.
3. **Backup in der App** statt „SHA256 einer externen Sicherung eintippen“: Ohne Testsystem und ohne SSH ist ein
   App-Dump (`.sql.gz`, außerhalb Webroot, Download) der verlässlichste Weg. Update nur mit Sicherung < 2 Stunden und
   bestätigtem Download. Für den direkten Vorgänger 370 ist das Serverflag `allow_web_update` nicht mehr nötig
   (Passwort, Sicherung, Dateimanifest und CSRF bleiben Pflicht); ältere Stände brauchen es weiterhin.
4. **Altdateien löschen**: Das Hochladen eines ZIPs entfernt keine Dateien. Das Update löscht daher genau die gelisteten
   Finanzdateien; der Systemstatus meldet Reste.
5. **Volksbank-Format**: Kein echter Export lag vor. Der Parser folgt dem bekannten VR-Banking-CSV (Atruvia) mit
   Spaltenvarianten, Windows-1252, Soll/Haben-Spalte. Weil diese CSV keine Transaktions-ID hat, wird eine stabile Kennung
   aus Buchungsdaten + „Saldo nach Buchung“ + laufender Nummer gleicher Zeilen gebildet. Annahme: ein Export enthält
   immer ganze Buchungstage. CAMT.053/054 wird zusätzlich automatisch erkannt (IBAN aus der Datei).
6. **Konto = Haus** wird einmal vom Inhaber zugeordnet (keine Vermutung aus Namen). Ein Haus gehört zu höchstens einem Konto.
7. **Mietzuordnung**: Vorschläge über Name/Zahlername/IBAN-Prüfwert/Betrag/Monat; „eindeutig“ nur bei exakt passendem Betrag.
   Gebucht wird erst durch Tippen. Zahlung wird in der Reihenfolge Kaltmiete → Betriebskosten → Heizkosten verteilt.
   Sollmieten werden je Monat und Haus mit einem Tipp angelegt (keine stille Automatik).
8. **Startseiten-Hinweis ab dem 10.** gilt nur für Konten, die einem Haus zugeordnet sind; „Upload im Monat“ =
   Upload-Datum (Europe/Berlin) im laufenden Kalendermonat.
9. **Abfall-Blöcke sind echte Aufgaben** (eine Zeile `tasks` je Objekt/Tag/Richtung), die enthaltenen Tonnen bleiben über
   `waste_task_links` einzeln nachvollziehbar. Rausstellen-Titel „… rausstellen“, Zurückstellen-Titel „… zurückstellen“.
   Die Fontänen-Sonderregel „Gelbe Tonne“ wird Teil des Tagesblocks. Erledigte Einzelaufgaben aus 370 bleiben unverändert;
   nur offene werden zusammengeführt (Notizen und Arbeitsnachweis-Verweise wandern mit, Zuordnung im Abfallverlauf protokolliert).
   Ein offener, nur aus einer Tonne bestehender Block wird bei Terminverschiebung als Ganzes verschoben (behält Uhrzeit/Notiz).
   Manuell gesperrte/geplante offene Blöcke werden – anders als in 370 – bei einer amtlichen Verschiebung mitgeführt.
10. **ICS/CSV-Export**: In 370 gab es keinen Export der Abfallaufgaben (nur Import). Ergänzt wurde ein schlanker Export
    der Blöcke (Woche → Planung). Adresse steht dort im Feld „Ort“, der Titel nennt nur den Objektnamen.
11. **Mail-Vault**: Der vorhandene Vault (`secret_vault.php`) war nur per CLI/Umgebungsvariablen nutzbar. Verwendet wird sein
    Format und seine Kryptografie mit festem Ablageort `private/secrets/`. Das Ändern/Löschen des Zugangs verlangt
    zusätzlich das Zentrale-Passwort (wie bisher bei „Dienste einrichten“).
12. **Oberfläche**: Hauptpunkte Heute/Woche/Objekte/Immobilien/Mail/Mehr. „Immobilien“ öffnet den Mietabgleich
    (häufigste Aufgabe); Häuser/Mieter/Verträge und Nebenkosten unter „Mehr“ bzw. als Link. Akzentfarbe ist das Grün aus dem
    Logo – auch für Hinweise/Warnungen (Wortlaut trägt die Bedeutung). Die Tagesansicht zeigt bewusst keine berechneten
    Uhrzeiten mehr, nur feste Uhrzeiten und die Reihenfolge; die Planungslogik ist unverändert.
13. Kein Dunkelmodus (nicht beauftragt; Logo hat weißen Hintergrund).
