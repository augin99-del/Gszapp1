# Kurzbestandsaufnahme und IST/SOLL – Version1.0

Basis: tatsächlicher neuester Workspace v331, abgeglichen mit dessen vollständiger ZIP und vor Änderungen gesichert. Schema330 hatte76 Tabellen. Es wurden weder ältere ZIPs zurückgespielt noch produktive Serverdaten eingelesen. Die vom Nutzer gemeldeten STRATO-Werte (PHP8.4.27,76 Tabellen, Inhaber/Sessions/DB-Schreibrechte) sind Angaben über dessen Installation, keine hier erhobenen Servermessungen.

| Bereich | Vor Änderung tatsächlich im Code | Lokaler Stand dieser Lieferung | Extern/offen |
|---|---|---|---|
| Login/Sessions | Passwort-Hash, Ratelimit, CSRF, sichere Cookies | Praxis- und Sicherheitsbrowserprüfungen PASS | echte HTTPS-/Sessionkonfiguration prüfen |
| DB | Version330,76 Tabellen, additive Migrationen | Version340,78 Tabellen, Fresh/Upgrade/Wiederholung geprüft | STRATO-Kopie migrieren, keine Produktion geändert |
| Objekte | sieben Katalogeinträge, Kunden, Akten, Notizen, Dokumente | erhalten; Änderungen/Uploads/Download lokal geprüft | echte Adressdaten nicht ungefragt ändern |
| Planung | bestätigte Vorschläge/Termine/Sperren; flexible Tage inkl.Wochenende, keine harte Kapazitätsgrenze | Mo–Fr, Treppenhaus Mo–Mi, Überlast bleibt sichtbarer Konflikt, gleiche Objekte/Städte gruppiert | kein echter Routingprovider aktiv |
| Wiederholungen | Kalenderidentität job+scheduled_for; Quartale ab Fälligkeit | expliziter Kalendermodus bzw. Abschlussmodus, tatsächlicher Berliner Abschlusstag, versionierte Ereignisse | vorhandene alte offene Quartalsduplikate manuell prüfen, niemals automatisch löschen |
| Abfall | geprüfte lokale ICS/CSV, Sonderregel Fontänenstraße, pausierte Normalroutinen | erhalten, sichere freigegebene HTTPS-Quellen, Review/Remapping, Objekt-Raus/Rein-Versatz | keine adressbezogene offizielle Abo-URL eingerichtet; keine erfundene Tonnenticker-API |
| Mail | Cache/UIDVALIDITY, MIME, Ordner, Entwürfe, Anhänge, SMTP, Gesendet-Recovery | PHPMailer465TLS ergänzt, BCC, sichere Diagnostik; Mocks/TLS lokal PASS | native IMAP hier nicht installiert; keine echten Zugangsdaten/Empfang/Versand geprüft |
| Oberfläche | ansprechendes Dashboard, uneinheitliche Unterseiten | gemeinsamer Rahmen für authentifizierte HTML-Seiten;26 Module erreichbar | echtes iPhone/Safari nicht geprüft |
| Konfiguration | private/config außerhalb public erwartet, unsichere Rechte als Blocker | externer absoluter Configpfad, keine Symlinks; sicherer Rechtecheck/CLI-Reparatur | tatsächliche STRATO-Datei nicht zugreifbar, keine Rotation durchgeführt |
| Cron | einzelne CLI-Jobs, kein gemeinsamer Beweis letzter erfolgreicher Läufe | DB-Sperre, Laufstatus/Dauer, sichere Fehlercodes; manueller Admin-POST | Tarif/Cron-Ausführung vor Ort nicht nachgewiesen |
| Rechnungen/Belege/Bank/Immobilien | vorhandene Services, CSV-Importe, PDFs, private Archive | erhalten, bestehende Logik-/DB-/Browserregressionen bestanden | steuerliche Abnahme/Onlinebanking/OCR bleiben eigenständige offene Punkte |

Reale Kernfelder: tasks(id,recurring_job_id,scheduled_for,due_date,done,row_version,plan_locked,planned_order,start_time,completed_at); recurring_jobs(id,frequency,next_due_date,rule_source,active); planning_settings/days/appointments/proposals; waste_sources/events/origins/imports/task_links/history; mail_folders/messages/drafts/draft_attachments. Migration340 erweitert diese Tabellen und ergänzt ausschließlich recurring_completions und automation_state; vollständiger erwarteter Spalten-/Indexvertrag in private/schema_requirements.php. private/schema.sql bleibt historischer Basisstand; Neuinstallation ausschließlich über den Installer, nicht diese Datei allein.

Lizenzentscheidung: vorhandene PHPMailer7.2.0(LGPL2.1) und tFPDF wiederverwendet, keine fremden Anwendungsfunktionen kopiert. Webklex6.2.0(MIT) geprüft, benötigt weitere geprüfte Abhängigkeiten/Extensions; bislang nicht eingebaut. Kein neuer monatlicher Dienst aktiviert. OPEN_SOURCE_PRUEFUNG.md und INTEGRATIONEN_V1.md enthalten Quellen und Grenzen.
