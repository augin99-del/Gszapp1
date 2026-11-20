# Täglicher Betrieb / Hintergrundjobs

Morgens Dashboard öffnen: heutige/überfällige Aufgaben, Objektgruppen, Müllticker, wichtige Termine, lokal gespeicherte Mailhinweise, offene Rechnungen/Belege und letzter Aufgabenlauf. E-Mail-/Bankwerte sind kein Liveabruf ohne tatsächlich verbundene Quelle. Bestätigte Zahlungsbewegungen sind keine vollständig steuerlich geprüfte EÜR.

Heute/Planung: offene Aufgabe direkt abhaken, Notiz/Foto freiwillig. Neue/verschobene Aufgabe und Fixtermin einfach erfassen. Wochenvorschläge prüfen und ausdrücklich übernehmen. Standard10:30/Ziel16:00/Kapazität17:00 editierbar. Treppenhaus automatisch Mo–Mi; sonst flexible Arbeiten Mo–Fr. Feste Müll-/manuell gesperrte Sonntagsaufgaben bleiben erhalten. Unbekannte Dauer oder nicht untergebrachte Bestandsaufgabe bleibt sichtbar; ihre alte Fälligkeit wird nicht still gelöscht. Teilvorschlag beseitigt keinen verbleibenden Kapazitätskonflikt.

Regelaufgaben: einmalige Arbeit als normale Aufgabe, sonst täglich/wöchentlich/14tägig/monatlich/quartalsweise/Saison. Fenster/Tiefgarage im Automodus nächste3Kalendermonate ab tatsächlichem Abschluss; Februar/Schaltjahre berücksichtigt. Alte offene Quartalsaufgaben zuerst bewusst prüfen. Neue Dauern/Intervalle nie ohne eigene Bestätigung aus echten Zeitlogs überschreiben.

Bank: richtige Kontozuordnung vor Vorschau prüfen, für CAMT erwartete IBAN eingeben. Nur gebuchte EUR-Einzelumsätze/stabile Referenzen; keine Zahlungsauslösung. Vorschläge zu Rechnung/Beleg/Miete manuell bestätigen. Ähnlichkeitswarnung beim Formatwechsel anhand echter Bankreferenzen klären. Zwei Volksbank-Slots bleiben vom Geschäft strikt getrennt. Nebenkosten nur aus geprüften Belegen/Umlageschlüsseln und tatsächlich zugeordneten Vorauszahlungen, nicht aus ungeprüften Bankbewegungen.

## Cron

Tarif/CLI/Intervall tatsächlich vor Ort prüfen. Keine ungeschützten öffentlichen Cron-URLs. Beispiel nach Prüfung des echten PHP-/Projektpfads:

```sh
/ABSOLUTER/PFAD/php /ABSOLUTER/PFAD/zgs-app/private/cron_gesamt.php
```

Basislauf erstellt fällige Aufgaben, einen unbestätigten Wochenvorschlag, lokale Bankimport-Prüfliste und Erinnerungszähler. Keine Planübernahme, Mail-/Bankzahlung oder externe Benachrichtigung. Exklusive DB-Sperre gegen Doppelstarts; Start/Erfolg/Fehler/Dauer sichtbar unter Systemcheck. Abgebrochene Läufe beim nächsten exklusiven Lauf als unterbrochen erkennbar.

Optional erst nach eingerichtetem Postfach/bewusst freigegebenen Kalenderquellen:

```sh
php private/cron_gesamt.php --mail-sync --calendar-sync
```

Versendet nichts, liest IMAPbegrenzt und verarbeitet Kalender nach Reviewpolitik. Fehlender Dienst blockiert diesen Job sichtbar, unabhängige Jobs laufen weiter. Für echte automatische Sollzeiten `automation_schedule_enabled:true`, reales Intervall und tatsächlich eingerichtete `automation_scheduled_jobs` serverprivat setzen; Standard ohne Zeitplan. Der angezeigte nächste Lauf ist rechnerisch aus letztem Ende/Intervall, kein Beweis der STRATO-Ausführung. SEO-Onlineabruf bleibt BLOCKED ohne echten Adapter; CSV-Messhistorie funktioniert unabhängig.

Ohne bestätigten Cron im Systemcheck angemeldet „Fällige Regelaufgaben aktualisieren“ nutzen. Manueller CSRF-POST, keine fingierte Hintergrundautomatik. Wartungsmodus sperrt CLI-Einstiege; Cron/Schreibzugriffe für Update/Backup kontrolliert pausieren. Regelmäßigeprivate Backups/Logrotation und tatsächliche Restoreproben vorsehen, keine Zugangsdaten in Logs. Auf neue Kalenderjahre/abgelaufene Quellen achten.
