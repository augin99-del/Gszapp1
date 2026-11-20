# Ergänzung Abfall360

Öffentlicher Kalenderabruf nur an fest geprüften HTTPS-Host/Port443, öffentliche DNS-Adressen, Pinning/TLS-Prüfung, keine Redirects/Kompression/Geheimnisse, begrenztes JSON/ICS und Cache außerhalb des Webroots. Keine externen Anfragen beim Dashboard-GET. Nur POST/CSRF oder CLI; bestehende sichere Importe/Transaktionen/Identitäten erhalten. Straße-/Hausnummernkennungen werden erst nach bewusster Serviceaktivierung an den amtlich verlinkten Dienst übermittelt, keine Tracking-/Bank-/Maildaten. Herstellerbedingungen/Schnittstellenstabilität und STRATO-Netzabnahme siehe QUELLEN_ABFALL.md. Paketprüfung siehe RELEASE-PRUEFUNG-ABFALL360.md. Keine produktiven Konfigurationen oder echten Kalender-/Konto-/Maildaten in Paketen.

# Aktueller FINAL-Stand 12.11.2026 / Schema350

Maßgeblich: INSTALLATION-STRATO.md, TESTSTATUS-FINAL.md, MODULE-STATUS.md, CHANGELOG-FINAL.md, INTEGRATIONEN-FINAL.md und CODEX_FORTSETZUNG.md. Neu: täglicheRegeln, integriertesDashboard/Navigation, CAMT/Kontentrennung/Review, OfflineSEO, geschütztesBestandsupdate/Dateiintegrität. Schema350/82Tabellen; keineProduktivinstallation, echteMail/Bank/Cron/Objektkalender weiter nichtnachgewiesen. Aussagenzu330/340nachfolgend sindHistorie. LiveSEO-/GSC-/GBP-Adapter,OCRundvalidierteE-Rechnung nichtalsfertigausgeben.

---

# Aktueller Stand Version1.0 / Schema340

Maßgebliche aktuelle Einrichtung: DEPLOYMENT.md, CRONJOBS.md, ROLLBACK.md, TESTSTATUS.md, INTEGRATIONEN_V1.md und OFFENE_PUNKTE.md. Schema330/76Tabellen in nachfolgender Historie gilt nur für ältere Auslieferungen; jetzt additive340/78Tabellen. Keine echte STRATO-/Mail-/Produktivfreigabe. Frühere Aussagen nicht als Beleg aktueller externer Einrichtung verwenden.

---

# Ergänzung Installationsstand331

Kein anonymer Installer/Diagnoseendpunkt. Systemcheck im Browser nur nach Inhaberlogin, ohne Konfigwerte/Geschäftszeilen/Granttexte/externe Verbindung; keine Geschäftsdatenmutation. CLI-Fresh nur komplett leere DB, Versions-/utf8mb4-Prüfung und Installationssperre; bestehende Logins bleiben erhalten. Keine pauschale Wiederaufnahme partieller Fresh-DDL. Speicherprobe explizit, privat und flüchtig. Bekannte Dateinamenwarnung behoben, Dokumentenupload protokolliert festen Fehlercode. Session-/CSRF-/XSS-/Download-/HTTP-/Logouttests lokal bestanden. Keine echte Hosting-/Safari-/Penetrations-/Fachabnahme. Bereinigte Pakete schließen Config/.env/Uploads/Vault/Schlüssel/Dumps/Backups aus; Source-Lizenzdateien erhalten. Keine neuen externen Datenflüsse oder Kosten.

---

# Ergänzung v310/v320/v330

Private Mieterdaten ausschließlich authentifizierter Inhaber, eigene Tabellen und geschützte Originale außerhalb public, keine Mieter-/Mitarbeiterfreigaben. Eindeutige Konten-/Quellen-/Zuordnungsidentitäten, Transaktion+Estate-Mutex, append-only Zahlungskorrekturen, eingefrorene Abrechnungsversionen/PDFs, Hashprüfung und Audit. Download-IDs/Dateinamen validiert, keine frei übergebenen Dateipfade, CSV-Formelschutz beim Export, CSRF/PRG für Mutationen. Größen-/Typ-/Listenlimits dokumentiert ARCHITEKTUR_IMMOBILIEN.md. Kein Virenscan/WORM/gesetzlicher Revisionssicherheitsnachweis.

Eigentümer-Beraterexport enthält sämtliche beteiligten Mieter; an einzelne Mieter nur jeweilige Einzel-PDF. Exportzugriff ist kein Ersatz für sichere Übertragung/Aufbewahrung. Datenbank und private/uploads/estate gemeinsam privat sichern. Keine echten privaten Gebäudedaten/Bankdateien/Originale oder Testuploads im Quellpaket.

Mail330 archiviert akzeptierte MIME-Originalbytes in der geschützten DB; Backups entsprechend vertraulich behandeln. Keine SMTP-Wiederholung zur Gesendet-Recovery. Quellpakete werden auf bekannte ursprüngliche Secretwerte, Schlüssel-/Tokenmuster, gesperrte Pfade und Archive geprüft; ein automatischer Scan beweist keine umfassende Geheimnisfreiheit beliebiger Geschäftstexte. Ausgelieferte Tests enthalten nur synthetische Daten.

---

# Sicherheits-/Datenschutzstand

Kein Produktivzugriff, keine echte Mail, kein Bank-/Routingkonto und keine kostenpflichtige API in dieser Sitzung benutzt. Öffentliche tFPDF-/Lizenzquellen zur Abhängigkeit gelesen; keine Geschäftsdaten dorthin übertragen. Ausgangsarchiv auf Zip-Slip/Symlinks/Secrets geprüft. Lieferpakete werden auf private Originalwerte, Pfade, Konfigurationen, Uploads, Dumps und Backup-Archive gescannt; Manifest/Integrität/Rekonstruktion separat geprüft.

## Implementierte Kontrollen

- HTTPS-Zwang, secure/httponly/SameSite=Strict, Session-Regeneration, Idle-/Absolute-Limits, dauerhafte gehashte E-Mail/IP-Bremse, Wartungsmodus, Prepared Statements, CSRF und PRG für neue Mutationen, Escaping, unterdrückte Fehlerdetails/Exceptionargumente im Web.
- Alle neuen Controller verlangen Admin-Session. Ein Inhaber, keine fingierten Mandanten-/Mitarbeiterrollen. CLI-Erstadmin schützt existierende Konten.
- Private Mail-/Belegdateien, content-/Originalhash, zufällige bzw. gehashte Namen, MIME/Größen-/Bildgrenzen, authentifizierte Downloads mit attachment/nosniff; Speicherreferenz-/Symlinkprüfungen verbessert. Keine PHP-Ausführung/HTMLdarstellung aus Uploads.
- MIME ohne aktives HTML/Remote-Tracking; Header-/Empfänger-/Grenzen-/Zeichensatzvalidierung. Native TLS-Zertifikatsprüfung, feste serverseitige Hosts, begrenzte Timeouts/Batches. Keine frei eingebbaren Proxy-/Bank-/Mail-Endpunkte.
- Zahlungen und Quellenübernahmen transaktional/idempotent mit eindeutigen Herkunfts-/Requestidentitäten. Unsichere SMTP-Phase wird gesperrt, nicht automatisch wiederholt. Manuelle Klärung protokolliert.
- Audit mit Hashkette/Mutex, Metadaten und IDs statt Bankbeträgen/Mailtexten im allgemeinen Systemaudit. Fachliche Tabellen enthalten selbstverständlich autorisierte Geschäftsdaten. Keine sensiblen neuen Servicefehler im Systemlog. Legacy-Logs/Betriebsserver vor Nutzung zusätzlich prüfen.
- CSV-Export gegen führende Formelzeichen über bestehendes invCsvCell geschützt; ZIP-Ausgabe nur kontrollierte Pfade, Größenlimits und Hashprüfliste. Kein XML-XXE-Pfad im Abfallimport, keine ungeprüften Bank-XML-Parser.

## Grenzen / Datenschutzpflichten

Kein Penetrations-/Last-/Paralleltest und keine GoBD-/Rechtssicherheits-/Produktionszusage. Hashkette allein schützt nicht gegen einen DB-Administrator, der sie neu berechnet; keine externe Verankerung/WORM. Keine vollständige Verfahrensdokumentation. Anhänge/PDFs können trotz MIMEprüfung schädliche Inhalte enthalten; kein Virenscan. Originalfotos können EXIF/GPS enthalten, kein verpflichtendes Tracking/Fotografieren.

Mailcache speichert berufliche Inhalte/Anhänge, Entwürfe und historische inaktive UID-Versionen privat. Bank-/Belegtabellen enthalten Finanzdaten. Aufbewahrungs-/Löschkonzept, Backupverschlüsselung, Zugriff und Zuständigkeiten müssen festgelegt werden. Noch keine automatische Dateibereinigung/Retention, um Originale nicht versehentlich zu löschen. SMTP-/IMAP-/Routingfreigaben erlauben spätere externe Datenflüsse: Inhalte an Mailprovider, nur geprüfte Koordinaten an Routingdienst; kein Datenfluss standardmäßig. Banking online nicht verbunden.

Loginbremse begrenzt pro E-Mail/IP; kein verteilter WAF-/2FA-Ersatz. Dateirechte/Webroot/HTTPS/Serverlogs müssen auf STRATO geprüft werden. Drittbibliothek tFPDF unverändert und gepinnt, Lizenz/Schrift im Paket; kein vollständiger Supply-Chain-/CVE-Audit. PDF-Schriftcache ausschließlich private/uploads, nicht im ausgelieferten Source.

Tests mit ausschließlich synthetischen Kunden/Postfächern/Kontoaliasen und bestehenden ausdrücklich gewünschten Objektvorlagen. Keine produktiven Zugangsdaten anfordern. Testpasswörter in Tests sind eindeutig synthetisch, niemals als echten Adminzugang übernehmen. Vorhandene private/config.php und originale eingebettete Backups wurden nicht übernommen. Neue Installation verlangt eigenen sicheren Admin über geschütztes Serververfahren.

Bei früherer Veröffentlichung tatsächlicher Original-Zugangsdaten bzw. öffentlich erreichbarer alter ZIP-Backups betroffene Werte rotieren; diese Sitzung behauptet keine beobachtete Offenlegung. Aktuelle Übergabe enthält keine Originalgeheimnisse.


## Fortschreibung v260

PDF-Originale neuer ausdrücklicher Ausstellungen werden außerhalb des Webroots in private/uploads/invoices mit SHA-256-Dateinamen gespeichert. Nur authentifizierter Download, Prüfsumme/Länge vor Ausgabe; kein automatischer Ersatz beschädigter Originale. Neue Schemafelder nullable, keine historische Nachbefüllung. Appseitige Unveränderlichkeit ist kein Schutz vor Serveradministratoren und kein WORM/GoBD-Nachweis. Datei-/DB-Sicherung gemeinsam durchführen. Hashdateien nach Rollback können unreferenziert verbleiben; keine automatische Löschung ohne Referenzprüfung.


## SMTP/Vault-Prüfung bei Wiederaufnahme

PHPMailer-LGPL-Komponente unverändert gepinnt, Adapter prüft TLS/Hostname/587, Debug aus. Loopback-Tests bestätigen Annahme, Ablehnungen, fehlendes STARTTLS, Zertifikatsfehler und unklare DATA-Ausgänge; keine Zustell-/STRATOabnahme.

Optionaler libsodium-Vault authentifiziert verschlüsselte Konfiguration; Schlüssel separat0600 außerhalb public, keine Chat-/Paketübergabe. Keine Geheimnisse aus ursprünglichen ZIPs übernommen. Kein Schutz vor kompromittiertem Serveradministrationszugang behauptet; sichere Schlüssel-/Backupverwaltung weiterhin Betreiberaufgabe.
