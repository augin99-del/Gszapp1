# Installation auf STRATO – Version 380

## A. Update einer bestehenden 370-Installation (der normale Weg)

Dauer etwa 15 Minuten. Es gibt nur eine Live-Datenbank – deshalb ist Schritt 3 Pflicht.

1. **ZIP prüfen.** `GSZ-ZENTRALE-KOMPLETT-STRATO-380.zip` herunterladen. Optional die Prüfsumme mit
   `SHA256SUMS-380.txt` vergleichen (Mac: `shasum -a 256 GSZ-ZENTRALE-KOMPLETT-STRATO-380.zip`).
2. **Dateien hochladen.** ZIP auf dem Mac entpacken. Den Inhalt von `zgs-app/public/` und `zgs-app/private/` per
   SFTP/STRATO-Dateimanager über die bestehenden Ordner kopieren („ersetzen“).
   Nicht löschen oder überschreiben: `private/config.php`, `private/server-config/`, `private/secrets/`,
   `private/uploads/`, `private/backups/` (sind im ZIP nicht enthalten, bleiben also unberührt).
3. **Anmelden → Sicherung.** In der Zentrale anmelden. Weil die Datenbank noch auf 370 steht, öffnet sich
   automatisch „Sicherung und Update“.
   - „Sicherung jetzt erstellen“ tippen.
   - Die Sicherung herunterladen („Sicherung vom … herunterladen“) und auf dem Mac aufbewahren.
4. **Update ausführen.** Häkchen „Sicherung heruntergeladen“ setzen, eigenes Zentrale-Passwort eingeben,
   „Update jetzt ausführen“. Die Zentrale
   - prüft, ob alle Programmdateien vollständig hochgeladen sind (Dateimanifest),
   - führt Migration 380 aus (entfernt nur die 24 Finanztabellen, ergänzt Mietabgleich, fasst Abfall-Aufgaben zu Blöcken zusammen),
   - löscht die nicht mehr benötigten Programmdateien des Finanzbereichs,
   - entfernt alte Finanz-/Bank-Einstellungen aus der verschlüsselten Konfiguration,
   - übernimmt ein bisher gespeichertes Mail-Passwort in den neuen Passwort-Speicher.
5. **Kontrolle.** Mehr → Einstellungen → Systemstatus: es soll „Alles Wesentliche in Ordnung“ stehen.
   Wenn „Alte Programmdateien entfernt“ einen Hinweis zeigt, die genannten Dateien per Dateimanager löschen.
6. **Einmalig einrichten.**
   - Immobilien: jedes Volksbank-Konto seinem Haus zuordnen (die Zentrale weist auf der Startseite darauf hin).
   - E-Mail: Mehr → Einstellungen → E-Mail einrichten → Postfach-Passwort einmal eintragen → „Verbindung testen“.

Mit SSH (falls im Tarif vorhanden) geht Schritt 3–4 auch so (legt die Sicherung automatisch an):
`php zgs-app/private/install.php --upgrade --backup-confirmed`

Ältere Stände als 370 werden über den Browser nur mit vorübergehend gesetztem `allow_web_update => true`
aktualisiert (siehe docs/archiv) oder über den grafischen Einrichtungsassistenten `setup.php`.

## B. Neuinstallation (leere Datenbank)

1. Bei STRATO eine leere MariaDB-Datenbank (utf8mb4) anlegen, Domain-Webroot auf `zgs-app/public/` setzen, SSL aktivieren.
2. ZIP hochladen und entpacken.
3. `https://<domain>/setup.php` öffnen und dem Assistenten folgen (Einmalcode aus `private/runtime-setup/access-code.txt`
   im Dateimanager, Datenbankzugang, Schema anlegen, Inhaber anlegen, abschließen).

## Voraussetzungen

PHP 8.2+ (64 Bit) mit pdo_mysql, mbstring, iconv, fileinfo, zlib, openssl, sodium, session, json; DOM optional (nur für
CAMT-Kontoauszüge). MariaDB 10.6+. HTTPS. Kein Node/Python auf dem Server nötig.
