# Gesendet-Kopien nach SMTP-Annahme – v330, 10.11.2026

Implementiert und ausschließlich lokal mit synthetischen Daten geprüft. Kein reales Postfach, kein echter Mailversand, keine Zugangsdaten, keine Bankaktion und kein kostenpflichtiger Dienst verwendet. Keine Produktions-/STRATOfreigabe.

## Verhalten

- Vor SMTP werden die tatsächlich aufgebauten MIME-Bytes samt SHA-256 und dem ursprünglichen Gesendet-Ziel dauerhaft eingefroren. Die additive `private/migration_v330.sql` ergänzt dazu die Entwurfstabelle; vorhandene Nachrichten werden nicht nachträglich rekonstruiert. Absender, Date, Message-ID, MIME-Grenzen und Anhänge der Wiederaufnahme stammen aus diesen Originalbytes, unabhängig von späterer Konfiguration.
- Ein bereits akzeptierter Entwurf bleibt gesperrt. Gesendet-Recovery ruft niemals einen SMTP-Sender auf. `sending`/`uncertain` lassen sich weiterhin ausschließlich durch die vorhandene ausdrückliche manuelle Postfachentscheidung auflösen; SMTP wird auch dabei nicht automatisch wiederholt.
- Die neue optionale Schnittstelle `GszMailSearchRemote::findMessageId()` erweitert den vorhandenen nativen IMAP-Adapter. Sie prüft exakte Message-ID-Header, UIDs, gelöschte Treffer und die UIDVALIDITY vor/nach der Suche. Fehlgeschlagene oder unvollständige Suchen zählen niemals als Abwesenheit. Höchstens100 Kandidaten; mehrdeutige Message-ID-Header werden gesperrt. Andere Adapter behalten ihre bisherige Schnittstelle, können ohne Suchfähigkeit jedoch keine Recovery ausführen.
- Schon die ursprüngliche Gesendet-Ablage prüft bei einem suchfähigen Adapter, ob der Provider eine eigene Kopie angelegt hat. Ein eindeutiger Treffer wird übernommen; bei Suchfehlern/Mehrdeutigkeit erfolgt kein APPEND. Vor APPEND wird ein unklarer Kopierversuch dauerhaft markiert, damit auch Prozessabbruch/verlorene Bestätigung sichtbar bleiben.
- In `public/compose.php` startet ausschließlich ein authentifiziertes CSRF-geschütztes POST die Suchvorschau. Die Vorschau wird über PRG in der Session gehalten, läuft nach zehn Minuten ab und enthält keine MIME-Bytes. GET, Aktualisieren und das reine Anzeigen der Vorschau öffnen kein Postfach.
- Ein zweites ausdrückliches POST bestätigt die Entscheidung. Unmittelbar davor wird erneut gesucht. Ein inzwischen vorhandener eindeutiger Treffer wird ohne APPEND übernommen. Mehrere/gelöschte Treffer, Suchfehler, ein geänderter Zustand oder eine seit der Vorschau geänderte UIDVALIDITY sperren die Ablage. Eine fehlende Kopie nach Wechsel der ursprünglich gespeicherten UIDVALIDITY erfordert manuelle Postfachklärung. Ein eindeutiger Message-ID-Treffer kann nach ausdrücklicher Bestätigung in der neuen UID-Epoche übernommen werden.
- Per-Entwurf-Advisory-Lock serialisiert ursprünglichen Versand, Recovery und manuelle Versandklärung. Zwei separate PDO-Verbindungen wurden tatsächlich gegeneinander geprüft. Die Unklar-Markierung wird vor dem Netzwerkaufruf gespeichert. Bestätigte Kopien speichern Ziel-UIDVALIDITY/UID; Audit-Einträge enthalten Identitäten, keine Nachrichtentexte/Passwörter.
- Nach einem unklaren APPEND und erneut leerer Suche ist zusätzlich die bewusste Bestätigung einer möglichen verzögert sichtbaren Kopie erforderlich. Keine automatische Wiederholung, kein EXPUNGE.

## Tatsächlich bestanden

PHP8.4.24/MariaDB11.8.6, `/workspace/gsz-tools/bin/php`, lokaler Socket `/workspace/gsz-tools/db/mysql.sock`:

| Prüfung | Ergebnis |
| --- | --- |
| Neue Recovery-Datenbanksuite, frische `gsz_test_mailrecovery_final` |51 Assertions bestanden |
| Nativer IMAP-Adapter mit synthetischen PHP-Funktionsshims, ohne Netzwerk |9 Assertions bestanden |
| Echte `compose.php`/`bootstrap.php` über temporären Loopback-PHP-Server, frische `gsz_test_mailrecovery_httpfifth` |16 Assertions bestanden |
| Bestehende Mail-/Security-Logiksuite |25 Assertions bestanden |
| Bestehende unveränderte Mail-Datenbanksuite nach Runner-Integration, frische `gsz_test_mailrecovery_legacyfinal` |34 Assertions bestanden |
| Bestehender SMTP-TLS-Loopbacktest, Native+PHPMailer |16 Szenarien bestanden, keine externe Zustellung |
| Syntax der drei geänderten und drei neuen PHP-Dateien |6 Dateien bestanden |

DB51 umfasst insbesondere: APPEND speichert tatsächlich und verliert anschließend die Bestätigung; die Wiederaufnahme findet dieselbe Identität und erzeugt keine Dublette. Außerdem Originalbytegleichheit, unveränderliche akzeptierte Entwürfe/Anhänge, fehlende Unklar-Bestätigung, parallel erscheinende Kopie, Dubletten/gelöschte Treffer, UIDVALIDITY-Wechsel während/nach Suche, abgelaufene/fremde/veränderte Vorschau, Originalmanipulation, historische Originale ohne Snapshot, gesperrte SMTP-Stände, zwei konkurrierende Datenbankverbindungen, Migration auf befüllten Snapshots und Auditkette. Providerseitige automatische Gesendet-Kopien werden bereits beim ersten Ablageversuch übernommen.

HTTP16 prüft tatsächliche Anmeldung, anonyme Sperre, CSRF vor Netzwerk, POST/303/GET, keine GET-Netzwerkaktionen, bewusste Bestätigung, fehlende Edit-/Sendesteuerung bei akzeptiertem Entwurf und Replay-Sperre. Der HTTP-Test ersetzt ext-imap durch rein lokale Shims und simuliert HTTPS ausschließlich im flüchtigen Test-Router; er ist kein realer TLS-/IMAP-/STRATO-Abnahmetest. SMTP bleibt dabei serverseitig deaktiviert.

Die ersten HTTP-Harnessversuche scheiterten vor der Mailfunktion an der Testumgebung: der rohe PHP-Binärpfad enthielt die Wrapper-Erweiterungen nicht und die leere synthetische Datenbankpasswort-Umgebungsvariable wurde im Kindserver nicht erhalten. Der Harness nutzt nun `GSZ_TEST_PHP` und setzt das bekannte leere Testpasswort explizit. Der danach vollständig bestandene Lauf ist oben genannt; fehlgeschlagene Test-Datenbanken wurden nicht wiederverwendet.

## Wiederholung und Integration

Je Datenbanksuite vorher eine neue leere synthetische Datenbank mit Präfix `gsz_test_mailrecovery_` anlegen. Die Tests verweigern andere Namen und befüllte Datenbanken.

```sh
GSZ_TEST_DSN='mysql:unix_socket=/workspace/gsz-tools/db/mysql.sock;dbname=gsz_test_mailrecovery_neu' /workspace/gsz-tools/bin/php tests/test_mail_recovery_database.php
/workspace/gsz-tools/bin/php tests/test_mail_recovery_imap.php
GSZ_TEST_PHP=/workspace/gsz-tools/bin/php GSZ_TEST_DSN='mysql:unix_socket=/workspace/gsz-tools/db/mysql.sock;dbname=gsz_test_mailrecovery_httpneu' /workspace/gsz-tools/bin/php tests/test_mail_recovery_http.php
/workspace/gsz-tools/bin/php tests/test_mail_core.php
GSZ_TEST_PHP=/workspace/gsz-tools/bin/php python3 tests/test_smtp_loopback.py
```

Der Integrationsverantwortliche hat `migration_v330.sql` nach den übrigen neuen Migrationen in `private/migrate_gesamt.php` aufgenommen und den abschließenden Versionsnachweis erweitert. Dieser Teilauftrag selbst verändert den Runner und die Beispielkonfiguration nicht. Danach wurde die bestehende `tests/test_mail_database.php` unverändert auf der frischen synthetischen `gsz_test_mailrecovery_legacyfinal` ausgeführt:34 Assertions bestanden, einschließlich befüllter erneuter Gesamtmigration. Fortschritts-/Gesamtstatus/Paketmanifest werden vom Integrationsverantwortlichen aktualisiert.

## Grenzen

IMAP bietet mit APPEND und Message-ID-Suche keine transaktionale Exactly-once-Garantie. Eine verzögert sichtbare serverseitige Kopie oder ein fremder Client kann zwischen Suche und APPEND eine Dublette erzeugen. Deshalb werden Unklarheit und vorhandene Konflikte sichtbar und ein weiterer Versuch ausdrücklich bestätigt. Auch eine positive SMTP-Annahme ist keine Zustellgarantie.

Historische akzeptierte Entwürfe ohne eingefrorenes Versandoriginal/Ziel bleiben zur manuellen Postfachprüfung gesperrt. Originale werden aus Datenschutzgründen nicht in Vorschauen, Sessions oder Logs kopiert, liegen jedoch als zusätzliche private MIME-Bytes in der Datenbank und gehören in deren geschützte Sicherung. Größenlimit bleibt15 MB für MIME; entsprechende Datenbank-/Hostinggrenzen müssen real geprüft werden.

Serverseitige Entwurfssynchronisation wurde in diesem Stabilitätsauftrag nicht ergänzt; lokale Entwürfe und vorhandene Anhänge bleiben erhalten. Kein Live-IMAP, kein STRATO-Test, keine Last-/umfassende Browserabnahme, keine vollständige Sicherheits-/Produktivabnahme. Keine neue Bibliothek: vorhandener optionaler PHP-IMAP-Adapter wiederverwendet, PHPMailer7.2.0/LGPL-2.1 unverändert, keine zusätzlichen laufenden Kosten aktiviert.
