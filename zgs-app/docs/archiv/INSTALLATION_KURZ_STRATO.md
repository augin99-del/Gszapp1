# Aktueller FINAL-Stand 12.11.2026 / Schema350

Maßgeblich: INSTALLATION-STRATO.md, TESTSTATUS-FINAL.md, MODULE-STATUS.md, CHANGELOG-FINAL.md, INTEGRATIONEN-FINAL.md und CODEX_FORTSETZUNG.md. Neu: täglicheRegeln, integriertesDashboard/Navigation, CAMT/Kontentrennung/Review, OfflineSEO, geschütztesBestandsupdate/Dateiintegrität. Schema350/82Tabellen; keineProduktivinstallation, echteMail/Bank/Cron/Objektkalender weiter nichtnachgewiesen. Aussagenzu330/340nachfolgend sindHistorie. LiveSEO-/GSC-/GBP-Adapter,OCRundvalidierteE-Rechnung nichtalsfertigausgeben.

---

# Aktueller Stand Version1.0 / Schema340

Maßgebliche aktuelle Einrichtung: DEPLOYMENT.md, CRONJOBS.md, ROLLBACK.md, TESTSTATUS.md, INTEGRATIONEN_V1.md und OFFENE_PUNKTE.md. Schema330/76Tabellen in nachfolgender Historie gilt nur für ältere Auslieferungen; jetzt additive340/78Tabellen. Keine echte STRATO-/Mail-/Produktivfreigabe. Frühere Aussagen nicht als Beleg aktueller externer Einrichtung verwenden.

---

# GSZ – erster Installationstest auf STRATO, Release331 / Schema330

Dieser Stand ist für einen kontrollierten realen Installationstest vorgesehen. Keine produktive Installation oder Datenänderung wurde ausgeführt. Zuerst getrennte Test-Subdomain und eigene Testdatenbank verwenden. Die tatsächlichen Tarifmöglichkeiten sind noch unbekannt; fehlendes SSH/CLI, Domainziel oder DB-Rechte nicht durch Annahmen ersetzen.

## 1. Vorbereiten

Vollständige ZIP GSZ-Installationsstand-v331.zip verwenden. Für exakt den vollständigen v330-Checkpoint genügt GSZ-Update-v330-auf-v331.zip. SHA256SUMS-GSZ-Inbetriebnahme.txt und UPDATE_DATEIEN_V331.md vergleichen. Vorhandene private/config.php, private/uploads und separat geschützte Vault-/Schlüsseldateien niemals durch Paketdateien ersetzen. Neue Version enthält keine Secrets oder Datenbankinhalte. Neue SQL-Migration ist für v330→331 nicht erforderlich.

Auf bestehendem System zuerst konsistente Datenbank- und Dateisicherung auf geschütztem Rechner/Backupziel erstellen, Wiederherstellung auf Kopie prüfen. Vorhandenes Kunden-/Objekt-/Mieter-/Rechnungsdatum nicht neu importieren. Keine Produktion ohne gesonderte Freigabe. Bei Kopie echter Daten auf Staging gesonderte Datenschutz-/Zugriffsfreigabe und sichere Ablage erforderlich; zunächst synthetische Testdaten.

## 2. Hosting prüfen

PHP mindestens8.2 auf64Bit, Referenz lokal8.4.24. Benötigt PDO/MySQL, mbstring, iconv, fileinfo, zlib, session, ctype, filter, hash, json. MariaDB mindestens10.6/InnoDB/utf8mb4; tatsächlich lokal11.8.6 getestet. Ältere/abweichende Versionen nicht als bestanden ansehen. Private Dateien und Sitzungsablage beschreibbar, minimale Rechte (Dateien0600 bzw. notwendige Hostinggruppe0640, private Ordner0700 bzw. geeignete Hostinggruppe0750). Niemals pauschal0777 verwenden. Web-PHP und CLI können unterschiedliche Versionen/Extensions verwenden: beide prüfen.

Für volle Dokumentgröße upload_max_filesize≥10M, post_max_size≥12M, memory_limit≥128M (256M für größere PDFs/Exporte empfohlen). display_errors=Off, log_errors=On, privates Hosting-Fehlerlog. Konfigurationsmethode/.user.ini-Unterstützung im Tarif prüfen; keine hartcodierten Serverpfade. HTTPS-Zertifikat auf iPhone und Computer ohne Ausnahme bestätigen. Nicht ungeprüft Forwarded-HTTPS-Header akzeptieren.

## 3. Dateien und Konfiguration

zgs-app mit unveränderter Struktur hochladen, Domainziel EXAKT zgs-app/public. private, tests und Dokumentation bleiben außerhalb der Webwurzel. private/.htaccess ist zusätzliche Absicherung, kein Ersatz für richtiges Domainziel. Apache-.htaccess-Wirkung auf STRATO gesondert prüfen. Keine Quell-ZIP/SQL-Sicherung in public ablegen.

Auf dem geschützten Server private/config.example.php nach private/config.php kopieren und dort ausschließlich die tatsächlichen Datenbankwerte aus dem Hostingkonto eintragen. Datei nicht per Chat/öffentlicher URL übertragen. Alternativ dokumentierte GSZ_-Serverumgebungsvariablen/optionalen libsodium-Vault verwenden. Beispielwerte sind keine nutzbaren Zugangsdaten. mail_enabled=false, allow_mail_send=false, routing_enabled=false belassen. Keine Bankverbindung aktivieren. Konfiguration und Schlüssel nicht in Git/ZIP sichern.

## 4. Leere Testdatenbank installieren – nur mit verfügbarem PHP-CLI

Aus dem Projektverzeichnis mit dem tatsächlich im Tarif verfügbaren PHP-CLI:

```sh
php private/install.php --fresh
```

Der Helfer prüft MariaDB/utf8mb4, verwendet eine DB-Sperre und verweigert JEDE nicht leere Datenbank. Er nutzt die vorhandenen Originalschritte192→200→210→220→230…330 und legt76 Tabellen an, aber keinen Standardzugang, keine geschäftlichen Daten und keine erfundenen privaten Immobilien. DDL ist nicht insgesamt atomar. Bei Abbruch NICHT bestehende Tabellen löschen oder Versionsmarker erfinden; Ursache/Teilschema auf Kopie prüfen. Für erneuten isolierten Fresh-Test eine neue leere Testdatenbank benutzen.

Ersten Inhaber anlegen:

```sh
GSZ_SETUP_EMAIL='DEINE_E_MAIL' php private/admin_setup.php
```

Das Skript erwartet das neue Passwort über geschützte nicht interaktive Standardeingabe (14–200 Zeichen), z.B. aus einem vertrauenswürdigen Passwortmanager/privatem Administrationswerkzeug. Nie als CLI-Argument/Chat oder in Shellhistorie angeben. Direkte interaktive Terminaleingabe wird absichtlich verweigert. Ein einfacher Bash-Ablauf mit unsichtbarer Eingabe, ohne Passwort im Aufruf:

```sh
read -r -s -p 'Neues GSZ-Passwort: ' gsz_initial_password
printf '\n'
printf '%s\n' "$gsz_initial_password" | GSZ_SETUP_EMAIL='DEINE_E_MAIL' php private/admin_setup.php
unset gsz_initial_password
```

Vorhandene Admins werden nicht geändert; wiederholte Einrichtung wird verweigert. Kein Passwort per SQL von Hand einsetzen. Ohne SSH/PHP-CLI ist dieser Einrichtungsweg nicht nachgewiesen: Tarif/gesicherten administrativen Ausführungsweg erst klären. Es gibt absichtlich keinen anonym erreichbaren Webinstaller und keine Diagnose mit phpinfo/Zugangsdaten.

## 5. Vorhandenes v330 aktualisieren

Konsistente Code+DB+Dateisicherung erstellen, Wartung in Serverkonfiguration aktivieren, Update gegenüber exakt passendem v330 einspielen. Schema330 bleibt unverändert, kein neues SQL nötig. Systemcheck prüft alle erwarteten Tabellen/Spalten/Ledgerversionen und Identitätsschlüssel. Ältere vollständige v220/v300-Strukturen benötigen die vorhandenen getesteten Gesamtmigrationen zunächst auf Kopie; optional kontrollierter Aufruf:

```sh
php private/install.php --upgrade --backup-confirmed
```

Die Bestätigung behauptet keine automatisch erstellte Sicherung. Backup vorher tatsächlich erstellen/prüfen. Der vorhandene Runner prüft v220-Voraussetzungen und führt additive Schritte durch. Unbekannte/teilmigrierte Altstruktur zuerst vergleichen; niemals Fresh auf Bestand und keine historischen SQL-Schritte blind erneut ausführen.

## 6. Systemcheck und Browser

```sh
php private/system_check_cli.php --web-root=/ABSOLUTER_PFAD/zgs-app/public --probe-storage
```

Optional --json für maschinenlesbaren Bericht. Exit0 bedeutet keine automatisch erkannten Blocker, Exit2 weist auf fehlende Voraussetzungen hin. CLI kann HTTPS/Zertifikat/Browsercookies/Cron nicht bestätigen; das bleibt ausdrücklich Hinweis. --probe-storage erzeugt ausschließlich eine zufällige32Byte-Testdatei privat, prüft sie und entfernt sie. Keine Geschäftszeilen werden geändert.

HTTPS-Testadresse öffnen, anmelden, „Systemcheck / Einrichtung“ aufrufen. Dieser authentifizierte GET prüft die tatsächliche Web-Laufzeit ohne Datei-/DB-Mutation und ohne externe Anfragen. Alle Blocker beheben; Hinweise zur Hostingkonfiguration einzeln prüfen. Sitzungscookie Secure/HttpOnly/SameSite=Strict, inaktive Sitzung nach30Minuten, spätestens8Stunden neu anmelden. Browserschließen beendet die Sitzung; Daten bleiben in MariaDB gespeichert.

## 7. Tagesbetrieb einrichten

Sieben Betreuungsobjekte bei leerem Bestand ausschließlich über „Bestandsobjekte prüfen und importieren“ als Vorschau prüfen/bestätigen; vorhandene Daten werden nicht überschrieben. Gewählten Beginn für neue Regeln prüfen. Alle neuen Monats-/Quartalsarbeiten nicht ungeprüft auf den ersten Tag zwingen: Wochenvorschau verteilt flexible Aufgaben, Fixtermine/Tonnen/manuelle Sperren bleiben geschützt. Quellen für normale Abfuhrdaten fehlen, Normalroutinen bleiben pausiert. Nur tatsächliche geprüfte Kalenderdaten importieren; Fontänenstraße-Sonderregel bleibt erhalten.

Wenn Tarif Cron unterstützt: eigenen geprüften PHP-CLI-Pfad und geschützte Konfiguration verwenden, täglich vor Arbeitsbeginn `php /ABSOLUTER_PFAD/zgs-app/private/cron_gesamt.php`, ohne --mail-sync. Erfolgreiche tatsächliche Ausführung und Wiederholung kontrollieren. Ohne Cron täglich „Fällige Regelaufgaben aktualisieren“, danach Wochen-/Tagesvorschau prüfen und übernehmen. Das reine Öffnen verändert keine Aufgaben. Erinnerungen zunächst in der App, kein Push-/Mailversprechen.

## 8. Abnahme und Rückfall

TAEGLICHE_NUTZUNG.md mit synthetischen Daten auf iPhone und Computer durchgehen, Browser schließen/neu anmelden und gespeicherte Zustände kontrollieren. Backup per Hosting/phpMyAdmin (SQL) plus vollständige geschützte Dateien einschließlich private/uploads konsistent erstellen; Schlüssel getrennt sichern. Auf separater Datenbank/Dateikopie wiederherstellen und Login/Aufgaben/Originaldownloads verifizieren. Erst dann gesonderte produktive Freigabe erteilen.

Rückfall: konsistente zusammengehörige Code+DB+Dateisicherung wiederherstellen; neue zwischenzeitliche Vorgänge/Originale vorher sichern und abgleichen. Keine destruktiven Downmigrationen, keine historischen Belege löschen. Ein bereits extern erfolgter Mailversand lässt sich durch Restore nicht rückgängig machen; echte Aktionen bleiben in diesem Installationsstand deaktiviert.
