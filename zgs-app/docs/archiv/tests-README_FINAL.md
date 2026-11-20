# Lokale QA – keine Produktivfixtures

PHP8.2+64Bit und MariaDB10.6+ in einer isolierten Testumgebung. Native Runtimeextensions siehe INSTALLATION-STRATO.md, DOM zusätzlich fürCAMT. FürsProdukt keinNode/Python nötig; nur Browser-/TLS-/Restore-QA braucht Playwright/Chromium,Python/OpenSSL/poppler und MariaDB-Clienttools.

Befehle/Ergebnisse in TESTSTATUS-FINAL.md sind tatsächliche lokale Ausführungen. DB-Suiten benötigen proTest eine neue leere gsz_test_*-Datenbank über GSZ_TEST_DSN (mysql:unix_socket=...;dbname=...). Sie benutzen bewusst den lokalen Test-Root ohne Passwort und niemals die private Anwendungskonfiguration. Nur auf einer separaten lokalen MariaDB ohne produktive Daten/Netzwerkfreigabe einsetzen. Keine Testpasswörter als echten Inhaberzugang übernehmen.

ReineTests z.B. php tests/test_final_core.php, test_v1_planning.php, test_mail_core.php. Datenbanktests erst nach Einrichtung eines passenden leeren Testschemas, Namenpräfix der jeweiligenSuite beachten. test_final_upgrade.php baut historische330/340-Struktur ausschließlich in dieser leerenTestDB, niemals auf vorhandenemBestand. NegativeSchemaguards können die eigeneTestDB absichtlich in abweichendemZustand hinterlassen; keine solchen Dumps installieren.

Browserfixtures sind keine Installer: prepare_gesamt_browser.php fürBasis, prepare_receivables_browser.php fürMahnungen/Finanzvorlagen, test_cash_assets_browser_fixture.php fürKasse, prepare_estate_settlement_browser.php fürAbrechnungen. ErstFixture, dann PHP-Testserver mit privatemSessionpfad und lokalemTLS-Proxy starten; GSZ_TEST_URL aufLoopback setzen, node tests/TEST.cjs. test_final_browser benötigt vorab den Kernpraxistag/v1-Daten und ausschließlich dort serverseitig erlaubtesWebupdate. Keine echtenZugänge. Den Upload-Dateiwähler mit SetInputFiles und tatsächlichen MIME-Daten prüfen, keine Clientvalidierung als Servervalidierung zählen.

test_smtp_transport.php ist Hilfstreiber für python3 tests/test_smtp_loopback.py, kein selbständigerLive-Sendtest. test_mail_recovery_imap.php kannStandaloneShimoderHilfstreiber sein. test_final_optional_dom.php ausdrücklich mit PHPohneDOM ausführen, sonstverweigertesTestsetup. Diese fehlendeVoraussetzung nicht alsFunktionsfehler/PASSumdeuten.

Paralleltests brauchen die ausdrücklich angegebenen unveränderten DB-Fixtures; sie verändern ausschließlich synthetische Daten. Restoretests erstellen eine zweite neueTestDB und vergleichen logischeDumps/privateDateihashes; keine Dateien oderDumps imAuslieferungs-ZIP aufbewahren. Die realen STRATO-/Mail-/Bank-/iPhone-Tests bleiben getrennte Abnahmeschritte.
