# Installation und Rückfall Phase 2

Keine STRATO-Installation ausgeführt. Kein Live-Deployment freigegeben. Vollpaket und Update enthalten weder config.php, .env, Geheimnisse, Datenbankdumps noch ZIP-Backups.

1. Vor späterem Update Schreibzugriffe/Cron in Wartung stoppen, Datenbank und Code sowie private Serverkonfiguration/Uploads separat geschützt sichern. Restore zunächst lokal oder auf separater Testkopie prüfen. Produktive Struktur und tatsächliche STRATO-Version vorab feststellen; diese Sitzung prüft nur das gelieferte Schema.
2. Voraussetzung vollständige Phase 1 einschließlich v200. Update-Dateien aus UPDATE_DATEIEN.md übernehmen; vorhandene private/config.php und Uploads erhalten. public/ ist der einzige Document-Root. tests/ und private/ niemals öffentlich ausliefern. PHP mindestens 8.1, empfohlen 8.2+, PDO MySQL/mbstring/iconv/fileinfo; geprüft PHP 8.4.24 und MariaDB 11.8.6. MariaDB 10.6+ Ziel, nicht separat getestet. Keine Node-/Python-Runtime für Produktivbetrieb nötig.
3. Auf einer Strukturkopie zuerst Vorprüfung und Migration ausführen: `php private/migrate_phase2.php`. Nutzt bestehende sichere Serverkonfiguration/GSZ_-Umgebung. Vorprüfung verlangt Phase-1-Ledger und wesentliche Basisspalten. Additive SQL in private/migration_v210.sql, zusätzliche Tabellen mit IF NOT EXISTS, Spalten/Index mit IF NOT EXISTS. Erneuter Lauf lokal getestet. Kein bestehendes Aufgabenfeld und keine Buchhaltungsdaten werden migriert/überschrieben. Bei unerwarteten, bereits gleichnamigen aber anders definierten Spalten/Tabellen manuell Schema abgleichen: IF NOT EXISTS repariert keine inkompatible Struktur.
4. MariaDB-DDL committet automatisch. Teilfehler: Ursache/SHOW CREATE TABLE in privater Umgebung prüfen, dann wiederanlaufbaren Runner erneut starten. Kein Versprechen atomarer SQL-Migration. Keine alten v192/v200-Migrationen auf bestehender Datenbank erneut ausführen. Neuinstallation nur in leerer Test-DB: v192-Bundle, v200, dann Runner v210. setup.php fehlt weiterhin; keinen Standardadmin in Produktion anlegen.
5. Als Inhaber planner.php öffnen (standardmäßig Heute). Unter Einstellungen Puffer und Tageszeiten prüfen. Längere Uni über abweichenden Tagesstart abbilden. Wochendatum auswählen, Vorschau erzeugen, Warnungen/Änderungen prüfen, ausdrücklich übernehmen. Ohne Übernahme bleiben Aufgaben/Regeln unverändert. Virtuelle Aufgaben vorab über Regelverwaltung korrigieren; existierende Aufgaben über Bearbeiten. Nach Änderungen Vorschau neu erstellen.
6. Wiederholte Übernahme darf nichts verdoppeln. Manuelle Aufgabenbearbeitung sperrt den Termin; Freigabecheckbox erlaubt spätere Automatik, sofern feste Uhrzeit/Reihenfolge ebenfalls geleert sind. Ungeprüfte Tonnenroutinen nicht aktivieren. Gelbe Sonderregel Mi/Do bleibt aktiv. Termine lassen sich entfernen und neu anlegen. Kollisionen sichtbar prüfen; keine echte Navigation ohne eigenen Klick.
7. Mailversandsperre bleibt standardmäßig aktiv. Keine Kontoaktionen. Tests vor Freigabe auf STRATO-Testkopie wiederholen.

## Rollback

Vorherige Phase-1-Codeversion wiederherstellen, Schreibzugriffe stoppen und neue Daten vorher sichern. Zusätzliche Tabellen/Spalten zunächst stehen lassen; alter Code ignoriert sie. Bereits bestätigte Terminverschiebungen sind Datenänderungen und werden durch Coderollback nicht zurückgesetzt. Für vollständigen Rückfall konsistente DB-/Dateisicherung wiederherstellen und neue Daten gesondert abgleichen. Kein destruktives Downskript; keine Belege löschen. Die Phase-1-Konfigurations-/Versandschutzmaßnahmen bleiben erhalten.

## Tests

```sh
find public private tests -name '*.php' -print0 | xargs -0 -n1 php -l
php tests/test_money.php
php tests/test_invoice_pdf.php
php tests/test_schema.php
php tests/test_recurring.php
php tests/test_planning.php
GSZ_TEST_DSN='mysql:unix_socket=/PFAD/mysql.sock;dbname=gsz_test_p2' php tests/test_planning_database.php
GSZ_TEST_DSN='mysql:unix_socket=/PFAD/mysql.sock;dbname=gsz_test_p1' php tests/test_phase1_database.php
```

DB-Tests benötigen getrennte leere lokale gsz_test_*-Datenbanken. Phase-2-Test nutzt isoliertes lokales root ohne Passwort; kein produktiver Benutzer. Browsertests: Playwright/Chromium auf Entwicklungsrechner, synthetischer Testadmin wie tests/prepare_browser.php, v210-Schema und Testfixtures wie Phase-2-DB-Test; GSZ_TEST_URL auf ausschließlich lokale HTTPS-Instanz setzen, dann `node tests/test_planning_browser.cjs`. Alter Browser-Smoke-Test: eigene frische v200-Fixtures, `node tests/test_browser.cjs`. Diese Testkonten und Tests niemals öffentlich bereitstellen. Lokaler HTTPS-Testproxy bleibt außerhalb des Lieferpakets; Produktionsbootstrap wurde nicht abgeschwächt.
