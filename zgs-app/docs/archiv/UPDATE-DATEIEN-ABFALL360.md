# Exakter Updatepatch FINAL350 →Abfall360

Basis-Voll-ZIP-SHA256: `ec71a0f3aed9f3f2f4a99033d1888bc4878e9c28cd872119fb943f8dba2ab70c`. Ausgang318Dateien/Schema350/82Tabellen, Ziel360/84Tabellen. Nicht auf abweichenden Code blind aufspielen. Keine Dateien gelöscht. Installation INSTALLATION-ABFALL360.md.

Einzige neue Migration: private/migration_v360.sql, ausgeführt über den bestehenden gesicherten Migrationsrunner. Historische SQL-Dateien unverändert. Vorher Datei-/DB-Backup plus Restoreprüfung. Keine privaten Konfigurationen oder Daten im Patch.

Alle neuen und geänderten Dateien:

| Datei | Art | Basis SHA256 | Ziel SHA256 |
|---|---|---|---|
| zgs-app/ABFALL360_BESTAND.md | neu | – | a68957418af82d672cb3947fd9b894652c8496ead3929192226fee939ea56f25 |
| zgs-app/AGENTS.md | geändert | b1c231cd53e486e8efcfa577fe7faf33c0f0724e1161e3e057998fc1def4cfba | 187e326f48be612715937ab1116ff55485a3d421379f1add665b208b8b606bba |
| zgs-app/ARCHITEKTUR_ABFALL_AUTOMATIK.md | neu | – | 9a4f5e02b755a9833c1edadee30bad15c22527727b49edac79e943a66bbe25be |
| zgs-app/CHANGELOG-ABFALL360.md | neu | – | f49ba7054b67bcb6eafea3fd86c2994650b7dfc4cf92d5cc110c1a08a775c309 |
| zgs-app/CHANGELOG.md | geändert | 4ed2bc7bba6ee273bb815eecb88ff7e32ab77402532a760fb5e4793c5e4d047d | d3548ee2dcfcb63e2565d60834bd4745e1bb8a38d8ccf5d901ba1986746db02c |
| zgs-app/CODEX_CONTINUE.md | geändert | e6f1bfaf6a4fc5d683d40dd7917bf899c5cbfde448ab2a76f28813a3cb2912a4 | 1d26131b19aafab9274d4a7f045af4145bf32b76a6946f5654b4d157f7c52ea3 |
| zgs-app/CODEX_FORTSETZUNG.md | geändert | c7dbdb931962f61afb3b0ad6fcf90aa3b4e1c93d114ed198f32c0b72bb83f518 | 2af8e242587a9a4a1a39c16666c5824dfd7990d99cce53a5f86e8073c0103c4c |
| zgs-app/CODEX_PROGRESS.md | geändert | da3ecb17846e2f5ca4e98e1179683b4a78fbf3d2e66c968b600716009d05f120 | 2dd4584d35174512f81a9372ae6d2a64ff23145fa45e9b5927c16e3c33723367 |
| zgs-app/CODEX_TODO.md | geändert | 57c6f1a53e267b7bc2e8189e36e112cd8fc5b168c9f8ed86d4af427ca4e999f0 | 09c8440fbcf7f0a00332b90f47ee9e61c8642cc88ac28565cae6345dcfc480e0 |
| zgs-app/DEPLOYMENT.md | geändert | b616fb2c2238f3aac2c1f29c8f330be5d229fe8075d25e33046e1011e22f9504 | 8d452027a0923427c60a7def295927a086c002d12cb196367f04af4dbfb48b21 |
| zgs-app/ENTSCHEIDUNGEN.md | geändert | fd1e4688719903cee5bf5f4539960c9ccb236e8d32b954d7d4bcb18844954ea2 | bef666562f6d03f27a07562cf88d3831ae7622f74933c826f576e97778e7fc95 |
| zgs-app/FORTSCHRITT.md | geändert | bfee5d6eef0882288bfb9d514dd225b374ab0b4091b70550c1bc564f9c28bc29 | d2bab3b02a8c357616d61d9dd0a8ff615d845affb5411e60d3aea35fcd57faa5 |
| zgs-app/GO_LIVE_STATUS.md | geändert | f682431d69745b4e81c40a24c220ac7f7c4b20e2c3d8a7057c781ca6e6d1bcbe | f31478918ba1c9da8c37cf9574188d3167b3c19a8b60ca201087a97c99b29e15 |
| zgs-app/IMPLEMENTIERUNGSPLAN.md | geändert | 109c800128e2a7526ba9fb321b5e9196fa28095742ed7f49f6e2b0ec4a1747a8 | e425774c757a3797e04a4121fd44dea5c654534478ffbbe3149d264d816391be |
| zgs-app/INSTALLATION-ABFALL360.md | neu | – | b2264eb07adc328ef8fc32f11a1aa3ff8e4c51fca708898b8b6239b5344aa728 |
| zgs-app/INSTALLATION-STRATO.md | geändert | 794ad9233f9ed67a04c6b4ea824d23a7a2f4d46a485a97193ddd9defbe476778 | 16387a2dec8ba2ce605ba61f76a83dd3bc494f499df1570c86d2d214675b168f |
| zgs-app/INTEGRATIONEN.md | geändert | 690aecdb576de3bd559b59e9457897d284ae69f67253613b75fdd9b2345a9ec1 | b70eecb7d370e9a8ad789b8d915b68b181acdf2acf53ece98388238858903b61 |
| zgs-app/LIESMICH-ABFALL360.md | neu | – | 6116de49256fa7d35fbc404dde805990597cbc6b1dada3c0dd979a1295b816c5 |
| zgs-app/MODULE-STATUS-ABFALL360.md | neu | – | d9a156b1a3fbded63185be07eb971c541d4bbe0a3a68f9130660ed35b494c174 |
| zgs-app/MODULE-STATUS.md | geändert | 3d8a4357f45a50ecdd7dabf67d850178504d24d454bd2f27edda9e743a4e80c8 | 1ebc2698a7b1f1c1a9881f45fc3d64d51f3fc3afdfa8bbb88c59ad4fc858f99a |
| zgs-app/NAECHSTE_SCHRITTE.md | geändert | 2afeef1da30a4ae5120e15bb9e987135135a1d5a601507c2e34c3986101703dc | f2d79eaa7efdc262caceecf76a874907842b8f6a6c382b9ea5897344f2c0087a |
| zgs-app/OFFENE-PUNKTE-ABFALL360.md | neu | – | 4fa72c1c0a0af80fe1d5fdb5b81fae697f34c8460082e3b4d693d35fd159c6be |
| zgs-app/OFFENE_PUNKTE.md | geändert | dcda1944a13fd0a72922463f9924b411bef8315fa4fcd87bf206a9bafd9ef8e0 | d91a64f61f2027476ca986567dbabbe4a2e63f9f97eed0c3348e7ccbf23fc6c4 |
| zgs-app/QUELLEN_ABFALL.md | neu | – | ddb3eb2200d624ac977cb6b7c8e3dba68b8adc41fa07d18d5b8ff5c7cbfe7ddd |
| zgs-app/RELEASE-PRUEFUNG-ABFALL360.md | neu | – | 58ac4501062b677e9991f99b0064e639e8f1afe7c0467be8613898f99b2c989b |
| zgs-app/SICHERHEIT_DATENSCHUTZ.md | geändert | e68295f883925639506108db0555efc1a156af21ac6a5287cfe98a6000ff2b4e | 5e1d372f83afb4d2c6d6b73ec60364a32367aa55478a6ddc77545617d562e6b0 |
| zgs-app/TESTSTATUS-ABFALL360.md | neu | – | fc2bf5364f63a3bf3ff2e02c7b7576605479a14d812fc586209ae1e8191157a8 |
| zgs-app/TESTSTATUS-FINAL.md | geändert | 476b2efc913d55f11e23920e3c8b91ee47bb281942552ec951194ad9474a89ac | efbb6656a5565c9e8049a765d4d2e48799698ca986c45725268a2e208d1d3f8a |
| zgs-app/TESTSTATUS.md | geändert | f44e15844a687610e8b1c05998361ddb80edffa7558b3a5c463a18b79e1965e2 | 2e4bac6125c3c1f6be24b351fe0a81331ee3b57f06c33d920991263b147daf02 |
| zgs-app/UPDATE-DATEIEN-ABFALL360.md | neu | – | externes Dateimanifest (Selbstreferenz) |
| zgs-app/private/automation.php | geändert | 0671106e1e4705aebb0b96215a593b21d147716a2a0af4225a98ee21a82c0a08 | 829bdb87c6d39b18669b8f4a6e67eab72e39460ae16cb072854da7fabae7e9e5 |
| zgs-app/private/config.example.php | geändert | bc8585e7cbede557303f8871cb6b8ca865a4af6cf671e8051123cdad36da179f | e794789db401d56f4d818497db49e9e76f5ce4a0bb3045ac40ae041a5d39ef19 |
| zgs-app/private/dashboard_service.php | geändert | d8d2bb1f83a59f2b84740d4b6f503e65812e94417fa88cad782094742d799742 | f715cc44affae741488a3d919f2bbcf463050f14aedca6335ecb1cc6bad08267 |
| zgs-app/private/install.php | geändert | 0931afda5d8cab92aae89d21cd62718409d3b7c2e1a875f471340b2ee8be49bc | a6d6ab70abe54f23f5710c67a2545e4c22e52f6f72c5dd71700db185f85ca7ea |
| zgs-app/private/install_core.php | geändert | ad49956e878b8f2405f0d667f961efc6cb176966c51d71b8a4f132b1ba5d8739 | 917ce30be9169b44742d2e101207d38bda5f26cb9bbff3e92f79a99e513b56b7 |
| zgs-app/private/migrate_gesamt.php | geändert | 62625eed8beaf674a1fae4245470ae3854ca29088318b56e73953865ede06f5b | c56b4988f15ffdcadb59f70dab1f80087140e9a13b956bfd4e62ff9439537826 |
| zgs-app/private/migration_v360.sql | neu | – | 8d6cf7bf1bac9b7beb9538a521fe0ac353b333155b1b65a97eed547847d5cfa6 |
| zgs-app/private/release_integrity.json | geändert | 26789588345496daa27f2d682d555e18999211ef4f33515075cb6b7dc2689d1a | 13e49c1e9a0fdaa2cbfe1c1201b79a79d8f64fa7a72e1b49c834a208b4db37b2 |
| zgs-app/private/release_integrity.php | geändert | 37ee7054465ea112994bf3464965ede3c56b91f66f4c8a9564277a4129074245 | 7f52fabd81e3c38d24c63675303595351788be0c23c53989168ca4ba10ab89bd |
| zgs-app/private/scheduler.php | geändert | c78c151d64eaea4eb525cf6c68084df67db076a65aa8eca434ece8b4d4578a91 | a7445423b321736bbc53ed0547d2daf11c13dce77d02041cc396405c8c83c67d |
| zgs-app/private/schema_requirements.php | geändert | de0f221dbb49baf7258440aedc8cbef1857f28a8046a3ea5c431af38f92c6617 | 4f056bf3be3bf53661ae01e67c2c2e84d0f0e082aa684e02855420718f41e6e7 |
| zgs-app/private/system_check.php | geändert | 891e4ac8e5e765de716459fc216dc2cc4ce606ccbed5b1f149c0f61c392dbc36 | ecb0988814bef2f56263bfbcffa2ad5989ed0bf9f4cfccc8678ce6653ccf1874 |
| zgs-app/private/waste_connections.php | neu | – | 69a7c32a3d79b5a6d0a2480babe997139f168c25cacadec259ffefc29ab1e7ac |
| zgs-app/private/waste_parser.php | geändert | 71a923f598abc381a8a3e167c1d0a326bcd0445a6de1ca995dd2fbf288caa1b6 | f21f1adc9a3c77d10ee449fd78c419977c481f5d1007081d4ee2cced645180e6 |
| zgs-app/private/waste_provider.php | neu | – | 3b7c3b013f1c7df197997248797651831e83f82a6c9fe198b606cfca2d22502f |
| zgs-app/private/waste_store.php | geändert | 8d5552bcb8eaf23f5115244913bce520a1af4ea1464872882cde0705c41fd13f | 532bbbc68b40764c4c198f93ca8a6cf57435aa2b7d843649db34b3250fd33b63 |
| zgs-app/private/waste_sync.php | geändert | 1f351f21100f83b726a2361e73021d4f31892a6d31ed2489f00bdffc3ccb9c26 | 4d0fc3bbd452135e06aadd412913f830f933cb19ec21e60aa5300380c53233b8 |
| zgs-app/private/web_update.php | geändert | 4f776835a67da192ed1e5f020f487e2e1e922b0c8b5d5b9fb72c46262f778daa | b028738f90e215c2938e2bd44b0e324dccd3f57b6efaf9e1fc4e621ebfc22ad9 |
| zgs-app/public/bootstrap.php | geändert | 3e42ccacaee17e09b600f1d9c96ccb5a558bb6f7d54be520af630702dd37f888 | 22769add290319407a6e4da80c9fd0c03d6c834040459f845eb9ee83e2cc26c1 |
| zgs-app/public/index.php | geändert | 7f13854e83c82a8329fdf28873821fdd95c252731f4dbe45a641270615275b0b | 5639f2802826ddde78a1512d76d00565aedfcf862f39ab45dcefac3311ad8843 |
| zgs-app/public/property.php | geändert | 156157b1caf508a8d11caa95a931d76c3d69ff3b4bedb49e640faf4bb1a2a3a1 | 9039357cbc2deac0b0c1819e75fabbd16a753438f815b3d3e8eebb51605d406e |
| zgs-app/public/update.php | geändert | a14781ea765dff489cd30e32451bd13e8d6d81d65ac53035e1f7cb4262028bbe | 62b84fe4f2f7953d1715a31eb1e16a96ee0e5972f03d74dd0888d1d6394740db |
| zgs-app/public/waste.php | geändert | c509d889cbb0d54c746e3e58856a2fc99df94a8675ea91f7a9eb6b636089661a | a11cbc52f9c0f3d502d599d34b9e600bab863d89b48ae3c77506e02b89a9bd3c |
| zgs-app/public/waste_setup.php | neu | – | e7d55614e01ea587fee79635a9a9af998a2aa52c3e73a509cbdcdc9beea75c31 |
| zgs-app/tests/prepare_waste_connections_browser.php | neu | – | ffe909a246db32525328068e13a8642fcc8758b0add3232d99411f3cb13db3bb |
| zgs-app/tests/test_final_browser.cjs | geändert | 62831a109f9d257cb5def6d5e7ac5c30eeb92725acf70fddbb0667e566dc86f2 | 690bc0a8136bf1cdb61ecb3c3a39a1c99c22408821d1fdb56b0742df1a28b25b |
| zgs-app/tests/test_final_integrity.php | geändert | 9bea9d9d1a14be86e9223d92226ab054987ad7eeb7da3875d7cd129ace91a2ce | 75ec98763cf56442ee162b8b050f39d448fc0e4beba462925e420a1e98866604 |
| zgs-app/tests/test_final_upgrade.php | geändert | bb273b4ea17364f706ada4eacdf581c7f22e008424009a4faaa9b2831d53298b | 17d19f50e15ffc10251af694dc258c1cd8c5015580fbae1391ea532bc8c23a52 |
| zgs-app/tests/test_installation_database.php | geändert | 844579e2e1098fa835f4bb12957efbb8d1c7c64c2fecc989e32e3ed01d8eb3d2 | 61b0b6a7cb2543f66a045a776f0efcf04d2dffb2009294d1e1047bacaf115ebb |
| zgs-app/tests/test_waste_connections.php | neu | – | 1a2737efec396775ab7200ab2b0a54ce4165018bfb8b1c17ee054a75fe25b069 |
| zgs-app/tests/test_waste_connections_browser.cjs | neu | – | 1b3457c5f98113f179f9e3875cd5c644e4daf87082b84a2bb733688443353c21 |
