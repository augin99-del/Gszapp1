# Ergänzung nach integriertem v320-Paket

Neuester Kern-DB-Lauf42 Assertions bestanden, Mathematik9, Immobilien-Browser27. Abrechnung jetzt tatsächlich integriert: TESTBERICHT_NEBENKOSTEN.md (DB32/Kern186/Browser17/Restore76Tabellen). Frühere Aussage „v320 folgt“ unten ist historisch. Aktuelle Migrationen310→320→330. Neu hinzugekommen: Kostenquellen/Perioden-/Ist-Zuordnung, PDFs/Versionen, Beraterarchiv, Saldo-Hinweise und Schutz bestätigter Kostenvereinbarungen. Keine Onlinebank-/rechtliche Freigabe.

---

# Immobilien-Grundlage v310 –10.11.2026

Verbindliche Basis gesicherter v300-SMTP/Vault-Checkpoint. Vor Änderungen Kopie GSZ-Checkpoint-v300-vor-Immobilien.zip angelegt. PHP/MariaDB beibehalten, keine bestehende GSZ-/Sparkassentabelle für Privatdaten verwendet.17 neue estate_-Tabellen, mit v300 gesamt71 Tabellen; v330 ergänzt ausschließlich Mailspalten.

Implementiert: Gebäude/Wohnflächen/Mieter, vertraglich explizite Konditionen und Nutzungsintervalle, Eigentümernutzung/Leerstand, Mietentwicklung, bestätigte Monatsforderungen, Teilzahlungen/Komponenten/Kaution, Gegenzeilen, zwei benennbare private Volksbank-Slots und neutraler CSV-Import mit Vorschau/Bestätigung/Quellenkonflikten/Idempotenz. Umbuchungen Vorschlag und explizite Bestätigung, keine Kontostandserfindung. Private Originale außerhalb public mit Hashprüfung, private CSV-Arbeitsdatenexporte ohne DATEV-/Anlage-V-Zusage.

Lizenzprüfung: Quellen/Entscheidung ARCHITEKTUR_NEBENKOSTEN_KERN.md. Mietfuchs-Lizenzinformationen widersprüchlich, kein Code übernommen; zusätzliche Dolibarr-Gesamtanwendung bewusst nicht vermischt. Vorhandene PDO/Auth/CSRF/Parser/Cent-/Datei-/Auditfunktionen wiederverwendet. Kein Zusatzdienst/Abonnement.

Befehle und Ergebnisse lokal:

```sh
/workspace/gsz-tools/bin/php tests/test_estate_core.php
GSZ_TEST_DSN='mysql:unix_socket=/workspace/gsz-tools/db/mysql.sock;dbname=gsz_test_estate_core_final' /workspace/gsz-tools/bin/php tests/test_estate_database.php
GSZ_TEST_URL=https://127.0.0.1:8460 node tests/test_estate_browser.cjs
/workspace/gsz-tools/bin/php tests/test_estate_calculation.php
```

Mathematik9, DB40, Browser27, Nebenkosten186 Assertions. Browserfixture tests/prepare_estate_browser.php auf leerem Schema gsz_test_estate_browser2, synthetischer Login, PHP8114/TLS8460 nur Loopback und privates Sessionverzeichnis. Keine echten Mieter/Konten. DB testet zusätzlich strikte Isolation GSZ-Daten/Finanzzahlungen, ausgefüllte wiederholte Migration, Überlappungen, Stale Forms, Vorauszahlung/Kaution, Quellen-IDs/Dubletten, echte Umbuchung und unveränderte Originale. Bestandsaufgaben/Rechnungen unverändert.

Paketgrenzen: keine echten Volksbankexporte/Onlineauthentifizierung verifiziert; CSV neutrales Schema mit Quellen-ID, keine offizielle Bankformatbehauptung. Nachträgliche Ausgaben-Reklassifizierung benötigt noch eigenen Korrekturworkflow. Monatsgebundene Vorzahlungen und endgültige Nebenkosten-PDF/Versions-Snapshots werden im nächsten v320-Paket ergänzt. Mathematischer Kern ist bereits geprüft, allein aber keine integrierte rechtlich geprüfte Abrechnung. Keine vertragliche/legal zulässige Umlage oder Heizkostenquote erfinden.

Update auf geprüfter v300-Struktur: Sicherung DB+Dateien, migrate_gesamt.php erweitert um310 und330; alle Schritte idempotent, DDL nicht gesamtatomar. Kein destruktiver Rollback; Zusatzdaten/Originale behalten, bei Rückfall konsistente Sicherung und zwischenzeitliche Vorgänge abgleichen. STRATO-Staging weiterhin vor Ort prüfen, kein Live-Deployment.
