-- Technical offer and correction documents; explicit review remains required.
ALTER TABLE invoices
 ADD COLUMN IF NOT EXISTS document_kind ENUM('invoice','offer','credit_note') NOT NULL DEFAULT 'invoice',
 ADD COLUMN IF NOT EXISTS source_document_id BIGINT UNSIGNED NULL,
 ADD COLUMN IF NOT EXISTS correction_document_id BIGINT UNSIGNED NULL;
CREATE UNIQUE INDEX IF NOT EXISTS uq_invoice_source_kind ON invoices(source_document_id,document_kind);
CREATE TABLE IF NOT EXISTS offer_sequences (
 year SMALLINT UNSIGNED PRIMARY KEY, last_number INT UNSIGNED NOT NULL DEFAULT 0
) ENGINE=InnoDB;
INSERT IGNORE INTO app_migrations(version) VALUES('250_documents');
