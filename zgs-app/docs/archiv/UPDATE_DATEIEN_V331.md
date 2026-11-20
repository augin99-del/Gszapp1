# Exaktes Update v330 → Installationsstand331

Basis GSZ-Checkpoint-v330-Immobilien-Nebenkosten.zip, SHA256 b75f6f196f39f5829fdb8cc9188558a5e7ddd3a6529fdb014db1c644d9a25a89.

Keine Löschungen, keine neue SQL-Migration; Schema330 bleibt unverändert. Konfiguration/Uploads/Vault niemals überschreiben. Erst Sicherung und getrennten Installationstest nach INSTALLATION_KURZ_STRATO.md durchführen. Keine produktive Freigabe.

Das Manifest selbst hat seinen Hash im separat mitgelieferten DATEIMANIFEST_GSZ_v331.sha256, um Selbstreferenz zu vermeiden.

| Datei | Zustand | SHA256 |
|---|---|---|
| zgs-app/AGENTS.md | geändert | 7288a56b8c9238ecec77e029f7d4b5cb98c2b6d551102540faad7bbbb63d46f9 |
| zgs-app/CHANGELOG.md | geändert | fc402b6f369a3339889667eddfe52a2845bd10df7edf72f942b06ffe6534fcfe |
| zgs-app/CODEX_FORTSETZUNG.md | geändert | a399da261a22d67c2b15e3534c1c3dcd85129c947b0f91021437a01d36c9ce40 |
| zgs-app/CODEX_PROGRESS.md | geändert | cd6171b3dc24b7e7559bbe0d0e134ffcc6758b8af4d2ea1a6c6145f89d6b0cda |
| zgs-app/CODEX_TODO.md | geändert | f6793fc453c9651d834e67e74bc90e59e3f32f224535ff4877dc1c07dde7c9cb |
| zgs-app/ENTSCHEIDUNGEN.md | geändert | 60c00ea26c3c0c7489b329b8b03953f45724a44bdff003672223af3d07ee45a7 |
| zgs-app/FORTSCHRITT.md | geändert | d8323afc60afa8a650dccaeb2f54af68ea015dc47adc28a018f2f898c8a66900 |
| zgs-app/GO_LIVE_STATUS.md | neu | 55187548d3265b2869c1db1004e963a5facb85fba14aa554fd4632ff998475e0 |
| zgs-app/INSTALLATION_KURZ_STRATO.md | neu | d9029f24ae98aa0764f78732bd4ffdfdffaf1f844972f604eb18c14f666d1d61 |
| zgs-app/INSTALLATION_STRATO.md | geändert | d61c100df5130d1a0257f8324a6610c745713ca114c2d5b602b1df8eef1e3a2b |
| zgs-app/NAECHSTE_SCHRITTE.md | geändert | ae44b98757eac2ce3b0ec75d962a6829537b1f27c94a592d1646a323dc423fdd |
| zgs-app/OFFENE_PUNKTE_INBETRIEBNAHME.md | neu | 60678e0e7583ea1ab7656f2375164e4c83ca0ac91aaf3f32b0a77805dfa35be3 |
| zgs-app/README.txt | geändert | 1a0fab3d85507800dec896b40c20c0e0938b84d9c9b4e573308fcd971de259c2 |
| zgs-app/SICHERHEIT_DATENSCHUTZ.md | geändert | 6790981c9e8528b3b6d296b64e768e576af6c3a2465cfa0b6a52f172487907b8 |
| zgs-app/START_HIER.md | neu | 4884bfcd473b2c455bc48727d7823cbff10af6d218dd5c1dd7aa054c8bd9f49d |
| zgs-app/TAEGLICHE_NUTZUNG.md | neu | 2f1858e3bcada834206b31cad2c65ec765428b93ea89d3d1e07fe2d67acdbb0d |
| zgs-app/TESTBERICHT_INBETRIEBNAHME.md | neu | b54ec0506325735c1bac5d8221322ec170b4deb45b1885939d14947b10661d95 |
| zgs-app/TESTSTATUS.md | geändert | c9036fec70a3ee06bb8a1da78cbfb5782def058f5727ec4e0f79644515a991dc |
| zgs-app/UPDATE_DATEIEN_V331.md | neu | externes vollständiges Dateimanifest |
| zgs-app/private/document_storage.php | geändert | e61216094c27a467591bca345b05b6c1de2c1e8b37cdefcd29d3ae48b0d6d1f6 |
| zgs-app/private/install.php | neu | 81ef40e87f9b3475f767be56ff642033c5698daf66b23c67569959e4d7a11b07 |
| zgs-app/private/install_core.php | neu | a0bf86e3bf60a0d8aa37c7e1bb1f64095cf2e521fefbbf285942a27ca0a10c7e |
| zgs-app/private/photo_storage.php | geändert | c449953008c16a34a939a13356131299fddd985be77fcf4ddfd7d8499e65ed9c |
| zgs-app/private/planning_store.php | geändert | bcecdba8b514acf4ae25963a6d081366656b02295609d1b303541b11ab5415cf |
| zgs-app/private/schema_requirements.php | neu | a9cfdebcd7593f9d853c139500c7e90b1e3af186c7ff2f0b9c97505d7ace74b2 |
| zgs-app/private/system_check.php | neu | 9ea1d5ea5c237c63abe4b8e916db99c9e4b68b0dd990f5d901267661d403d506 |
| zgs-app/private/system_check_cli.php | neu | c4ac7891f73387e1f5855eeef692c0725815630d07268cf36a2c4eb0b5c3be5c |
| zgs-app/private/ui.php | geändert | ceac1ecb0218936e0177889517efdb6b994277a6ba78acb1bd8a39c8b039964f |
| zgs-app/public/assets.css | geändert | 3454cb8c28d181e623dac58d69639eab1db06a59d6172c6c007a176e72ffa56e |
| zgs-app/public/documents.php | geändert | 55ce29a5d57e39948b93ca30d50f47ed5c13541e4d5b059e2fa49eedb213e178 |
| zgs-app/public/gsz-dashboard.css | geändert | ef8889704a8475ba7df53a678d14f0815ff8b943886eb48129aadeb5347fbe68 |
| zgs-app/public/index.php | geändert | f8f1136e4fa234f1e241d164541222a09399682aad8de379960a8e2d9d6c5f27 |
| zgs-app/public/planner.php | geändert | 32e23a5df3f0a2a885c329612591950c5775db5b2364719dfe60d4691d2bb3dd |
| zgs-app/public/recurring.php | geändert | 7fcaf0e13b8a09eaff9fd9c6474877d05eeb96d72e532638b68ab30c71508d5a |
| zgs-app/public/system_check.php | neu | 8453f7f96156015bc7886ccc35c89475546169b194f00fbf8f4420e502d3c3bb |
| zgs-app/tests/test_golive_browser.cjs | neu | ca6c201236052e0bb612bc4d686479c42dbd93b65fd52b7a5f64904e461d4c87 |
| zgs-app/tests/test_installation_database.php | neu | 6d7b8d4d89ff47abaa217a61a14dbd57468a5259bf373b12d44491258f715d6d |
| zgs-app/tests/test_installation_restore.py | neu | c9a8af4cd0cd105db620fc657bc140f0c78e7f8abcd309fc47249bfa2f53feca |
| zgs-app/tests/test_installation_smoke.cjs | neu | 5086fc214485ebe52cabbfb2af4a8e0fcbe9065a3b439e3f7c33f4c69fbe22b9 |
