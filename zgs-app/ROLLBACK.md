# Rollback 380 → 370

Nur nötig, wenn nach dem Update etwas grundlegend nicht funktioniert. Das Update entfernt die Finanztabellen
endgültig; zurück geht es **nur** über die Sicherung aus „Sicherung und Update“ (Schritt 3 der Installation).

## Was du brauchst

- die heruntergeladene Datei `gsz-sicherung-JJJJMMTT-HHMMSS-xxxxxxxx.sql.gz` (vor dem Update erstellt),
- das alte Paket `GSZ-ZENTRALE-KOMPLETT-STRATO-370.zip`.

## Weg 1: ohne SSH (phpMyAdmin im STRATO-Kundenbereich)

1. STRATO-Kundenbereich → Datenbanken → phpMyAdmin für die Zentrale-Datenbank öffnen.
2. Die Datenbank auswählen → Reiter **Importieren** → die `.sql.gz`-Datei wählen → Ausführen.
   Die Sicherung löscht zuerst jede gesicherte Tabelle und legt sie neu an; zusätzlich entfernt sie die erst mit
   380 entstandene Tabelle `estate_bank_uploads`. Ergebnis: exakt der Datenbankstand vor dem Update.
3. Die Programmdateien aus dem 370-Paket wieder hochladen (`public/` und `private/` ersetzen).
   Zusätzliche 380-Dateien dürfen liegen bleiben; sie werden von 370 nicht benutzt.
4. Anmelden und Systemcheck öffnen.

## Weg 2: mit SSH

```sh
cd zgs-app
php private/db_restore_cli.php gsz-sicherung-JJJJMMTT-HHMMSS-xxxxxxxx.sql.gz --bestaetigt
```

Ohne Dateiname zeigt der Befehl die vorhandenen Sicherungen in `private/backups/`. Danach wie oben die 370-Dateien hochladen.

## Was lokal nachgewiesen ist

Die Testsuite `tests/test_final_upgrade.php` erstellt eine Sicherung, aktualisiert 370 → 380 (und 340 → 380),
spielt die Sicherung in eine zweite leere Datenbank zurück und vergleicht Tabellenstruktur und alle Zeilen:
**identisch**. Ein Import über STRATO-phpMyAdmin wurde nicht real ausprobiert (siehe OFFENE-PUNKTE.md).

## Hochgeladene Dateien

Die Sicherung enthält die **Datenbank**. Hochgeladene Originale (Dokumente, Fotos, Immobilienbelege in
`private/uploads/`) verändert das Update nicht; sie bleiben bei einem Rollback einfach liegen. Für eine vollständige
Notfallsicherung zusätzlich `private/uploads/` gelegentlich per SFTP herunterladen.

## Mail-Passwort und Einstellungen

Das Mail-Passwort liegt nach dem Update in `private/secrets/` (Vault). 370 liest diesen Speicher nicht; nach einem
Rollback das Mail-Passwort in 370 wie früher unter „Dienste einrichten“ eintragen.
