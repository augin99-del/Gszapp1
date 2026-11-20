GSZ-ZENTRALE FINAL / SCHEMA350 – 12.11.2026

Aktueller Einstieg: INSTALLATION-STRATO.md, TESTSTATUS-FINAL.md, MODULE-STATUS.md,
CHANGELOG-FINAL.md und INTEGRATIONEN-FINAL.md. Vollpaket und Update sind bereinigt;
keine tatsächlichen Zugangsdaten oder privaten Originale enthalten.

Schema350 hat82Tabellen. Bestehende330/340-Datenbanken nur nach verifizierter
Sicherung auf Testkopie über den geprüften Upgrade-Runner aktualisieren.
Fresh verweigert nichtleere Datenbanken. Keine alten SQL-/Setupanleitungen
nachfolgend erneut auf Bestandsdaten ausführen. Kein ungeschützter Webinstaller.

Das Dashboard erzeugt keine Aufgaben allein durch Öffnen. Expliziter sicherer
Owner-POST oder tatsächlich eingerichteter CLI-Cron erzeugt Aufgaben und einen
unbestätigten Wochenvorschlag. Produktivdeployment und echte Testmails separat
freigeben. Die tatsächliche STRATO-/Postfachabnahme steht aus.

Die folgenden älteren Inhalte bleiben ausschließlich als Historie erhalten.

---------------- HISTORIE ----------------

# GSZ – erster Installationsstand331

1. GO_LIVE_STATUS.md lesen: lokal geprüfter Kernbetrieb, noch keine STRATO-/Produktivfreigabe.
2. INSTALLATION_KURZ_STRATO.md im getrennten Hosting-Testbereich befolgen. Domainwurzel ausschließlich public, keine Konfiguration/Uploads überschreiben. Fresh nur leere DB; für vorhandenes v330 kein neues SQL.
3. Systemcheck CLI und nach Login im Browser ausführen, danach TAEGLICHE_NUTZUNG.md auf iPhone/Computer prüfen. Testergebnisse TESTBERICHT_INBETRIEBNAHME.md.
4. Erst nach Hosting-/Restoreprüfung konkreten Produktivschritt gesondert freigeben. Keine Passwörter/TAN im Chat. Fortsetzung CODEX_FORTSETZUNG.md.
