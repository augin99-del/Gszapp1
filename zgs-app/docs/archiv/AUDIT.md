GSZ-ZENTRALE FINAL / SCHEMA350 – 12.11.2026

Aktueller Einstieg: INSTALLATION-STRATO.md, TESTSTATUS-FINAL.md, MODULE-STATUS.md,
CHANGELOG-FINAL.md und INTEGRATIONEN-FINAL.md. Vollpaket und Update sind bereinigt;
keine tatsächlichen Zugangsdaten oder privaten Originale enthalten.

Schema350 hat82Tabellen. Bestehende330/340-Datenbanken nur nach verifizierter
Sicherung auf Testkopie über den geprüften Upgrade-Runner aktualisieren.
Fresh verweigert nichtleere Datenbanken. Keine alten SQL-/Setupanleitungen
nachfolgend erneut auf Bestandsdaten ausführen. Kein ungeschützter Webinstaller.

Das Dashboard erzeugt keine Aufgaben allein durch Öffnen. Expliziter sicherer
Owner-POST oder tatsächlich eingerichteter CLI-Cron erzeugt Aufgaben und einen
unbestätigten Wochenvorschlag. Produktivdeployment und echte Testmails separat
freigeben. Die tatsächliche STRATO-/Postfachabnahme steht aus.

Die folgenden älteren Inhalte bleiben ausschließlich als Historie erhalten.

---------------- HISTORIE ----------------

# GSZ-App – technischer Erstcheck (Version 1.9.1)

**Status: Entwicklungsfassung, nicht produktionsreif.** Ausgangspunkt ist das vorhandene ZIP v1.9. Es wurden weder eine STRATO-Installation noch eine MariaDB-Integration noch ein SMTP-Test durchgeführt.

## Durchgeführt

- Sämtliche PHP-Dateien mit `php -l` auf Syntax geprüft.
- Zwei ausführbare PHP-Smoke-Tests hinzugefügt (`tests/test_invoice_pdf.php`, `tests/test_money.php`).
- `private/invoice_pdf.php` so abgesichert, dass bei mehr als 52 Textzeilen eine Exception statt stiller Abschneidung erfolgt. Das **verhindert einen bekannten Datenverlust**, löst aber noch nicht das Mehrseiten-PDF-Problem.
- `.gitignore` zum Schutz lokaler Zugangsdaten, Uploads und Sicherungen ergänzt.
- ZIP auf Integrität geprüft.

## Kritische offene Punkte (vor Echtdaten zu beheben)

1. **Rechnungen:** Der PDF-Generator ist nur eine einfache Einseiten-Ausgabe; keine verlässliche Mehrseiten-Ausgabe, keine vollständige Zeichensatzunterstützung, keine fachliche Validierung der Pflichtangaben oder E-Rechnung. Auch kurze Rechnungen müssen visuell geprüft werden. Rechnungsversand nicht produktiv freigeben.
2. **Ersteinrichtung:** `public/setup.php` führt das Basisschema per Semikolon-Splitting aus. Es gibt keinen durchgängigen getesteten Migrationslauf für alle Versionsstände. Einrichtung und Updates erst auf einer Testdatenbank prüfen.
3. **Datenbank:** Keine echte MariaDB-Integration getestet; Fremdschlüssel, Rechnungsstatus und Migrationen müssen end-to-end getestet werden.
4. **E-Mail:** SMTP/IMAP noch nicht mit STRATO getestet. Versand kann nach Serverannahme dennoch scheitern; Gesendet-Synchronisierung fehlt.
5. **Dateien:** Upload- und Downloadfunktionen benötigen Sicherheitstests (Zugriff, Dateiformate, Dateigrößen, Pfadkonfiguration, Datenschutz und Backup).
6. **Betrieb:** HTTPS, Backup/Restore-Test, Rollen/Rechte, Login-Rate-Limits, Audit-Protokolle und Datenschutzkonzept fehlen bzw. sind unvollständig.
7. **GoBD/E-Rechnung:** Keine bestätigte Konformität; XML-Prototyp nicht als validierte XRechnung/ZUGFeRD verwenden.

## Ausführen

```bash
find public private tests -name '*.php' -print0 | xargs -0 -n1 php -l
php tests/test_invoice_pdf.php
php tests/test_money.php
```

## Nächste Prioritäten

1. Reproduzierbare Installation in separater Testdatenbank, Migrationsskripte testen.
2. Rechnungsmodul mit belastbarem PDF-Generator und korrektem Status-/Korrekturablauf neu absichern.
3. Login- und Upload-Sicherheit mit Integrationstests prüfen.
4. Erst danach HTTPS/STRATO-Testbetrieb mit Testdaten; keine produktiven Kundendaten oder Rechnungen.

## Version 1.9.2
FK-Typen in v16/v19 korrigiert; sicherer manueller Neuinstallationspfad mit gebündeltem SQL, setup.php fail-closed, statischer Schema-Test. Noch kein MariaDB-Integrationstest.

## Lokaler Folgeaudit 09.11.2026 – Phase 0/1

Siehe IMPLEMENTIERUNGSPLAN.md und TESTBERICHT_PHASE01.md. Entgegen alten Annahmen ist setup.php im tatsächlich gelieferten ZIP NICHT vorhanden. Das v192-Schema ließ sich lokal auf MariaDB 11.8.6 importieren. Phase-1-Migration und Datenbanktests sowie Browser-Smoke-Tests sind jetzt ausgeführt; keine Bestätigung der produktiven Struktur oder STRATO-Installation.

Originalarchiv enthält befüllte private/config.php und zwei eingebettete ZIPs mit config.php. In bereinigter Arbeitskopie/Übergabe entfernt. Bei früherer Offenlegung Zugangsdaten rotieren. Serverkonfiguration/Umgebungsvariablen vorgesehen, Versandsperre standardmäßig aktiv.

Offen bleiben unter anderem serverweiter Login-Schutz, Session-Timeout, umfassende Uploadtests/Scan/EXIF-Datenschutz, Foto-Speicherreferenzen aus der Datenbank strenger validieren, Auditprotokolle für Stammdaten/Regeländerungen, vollständiger Backupbetrieb. Vorhandene SQL-/SMTP-Fehlerlogs auf personenbezogene Details prüfen. Neue Phase-1-UI gibt keine PDO-Details aus. Kein Archiv oder Rechnungsbeleg wurde gelöscht. Keine vollständige Security-, GoBD-, Steuer- oder Produktionsfreigabe.

## Phase 2 (09.11.2026)

Neue Planung verwendet bestehende Admin-Session, CSRF bei allen POST-Aktionen, Prepared Statements, HTML-Escaping und POST-Redirect-GET. Vorschläge mit Adminbindung, Token, Bestandshash, 2h-Frist und transaktionaler Übernahme; Wiederholung ohne Dubletten. Migration additiv/wiederanlaufbar, kein Eingriff in Rechnungen/Archive. Neue Planungsblocker sind entfernbar; Belege bleiben unverändert. Grenzen: keine parallele Belastungsprüfung, harte Planungslimits, noch keine automatische Vorschlagsaufbewahrungsregel. Test-/Installationsberichte Phase02 ergänzen die vorherigen Berichte, keine Produktionsfreigabe.

## Phase 3 – lokaler Stand 09.11.2026

Archiv sicher isoliert, Offline-Import mit UTF-8/MIME/Endung/Größen-/Zeilenlimits und expliziter Zuordnung. Kein XML-/XXE-Pfad oder Abfall-URL-Abruf. Import- und Routenaktionen Admin/CSRF/Prepared Statements/Escaping/PRG. Token/Fingerprint/Transaktionen schützen Übernahmen; Quellenänderungen lassen alte Aufgaben bestehen. Routingprovider nur ausdrücklich serverseitig freigegeben, HTTPS ohne Redirects, Timeout/Antwort-/Punktlimit, Cache und Fallback; keine Dashboard-Remoteanfragen. DB-Loader setzt technische Zeitstempel UTC; historische DATETIME-Zonen vor Rollout prüfen. Testbericht Phase03 dokumentiert Sicherheits-/Betriebsgrenzen; keine vollständige Security- oder Produktionsfreigabe. Originalgeheimnisse nicht in Arbeitskopie oder Lieferpaket übernommen.


## Ergänzung Gesamtstand 09.11.2026

Loginbremse, begrenzte Sessions, neue CLI-Ersteinrichtung, sichere Mail-/Finanzdienste, Upload-/Downloadprüfungen, Wartung und verkettete Auditereignisse ergänzt. Neue SQL-Schritte v230/v240/v250; lokal nachgewiesene Ergebnisse in TESTBERICHT_GESAMT.md. Vorhandene Risiken gelten weiter, soweit nicht konkret belegt behoben. SICHERHEIT_DATENSCHUTZ.md nennt Kontrollumfang und Grenzen; Hashkette ist kein Schutz gegen privilegierten DB-Administrator. Kein Virenscan, keine vollständige Pentest-/GoBD-/Datenschutz-/STRATO-Abnahme. Echte Zugangsdaten aus Altarchiv bleiben ausgeschlossen; bei tatsächlicher Offenlegung außerhalb geschützter Ablage Rotation veranlassen. Keine Live-Aktionen ausgeführt.
