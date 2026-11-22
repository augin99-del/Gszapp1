-- after v230; no changes to historical invoice/document business values
CREATE TABLE IF NOT EXISTS bank_accounts (
 id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY, name VARCHAR(190) NOT NULL UNIQUE,
 currency CHAR(3) NOT NULL DEFAULT 'EUR', opening_balance_cents BIGINT NULL,
 opening_date DATE NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
CREATE TABLE IF NOT EXISTS bank_imports (
 token CHAR(64) PRIMARY KEY, account_id INT UNSIGNED NOT NULL, admin_id INT UNSIGNED NOT NULL,
 file_hash CHAR(64) NOT NULL, payload LONGTEXT NOT NULL,
 created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP, applied_at DATETIME NULL,
 FOREIGN KEY(account_id) REFERENCES bank_accounts(id) ON DELETE RESTRICT
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
CREATE TABLE IF NOT EXISTS bank_transactions (
 id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY, account_id INT UNSIGNED NOT NULL,
 source_key CHAR(64) NOT NULL, record_hash CHAR(64) NOT NULL,
 external_id VARCHAR(190) NOT NULL, booked_on DATE NOT NULL, value_on DATE NOT NULL,
 amount_cents BIGINT NOT NULL, currency CHAR(3) NOT NULL,
 purpose VARCHAR(2000) NOT NULL, counterparty VARCHAR(500) NOT NULL,
 created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
 UNIQUE KEY uq_bank_source(account_id,source_key), INDEX idx_bank_date(booked_on),
 FOREIGN KEY(account_id) REFERENCES bank_accounts(id) ON DELETE RESTRICT
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
CREATE TABLE IF NOT EXISTS receipt_sequences (
 year SMALLINT UNSIGNED PRIMARY KEY, last_number INT UNSIGNED NOT NULL
) ENGINE=InnoDB;
CREATE TABLE IF NOT EXISTS finance_receipts (
 id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY, document_id BIGINT UNSIGNED NOT NULL,
 INDEX idx_receipt_document(document_id),
 number VARCHAR(40) NULL UNIQUE, status ENUM('draft','confirmed','corrected') NOT NULL DEFAULT 'draft',
 merchant VARCHAR(190) NOT NULL DEFAULT '', receipt_date DATE NULL,
 direction ENUM('expense','income') NOT NULL DEFAULT 'expense',
 gross_cents BIGINT NULL, net_cents BIGINT NULL, tax_cents BIGINT NULL,
 tax_rate SMALLINT UNSIGNED NULL, category VARCHAR(100) NOT NULL DEFAULT '',
 payment_method VARCHAR(40) NOT NULL DEFAULT '', property_id INT UNSIGNED NULL,
 predecessor_id BIGINT UNSIGNED NULL UNIQUE,
 correction_reason VARCHAR(500) NOT NULL DEFAULT '', version INT UNSIGNED NOT NULL DEFAULT 1,
 mail_attachment_id BIGINT UNSIGNED NULL UNIQUE,
 created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
 FOREIGN KEY(document_id) REFERENCES documents(id) ON DELETE RESTRICT,
 FOREIGN KEY(property_id) REFERENCES properties(id) ON DELETE RESTRICT,
 FOREIGN KEY(predecessor_id) REFERENCES finance_receipts(id) ON DELETE RESTRICT,
 FOREIGN KEY(mail_attachment_id) REFERENCES mail_attachments(id) ON DELETE RESTRICT
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
CREATE TABLE IF NOT EXISTS finance_payments (
 id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
 receipt_id BIGINT UNSIGNED NULL, invoice_id BIGINT UNSIGNED NULL,
 bank_transaction_id BIGINT UNSIGNED NULL,
 allocation_key BIGINT UNSIGNED NULL UNIQUE,
 paid_on DATE NOT NULL, amount_cents BIGINT NOT NULL, tax_cents BIGINT NOT NULL,
 request_key CHAR(64) NOT NULL UNIQUE, reversal_of BIGINT UNSIGNED NULL UNIQUE,
 reason VARCHAR(500) NOT NULL DEFAULT '', admin_id INT UNSIGNED NOT NULL,
 created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
 FOREIGN KEY(receipt_id) REFERENCES finance_receipts(id) ON DELETE RESTRICT,
 FOREIGN KEY(invoice_id) REFERENCES invoices(id) ON DELETE RESTRICT,
 FOREIGN KEY(bank_transaction_id) REFERENCES bank_transactions(id) ON DELETE RESTRICT,
 FOREIGN KEY(reversal_of) REFERENCES finance_payments(id) ON DELETE RESTRICT,
 CHECK ((receipt_id IS NULL) <> (invoice_id IS NULL)),
 INDEX idx_payment_date(paid_on)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
INSERT IGNORE INTO app_migrations(version) VALUES('240_finance_basis');
