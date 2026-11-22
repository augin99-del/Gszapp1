# Tägliche Nutzung

Tipp fürs iPhone: Zentrale in Safari öffnen → Teilen → „Zum Home-Bildschirm“. Dann startet sie wie eine App.

## Heute

Die Startseite ist der heutige Tag: ein Block je Objekt und Tätigkeit, in der geplanten Reihenfolge.

- **Haken tippen** = erledigt. Nochmal tippen (unter „Erledigt“) = wieder offen.
- **Verschieben** = „Auf morgen“ oder ein Datum wählen.
- Objektname tippen = Notiz, Zuordnung, Verlauf der Aufgabe.
- Darunter steht nur, was Aufmerksamkeit braucht: fehlende Miete, fehlender Kontoauszug, neue Mails, offene Aufgaben
  von Vortagen, neue Abfuhrtermine zum Bestätigen.

## Woche

Sieben Tage auf einen Blick. Tag antippen = Tagesansicht. Unter „Planung“: Woche neu vorschlagen lassen
(wird erst nach „Vorschlag übernehmen“ wirksam), Regelaufgaben von Hand anlegen, Tonnen-Termine als Kalender (ICS)
oder Tabelle (CSV) exportieren.

## Tonnen (Abfall)

Werden an einem Objekt am selben Tag mehrere Tonnen abgeholt, gibt es **einen** Block, z. B.
„Restmüll und Biotonne rausstellen“ – und am Abholtag einen zweiten Block „… zurückstellen“.
Ein Haken erledigt den ganzen Block. Verschiebt der Entsorger eine einzelne Tonne (z. B. Feiertag), setzt die Zentrale
die Blöcke beim nächsten Kalenderabgleich neu zusammen; bereits erledigte Blöcke bleiben unverändert.

## Immobilien – Kontoauszug am 10. jedes Monats

Ab dem 10. erinnert die Startseite, solange für ein Konto in diesem Monat noch kein Auszug hochgeladen ist.

1. **Volksbank-Onlinebanking** (Browser am Mac oder App) öffnen → Vermieterkonto des Hauses wählen → **Umsätze**.
2. Zeitraum: ab dem Tag des letzten Uploads (oder einfach „letzte 60 Tage“) bis heute.
   Überschneidungen sind kein Problem – bereits vorhandene Umsätze werden erkannt und nicht doppelt gezählt.
3. **Exportieren → Format „CSV“** wählen und speichern. (Falls angeboten, geht auch der Kontoauszug als
   **CAMT.053** (XML). Nicht CAMT.052 und nicht PDF wählen.)
4. Zentrale → **Immobilien** → „Kontoauszug hochladen“ → beim richtigen Haus die Datei wählen → „Hochladen und prüfen“
   → Übersicht ansehen → **Übernehmen**.
5. Dasselbe für das zweite Konto.

> Hinweis: Bei der Entwicklung lag kein echter Volksbank-Export vor. Die Zentrale erkennt die übliche
> VR-Banking-CSV (Spalten „Buchungstag“, „Betrag“, „Name Zahlungsbeteiligter“, „Verwendungszweck“, „IBAN Auftragskonto“ …)
> auch mit abweichenden Spaltennamen, älterem Format mit Soll/Haben-Spalte und Windows-Zeichensatz. Sollte der erste
> echte Export nicht erkannt werden, zeigt die Zentrale eine klare Meldung und übernimmt nichts.

Danach im Monatsbild:

- Je Mieter: **Vollständig**, **Teilweise** (mit offenem Betrag), **Fehlt** oder **Noch nicht fällig** – mit Betrag und Datum.
- **Eingänge zuordnen**: Die Zentrale schlägt den Mieter vor (Name, Zahlername, IBAN, Betrag, Monat im Text).
  „Zuordnen“ tippen; der Betrag wird auf Kaltmiete, Betriebs- und Heizkosten verteilt. Mehrere eindeutige Vorschläge
  gehen mit einem Tipp. Nichts wird ohne dich gebucht.
- **Ausgaben**: Abbuchungen werden als Kosten des Hauses vorgeschlagen (Kategorie aus früheren Zuordnungen oder
  Stichworten wie „Grundsteuer“). Kategorie prüfen → „Bestätigen“.
- Neuer Monat: „Sollmieten für … anlegen“ tippen (einmal pro Monat und Haus).
- Zahlt jemand anderes für einen Mieter (Partner, Amt): unter „Zuordnungshilfen für Mieter“ den Namen eintragen.

Einmalig nach dem Update: jedes Konto seinem Haus zuordnen („Konto = Haus“).

## Mail

Posteingang, Lesen, Antworten. „Abrufen“ holt neue Mails (zusätzlich automatisch per Cron, falls eingerichtet).
Das Postfach-Passwort wird nicht mehr abgefragt. Lehnt das Postfach das Passwort ab (z. B. nach einer Änderung bei
STRATO), erscheint eine klare Meldung mit dem Weg zum Neueintragen; bis dahin versucht die Zentrale keine weitere
Anmeldung, damit das Postfach nicht gesperrt wird.

## Mehr

Abfallkalender, Regelaufgaben, Termine, Route, Arbeitsnachweise, Helfer, Kunden, Dokumente, Häuser/Mieter/Verträge,
Nebenkostenabrechnung, SEO und **Einstellungen** (E-Mail einrichten, Sicherung und Update, Systemstatus, Arbeitswoche).

## Sicherung

Mehr → Einstellungen → Sicherung und Update → „Sicherung jetzt erstellen“ → herunterladen. Empfehlung: einmal im Monat
und vor jedem Update. Die Sicherung umfasst die Datenbank; hochgeladene Originale (`private/uploads/`) bei Bedarf
zusätzlich per SFTP sichern.
