# Cronjobs (STRATO)

Ob und wie oft Cronjobs laufen dürfen, hängt vom STRATO-Tarif ab – vor Ort im Kundenbereich prüfen.
Alle Einstiege liegen in `private/` und verweigern Aufrufe über HTTP. Absolute Pfade verwenden.

## Grundlauf (empfohlen alle 30–60 Minuten)

```sh
/ABSOLUTER/PFAD/php /ABSOLUTER/PFAD/zgs-app/private/cron_gesamt.php
```

Legt fällige Regelaufgaben an (ohne Dubletten), bereitet einen Wochenvorschlag vor (wird nicht ungefragt übernommen),
zählt Erinnerungen und gleicht – falls eingerichtet – die Abfallkalender ab (inkl. Neuzusammensetzen der Tonnen-Blöcke
bei Terminverschiebungen). Kein Bankabruf mehr (entfernt).

## Mit Mailabruf

```sh
/ABSOLUTER/PFAD/php /ABSOLUTER/PFAD/zgs-app/private/cron_gesamt.php --mail-sync
```

Nutzt denselben gespeicherten Postfachzugang wie die Oberfläche (verschlüsselt in `private/secrets/`).
Hat das Postfach das Passwort abgelehnt, bricht der Lauf **ohne Anmeldeversuch** ab, bis das Passwort in der
Oberfläche neu eingetragen ist. Versendet nie eine Mail.

`--calendar-sync` erzwingt den Kalenderabgleich zusätzlich. Beide Flags sind kombinierbar.

## Kontrolle

Mehr → Einstellungen → Systemstatus → „Hintergrundaufgaben (Cron)“: letzter erfolgreicher Lauf je Aufgabe.
Ein Lauf von Hand („Hintergrundaufgaben jetzt ausführen“) beweist nicht, dass der STRATO-Cron eingerichtet ist.
Exit-Code 0 = in Ordnung, 2 = eine Aufgabe blockiert (Details im Systemstatus, keine Zugangsdaten in der Ausgabe).

Nicht mehr vorhanden: der Job „bank“ (Bankimport-Prüfliste) – wurde mit Migration 380 entfernt.
