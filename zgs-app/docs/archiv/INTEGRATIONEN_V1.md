# Tatsächliche externe Integration / geprüfte Quellen

Prüfdatum11.11.2026. Öffentliche Dokumentation gelesen, kein produktives Konto angesprochen. Kein neuer Dienst gebucht, laufende Zusatzkosten0EUR aktiviert. Bestehende Hosting-/Mailkosten bleiben tarifabhängig. Keine behaupteten kostenlosen unbegrenzten Community-Routingdienste.

## STRATO-Mail

Offiziell IMAP imap.strato.de993SSL/TLS; normaler SMTP-Client smtp.strato.de465SSL/TLS.587STARTTLS nur beim tatsächlich bestätigten Profil; STRATO beschreibt587 insbesondere für Relay. Vollständige Mailadresse und Postfachpasswort serverprivat. POP3 nicht genutzt. PHPMailer-SMTP7.2.0 vorhandene Lizenz unverändert,465implizitesTLS ergänzt; native Rückfalloption nur587. Beide TLS-Modi lokal gegen synthetischen TLS-Server getestet, nicht STRATO. PHP8.4 liefert IMAP nicht mehr gebündelt; PECL-IMAP muss tatsächlich verfügbar sein. Kein falscher Schluss von einer deaktivierten Tarifoption auf den Postfachstatus. Sichere lokale Konfiguration fehlt; keine Postfachprüfung behauptet.

Quellen:
- https://www.strato.de/faq/mail/e-mailserver-adressen-ports-ssl-tls/
- https://www.strato.de/faq/mail/externes-e-mail-programm-mit-strato-e-mail-adresse-nutzen/
- https://www.php.net/manual/de/imap.installation.php

## Kalender

Gütersloh nennt offiziellen iCal-Download2026 und Tonnenticker. Amtliche Weblinks:
- https://www.guetersloh.de/de/rathaus/presseportal/news/meldungen/abfallkalender-2026.php
- https://www.guetersloh.de/de/rathaus/fachbereiche-und-einrichtungen/umweltschutz/abfallberatung.php
- https://www.guetersloh.de/de/rathaus/fachbereiche-und-einrichtungen/umweltschutz/abfallberatung/abfallnavi.php
- https://www.rheda-wiedenbrueck.de/umwelt-bauen-verkehr/umwelt-klima/umwelt-abfall/abfall-muellabfuhr/

Rheda-Seite enthält amtlichen Kalender/Abfallnavi, einzelne allgemeine Links verwiesen auf2025: nicht ungeprüft verwenden. Keine dokumentierte frei zugängliche Tonnenticker-API oder stabile adressbezogene ICS-Abo-URL nachgewiesen; keine erfunden. Offizielle Seite im eigenen Browser öffnen, korrekte Adresse/Jahr/Fraktionen wählen, ICS/CSV exportieren und importieren. Ein Jahresdownload allein ist noch kein laufend aktualisiertes Abo.

Privater optionaler Serverkonfigurationsschlüssel `waste_sync_config_json`: JSON-Liste mit source_id (vorher im Müllkalender angelegte echte Quelle), url (tatsächlicher freigegebener HTTPS-ICS-Link), allowed_host (exakter Host), auto_apply:false und mapping (exakte Originalbezeichnung→Restmüll/Bio/Papier/Gelb entsprechend angezeigtem Auswahlkatalog). Keine Beispiel-URL als echte Quelle übernehmen. Max16 Quellen, HTTPS443, DNS-Prüfung und gepinnte öffentliche Ziel-IP, TLS-Prüfung, keine Redirects/Authentifizierungs-URLs,8sTimeout,1MBICS. Abolinks mit geheimen Tokens ausschließlich serverprivat behandeln.

Erst Download→Vorschau→Fraktionen/Adresse/Quelldatum/Gültigkeit prüfen→ausdrücklich freigeben. auto_apply:true nur bei bewusst freigegebener und bereits geprüfter Quelle. Geänderte IDs/Daten, unklare Fraktionen und Adressabweichungen bleiben Prüfkonflikte; erledigte/gesperrte Aufgaben werden nicht geändert. Eindeutige zuvor bestätigte Fraktionszuordnung darf wiederverwendet werden. Offene Vorschauen sind im Müllkalender sichtbar und können explizit neu zugeordnet werden. Quellen-Abruf ist noch keine fachliche Freigabe.

Objektbezogene Raus-/Rein-Versätze -3..0/0..3 Tage werden ausschließlich für neue Aufgaben verwendet. Defaults Vortag/Abfuhrtag,15Minuten je Richtung. Explizites Bündeln mehrerer Fraktionen pro Besuch15Minuten nur nach Auswahl; sonst15jeFraktion. Keine stillschweigende Änderung alter Aufgaben. FontänenstraßeMi/Do-Sonderregel wiederverwenden, normale historische Routinen pausiert lassen.

## Routing, Banking, OCR

Unveränderte Offline-Gebietsheuristik mit klar geschätztem manuellem Puffer; keine echten Kilometer/Echtzeitverkehr behauptet. OSRM-Adapter deaktiviert, echte Abfragen nur nach separater Freigabe. Sparkasse und zwei getrennte Volksbank-Konten bleiben vorhandene Offline-CSV-/Prüfworkflows; keine Onlineanmeldung, keine Überweisungen. Kein externer OCR-/KI-Server aktiviert. Bestehende Lizenzübersicht OPEN_SOURCE_PRUEFUNG.md bleibt erhalten.
