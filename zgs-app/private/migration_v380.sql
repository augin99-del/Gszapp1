-- GSZ 380. Nur über gszMigrate380() ausführen (GUI-Einrichtung, geschütztes Update oder migrate_gesamt.php),
-- niemals manuell: vorher werden Fremdschlüssel anderer Module auf Finanztabellen dynamisch gelöst und eine
-- Sicherung verlangt. Jede Anweisung ist wiederholbar (IF EXISTS / IF NOT EXISTS).
-- Teil A: Finanzblock entfernen. Ausschließlich Tabellen, die nur Angebote/Rechnungen/Offene Posten/Mahnungen,
-- EÜR/Belege, Finanzvorlagen, Kasse/Anlagen und das Geschäftskonto (Sparkasse) inkl. Live-Bankanbindung betreffen.
SET FOREIGN_KEY_CHECKS=1;
DROP TABLE IF EXISTS bank_connections;
DROP TABLE IF EXISTS cash_entries;
DROP TABLE IF EXISTS cash_accounts;
DROP TABLE IF EXISTS asset_plan_rows;
DROP TABLE IF EXISTS asset_events;
DROP TABLE IF EXISTS asset_register;
DROP TABLE IF EXISTS asset_sequences;
DROP TABLE IF EXISTS finance_payments;
DROP TABLE IF EXISTS finance_receipts;
DROP TABLE IF EXISTS finance_recurrence_occurrences;
DROP TABLE IF EXISTS finance_recurrence_proposals;
DROP TABLE IF EXISTS finance_recurrence_revisions;
DROP TABLE IF EXISTS finance_recurrences;
DROP TABLE IF EXISTS invoice_reminder_proposals;
DROP TABLE IF EXISTS invoice_reminders;
DROP TABLE IF EXISTS invoice_events;
DROP TABLE IF EXISTS invoice_items;
DROP TABLE IF EXISTS invoices;
DROP TABLE IF EXISTS invoice_sequences;
DROP TABLE IF EXISTS offer_sequences;
DROP TABLE IF EXISTS receipt_sequences;
DROP TABLE IF EXISTS bank_imports;
DROP TABLE IF EXISTS bank_transactions;
DROP TABLE IF EXISTS bank_accounts;
DELETE FROM automation_state WHERE job_key='bank';
-- Teil B: Mietabgleich der zwei Volksbank-Vermieterkonten (additiv, Immobilientabellen bleiben erhalten).
ALTER TABLE estate_bank_accounts ADD COLUMN IF NOT EXISTS building_id INT UNSIGNED NULL;
ALTER TABLE estate_bank_accounts ADD UNIQUE KEY IF NOT EXISTS uq_estate_account_building(building_id);
ALTER TABLE estate_bank_accounts ADD CONSTRAINT fk_estate_account_building FOREIGN KEY IF NOT EXISTS (building_id) REFERENCES estate_buildings(id) ON DELETE RESTRICT;
ALTER TABLE estate_tenants ADD COLUMN IF NOT EXISTS payer_names VARCHAR(500) CHARACTER SET utf8mb4 NOT NULL DEFAULT '';
ALTER TABLE estate_tenants ADD COLUMN IF NOT EXISTS iban_hash CHAR(64) CHARACTER SET utf8mb4 NULL;
ALTER TABLE estate_bank_transactions ADD COLUMN IF NOT EXISTS counterparty_iban_hash CHAR(64) CHARACTER SET utf8mb4 NULL;
CREATE TABLE IF NOT EXISTS estate_bank_uploads (
 id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,account_id TINYINT UNSIGNED NOT NULL,admin_id INT UNSIGNED NOT NULL,
 uploaded_on DATE NOT NULL,uploaded_at DATETIME NOT NULL,file_hash CHAR(64) NOT NULL,format VARCHAR(20) NOT NULL,
 period_from DATE NOT NULL,period_to DATE NOT NULL,rows_total INT UNSIGNED NOT NULL,rows_new INT UNSIGNED NOT NULL,rows_known INT UNSIGNED NOT NULL,
 INDEX idx_estate_upload_month(account_id,uploaded_on),
 FOREIGN KEY(account_id) REFERENCES estate_bank_accounts(id) ON DELETE RESTRICT,CHECK(period_from<=period_to)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
