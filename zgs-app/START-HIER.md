# GSZ-Zentrale 380 – Start hier

Interne Software des Gebäudeservice Zentrum für genau einen Nutzer (den Inhaber). PHP/MariaDB auf STRATO Shared Hosting.
Webroot ist ausschließlich `zgs-app/public/`, alles Private liegt in `zgs-app/private/` (außerhalb des Webroots).

## Was in 380 neu ist

| Bereich | Kurz |
|---|---|
| Finanzbereich entfernt | Angebote, Rechnungen, Offene Posten/Mahnungen, EÜR, Finanzvorlagen, Kasse/Anlagen, Sparkasse und Live-Bankanbindung sind raus. Buchhaltung läuft in Lexware Office. |
| Mietabgleich | Zwei Volksbank-Vermieterkonten, Konto = Haus. Monatlicher Upload, Dubletten werden erkannt, Monatsübersicht je Haus, Zuordnen mit einem Tipp. |
| Abfall | Ein Block je Objekt und Tag („Restmüll und Biotonne rausstellen“), getrennt für Rausstellen und Zurückstellen. |
| E-Mail | Postfach-Passwort einmal eintragen; verschlüsselt gespeichert; Schutz vor wiederholten Fehlanmeldungen. |
| Oberfläche | Ruhige, mobile Oberfläche: Heute, Woche, Objekte, Immobilien, Mail, Mehr. |
| Sicherung | „Sicherung und Update“ erzeugt eine vollständige Datenbanksicherung zum Herunterladen; das Update ist ohne frische Sicherung nicht möglich. |

## Welches Dokument wofür

| Dokument | Inhalt |
|---|---|
| [INSTALLATION-STRATO.md](INSTALLATION-STRATO.md) | Update 370 → 380 über die bestehende Installation (Schritt für Schritt) und Neuinstallation |
| [ROLLBACK.md](ROLLBACK.md) | Zurück auf 370, falls etwas schiefgeht |
| [TAEGLICHE-NUTZUNG.md](TAEGLICHE-NUTZUNG.md) | Alltag: Tag abarbeiten, Kontoauszug am 10., Mail, Abfall |
| [MODULE-STATUS.md](MODULE-STATUS.md) | Was es gibt, was getestet ist, was nicht |
| [OFFENE-PUNKTE.md](OFFENE-PUNKTE.md) | Was noch real auf STRATO/iPhone/mit echten Daten geprüft werden muss |
| [CRONJOBS.md](CRONJOBS.md) | Automatische Hintergrundaufgaben bei STRATO |
| [SICHERHEIT.md](SICHERHEIT.md) | Login, Verschlüsselung, Ablage der Geheimnisse |
| [CHANGELOG.md](CHANGELOG.md) | Änderungen 370 → 380 |
| `docs/` | Testbericht, Liste entfernter Dateien/Tabellen, Annahmen, Screenshots; `docs/archiv/` = alte Dokumente (Historie) |

## Ehrlicher Stand

Alles hier wurde **lokal** mit synthetischen Daten geprüft (PHP 8.3, MariaDB 10.11, Chromium). **Nicht** geprüft:
echte STRATO-Umgebung, echter Volksbank-Export, echtes Postfach, echte iPhone/iPad/Mac-Geräte. Siehe OFFENE-PUNKTE.md.
