# Phase 2 – lokale Ergebnisse, 09.11.2026

Quellstand GSZ-Phase01.zip, separate Kopie. Getestet auf PHP 8.4.24, MariaDB 11.8.6, Chromium/Playwright. Keine externe API, Produktion, Mails oder Bankaktionen.

| Test | Tatsächliches Ergebnis |
|---|---|
| PHP-Syntax | 43 PHP-Dateien bestanden |
| Bestand | Money, Invoice-PDF und statischer Schema-Test bestanden |
| Phase-1-Regeln | 73 Assertions bestanden |
| Phase-1-MariaDB-Regression | 39 Assertions, 28 Seedregeln bestanden auf frischer Datenbank |
| Neuer isolierter Planungskern | 24 Assertions bestanden: deterministische 7-Tage-Woche, alle Aufgaben einmal, 10:30, Puffer, 16/17-Warnung, Fixtermin 13–14:30, expliziter Konflikt, unbekannte Dauer, Sonntag, Datums-/DST-Grenzen, Sommerfenster/Oktober, 90-Minuten-Fallback und Bestandserhalt |
| Neue MariaDB-Integration | 10 Assertions, 10 materialisierte Aufgaben bestanden: Migration zweimal, nur lesende Vorschau bzgl. Geschäftsdaten, wiederholte Übernahme/neuer Vorschlag, anschließender Scheduler ohne Dubletten, erledigte/verschobene/manuelle Daten erhalten, ungeprüfte Tonnen ausgeschlossen, gelbe Sonderregel tagfest, veraltete Vorschau abgewiesen |
| Neuer mobiler Browser-Smoke-Test | 11 Prüfungen bestanden: Login/Anonymumleitung, Woche, Vorschau/PRG-Übernahme, spontane Aufgabe mit HTML-Zeichen sicher angezeigt, Erledigen, Tagesvorschau, CSRF 403, 390px/1440px ohne horizontale Überläufe, keine Browser-JS-Fehler |
| DB-Backup/Restore | Tatsächlicher single-transaction-Dump des erweiterten Schemas in separate lokale Datenbank zurückgespielt. Vollständige nach PK sortierte Dumps ohne Kommentare bytegleich, cmp Exit 0 |

Browserharness: externe Loopback-HTTPS-Brücke mit Testzertifikat, PHP-Entwicklungsserver nur auf Loopback, Produktions-HTTPS-Zwang unverändert. Nur synthetische Daten. Testquellen im Paket; keine Test-DB oder privaten Harnessdateien.

## Offen / Grenzen

Keine echte produktive Strukturkopie, kein STRATO-Livetest, keine zusätzliche PHP-8.2/MariaDB-10.6-Matrix, kein Parallel-/Lasttest, keine vollständige Security-/Steuer-/GoBD-Abnahme. Backup/Restore betrifft die lokale Datenbank, keinen kompletten Hosting-/Uploadbestand. Die Testfälle prüfen keine Feiertagsdaten, weil keine Kalenderquelle existiert; es findet keine Feiertagsverschiebung statt. Keine echte Routenberechnung, Fahrtzeiten fehlen. Adressgruppierung ist nur eine Heuristik. Unbekannte Dauern ergeben unvollständige Endzeit. Details zu Fenstern, Kapazitätsgrenzen und Bedienung in ARCHITEKTUR_PLANUNG.md und INSTALLATION_PHASE2.md.

Phase-1-Browserregression zusätzlich erneut ausgeführt: 8 Prüfungen auf frischen v200-Testfixtures bestanden.
