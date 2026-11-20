# Phase 3 – Abfall und Routing

Basis ausschließlich GSZ-Phase02.zip, SHA256 74010dfc561023108f2070f1aa2833742dbb032bc8dd0d0a5f05503d5bd1a304. Ist-Aufnahme in PHASE3_BESTAND.md. Keine Produktivzugriffe. Keine Änderung an Rechnungs-/Dokumenten-/Buchhaltungslogik.

## Quellenprüfung, 09.11.2026

- [Stadt Gütersloh: Abfall-App](https://www.guetersloh.de/de/rathaus/fachbereiche-und-einrichtungen/umweltschutz/abfallberatung/abfall-app.php): beschreibt adressbezogene Abfuhren und Integration in den persönlichen Terminkalender. Dies belegt KEINE dokumentierte Backend-API oder frei abrufbare ICS-URL für jedes Objekt.
- [Rheda-Wiedenbrück: Abfall/Müllabfuhr](https://www.rheda-wiedenbrueck.de/umwelt-bauen-verkehr/umwelt-klima/umwelt-abfall/abfall-muellabfuhr/): amtlicher Abfuhrkalender, Tonnenticker und eingebettetes abfallnavi erwähnt. Verlinkt amtlichen PDF-Kalender 2026. Keine belastbar dokumentierte frei nutzbare Backend-Schnittstelle in der gelesenen Seite. Portal nicht gescrapt, keine Schutzumgehung, keine privaten Adressen übertragen.
- ECOWEST-Seite /tonnenticker-pro im Suchergebnis gefunden, Inhaltsabruf scheiterte mit proxy_error; keine Exportfähigkeit daraus behauptet.

Daher funktionierender manueller ICS-/CSV-Workflow. Aus persönlichem Kalender nur zulässige, adressbezogene Einzeltermine exportieren. Für amtliche PDF-Kalender geprüfte Zeilen manuell in CSV übertragen, keine PDF-OCR oder erfundene Fraktionszuordnung. Konkrete Quellen/Abfuhren für sieben Adressen wurden nicht extern validiert.

## Datenmodell und Sicherheit

Additive v220-Migration nach v210. Neue Tabellen: waste_sources, waste_events, waste_origins, waste_imports, waste_task_links, waste_history, routing_locations, routing_cache. Mit Phase 2 insgesamt 25 Tabellen. tasks.waste_key ist eindeutige zusätzliche Herkunft; bestehende tasks bleiben die einzige Aufgabenwahrheit. Keine doppelten recurring_jobs. planning_settings erhält Start-/Endadresse; planning_proposals wird auch für Routen verwendet.

Quellen speichern Objekt, Abholadresse, Quelldatum, Gültigkeit, Nutzerprüfzeit, letzten Import. Abfuhridentität objektübergreifend je Objekt+Datum+Fraktion; beliebig viele Quellenherkünfte mit Originaltitel, ursprünglichem DTSTART inklusive Zeitzone, Quellen-ID, Datensatzhash und Importzeit. Vorschlag speichert Datei-SHA256 und normalisierten Inhalt; Originaldateien werden nicht im Webverzeichnis oder Lieferpaket gespeichert. PHP-Session hält nur den maximal 1-MB-Entwurf zwischen Upload und Fraktionszuordnung. Kein XML-Parser, kein XXE, kein URL-Fetch im Abfallmodul.

UTF-8, Text-MIME, passende .ics/.csv-Endung, max. 1 MB, max. 500 Termine, begrenzte Zeilen-/Textlängen. CSV-Spaltennamen und Trennzeichen ausdrücklich angeben; Datum YYYY-MM-DD. CSV-Export nicht implementiert, also keine ungeschützte Excel-Formelausgabe. UI-Escaping auch für Originaltexte/Fehler.

ICS: ganztägige DTSTART-Daten; UTC oder Europe/Berlin; Kalenderüberschrift und X-WR-TIMEZONE berücksichtigt. Nicht existente Sommerzeit und mehrdeutige lokale Winterzeit werden abgewiesen (UTC verwenden). DTEND wird auf plausiblen eintägigen Termin geprüft. RRULE/RDATE/EXDATE/RECURRENCE-ID/DURATION und CANCELLED werden bewusst nicht interpretiert: als konkrete Einzeltermine exportieren oder manuell prüfen. Keine stille Berechnung von Abholverschiebungen.

## Import und Konflikte

1. Quelle als separaten, ungeprüften Stammdatenschritt anlegen.
2. Datei lesen, jede vorkommende Originalbezeichnung ausdrücklich Restmüll/Bio/Papier/Gelb oder unklar zuordnen.
3. Persistierte Vorschau mit Konfliktliste; keine Abfuhren/Aufgaben durch Vorschau. Eine gespeicherte Vorschlagszeile ist keine fachliche Importfreigabe.
4. Bestätigen: Import allein erzeugt Status importiert, unbekannte Fraktion unklar. Nur separate ausdrückliche Fachprüfung erzeugt Status geprueft und Aufgaben. Quellenprüfung und Terminstatus bleiben unterscheidbar.
5. Bei geänderter Quellen-ID, fehlenden vorherigen Terminen (auch bei Teildateien), abweichender Adresse/Gültigkeit: Konflikte. Sie können nur mit ausdrücklicher Kenntnisnahme unter Erhalt ALLER alten Daten zunächst ungeprüft importiert werden. Anschließend Einzeltermine abgleichen. Alter Termin kann explizit veraltet markiert werden; alte Aufgaben bleiben erhalten und müssen ggf. im Plan manuell korrigiert werden. Keine automatische Terminverschiebung, kein Löschen/stilles Erledigen. Bei unbekannter Fraktion korrigierte Datei mit richtiger Zuordnung importieren, unklaren Altdatensatz nachvollziehbar veraltet markieren.

GET_LOCK serialisiert Importe, Transaktion sperrt Bestandsdaten und prüft Fingerprint. Wiederholter angewandter Token hat keine Wirkung; erneute Datei identifiziert existierende Abfuhren und Aufgaben. Eindeutige Schlüssel plus Aufgabenlinks verhindern Dubletten. Vorschläge mit Adminbindung und 2h-Frist. Prüfhistorie speichert Nutzer, Entscheidungen, Hash und Konflikte. Quellen/Importe werden derzeit nicht automatisch gelöscht. Kein allgemeines revisionssicheres Auditversprechen.

## Aufgaben / Zeitlogik

Geprüfte Abfuhrdatum-Daten erzeugen Vortag raus und Abfuhrtag rein, standardmäßig 15 Minuten. Monats-/Jahreswechsel durch lokalen Kalender. Ungeprüfte Routinen bleiben pausiert; Sonntag Diekstraße entsteht nur aus konkreter freigegebener Montagabfuhr (oder ausdrücklich manueller Aufgabe).

Fontänenstraße Gelb am Donnerstag: vorhandene special_weekly-Regel für Mittwoch raus/Donnerstag rein und deren occurrence-Identität werden wiederverwendet, bei Bedarf über zgsInsertOccurrence materialisiert. Scheduler erzeugt danach keine zweite Aufgabe. Anderes importiertes Datum wird nicht als Wochenregel umgedeutet.

Bündelung ist optional und ausdrücklich: mehrere gleichzeitig importierte Fraktionen am selben Objekt/Datum/Richtung können EINEN Besuch mit zusammen 15 Minuten bilden; Aufgabenlinks erhalten alle Fraktionen. Ohne Auswahl 15 Minuten pro Fraktion, in Vorschau ausdrücklich ausgewiesen. Gespeicherte Dauer danach über vorhandene Aufgabenbearbeitung editierbar. Bereits erledigte/gesperrte/verschobene/fest sortierte Bündelbesuche werden nicht um neue Fraktionen erweitert: Vorgang bricht transaktional ab, stattdessen Einzelaufgaben verwenden. Keine nachträgliche automatische Zusammenführung früherer Einzelbesuche. Eine erstmalig angehängte Fraktion bei einem noch unveränderten Bündel ergänzt nur den Titel; die bewusste Bündelentscheidung wird protokolliert.

Müllkalender mit Objekt-/Wochenfilter, lesbaren Fraktionen, Originalherkunft, Quelle/Prüfstatus und fehlenden/veralteten Kalendern. Dashboard/Tagesansicht zeigt heute raus/rein und morgen fällig mit tatsächlichem Planwert, Quelle, Status und Erledigt-Knopf über geschützte bestehende Planungsaktion. Optionale Notiz im Aufgabenformular, kein Fotozwang. Sonderwochenregeln ohne importierte Quelle stehen weiterhin separat im Tagesplan und werden nicht als amtlich verifiziert dargestellt.

## Routingoptionen und Lizenzen

- [OSRM](https://project-osrm.org/), [Lizenz](https://github.com/Project-OSRM/osrm-backend/blob/master/LICENSE.TXT): BSD-artige 2-Klausel-Lizenz; Selbstbetrieb mit eigenem OSM-Datensatz technisch möglich. Serverressourcen, Datenpflege und Betrieb kosten Aufwand; kein kostenloses Hosting versprochen. OSM-Datenbedingungen/Attribution und ggf. ODbL gesondert beachten. Ein dedizierter HTTPS-OSRM-Table-Adapter ist implementiert, standardmäßig deaktiviert.
- [Valhalla](https://github.com/valhalla/valhalla/blob/master/COPYING): MIT; flexibel und selbst betreibbar, aber zusätzlicher Betriebsaufwand. [Offizielle Demo-Hinweise](https://valhalla.github.io/valhalla/start/introduction/) nennen Fair Use, Rate Limits und bei publizierten Apps Rücksprache/Client-ID. Keine Demo als unbegrenztes Backend konfiguriert. Valhalla-Adapter nicht implementiert, Provider-Interface erlaubt spätere Ergänzung.
- Kommerzielle/gehostete APIs: kein Konto eingerichtet, keine Tarifzusage/Integration/Kostenfreigabe. Vor Aktivierung Datenschutz, Bedingungen, Aufbewahrung, Anfragebudget und Kosten prüfen.

## Provider / Heuristik

GszRoutingProvider::matrix(points) getrennt von HTML, PDO und Scheduler. Implementierung GszOsrmProvider nutzt fest serverseitig konfigurierten HTTPS-Endpunkt (kein Benutzer-URL-Eingabefeld), TLS-Prüfung, keine Redirects, 5s Netzwerk-Timeout, 1-MB-Antwortlimit, validierte endliche nichtnegative Distanzen/Dauern samt Quelle/Zeitstempel. Koordinaten werden nur nach bewusster manueller Prüfung mit Quelle/Qualität/Zeitpunkt gespeichert (90 Tage gültig). Keine automatische Geokodierung, keine erfundenen Koordinaten.

Maximal eine Matrix-Anfrage pro bewusster Vorschau, maximal 25 Punkte (einstellbar 2–25), keine O(n²)-Remote-Anfragen. Matrix intern quadratisch, lokal streng begrenzt. Cache konfigurierbar 60–86400 Sekunden, standardmäßig 3600; Providerantworten zu alt/fehlerhaft führen zum vollständigen Offline-Fallback. Eine bestätigbare oder aktuell im Tagesplan verwendete echte Route darf höchstens eine Stunde alte Werte enthalten. Keine Anfragen beim GET auf Dashboard/Plan/Route. Cache ist serverseitig; zukünftige Aufräumregel erforderlich.

Ohne vollständige geprüfte Koordinaten inklusive Start/Ziel/Terminen oder bei Providerfehler vollständig offline, keine Kilometer/Dauern ausgedacht. Klar geschätzter Puffer (auch Start-/Zielfahrt, wenn Adresse eingegeben), bei 0 fehlt Reserve. Endziel benötigt tatsächliche Eingabe, keine Privatadresse im Code.

Flexible Aufgaben zwischen geschützten Ankern werden deterministisch nach Fahrkosten (falls verfügbar), Adresse/Objektgruppierung und stabiler Herkunft geordnet. Geschützte manuelle Reihenfolgen/Fixzeiten und gebundene Mülltermine bleiben erhalten; erledigte Aufgaben fehlen in der neuen Tour. Tage werden bei Route nicht verändert. Wochenverteilung bleibt die vorhandene Phase-2-Vorschau. Keine mathematische Optimalitätsbehauptung.

Fixtermine bleiben Blocker. Bei vollständiger Routingmatrix werden konservative maximale An-/Abfahrtsreserven aus der Matrix um Blocker gelegt; Fahrabschnitte enthalten die Terminadressen in chronologischer Folge. Daher keine minutengenaue optimale Terminanfahrt behaupten: bewusst konservatives Zeitbudget. Ohne Daten wird das Konfliktrisiko deutlich angezeigt. Manuell widersprüchliche Fixzeiten werden gewarnt und nicht still geändert.

Routenvorschau/Differenz und ausdrückliche Übernahme nutzen planning_proposals. routeApply aktualisiert nur flexible planned_order-Werte; erledigte/geschützte Aufgaben und Herkunft bleiben unverändert. Hashabweichungen stoppen. Nach Übernahme aktueller Snapshot gespeichert; Tagesansicht verwendet die berechnete Tour nur solange Bestand und Datenfrische passen. Nach manuellen Änderungen Route neu berechnen; sonst wieder gewöhnliche Phase-2-Anzeige. Quelle/Stand und einzelne Navigationslinks immer nachvollziehbar, keine automatische Navigation.

## Betriebsgrenzen

PHP-Zeitzone Europe/Berlin für Kalendertage; DB-Verbindungen über bestehenden Loader setzen UTC für technische Zeitstempel/Cache. Keine historischen Geschäftsdaten umgeschrieben. Alte ungeprüfte DATETIME-Zonen vor späterem Rollout prüfen. Limits aus Phase 2 (5000 Aufgaben, 500 Regeln, 2000 Termine/104 Wiederholungsschritte) bleiben bestehen; Importfingerprint maximal 5000 Zeilen je beteiligter Tabelle, Wochendarstellung max. 1000 Abfuhren und begrenzte Herkunftsanzeige. Kein Parallel-/Lasttest, keine vollständige fachliche/Hosting-/Security-Abnahme. Noch keine automatische Quellensynchronisierung, keine PDF-Auswertung, kein kompletter ICS-Standardparser.
