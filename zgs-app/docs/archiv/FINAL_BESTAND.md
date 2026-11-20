# Tatsächlicher Bestand / Ausgangsprüfung – 12.11.2026

Der vorhandene Quellstand wurde vor Änderungen gegen alle287Dateien der gespeicherten GSZ-Zentrale-v1.0-STRATO-Release.zip geprüft: bytegleich. SHA2561f223a15a4996b8f3eb7fd83975474396afe9739107bd866e4100a743b0382e3. Das zugehörige Update-ab-v331 hat SHA2561b98120a8ce0e16b573e66d5d34397619869bd57596fe8330676c70c5fe9f86f. Beide passen zur vorhandenen SHA256SUMS-GSZ-v1.txt. Unveränderte Sicherung GSZ-Ausgangssicherung-v340-vor-FINAL.zip angelegt. Keine ältere Codebasis zurückgespielt.

Die im Auftrag genannten Namen mit „(3)“ und TESTSTATUS2.md waren nicht separat im Workspace/Anhangsverzeichnis vorhanden. Verwendet wurden die nachweislich gespeicherten Originalpakete und ihre passende Prüfsummendatei; keine Behauptung über den Inhalt nicht vorliegender zusätzlicher Dateien.

## Geprüfte Architektur

PHP-Controller in public/, gemeinsame Anmeldung/bootstrap/layout; Services und versionierte SQL-Migrationen in private/. PDO mit echten Prepared Statements, serverseitigen Transaktionen und CSRF-POST/Redirect/Get. Keine parallelen Kunden-/Aufgaben-/Finanztabellen eingeführt. Ursprünglich Schema 340/78Tabellen; neuer additiver Schritt 350/82Tabellen. Historische SQL-Dateien bleiben unverändert. Laufzeit PHP 8.2+64Bit/Maria DB10.6+, lokal PHP 8.4.24/Maria DB11.8.6. My SQL ist wegen bestehender Maria DB-spezifischer DDL nicht freigegeben. Tatsächlicher STRATO-Tarif, CLI-/Web-PHP und Serverstruktur sind nicht zugänglich; frühere Nutzerangaben8.4.27/Schema 330/76Tabellen wurden nicht als Servermessung ausgegeben.

## IST/SOLL vor Änderungen

| Bereich | Tatsächlicher Ausgangsstand | Bearbeitung |
|---|---|---|
| Login/Sessions/CSRF/Uploads | vorhanden und zuvor lokal geprüft | erhalten, bestehende Sicherheits-/Praxistests erneut ausgeführt |
| Objekte/Kunden/Akten | sieben Katalogobjekte, Zuordnung/Notizen/Dokumente/Verlauf | erhalten; keine produktiven Stammdaten verändert |
| Wochenplanung | Vorschau/Übernahme, Kapazität/Mo–Fr/Treppenhaus Mo–Mi, Sperren, Abschlussquartale | erhalten und erneut geprüft; täglicher Rhythmus ergänzt |
| Abfall | lokaler ICS/CSV-Import, sichere optionale HTTPS-Synchronisation, Fontänen-Sonderregel | amtliche gemeinsame Quelle recherchiert, Konflikt mit gespeicherten Routinetagen ergänzt; keine echten Termine eingetragen |
| Mail | native IMAP, PHPMailer465/587, Cache/UIDVALIDITY, Entwürfe, Anhänge, Recovery | erhalten; MIME/DB/HTTP/IMAP-Shim/TLS erneut geprüft; live weiter blockiert |
| Finanzen/Bank | Angebote/Rechnungen/Belege/Teilzahlungen/Kasse/Anlagen/neutraler CSV-Import | wiederverwendet; begrenzter CAMT053/054-Reader und Konto-/Dublettenprüfung ergänzt |
| Immobilien | Gebäude/Einheiten/Mieter/Verträge/Mieten,2Volksbank-Slots, Nebenkosten/PDF | erhalten; CAMT in vorhandene beiden Konten integriert |
| Dashboard/Navigation | gemeinsamer Rahmen,26Module; Mail-/Finanzdaten teilweise nur intern ermittelt |12Hauptgruppen,28Module erreichbar, tatsächliche Aufmerksamkeitsdaten integriert |
| Automation | Wiederholungen/Planvorschlag und Laufzustand vorhanden | Bank-Prüfliste/Erinnerungen, vollständige lesende Jobübersicht und ehrlicher nächster Soll-Lauf ergänzt |
| Installation | CLI-Fresh/Upgrade; ohne SSH kein Bestandsupdate in UI | vorhandenen Installer wiederverwendet; geschütztes, standardmäßig deaktiviertes Inhaber-Update und Uploadintegrität ergänzt |
| SEO | nicht vorhanden | funktionierender Offline-Keyword/CSV/GeoGrid/Historie/Vergleich/Export; keine Live-API/OAuth-Anbindung behauptet |

Aktuelle Modulabgrenzung MODULE-STATUS.md. Offene externe/fachliche Grenzen TESTSTATUS-FINAL.md und INTEGRATIONEN-FINAL.md. Keine aktuellen Hosting-/Bank-/Mail-Zugangsdaten vorhanden oder aus alten Archiven übernommen; keine Produktivaktionen.
