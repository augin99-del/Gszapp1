# Exaktes Update ab gesichertem v300

Basis GSZ-Checkpoint-v300-vor-Immobilien.zip, SHA-256 1ac696947696f14b888ea84818b4240aac0b8293e22f8872f0a151c70981fb6d.

Enthält ausschließlich geänderte/neue Dateien gegenüber dieser v300-Sicherung. Keine Löschungen. Migrationsreihenfolge310→320→330 innerhalb des vorhandenen geprüften Gesamt-Runners. INSTALLATION_STRATO.md lesen; keine Produktion freigegeben. Dateien/Konfiguration/Originale vor Update gemeinsam sichern.

Die zwei gegenseitig referenzierenden generierten Dateilisten haben ihre tatsächlichen Hashes im separat mitgelieferten GSZ-Zentrale-Dateimanifest.sha256; dadurch keine unmögliche Selbstreferenz. Alle übrigen Änderungen unten exakt mit SHA-256.

| Datei | Zustand | SHA-256 |
|---|---|---|
| zgs-app/AGENTS.md | geändert | e3f243ec1321a545aa4895b9b926ba3aaa7e6a6ea6f2cc7730cd2938fd7318a8 |
| zgs-app/ANLEITUNG_MEINE_IMMOBILIEN.md | neu | 166d6ea9debf296b45b5b39266e4d7c4990ba05b666c3c3eab8f7d30fa705c97 |
| zgs-app/ARCHITEKTUR_GESAMTSYSTEM.md | geändert | d215691150c4acf5f116a9c69f74fbfe8106098fdf039a3845912ea73a3f2e1a |
| zgs-app/ARCHITEKTUR_IMMOBILIEN.md | neu | 7849dfd1a8cf05f7f35bf13fd0c042d74610ecc7240eb719ac95e02269fa8216 |
| zgs-app/ARCHITEKTUR_NEBENKOSTEN_KERN.md | neu | 0ba05484344cf4dd043b38bbca758120fb6c9e460f408e0c26c4873ac23dbc44 |
| zgs-app/CHANGELOG.md | geändert | 73e780dc88607d2e648c49da402f970a95b147abeeefd342457619d9b56bff11 |
| zgs-app/CODEX_FORTSETZUNG.md | geändert | bbd67352c6cb9f959f9b0c109c0520f999ee03867cb2f86504fe3e7258bed1a8 |
| zgs-app/CODEX_PROGRESS.md | geändert | 02ccbf3b985645ab8fff2a60b64a94339b5fdf51ba0d152ab1a9c8f1d1b6cbe4 |
| zgs-app/CODEX_TODO.md | geändert | 21cb31b30950f8d359dff30099b12737cc38778bfbd58e6a20399df6d86cc07b |
| zgs-app/ENTSCHEIDUNGEN.md | geändert | 30bf93fb94060b6620787b40f8365b0a6867908dd12b0f72fbeebbb9664381c4 |
| zgs-app/ENTWICKLUNGSREST.md | geändert | c2e735143b322ac57f09039833966cbf7a58d6fa5ec402a7c9d6dd593ddfbffa |
| zgs-app/FORTSCHRITT.md | geändert | a1c82b179b1ee65bbc7b3b0cf33e4d6c023b770a7c16550b46f7a6d673aec9df |
| zgs-app/INSTALLATION_STRATO.md | geändert | 99936a60b0aa7c180fc1d6b17a95d0443f07aa4f92e8298702718b6b59ea3e26 |
| zgs-app/INTEGRATIONEN.md | geändert | 690aecdb576de3bd559b59e9457897d284ae69f67253613b75fdd9b2345a9ec1 |
| zgs-app/KOSTEN.md | geändert | 2024826578b5aa1d9387b37908ecb937341ddee494032002eebe0b13fb9b33df |
| zgs-app/NAECHSTE_SCHRITTE.md | geändert | 62f5507a8d2ab778d89d10debb0cbc8da7700e85061f71dd9e34587640d9bb33 |
| zgs-app/OFFENE_PUNKTE.md | geändert | e2ad7e0e73c1364f576754e677fdf3c2d804d92794a9e3937d46aba7b12ebe41 |
| zgs-app/OPEN_SOURCE_PRUEFUNG.md | geändert | 3c19b9337a2036b3513d5ca36dd7d7f18dc32ff685816c87ca32181fcaebfd17 |
| zgs-app/SICHERHEIT_DATENSCHUTZ.md | geändert | c73a6f6eab50735c1f127f2251e3366cac74c626cc06db4554eb478b35a766d7 |
| zgs-app/TESTBERICHT_IMMOBILIEN.md | neu | 5246172af86bacd15f7d429f2c22d08ebec442847cc880eaeb9fde055ad36691 |
| zgs-app/TESTBERICHT_MAIL_RECOVERY.md | neu | 5b7729cb0007316feea5149aa9718f2dc6efde9956e280a3c50121a5266a492a |
| zgs-app/TESTBERICHT_NEBENKOSTEN.md | neu | 870d37f1777244a12273564092f7dbda43b8f66786f17cd19c393698fa90b25a |
| zgs-app/TESTSTATUS.md | geändert | ddc3c5ee77064bedb428c0c3f00545270d225f667529b35ca670c4068c2f5928 |
| zgs-app/UPDATE_DATEIEN_AB_V300.md | neu | externes vollständiges Dateimanifest |
| zgs-app/UPDATE_DATEIEN_GESAMT.md | geändert | externes vollständiges Dateimanifest |
| zgs-app/private/estate_bank.php | neu | 89a4fcaead6b6501af944613efac5fd27799a5fb8c0c2b61b49711d6b7a75585 |
| zgs-app/private/estate_calculation.php | neu | e81763dc75d0699792495e7f4a3025d020cdc5bc8e39b883a9b7955675de36b0 |
| zgs-app/private/estate_documents.php | neu | 9d3903348087b3ea87daf2b8227cb7e261aa2b11b26278093103f3a0faf878ab |
| zgs-app/private/estate_math.php | neu | 758b24dc3a55b888de9b1762ece6e2014c7ee8e9f6b3c5a7d63303aa1fc12c6a |
| zgs-app/private/estate_settlement_export.php | neu | 9a84b70de6c0f860274b7c2373634130933ac9a7f7a911ead7a31d18ff5e6439 |
| zgs-app/private/estate_settlement_store.php | neu | ac4b6fbe15744cdb27922a89711c106d4b00a32c7b07b4b025f4fec85b26ff58 |
| zgs-app/private/estate_store.php | neu | 43b97ac906b71f654c65c5cdc1fff7acbef13dd9f31c4226b3cf45e559db31d4 |
| zgs-app/private/estate_ui.php | neu | 714fac005630e9a12db2a420cabebd039e56daaea990550099e9bb525d1cf262 |
| zgs-app/private/mail_remote.php | geändert | 649e7d354d058a2bb8f4e8d73eca63b7f310520ca6868938cff19d1bfd6e8c99 |
| zgs-app/private/mail_service.php | geändert | 56c1630501d03bcf3ca426d0a1b3c73d1fb423ef3ae17c16cd76991a17b2db60 |
| zgs-app/private/migrate_gesamt.php | geändert | e0092d14ff171da99d191cb4b7d018877ca054137777e3c2faafda206943d0d5 |
| zgs-app/private/migration_v310.sql | neu | 9075e2c2290861f1a93f81d4b9a59c7b5e3edcc2967181002b5fb5004a014cc6 |
| zgs-app/private/migration_v320.sql | neu | 10b7e82788a192686b700b7ffbaf8fb65486d14b989593ff20b66b2ac6c8c74c |
| zgs-app/private/migration_v330.sql | neu | a847637d92d5fe220a2f860a228c3f6e6e2075212bbe6dac6953584f27a28381 |
| zgs-app/private/platform.php | geändert | 8c12caebaf8f361034656e3efa72d9d53bc0e6cbc17a52df1ac99b437cd434d3 |
| zgs-app/private/ui.php | geändert | 25bb5a4972603a742eba8260fdc30f0bbda613b139edaa88680b416e6ce22a10 |
| zgs-app/public/compose.php | geändert | 43cd55c373a82558df6669a7f3589f2394edd3d48bfdefbb41302a3ace6899a4 |
| zgs-app/public/estate.php | neu | e128b99136f6a576f915b8f4c26b5da165b4a8ba25587eb9014834f7a47e026c |
| zgs-app/public/estate_bank.php | neu | daed3bab23a8ce00e7f2e8dfc34aa267810e2c0c283d203c3013f2fad3f16bbc |
| zgs-app/public/estate_download.php | neu | 2666738d5b4121989b1c7771753e8946dbcdc8485d74a6161a054b563f4f6d31 |
| zgs-app/public/estate_export.php | neu | 83b0fdc126b4f4c18d83b274701da62aedb99d83e845f767d8c5fd2e22c852ca |
| zgs-app/public/estate_settlement_export.php | neu | 007ad68199da34a6ff68f3a82da76c60e3a6e125ad295e034e5de1217306eff0 |
| zgs-app/public/estate_settlements.php | neu | e6aa3971eb6f53f7fbb5156c9912c7498f5a79a6789549e95433734a024a6f67 |
| zgs-app/public/estate_statement.php | neu | d9e5d329b1ed83226d8896787f736132532f49c78a0abdcea7de411d32ac76f6 |
| zgs-app/public/index.php | geändert | e26c8a59f510f0dab9c5483978a9dd36552b01615cbfbc85956022c36c46d6eb |
| zgs-app/tests/prepare_estate_browser.php | neu | 70246ccebc778f0cb1b0d6b7c735afa7878a4aa0bee2ee4e84c5caf9a8bdf562 |
| zgs-app/tests/prepare_estate_settlement_browser.php | neu | 9a313727410f420e8eb69ef09ac75b6915848fe1fbee70c148016ffaa886e271 |
| zgs-app/tests/test_estate_backup_restore.py | neu | 24b99deab612f372a4e9f53b26ed3a59d88eff0efb1fd63a759ef1b357010015 |
| zgs-app/tests/test_estate_browser.cjs | neu | 1aa0734d5772a0e4c50c544f7e7b966e6b54d2874902fda6bda86558deb8dbb1 |
| zgs-app/tests/test_estate_calculation.php | neu | df09f4e8f7cee0807817aa985bd9e4fe3dd02e5746191da310fe93db5ae71bc3 |
| zgs-app/tests/test_estate_concurrency.cjs | neu | 942e672542452cdc0108633b15233d24e456671c99f308459936c8335e6e8878 |
| zgs-app/tests/test_estate_core.php | neu | bdb32dee3c2c1d786791ed6be686e0b998bb6494c20c94803c9d870a3d497fe4 |
| zgs-app/tests/test_estate_database.php | neu | aebe157b55f0f111c9f00e3d72f9d98e3504a4117a10836602d2605606bbd8c7 |
| zgs-app/tests/test_estate_export_browser.cjs | neu | 3654611791464a9df2d2304b187958f2563527f8aad6eef63c4a7cafd4c82676 |
| zgs-app/tests/test_estate_settlement_browser.cjs | neu | 00dcde3e6e44c6aa364f7cb97f5646be678d63a76c77fb1706930a4a11476b89 |
| zgs-app/tests/test_estate_settlement_database.php | neu | c7f9a92ea703999dcd15386b5da5a805782e2a3c1b8bb13ac3c249233af99fff |
| zgs-app/tests/test_mail_recovery_database.php | neu | 7d85bc6b9b6d53a8ec29d606824478f21bea74dc4cdc2b86866464bcfc639e81 |
| zgs-app/tests/test_mail_recovery_http.php | neu | 3372448a8a4e1ddbaef681b2adc6d86cb117c50c26843d1887bb7ce52d1273af |
| zgs-app/tests/test_mail_recovery_imap.php | neu | dc86debbfe263eb03fcad2cba23d380a98543a66ca8d8a841a8fb86b5ef5105d |
| zgs-app/tests/test_upgrade_gesamt.php | geändert | 0a8bf85185fd488a3fe600eae5be0a9ddf1ba066e3d4b385c069ed1d12150252 |
