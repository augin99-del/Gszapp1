# Aktueller Installationskandidat: Abfall360

Exakte FINAL350 →Schema360,84Tabellen. [INSTALLATION-ABFALL360.md](INSTALLATION-ABFALL360.md) verwenden. Lokale Abnahme ist keine Produktivfreigabe; native STRATO-Verbindung zuerst geschützt testen.

# Aktueller FINAL-Stand 12.11.2026 / Schema350

Maßgeblich: INSTALLATION-STRATO.md, TESTSTATUS-FINAL.md, MODULE-STATUS.md, CHANGELOG-FINAL.md, INTEGRATIONEN-FINAL.md und CODEX_FORTSETZUNG.md. Neu: täglicheRegeln, integriertesDashboard/Navigation, CAMT/Kontentrennung/Review, OfflineSEO, geschütztesBestandsupdate/Dateiintegrität. Schema350/82Tabellen; keineProduktivinstallation, echteMail/Bank/Cron/Objektkalender weiter nichtnachgewiesen. Aussagenzu330/340nachfolgend sindHistorie. LiveSEO-/GSC-/GBP-Adapter,OCRundvalidierteE-Rechnung nichtalsfertigausgeben.

---

# STRATO-Einrichtung Version1.0 – kontrollierter Installationstest

**Status: lokal geprüfter Releasekandidat, nicht vollständig abgenommen und nicht produktiv installiert.** ZIP-Name bezeichnet die Zielversion, keine automatische Produktionsfreigabe. Keine aktuellen Serverzugänge vorhanden. Kritische externe Abnahmen OFFENE_PUNKTE.md. Bestehende Installation bleibt bis ausdrücklicher Freigabe unverändert.

## A – Zuerst überprüfbare Sicherung

Im STRATO-Kundenbereich bestehende Domainzuordnung/DocumentRoot, PHP-Version, Tarif/SSH-/Cronmöglichkeiten und Datenbankversion festhalten, ohne Zugangsdaten in Chat/Screenshots zu zeigen. Vollständige vorhandene Anwendung einschließlich privater Konfiguration, Uploads/Originale/Vault sichern, zusätzlich konsistenten vollständigen MariaDB-Export (Struktur, Daten, Indizes, Trigger soweit vorhanden). Sicherungen außerhalb jedes Webroots und verschlüsselt aufbewahren. Keine Sicherungs-ZIP ins public-Verzeichnis. Zeitpunkt, Dateianzahl, Größe und SHA256 festhalten; Rücksicherung auf separater Testdatenbank/Dateikopie verifizieren. Ein Download ohne erfolgreiche Restoreprüfung reicht nicht als Rollbacknachweis.

Für konsistente produktive Sicherung Schreibzugriffe und vorhandene Cronjobs kontrolliert pausieren; dieser Eingriff erfordert vorherige gesonderte Freigabe. Keine echten Bestandsdaten an öffentliche Testdienste senden. Zuerst abgeschottete Testsubdomain mit eigener Testdatenbank und zugriffsgeschützten Dateien erstellen. Testinstanz darf keine produktiven Mail-/Bank-/Routingaktionen auslösen.

## B – Paket prüfen und hochladen

GSZ-Zentrale-v1.0-STRATO-Release.zip und SHA256SUMS-GSZ-v1.txt vergleichen. ZIP enthält zgs-app/, keine private/config.php, Uploads, Bankdaten, Postfachinhalte, Backups oder Secrets. Im eigenen geschützten Verzeichnis entpacken. Domain/Testsubdomain muss ausschließlich auf `zgs-app/public/` zeigen. `private/`, Tests, Dokumentation und Archive dürfen nicht per HTTP erreichbar sein. Falls das Hosting dies nicht sicher zulässt: BLOCKED, keine Veröffentlichung des Projektwurzelverzeichnisses.

Updatepaket GSZ-Zentrale-v1.0-Update-ab-v331.zip ist ausschließlich ein Patch zur exakten v331-ZIP (SHA25649dd763afb6d8480ed4a42508fb7c0e457f679564d9f4b2b2669458d3fc44c21). Vorhandene abweichende Dateien anhand Manifest vergleichen; nicht blind überschreiben. Keine Dateien löschen. Wer eine andere Basis hat, verwendet getrennt das Vollpaket und übernimmt kontrolliert seine private Konfiguration/Originale. Niemals die Konfigurationsvorlage als Ersatz echter Konfiguration über eine vorhandene Datei kopieren.

## C – Konfiguration und Datenbank

Voraussetzungen: PHP8.2+64Bit (lokal8.4.24 geprüft; Nutzer meldet8.4.27 vor Ort), MariaDB mit InnoDB/utf8mb4, PDO/MySQL, mbstring, iconv, fileinfo, session, openssl, json/hash/filter/ctype, zlib. Sodium für verschlüsselten Vault; native IMAP für aktuellen Mailadapter. Tatsächliche Extensions über Systemcheck prüfen, nicht aus Tarifnamen ableiten. CLI-PHP und Web-PHP können unterschiedlich sein. `session.save_path` muss in beiden tatsächlich auf eine beschreibbare private Ablage zeigen (Verzeichnis0700bevorzugt); CLI kann vorübergehend mit `php -d session.save_path=/ABSOLUTER/PRIVATER/PFAD ...` geprüft werden. Das setzt den Web-PHP-Wert nicht automatisch. Ein Systemcheck-Sessionblocker ist vor Inbetriebnahme zu beheben. PHP-Verzeichnisrechte/privater Sessionpfad, genügend Speicher/Uploadgrenzen und funktionierende CA-Zertifikate müssen vor Ort geprüft werden. Lokale Testwerkzeuge Node/Python/Chromium werden nicht auf STRATO benötigt.

Neuinstallation: private/config.example.php als private/config.php ausschließlich außerhalb public kopieren und echte DB-Werte sicher auf dem Server eintragen. Alternativ absolute externe Konfiguration via serverseitigem GSZ_CONFIG_FILE. Kein Symlink, keine öffentlichen anderen Leserechte,0600 bevorzugt;0640 nur wenn tatsächliche PHP-Hostinggruppe es erfordert. GSZ_*Umgebungsvariablen überschreiben bekannte Konfigurationsschlüssel. Passwörter niemals im Chat, in URLs oder Shellkommandozeilen übermitteln. mail_enabled/allow_mail_send/routing_enabled zunächstfalse lassen. Bei bestehender Installation alle privaten Werte erhalten.

Mit tatsächlich verfügbarem CLI-PHP aus zgs-app/:

```sh
php private/secure_config.php --check
php private/system_check_cli.php --json --web-root=/ABSOLUTER/PFAD/zgs-app/public
```

Bei unsicheren Rechten, sicherem Speicherort und eigener autorisierter Testkopie:

```sh
php private/secure_config.php --fix --confirm
```

Das Werkzeug ändert nur Rechte auf0600, liest keine Inhalte, verschiebt keine Dateien und rotiert keine Zugangsdaten. Bei Symlink/falschem Webroot/Eigentümerproblem stoppt es. Speicherort/Domainzuordnung zuerst korrigieren; keine Symlink-/Permission-Prüfung abschalten. Öffentlich gewordene Kennwörter/Diagnosetokens über den jeweiligen sicheren Anbieterweg rotieren und alte Diagnoseskripte entfernen. Hier wurde keine reale Rotation durchgeführt.

**Neue leere Testdatenbank** (Fresh verweigert jede bestehende Tabelle):

```sh
php private/install.php --fresh
php private/admin_setup.php
```

Für admin_setup zuvor GSZ_SETUP_EMAIL sicher auf dem Server setzen; neues Passwort wird über Standardeingabe eingegeben. Nur auf neuer leerer Inhaberinstallation ausführen. Kein vorhandenes Konto zurücksetzen. Danach sieben Objektregeln über authentifizierten Import prüfen/bestätigen, nicht nochmals ungefragt seeden.

**Bestehende v330-Kopie** (gesicherte, getestete Kopie, Wartung/Cron vorher kontrolliert):

```sh
php private/install.php --upgrade --backup-confirmed
```

Neuer Schritt migration_v340.sql, Schema340/78 Tabellen. Vorbedingungen vollständige protokollierte Vorgängerstruktur. Runner prüft Historie, sperrt parallele Upgrades, überspringt bereits protokollierte Schritte und prüft v340-Felder. Historische SQL-Dateien nicht direkt erneut ausführen. Additive DDL ist nicht komplett transaktional rückrollbar. Bei Teilfehlern stoppen, Grund/Rechte/Struktur prüfen; derselbe noch nicht protokollierte idempotente340-Schritt darf nach Prüfung erneut laufen. Fehlt etwas trotz bereits protokollierter340-Version, stoppt der Runner; keine automatische historische Reparatur. Nie `--fresh` als Reparatur einer bestehenden Datenbank.

Migration340 erweitert Aufgaben/Regeln/Planung/BCC/Objekt-Tonnenversätze und legt recurring_completions/automation_state an. Keine automatischen Änderungen an erledigten/verschobenen/gesperrten Aufgaben, Rechnungen, Mietverträgen oder Belegoriginalen. Automodus behandelt neue Quartals-Fenster-/Garagenabschlüsse als Anker; vorhandene offene historische Quartalsaufgaben bewusst einzeln prüfen. Standard flexible Arbeit Mo–Fr, Treppenhäuser Mo–Mi; geschützte Sonntags-/Müllaufgaben bleiben erhalten. Einstellungen sind editierbar.

Wenn Tarif kein SSH/CLI bietet, den vom Hoster unterstützten sicheren Weg erheben; keinen anonymen Webinstaller oder öffentlichen SQL-/Diagnose-Endpunkt improvisieren. SQL340 allein ersetzt keine Vorprüfung/Versionskontrolle.

## D – Mail, Kalender und Cron bewusst einrichten

Private Mailkonfiguration: imap.strato.de993SSL/TLS, smtp.strato.de465SSL/TLS, vollständige eigene Postfachadresse, Postfachpasswort und verifizierter Absender. mail_enabled erst nach sicherer Einrichtung aktivieren. allow_mail_send bis zur konkreten Testfreigabefalse. Native IMAP muss vorhanden sein; bei fehlender Extension bleibt Mail BLOCKED. Ein deaktiviertes Zusatzprodukt belegt nicht, dass das eigentliche Postfach deaktiviert ist.

```sh
php private/mail_check_cli.php --check
php private/mail_check_cli.php --imap
```

--check prüft nur Konfiguration/Extension; SMTP-Verbindung bleibt ausdrücklich NOT TESTED. --imap liest tatsächliche Ordner/INBOX-UIDVALIDITY ohne Mailversand. Gesendet-/Papierkorb-/Archivordner aus der tatsächlichen Ordnerliste auswählen, nicht vermuten. mail_sent_folder vor Versand konfigurieren. Freigegebene Testnachricht in der UI als Entwurf prüfen, dann ausdrücklich senden; SMTP-Annahme UND Gesendet-Kopie/Empfang/Anhänge prüfen. Bei unklarer SMTP-Annahme nicht nochmals automatisch senden, vorhandenen Recoveryweg benutzen. Postfachstatus erst nach realem Empfang und Versand alsfunktionsfähig markieren.

Offizielle Objektkalender und echte Exportlinks nach INTEGRATIONEN_V1.md, manuelle ICS/CSV-Vorschau/Freigabe zuerst. Keine frei erfundenen Abos/APIs. Zeitverschiebungen ausschließlich importierte Daten. Cron ausschließlich nach tatsächlicher Tarifprüfung/CRONJOBS.md, mindestens einen echten erfolgreichen Lauf nachweisen. Ohne Cron authentifizierter manueller Knopf im Systemcheck.

## E – Abnahme in der Testinstanz

Systemcheck im Browser UND CLI, Webroot/HTTPS/Cookie/Sitzung und privater Dateizugriff prüfen. Auf iPhone/Safari390px und Computer anmelden; Kundenobjekt erstellen/bearbeiten, Notiz/Dokument speichern/herunterladen, Regel anlegen, idempotent erzeugen, Woche vorschlagen und prüfen, Fixtermin13:00–14:30, Aufgabe abhaken/verschieben und nach Neu-Anmeldung auf Persistenz prüfen. Fenster tatsächlich abschließen und nächste3Kalendermonate prüfen. Unbekannte Dauer/Überlast müssen sichtbar bleiben. Normale Tonnenroutinen nicht aktivieren; Fontänen-Sonderregel erhalten. Rechnungen/Archive vorhandener Originale nur lesend kontrollieren; keine echten Rechnungen/Testmails ungefragt erzeugen.

Für Mail und Kalender reale Empfang-/Versand-/Gesendet-/Importprüfungen separat dokumentieren. Backup aus genau diesem Stand zurückspielen und Geschäftsdatensummen/Originalhashes vergleichen. Technische Vorprüfung ohne Blocker ist keine Gesamtfreigabe. Ergebnisse als PASS/FAIL/BLOCKED/NOT TESTED protokollieren.

## F – Produktivfreigabe als eigener letzter Schritt

Erst nach behobenen kritischen Blockern, gesicherter Wiederherstellung, erfolgreichem Kern-/Mail-/Kalender-/Gerätetest und sichtbaren Restgrenzen den konkreten Produktivplan freigeben lassen. Kontrolliertes Wartungsfenster, aktuelles verifiziertes Backup, geprüfte Dateiliste, Migration340 und Rückfallplan erforderlich. Keine Änderung an gszintern.de wurde hier vorgenommen. Nach Freigabe erneut Kernfunktionen prüfen; Abschlussbericht mit verbleibenden Einschränkungen verfassen. ROLLBACK.md beachten.
