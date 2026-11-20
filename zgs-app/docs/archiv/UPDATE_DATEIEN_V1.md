# Exaktes Update v331 → Version1.0-Kandidat / Schema340

Basis: GSZ-Installationsstand-v331.zip, SHA25649dd763afb6d8480ed4a42508fb7c0e457f679564d9f4b2b2669458d3fc44c21. Vollständiger Ausgangsstand separat erhalten. Kein Patch zu beliebiger älterer/abweichender Installation.

Neue Migration: private/migration_v340.sql; Upgrade-Runner überspringt protokollierte historische Schritte. Reihenfolge: verifizierte Sicherung, geschützte Testkopie, Dateien kontrolliert ersetzen/ergänzen, private Konfiguration/Originale erhalten, php private/install.php --upgrade --backup-confirmed, Systemcheck/Kern-/Mail-/Kalenderabnahme. DEPLOYMENT.md / ROLLBACK.md. Keine Löschungen.

Alle anderen SQL-Dateien sind byteidentisch zur v331-Basis. Privates config.php/.env/Uploads/Secrets/Dumps/Backups fehlen absichtlich. Noch keine vollständige Produktivabnahme.

Das Manifest selbst hat seinen Hash im externen DATEIMANIFEST_GSZ_v1.sha256 (keine zirkuläre Selbstreferenz). Patch angewendet auf exaktv331 muss den Vollpaket-Dateibestand ergeben.

| Datei | Zustand | SHA256 |
|---|---|---|
| zgs-app/AGENTS.md | geändert | 00c01faa344e1a532253f968c11d935d07a53a4ea8cbf842d834cb23ec0a5467 |
| zgs-app/ARCHITEKTUR_V1.md | neu | e4e4cd9d03271e5394a88775790f15f8f108362355d529879e9939c1b3dd25a3 |
| zgs-app/CHANGELOG.md | geändert | 22afa14095d5529bab37769191dc79ee4e763812eded4ae8805bee979d0b6fe5 |
| zgs-app/CODEX_CONTINUE.md | geändert | d60ce61909ef5abc5bf885c665b905ffd23b89669a72b889e12d955cba288cd3 |
| zgs-app/CODEX_FORTSETZUNG.md | geändert | ca76f1055d518980a744c2cded01de8c2d69ea82657df7e477d2ae4226423714 |
| zgs-app/CODEX_PROGRESS.md | geändert | cf1f9bab0a543a64d01d9db2dba68ede8135a8d4c408b989367face3ed2eac8b |
| zgs-app/CODEX_TODO.md | geändert | 9eb2fd9541bfd2adc31bcff06795955af9c692c990231f2a669ea741a19cb057 |
| zgs-app/CRONJOBS.md | neu | a708ae7a851ef33b4b6ef07c702693076391b20cd550d47e206473ae0a2a31d8 |
| zgs-app/DEPLOYMENT.md | neu | 12b4c89f984f5fa6ab60671856462b31ba5534d01e913e8295a92cefa177932f |
| zgs-app/ENTSCHEIDUNGEN.md | geändert | 54c2fcaa513e90bcffb9e7dd590513b9ab0da0bcbade52b54b2df05de8c7afe1 |
| zgs-app/FORTSCHRITT.md | geändert | 7f95619a16c53e3f1b371476db347f23364d32d81cfaf873d7bd8aad7eecb197 |
| zgs-app/GO_LIVE_STATUS.md | geändert | fe8fa0757b829906267e77863378185ed2d52412ae353d63945db5f1e09e6006 |
| zgs-app/IMPLEMENTIERUNGSPLAN.md | geändert | bbe5d21b3b9bdcd04f2443218bc4eef3149985310e2b2bb04ad10eb65d20b9b7 |
| zgs-app/INSTALLATION_KURZ_STRATO.md | geändert | 7f542a75f0f029ea82f239f947826becc685dbf7bfe08ba4fc6a22e3fd929063 |
| zgs-app/INSTALLATION_STRATO.md | geändert | 3afe2f86193eb9779d3bea520bfafa0bf478ebc7f3b425b0241f1ba8427e04ea |
| zgs-app/INTEGRATIONEN_V1.md | neu | f87780e06129df0cccec53e4797e0e569a6952f268fa8d9b9ce9856412fb15d0 |
| zgs-app/NAECHSTE_SCHRITTE.md | geändert | f7aa56204506f720d7c4735e948ca352ff3c11db47a4d142a4c07ae5f3f00356 |
| zgs-app/OFFENE_PUNKTE.md | geändert | 93f893cd8be1889bceed18a75bf6c23f0a557430e615056279108e19969ffd79 |
| zgs-app/OPEN_SOURCE_PRUEFUNG.md | geändert | 0ffaf365917d1705c2fd5433786b828eb04e94faa48280a356e0bb7c4b62fe88 |
| zgs-app/PAKETPRUEFUNG_V1.md | neu | d4a65bea5efd9bcd6b91751736aef92f56c75d60668d2f642b36fc5318921f5a |
| zgs-app/ROLLBACK.md | neu | f4caffaf912d6739047423136a44d6a9789a2d3c04333635ddd957a9d05723cc |
| zgs-app/SICHERHEIT_DATENSCHUTZ.md | geändert | c067ca502cd2baed30ff4d999888aecafa0639c0e2c3fa707e25133978a27add |
| zgs-app/TAEGLICHE_NUTZUNG_V1.md | neu | 1c483af01defba82542682709791ae2654f487450ff689f15d4d99fd99b8e504 |
| zgs-app/TESTBERICHT_V1.md | neu | 43c7ab72dd813789f33efcacfa365b823516c4e8ae633a60ac8710e25eb87973 |
| zgs-app/TESTSTATUS.md | geändert | 5124f93279b8db8ac0679ce4b36fbab6e7641c948bf86be73c53dbe5d99e7ccc |
| zgs-app/UPDATE_DATEIEN_V1.md | neu | externes Dateimanifest |
| zgs-app/V1_BESTAND.md | neu | aa339e5921fa29f2fdbf733412f5e0f3463838b6d1e2d09329baecd209d3e734 |
| zgs-app/private/automation.php | neu | b96070ad9155f13fa38a498b9de52fd84b85f0fe44288fbae239472475ffb1b0 |
| zgs-app/private/config.example.php | geändert | 862b204f728a2c6e5959adfd45d869b941492287c3df7fd7aee9413c0ed1cece |
| zgs-app/private/config_loader.php | geändert | 8fb91d39ed73956f84c0262a3a67b9a58e502e3fe6a6c0e3855396993e67a1da |
| zgs-app/private/config_security.php | neu | c3c64d0e667e2f48c61a9c3dd064a0dd3f192fd3aca9aeb244fb4aa1ced7e107 |
| zgs-app/private/cron_gesamt.php | geändert | d4ba4d28686df1694fa74fc4f3a5090975183247b451339744b4e8501e644aad |
| zgs-app/private/cron_scheduler.php | geändert | a14c7837247af7974f56627097306006615ad3b9e49da42b3afb5f8d425b48f4 |
| zgs-app/private/dashboard_service.php | geändert | 1f51b66fe910bdd13cc3f1c1524c6ef610bf0b17c36e21b1ea370be3574448ec |
| zgs-app/private/install.php | geändert | 5b736b159e259e1327876acc91bca8ab80e967288497cf3ae3ab52c32942573c |
| zgs-app/private/install_core.php | geändert | 18c654ae325e0ef40af5381ff392b95d017660c383026a17953a7dc323d483ed |
| zgs-app/private/layout.php | neu | aa518293a4b50a7210dcf7d0a66e77d0bb2c10094d38fed208be3460af664ad0 |
| zgs-app/private/mail_check_cli.php | neu | 0bbfc08aff2b66d4655bb01f58a1aeb37902b353aa70dd6e64f5f4ba1c69bf52 |
| zgs-app/private/mail_mime.php | geändert | f5f63b8e3eab63d95bdef2fc509d94fba45b8d573926c8297f55f272236ca989 |
| zgs-app/private/mail_remote.php | geändert | fec2da293762907032673e25abe4eea70d7d882eedd0f09ad183ef8ca019cdbe |
| zgs-app/private/mail_service.php | geändert | 1b745628bd2c7d0f70f4079bf0286af3d115af11c37a18d134c88a25f183c8da |
| zgs-app/private/migrate_gesamt.php | geändert | 9afafb1931beed345246af7b498de944a8977b32db2a5e6dced832e6ae1ef0ab |
| zgs-app/private/migration_v340.sql | neu | 27c5c255ab9e3b24598a008c57dbad70fe3059404a6ce5eae1c68f6ca1032a11 |
| zgs-app/private/operations.php | geändert | 4670413a881211131dceea20936df7d60bf2b8320f25cc92360235a6e2bb261f |
| zgs-app/private/planning_engine.php | geändert | 7204343473ca66a09435f8f8b3fb05fc1db7af3625bd71c8453cde8f13dc2500 |
| zgs-app/private/planning_store.php | geändert | 42eb659073b0c01edb0713809db9ebd711707e2406164121331940dc8e5b51ca |
| zgs-app/private/recurrence_policy.php | neu | 5dfd73d0581fd8f56dd39d8884f23411d1910725d1e3848e12c3ea0841fb2936 |
| zgs-app/private/recurring_rules.php | geändert | a80b9a4968219b382c02007c853e181a4a3878d47ef47cf495324e7362c32f88 |
| zgs-app/private/routing_store.php | geändert | 2244b30aa78a879792c7a49f569c31f358c0c80b896b7bc60a520cf793c39fb2 |
| zgs-app/private/scheduler.php | geändert | 759a3ad77251436c68b0a06171381cc58b0eb5c5303cae9fdac269aa1b920396 |
| zgs-app/private/schema_requirements.php | geändert | 0b4a8f2237e7cc434b3dc8bb28d6e8d83eecf36b4bc83fc2a802549289a34816 |
| zgs-app/private/secure_config.php | neu | 400ee37a531e3aa00d32f169607854a429e4f5cfc83995b7c7d16b19d4592868 |
| zgs-app/private/smtp_phpmailer.php | geändert | 9326080e7bf8503c414e2aa7817cdc6e81683df575bb04b7f1b156b5d4120aa0 |
| zgs-app/private/system_check.php | geändert | 83754d69dd1d8c7ad066fe1ee53577e4d92323ac573397b02d2e998f0796baf0 |
| zgs-app/private/waste_store.php | geändert | 3abb09fc49e0adc6d92b0f5ba381c38494f13bfc5cd760b7e9f11938629726f5 |
| zgs-app/private/waste_sync.php | neu | 1f351f21100f83b726a2361e73021d4f31892a6d31ed2489f00bdffc3ccb9c26 |
| zgs-app/private/waste_widget.php | geändert | 56474f70427732e9a6893727fa4b86a1783e94b6c587826bf2f2aaaa8da933a5 |
| zgs-app/public/app-unified.css | neu | 3a23a9b2a0e86dac12f98507b3d26493d75b1bb9ea143924c26ee3277d25df31 |
| zgs-app/public/bootstrap.php | geändert | 77dca495906df7be24d1a5c9384416e938bb0f24e9d6057eb8f6776f4a133aa4 |
| zgs-app/public/compose.php | geändert | b8da03f096ca08e229f422d1255fb784a27d62f8fbeebe558a3d876dd1137768 |
| zgs-app/public/gsz-dashboard.js | geändert | bd83e16d9cfa8a3253fa203ed4f34cd1110bc9b8eb85154a4aae028231594128 |
| zgs-app/public/index.php | geändert | 7f13854e83c82a8329fdf28873821fdd95c252731f4dbe45a641270615275b0b |
| zgs-app/public/planner.php | geändert | efc12ad91529eb347c2183f1069b4175ed90ee1806a43a4f519172d6b7131d5c |
| zgs-app/public/property.php | geändert | 156157b1caf508a8d11caa95a931d76c3d69ff3b4bedb49e640faf4bb1a2a3a1 |
| zgs-app/public/recurring.php | geändert | 1f38cf3e5cf543bdcc1eda0a1f1dcc6ed75123c48f19359a036792320a617363 |
| zgs-app/public/settings.php | neu | a1831facc55bac34acad3c3b18ede430dd2f44c8851df471e47deac682e6a346 |
| zgs-app/public/system_check.php | geändert | 430bddfae35767c71907aaddfd4667a409999e04dab007bedebf9730a8d04da8 |
| zgs-app/public/task.php | geändert | 25f7671acbb463f30a3ca5498046c203260a1f15f130419ba882f02a99a2e9e2 |
| zgs-app/public/waste.php | geändert | 20a51dca66befe7d03df816335888886f66d8495e2b92fa44dd61d6492058382 |
| zgs-app/tests/test_gesamt_browser.cjs | geändert | b4781c230fdd00e49de40cc78a43f1a443954f86b7ef7ffc3981536bacef2998 |
| zgs-app/tests/test_installation_database.php | geändert | 59e6c4742fdc7c6cef40073a86ee411f0d0166d8fb4fc58e0256b21e6ff41993 |
| zgs-app/tests/test_smtp_loopback.py | geändert | 7c38e50771940a0f9da7a3402642a1a4b950a9742b9669fb7ce2d02e0b767d1e |
| zgs-app/tests/test_smtp_transport.php | geändert | 233d646b915f68a26c60d6a9d4375d1d0b74c386b9f59bd24ba4b56bd4732acf |
| zgs-app/tests/test_v1_browser.cjs | neu | c653d5062eb129e4fe0b255e7b37500e3e3c55d443ab9d6356234b9dcab06aec |
| zgs-app/tests/test_v1_calendar_browser.cjs | neu | 940f40bd1c01280576f8f91ba3b074de7ca0d8ea66ffb60d25f9a5581e1e75b3 |
| zgs-app/tests/test_v1_database.php | neu | 9e887fe9282a6d27bcd6e9636f7706be419489a579a1b1118d9681fd40b0b14a |
| zgs-app/tests/test_v1_integrations.php | neu | 04a55028e6a0a9557cc712e5c2360175c1025fa92e94d441e65b2b9d48de581a |
| zgs-app/tests/test_v1_planning.php | neu | 12a16a0e5bd8eeceb91cd5dbf20c31ff07ac040ad16b183db44f4bbc6bcef877 |
