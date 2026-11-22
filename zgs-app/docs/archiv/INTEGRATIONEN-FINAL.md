# Integrationen, geprüfte Komponenten und Kosten – 12.11.2026

Keine aktuellen Hosting-/Mail-/Bankzugänge im Workspace oder in der Umgebung konfiguriert. Keine echten Konten angesprochen, keine Mail gesendet, keine kostenpflichtigen Dienste aktiviert. Neue laufende Softwarekosten durch diese Lieferung:0EUR aktiviert. Vorhandenes STRATO/Bankkosten und externe künftige API-Preise sind nicht hier geprüft.

## Amtliche Müllkalender

Gütersloh: städtische Abfallberatung, Tonnenticker/Abfallnavi der GEG/regioi T. Rheda-Wiedenbrück: städtische Abfallberatung, GEG/Tonnenticker, gleicher Abfallnavi-Client. Amtliche Seiten per HTTPS gelesen:

- https://www.guetersloh.de/de/rathaus/fachbereiche-und-einrichtungen/umweltschutz/abfallberatung/abfallnavi.php
- https://www.guetersloh.de/de/rathaus/presseportal/news/meldungen/abfallkalender-2026.php
- https://www.rheda-wiedenbrueck.de/umwelt-bauen-verkehr/umwelt-klima/umwelt-abfall/abfall-muellabfuhr/
- https://abfallnavi.de/krgt/

Gütersloh bettet diesen Client periframe ein; Rheda lädt dessen öffentliche Clientskripte. Im tatsächlich ausgelieferten Client sind ICS-Exporte für Straße/Hausnummer und die Exportbasis `https://abfallkalender.regioit.de/kalender-krwaf/downloadfile.jsp` belegt. **Diese Basis allein ist kein verwendbarer Objektkalender!** Objekt-/Fraktionsparameter nur aus einer tatsächlichen Auswahl im amtlichen UI übernehmen; keine IDs/Termine geraten. Keine dokumentierte frei nutzbare allgemeine Tonnenticker-API nachgewiesen. Einzelne allgemeine Rheda-Links führten noch zu2025-PDFs; den gesonderten2026-Link verwenden.2027vor Nutzung tatsächlich prüfen.

Vollständige bisherige Katalogadressen: Fröbelstraße19/Gütersloh, Fontänenstraße29–31/Rheda-Wiedenbrück, Aufder Warte8–10/Rheda-Wiedenbrück, Lippstädter Straße5/Rheda-Wiedenbrück, Heiligen Häuschenweg2–4/Rheda-Wiedenbrück, Diekstraße3/Gütersloh, Berliner Straße45/Gütersloh. Neuester Auftrag nennt Fontänen/Lippstädter in Gütersloh. Keine echte produktive DB liegt vor: gespeicherten Ort/Hausnummer vor Quellenfreigabe prüfen, nicht automatisch ändern.

Im Müllkalender amtlichen Link öffnen→Ort/Straße/Hausnummer/Jahr/Fraktionen wählen→ICSexportieren→Quelle mit vollständiger Adresse/Quelldatum/Gültigkeit anlegen→hochladen→Fraktionen explizit zuordnen→Vorschau/Konflikt prüfen→fachlich freigeben. Für Folgesynchronisation tatsächlichen Export-/Abolink serverprivat als waste_sync_config_json konfigurieren (source_id,url,allowed_host,mapping,auto_apply:false). Exakter HTTPS-Host443, öffentliche DNS/IP, Zertifikatsprüfung, keine Redirects, Größen-/Zeitlimits. Download allein ist keine fachliche Freigabe. Nur nach bewusst geprüfter Quelle auto_apply:true.

Abweichende Quelle/UID/Datum/Fraktion/Adresse oder gespeicherte Wochentagsroutine erzeugt Konflikt. Bei Feiertagsverschiebung offiziellen Datensatz zunächst ungeprüft mit erhaltenen alten Aufgaben übernehmen, danach Einzeltermin bewusst prüfen. Alte erledigte/gesperrte/verschobene Aufgaben und Routinen bleiben erhalten. Keine normale unbestätigte Routine wird aktiviert. Fontänen-Gelb Mi/Do wird wiederverwendet. Nur bestätigte konkrete Daten erzeugen neue Raus/Reinaufgaben nach Objektversatz; Default Vortag/Abfuhrtag,15Minuten. Absichtliches Bündeln ist eine ausdrückliche Zeitentscheidung.

Adressgenaue Objekt-Exports und echte Abrufsynchronisation sind weiterhin BLOCKED/nicht nachgewiesen. Ein öffentlicher Browserprobeaufruf scheiterte an der lokalen Chromium-Proxy-CA; TLS-gesicherte HTML-/Clientrecherche und synthetische Parser-/Importtests waren möglich. Schutzmechanismen wurden nicht umgangen. Kein echter Abfuhrkalender ist als verifiziert in die Anwendung eingetragen.

## STRATO-Mail

Bestehende PHPMailer7.2.0/LGPL2.1-SMTP-Komponente und native IMAP-Implementierung erhalten.465implizites TLS/587STARTTLS lokal mit23synthetischen TLS-Szenarien geprüft; keine echte Zustellung. MIME/Cache/UIDVALIDITY/Entwürfe/Anhänge/Sent-Recovery zusätzlich per Mock/DB/HTTP geprüft. Native IMAP fehlt lokal; tatsächliche STRATO-Erweiterung/Postfachstatus unbekannt. Webklex6.2.0/MIT bleibt geprüfter, nicht integrierter Alternativkandidat mit weiteren Abhängigkeiten. Keinen angeblich eingebauten nativen Ersatz behaupten.

Offizielle Quellen:
- https://www.strato.de/faq/mail/e-mailserver-adressen-ports-ssl-tls/
- https://www.strato.de/faq/mail/externes-e-mail-programm-mit-strato-e-mail-adresse-nutzen/
- https://www.php.net/manual/de/imap.installation.php

Private Konfiguration: imap.strato.de993SSL/TLS, smtp.strato.de465SSL/TLS, vollständige eigene Mailadresse/Postfachpasswort/verifizierter Absender. POP3nicht verwenden. Postfachaktivität nicht aus einer unbekannten Zusatz-Tarifoption ableiten. mail_enabled erst nach sicherer Einrichtung, allow_mail_send bis konkreter Testfreigabefalse. Gesendet/Archiv/Papierkorb aus tatsächlicher Ordnerliste wählen. SMTP-Annahme bedeutet nicht endgültige Zustellung; unklaren Status niemals automatisch erneut senden. Reale Empfangs-/Versand-/Sent-/Anhangstests BLOCKED.

## Banking/CAMT

Bestehende CSV-/Matching-/Buchhaltungs-/Mietservices wiederverwendet. Kein Onlineabrufadapter aktiviert, keine PIN/TAN/Überweisungen. Genkgo/CAMT-MIT-Kandidat war bereits geprüft; dessen zusätzlicher Money-/IBAN-/Framework-Abhängigkeitsbaum wurde für den begrenzten vorhandenen Centworkflow nicht ungeprüft übernommen. Diese Lieferung nutzt native PHP-DOM/libxml und einen engen, eigenen ISO20022-Reader; keine fremde Banking-Anwendung kopiert. DOMlokal aus offiziellem Debian PHP 8.4.24-Paket nur für QA eingerichtet, keine Serverbinary im ZIP.

Unterstützt: UTF-8XML≤2MB, camt.053/054 .001.02/04/08/10/11, nur gebuchte EUR-Einzelumsätze, eindeutiger Status/Betrag/Datum, gültige erwartete IBAN und stabile Acct Svcr Ref/Ntry Ref/Tx Id. Stornozeichen bleibt nachvollziehbar, CRDT/DBIT bestimmt Vorzeichen. Keine DTD/Entities/Fremdnamensräume/XInclude/externen Schemaabrufe. Ressourcenlimits20k Elemente/40Ebenen, konfigurierte Zeilenlimits. Sammelbuchungen mit mehreren Tx Dtls, Fremdwährung, vorgemerkte Umsätze, fehlende stabile Referenz oder unbekannte Nachrichten werden zur Prüfung abgewiesen. Kein vollständiger XSD-/Bankprofil-Konformitätsnachweis.

Vorschau verändert keine Umsätze. Erster bestätigter CAMT-Import bindet nur den IBAN-Hash an genau ein Geschäfts-/Immobilienkonto. Wechsel der Identität oder Wiederverwendung zwischen Sparkasse/Volksbank1/Volksbank2 wird blockiert, auch im Paralleltest. Aliasänderung hebt diese Bindung nicht auf. Eine fälschlich bestätigte Bindung nur nach geordneter fachlicher/technischer Prüfung korrigieren, nicht per spontaner SQL-Löschung umgehen.

Stabile Konto-/Quellenidentität verhindert Wiederholungsduplikate. Bei CAMT-/CSV-Wechsel werden weitere ähnliche Bewegungen mit gleichem Tag/Betrag zur ausdrücklichen Prüfung vorgelegt; abweichende Quellen-IDs mit abweichendem Inhalt bleiben gesperrt. Unterschiedliche echte Zahlungen nicht automatisch verschlucken. Vergleich ist keine garantiert vollständige Dublettenerkennung bei abweichenden Datums-/Betrags-/Referenzformaten; vor Formatwechsel überlappende Bankauszüge kontrollieren.

Neutrale CSV benötigt UTF-8,EUR,ISO-Datum, explizite Feldzuordnung/stabile ID. Keine echten Sparkassen-/Volksbankdateien liegen vor; herstellerspezifische CSV-MT940/Windows1252/zweistellige Jahre/fehlende IDs sind nicht als verifiziert unterstützt ausgegeben. Solche Dateien sicher offline nach neutralem Format konvertieren oder den unterstützten CAMT-Export wählen, keine erfundenen IDs erzeugen. Proprietäres MT940 nicht integriert. Kein online bestätigter Kontostand, Summe importierter Bewegungen bleibt entsprechend beschriftet.

## SEO / Open Source

GeoGrid geprüft: https://github.com/orenagassy/geogrid , LICENSEund README aus tatsächlichem main gelesen. MIT, kommerzielle Nutzung unter Lizenzbedingungen möglich; Python/Fast API/Playwright/SQLite und browserbasiertes Maps-Auslesen passen nicht als Laufzeitbasis in das bestehende STRATO-PHP-System. Optionaler Data For SEO-Pfad ist kostenbehaftet laut Projekt README, kein bestätigtes Angebot. Kein GeoGrid-/nahaus-Code/Oberfläche kopiert, kein Scraper als Pflichtgrundlage eingebaut.

Funktionierend integriert: eigene Keywordverwaltung,121Punkte-CSV-Vorschau/Freigabe, GeoGrid/Top3/Wettbewerber je Punkt, unveränderte Historie,7Tage-Vergleich nur gleicher Quelle/Grid/Messtiefe,CSVexport.0bedeutet bis Messtiefe nicht gefunden, nicht fehlende Messung. CSV-Quelle ist Nutzerangabe; keine unabhängige Liveverifikation. Keine Rankings werden erfunden/mitgeliefert. Wöchentliche Auswertung erfolgt beim Öffnen/Export, kein automatischer Berichtversand.

Live-Rankingprovider, Google Search Console-/Unternehmensprofil-OAuthadapter sind nicht implementiert/verbunden. Anbieterwahl/Bedingungen, Kostenkontingente und Autorisierung müssen vor einer späteren tatsächlichen Integration geklärt werden. Keine kostenpflichtigen Testanfragen. Offizielle API-Dokumentation: https://developers.google.com/webmaster-tools/about , https://developers.google.com/my-business/content/overview . Ein Google Maps-Places-Ergebnis ist nicht automatisch ein geprüftes lokales Ranking.

Vorhandenes tFPDF/Deja Vu und PHPMailer mit Original-Lizenzdateien erhalten. Neue Runtimepflicht nur PHP-DOM für CAMT, keine zusätzliche monatliche Software/Saa S. Vollständige ältere Bibliotheksprüfung OPEN_SOURCE_PRUEFUNG.md.
