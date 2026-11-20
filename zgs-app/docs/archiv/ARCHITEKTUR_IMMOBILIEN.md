# Meine Immobilien – integrierte Architektur v310/v320

Eigenständiger Fachbereich innerhalb derselben PHP-/MariaDB-Anwendung, ausschließlich für eigene Immobilien und denselben authentifizierten Inhaber. Keine Mieterportale, keine Fremdverwaltung. Vorhandene Navigation, Login/CSRF/PRG, PDO, Centparser, CSV-Prüfung, SHA-Originalablage, Audit und Unicode-Mehrseiten-PDF wiederverwendet. Keine neue kostenpflichtige Bibliothek, kein zusätzlicher Serverdienst. Lizenzprüfung OPEN_SOURCE_PRUEFUNG.md und ARCHITEKTUR_NEBENKOSTEN_KERN.md.

## Reale Tabellen

v310 ergänzt17 estate_-Tabellen: mutex, buildings, units, tenants, leases, terms, rent_charges, charge_adjustments, bank_accounts, proposals, bank_transactions, payments, allocations, transfers, documents, expenses, history. Die ausgeschriebenen Tabellennamen tragen jeweils Präfix estate_. v320 ergänzt settings, costs, settlement_proposals, settlements, settlement_documents; dazu allocation_from/allocation_to und Bankbetrag-/Datumsindex. Zusammen mit vorhandenen54 Tabellen:76. v330 ergänzt ausschließlich Mailentwurfspalten, keine weiteren Tabellen.

Private Gebäude sind keine GSZ-Betreuungsobjekte; private Mieter keine Geschäftskunden. Estate-Bankbewegungen und Zahlungen liegen vollständig getrennt von business bank_transactions/finance_payments. Genau zwei fest angelegte neutrale Volksbank-Kontoslots, IDs1/2, DB-Check und Serviceprüfung; keine tatsächlichen IBANs/Salden erfunden. Sparkasse/Geschäfts-EÜR werden weder gelesen noch bebucht, um Immobilienergebnisse zu berechnen. Geteilte technische Auditkette enthält nur private Aktionsidentitäten; private Änderungshistorie bleibt im geschützten Estate-Bereich.

## Verträge und tatsächliche Zahlungen

Inklusive ISO-Nutzungszeiträume, keine überlappenden Verträge je Einheit. Flächen in hundertstel m², Geld ausschließlich Integer-Cent; Grenzen vor Multiplikation und deterministische Rundung. Leerstand wird aus Lücken ermittelt, Eigennutzung explizit erfasst. Monatskonditionen mit Wirksamkeitsdatum; bestätigte Sollforderungen unverändert, Korrekturen als begründete Gegenzeilen. Bei Teilmonaten ausdrückliche kalendertägliche Vereinbarung oder manuell geprüfte Forderung; fehlende Vereinbarung wird nicht erraten.

Betriebs-/Heizkosten separat advance/flat/included/unconfirmed. Ungeklärte Vereinbarungen blockieren Nebenkosten, Pauschalen/Inklusivanteile bleiben beim Eigentümer. Bestätigte Alt-Sollwerte verhindern rückwirkende Änderung einer bereits geprüften Abrechnungsart. Komplexe Vertragsnachträge sind noch kein vollständiger Workflow.

CSV-Import nur mit eigener Kontoprüfung, expliziter Spaltenzuordnung, stabiler Quellen-ID, Vorschau und Bestätigung. Geänderte gleiche Quellen-ID blockiert; wiederholter Import idempotent. Tatsächliche Zahlung manuell oder aus einem Umsatz, danach explizite Zuordnung auf Kaltmiete/Betriebskosten/Heizung/Kaution. Teilzahlungen, signierte Erstattungen und unveränderliche Gegenzeilen; Saldo- und Quellenpoolprüfung unter Mutex verhindert parallele Überzuordnung. Eigene Umbuchungen nur nach Vorschlag/Bestätigung; keine Einnahme und keine Ausgabe. Bankausgabe erst manuell mit Gebäude/Einheit/Beleg zugeordnet und dadurch noch keine umlagefähige Nebenkostenposition.

## Abrechnung

Reiner, von PDO/HTML getrennter estateCalculateSettlement-Kern. Flächen/Einheiten/vertragliche Gewichte, Verbrauch oder ausdrücklich freigegebene externe Beträge; getrennte Belegpositionen und rechtliche Freigabe. Instandhaltung/Verwaltung bleiben Eigentümerkosten. Tagesanteile einschließlich Schaltjahr/Mieterwechsel/Vakanz, centgenaue Restsummenverteilung und Kostenerhaltung. Heizkostensplit kann im Kern anhand freigegebener Parameter rechnen; Oberfläche übernimmt explizite externe Empfängerbeträge. Keine selbst erfundene Heizquote/CO2-Stufe.

Tatsächliche Vorauszahlungen aus bestätigten Zahlungszuordnungen bis Ist-Stichtag; Sollbeträge werden nie als Zahlung unterstellt. Fachlicher Zuordnungszeitraum je Vorauszahlung; Teilperiodengrenzen müssen ausdrücklich gesplittet werden. Keine automatische Verlagerung eines Mietervorauszahlungsrests auf den Nachmieter.

Vorschau speichert vollständigen Quell-/Enginefingerprint. Veränderte Kosten/Verträge/Originale/Zahlungen/Vermieterstammdaten erfordern neue Vorschau. Freigabe erzeugt unveränderlichen JSON-Snapshot mit SHA und separate archivierte Original-PDF je Vertrag; Korrektur erstellt neue Version mit Vorgängerbezug. Exakt gleiche Periode kann versioniert werden, andere überlappende abgeschlossene Perioden blockieren. Belegdubletten auch gebäudeübergreifend warnen/blockieren ohne ausdrückliche begründete Abgrenzung. Keine stille historische Überschreibung.

Eigentümer-/Steuerberater-ZIP enthält alle betroffenen Mieter, Originalbelege, getrennte PDFs und Hashmanifest. Nur jeweilige Einzel-PDF an einen Mieter geben. Finale Salden werden bisher angezeigt/archiviert, nicht automatisch zu neuen Zahlungsforderungen. Historische Soll-Vorauszahlungen nicht zusätzlich zum Abrechnungssaldo addieren.

## Grenzen und Betrieb

Uploads außerhalb Webroot unter private/uploads/estate, zufreie SHA-Dateinamen; Download nur nach Login/Hash-/Größenprüfung. PDF/JPEG/PNG/UTF8-Text/CSV max10MB, BankCSV max2MB/1000Zeilen, VerteilCSV256KB/2000Empfänger, Vorschau max8MB, PDF-/Export-Summe max20MB. Begrenzte Listen; bei sehr vielen Stammdaten gezielte Suche/Paginierung ergänzen. Kein automatischer Malware-Scanner, kein hardwareseitiges WORM und keine GoBD-Garantie. Vollständige DB und geschützte Dateien gemeinsam sichern.

Neutrales Messdienst-CSV ist keine nachgewiesene offizielle ista-Schnittstelle. Onlinebanking/SCA/echte Volksbankdateien, gesetzliche Vertrags-/Heiz-/CO2-Prüfung und echte STRATO-Umgebung offen. Kein vollständiger rechtlicher Ersatz für fachliche Prüfung. Zustandsänderungen nur authentifiziert, CSRF, Prepared Statements, serverseitige Prüfungen und PRG.
