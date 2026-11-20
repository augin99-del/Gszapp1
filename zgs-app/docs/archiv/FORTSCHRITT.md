# Aktuell: Komplett370 –13.11.2026

Arbeitskopie /workspace/gsz-inbetriebnahme/zgs-app, Ziel370/85Tabellen. Verbindlicher neuer Auftrag: EIN vollständiges GSZ-ZENTRALE-KOMPLETT-STRATO.zip, keine Patches. Aktuellen Code erhalten; Abfall360 nicht neu entwickeln. Zuerst START-HIER.md, TESTSTATUS-FINAL.md, MODULE-FINAL.md, CHANGELOG-FINAL.md lesen.

Implementiert: unabhängiges geschütztes Browser-Setup, privater Zufallscode statt universellem Schlüssel, genaue Schemaerkennung/gesicherte Migration, verschlüsselte verwaltete Konfiguration, individuelle Adminanlage ohne Bestandsreset, gebündelter IMAP-Fallback, grafische Dienste-/Bankeinrichtung und optionale lesende Anbieterautorisierung. Aktueller tatsächlicher Teststand steht ausschließlich TESTSTATUS-FINAL.md. Alle produktiven Konfigurations-, Bank-, Mail- und Hostingzugänge fehlen weiter. Keine Produktivänderung/echte Mail/Bankaktionen/Kosten ohne Freigabe.

Tests:33/33 alte Suiten,9Migrationsfälle568Prüfungen, Setup-Konfig22,Mail33+TLS7,Bank73/Browser36 und Setup-Browser40frisch/40bestehend bestanden. Finales Vollpaket aus ZIP frisch installiert:40Browserprüfungen bestanden, einschließlich Wartungsmodus/defekter Konfiguration/leerem Umgebungswert. Backup/Restore85Tabellen und privater verschlüsselter Konfiguration bestanden. Keine config.php/private/server-config/runtime-setup/secrets/uploads/Logs/Dumps/Backups in Artefakte. Codeintegrität COMPLETE370 enthält Vendor und muss nach jeder Codeänderung regeneriert werden. Alte Fortsetzungseinträge unten sind Historie.

Nächster Schritt nach Paketprüfung: geschütztes STRATO-Staging mit echtem PHP-HTTPS-Test, realen Anschriften/Behältern, Postfach-/Bankanbieterfreigaben und Cronnachweis. Interne Serverursache nicht aus HTTP-Antwort oder Tabellenzahl erfinden. Bei Wiederaufnahme aktuelle Dateien/Reports prüfen statt alte Phasen erneut umzusetzen.

---
# Aktuell – Abfall360

Neuster Auftrag implementiert und lokal geprüft:Verbindliche Zahlen siehe TESTSTATUS-ABFALL360.md (93DB-Prüfungen,50neue Browserprüfungen). Schema360/84Tabellen. Basissicherung350 vorhanden; Installation erst geschützt testen. Quellen beider Kommunen tatsächlich abgerufen, nativer STRATO-Transport/Cron und echte Bestandsadressfreigaben noch offen. Weiterarbeiten nach CODEX_FORTSETZUNG.md.

# Gesicherter FINAL-Checkpoint 12.11.2026

Vollpaket GSZ-Zentrale-FINAL-STRATO.zip und exakter340-Patch GSZ-Zentrale-FINAL-UPDATE.zip erstellt. Code und Datenmodell350/82 lokal getestet, entpackte Installation/Upgrade/PDF/Integrität bestanden.318Dateien vollständig,79Update-Dateien,165Code-/Assethashes. LaufendeTests abgeschlossen; Wiederaufnahme nach CODEX_FORTSETZUNG.md ohne Neuentwicklung. Nächster Schritt ist echte geschützteSTRATO-Abnahme, kein weiterer Großmodulbau. Keine produktiven Daten verändert oder externen Kontenaktionen ausgeführt.

---

# Aktueller FINAL-Stand 12.11.2026 / Schema350

Maßgeblich: INSTALLATION-STRATO.md, TESTSTATUS-FINAL.md, MODULE-STATUS.md, CHANGELOG-FINAL.md, INTEGRATIONEN-FINAL.md und CODEX_FORTSETZUNG.md. Neu: täglicheRegeln, integriertesDashboard/Navigation, CAMT/Kontentrennung/Review, OfflineSEO, geschütztesBestandsupdate/Dateiintegrität. Schema350/82Tabellen; keineProduktivinstallation, echteMail/Bank/Cron/Objektkalender weiter nichtnachgewiesen. Aussagenzu330/340nachfolgend sindHistorie. LiveSEO-/GSC-/GBP-Adapter,OCRundvalidierteE-Rechnung nichtalsfertigausgeben.

---

# Fortschritt11.11.2026 – Version1.0 in lokaler Abnahme

Neuester gesicherter Quellstand wird aus v331 weitergeführt, Zielschema340/78 Tabellen. Implementierte Pakete und echte Grenzen: V1_BESTAND.md, TESTSTATUS.md, CODEX_FORTSETZUNG.md. Kernplanung, Abschlussintervalle, Konfigurationsschutz, Kalender-Review/Synchronisationsadapter, SMTP465/BCC und gemeinsame Oberfläche sind umgesetzt. Lokale Abschlussprüfungen bestanden:30bestehende PHP-/DB-Testreihen,46/21/28neue Assertions,23TLS-Szenarien,48/218/10neue und139bestehende Browserprüfungen. Restore78Tabellen/87Dateien und entpackter Installations-Smoke bestanden. Vollpaket GSZ-Zentrale-v1.0-STRATO-Release.zip und minimaler Patch GSZ-Zentrale-v1.0-Update-ab-v331.zip mit Manifest/Prüfsummen gesichert. PAKETPRUEFUNG_V1.md dokumentiert die Bereinigung. Keine STRATO-Installation oder echte Mail nachgewiesen. Historie folgt unverändert.

---

# Release331: erste Inbetriebnahme ab v330 vorbereitet

11.11.2026: v330-Vollcheckpoint/Update konsistent geprüft, gesichert, isolierte Arbeitskopie /workspace/gsz-inbetriebnahme/zgs-app. Keine weiteren Großmodule. Installer, CLI-/authentifizierter Websystemcheck, Tagesplan-Schedulerknopf, Regel-PRG, Dokument-/Fotodateinamenfix, mobile Eingaben/Schaltflächen und verständliche Überlastmeldung ergänzt. Keine neue SQL-Migration, alle Module und Identitäten erhalten. Kern-Praxistest48/Smoke9/Installations-DB29, bestehende Regressionen und DB-/Dateirestore bestanden. Vollständiger Installationsstand und v330-Patch werden bereinigt/hashgeprüft geliefert. Nächster Schritt tatsächlicher Hostingtest nach GO_LIVE_STATUS.md, keine Produktion ausgeführt.

---

# Aktueller Checkpoint v330 – Immobilien und Nebenkosten integriert

10.11.2026: v300 erhalten und gesichert; v310-Paket separat abgelegt; jetzt v320-Abrechnung und v330-Mail-Recovery integriert. Gebäude/Einheiten/Mieter/Verträge, tatsächliche Zahlungen, Kaution, zwei private Volksbank-CSV-Konten, Originalablage, private Auswertungen/Exporte und Nebenkosten mit Kostenbelegen/Umlagen/Ist-Vorauszahlungen/Mieterwechsel/Leerstand/Messdienstwerten/CO2-Entscheidung/Versionen/PDF/Eigentümerarchiv implementiert. Keine vorhandenen Geschäfts-/Sparkassendaten zur Immobilienbuchhaltung umgenutzt.

Nachweis: Immobilien-DB42, Abrechnungs-DB32, Rechenkern186, Immobilien-Browser27 und Abrechnungs-Browser17 bestanden. Zusätzliche Owner-Exportprüfung4, bestehender Gesamtbrowser44; DB-/Dateirestore und Regression detailliert TESTSTATUS.md. Keine rechtliche/steuerliche/STRATO-Abnahme. Volksbank nur Offline-CSV, ista nur neutrales geprüftes Werteformat, keine echte Schnittstelle. Aktuelle interne Restarbeiten und konkrete nächste Schritte CODEX_FORTSETZUNG.md. Ältere Aussagen „Immobilien fehlen“/„Pause“ gelten ausschließlich historisch.

---

## Neue Priorität umgesetzt: Immobilien-Grundlage v310, Mail v330

Eigene Gebäude/Wohnungen/Mieter/Verträge/Mietforderungen/Zahlungen und genau zwei getrennte Volksbank-Importslots integriert. Tests und Grenzen TESTBERICHT_IMMOBILIEN.md. Mail-Recovery parallel geprüft TESTBERICHT_MAIL_RECOVERY.md. Nächste Arbeit v320 integriert den bereits mathematisch geprüften Nebenkostenkern mit tatsächlichen Vorauszahlungen, Messdienstwerten, Dokument-/PDF-Snapshots und expliziter Freigabe. Keine echte Bank-/Mail-/Produktivaktion.

## Geprüfter Checkpoint v300 + PHPMailer/Vault –10.11.2026

Gesamtprüfung nachgeholt: TESTSTATUS.md. PHPMailer-SMTP und optionaler verschlüsselter Secret-Vault implementiert, integriert, lokal getestet; keine neue SQL-Migration nötig. Offizielle Bibliotheksmanifeste/Lizenzen geprüft und dokumentiert: OPEN_SOURCE_PRUEFUNG.md. Laufkosten KOSTEN.md, derzeit keine neuen Dienste. Gesamtprojekt weiterhin offen, kein Final-/Produktivreifestatus. Nächster Schritt laut NAECHSTE_SCHRITTE.md; vorhandene Implementierungen nicht duplizieren.

## Wiederaufnahme – neuer autonomer Auftrag10.11.2026

Pause aufgehoben durch neue Nutzernachricht. v300-Stand zuvor versioniert gesichert. Maßgebliche Fortsetzung ab jetzt AGENTS.md / CODEX_FORTSETZUNG.md / NAECHSTE_SCHRITTE.md / TESTSTATUS.md / ENTSCHEIDUNGEN.md. Zuerst offene kombinierte Abnahme nachholen, dann vorhandene kostenlose Bibliotheken prüfen und nächste Lücke implementieren. Frühere Pausen-/Stopphinweise unten sind Historie.

## PAUSE AUF NUTZERWUNSCH – 10.11.2026

Aktueller Stand bis v300 gesichert, nicht vollständig abgenommen. Neuer Plan angekündigt; keine automatische Weiterarbeit. Maßgeblich CODEX_PROGRESS.md, CODEX_TODO.md, CODEX_CONTINUE.md und TESTBERICHT_SICHERUNGSSTAND.md. Ältere Fortsetzungsanweisungen unten sind aufgehoben bis zur neuen Nachricht.

## Neuer priorisierter Auftrag – P1 v270 am 10.11.2026

Aktueller Wiedereinstieg ab jetzt CODEX_PROGRESS.md / CODEX_TODO.md / CODEX_CONTINUE.md. P1-Betriebsworkflow ergänzt; tatsächliche Tests TESTBERICHT_P1_BETRIEB.md. Der ältere Hinweis „als Nächstes Mail“ ist durch die neue Priorität P1→P2→P3 ersetzt. Immobilien P5/P6 noch nicht implementiert und nachrangig. Gesamtprojekt weiterhin nicht vollständig abgeschlossen/produktivreif.

## Fortschreibung 10.11.2026 nach Wiederaufnahme

Dateistand zuerst gegen Original-Phase-3-ZIP geprüft: 69 neue/geänderte Dateien, davon 48 neu, vor der Fortsetzung. Die erhaltenen mtime lagen am 09.11.; ein exaktes 66-Minuten-Fenster vor Sitzungsunterbrechung war daraus nicht beweisbar. Sicherung GSZ-Zentrale-Zwischenstand-2026-11-10.zip erstellt, inkl. Dateiliste/Hashes; Uploads/Secrets ausgeschlossen. Danach ausschließlich offene Arbeiten fortgesetzt.

Neu implementiert: additive v260 und `private/invoice_archive.php`; neue Angebots-/Rechnungs-/Stornobelegausstellungen archivieren genau ihre PDF-Bytes innerhalb der Nummern-/Ausstellungstransaktion. Downloads, Versandanhänge und Steuerberaterpaket verwenden die gespeicherten Bytes; SHA-256-/Größenprüfung schlägt bei Manipulation/Fehlen fehl statt neu zu erzeugen. Historische Dokumente nicht nachträglich verändert oder als vermeintliche Originale befüllt. Unreferenzierte Dateien nach Transaktionsabbruch werden nicht automatisch gelöscht. Kein WORM-/GoBD-Nachweis.

Lokal erneut geprüft: Mail34, Finanz25, Dokumente jetzt26 Assertions (vorher14), acht Unit-Suiten und Syntax82 PHP-Dateien. Neue reproduzierbare Browserfixture `tests/prepare_gesamt_browser.php` geprüft. Browserprüfung um zwei PDF-Downloadprüfungen erweitert: 44 Funktions- und 12 Sicherheitsprüfungen bestanden. Abschließende Browser-/Paketresultate stehen im Test-/Paketbericht. Gesamtauftrag weiterhin offen; nächster Schritt bleibt Mail-Draft-/Sent-Synchronisierung und kontrollierter nativer Transporttest. Produktionsflags bleiben gesperrt.

# Aktueller Wiedereinstieg – Gesamtstand ab Phase 3

Stand 09.11.2026. Aktives Projekt: `/workspace/gsz-gesamt/zgs-app`. Verbindliche Basis ausschließlich GSZ-Phase03.zip, SHA-256 `139d1a4d291f5c55bae3d7a151e7ca53cce9e0dd9e2decffd21df1629383e815`. Die älteren Einträge unten sind Historie und keine aktuellen Arbeitsanweisungen.

**Ergebnis: umfangreicher implementierter und lokal getesteter Zwischenstand, Gesamtauftrag noch nicht vollständig erledigt. Keine Produktivfreigabe, kein Deployment, kein Kontakt zu gszintern.de, kein echter Mailversand, keine Bankaktion.**

Abgeschlossen im hier nachgewiesenen Umfang:

- A: Bestand/IST-SOLL geprüft; additive Migration v230/v240/v250/v260, Konfigurationsflags, lokale Sicherheitsbasis, wiederholte Migration und Upgrade-/Restoreprüfung.
- B: Dashboard mit Aufgaben/Terminen/Prognose, Mail-/Finanz-/Quellenhinweisen und Objektintegration; Phase-1–3-Funktionen erhalten. Neue Seedwerte Fenster/Tiefgarage 90 Minuten; vorhandene Daten werden nicht umgeschrieben.
- C: vorhandener Kalenderimport, Sonderregel und Offline-Routing erhalten und regressionsgeprüft; keine Livequelle oder Routingkosten aktiviert.
- D: lokaler klassischer Ordnercache, Suche/Paginierung, Lesen/Anhänge, Regeln/24h, Zuordnungen, lokale Entwürfe, Antwort/Allen antworten/Weiterleitung/CC, TLS-Adapter und sichere Versandzustände mit Mocks geprüft. Kein vollständiger abschließend getesteter Live-Mailclient.
- E: neutraler CSV-Import mit expliziter Feldzuordnung, Vorschau/Konflikten/Idempotenz, read-only-Adaptergrenze und manueller Zuordnung. Sparkasse nicht verbunden, CAMT/MT940 nicht umgesetzt.
- F: Belegoriginale/Entwürfe/Korrekturketten, Centbeträge, Zahlungen/Teilzahlungen/Erstattungen, Arbeits-EÜR, Prüflisten-/Originalexport. Angebote und idempotente Übernahme, technischer Vollstornobeleg, Unicode-/Mehrseiten-PDF. Nicht fachlich vollständig und kein freigegebener Lexware-Ersatz; Ausstellung/unvalidiertes XML standardmäßig gesperrt.
- G/Sicherheit: begrenzter CLI-Cron, Loginbremse, Sessionlimits, Auditkette, geschützte Downloads, Wartungsmodus; mobile und Desktop-Prüfungen. Kein vollständiger Penetrations-/Last-/Datenschutz-/Hostingnachweis.

Prüfungen und tatsächliche Befehle: TESTBERICHT_GESAMT.md. Migration: `php private/migrate_gesamt.php` erst auf vollständiger geprüfter v220-Kopie, niemals alte Migrationen blind wiederholen. Installation/Rollback: INSTALLATION_STRATO.md. Alle Datenbanken und Browserdaten waren synthetisch und lokal; Testwerkzeuge unter `/workspace/gsz-tools` gehören nicht zum Lieferpaket.

**Nächste konkrete Arbeit:** TESTBERICHT_GESAMT.md reproduzieren; danach kontrollierten lokalen IMAP-/SMTP-Testserver einrichten und echte Adapter-/TLS-/Ordnerfehler testen, wiederaufnehmbare serverseitige Entwurfs-/Gesendet-Synchronisierung ergänzen. Anschließend Sammelumsatzaufteilung und steuerliche Fallmatrix abarbeiten. Weitere interne Codearbeiten stehen in ENTWICKLUNGSREST.md, ausschließlich externe Entscheidungen in OFFENE_PUNKTE.md. Secrets niemals aus den isolierten Altarchiven übernehmen. Konfiguration/Uploads im Staging erhalten.

---

# GSZ – Wiedereinstieg

Stand 09.11.2026. Arbeitsprojekt: /workspace/gsz-work/zgs-app. Original isoliert /workspace/gsz-original/zgs-app; dort config.php und geheime ZIP-Backups, NIEMALS ausliefern oder ausführen. Übergabe nur bereinigtes Paket. Keine Produktion verändert oder kontaktiert. Produktionsänderungen benötigen weiterhin ausdrückliche Freigabe.

Masterauftrag vollständig gelesen. Nutzer hat Lippstädter Straße 5 als Rheda-Wiedenbrück bestätigt. Keine offenen blockierenden Rückfragen.

Phase 0: Bestand, Quellmodule, README/Installation/AUDIT und sämtliche SQL-Dateien analysiert; IST/SOLL in IMPLEMENTIERUNGSPLAN.md. Geheimnisse erkannt und ausgeschlossen, lokale PHP-/MariaDB-Umgebung, ursprüngliche Tests ausgeführt, DB-Backup/Restore getestet, Sicherheitsgrenzen dokumentiert. Vollständige Security-/Hostingabnahme und echte Produktivstruktur fehlen weiter.

Phase 1: additive Migration v200, sieben Objekte/28 Regeln als bestätigbarer Import, konservative Bestandszuordnung, Konfliktabbruch, wiederholbarer Import, editierbare Dauer/Rhythmus/Start/Saison/Quelle, separate Gelbregel, unbestätigte Routinen pausiert, bestehender Scheduler erweitert, manuelle Dubletten verhindert. Änderungen lokal getestet, Details TESTBERICHT_PHASE01.md. Nicht produktiv installiert/freigegeben.

Nächste Sitzung zuerst IMPLEMENTIERUNGSPLAN.md, INSTALLATION_PHASE1.md, TESTBERICHT_PHASE01.md und CHANGELOG.md lesen. Keine alten Migrationen erneut ausführen. Bei neuer Umgebung ZIP nur ohne Secrets entpacken und lokale Tests gemäß Anleitung neu einrichten. Lokale Testwerkzeuge unter /workspace/gsz-tools sind NICHT Teil des Pakets und müssen gegebenenfalls neu installiert werden.

Danach Phase 2: vorhandene planner.php weiterverwenden, Heute zuerst; 06:30–09:30 Universität variabel, weitere Einsätze ab 10:30; 16:00 Ziel, 17:00 Wunschgrenze, Überläufe sichtbar. Tagesstart/-ziel ohne erfundene Privatadresse, Termine blockieren Zeitfenster, flexible Priorisierung/Dauer/Ortsnähe ohne erfundene Fahrtzeiten. Erledigt mit einem Tipp, optionale Notiz/Foto, spontane Einsätze, flexible unerledigte Aufgaben verschieben, manuelle Reihenfolge erhalten. Realitätstests mit Beispielwoche einschließlich Freitag/Sonntag. Keine Karten-API vor Quellen-/Kostenprüfung und Freigabe.

Später Phase 3 Kalender/Routing (Tonnenticker dokumentierte Exporte prüfen, echte Fraktion/Datum, Vortag raus/Abfuhrtag rein, Feiertage, Syncfehler sichtbar); Phase 4 vollständiger klassischer Mailclient auf Testkonto, Versandbestätigung; Phase 5 Belegoriginale, manuelle Bankimporte, Vorschlagsmatching, EÜR/Exporte fachlich prüfen; Phase 6 Sicherheit, mobile Regression, Betriebs-/Restoreprüfung, Rollout nur nach Freigabe. Keine automatische Steuerbuchung oder Banküberweisungen.

## Phase 2 – 09.11.2026

Verbindlicher Ausgangspunkt war GSZ-Phase01.zip, nicht das Originalarchiv. Aktuelle Arbeitskopie /workspace/gsz-phase02/zgs-app. Implementiert: additive wiederanlaufbare Migration v210, isolierter deterministischer Planungskern, persistierte bestätigbare Vorschläge mit Bestandshash, virtuelle Wiederholungen ohne Geschäftsdatenänderung bis Übernahme, Hauptansicht Heute/7-Tage-Woche, Fixtermine, spontane Aufgaben, Notizen, Erledigen/Wiederöffnen, manuelle Sperren, Fälligkeitsfenster, Tagesstartabweichung und geschätzte Fahrtpuffer. Phase-1-Regeln unverändert; vorhandene Dauern nicht überschrieben.

Lokale Tests siehe TESTBERICHT_PHASE02.md. Auslieferung GSZ-Phase02.zip (gesamt) und GSZ-Phase02-Update.zip (nur geänderte/neue Dateien gegen Phase01; Liste/Hashes UPDATE_DATEIEN.md). Vor nächster Sitzung diese Berichte, ARCHITEKTUR_PLANUNG.md und INSTALLATION_PHASE2.md lesen. Migration v210 nur nach Schemaabgleich auf separater Strukturkopie; Produktion unverändert und weiterhin gesperrt ohne Freigabe.

Nächster fachlicher Schritt Phase 3: dokumentierte Kalenderquellen prüfen und transparente Import-/Synchronisationslogik, danach Kosten-/Routingvergleich. Vor Weiterentwicklung Planungstests beibehalten. Offene technische Grenzen (insbesondere 5000-Aufgabenlimit, parallele Last, Vorschlagsaufbewahrung und STRATO-Matrix) dokumentiert; keine Produktionsreife behaupten.

## Phase 3 – 09.11.2026

Verbindlich aus GSZ-Phase02.zip in /workspace/gsz-phase03/zgs-app weiterentwickelt. PHASE3_BESTAND.md dokumentiert tatsächliches Schema und Adresskonflikt Lippstädter Straße 5 (Phase-1-Ort Rheda-Wiedenbrück beibehalten). Quellenprüfung belegt persönliche Kalenderintegration, keine dokumentierte freie Backend-API. Öffentliche kommunale Kalender-/OSRM-/Valhalla-Seiten nur lesend geprüft.

Implementiert: v220-Migration/Runner, Offline-ICS/CSV-Parser und explizite Fraktionszuordnung, Quellen/Originaltermine/Hashes/Status, persistierte Importvorschau, Konflikte und bewusste Fachfreigabe, Vortag/Abfuhrtag-Aufgaben, explizite Besuchsbündelung, Wiederverwendung Fontänenstraße-special_weekly, Müllkalender und Dashboard-/Tageswidget. Normalroutinen unverändert pausiert. Quellenänderungen löschen/verschieben keine Aufgaben.

Routing: isolierte Provider-Schnittstelle, deaktivierter dedizierter HTTPS-OSRM-Matrixadapter, manuell geprüfte Koordinaten, begrenzter Cache/Budget, vollständiger Offline-Fallback, deterministische Gruppierung zwischen Schutzankern, Start-/Zieladressen ohne Vorgaben, echte Test-Fahrabschnitte inkl. Terminreserve, Vorschau/Differenz/Bestätigung und aktueller Routendatenstand in Tagesansicht. Keine bezahlte API/Kontoeinrichtung, kein externer Routingtest. Kein Standorttracking.

Lokale Ergebnisse und Grenzen TESTBERICHT_PHASE03.md, Betrieb INSTALLATION_PHASE3.md, Architektur und Quellen ARCHITEKTUR_ABFALL_ROUTING.md. Auslieferung GSZ-Phase03.zip und GSZ-Phase03-Update.zip; exaktes Manifest UPDATE_DATEIEN_PHASE3.md. Keine Produktion verändert. Vor Deployment weiterhin ausdrückliche Freigabe und Prüfung tatsächlicher STRATO-/Produktivstruktur erforderlich.

Nächste Sitzung diese vier Phase-3-Dokumente lesen, Tests gegen frische lokale Fixtures reproduzieren. Fachlich anschließend Phase 4 (vollständiger Mailclient ausschließlich auf Testkonto, keine echte Mail ohne Freigabe). Echte Kalender-/Routingquellen dürfen später nach verifizierten Bedingungen eingerichtet werden. Offen: Mengenlimits/Paginierung, Quellensync, begrenzter ICS-Umfang, Retention von Import-/Routenvorschlägen, Last-/Paralleltests und Hostingabnahme.
