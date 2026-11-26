# Tägliche Nutzung

Tipp fürs iPhone: Zentrale in Safari öffnen → Teilen → „Zum Home-Bildschirm“. Dann startet sie wie eine App.

## Heute

Die Startseite ist der heutige Tag: ein Block je Objekt und Tätigkeit, in der geplanten Reihenfolge.

- **Haken tippen** = erledigt. Nochmal tippen (unter „Erledigt“) = wieder offen.
- **Verschieben** = „Auf morgen“ oder ein Datum wählen.
- Objektname tippen = Notiz, Zuordnung, Verlauf der Aufgabe.
- Darunter steht nur, was Aufmerksamkeit braucht: fehlende Miete, fehlender Kontoauszug, neue Mails, offene Aufgaben
  von Vortagen, neue Abfuhrtermine zum Bestätigen.

## Merkliste: To-dos und „Rechnungen offen“

- **„+ Notieren“** (Handy: oben, Computer: unten rechts) – kurz aufschreiben, z. B. „Loch Wand Fontänenstraße“.
  Das Objekt erkennt die Zentrale aus dem Text und zeigt es nach dem Speichern an. Falsch oder nicht erkannt:
  am Eintrag „Bearbeiten“ → Objekt wählen.
- **Wann:** „Irgendwann“ (Standard), Heute, Morgen, Diese Woche oder ein Datum. Auf „Heute“ stehen überfällige,
  heutige und undatierte To-dos; spätere erscheinen an ihrem Tag.
- **Material** landet auf der Einkaufsliste (Merkliste → Einkauf). Abhaken dort heißt „besorgt“.
- **Rechnung offen:** Umschalter auf „Rechnung offen“, z. B. „Fröbelstraße Beleuchtung“, Notiz „3 Std. + Material“.
  Die Rechnung schreibst du wie immer in Lexware und hakst den Eintrag danach ab.
- Nach dem Abhaken einer einmaligen Arbeit fragt die Zentrale „Rechnung nötig?“ – „Rechnung merken“ antippen.
- **Mitnehmen:** Hat ein Objekt einen Einsatz im Plan, stehen seine offenen To-dos direkt in diesem Block unter
  „Mitnehmen“. Oben auf „Heute“ steht, welches Material du dafür einpacken musst.
- Erledigtes bleibt 30 Tage unter „Erledigt“ bzw. „Geschrieben“ sichtbar.

## Ferienwohnung (Airbnb / Booking.com)

- Einmalig: Objekt öffnen → „Ferienwohnung“ → „Als Ferienwohnung einrichten“ (10:00 / 15:00 / 60 Min.), dann je
  Plattform den Link aus „Kalender exportieren“ einfügen → „Verbinden“.
- Danach plant die Zentrale die Reinigung am Abreisetag um 10:00 selbst. „Neue Gäste 15:00“ heißt: Gästewechsel, bis
  15 Uhr fertig sein. To-dos zur Wohnung stehen bei der Reinigung unter „Mitnehmen“.
- Steht beim Kalender „Abruf fehlgeschlagen“, gilt weiter der letzte Stand. Bleibt der Fehler, Link neu kopieren.

## Bauprojekte (Mehr → Bauprojekte)

- Projekt anlegen, Haus wählen, Budget eintragen – typische Gewerke sind gleich drin.
- Handwerker einmal anlegen (Tab „Handwerker“), dann bei Gewerk, Termin und Kosten auswählen.
- Termine erscheinen automatisch in der Woche. Rechnungen mit PDF erfassen und „Bezahlt“ tippen.
- Mängel je Wohnung mit Foto notieren und abhaken. „Gewährleistung bis“ beim Gewerk eintragen – „Heute“ erinnert 60 Tage vorher.

## Woche

Sieben Tage auf einen Blick. Tag antippen = Tagesansicht. Unter „Planung“: Woche neu vorschlagen lassen
(wird erst nach „Vorschlag übernehmen“ wirksam), Regelaufgaben von Hand anlegen, Tonnen-Termine als Kalender (ICS)
oder Tabelle (CSV) exportieren.

## Tonnen (Abfall)

Werden an einem Objekt am selben Tag mehrere Tonnen abgeholt, gibt es **einen** Block, z. B.
„Restmüll und Biotonne rausstellen“ – und am Abholtag einen zweiten Block „… zurückstellen“.
Ein Haken erledigt den ganzen Block. Verschiebt der Entsorger eine einzelne Tonne (z. B. Feiertag), setzt die Zentrale
die Blöcke beim nächsten Kalenderabgleich neu zusammen; bereits erledigte Blöcke bleiben unverändert.

**Neue Abfuhrtermine** (Mehr → Abfall, oder der Hinweis auf „Heute“): Die Zentrale zeigt Objekt, Abholadresse und die
Termine je Tag („Mo, 30.11. · Papiertonne, Gelbe Tonne“). „Adresse und Termine stimmen“ anhaken → **Übernehmen** legt
die Blöcke an; „Nur speichern, keine Aufgaben“ übernimmt die Termine ohne Blöcke (z. B. für eine Testadresse).
Stehen oben Hinweise (z. B. bisherige Termine fehlen im neuen Kalender), werden die Termine zunächst ohne Aufgaben
übernommen und unter „Nächste Abholungen“ einzeln mit „… freigeben“ bestätigt. Einrichten, Abruf von Hand und
Kalenderdateien (ICS/CSV) liegen eingeklappt unter „Kalender einrichten und abrufen“.

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

- **Posteingang** öffnet sich zuerst; neue Mails holt die Zentrale beim Öffnen selbst (höchstens alle 5 Minuten),
  „Abrufen“ (Pfeilsymbol) holt sofort. Die anderen Ordner (Gesendet, Entwürfe, Archiv, Spam, Papierkorb) über den
  Ordnernamen oben. Filter: Alle · Ungelesen · Wichtig.
- **Mail lesen:** Antworten, Weiterleiten, Archivieren, Löschen (Papierkorb). Darunter: **Objekt oder Kunde zuordnen**
  und **Aufgabe daraus machen** (Titel aus dem Betreff, Datum, Objekt – erscheint danach auf „Heute“/„Woche“).
  Auf iPad quer und Mac stehen Liste und Mail nebeneinander.
- **Neue Mail:** An, Betreff, Text, Anhänge (mehrere möglich), dann **Senden** – mit einer kurzen Rückfrage, an wen.
  „Als Entwurf sichern“ hebt die Mail auf; Entwürfe stehen unter „Neue Mail“.
Das Postfach-Passwort wird nicht mehr abgefragt. Lehnt das Postfach das Passwort ab (z. B. nach einer Änderung bei
STRATO), erscheint eine klare Meldung mit dem Weg zum Neueintragen; bis dahin versucht die Zentrale keine weitere
Anmeldung, damit das Postfach nicht gesperrt wird.

## Google-Sichtbarkeit (Mehr → Online)

Zeigt aus der Google Search Console, bei welchen Suchbegriffen die Website erscheint: durchschnittliche Position
(1 = ganz oben), Klicks, wie oft angezeigt, Trend gegenüber den 28 Tagen davor, neue und nicht mehr gefundene Begriffe.
Aktualisiert sich einmal am Tag beim Öffnen der Zentrale (Google liefert mit 2–3 Tagen Verzug). Einrichtung einmalig:
Dienstkonto in der Google Cloud anlegen, Search-Console-API aktivieren, JSON-Schlüssel herunterladen, Dienstkonto in der
Search Console mit Berechtigung „Eingeschränkt“ eintragen, Schlüssel in der Zentrale hochladen (Schritte stehen auf der Seite).

## Mehr

Abfallkalender, Regelaufgaben, Termine, Route, Arbeitsnachweise, Helfer, Kunden, Dokumente, Häuser/Mieter/Verträge,
Nebenkostenabrechnung, SEO und **Einstellungen** (E-Mail einrichten, Sicherung und Update, Systemstatus, Arbeitswoche).

## Sicherung

Mehr → Einstellungen → Sicherung und Update → „Sicherung jetzt erstellen“ → herunterladen. Empfehlung: einmal im Monat
und vor jedem Update. Die Sicherung umfasst die Datenbank; hochgeladene Originale (`private/uploads/`) bei Bedarf
zusätzlich per SFTP sichern.

## Neu in 430 (11/2026)
- **Alle Häuser auf einen Blick:** Objekte → „Häuser: QR-Meldung & Portal“ – je Haus einrichten, erneuern, abschalten; „Alle Aushänge drucken“ gibt ein PDF mit je einer Seite pro Haus.
- **Mieter-Meldungen:** Objekt öffnen → „Mieter-Meldungen & Hausverwaltung“ → „QR-Meldung einrichten“ → Aushang drucken und ins Treppenhaus hängen. Neue Meldungen stehen auf „Heute“; „Als To-do übernehmen“ legt sie in die Merkliste. Abgehakt = für die Verwaltung „Erledigt“.
- **Meldungs-Portal für Hausverwaltungen:** im selben Abschnitt „Portal einrichten“ → Link per Mail an die Verwaltung. Die Verwaltung sieht nur die Mieter-Meldungen ihres Hauses und hakt Erledigtes selbst ab. Den Monatsbericht (PDF) erstellst du bei Bedarf selbst auf der Objektseite.
- **Meldungen abhaken/löschen:** Mehr → Mieter-Meldungen: „✓ Erledigt“, „Wieder öffnen“ (unter Erledigt), „Löschen“ mit Sicherheitsabfrage.
- **Spam-Anfragen:** Anfragen → bei der Anfrage „Löschen / Spam“ → „Endgültig löschen“.
- **Helfer-App:** Mehr → Helfer → Person öffnen → „Helfer-Zugang“. Der Helfer meldet sich unter gszintern.de/helfer.php an und sieht nur seine zugeteilten Aufgaben.
- **Schlüsseldienst:** Mehr → „Schlüsseldienst: Einsätze und Umsatz“. Bei Zahlung „Rechnung“ entsteht automatisch eine Erinnerung „Rechnung offen“.
- **Preisrechner:** Mehr → Website → „Preisrechner Treppenhausreinigung“: eigenen Stundensatz und Minutenwerte eintragen, einschalten.
- **Bewertungskarten:** Mehr → „Bewertungen: per WhatsApp anfragen, Karten drucken“ (A4, 10 Karten).

## Neu in 440 (11/2026)
- **Push aufs Handy:** Zentrale am iPhone in Safari öffnen → Teilen → „Zum Home-Bildschirm“ → über das neue Symbol öffnen → Mehr → Benachrichtigungen → „Benachrichtigungen auf diesem Gerät aktivieren“ → „Test-Benachrichtigung senden“.
- **Welche Nachrichten?** Mehr → Benachrichtigungen → „Wobei benachrichtigen?“: Winterdienst, Frost, Mein Tag (06:30), Tonnen (18:00), Termine, Anfragen, Mieter-Meldungen, Mieten, E-Mail, Systemüberwachung – je mit Schalter, Uhrzeiten einstellbar.
- **Glättewarnung:** erscheint auf Heute (❄️). „Winterdienst für N Objekte einplanen“ legt dringende Aufgaben am Warntag (06:00) an. Winterdienst-Objekte = Objekte mit Regelaufgabe „Winterdienst“.
- **Wecker:** Mehr → Benachrichtigungen → „Wecker-Adresse erzeugen“ und bei cron-job.org alle 15 Minuten eintragen – sonst kommen Warnungen nur, wenn die Zentrale offen ist.
