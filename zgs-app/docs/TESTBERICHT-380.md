# Testbericht 380 (20.11.2026)

**Umgebung (lokal, Container):** Linux, PHP 8.3.6 CLI, MariaDB 10.11.14, Node 22 + Playwright/Chromium 1243, OpenSSL-Loopback-Zertifikat.
Ausschließlich synthetische Daten in leeren `gsz_test_*`-Datenbanken. **Nicht getestet:** STRATO-Hosting, echtes
Postfach, echter Volksbank-Export, echte Geräte (iPhone/iPad/MacBook). Siehe OFFENE-PUNKTE.md.

Befehle: `tests/run_php_suites.sh`, `tests/run_browser_suites.sh` (Einrichtungs-Suite über `tests/dev/run_setup_browser.sh`),
`python3 tests/test_smtp_loopback.py`, `python3 tests/test_setup_mail_loopback.py`. Syntaxprüfung aller eigenen PHP-Dateien: ohne Befund.

## Ergebnis

| Bereich | Ergebnis |
|---|---|
| PHP-/Datenbank-Suiten inkl. Quellstand-Matrix (0/192/220/320/340/360/370 → 380) | **46/46 PASS** |
| Browser-Suiten (Playwright, HTTPS-Loopback) | **20/20 PASS** |
| SMTP-TLS-Loopback (Hilfstreiber) | PASS, 23 Szenarien |
| IMAP-TLS-Loopback | PASS, 7 Szenarien |
| `test_installation_restore.py`, `test_estate_backup_restore.py` (allgemeine mariadb-dump-Rücksicherung) | **nicht ausgeführt**: benötigen eine historische Fixture mit archivierten Originaldateien. Die App-Sicherung/Rücksicherung ist in `test_final_upgrade` (370/360/340 → 380) mit identischem Ergebnis nachgewiesen. |
| `test_final_optional_dom.php` | nicht ausgeführt (verlangt PHP ohne DOM-Erweiterung) |

## Neue Suiten

| Suite | Teil | Prüft |
|---|---|---|
| `test_estate_rent_match.php` (38) | B | Volksbank-CSV (Vorspann, Windows-1252, S/H, identische Zeilen), überlappende Uploads ohne Doppelzählung, falsches Konto abgelehnt, CAMT-Autoerkennung, Monatsübersicht, Vorschläge (Name/Zahlername/IBAN), Verteilung auf Komponenten, Idempotenz, Kosten nur nach Bestätigung, Hinweis ab dem 10. |
| `test_waste_blocks.php` (27) | C | ein Block je Objekt/Tag/Richtung, Titel, Nachvollziehbarkeit je Tonne, Verschiebung/Rückverschiebung, erledigte Blöcke unverändert, Fontänen-Sonderregel, Migration der Einzelaufgaben (Notizen, Arbeitsnachweise), Wiederholbarkeit, ICS/CSV |
| `test_mail_credentials.php` (21) | D | Vault-Ablage/Rechte/Verschlüsselung, neuer Prozess (Cron) liest Zugang, Schutzschaltung für IMAP/SMTP/Cron ohne Netzwerk, Ändern hebt Sperre, Löschen, Übernahme Altpasswort |
| `test_zentrale_ui_browser.cjs` (184) | F | 390/820/1180/1440: Navigation (6 Punkte, Tab-Leiste/Seitenleiste), kein horizontales Scrollen, Objektnamen ohne Adresse, keine Kennzahlen, Tippziele ≥ 44 px, Erledigen/Verschieben mit einem Tipp, leere Zustände |
| `test_zentrale_flows_browser.cjs` (27) | D/B | Mail einmal eintragen, neuer Login ohne Eingabe, Ablehnung → klare Meldung + Weg zum Neueintragen, ändern/löschen; Miete zuordnen, Kosten bestätigen |
| `test_final_estate_camt_browser.cjs` (20) | B | zwei Hauskonten, CAMT, Kontotrennung, Ähnlichkeitsprüfung, übernommene Vorschau |
| `test_final_upgrade.php` (neu gefasst) | A | Update nur mit App-Sicherung, Passwort, Download-Bestätigung; Nicht-Finanz-Tabellen unverändert; 62 Tabellen; Rücksicherung identisch; kein Downgrade |
| `test_upgrade_gesamt.php` (neu gefasst) | A | 260 → 380, Finanztabellen weg, Rest unverändert, wiederholbar |

Angepasste Bestandssuiten: siehe Commits; entfernte Finanz-Suiten: docs/ENTFERNT-380.md.

## Einzelergebnisse PHP

```
PASS test_estate_calculation :: Estate calculation tests passed (186 assertions).
PASS test_estate_core :: Estate math tests passed (9 assertions).
PASS test_estate_concurrency :: Estate concurrency passed (two independent processes; one allocation accepted, one rejected; no over-allocation).
PASS test_estate_database :: Estate database tests passed (42 assertions).
PASS test_estate_rent_match :: Estate rent matching PASS (38 assertions; synthetic data, no real bank export).
PASS test_estate_settlement_database :: Estate settlement database tests passed (32 assertions).
PASS test_final_core :: FINAL core PASS (40 assertions; synthetic data only).
PASS test_final_database :: FINAL database PASS (33 assertions).
PASS test_final_integrity :: FINAL integrity PASS (9 assertions).
PASS test_installation_database :: Installation/system database tests passed (29 assertions).
PASS test_mail_core :: Mail/security unit tests passed (25 assertions).
PASS test_mail_credentials :: Mail credentials PASS (21 assertions; synthetic, no real mailbox contacted).
PASS test_mail_database :: Mail database tests passed (34 assertions).
PASS test_mail_recovery_database :: Mail recovery database tests passed (51 assertions).
PASS test_mail_recovery_http :: Mail recovery HTTP tests passed (16 assertions).
PASS test_mail_recovery_imap :: Mail recovery native IMAP shim tests passed (10 assertions).
PASS test_money :: Money/CSV/ZIP tests passed (17 assertions).
PASS test_operations :: Operations unit tests passed (18 assertions).
PASS test_operations_database :: Operations database tests passed (31 assertions).
PASS test_pdf_document :: PDF document tests passed (4 assertions).
PASS test_phase1_database :: MariaDB phase 1 integration passed (39 assertions; 28 seeded rules).
PASS test_phase3_database :: Phase 3 database tests passed (21 assertions).
PASS test_planning :: Planning unit tests passed (24 assertions).
PASS test_planning_database :: Planning database tests passed (10 assertions; 10 tasks).
PASS test_recurring :: Recurring/catalog/config tests passed (73 assertions).
PASS test_schema :: Schema static checks passed (not a database integration test).
PASS test_secret_vault :: Secret vault tests passed (14 assertions).
PASS test_setup_config :: Setup configuration/security PASS (22 assertions).
PASS test_setup_mail :: Setup/IMAP fallback PASS (33 assertions; synthetic protocol, no real mail sent).
PASS test_upgrade_gesamt :: Overall upgrade v260→380 passed (29 non-finance tables unchanged; finance tables removed; repeated migration).
PASS test_v1_database :: V1 database PASS (21 assertions).
PASS test_v1_integrations :: V1 integrations PASS (28 assertions; synthetic calendars only).
PASS test_v1_planning :: V1 planning PASS (46 assertions).
PASS test_waste_blocks :: Waste blocks PASS (27 assertions; synthetic data only).
PASS test_waste_connections :: Waste connections PASS (95 assertions).
PASS test_waste_routing :: Waste/routing unit tests passed (32 assertions).
PASS test_setup_schema[0] :: Setup schema PASS (21 assertions; source0 -> target380).
PASS test_setup_schema[192] :: Setup schema PASS (33 assertions; source192 -> target380).
PASS test_setup_schema[220] :: Setup schema PASS (45 assertions; source220 -> target380).
PASS test_setup_schema[320] :: Setup schema PASS (96 assertions; source320 -> target380).
PASS test_setup_schema[340] :: Setup schema PASS (98 assertions; source340 -> target380).
PASS test_final_upgrade[340] :: FINAL upgrade PASS (92 assertions; 340 -> 380; backup, rollback restore, owner and non-finance rows preserved).
PASS test_setup_schema[360] :: Setup schema PASS (104 assertions; source360 -> target380).
PASS test_final_upgrade[360] :: FINAL upgrade PASS (98 assertions; 360 -> 380; backup, rollback restore, owner and non-finance rows preserved).
PASS test_setup_schema[370] :: Setup schema PASS (105 assertions; source370 -> target380).
PASS test_final_upgrade[370] :: FINAL upgrade PASS (97 assertions; 370 -> 380; backup, rollback restore, owner and non-finance rows preserved).
SUMMARY pass=46 fail=0 
```

## Einzelergebnisse Browser

```
PASS test_browser :: Browser tests passed (8 checks, mobile and desktop).
PASS test_estate_browser :: Estate browser passed (30 checks).
PASS test_estate_export_browser :: Estate owner export browser passed (4 checks).
PASS test_estate_settlement_browser :: Estate settlement browser passed (17 checks).
PASS test_final_browser :: FINAL integrated browser PASS (25 checks; navigation/SEO/backup/system status).
PASS test_final_estate_camt_browser :: Estate CAMT browser PASS (20 checks; two separate house accounts, synthetic statements, no online banking).
PASS test_gesamt_browser :: Gesamt browser passed (16 checks; finance block removed).
PASS test_golive_browser :: Go-live practice browser passed (48 checks; today 2026-11-20; synthetic property 8).
PASS test_operations_browser :: Operations browser passed (29 checks).
PASS test_phase3_browser :: Phase 3 browser passed (17 checks).
PASS test_planning_browser :: Phase 2 browser passed (10 checks).
PASS test_security_browser :: Security browser passed (16 checks).
PASS test_setup_browser[leer] :: Setup browser PASS (41 checks; broken-config recovery, encrypted connection, explicit fresh migration, owner, completion, mobile).
PASS test_setup_browser[320] :: Setup browser PASS (41 checks; broken-config recovery, encrypted connection, explicit fresh migration, owner, completion, mobile).
PASS test_setup_browser[370] :: Setup browser PASS (41 checks; broken-config recovery, encrypted connection, explicit fresh migration, owner, completion, mobile).
PASS test_v1_browser :: V1 UI PASS (154 checks; all module palettes390/1440; completion recurrence).
PASS test_v1_calendar_browser :: V1 calendar UI PASS (10 checks; remap, pending/applied, replay, manual automation).
PASS test_waste_connections_browser :: Waste UI PASS (50 checks; synthetic cached provider; create/discover/bins/preview/confirm/tasks/CSRF/mobile).
PASS test_zentrale_flows_browser :: Zentrale flows browser PASS (27 checks; mail vault once, lockout protection, rent assignment; no real mailbox or bank).
PASS test_zentrale_ui_browser :: Zentrale UI browser PASS (184 checks; 390/820/1180/1440, synthetic data, no real device test).
SUMMARY pass=20 fail=0 
```
