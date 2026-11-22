-- Einmalig nach v0.3 importieren. Bestehende Daten bleiben erhalten.
ALTER TABLE properties ADD COLUMN contact_name VARCHAR(190) NOT NULL DEFAULT '', ADD COLUMN contact_phone VARCHAR(80) NOT NULL DEFAULT '', ADD COLUMN contact_email VARCHAR(190) NOT NULL DEFAULT '';
