# Reiner Nebenkosten-Rechenkern, Schema 1

Stand 10.11.2026. Implementierung `private/estate_calculation.php`, Einstieg
`estateCalculateSettlement(array $input): array`. Keine Datenbank, Uhr, Dateien,
Netzwerkzugriffe, HTML-Ausgabe oder automatische Rechtsentscheidung. Der Aufrufer
liest und autorisiert seine Daten und bestätigt fachliche Grundlagen außerhalb des
Kerns. Bei fehlenden/mehrdeutigen Grundlagen: `InvalidArgumentException` mit
Feldpfad; kein vermeintlich vollständiges Teilergebnis.

Der Kern ist eine mathematisch geprüfte Arbeitshilfe. Kein Nachweis rechtlicher,
steuerlicher, HeizkostenV-/CO2KostAufG-/GoBD-Konformität oder Produktivreife.
Insbesondere bedeutet `approved: true` die vom Aufrufer dokumentierte externe
Entscheidung, keine juristische Prüfung durch den Rechenkern.

## Quellen- und Komponentenprüfung vor Implementierung

Öffentliche Quellen am 10.11.2026 lesend über Webwerkzeuge geprüft:

- Mietfuchs: <https://github.com/speedone/mietfuchs>,
  <https://mietfuchs-app.de/>,
  <https://github.com/speedone/mietfuchs/blob/main/LICENSE>.
  Repository-/Websiteinformationen und die gecachte GitHub-Lizenzansicht nennen
  AGPL-3.0. Die ebenfalls geprüfte rohe Datei
  <https://raw.githubusercontent.com/speedone/mietfuchs/main/LICENSE> lieferte
  im Webwerkzeug dagegen MIT, Copyright 2026 speedone. Dieser Widerspruch ist
  **keine belastbare Freigabe zur Codeübernahme**. Kein Code kopiert und kein
  Paket eingebunden. Funktionale Anforderungen wie Leerstand und Mieterwechsel
  wurden eigenständig umgesetzt; fremde Rechtsbehauptungen nicht übernommen.
- <https://github.com/BitNike/immobilienverwalter>: öffentliche Repositoryangabe
  AGPL-3.0, Gesamtanwendung statt isoliertem PHP-Rechenkern. Kein Code übernommen.
- Brick/Money: <https://github.com/brick/money> und offizielles Manifest
  <https://raw.githubusercontent.com/brick/money/main/composer.json>: MIT,
  PHP `^8.2`, Abhängigkeiten `brick/math` und `psr/simple-cache`. Geeignete
  Geldbibliothek, aber löst Vertrags-/Zähler-/Heizkostenfragen nicht. Für diesen
  begrenzten Centkern ist keine weitere Abhängigkeit erforderlich. Keine Dateien
  aus Brick/Money übernommen.

Entscheidung: eigenständiger begrenzter PHP-Code, 64-Bit-PHP, Standard-
DateTime, keine zusätzliche Extension, kein Composer-/Serverdienst, keine
laufenden Kosten. Bestehendes `financeCents` wird nicht eingebunden, weil dessen
Datei weitere Plattformteile nachlädt und dieser Kern ausschließlich bereits
validierte Integer-Centbeträge annimmt. STRATO muss PHP-Version/64-Bit separat
nachweisen; lokale Tests verwenden `/workspace/gsz-tools/bin/php`.

## Eingabevertrag

Arrays/Literale sind JSON-kompatibel. IDs sind Strings aus
`A–Z a–z 0–9 _ . -`, Länge 1–80; auch Datenbank-IDs vorher explizit als String
übergeben. Texte/Quellenbezeichnungen: nicht leer, maximal 2000 Bytes.
Datumswerte sind echte inklusive ISO-Kalendertage `YYYY-MM-DD`, Jahre 1000–9999,
stets `Europe/Berlin`. Alle Geld-/Gewichtswerte sind echte PHP-/JSON-Integer;
Dezimalstrings und Fließkommazahlen sind unzulässig.

Vollständiges Minimalbeispiel:

```json
{
  "schema_version": 1,
  "currency": "EUR",
  "period": {"from": "2024-01-01", "to": "2024-12-31"},
  "as_of": "2025-03-01",
  "units": [{"id": "1", "area_units": 6000}, {"id": "2", "area_units": 4000}],
  "occupancies": [
    {"id": "10", "unit_id": "1", "kind": "tenant", "tenant_id": "100", "from": "2023-07-01", "to": "2025-06-30", "billing_modes": {"operating": "settlement", "heating": "settlement"}},
    {"id": "11", "unit_id": "2", "kind": "owner", "from": "2024-01-01", "to": "2024-12-31"}
  ],
  "costs": [{
    "id": "insurance-2024", "source_id": "receipt:300:position:1",
    "category": "operating", "amount_cents": 100000,
    "from": "2024-01-01", "to": "2024-12-31", "apportionable": true,
    "legal_basis": "Geprüfte Vertragsanlage Betriebskosten",
    "allocation": {"method": "area"}
  }],
  "advances": [{
    "id": "allocation-400", "source_id": "confirmed-rent-allocation:400",
    "occupancy_id": "10", "component": "operating", "status": "paid",
    "paid_on": "2024-12-02", "allocation_from": "2024-12-01", "allocation_to": "2024-12-31",
    "amount_cents": 50000
  }]
}
```

Dieses Beispiel ergibt Kosten 100000 Cent, Mieter 60000, Eigentümer 40000,
Ist-Vorauszahlung 50000 und Mieternachzahlung 10000 Cent. Es werden keine zwölf
Monatszahlungen aus einer Sollvereinbarung erzeugt.

| Feld | Vertrag |
| --- | --- |
| `schema_version`, `currency` | Pflicht: `1`, `EUR`; keine Währungsumrechnung. |
| `period` | Beliebiger Beginn/Ende, 1–3660 inklusive Tage. Kein Kalenderjahrzwang. |
| `as_of` | Expliziter Snapshot-Stichtag, mindestens `period.to`; Zahlungsdatum höchstens dieser Tag. |
| `units` | 1–1000 vollständige Gebäudeeinheiten. `area_units` nur für Flächenverteilung Pflicht, positiv bis 1000000000. Einheit überall gleich, z.B. hundertstel m². |
| `occupancies` | 0–5000 Datensätze; `id` entspricht dem Miet-/Nutzungsvertrag, nicht nur der Person; `unit_id`, `kind=tenant|owner`, `from`, `to`; bei tenant zusätzlich `tenant_id`. `billing_modes` siehe unten. |
| `costs` | 0–2000 Quellenpositionen. ID und `source_id` jeweils eindeutig. |
| `advances` | 0–10000 bestätigte Ist-Zahlungszuordnungen. ID und `source_id` jeweils eindeutig. |

Belegungszeiträume dürfen über den Abrechnungszeitraum hinausreichen, innerhalb
derselben Einheit aber niemals überlappen, auch nicht auf einem gemeinsamen
Grenztag. Auszug 30.06. und Einzug 01.07. ist zulässig; beide am 30.06. ist ein
Fehler. Unbelegte Tage sind automatisch Leerstand. Alle Einheiten bleiben in der
Verteilbasis, einschließlich Selbstnutzung und Leerstand. Bei Mietern enthält
`billing_modes` für **beide** Komponenten `operating` und `heating` jeweils
`settlement` oder `owner`. Der Aufrufer ordnet geprüfte Vorauszahlungsverträge
`settlement` zu; Pauschal-/Inklusivverträge werden `owner`. Unbestätigte
Vertragsgrundlagen dürfen nicht übersetzt werden; unbekannte/teilweise Modi
erzeugen einen Fehler. Die Integration liefert immer explizite Modi.

Für einfache bisherige isolierte Eingaben bleibt das gesamte Feld optional;
ohne `billing_modes` erklärt der Aufrufer durch seinen Input beide Komponenten
als `settlement`. Dieser Standard ersetzt keine Vertragsprüfung. Beim owner-
Modus bleiben Mieter und Anteil vollständig im Nenner, die Kosten werden beim
Eigentümer zurückbehalten. Das gilt pro Komponente auch bei Mieterwechsel und
externen Messdienstbeträgen; ein extern ausgewiesener Anteil wird dadurch nicht
automatisch erneut vom Pauschalmieter verlangt. `shares.charged_to` zeigt das
Ergebnis als `tenant` oder `owner`, `kind` bleibt die tatsächliche Nutzungsart.
Nicht abrechenbare Komponenten bekommen keine Ist-Vorauszahlungen; echte
Pauschal-/Inklusivzahlungen sind keine Nebenkostenvorauszahlungen. Statements
enthalten die expliziten effektiven Modi, auch wenn beide Kosten null sind.

Kostenbeträge beziehen sich **genau auf ihren angegebenen Leistungszeitraum**.
Dieser muss vollständig im Abrechnungszeitraum liegen. Keine stille Aufteilung
einer periodenübergreifenden Rechnung. Der Aufrufer muss eine fachlich bestätigte
Abgrenzung als eigene, eindeutig referenzierte Quellenposition liefern. Mehrere
unterschiedliche Kosten dürfen denselben Leistungszeitraum abdecken.

Kostenkategorien: `operating`, `heating`, `maintenance`, `administration`,
`other_non_apportionable`. Jede Position braucht `apportionable` als Boolean.
Die letzten drei Kategorien dürfen nur `false` sein. Dann bleibt der gesamte
Betrag beim Eigentümer und zählt zu `excluded_cents`; eine Verteilbasis ist nicht
erforderlich. Umlagefähige Positionen brauchen `legal_basis` und `allocation`.
Gutschriften als negative Quellenbeträge sind zulässig und werden symmetrisch
verteilt; keine Beträge stillschweigend auf null begrenzen.

`source_id` bezeichnet eine konkrete Quellenposition, z.B. Beleg plus Zeile;
mehrere echte Positionen derselben Rechnung brauchen unterschiedliche
Positionsreferenzen. Doppelte Quellen werden auch bei identischem Betrag
abgewiesen. Der Kern ersetzt keine Belegablage oder quellenübergreifende
Duplikaterkennung.

## Verteilungsmethoden

Jeder Kostensatz erzeugt Empfänger für genau seinen Zeitraum:
`occupancy:<occupancy.id>` für jede berührte Belegung und
`vacancy:<unit.id>` für unbelegte Tage dieser Einheit. Ein Leerstandsempfänger
kann mehrere Lücken aggregieren; `date_ranges` gibt alle tatsächlichen Lücken
aus. Die Empfängerliste lässt sich vor der Eingabe expliziter Werte mit der reinen
Hilfsfunktion `estateCalcRecipients($unitsById, $occupanciesById, $from, $to)` bestimmen.

| `allocation.method` | Weitere Pflichtfelder/Bedeutung |
| --- | --- |
| `area` | Gewicht = `unit.area_units × berührte Kalendertage`. |
| `unit` | Gewicht = berührte Kalendertage; jede Einheit zählt gleich. |
| `custom` | `contract_basis` und `unit_weights` als Map aller Einheiten auf ganze Gewichte 0–1000000000; multipliziert mit Tagen. Null nur als explizite Vertragsentscheidung; positive Gesamtsumme erforderlich. |
| `consumption` | `source_id`, `validated: true`, `from`, `to`, `weights` als Map **aller** Empfängerschlüssel auf ganze belegte Verbrauchs-/Anteilswerte 0–1000000000. Quellenzeitraum muss exakt dem Kostenzeitraum entsprechen. |
| `external` | Wie consumption, aber `amounts_cents` statt weights. Exakte externe Empfängerbeträge müssen zusammen genau den verteilbaren Betrag ergeben und dasselbe Vorzeichen haben. |
| `heating_split` | Ausschließlich heating; unten beschrieben. |

Verbrauchswerte beziehen sich bereits auf den jeweiligen Empfänger und Zeitraum.
Es gibt keine automatische Multiplikation von Verbrauch mit Belegungstagen,
keine geschätzte Zwischenablesung und keine Ableitung aus dem Zahlungsstand.
Auch Nullverbrauch und Nullbetrag müssen für jeden Empfänger ausdrücklich
geliefert werden; fehlende Nullwerte werden nicht erfunden. Nicht berührte alte
Mietverhältnisse dürfen nicht in einer Verbrauchs-/externen Verteilung stehen.

## Heizung, Messdienst und CO2

Umlagefähige heating-Positionen akzeptieren ausschließlich `external` oder
`heating_split`. Eine reine Fläche-/Einheitenverteilung wird nicht als automatisch
rechtlich erlaubte Heizkostenformel angeboten. Externe ista-/Messdienstbeträge
werden exakt konsumiert, nicht aus einer behaupteten API abgerufen.

Jede solche Position braucht zusätzlich:

```json
"co2": {
  "decision": "external",
  "source_id": "messdienst-co2:2024",
  "reason": "Explizit geprüfte externe Eigentümeraufteilung",
  "approved": true,
  "owner_cents": 10000
}
```

Alternativ `decision: "not_applicable"`, mit Quelle, begründeter expliziter
Bestätigung und `owner_cents: 0`. Ungeklärte CO2-Fragen blockieren die Abrechnung.
Kein Gebäudestufenmodell, Emissionsfaktor oder gesetzlicher Prozentsatz wird
erfunden. Der externe Eigentümerbetrag muss Vorzeichen und Betragsgrenze der
Position einhalten. `amount_cents` ist der Gesamtbetrag **einschließlich** dieses
Eigentümeranteils. Erst wird `co2.owner_cents` beim Eigentümer zurückbehalten,
der Rest wird verteilt. Externe Empfängerbeträge müssen daher genau
`amount_cents - co2.owner_cents` ergeben. Bei bereits netto importierten
Messdienstbeträgen muss die Quelle eine dazu passende explizite Entscheidung
liefern; kein stiller zweiter CO2-Abzug.

Expliziter Verbrauchssplit:

```json
"allocation": {
  "method": "heating_split", "approved": true,
  "source_id": "freigegebene-vertrags-und-messgrundlage:2024", "fixed_bps": 3000,
  "fixed_basis": {"method": "area"},
  "consumption": {
    "source_id": "validierte-zwischenablesungen:2024", "validated": true,
    "from": "2024-01-01", "to": "2024-12-31",
    "weights": {"occupancy:10": 150, "occupancy:11": 100}
  }
}
```

`fixed_bps` ist die **extern freigegebene** Grundkostenquote von 0–10000
Basispunkten. Der Kern setzt keine gesetzliche Bandbreite oder Zulässigkeit
voraus. `fixed_basis` unterstützt area/unit/custom. `consumption` folgt dem
vollständigen belegten Verbrauchsschema. Zunächst wird der verteilbare Betrag
centgenau zwischen Grund-/Verbrauchskosten geteilt, dann werden beide Töpfe
getrennt auf Empfänger verteilt. Beide Grundlagen müssen positiv belegbar sein,
auch wenn eine Quote null ist; bei fehlender belastbarer Grundlage externe
fertige Beträge liefern. `split` enthält beide Geldtöpfe, Gewichte und Anteile.

## Tatsächliche Vorauszahlungen und Salden

Nur `status: "paid"`, eindeutiger bestätigter Quellenbezug, `occupancy_id`,
`component: "operating"|"heating"`, echter `paid_on` und nicht-null
`amount_cents`. `allocation_from`/`allocation_to` müssen vollständig in der
betreffenden Miet-/Abrechnungszeit liegen; sie sind die explizite Zuordnung,
nicht das Bankbuchungsdatum. Spätere tatsächliche Zahlungen bis `as_of` können
für die Vorperiode berücksichtigt werden. Keine Aufteilung einer Zahlung auf
Perioden oder Komponenten ohne ausdrückliche einzelne Zuordnungen.

Tatsächliche Rückzahlung/Storno als zusätzliche belegte negative Zeile, mit
eigener ID/Quelle; die ursprüngliche positive Zeile bleibt erhalten. Netto
Vorauszahlungen dürfen je Mietverhältnis **und Komponente** nicht negativ sein;
sonst Fehler. Keine automatische Verrechnung mit Kaution, Kaltmiete,
Soll-Vorauszahlungen, anderem Mieter oder anderer Komponente. Mehrere Zahlungen
für denselben Monat sind erlaubt; doppelte Quellen sind verboten.

`balance_cents = cost_cents - advance_cents`: positiv = Nachzahlung,
negativ = Guthaben des Mieters. Statement pro Vertrags-/Belegungs-ID; dieselbe
Person kann mehrere getrennte Verträge haben. `components` enthält dieselben
Kosten-/Ist-/Saldozahlen getrennt für operating/heating.

## Ergebnis und Rundung

`schema_version`, `currency`, `timezone`, `period` mit `days`, `as_of`,
`cost_lines`, `tenant_statements`, `advances`, `totals`, `rounding`.

- Jede Kostenzeile enthält Quelle, Zeitraum, Gesamtkosten, Mieter-/Eigentümer-
  und ausgeschlossene Beträge, gegebenenfalls CO2 sowie übernommene Grundlage.
  `shares` listet Empfängerschlüssel, belegte Tage, Nutzungsart, IDs, Gewicht und
  Centbetrag. Die Teilbeträge summieren sich einschließlich CO2 exakt zur Quelle.
- `totals.owner_cents` enthält Selbstnutzung, Leerstand, ausgeschlossene
  Kosten und extern zurückbehaltenen CO2-Anteil.
  `totals.excluded_cents` ist **darin enthalten**, nicht zusätzlich zu addieren.
- `totals.cost_cents = tenant_cost_cents + owner_cents`;
  `balance_cents = tenant_cost_cents - advance_cents`.
- Größter Rest je Kostentopf; Gleichstand: binäre aufsteigende Empfängerschlüssel.
  Keine Locale-/Array-/Datenbank-Reihenfolge beeinflusst Beträge. Negative
  Gutschriften kehren dieselbe positive Verteilung um. Beim Split ist bei
  Gleichstand `consumption` vor `fixed` die feste lexikalische Reihenfolge.

Sämtliche Geldwerte und Zwischensummen sind auf ±99999999999 Cent begrenzt.
Gewichte pro Verbrauch/Einheit maximal 1000000000; Tagesgewichte höchstens
3660 × diesen Wert und Gewichtssumme höchstens 4000000000000000. Die rationale
Multiplikation/Division verarbeitet Integer-Bits, ohne den möglicherweise
überlaufenden Gesamtzähler `Cent × Gewicht` zu bilden. Weder float noch bcmath/
gmp nötig. Überschrittene Grenzen erzeugen Fehler; keine stillen Casts.

## Tatsächlich ausgeführte Tests

```sh
/workspace/gsz-tools/bin/php -l private/estate_calculation.php
/workspace/gsz-tools/bin/php tests/test_estate_calculation.php
```

Aktueller bestandener Stand: 186 Assertions. Die Suite prüft ein vollständiges
Gebäude mit 495900 Cent Kosten, Schaltjahr 2024, Wechsel/31 Tagen Leerstand,
Eigennutzung, ausgeschlossener Verwaltung/Instandhaltung, Teilzahlungen und
Rückzahlung sowie ein weiteres Gesamtgebäude mit Pauschalvorgänger,
abrechenbarem Nachfolgevertrag und inklusiver Heizung. Dazu Jahreswechsel/DST,
numerische Datenbank-IDs, exakte Centreste und Überlaufprodukte,
Verbrauch, Vertragsgewichte, externe Heiz-/CO2-Beträge, freigegebener Split,
Quell-/ID-Dubletten und blockierende fehlende Grundlagen. Keine echte Bank,
kein Messdienst und keine Produktion kontaktiert. Diese Suite allein ist keine
DB-/Browser-/Betriebs-/Rechtsabnahme der späteren Integration.
