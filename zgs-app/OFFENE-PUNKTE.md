# Offene Punkte (vor Ort / mit echten Daten zu prüfen)

Diese Punkte konnten bei der Entwicklung nicht geprüft werden und werden hier nicht als erledigt ausgegeben.

1. **Update auf STRATO real durchführen** (INSTALLATION-STRATO.md, Teil A) und Systemstatus prüfen.
   Insbesondere: Dürfen die alten Finanzdateien vom PHP-Prozess gelöscht werden? Sonst im Systemstatus gelistete
   Dateien per Dateimanager löschen.
2. **Sicherung in phpMyAdmin importieren** einmal testweise ausprobieren (nicht in die Live-Datenbank, sondern in eine
   zweite, leere STRATO-Datenbank), damit der Rollback-Weg vor Ort bekannt ist. Große Sicherungen können am
   phpMyAdmin-Uploadlimit scheitern; dann SSH-Weg (ROLLBACK.md).
3. **Erster echter Volksbank-Export** (CSV) je Konto hochladen und prüfen, ob Umsätze, Beträge, Daten und Zahler
   stimmen. Die Erkennung ist dem dokumentierten VR-Banking-Format nachgebaut, aber nicht mit einer echten Datei getestet.
   Bei Problemen die Datei **nicht** weitergeben (Bankdaten), sondern nur die Kopfzeile (Spaltennamen) prüfen.
4. **Echtes Postfach**: Am 22.11.2026 auf STRATO eingetragen; „Verbindung testen“ meldete Empfang und
   Versand-Anmeldung in Ordnung; Gesendet-Ordner „Sent Items“ erkannt. Testmail an eine eigene Adresse kam an,
   Gesendet-Kopie bestätigt (Bildschirmfotos des Inhabers). Noch offen: Abruf des Posteingangs (zeigte noch keine Mails).
   Die Schutzschaltung bei falschem Passwort wurde nur simuliert.
5. **Cronjob** bei STRATO einrichten (CRONJOBS.md) und nach einem Tag im Systemstatus den letzten Lauf prüfen.
6. **Geräte**: iPhone (Safari, Home-Bildschirm), iPad hoch/quer, MacBook (Safari/Chrome) einmal durchklicken.
   Die Oberflächen-Tests liefen in Chromium mit den Breiten 390/820/1180/1440 px – kein echter Gerätetest.
7. **Konto = Haus** nach dem Update einmal zuordnen; Zahlernamen für Mieter ergänzen, die nicht selbst überweisen.
8. **Abfallkalender**: Feiertagsverschiebungen werden beim automatischen Abgleich neu zusammengesetzt, wenn der
   Anbieter dieselbe Termin-ID mit neuem Datum liefert. Ob der amtliche Kalender (Abfallnavi) das so tut, ist nicht
   real beobachtet. Sonst erscheint die Änderung wie bisher als „Neue Abfuhrtermine prüfen“.
9. Historische Hinweise in `docs/archiv/` beschreiben ältere Stände und sind keine aktuelle Anleitung.
