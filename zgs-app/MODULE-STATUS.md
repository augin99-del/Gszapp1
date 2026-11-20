# Module – Stand 380

„Lokal geprüft“ = automatische Tests mit synthetischen Daten (PHP 8.3, MariaDB 10.11, Chromium in 390/820/1180/1440 px).
Nichts davon ist ein Test auf STRATO, auf echten Geräten oder mit echten Bank-/Maildaten.

| Modul | Seiten | Stand |
|---|---|---|
| Heute (Tagesansicht) | index.php | neu gestaltet; lokal geprüft |
| Woche / Planung / Route / Termine | planner.php, route.php, appointments.php, task.php | Darstellung neu, Logik unverändert; lokal geprüft |
| Objekte, Kunden | objects.php (neu), property.php, customers.php, object_import.php | Darstellung neu, Logik unverändert; lokal geprüft |
| Regelaufgaben, Helfer, Arbeitsnachweise | recurring.php, workers.php, worklogs.php, worklog_*.php | unverändert; lokal geprüft |
| Abfall360 | waste.php, waste_setup.php, waste_export.php (neu) | **geändert:** ein Block je Objekt/Tag/Richtung, ICS/CSV-Export; lokal geprüft. Echte Anbieterabrufe nicht real geprüft |
| Mail | mail.php, compose.php, connections.php | **geändert:** Passwort einmal (Vault), Schutzschaltung, neue Liste; lokal geprüft ohne echtes Postfach |
| Eigene Immobilien | estate.php, estate_settlements.php | unverändert; lokal geprüft |
| Mietabgleich (Volksbank) | estate_bank.php | **neu gestaltet:** Konto = Haus, Upload, Dubletten, Monatsübersicht, Zuordnen, Kosten; lokal geprüft ohne echten Export |
| Dokumente | documents.php, document_download.php | unverändert; lokal geprüft |
| SEO | seo.php, seo_export.php | unverändert (nur Exporthilfe verschoben); lokal geprüft |
| Einstellungen | more.php (neu), settings.php, connections.php, update.php, system_check.php | neu geordnet; lokal geprüft |
| Sicherung/Update | update.php, backup_download.php (neu), private/db_backup.php, db_restore_cli.php | **neu**; Sicherung → Update → Rücksicherung lokal identisch nachgewiesen |
| Einrichtungsassistent | setup.php | unverändert bis auf Texte; leer/320/370 → 380 lokal geprüft |
| Automation/Cron | private/cron_gesamt.php, automation.php | Bank-Job entfernt; lokal geprüft, STRATO-Cron nicht nachgewiesen |

## Entfernt (Finanzbereich)

Angebote, Rechnungen inkl. Versand/Export/PDF, Offene Posten/Forderungen, Mahnungen, EÜR-Arbeitsgrundlage/Belege,
wiederkehrende Finanzbuchungen, Finanzexporte, Kasse/Anlagen, Geschäftskonto (Sparkasse) inkl. Bankimporten,
Live-Bankanbindung (Enable Banking). Liste: `docs/ENTFERNT-380.md`. Buchhaltung, Angebote und Rechnungen laufen in Lexware Office.
