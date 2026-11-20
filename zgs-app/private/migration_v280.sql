-- Confirmed local payment reminders only; no fees, SMTP or changes to invoice balances.
CREATE TABLE IF NOT EXISTS invoice_reminder_proposals (
 token CHAR(64) PRIMARY KEY, admin_id INT UNSIGNED NOT NULL,
 invoice_id INT UNSIGNED NOT NULL, fingerprint CHAR(64) NOT NULL,
 payload TEXT NOT NULL, created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
 applied_id INT UNSIGNED NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
CREATE TABLE IF NOT EXISTS invoice_reminders (
 id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY, invoice_id INT UNSIGNED NOT NULL,
 stage TINYINT UNSIGNED NOT NULL, issued_on DATE NOT NULL, due_on DATE NOT NULL,
 outstanding_cents BIGINT NOT NULL, body TEXT NOT NULL,
 pdf_sha256 CHAR(64) NOT NULL, pdf_size INT UNSIGNED NOT NULL,
 created_by INT UNSIGNED NOT NULL, created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
 UNIQUE KEY uq_reminder_stage(invoice_id,stage),
 INDEX idx_reminder_invoice(invoice_id,id),
 CHECK(stage BETWEEN 1 AND 3), CHECK(outstanding_cents>0)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
INSERT IGNORE INTO app_migrations(version) VALUES('280_receivables');
