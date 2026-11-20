# Aktuelles Update: Abfall360

Für das neue Voll-/Updatepaket gilt [INSTALLATION-ABFALL360.md](INSTALLATION-ABFALL360.md): exakte Basis350 →360,84Tabellen, nur neue additive Migration360. Folgende Anleitung beschreibt den historischen vorherigen FINAL-Stand. Nicht alte Zielversionen ungeprüft als aktuellen Stand übernehmen.

---

# Sichere STRATO-Installation / Aktualisierung – FINAL, Schema 350

Lokal geprüfter Installationskandidat, keine erfolgte STRATO-Installation und keine Produktivfreigabe. Zuerst geschützte Testkopie, anschließend konkrete Freigabe. Vorhandene Rechnungen, Kunden, private Konfiguration, Originale und Inhaberzugang erhalten.

## 1. Das passende Paket

Vollpaket **GSZ-Zentrale-FINAL-STRATO.zip** enthält zgs-app/ mit allen Anwendungsdateien, Tests und Migrationen. Keine echte config.php, Uploads, Dumps, Mail-/Bankdaten oder Vault-Schlüssel.

**GSZ-Zentrale-FINAL-UPDATE.zip** ist ausschließlich der exakte Patch zur vollständigen bisherigen v1.0-Release-ZIP mit SHA2561f223a15a4996b8f3eb7fd83975474396afe9739107bd866e4100a743b0382e3 (Schema 340). Dateiliste und Hashes CHANGELOG-FINAL.md/DATEIMANIFEST-GSZ-FINAL.sha256. Nicht blind auf eine abweichende oder ältere Dateibasis anwenden. Keine Löschungen nötig.

Für die früher gemeldete produktive **Schema 330/76Tabellen**-Installation reicht dieser340-Dateipatch allein nicht. Dort das Vollpaket in eine getrennte Kopie entpacken, vorhandene private Konfiguration/Originale kontrolliert übernehmen und den getesteten330→350-Upgradeweg verwenden. Kein Zurückspielen älterer Schema-/Setupskripte, kein `--fresh` auf Bestandsdaten. Änderungen am echten Server erst gesondert freigeben.

## 2. Vor jeder echten Änderung sichern

Bestehende Dateien vollständig einschließlich private/config.php, Uploads/Belegoriginalen, Vault und Schlüssel sicher herunterladen/verschlüsselt außerhalb Webroot ablegen. Datenbank vollständig und konsistent exportieren (Struktur/Daten/Indizes, vorhandene Trigger berücksichtigen). Schreibzugriffe und Cron für die konsistente produktive Sicherung nur nach Freigabe kontrolliert pausieren. Auf getrennter Testdatenbank/Dateikopie wiederherstellen und Originalhashes, Inhaberzugang und Geschäftsdatensummen vergleichen. SHA256 der tatsächlichen Sicherung und Zeitpunkt festhalten. Lokale Test-Restoreprobe ersetzt diese Sicherung nicht.

Niemals Backup-ZIPs/Dumps im Webverzeichnis ablegen. Keine Zugangsdaten, DB-Exporte oder Postfachinhalte im Chat senden. Bereinigtes Release und separate Sicherung sind verschiedene Dinge: Das Release enthält absichtlich keine privaten Originale.

## 3. Hosting tatsächlich prüfen

Tarif, SFTP, PHP-CLI/SSH bzw. unterstützte PHP-Aufrufe, Cron und getrennte Testdatenbank/Testsubdomain im STRATO-Kundenbereich feststellen. PHP 8.2+64Bit, Maria DB10.6+ mit Inno DB/utf8mb4. Lokal8.4.24/Maria DB11.8.6 getestet, gemeldetes STRATO8.4.27 nicht hier gemessen. My SQL ist wegen der bestehenden Maria DB-DDL nicht freigegeben; bei My SQL-only nicht improvisieren.

Benötigt: PDO/My SQL, mbstring, iconv, fileinfo, openssl, session, json/hash/filter/ctype/zlib; Sodium für den Vault. **Native IMAP** für den vorhandenen Mailadapter, **DOM** für CAMT. Ohne DOM funktioniert neutraler CSV-Import; CAMT bleibt ausdrücklich BLOCKED. Native IMAP ist ab PHP 8.4 separat und darf nicht vorausgesetzt werden. Keine Node-/Python-Laufzeit auf STRATO nötig.

Document Root der Testdomain muss ausschließlich `zgs-app/public/` sein. private/, Tests, Konfiguration, Originale, Logs und Backups dürfen nicht per HTTP erreichbar sein. Falls die Domainzuordnung das nicht sicher erlaubt: BLOCKED. Nicht auf einen möglicherweise unwirksamen .htaccess-Schutz vertrauen. HTTPS vor Anmeldung; App verweigert HTTP. Sessionverzeichnis privat, beschreibbar,0700bevorzugt; CLI und Web-PHP getrennt prüfen. PHP-Upload-/Speichergrenzen nach tatsächlicher Umgebung einstellen, App-Importlimits bleiben zusätzlich wirksam.

## 4. Dateien und private Konfiguration

SHA256SUMS-GSZ-FINAL.txt vor Upload prüfen, vollständig sicher entpacken. Keine Symlinks/ZIP-in-ZIP. Bei Update nur die dokumentierten geänderten/neuen Dateien ersetzen; vorhandene private config.php, .env, Uploads, Logs, Vault/Schlüssel niemals überschreiben/löschen. Bei Vollpaket getrennt installieren und private Daten kontrolliert übernehmen, nicht in den ausgelieferten ZIPs nach Zugangsdaten suchen.

Konfiguration aus private/config.example.php nur für eine neue Kopie anlegen, außerhalb public. Alternativ absoluter externer Pfad über GSZ_CONFIG_FILE. Keine Symlinks,0600bevorzugt;0640nur bei tatsächlich erforderlicher Hostinggruppe. Niemals durch Freigabe für „alle“ reparieren. Datenbankwerte und Postfachzugang ausschließlich sicher auf dem Server eingeben. Vorhandene Inhaberkennwörter bleiben erhalten. Mailversand/Routing standardmäßig deaktiviert.

Mit tatsächlich verfügbarem CLI-PHP, aus zgs-app/:

```sh
php private/secure_config.php --check
php private/system_check_cli.php --json --web-root=/ABSOLUTER/PFAD/zgs-app/public
```

Rechtewerkzeug auf autorisierter Testkopie: `php private/secure_config.php --fix --confirm`. Ändert ausschließlich auf0600, liest keine Inhalte, rotiert keine Kennwörter. Falschen Speicherort/Eigentümer vorher klären. Öffentlich gewordene Kennwörter/Tokens sicher beim Anbieter rotieren, temporäre Diagnosedateien entfernen; in dieser Sitzung wurde nichts produktiv rotiert.

## 5. Bestandsupdate mit SSH/CLI

Erst Sicherung/Restore/Staging und Wartungsfenster nachweisen. Andere Schreibzugriffe/Cron pausieren. Nach vollständigem Upload:

```sh
php private/install.php --upgrade --backup-confirmed
```

Der Installer prüft Code/Assets gegen private/release_integrity.json und verweigert fehlende/abweichende Dateien. Historische protokollierte Migrationen werden übersprungen, Lücken/Parallelmigrationen/unbekannte Rhythmustypen abgewiesen.330→350 führt nur fehlende340/350 aus,340→350 nur350. Benutzerdefinierte ENUM-Werte werden nicht durch die Erweiterung um „daily“ vernichtet.350 ergänzt vier SEO-Tabellen und nullable Kontoidentitätshashes/Indizes, keine bestehenden Datensätze werden umgeschrieben. Ergebnis82Tabellen.

DDL kann teilweise bereits wirksam sein, wenn ein Lauf abbricht. Ursache/Struktur prüfen, nicht behaupten, alles sei zurückgerollt. Ein noch nicht protokollierter idempotenter Schritt kann nach Prüfung wiederholt werden; bereits protokollierte beschädigte Strukturen werden nicht blind repariert. Keine historischen SQL-Dateien einzeln erneut ausführen. ROLLBACK-FINAL.md.

## 6. Bestandsupdate ohne SSH

Dieser Weg benötigt einen **bestehenden funktionierenden Inhaberzugang** und vollständige Vorgängerstruktur. Kein anonymer Webinstaller/Passwortreset.

1. Auf geschützter, gesicherter Testkopie alle Dateien vollständig hochladen; Codeintegrität wird vor der Migration geprüft.
2. Andere Sitzungen/Schreibzugriffe und Cron für das Wartungsfenster stoppen. Den globalen maintenance-Modus während dieses Browserwegs nicht aktivieren: Er sperrt auch die Anmeldung/Update-Seite. Der Browserweg ersetzt keine betriebliche Exklusivitätssicherung.
3. Im geschützten Serverkonfigurationseditor/SFTP vorübergehend `allow_web_update => true` setzen. Standard istfalse. Keine URL-Tokens oder Passwörter in Java Script.
4. Per HTTPS anmelden, Einstellungen → Geschütztes Bestandsupdate öffnen. Tatsächlichen Backup-SHA256 eintragen, Dateien-/DB-Sicherung, Restoreprüfung und exklusives Fenster bestätigen; eigenes Inhaberpasswort erneut eingeben.
5. Ausführen, Ergebnis/Systemcheck prüfen, Flag sofort wieder auffalse setzen. Keine erneute Migration ohne Ursachenprüfung bei Fehlern.

Hash/Checkboxen sind die dokumentierte Sicherungserklärung des Inhabers, keine automatische Prüfung des externen Backups. Anmeldung, Ratelimit, CSRF, erneute Passwortprüfung, Serverflag, Codeintegrität, Ledger/DB-Sperre bleiben erforderlich.

**Neue leere Installation:** nur `php private/install.php --fresh`, danach `private/admin_setup.php` mit sicher gesetztem GSZ_SETUP_EMAIL und neuem eigenen Passwort über Standardeingabe. Fresh verweigert jede nichtleere Datenbank; keine Testkonten werden automatisch erstellt. Ohne vorhandenen Inhaber und ohne einen vom Tarif sicher unterstützten privaten PHP-/CLI-Ausführungsweg bleibt die Ersteinrichtung BLOCKED. Keinen öffentlichen Token-/SQL-/Admininstaller improvisieren, keine Fixtures auf STRATO ausführen.

## 7. E-Mail/Kalender/Bank/Cron einrichten

Details INTEGRATIONEN-FINAL.md und BETRIEB-FINAL.md. IMAP imap.strato.de993SSL/TLS, SMTP smtp.strato.de465SSL/TLS;587STARTTLS nur beim tatsächlich bestätigten Profil. Vollständige eigene Mailadresse/Passwort privat konfigurieren, native IMAP prüfen. `mail_check_cli.php --check` und nach Einrichtung `--imap` versenden nichts. Gesendet-/Archiv-/Papierkorbzuordnung aus echter Ordnerliste wählen, nicht vermuten. Reale Testmail erst nach Freigabe konkreter Nachricht/Empfänger; SMTP-Annahme, tatsächlichen Empfang und Gesendet-Kopie gesondert nachweisen.

Amtliches Abfallnavi, tatsächliche Adresse/Jahr/Fraktionen wählen, ICS/CSV zuerst mit Vorschau/fachlicher Freigabe importieren. Keine erfundenen Abo-URLs. Fontänen-/Lippstädter-Ortskonflikt prüfen; normale pausierte Routinen nicht aktivieren. Bank nur lokale Vorschau/Bestätigung, EUR/stabile Referenzen; Kontobindung und mögliche Formatwechsel-Dubletten prüfen. Keine PIN/TAN/Überweisungen.

## 8. Abnahme und Produktivfreigabe

Browser-Systemcheck und CLI getrennt, Login/Logout, Inhaberbestand, Objekt/Notiz/Dokument, Erzeugen ohne Dubletten, Treppenhaus Mo–Mi, Woche/Fixtermin13–14:30, Abschließen/Verschieben/Neuanmeldung/Persistenz prüfen. Beide Volksbank-Slots und geschäftliche Bank bleiben getrennt. Alte PDFs/Dokumenthashes und Summen vergleichen. Auf echtem i Phone/Safari und Desktop testen. Mail-Empfang/-Versand/Gesendet und echten Cronlauf dokumentieren, Backup zurückspielen können.

Erst nach kritischer Abnahme konkrete Produktivänderung mit frischer verifizierter Sicherung gesondert freigeben. Keine Produktivdaten überschreiben.0Systemcheck-Blocker ist eine technische Vorprüfung, keine steuerliche/Go BD-/Live-Gesamtfreigabe. Diese Lieferung hat gszintern.de nicht angesprochen oder verändert.
