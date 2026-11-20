# Phase 3 – Testbericht, 09.11.2026

Implementiert auf GSZ-Phase02.zip. Ausschließlich lokal ausgeführt. PHP 8.4.24, MariaDB 11.8.6, Chromium/Playwright. Keine Live-Installation, kein Mailversand, keine Bankaktionen, kein Routing-/Geokodierungsdienst aktiviert. Öffentliche Informationsseiten nur lesend zur Quellen-/Lizenzprüfung abgerufen.

| Prüfung | Tatsächliches Ergebnis |
|---|---|
| Archivaufnahme | 82 Basisdateien, ZIP-Slip/Symlinks/config.php/.env/Backups ausgeschlossen |
| Finale PHP-Syntax | 53 PHP-Dateien bestanden |
| Alt-Smoke-Tests | money, invoice_pdf, schema bestanden |
| Phase-1-Regeln | 73 Assertions bestanden |
| Phase-2-Planung | 24 Assertions bestanden |
| Phase-1-MariaDB-Regression | 39 Assertions / 28 Regeln auf leerer eigener DB bestanden |
| Phase-2-MariaDB-Regression | 10 Assertions / 10 Aufgaben auf eigener DB bestanden |
| Neue Parser-/Routingtests | 32 Assertions bestanden: ganztägig, UTC/Berlin/Kalenderzeitzone, Sommerzeit/Winterzeit-Ablehnung, ungültige/mehrtägige/rekursive ICS, CSV-Zuordnung/Fehldatum/Spalten, Dateilimit/Nullbytes, keine Fraktionsratung, Jahreswechsel, Offlinewerte, deterministische Gruppierung, geschützte/erledigte Aufgaben, Fake-Distanz/-Zeit, Quelle/Stand, Routing-Fixterminreserve und Termin-Fahrabschnitte |
| Neue MariaDB-Integration | 21 Assertions bestanden: v220 zweimal, Vorschau ohne Geschäftsdatenänderung, ungeprüfter Import ohne Aufgaben, Wiederholungsimport/Token ohne Dubletten, explizite Bündelung mit allen Fraktionen, Diekstraße Sonntag aus bestätigtem Montag, geänderte UID mit Konflikt, Bestandserhalt erledigter/manueller Aufgabe, unklare Fraktion, vorhandene Gelb-Regel und nachfolgender Scheduler ohne Duplikat, pausierte Normalroutinen, veralteter Fingerprint, einmaliger gecachter Fake-Matrixabruf, Provider-Fallback |
| Neuer Browserablauf | 17 Prüfungen bestanden: Login/Schutz, separate Quelle, HTML-Upload abgewiesen, CSV-Upload mit Sonderzeichen, explizite Zuordnung/Vorschau/Freigabe, Sonntag-Aufgabe, Offline-Routenvorschau ohne km, Übernahme, Import/Route 390px und 1440px ohne horizontale Überläufe, CSRF für beide Module |
| Bestehende Browserregression | Phase-1-Test 8 Prüfungen und Phase-2-Test 11 Prüfungen erneut bestanden auf getrennten Testfixtures mit aktuellem Quellcode |
| Datenbank-Backup/Restore | Vollständiger single-transaction-Dump, Restore in separate DB, 25 Tabellen. Sortierte vollständige Dumps ohne Kommentare bytegleich, cmp Exit 0 |

Feiertagsverhalten: Parser übernimmt exakt z.B. 01.01.2027 und erzeugt rechnerisch Vortag 31.12.; kein Algorithmus erfindet Verschiebungen. Konkrete kommunale Feiertagsabfuhrpläne nicht geprüft. Quellen-/Fachprüfung bleibt Nutzerentscheidung.

Erster neuer Browserlauf scheiterte an bewusst als CSV benanntem HTML-Inhalt: MIME-Prüfung wies Datei ab. Test anschließend mit eigenständiger Assertion auf HTML-Abweisung und zulässigem Text-Sonderzeichenfall erweitert, vollständig bestanden. Kein Sicherheitsfilter für den Test gelockert.

## Nicht als Erfolg behauptet

Keine verifizierte automatisierte Tonnenticker-/abfallnavi-Anbindung oder adressspezifische reale Kalenderprüfung, kein externer OSRM-Livetest, keine reale Verkehrs-/Distanzmessung. Fake-Werte sind synthetische Testdaten. Keine STRATO-/PHP-8.2-/MariaDB-10.6-Matrix, kein Last-/Paralleltest, kein vollständiger Penetrations-/Datenschutz-/GoBD-/Steuercheck. Kein Datei-/Upload-/kompletter Hosting-Restore. Die mitgelieferte Struktur ersetzt keinen geprüften Produktivschemaexport.

Grenzen: begrenzter ICS-Einzelterminimport statt vollständiger RFC-Engine; konservative Konflikte bei Teildateien; keine automatische Verschiebung alter Aufgaben; manuelle Prüfung/Veraltet-Markierung erforderlich. Routing nur flexible Tagesreihenfolge, keine mathematische Optimierung. Reale Terminanfahrt konservativ reserviert. Bekannte Mengen-/Cache-/Aufbewahrungsgrenzen siehe ARCHITEKTUR_ABFALL_ROUTING.md. Testbefehle und Umgebungseinrichtung siehe INSTALLATION_PHASE3.md.
