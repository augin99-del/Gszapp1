# Phase 0/1 – lokale Prüfung und spätere Testinstallation

Keine Produktionsfreigabe. Nichts auf gszintern.de installiert. Dieses Paket enthält weder private/config.php noch eingebettete Sicherungen, private Uploads, Datenbankdumps oder echte Zugangswerte.

## Voraussetzungen und Versionsprüfung

PHP mindestens 8.1 wegen vorhandener never-Rückgabetypen; für neue Installation mindestens 8.2 empfohlen. Getestet: PHP 8.4.24, PDO MySQL, mbstring, iconv, fileinfo; MariaDB 11.8.6/InnoDB/utf8mb4. SQL orientiert sich am bestehenden MariaDB-10.6+-Schema, aber nicht auf 10.6 getestet. Tatsächliche PHP-/MariaDB-Version des STRATO-Tarifs vor Installation über Kundenbereich/CLI feststellen, erforderliche Extensions prüfen. Keine phpinfo-Seite öffentlich belassen. IMAP/SMTP wurden nicht kontaktiert.

## Sicherung vor jedem Update

1. Wartungsfenster festlegen, Cron pausieren und Web-Schreibzugriffe sperren.
2. Konsistente DB-Sicherung erstellen (z.B. mariadb-dump --single-transaction, Zugang über geschützte Optionsdatei; Passwort nicht in Kommandozeile).
3. Gesamte alte Codeversion, private Serverkonfiguration und private/uploads getrennt sichern, Dateihashes erfassen. Sicherungen verschlüsselt außerhalb des Document-Roots mit beschränktem Zugriff aufbewahren.
4. Sicherung zuerst in separater Datenbank/Dateikopie wiederherstellen und Datensätze, Datei-Hashes und Anmeldung prüfen. Nicht die Produktion als Restore-Ziel verwenden.

## Lokale oder separate Testinstallation

1. Separate leere Testdatenbank anlegen. Domain/Server nur auf public/ richten; private/ und tests/ nicht veröffentlichen. Keine Original-ZIP auf Server ablegen.
2. In leerer Datenbank zuerst private/fresh_install_v192.sql, danach einmalig private/migration_v200.sql importieren. Keine anderen historischen Migrationen zusätzlich ausführen. Basisschema hat 12 Tabellen, mit app_migrations sind es 13.
3. Bei bestehender Installation: Zustand mit gelieferten Dateien vergleichen; erst auf bereinigter Kopie der tatsächlichen Produktivstruktur testen. Nur wenn vollständige v1.9.2-Struktur besteht und v200 fehlt, einmal v200 ausführen. DDL in MariaDB committet automatisch; nach Teilfehler nicht blind erneut starten. Der lokale Test mit dem gelieferten SQL ersetzt diese Prüfung nicht.
4. Sichere Serverkonfiguration: vorhandene private/config.php erhalten. Alternativ Beispiel kopieren und ausschließlich serverseitig konfigurieren. Alle Schlüssel können durch GSZ_<SCHLÜSSEL_IN_GROSSBUCHSTABEN> überschrieben werden, etwa GSZ_DB_HOST, GSZ_DB_NAME, GSZ_DB_USER, GSZ_DB_PASS. GSZ_DB_SOCKET nur für lokale Tests. Keine echten Werte in Chat, Logs oder Paket schreiben.
5. Versand ist zusätzlich serverseitig gesperrt: allow_mail_send=false ist Standard. Nur nach ausdrücklicher Nutzerfreigabe und Testkonto später GSZ_ALLOW_MAIL_SEND=true setzen. In dieser Sitzung keine Freigabe erteilt. Das Mailmodul ist weiterhin ein Prototyp.
6. Bestehenden Admin in Testkopie verwenden. Das überlieferte Paket enthält KEIN setup.php; die alte Installationsanleitung ist an dieser Stelle unvollständig. Kein versteckter Default-Admin. Für automatisierte lokale Tests ausschließlich tests/prepare_browser.php mit synthetischem Admin nutzen, niemals auf produktiver DB. Eine vollständige neue Ersteinrichtung ist nicht Teil der Phase 1.
7. object_import.php als angemeldeter Admin öffnen. Startdatum sorgfältig wählen: aktive Regeln erzeugen beim nächsten Dashboard-/Planeraufruf alle fälligen Termine (24 je Regel/Aufruf). Zukünftiges Datum nutzen, wenn noch keine Aufgaben entstehen sollen.
8. Vorschau prüfen. Bei Objektkonflikten Bestand zunächst manuell bereinigen; Import bricht ab. Bei abweichenden Leistungsnamen vorhandene Regel zuordnen oder Neuanlage bewusst bestätigen. Bestehende Daten, Frequenzen und Aktivstatus werden NICHT überschrieben; Abweichungen danach unter recurring.php prüfen. Der Import ergänzt bei übernommenen Datensätzen nur seed_key.
9. Checkbox bestätigen und importieren. Sieben Objekte, normalerweise 28 Regeln. Erneuter Import erzeugt null neue Datensätze. Lippstädter Straße 5 ist Rheda-Wiedenbrück (Nutzerbestätigung 09.11.2026).
10. Zeiten/Starttermine bearbeiten. Neue normale Tonnenroutinen sind pausiert und zusätzlich vom Scheduler ausgeschlossen. Nach echtem Kalenderabgleich als manuelle Regel mit ausdrücklicher Bestätigung speichern, dann bewusst aktivieren. Fontänenstraße Gelbe Tonne Mi/Do ist separat aktiv. Saisonfenster sind inklusive, auch 10–4 ist möglich; außerhalb wird der Terminrhythmus weitergezählt, aber keine Aufgabe erzeugt. Winterdienst und Kleinreparatur nur manuell. Zeitbedarf unbekannter Arbeiten bleibt leer, keine Schätzung erfunden.

## Rollback

Fehlgeschlagener Stammdatenimport wird vollständig zurückgerollt. Bereits erfolgreich importierte Datensätze nicht pauschal löschen: sie können inzwischen Aufgaben/Referenzen haben. Bei fachlichem Irrtum Regeln pausieren und Daten nachvollziehbar korrigieren. Keine Belege oder Archive löschen.

Bei Codeproblemen alten geprüften Code zurückspielen; zusätzliche v200-Spalten sind für alten Code grundsätzlich unsichtbar. alten Cron weiterhin pausiert lassen, bis Schedulerstand geprüft ist. Für vollständigen Rückfall ausschließlich konsistente DB-/Dateisicherung in Wartung wiederherstellen; dabei neu entstandene Daten vorher gesondert sichern und abgleichen. Kein automatisches DROP-/DELETE-Downskript: vermeidet Verlust neuer Regeln/Aufgaben. Die Phase-1-Migration ist additiv, aber nicht transaktional rücknehmbar.

## Tests reproduzieren

```sh
find public private tests -name '*.php' -print0 | xargs -0 -n1 php -l
php tests/test_money.php
php tests/test_invoice_pdf.php
php tests/test_schema.php
php tests/test_recurring.php
```

MariaDB: neue leere lokale Datenbank mit Namen gsz_test_<kennung> anlegen. Explizit den lokalen Test-Socket und Testbenutzer angeben:

```sh
GSZ_TEST_DSN='mysql:unix_socket=/PFAD/ZUM/TEST/mysql.sock;dbname=gsz_test_phase1' GSZ_TEST_USER=testbenutzer php tests/test_phase1_database.php
```

Der Integrationstest lädt Schema und Migration selbst und verweigert nichtleere Datenbanken sowie andere Namens-/DSN-Formen. Er hinterlässt synthetische Daten für Prüfung und Restoretests. Testkennwort ggf. nur über sichere Umgebung GSZ_TEST_PASSWORD.

Browser: tests/prepare_browser.php benötigt separate leere gsz_test_*-DB und lokalen root ohne Passwort (nur isolierter Testserver). Playwright/Chromium installieren; tests/test_browser.cjs gegen lokale HTTPS-Testinstanz mit diesen synthetischen Fixtures ausführen, GSZ_TEST_URL und GSZ_TEST_SCREENSHOT optional. Hier kam ausschließlich ein externer Loopback-Testproxy zum Einsatz, der HTTPS für den PHP-Entwicklungsserver setzt; Produktions-bootstrap wurde dafür nicht abgeschwächt. Der Test importiert Daten und ändert eine Dauer, deshalb vor jedem Lauf frische Fixtures verwenden. tests/ niemals öffentlich ausliefern.
