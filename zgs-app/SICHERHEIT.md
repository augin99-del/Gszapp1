# Sicherheit

## Zugang
- Anmeldung mit E-Mail und Passwort (bcrypt/`password_hash`), Sperre nach wiederholten Fehlversuchen (15 Minuten),
  neue Session-ID beim Login, Abmeldung nach Inaktivität (Standard 30 Minuten).
- Nur HTTPS; Session-Cookie `Secure`, `HttpOnly`, `SameSite=Strict`.
- Jede Änderung per POST mit CSRF-Token; danach Weiterleitung (PRG). Datenbankzugriffe mit Prepared Statements,
  Ausgaben HTML-escaped.
- Änderungen am Postfachzugang, das Update und der Datenbankzugang verlangen zusätzlich das Zentrale-Passwort.

## Ablage
| Was | Wo | Schutz |
|---|---|---|
| Programm | `public/` (Webroot), `private/` | `private/` außerhalb Webroot |
| Datenbankzugang, Einstellungen | `private/server-config/` (`managed.json` + getrennter Schlüssel) | AES-256-GCM, Ordner 0700, Dateien 0600 |
| **Postfachzugang** | `private/secrets/mail.vault` + `mail.key` | libsodium XChaCha20-Poly1305 (Secret-Vault-Format), Schlüssel getrennt, 0700/0600 |
| **Google Search Console** (Dienstkonto-Schlüssel, nur lesen) | `private/secrets/gsc.vault` + `gsc.key`; Ergebnis-Zusammenfassung `gsc-data.json` ohne Zugangsdaten | wie Postfach; Hochladen/Löschen nur mit Zentrale-Passwort; Netzwerk nur zu oauth2.googleapis.com und www.googleapis.com, TLS geprüft |
| Sicherungen | `private/backups/` | 0700/0600, Download nur angemeldet |
| Uploads/Originale | `private/uploads/` | Download nur angemeldet, erzwungener Download, `nosniff` |

Das Mail-Passwort steht nie im Klartext in Datenbank, Logs, HTML, JavaScript, Sicherungs-ZIP oder Git.
Es wird nie angezeigt; das Formular lässt das Feld leer („leer lassen = unverändert“). Der Login in die Zentrale
bleibt der Schutz davor: Wer angemeldet ist, kann Mails lesen und senden.

Schutzschaltung: Lehnt das Postfach die Anmeldung ab, wird das vermerkt (`private/secrets/mail-auth.json`, enthält kein
Geheimnis). Danach gibt es keine automatischen Anmeldeversuche mehr (Seiten, Versand, Cron), bis das Passwort neu
eingetragen oder bewusst „Verbindung testen“ getippt wird.

## Bank
Keine Bankzugangsdaten, PIN oder TAN in der Zentrale. Kontoauszüge werden als Datei hochgeladen. IBANs von
Zahlern werden nur als SHA-256-Prüfwert gespeichert. Jedes Konto ist fest einem Haus zugeordnet; ein Auszug mit
fremder Konto-IBAN wird abgelehnt.

## Paket
Das STRATO-ZIP enthält keine `config.php`, keine `server-config/`, `secrets/`, `uploads/`, `backups/`, keine Logs,
keine Testdaten-Dumps. Das Dateimanifest (`private/release_integrity.json`) wird vor jedem Update geprüft.
