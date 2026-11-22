# Ergänzung Abfall360

Amtlich verlinkter Abfallnavi GT/RH tatsächlich per TLS geprüft; Adapter/Verbindungsprofile und manuelle Importe implementiert. Direkte PHP-HTTPS-Ausführung auf echtem STRATO noch nicht geprüft, in der hiesigen Netz-/TLS-Konfiguration BLOCKED. Keine allgemeinen Tonnenticker-API-/Deutschland-/SLA-Zusagen. QUELLEN_ABFALL.md und MODULE-STATUS-ABFALL360.md sind maßgeblich.

# Neuer tatsächlicher Status: Immobilien/Volksbank/Messdienst

Zwei private Volksbank-Konten als getrennte Slots im eigenen estate_-Modell; Offline-CSV mit freier Zuordnung/Quellen-ID und Vorschau funktionsfähig getestet. Kein Onlineabruf, kein echter Kontostand, keine tatsächliche Volksbankformatversion verifiziert. Sparkasse bleibt separat, keine Kontenvermischung.

ista/andere Messdienste: neutrales explizites Empfänger/Betrag-CSV und Originalbelegübernahme für extern geprüfte Heizwerte, eigene CO2-Entscheidung. Keine offizielle ista-API, kein allgemeiner ista-PDF-Parser/OCR und keine ungeprüfte Übernahme. Freigabe vor endgültiger Abrechnung. Keine Zusatzkosten/externen Abfragen.

Mail: v330 lokale Recovery stabilisiert; Native-IMAP nur Shim, STRATO weiterhin nicht verbunden. Bericht TESTBERICHT_MAIL_RECOVERY.md. Tatsächliche Konten nur über sichere Hostingkonfiguration und separate Freigabe anschließen.

---

## Fortschreibung 10.11.2026: nativer SMTP-Transport lokal geprüft

`GSZ_TEST_PHP=/workspace/gsz-tools/bin/php python3 tests/test_smtp_loopback.py` bestanden: acht Szenarien auf127.0.0.1:587 mit flüchtiger lokaler CA/Testzertifikat, echtem STARTTLS und nativem GszSmtpSender. Annahme/Dot-Stuffing, fehlendes STARTTLS, Auth-/Empfänger-/DATA-Ablehnung, verlorene DATA-Bestätigung, Verbindungsende nach Annahme und Zertifikatsablehnung geprüft. Keine echte Kontokonfiguration, keine externe Zustellung, keine Schlüssel/Mailinhalte im Paket. Test benötigt freien lokalen587-Port sowie Python/OpenSSL nur lokal. Native IMAP-Extension und STRATO weiterhin nicht angebunden/getestet; globale Versandfreigabe bleibt false.

Historischer Stand vor dieser Prüfung folgt:

# Integrationen – tatsächlicher Stand

| Bereich | Implementiert | Tatsächlich verbunden/getestet | Voraussetzung / Freigabe |
|---|---|---|---|
| STRATO IMAP | Native PHP-IMAP-Adapter, Cache/UIDVALIDITY/Ordner/Flags/Move/Append | Nicht verbunden; Services mit Mock geprüft, native PHP-IMAP-Erweiterung hier nicht vorhanden | Hostingversion/Extension, TLS/993, Postfachdaten ausschließlich sicher serverseitig, ausdrückliches Testkonto/Lesefreigabe, echte Ordnernamen prüfen |
| SMTP | Neuer MIME-/Mehr-Empfänger-Transport STARTTLS/587; bestehender Rechnungsversand bleibt geschützt | Kein echter Versand; Zustandsautomat/Fehler mit Mock und Versandsperre lokal geprüft, native TLS-Protokollstrecke nicht als geprüft behauptet | allow_mail_send=false bleibt Standard; erst Testkonto und ausdrückliche Freigabe; Auth-/Versandlimits im Tarif prüfen |
| Sparkasse | Read-only Interface, neutraler CSV-Import, Belegmatching | Kein Onlineanbieter, kein echter Kontostand, keine verifizierte Sparkassendatei | Repräsentative bereinigte Beispieldatei für Profilprüfung; PSD2/FinTS-Anbieter inkl. Kosten/Vertrag/Nutzerfreigabe separat prüfen. Nie PIN/TAN in Chat/Code |
| CAMT/MT940 | Nicht implementiert | Nicht geprüft | Exakte Version/Spezifikation und bereinigte Beispiele; keine pauschale Formatkompatibilität behaupten |
| Kalender/Tonnenticker | Phase-3-Offline ICS/CSV, Quellen-/Fachprüfung | Synthetische Tests; keine konkrete Objektquelle extern validiert | Zulässigen Einzelterminexport/amtliche CSV manuell nutzen; kein Scraping, keine erfundene API |
| Routing | Offline-Heuristik, deaktivierter dedizierter OSRM-Adapter, Cache/Koordinatenprüfung | Fake-Provider lokal; kein echter Routingdienst | Tatsächliche Adressen/Koordinaten, Datenübermittlung/Bedingungen/Kosten ausdrücklich prüfen und freigeben; keine öffentliche Demo voraussetzen |
| OCR | Nicht angebunden | Keine OCR-Genauigkeit behauptet | Manuelle Metadaten vollständig nutzbar. Falls später möglich: Hosting-/Datenschutz-/Kostenprüfung und echte Genauigkeitstests |
| E-Rechnung/DATEV/ELSTER | Keine validierte Verbindung; XML-Prototyp standardmäßig gesperrt | Nicht validiert | Verifizierte Spezifikation, Validierungswerkzeuge und fachliche Abnahme. Kein automatischer Steuer-/ELSTERversand |
| Lexware | Keine technische Abhängigkeit | Nicht verbunden | Eigenes Beleg-/Zahlungsmodell nutzbar; bestehende externe Software allenfalls Übergang bis Vollabnahme |
| KI/Push/Standorttracking | Nicht eingebaut | Keine Datenübermittlung | Optionaler späterer Auftrag mit Datenschutz-/Kostenfreigabe, keine Voraussetzung |

Mailkonfiguration ausschließlich private Serverkonfiguration/GSZ_-Variablen: mail_enabled, mail_host, mail_port=993, mail_user, mail_pass, smtp_host, smtp_port=587, mail_from, allow_mail_send, mail_sent_folder, mail_signature, mail_sync_limit. Keine echten Werte im Chat. Ordner vom Server ermitteln, nicht `Sent`/`Trash` als STRATO-Behauptung hardcoden. Lokale Entwürfe nicht automatisch zum IMAP-Drafts-Ordner synchronisiert. Papierkorb/Archiv durch bewusst gewählten echten Zielordner, kein endgültiges EXPUNGE.

Keine Kostenfreiheit eines späteren Banking-/Routinganbieters zugesagt. Selbstbetrieb von Routing benötigt Ressourcen/Datenpflege. Aktuell keine kostenpflichtigen APIs oder produktiven Konten genutzt. Externe Dienste werden durch Dashboard-GET nicht kontaktiert.
