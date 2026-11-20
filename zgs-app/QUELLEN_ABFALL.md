# Amtliche Quellen und Komponentenprüfung – 12.11.2026

## Tatsächlich geprüfte kommunale Verweise

- Gütersloh: https://www.guetersloh.de/de/rathaus/fachbereiche-und-einrichtungen/umweltschutz/abfallberatung/abfallnavi.php
- Rheda-Wiedenbrück: https://www.rheda-wiedenbrueck.de/umwelt-bauen-verkehr/umwelt-klima/umwelt-abfall/abfall-muellabfuhr/
- Beide kommunalen Seiten antworteten HTTPS200 und verweisen auf Abfallnavi. Öffentlicher Kalenderclient: https://abfallnavi.de/krgt/
- Der öffentliche Client konfiguriert den tatsächlichen Datendienst `https://krwaf-abfallapp.regioit.de/abfall-app-krwaf/rest`. Dieser wurde abgerufen, kein Endpunkt erfunden. Benutzt: /orte, /orte/{id}/strassen?jahr=..., /strassen/{id}, /fraktionen, /strassen/{id}/termine?fraktion=...; hausnummern/{id}/fraktionen und /termine nur bei ausdrücklich angebotener Hausnummernzuordnung.
- Der Client benennt außerdem `https://abfallkalender.regioit.de/kalender-krwaf/downloadfile.jsp` als ICS-Export. Der neue automatische Adapter nutzt die tatsächlich geprüften strukturierten Antworten. Vorhandener sicherer manueller ICS-Import bleibt erhalten.
- Anbieterhinweise im Client: https://www.geg-gt.de/de/download/agb-satzungen-zertifkate/ und https://www.geg-gt.de/de/download/datenschutz-nach-dsgvo/

Ergebnisse:25 Kommunen im Dienst,59 Kalenderarten; GT1106, RH714 Straßen-/Bezirkeinträge. Daraus wird keine Freigabe sämtlicher25 Kommunen oder bundesweite Unterstützung abgeleitet. Register bewusst nur für die beiden nachweislich amtlich verlinkten Kommunen. Beispielprüfung Diekstraße: tatsächliche Straßenkennung/PLZ und245 Quelltermine in2026–2027, darunter Behältervarianten und allgemeine Veranstaltungstermine. Letztere sind keine automatisch betreuten Tonnen. Echte Antworten außerhalb des Lieferpakets gespeichert; PHP-Adapter gegen diese Antworten geprüft. Keine tatsächlichen Bestandskalender in die ausgelieferte Anwendung voreingespielt.

Kein Login/Schutzmechanismus umgangen, kein JS-Quellcode übernommen. Öffentlicher Client-Dienst ist nicht gleichbedeutend mit einer vertraglich garantierten API. Es liegen keine bestätigte API-Langzeitvereinbarung und kein SLA vor. Nur begrenzte, gecachte Abfragen für eigene Objektkalender. Ändert der Anbieter Zugriff oder Bedingungen, bleibt der manuelle Import nutzbar; Freigabestatus nicht erfinden.

## Kostenlose Open-Source-Komponenten geprüft

Packagist-Metadaten am12.11.2026 tatsächlich abgerufen:

| Kandidat | Version / Stand | Lizenz | Laufzeit | Entscheidung |
|---|---|---|---|---|
| sabre/vobject |5.0.0,07.07.2026|BSD-3-Clause|PHP^8.2, mbstring/json, sabre/xml|Grundsätzlich kommerziell nutzbar; für vorhandenen begrenzten Import unnötiger zusätzlicher XML-/Abhängigkeitsumfang |
| johngrogg/ics-parser |v3.6.0,30.09.2026|MIT|PHP>=5.6.40, mbstring|Grundsätzlich kommerziell nutzbar; keine zweite konkurrierende Interpretation komplexer Kalender eingeführt |
| Bestehender GSZ-Parser/Transport/Store | lokal vorhanden, Regressionen ausgeführt | vorhandener Projektcode | PHP8.2+/MariaDB10.6+, kein neuer Dienst | Wiederverwendet und um begrenzten JSON-Transport ergänzt |

Keine neue fremde Bibliothek eingebaut; bestehende Drittbibliotheken samt Lizenzen bleiben unverändert enthalten. Der neue Anbieteradapter ist eigener PHP-Code. Keine neuen laufenden Software-/API-Kosten aktiviert. Ein Provider kann Bedingungen künftig ändern; keine unbegrenzte kostenlose Nutzung zugesichert. STRATO benötigt weiterhin vorhandenes Hosting, PDO/MySQL, mbstring, OpenSSL, DNS-/HTTPS-Ausgangszugriff; kein Node/Python im Produktivbetrieb.
