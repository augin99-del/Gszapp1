# Rückfallplan Version1.0 / Schema340

Vor Upgrade: konsistente Datenbank UND vollständiger privater Dateibestand (Konfiguration, Vault, Originale/Uploads), alter Programmstand, Domainzuordnung/PHP-Konfiguration sichern. Sicherungen vertraulich außerhalb Webroot, verschlüsselt aufbewahren; Rücksicherung auf separater Testkopie nachweisen. Begleitend Dateihashes/Tabellenanzahl/Fremdschlüssel/Zeitraum festhalten, keine Kontodaten im Prüfbericht.

Schema340 ist additiv. Es gibt absichtlich keine produktive DROP-/Downmigration: nach Nutzung vorhandene neue Abschluss-/BCC-/Automationsdaten nicht löschen. Bei DDL-Fehler keine Behauptung einer automatischen Transaktionsrücknahme; Teilstruktur untersuchen. Protokollierter Schritt wird nicht blind wiederholt. Sichere Wiederholung nur des noch nicht protokollierten Schritts nach Ursachenprüfung. Kein Fresh auf Bestandsdatenbank.

Fehler vor Schreibfreigabe: Testinstanz stoppen, neuen Code isolieren, konsistente Sicherung in separate DB/Dateikopie wiederherstellen, prüfen, dann kontrolliert umschalten. Ein Code-Rollback allein ersetzt keinen getesteten DB-/Datei-Restore; v331-Systemcheck erwartet älteren Strukturvertrag. Nicht einzelne neue Spalten löschen, um Warnungen zu unterdrücken.

Fehler nach neuen Geschäftseingaben: zuerst Schreibzugriffe/Cron pausieren und den fehlerhaften neuesten Stand zusätzlich vertraulich sichern. Änderungen seit Backup erfassen und gemeinsam geordnet nachführen; eine alte Sicherung darf keine neuen Kunden-/Beleg-/Abschlussdaten heimlich verlieren. Umschaltung/Rücksicherung produktiv nur nach ausdrücklicher Freigabe. Keine automatische produktive Wiederherstellung durch diese Lieferung.

Lokale Testdatenbank+Privatdatei-Restoreprobe ist bestanden (TESTSTATUS.md); sie beweist nicht die Verfügbarkeit oder Wiederherstellungsdauer des STRATO-Tarifs. Vor Produktivbetrieb tatsächliche RPO/RTO/Backupaufbewahrung vor Ort festlegen und testen.
