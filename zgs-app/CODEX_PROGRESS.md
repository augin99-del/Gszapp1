# Aktuell: Komplett370 –13.11.2026

Arbeitskopie /workspace/gsz-inbetriebnahme/zgs-app, Ziel370/85Tabellen. Verbindlicher neuer Auftrag: EIN vollständiges GSZ-ZENTRALE-KOMPLETT-STRATO.zip, keine Patches. Aktuellen Code erhalten; Abfall360 nicht neu entwickeln. Zuerst START-HIER.md, TESTSTATUS-FINAL.md, MODULE-FINAL.md, CHANGELOG-FINAL.md lesen.

Implementiert: unabhängiges geschütztes Browser-Setup, privater Zufallscode statt universellem Schlüssel, genaue Schemaerkennung/gesicherte Migration, verschlüsselte verwaltete Konfiguration, individuelle Adminanlage ohne Bestandsreset, gebündelter IMAP-Fallback, grafische Dienste-/Bankeinrichtung und optionale lesende Anbieterautorisierung. Aktueller tatsächlicher Teststand steht ausschließlich TESTSTATUS-FINAL.md. Alle produktiven Konfigurations-, Bank-, Mail- und Hostingzugänge fehlen weiter. Keine Produktivänderung/echte Mail/Bankaktionen/Kosten ohne Freigabe.

Tests:33/33 alte Suiten,9Migrationsfälle568Prüfungen, Setup-Konfig22,Mail33+TLS7,Bank73/Browser36 und Setup-Browser40frisch/40bestehend bestanden. Finales Vollpaket aus ZIP frisch installiert:40Browserprüfungen bestanden, einschließlich Wartungsmodus/defekter Konfiguration/leerem Umgebungswert. Backup/Restore85Tabellen und privater verschlüsselter Konfiguration bestanden. Keine config.php/private/server-config/runtime-setup/secrets/uploads/Logs/Dumps/Backups in Artefakte. Codeintegrität COMPLETE370 enthält Vendor und muss nach jeder Codeänderung regeneriert werden. Alte Fortsetzungseinträge unten sind Historie.

Nächster Schritt nach Paketprüfung: geschütztes STRATO-Staging mit echtem PHP-HTTPS-Test, realen Anschriften/Behältern, Postfach-/Bankanbieterfreigaben und Cronnachweis. Interne Serverursache nicht aus HTTP-Antwort oder Tabellenzahl erfinden. Bei Wiederaufnahme aktuelle Dateien/Reports prüfen statt alte Phasen erneut umzusetzen.

---
# Aktueller gesicherter Stand: Abfall360,12.11.2026

Maßgeblich sind CODEX_FORTSETZUNG.md, NAECHSTE_SCHRITTE.md und TESTSTATUS-ABFALL360.md. Schema360/84Tabellen,93neue DB-Prüfungen/50neue Browserprüfungen sowie Altregressionen bestanden. Voll-/Updatecheckpoint GSZ-Zentrale-Abfall360-STRATO.zip und GSZ-Zentrale-Abfall360-UPDATE-ab-350.zip mit externen Prüfsummen. Nächster Schritt geschützter STRATO-Test, tatsächliche Objekt-/Behälterfreigaben und Cronnachweis; keine produktiven Änderungen ohne gesonderte Freigabe. Folgende ältere Aufgaben/Arbeitsstände sind Historie.

---

# Aktuelle Fortsetzung FINAL350

Siehe CODEX_FORTSETZUNG.md und TESTSTATUS-FINAL.md. Keine ältereCodebasiswiederaufsetzen. Nachfolgend ältereHistorie.

---

# Aktueller Wiedereinstieg Version1.0

Diese Datei enthält ältere Historie. Maßgeblich: CODEX_FORTSETZUNG.md, TESTSTATUS.md, OFFENE_PUNKTE.md und DEPLOYMENT.md. Neueste Basis: lokal geprüfter Version1.0-Kandidat aus v331, Schema340/78Tabellen; keine produktive Installation, Mail-/Hosting-Abnahme noch offen. Nicht zurück auf ältere Codebasis wechseln.

---

# Neuer Vorrang Release331 – nur reale Kerninbetriebnahme

Maßgeblich GO_LIVE_STATUS.md und CODEX_FORTSETZUNG.md in /workspace/gsz-inbetriebnahme/zgs-app. Kern-/Installations-/Regressionstests bestanden; nächste Arbeit ist tatsächlicher gesicherter Hostingtest einschließlich iPhone/Safari und Hostingrestore. Fehlende STRATO-Tarif-/CLI-/Testbereichsdaten konkret klären, keine Passwörter im Chat. Keine weitere Immobilien-/Bank-/Mail-Großentwicklung und keine produktiven Aktionen ohne separate Freigabe. Ältere Implementierungsprioritäten darunter sind historisch.

---

# Aktueller Vorrang nach Immobilien-Checkpoint v330

v310 Immobilien und v320 integrierte Nebenkosten sind jetzt implementiert/lokal getestet; nicht erneut schreiben. v330 Mail-Recovery ebenfalls integriert. Neuester Wiedereinstieg ausschließlich CODEX_FORTSETZUNG.md, Testbelege TESTSTATUS.md/TESTBERICHT_NEBENKOSTEN.md. Nächste Arbeit: private Abrechnungssalden/Zahlungszuordnung ohne doppelte Sollvorauszahlungen, append-only Ausgaben-Korrekturworkflow, zeitliche Vertragsnachträge; anschließend unabhängige CAMT-/Mail-/Dashboardarbeiten. Frühere fehlende Immobilien/Pausen sind historische Momentaufnahmen. Keine Produktion, echten Bank-/Mailaktionen oder Kosten ohne gesonderte Freigabe.

---

# Aktueller Stand nach Wiederaufnahme –10.11.2026

Neuer autonomer Auftrag hebt die frühere Pause auf. Vor Änderung Checkpoint GSZ-Checkpoint-v300-vor-Neustart.zip erstellt. Neueste maßgebliche Dokumente AGENTS.md, CODEX_FORTSETZUNG.md, NAECHSTE_SCHRITTE.md, TESTSTATUS.md, ENTSCHEIDUNGEN.md. Alte Stopphinweise unten sind Historie.

Offene Gesamtintegration bis v300 jetzt mit allen PHP-Logiktests, zwölf DB-/Upgrade-Suiten und Browser22/44/29 sowie Restore54 Tabellen nachgeprüft. Details TESTSTATUS.md. PHPMailer-SMTP7.2.0 LGPL-Komponente integriert/16 TLS-Szenarien mit nativem Adapter gemeinsam getestet; optionale libsodium-Konfiguration samt14 Tests/CLI ergänzt. Kein externer Versand/Kontozugriff/Deployment, keine laufenden Zusatzkosten.

Gesamtprojekt nicht vollständig abgeschlossen: Bankformate/CAMT, vollständiger IMAP-/Entwurfs-/Gesendet-Abgleich, Steuerfallmatrix/Anlagenklassifikation weiter offen; Immobilien-/Volksbank-/Nebenkostenmodule noch nicht begonnen. Keine Endlieferung/Produktivfreigabe behaupten. Nächste konkrete Arbeit: Genkgo-CAMT-Abhängigkeiten/Tests in isolierter Umgebung prüfen, dann geprüften Importadapter oder danach unabhängige Mail-/Immobiliengrundlage entwickeln. Vorhandene CSV-/Planungs-/Belegfunktionen behalten.

--- Historie ---

# PAUSIERT AUF NUTZERWUNSCH – 10.11.2026

Der Nutzer hat die Weiterentwicklung gestoppt: „Sichere den jetzigen Stand ab und warte auf die nächste Nachricht wir haben einen neuen Plan“. Keine automatische Wiederaufnahme und kein eigenständiges Weiterarbeiten nach dieser Sicherung. Auf neuen Plan warten. Parallelbearbeitung angehalten.

Gesichert ist die aktuelle Arbeitskopie /workspace/gsz-gesamt/zgs-app, fortentwickelt aus exakt GSZ-Phase03.zip. Jüngste Migration im Runner v300; Reihenfolge230/240/250/260/270/280/290/300. Keine Produktion verändert, keine echten Mails, keine Bank-/kostenpflichtigen API-Aktionen.

Seit letzter Lieferung implementiert:

- v270: Kunden bearbeiten/archivieren, Objekt-Kundenzuordnung, organisatorische Mitarbeiter-/Subunternehmerzuordnung ohne Konten; versionierte Aufgabenstatus/Notizen/Historie; Termine ändern/stornieren/lokal erinnern. Keine Aufgabenmutation mehr durch Dashboard-GET (POST/CSRF oder CLI-Cron).
- v280: Offene Posten mit Teilzahlungen; Zahlungserinnerungen Stufe1–3 als Vorschau→Bestätigung, keine Gebühren/automatischer Versand, archiviertes PDF und Bestandsschutz.
- v290: Wiederkehrende Rechnungen erzeugen nur unnummerierte Entwürfe; wiederkehrende Ausgaben nur Prüfaufgaben, keine Buchungen/erfundenen Belegoriginale. Monats-/Jahresanker, Herkunftsidentität, Grenzen und ausdrückliche Übernahme.
- v300: Kassenbewegungsregister verknüpft vorhandene Zahlungen ohne zweite Finanzbuchung; keine negativen historischen Bestände, append-only Korrekturen. Anlagenregister mit manuell geprüften Jahresplänen, keine automatische AfA-/Steuerbuchung. Anlagenbezogene Zahlungen in der Arbeitsauswertung ausdrücklich als steuerlich ungeklärt gewarnt.
- Native SMTP/TLS-Strecke gegen lokalen Loopback-Testserver geprüft; kein externes Postfach. IMAP-Liveprüfung weiterhin offen.

Tatsächlich bestandene Teilprüfungen: Operations Unit18/DB31/Browser29; Phasen1–3 DB39/10/21, Mail34/Finanz25/Dokument26 und Gesamtbrowser44 beim v270-Paket. Erinnerungen DB24/Browser15; Finanzwiederholungen Unit8/DB22/Browser12; SMTP8 Loopback-Szenarien; Anlagenklassifikation DB5. Kasse/Anlagen separat durch Parallelbearbeitung Unit16/DB39/Browser21 plus Zwei-Prozess-Race geprüft. Abschließender zusätzlicher Browserlauf mit CSV-Erweiterung und Gesamtregression nach Zusammenführung waren bei Stopp noch nicht abschließend protokolliert. Nicht als vollständig abgenommen ausweisen.

Der jetzige integrierte Stand ist ein Entwicklungs-Zwischenstand, keine fertige Buchhaltung/GoBD-/STRATO-Freigabe. Exakte aktuelle Grenzen und noch ausstehende Prüfungen TESTBERICHT_SICHERUNGSSTAND.md. Immobilien/Volksbank/Nebenkosten noch nicht begonnen. Frühere Weiterarbeitsempfehlungen unten sind Historie und durch die Pause aufgehoben.

---

# CODEX – aktueller Stand

10.11.2026: Neuer verbindlicher Auftrag übernommen. Fortsetzung des geprüften Gesamtstands ab GSZ-Phase03.zip, kein Neustart. Letzte sichere ZIP: GSZ-Zentrale-Gesamtstand.zip (v260). Bisherige Funktionen und Testergebnisse siehe FORTSCHRITT.md und TESTBERICHT_GESAMT.md.

Aktuell: P0/P1-Lücken anhand Code geprüft. Kunden bisher nur anlegbar, Objekt-Kundenzuordnung nach Anlage nicht editierbar; Aufgabenstatus ohne eigene Änderungshistorie/Versionsprüfung; Fixtermine nur anlegen/löschen; keine einfache Mitarbeiter-/Subunternehmerzuordnung. Planung/Abfall/Offline-Routing bereits vorhanden und regressionsgeprüft. Priorität jetzt P1, danach P2; Immobilien nicht begonnen.

Keine Produktionskontakte/echten Mails/Bankaktionen. Alles lokal/synthetisch. Fortschreibung nach jedem geprüften Paket.

## P1-Paket v270 abgeschlossen im geprüften Umfang

Implementiert: transaktionale Kundenpflege mit Rechnungsanschrift/Archivierung/Versionsschutz; editierbare Objekt-Kundenzuordnung; optionale Mitarbeiter-/Subunternehmerdatensätze ohne Benutzerportal; Aufgabenstatus/Notizen/Zuweisungen und Historie; Termine ändern/stornieren/lokal erinnern. Kein Erledigungszeitpunkt für Altaufgaben erfunden. Frühere Wiederholungsidentitäten, gesperrte/erledigte/manuell verschobene Aufgaben erhalten. Dashboard-GET erzeugt keine Aufgaben mehr: begrenzter Scheduler explizit per POST/CSRF oder CLI-Cron.

Tatsächlich geprüft: Operations-Unit18, MariaDB31, Operations-Browser29; Phasen1/2/3 DB39/10/21 sowie Mail34/Finanz25/Dokument26 bestehen. Gesamtbrowser44 und Syntax89 PHP-Dateien ebenfalls bestanden. Planung weiterhin für einen Inhaber; Mitarbeiterzuordnung ist organisatorisch, keine Mehrpersonen-Kapazitätsplanung. Externe Kalender-/Routing-/STRATOabnahme offen.

Nächstes priorisiertes Paket P2: offene Posten und bestätigbare Zahlungserinnerungen/Mahnstufen ohne automatische Gebühren oder Versand. Immobilien weiter nachrangig.
