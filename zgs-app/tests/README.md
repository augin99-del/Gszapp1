# Tests (nur lokal, nur synthetische Daten)

Voraussetzungen: PHP 8.2+, lokale MariaDB 10.6+ (Unix-Socket, root ohne Passwort, **ohne** produktive Daten),
für Browser-Tests Node + Playwright/Chromium (`/usr/bin/chromium`), OpenSSL-Zertifikat für den HTTPS-Loopback.
Auf STRATO wird nichts davon benötigt.

```sh
tests/run_php_suites.sh        # alle PHP-/DB-Suiten, je Suite eine neue gsz_test_*-Datenbank, inkl. Quellstand-Matrix
tests/run_browser_suites.sh    # alle Playwright-Suiten: Fixture → PHP-Testserver → HTTPS-Proxy → Test
tests/run_browser_suites.sh test_zentrale_ui_browser   # einzelne Suite
```

Hilfen in `tests/dev/`: `serve.sh` (Testserver), `router.php` (HTTPS-Kennung nur lokal), `tls_proxy.cjs`,
`screens_fixture.php` (Vorführdaten), `run_setup_browser.sh` (Einrichtungsassistent), Generatoren für Manifest/Schema.
Der Ordner tests/ liegt außerhalb des Webroots public/ und wird nie über das Web ausgeliefert; auf STRATO wird er nicht ausgeführt.
