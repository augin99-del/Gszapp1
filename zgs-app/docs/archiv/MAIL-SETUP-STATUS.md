# Grafische STRATO-Mail-Einrichtung / PHP-IMAP-Fallback –13.11.2026

## Tatsächlicher Stand

Bestehende Mailtabellen, sichere MIME-/Anhangsverarbeitung, Entwürfe, Antworten/Weiterleitung, Suche, Flags und Gesendet-Kopierwiederherstellung bleiben bestehen. Native GszImapRemote und PHPMailer wurden nicht neu geschrieben. Für Hosting ohne natives IMAP ist GszEngineImapRemote hinzugefügt: gepflegter MIT-Protokollkern DirectoryTree/ImapEngine1.25.9, sämtliche37Abhängigkeiten mit Autoloader vollständig gebündelt. Kein Composer/SSH oder separates Mailprogramm auf STRATO erforderlich. PHP>=8.2/OpenSSL/mbstring/iconv/ctype/json; ZIP/nativeIMAP nicht notwendig. Lizenzliste private/vendor/imapengine/BIBLIOTHEKEN.md, lockfile inklusive exakter Versionen. Am13.11.2026 Composer-Audit ohne gemeldete Advisories/abandoned; keine dauerhafte Sicherheitsgarantie.

## Schnittstellen für den geschützten Einrichtungsassistenten

- `gszSetupMailValidate($input,$existing=[])`: feste STRATO-Hosts993/465 mit verpflichtendem TLS, validierte Postfach-/Absenderadresse, Signatur, Gesendet/Papierkorb/Archiv. Standardadresse aus Nutzerauftrag info@gebaeudeservice-zentrum.de. Postfachpasswort nur als Serverwert, nicht trimmen oder ausgeben; leeres Feld erhält vorhandenes Passwort. Ohne ausdrücklich gesetztes mail_enabled bleibt das Postfach deaktiviert, ein Passwort ist dann nicht erforderlich. Ergebnis setzt allow_mail_send=false; Versandfreigabe bleibt separater bewusster Schritt.
- `gszSetupMailProbe($config)`: nur nach Authentifizierung/CSRF-POST aufrufen. Prüft IMAP-Anmeldung, tatsächliche Ordner und UIDVALIDITY sowie SMTP-TLS/EHLO/Authentifizierung. Führt niemals MAIL FROM/RCPT/DATA aus, sendet keine Nachricht. Liefert PASS/BLOCKED, konkrete sichere AUTH/TLS/TIMEOUT/CONNECTION-Fehlercodes, keine rohen Bibliotheksausnahmen/Secrets. Deaktivierte Einrichtung macht überhaupt keine Netzwerkaufrufe.
- `gszMailRemote($config)`: mail_adapter=auto verwendet vorhandenes natives IMAP, andernfalls gebündelten Adapter. engine/native können serverseitig bewusst gewählt werden. Rootcontroller/Automation nutzen dieselbe Factory; kein zweites Maildatensystem.

Private atomare Konfigurationsspeicherung außerhalb des öffentlich zugänglichen Verzeichnisses übernimmt der zentrale Einrichtungsassistent. Dieser Mailhelper schreibt bewusst keine konkurrierende Konfigurationsdatei.

## Schutz und Grenzen

TLS1.2/1.3, Peer-/Hostnameprüfung und SNI, keine deaktivierte Zertifikatsprüfung und keine Debugtranskripte. Zeilen-, Literal-, Antwort-, Ordner-/UID-/Nachrichten-/Batchgrenzen und Socket-/Lese-/Schreibzeitlimits. Zunächst Größenprüfung vor Bodyabruf. PEEK schützt Gelesenstatus. Epochenschutz, genaue UID/Message-ID und bestehende ursprüngliche MIME-Bytes bleiben erhalten. Kein EXPUNGE. Verschieben über präzises UID MOVE; falls der Server MOVE nicht unterstützt, verständlicher Fehler und manuelles Verschieben im bisherigen Mailprogramm, keine riskante COPY/EXPUNGE-Simulation. Standardoberfläche arbeitet weiter mit vorhandenen Serverordnern; ein umfassender grafischer CREATE/RENAME/DELETE-Ordnereditor ist in diesem Arbeitspaket nicht ergänzt.

## Ausgeführte Tests

- `php tests/test_setup_mail.php`: PASS33Assertions, inklusive echter gebündelter Parser/IMAP-Befehle gegen synthetische Antworten, Tonnen-/Bank-/Rechnungsdaten nicht beteiligt.
- `GSZ_TEST_PHP=/lokaler/test/php python3 tests/test_setup_mail_loopback.py`: PASS7lokale TLS-Szenarien auf loopback993, keine externe Zustellung oder echtes Postfach. Vertrauenswürdiges TLS, falscher Zugang, Größenlimit vor Bodyabruf, unvollständige Bodyantwort, UIDVALIDITY-Wechsel, nicht vertrauenswürdiges Zertifikat, falscher Hostname; vor bestätigtem TLS keine Zugangsdaten.
- `php tests/test_mail_core.php`: PASS25Assertions.
- `php tests/test_mail_recovery_imap.php`: PASS9native IMAP-Shimprüfungen.
- Gepinnter Vendor auf lokalem PHP8.4 syntaktisch geprüft, Ergebnis im Gesamtbericht übernehmen.

Nicht live geprüft: echte STRATO-IMAP/SMTP-Anmeldung, tatsächlicher Empfang/Versand, Gesendet-Ordner und Tarif-Netzlimits. Kein echtes Passwort bekannt/angefordert/verbaut, keine reale Nachricht gesendet. Authentifizierungsprobe ist ausdrücklich kein Beweis tatsächlicher Empfangs-/Versandfunktion; das benötigt gesonderte reale Abnahme und Sendefreigabe.
