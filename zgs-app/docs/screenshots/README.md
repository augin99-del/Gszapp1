# Vorher-/Nachher-Screenshots

Erstellt am 20.11.2026 mit Chromium (Playwright) in den Breiten **390** (iPhone), **820** (iPad hoch), **1180** (iPad quer),
**1440** (MacBook); ganze Seite, Pixelverhältnis 1. Kein Ersatz für einen Test auf echten Geräten.

Datenbasis: dieselben synthetischen Vorführdaten (`tests/dev/screens_fixture.php`).
- `vorher/`: unverändertes Paket 370 auf einer 370-Datenbank.
- `nachher/`: Paket 380 auf derselben Datenbank nach echter Migration 370 → 380 und Zuordnung Konto = Haus.
- `vergleich/`: oberer Seitenbereich nebeneinander (links vorher, rechts nachher).

Seiten vorher: index (Dashboard), planner (Woche), estate_bank, mail, waste, customers.
Seiten nachher: index (Heute), planner (Woche), objects, estate_bank (Immobilien), mail, more (Mehr).
Hinweis 370: In der Kopfzeile der alten Planungsseite war roher SVG-Code sichtbar (Fehler im alten Layout).
