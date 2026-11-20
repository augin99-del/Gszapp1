# Phase 3 – tatsächlicher Ausgangsbestand

Basis GSZ-Phase02.zip, SHA256 74010dfc561023108f2070f1aa2833742dbb032bc8dd0d0a5f05503d5bd1a304. Archiv geprüft: 82 Dateien, keine unsicheren Pfade/Symlinks/config.php/Backups. Isoliert /workspace/gsz-phase03/zgs-app.

Schema bis v210: tasks mit recurrence-Identität, plan_locked, planned_order, Notiz und Fälligkeitsfenster; recurring_jobs mit seed_key, Quelle, Dauer und Saison; planning_settings/days/appointments/proposals; insgesamt 17 Tabellen. Keine Abfallquelle, kein Abfuhrmodell, keine Koordinaten, kein Routing-Provider im Bestand. planArea() arbeitet mit Adresstext, planTimeline() mit manuellem Wechselpuffer. Vorschläge werden persistiert, über Bestandshash und bestätigte Transaktion übernommen.

Keine dokumentierte Tonnenticker-Backend-API im Code. Externe Quellen werden gesondert geprüft. Implementierungsstandard: offline ICS/CSV mit expliziter Fraktionszuordnung, keine URL-Abrufe im Produkt. Routenstandard offline, Providergrenze mit begrenztem Budget/Cache; keine öffentlichen Demo-Server als Produktivdienst.

Adresskonflikt: neuer Auftrag nennt Lippstädter Straße 5 Gütersloh; gespeicherte Phase-1-Stammdaten enthalten Rheda-Wiedenbrück nach expliziter Nutzerbestätigung. Keine stille Änderung; Importadresse muss bewusst gegen Objektakte geprüft werden.
