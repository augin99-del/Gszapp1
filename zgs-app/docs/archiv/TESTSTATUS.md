# Aktuell: Komplett370 –13.11.2026

Arbeitskopie /workspace/gsz-inbetriebnahme/zgs-app, Ziel370/85Tabellen. Verbindlicher neuer Auftrag: EIN vollständiges GSZ-ZENTRALE-KOMPLETT-STRATO.zip, keine Patches. Aktuellen Code erhalten; Abfall360 nicht neu entwickeln. Zuerst START-HIER.md, TESTSTATUS-FINAL.md, MODULE-FINAL.md, CHANGELOG-FINAL.md lesen.

Implementiert: unabhängiges geschütztes Browser-Setup, privater Zufallscode statt universellem Schlüssel, genaue Schemaerkennung/gesicherte Migration, verschlüsselte verwaltete Konfiguration, individuelle Adminanlage ohne Bestandsreset, gebündelter IMAP-Fallback, grafische Dienste-/Bankeinrichtung und optionale lesende Anbieterautorisierung. Aktueller tatsächlicher Teststand steht ausschließlich TESTSTATUS-FINAL.md. Alle produktiven Konfigurations-, Bank-, Mail- und Hostingzugänge fehlen weiter. Keine Produktivänderung/echte Mail/Bankaktionen/Kosten ohne Freigabe.

Tests:33/33 alte Suiten,9Migrationsfälle568Prüfungen, Setup-Konfig22,Mail33+TLS7,Bank73/Browser36 und Setup-Browser40frisch/40bestehend bestanden. Finales Vollpaket aus ZIP frisch installiert:40Browserprüfungen bestanden, einschließlich Wartungsmodus/defekter Konfiguration/leerem Umgebungswert. Backup/Restore85Tabellen und privater verschlüsselter Konfiguration bestanden. Keine config.php/private/server-config/runtime-setup/secrets/uploads/Logs/Dumps/Backups in Artefakte. Codeintegrität COMPLETE370 enthält Vendor und muss nach jeder Codeänderung regeneriert werden. Alte Fortsetzungseinträge unten sind Historie.

Nächster Schritt nach Paketprüfung: geschütztes STRATO-Staging mit echtem PHP-HTTPS-Test, realen Anschriften/Behältern, Postfach-/Bankanbieterfreigaben und Cronnachweis. Interne Serverursache nicht aus HTTP-Antwort oder Tabellenzahl erfinden. Bei Wiederaufnahme aktuelle Dateien/Reports prüfen statt alte Phasen erneut umzusetzen.

---
# Aktuell: Abfall360

[TESTSTATUS-ABFALL360.md](TESTSTATUS-ABFALL360.md) ist der aktuelle Testbericht: neue93DB-/50Browserprüfungen, Altregressionen PASS; echtes STRATO/PHP-Netz und Bestandsadressfreigaben noch offen. Keine Produktivfreigabe.

---

# Aktueller Teststatus FINAL / Schema 350

Maßgeblich ist TESTSTATUS-FINAL.md. Lokal: 168 PHP-Dateien syntaxgeprüft, 33 PHP-/DB-/HTTP-Suiten bestanden, neue37DB-/40Kern-/86bzw84Upgrade-/9Integritätsprüfungen und v1-Regressionsprüfungen bestanden. 564 Browserprüfungen ohne doppelt gezählte Wiederholungen; 23 lokale SMTP-TLS-Szenarien; drei Parallelitätsszenarien; CSV-Fallback ohneDOM;82-Tabellen- und privateDatei-Restoreproben bestanden.

Paket-/Extraktions-Smoke bestanden, einschließlich echter Ablehnung eines beschädigten Uploads; PAKETPRUEFUNG-FINAL.md. STRATO, echtes Postfach/Versand/Gesendet, tatsächliche Objektkalender und Cron: BLOCKED. Physisches iPhone/Safari und reale Bankexportprofile: NOT TESTED. LiveSEO/GSC/GBP/OCR/validierteE-Rechnung nicht als fertig ausgegeben. Keine Produktivfreigabe oder echten externen Kontoaktionen.
