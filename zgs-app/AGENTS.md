# Wiedereinstieg für Entwickler/Agenten – Stand 380

Zuerst START-HIER.md, MODULE-STATUS.md, OFFENE-PUNKTE.md und docs/TESTBERICHT-380.md lesen. docs/archiv ist Historie.

- Schema 410 (390 Merkliste `quick_notes`; 400 Ferienwohnung `stay_*`; 410 Bauprojekte `project*`; 420 Anfragen/Website `leads`, `web_events`, `app_settings`, `private/leads.php`), 76 Tabellen, Release-Kennung COMPLETE380. Migrationen sind additiv und wiederholbar; 380 (`private/migrate_380.php`)
  entfernt ausschließlich die Finanztabellen. Neue Migrationen als 430 usw. mit eigenem Marker, Vertrag in
  `private/setup_schema_contracts.json` über `tests/prepare_setup_contracts.php` und `private/schema_requirements.php` über
  `tests/dev/generate_schema_requirements.php` neu erzeugen.
- Nach jeder Codeänderung `php tests/dev/build_release_manifest.php` (Dateimanifest) – sonst verweigert das Update.
- Tests: `tests/run_php_suites.sh` und `tests/run_browser_suites.sh` (lokale MariaDB, nur leere `gsz_test_*`-Datenbanken,
  synthetische Daten). Siehe tests/README.md.
- Regeln: keine echten Zugangsdaten/Bankdaten/Personendaten in Code, Tests, Logs oder Git; Fakten und Annahmen trennen;
  nichts als live/getestet ausgeben, was nicht nachweislich geprüft wurde.
- Oberfläche: ein Stylesheet `public/zentrale.css`, Rahmen `private/layout.php`, Tagesblöcke `private/day_view.php`.
