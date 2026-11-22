-- Additive PDF originals for NEW explicit document issuance only. No historical backfill.
ALTER TABLE invoices
 ADD COLUMN IF NOT EXISTS archived_pdf_sha256 CHAR(64) NULL,
 ADD COLUMN IF NOT EXISTS archived_pdf_size INT UNSIGNED NULL,
 ADD COLUMN IF NOT EXISTS archived_pdf_at DATETIME NULL;
INSERT IGNORE INTO app_migrations(version) VALUES('260_pdf_originals');
