# Paketprüfung Version1.0 – PASS lokal, externe Abnahme ausstehend

Basis SHA25649dd763afb6d8480ed4a42508fb7c0e457f679564d9f4b2b2669458d3fc44c21 für GSZ-Installationsstand-v331.zip; unveränderte Ausgangssicherung vorhanden. Keine ältere Codebasis verwendet. Vollständige Anwendung plus minimaler Patch mit exaktem Datei-/Hashverzeichnis UPDATE_DATEIEN_V1.md; extern DATEIMANIFEST_GSZ_v1.sha256 enthält alle Dateihashes einschließlich Manifest. SHA256SUMS-GSZ-v1.txt enthält Prüfsummen beider ZIPs und des Dateimanifests. Hashdateien separat ausliefern, keine zirkuläre Selbstreferenz.

Automatisierte Paketprüfung durch lokales Python/zipfile/hashlib:

- PASS: CRC aller ZIP-Einträge, eindeutige Namen, keine absoluten/Traversalpade/Backslashpfade/Symlinks.
- PASS: Vollarchiv ist bytegleich zum erlaubten aktuellen Quellbestand. Keine Basisdatei gelöscht. Exakte v331-Basis plus Update ergibt exakt das Vollpaket.
- PASS: historische SQL-Dateien byteidentisch zur Basis; ausschließlich neue migration_v340.sql.78Tabellen nach Neuinstallation.
- PASS: keine config.php/.env, Uploads, Postfach-/Bankdateien, Vaults/Schlüssel, Logs, Dumps, Backups oder eingebetteten ZIPs. SQL-Dateien sind versionierte Schema-/Migrationsquellen, keine produktiven Dumps. Originale/Private Konfiguration müssen auf dem Server erhalten bleiben.
- PASS: Prüfung gegen bekannte vertrauliche Werte aus ursprünglicher Konfiguration ausschließlich im Speicher sowie gängige Private-Key/Tokenmuster, ohne Ausgabe/Kopie der Werte. Synthetische ungültige example.invalid-Testdaten sind absichtlich in Tests enthalten; keine echten Zugangsdaten. Musterprüfung ersetzt keine vollständige forensische Sicherheitsprüfung.
- PASS: vollständiges Archiv sicher in frisches lokales Verzeichnis extrahiert,155PHP-Dateien syntaxgeprüft. Installer Fresh340/78Tabellen, Erstinhaber, Upgrade/Wiederholung, Planungs46 und Rechnungs-PDF-Mehrseitentest bestanden. Systemcheck mit privatem Sessionpfad0Blocker/6Hinweise, production_approvedfalse. Nichtleere Neuinstallation verweigert. Abschließende Dokumentationsänderungen änderten keine ausführbaren Paketdateien.

Tatsächliche lokale Befehle: `python3 /workspace/gsz-tools/package_v1.py` und `python3 /workspace/gsz-tools/test_v1_package.py`; Produktivserver benötigt diese lokalen Hilfsskripte nicht. Separate Hash-/Rekonstruktions-/Extraktionsprüfung nach finaler Paketierung. Teststatus der Anwendung TESTSTATUS.md; keine Aussage zu realer STRATO-/Mailverbindung, keine Produktion verändert.
