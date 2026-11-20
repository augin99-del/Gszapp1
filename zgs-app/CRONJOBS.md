# Hintergrundläufe, Sperren und manueller Ersatz

STRATO-Tarif/SSH/Cron-Funktionen sind vor Ort zu prüfen. Keine öffentliche Gratis-Cron-URL und kein geheimer Token in URL. CLI-Dateien bleiben unter private/, HTTP-Aufrufe verweigert. CLI-PHP muss dieselbe Version/Extensions und sichere Konfiguration wie Web-PHP verwenden. Absolute Pfade und Rechte prüfen.

Basislauf, z.B. alle30 Minuten (Intervall im tatsächlich vorhandenen STRATO-Cronbereich einstellen):

```sh
/ABSOLUTER/PFAD/php /ABSOLUTER/PFAD/zgs-app/private/cron_gesamt.php
```

Erzeugt fällige Aufgaben idempotent und einen unapplizierten Wochenvorschlag. Keine automatische Planübernahme, keine Mail, kein Bankabruf. Bestehender cron_scheduler.php ist ein kompatibler Einstieg für dieselbe Automation340. Datenbankspezifische Advisory-Sperre verhindert gleichzeitige Läufe; letzter Start/Erfolg/Fehler/Dauer steht in automation_state und im Systemcheck. Exit2 bei blockiertem Job, Exit0 bei Erfolg/parallel bereits laufend. Abgebrochene running-Läufe werden beim nächsten exklusiven Lauf als unterbrochen markiert.

Nur nach eingerichteten und freigegebenen Quellen/Postfach:

```sh
/ABSOLUTER/PFAD/php /ABSOLUTER/PFAD/zgs-app/private/cron_gesamt.php --calendar-sync
/ABSOLUTER/PFAD/php /ABSOLUTER/PFAD/zgs-app/private/cron_gesamt.php --mail-sync
```

Optional beide Flags. Mail-Sync liest Posteingang begrenzt und versendet nichts. Kalender erzeugt ohne auto_apply ausschließlich eine sichtbare Prüfung; Bestätigung bleibt erforderlich. Lokaler Test kann keine tatsächliche STRATO-Ausführung beweisen. Protokolle privat speichern/rotieren, keine Konfiguration/SMTP-Transkripte/Bankdaten loggen. Bei Wartungsmodus verweigern CLI-Einstiege den Lauf. Ohne verifizierten Cron im Systemcheck angemeldet „Fällige Regelaufgaben aktualisieren“ verwenden; das ist ein manueller POST mit CSRF, keine vorgetäuschte Automatik.
