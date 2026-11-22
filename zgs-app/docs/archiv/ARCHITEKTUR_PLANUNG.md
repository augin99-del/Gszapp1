# Planung Phase 2

Quellbasis: unverändertes GSZ-Phase01.zip, SHA256 f7ed13abc1690d98a4df0c5b717d3f3d37b58e42e8d8908805f69518bb6e67ef. Separate Arbeitskopie /workspace/gsz-phase02/zgs-app. Kein Neubau, keine Produktion.

## Bestand und Ergänzung

Vorhandene tasks-Felder due_date/start_time/duration_minutes/sort_order/priority und UNIQUE(recurring_job_id,scheduled_for) bleiben maßgeblich. Keine neue Aufgaben- oder Regelkopie. Die Migration ergänzt tasks um plan_locked, notes, window_start/window_end und planned_order. start_time bleibt eine ausdrücklich feste Zeit, planned_order ist die bestätigte automatische Reihenfolge. Vorhandene manuelle Reihenfolgen, feste Startzeiten und vor Phase 2 verschobene Wiederholungen werden auch ohne nachträgliches Sperr-Update geschützt.

planning_settings enthält globale Start-/Ziel-/Warnzeit, geschätzten Objektwechselpuffer und optionales Abendziel ohne Routing. planning_days erlaubt längere Universitätstage durch abweichenden Tagesstart. planning_appointments blockiert feste Intervalle. planning_proposals speichert Vorschläge mit Nutzer-ID, Zufallstoken, Bestandshash und Übernahmestatus. Keine redundanten Wochen-/Tageszuordnungstabellen: die Zuordnung steht weiter in tasks.due_date.

## Vorschlag und Übernahme

GET zeigt den gespeicherten Plan ohne Scheduler-Mutation. POST mit CSRF erstellt einen Vorschlag; künftige Wiederholungen werden rein lesend mit derselben Datums-/Saisonlogik wie im bestehenden Scheduler expandiert. Identitäten bestehender erledigter oder verschobener Aufgaben werden vorher gesammelt, sodass deren ursprüngliche Wiederholung nicht erneut vorgeschlagen wird. Kein Kalenderimport; ungeprüfte Routinen bleiben ausgeschlossen, Sonderquelle special_weekly bleibt tagfest.

Ein Vorschlag schreibt nur planning_proposals. Erst eine zweite bestätigte POST-Anfrage erzeugt fehlende Aufgaben oder ändert flexible Tageszuordnungen. Die Übernahme sperrt Vorschlag und betroffene Basistabellen transaktional und vergleicht einen vollständigen Hash des gelesenen Bestands. Schon angewandte Tokens sind wiederholbar ohne Wirkung, fremde/abgelaufene Tokens werden abgewiesen. Vorschläge verfallen nach zwei Stunden. Erledigte und geschützte Aufgaben werden nicht geändert. scheduled_for und recurring_job_id bleiben unverändert; der bestehende Scheduler kann danach dieselbe occurrence gefahrlos durchlaufen.

Manuelle Korrekturen: Vorschau verwerfen, bestehende Aufgabe bearbeiten oder für noch virtuelle Aufgaben die vorhandene Regelverwaltung nutzen, anschließend Vorschau neu erzeugen. Die Vorschau ist keine direkt editierbare zweite Aufgabenliste. Speichern einer Aufgabe sperrt sie standardmäßig; Freigabe nur durch ausdrückliche Checkbox. Feste Uhrzeit und Reihenfolge ggf. separat leeren. Für spontane Aufgaben zunächst einzelner Termin; für freie Verteilung Fälligkeitsfenster setzen und freigeben.

## Heuristik und Zeitmodell

planning_engine.php ist unabhängig von PDO, Session und HTML. Deterministisch bei gleichen Eingaben. Regelaufgaben ohne explizites Fenster dürfen ab occurrence bis sechs Kalendertage später verteilt werden, begrenzt auf die ausgewählte Woche; Einmalaufgaben standardmäßig nur am Fälligkeitstag. Explizite Fenster sind editierbar. Sommer-/Wintermonate begrenzen auch die flexible Verteilung. Tagoptimierung bleibt innerhalb der Fenster und des ausgewählten Tages. Überfällige oder nicht planbare Aufgaben bleiben sichtbar, werden nicht gelöscht. Undatierte Aufgaben werden zur manuellen Zuordnung gezeigt.

Priorisierung: geschützte Aufgaben zuerst reservieren; danach ursprüngliche Fälligkeit, Priorität und normalisierte Adresse als grobe Gruppierung. Tagesauswahl minimiert zuerst Überschreitung des Zielendes, bevorzugt dieselbe Adressgruppe und verbleibende Kapazität. planArea() ist die isolierte austauschbare Strategiegrenze für spätere Gebiets-/Routingdienste; aktuell keine Distanzberechnung, keine externe Anfrage. Stadt-/PLZ-Stammdaten sind nicht separat strukturiert; Gruppierung verwendet die gespeicherte Adresse, keine geokodierte Nähebehauptung.

Zeitrechnung in lokalen Minuten am Kalendertag; Datumsschritte über DateTimeImmutable mit Europe/Berlin. Standard 10:30/16:00/17:00. Puffer nur bei Objektwechsel, explizit als Schätzung; Standard 0 bedeutet fehlende Fahrzeitreserve. Fixtermine werden von flexiblen Arbeitsblöcken vollständig umgangen. Explizit widersprüchliche feste Uhrzeiten werden gewarnt, nicht still verschoben. Über Mitternacht liegende Vorschläge werden bei Übernahme abgewiesen. Arbeitsminuten sind Aufgabenminuten, Terminblöcke werden separat gezeigt und beeinflussen Endzeit/Kapazität.

Gespeicherte Dauer hat Vorrang. Fehlende bekannte Leistungsdauern bekommen Planungsstandards (Tonnen 15, Treppenhaus 75, Außenanlage 90/120, Fenster/Tiefgarage 90). Vorhandene Phase-1-Werte 120 bleiben 120; keine unbeaufsichtigte Regeländerung. Unbekannte Leistungen bleiben unbestimmt, Endzeit ist dann unvollständig. Nachfolgende Uhrzeiten sind in diesem Fall nur ein unvollständiger Ablauf, keine belastbare Zusage.

## Grenzen und Risiken

Kein global optimaler Tourenalgorithmus; keine echten Fahrt-/Pausen-/Feiertags-/Uni-Kalenderdaten. Keine automatische Foto- oder Leistungsnachweispflicht. Vorhandene freiwillige Fotofunktionen bleiben unverändert in Arbeitsberichten.

Harte transparente Grenzen: 5000 gespeicherte Aufgaben, 500 Regeln, 2000 Termine und 104 zu durchlaufende occurrences je Regel/Vorschau. Bei Überschreitung Abbruch statt unvollständigem Plan. Für langfristigen Betrieb sollten Datenzugriffe nach Zeitraum paginiert werden; aktuell konstante gebündelte Abfragen statt N+1, vorhandene Schedulerbegrenzung bleibt erhalten. Vorschläge werden nicht automatisch gelöscht; spätere datenschutzgerechte Aufräumregel erforderlich. Keine Last-/Parallelitätsprüfung trotz Transaktions-/Sperrkonzept. Neue Terminlöschung betrifft ausschließlich frei erfasste Planungsblocker, niemals Belege/Archive. Kein allgemeines revisionssicheres Änderungsjournal.
