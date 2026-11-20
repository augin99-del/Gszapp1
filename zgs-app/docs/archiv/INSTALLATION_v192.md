# GSZ-App v1.9.2 – Datenbankinstallation (Testbetrieb)

**Entwicklungsfassung. Keine echten Kunden-, E-Mail- oder Rechnungsdaten verwenden.**

## Was wurde korrigiert?

- `migration_v16.sql` und `migration_v19.sql`: Fremdschlüsselspalten verwenden jetzt `INT UNSIGNED`, passend zu den referenzierten Primärschlüsseln. Das behebt einen MariaDB-Fehler beim Erstellen der Tabellen.
- `public/setup.php` erstellt das Schema **nicht mehr selbst** und verweigert die Admin-Einrichtung, wenn Tabellen oder wichtige Spalten fehlen. Dadurch wird eine halb installierte App nicht irrtümlich freigegeben.
- `private/fresh_install_v192.sql`: Einmaliger, zusammengeführter SQL-Import für eine **leere** Testdatenbank. Enthält das Basisschema und die nötigen Migrationen in Reihenfolge.
- `tests/test_schema.php`: Statische Prüfungen der Tabellen, Fremdschlüsseltypen und Setup-Sicherheit.

## Neue Testinstallation

1. HTTPS für `gszintern.de` vollständig aktivieren; Domainziel muss auf `/zgs-app/public` zeigen.
2. Auf STRATO eine **separate, leere MariaDB-Testdatenbank** erstellen. Keine bestehende Datenbank überschreiben.
3. `private/fresh_install_v192.sql` **einmalig** über phpMyAdmin in diese leere Testdatenbank importieren. **Nicht** zusätzlich die einzelnen Migrationsdateien importieren.
4. Tabellen in phpMyAdmin kontrollieren. Bei einem Fehler stoppen; nicht einfach erneut importieren, sondern Ursache untersuchen.
5. Dateien hochladen; `private/` darf nicht im öffentlich erreichbaren Webverzeichnis liegen. `private/config.example.php` lokal auf dem Server als `private/config.php` kopieren und Zugangsdaten/Setup-Key dort eintragen. Keine Passwörter im Chat oder Repository teilen.
6. Erst dann `https://gszintern.de/setup.php` öffnen und den Administrator anlegen. `public/setup.php` danach **vom Server löschen**.
7. Mit fiktiven Testkunden/Objekten Funktionen prüfen. Backups und Wiederherstellung testen, bevor echte Daten verwendet werden.

## Upgrade einer bestehenden Installation

**Nicht `fresh_install_v192.sql` verwenden.** Zuerst Datenbank-Backup und Schema-Zustand ermitteln. Die angepassten SQL-Dateien reparieren keine bereits teilweise ausgeführten Migrationen automatisch. Für bestehende Installationen ist ein gesonderter, geprüfter Migrationsplan erforderlich.

## Testgrenzen

Alle PHP-Dateien wurden auf Syntax geprüft und statische Tests ausgeführt. **Keine echte MariaDB- oder STRATO-Integration** wurde durchgeführt. Der SQL-Import und die Anwendung müssen auf der Testdatenbank noch tatsächlich getestet werden. Rechnungsmodul, E-Rechnung, PDF und Archiv sind nicht produktionsreif.
