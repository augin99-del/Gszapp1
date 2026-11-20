# Aktuelle Priorität: Abfall360

IST/SOLL ABFALL360_BESTAND.md, Umsetzung ARCHITEKTUR_ABFALL_AUTOMATIK.md. Laufende Arbeit: lokale Abnahme/Release abgeschlossen, nächster Schritt geschützter STRATO-Verbindungstest und bewusste Freigabe vollständiger Objektadressen/Behälter. Keine weiteren Großmodule für diesen Auftrag. Folgende vorherigen Pläne bleiben Historie.

# Aktueller FINAL-Stand 12.11.2026 / Schema350

Maßgeblich: INSTALLATION-STRATO.md, TESTSTATUS-FINAL.md, MODULE-STATUS.md, CHANGELOG-FINAL.md, INTEGRATIONEN-FINAL.md und CODEX_FORTSETZUNG.md. Neu: täglicheRegeln, integriertesDashboard/Navigation, CAMT/Kontentrennung/Review, OfflineSEO, geschütztesBestandsupdate/Dateiintegrität. Schema350/82Tabellen; keineProduktivinstallation, echteMail/Bank/Cron/Objektkalender weiter nichtnachgewiesen. Aussagenzu330/340nachfolgend sindHistorie. LiveSEO-/GSC-/GBP-Adapter,OCRundvalidierteE-Rechnung nichtalsfertigausgeben.

---

# Aktueller Umsetzungsstand Version1.0

P0 Konfigurationsschutz/Backup/Diagnostik; P1 Kapazitätswoche/Abschlussintervalle/Kalender; P2 Mail465TLS/BCC/sichere Einrichtung; P3 gemeinsamer Dashboardrahmen; P4 Cron/Regression/Release. Implementierte Dateien im v1-Update-Manifest. Externe Abnahme nach DEPLOYMENT.md offen; keine neuen Immobilien-/Banking-Großmodule. Priorisierte IST/SOLL-Matrix V1_BESTAND.md. Historische Pläne folgen.

---

# Aktueller Entwicklungsplan – Gesamtstand ab Phase 3

IST/SOLL vor Implementierung: IST_SOLL_GESAMT.md. Umsetzung und Architektur: ARCHITEKTUR_GESAMTSYSTEM.md. Verbindlicher Ausgangspunkt GSZ-Phase03.zip; keine Neubau-/Altarchivbasis.

Reihenfolge umgesetzt: A Bestandsaufnahme → Sicherheits-/Dashboardbasis → Maildienste/Mocktests → Bank-CSV/Beleg-/Zahlungsworkflow → Angebote/PDF/Arbeits-EÜR/Export → Regression und sichere Auslieferung. Neue Schemaergänzungen v230, v240, v250, v260 sind additiv, wiederanlaufbar auf geprüfter v220 und lokal auf frischer Struktur sowie Upgrade-Kopie geprüft. DDL ist nicht gesamtatomar.

Aktueller Status: umfangreicher getesteter Zwischenstand. Keine vollständige fachliche/produktive Fertigstellung. Details der umgesetzten Teilpakete in FORTSCHRITT.md, Testbefehle in TESTBERICHT_GESAMT.md. Rechnungsausstellung/Versand/XML/Live-Routing standardmäßig gesperrt, Bank/OCR nicht verbunden.

Priorisierte Fortsetzung:

1. Lokale echte IMAP-/SMTP-Adaptertests, serverseitige Entwürfe und wiederaufnehmbare Gesendet-Kopie, größere Ordner/Flag-Abgleich.
2. Sammelzuordnungen und abgestimmter Konten-/Steuerkennzeichenplan; Steuerfallmatrix statt stiller Vereinfachungen.
3. Pflichtangaben-/E-Rechnungsprüfung und Archiv-/Verfahrensdokumentation.
4. Mengen-/Parallel-/Recovery-/Penetrationsprüfung, vollständige Tastatur-/Screenreader-Abnahme; Staging auf tatsächlicher STRATO-Konfiguration.
5. Erst nach Fach-/Staging-/Restoreprüfung gesonderte Produktionsfreigabe einholen; kein automatisches Deployment.

ENTWICKLUNGSREST.md enthält alle weiteren internen Arbeiten; OFFENE_PUNKTE.md ausschließlich externe Blocker. Historischer Plan folgt, nicht als aktuelle Freigabe interpretieren.

---

# Implementierungsplan GSZ-Zentrale

Stand 09.11.2026. Grundlage: vollständig gelesener Masterauftrag und ZIP `montag.morgen.zip`. Ausgangscode: README v0.5–1.9, Installation v1.9.2, AUDIT v1.9.1/1.9.2; keine konsistente zentrale Versionsverwaltung. Kein Produktionszugriff.

## Phase 0 – festgestellter Bestand

| Bereich | IST im Quellcode | SOLL / Vorgehen |
|---|---|---|
| Objekte/Kunden | CRUD teilweise vorhanden, Objektakte mit Kontakten und Aufgaben | Wiederverwenden; sieben überprüfbare Seeds ohne Überschreiben |
| Wiederholungen | weekly/biweekly/monthly/quarterly; seasonal rein manuell; UNIQUE(regel, occurrence), Zeilensperre | Dauer, Monatsanker, Saisonfenster, Vorschauimport, editierbare Regeln ergänzen |
| Scheduler | Dashboard/Planner/CLI; maximal 24 Termine je Regel; Auswahl CURRENT_DATE trotz Parameter | Parameter verwenden, Monatsanker erhalten, Dauer übernehmen, Dublettenschutz vereinheitlichen |
| Planung | Tages-/Wochenliste, Dauer, Priorität, Uhrzeit, Reihenfolge, verschieben/erledigen | Phase 2: 10:30, 16/17 Uhr, Zeitfenster, Notiz, Überläufe, Beispielwoche |
| Tonnen/Karten | Keine Abfallkalender-/Routing-Anbindung | Phase 3: echte Quelle prüfen; normale Routinen zunächst pausieren, Sonderregel separat |
| E-Mail | IMAP read-only Ordner/Suche; SMTP Text, Rechnungs-PDF; keine lokale Synchronisation | Phase 4: Testkonto, Entwürfe, Anhänge, Gesendet, Erinnerungen |
| Rechnungen | Entwurf, Nummerntransaktion, Events, Roh-CSV, unvalidiertes XML, einseitiges PDF mit Längenabbruch | Phase 5: Belege/Bankimport zuerst, fachliche Prüfung, kein Produktivversand |
| Dokumente/Fotos | private Ablage, MIME/Größe, Zufallsnamen; Dokument-SHA256 bei Download | Weiterverwenden; Upload-/Pfad-/Restoretests und Auditprotokoll fehlen |
| Bank/EÜR/OCR | Nicht vorhanden | Phase 5, keine Bankaktionen |
| Sicherheit | HTTPS-Zwang, sichere Sessioncookies, CSRF, prepared statements, HTML escaping; sessionbasierte Loginsperre | Serverweite Ratelimits, Timeout, Berechtigungs-/Uploadtests, CSP prüfen; keine Produktionsfreigabe |
| Installation | 12 Tabellen im v192-Bundle; historische inkrementelle SQL-Dateien, kein Versionsledger; setup.php fehlt bewusst | Leere lokale MariaDB, Altbundle und additive Migration testen; Einrichtung nicht als vorhanden behaupten |

## Vertrauliche Dateien

Original isoliert unter `/workspace/gsz-original`, nicht ausliefern. `private/config.php` enthält befüllte DB-/Mail-/Setup-Konfiguration; zwei eingebettete Archive enthalten ebenfalls config.php. Werte weder ausgeben noch übernehmen. Arbeitskopie ohne config.php/ZIP; neue Konfiguration aus Umgebung. Original nicht ausführen. Bei früherer Veröffentlichung oder öffentlich erreichbaren Sicherungen betroffene Zugangsdaten rotieren. Keine Aussage, dass solche Veröffentlichung tatsächlich erfolgt ist.

## Datenbank / Grenzen

Vorhandene Migrationen: v03, v04, v05 im Basisschema enthalten; v07 Planung; v11–15 Rechnungen; v16 Arbeitsberichte; v17 Fotos; v19 Dokumente. v192-Bundle ist nur für leere Datenbanken. PK/FK-Typen unsigned int bzw. bigint konsistent in aktuellem Bundle. Kein produktiver Schemaexport oder Serverversionsnachweis vorhanden: lokale Tests prüfen die mitgelieferte Struktur, keine bestätigte Kopie der produktiven Struktur. STRATO PHP-/MariaDB-Version und Extensions vor späterem Rollout feststellen. Code verwendet PHP 8.1+ (`never`), Ziel PHP 8.2+; MariaDB 10.6+ laut Migration, tatsächlich lokal getestete Version im Testbericht.

## Phase 1 – Umsetzung

1. Additive Migration v200: stabile Importidentitäten, Dauern, Monatsanker, Saisonfenster und Regelquelle; keine Bestandsdaten löschen.
2. Vorschau-/Bestätigungsimport: gleiche normalisierte Straße/Ort erkennen, Mehrdeutigkeit oder Regelkonflikt führt zu Abbruch mit Rollback. Bestehende Daten niemals still überschreiben. Transaktion und Importsperre; wiederholbarer Import.
3. Sieben Objekte; Lippstädter Straße 5 ausdrücklich am 09.11.2026 durch Nutzer als Rheda-Wiedenbrück bestätigt. Tonnenroutine pausiert bis realem Kalenderabgleich. Fontänenstraße Gelb Mi/Do aktiv als unabhängige Sonderregel. Flexible Leistungen als editierbare Startvorschläge. Außenanlage Heiligen Häuschenweg: zwei getrennte Saisonfenster, Winterrhythmus monatlich als editierbarer Vorschlag. Winterdienst/Kleinreparatur manuell, keine Wetterdaten erfinden.
4. Bestehenden Scheduler und Regelverwaltung erweitern; automatische und manuelle Generierung nutzen dieselbe occurrence-Identität. Originaltermin bleibt auch nach Aufgabenverschiebung bestehen.
5. Syntax, Alt-Smoke-Tests, Datumstests, MariaDB Import/Migration/Idempotenz/Konflikte/Restore; lokal Browserzugriff versuchen. Jede nicht geprüfte Grenze dokumentieren.

## Nächste Phasen

Phase 2 nach Abschluss/Übergabe Phase 1: Tages-/Wochenplanung. Danach Kalender/Routing, Mail, Bank/Buchhaltung, Härtung entsprechend Masterauftrag. Keine bezahlten APIs oder Produktion ohne Freigabe. SQL-Migrationen vor Deployment zusätzlich gegen eine autorisierte, bereinigte Kopie der tatsächlichen Produktivstruktur testen.

## Phase 2 umgesetzt (lokaler Stand 09.11.2026)

Siehe ARCHITEKTUR_PLANUNG.md: Bestandsfelder wiederverwendet, additive Migration v210, Vorschau/Bestätigung getrennt, virtuelle Regeln, Heute/Woche, Termine/Sperren/Puffer und Tests. Grenzen/Abnahmen siehe TESTBERICHT_PHASE02.md. Keine Phase-1-Regeln dupliziert oder unbestätigte Tonnen aktiviert. Nächste Phase 3, kein produktiver Rollout.

## Phase 3 umgesetzt (lokal)

Siehe PHASE3_BESTAND.md, ARCHITEKTUR_ABFALL_ROUTING.md und TESTBERICHT_PHASE03.md: bestehende Planung/Herkunftsidentitäten weiterverwendet, additive v220, Offlinekalender mit ausdrücklicher Fachfreigabe, separate Routingadapter/-vorschläge. Quellenprüfung statt erfundener Tonnenticker-API. Unverifizierte Normalroutinen bleiben pausiert. Reale Objektkalender und externer Routingdienst noch nicht verifiziert. Nächste fachliche Phase 4 auf Testkonto, keine Produktionsfreigabe.
