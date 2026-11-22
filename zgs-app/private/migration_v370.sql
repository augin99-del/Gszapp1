-- Additive connection metadata only; transactions remain in existing business/estate tables.
CREATE TABLE IF NOT EXISTS bank_connections (
 slot VARCHAR(16) PRIMARY KEY, admin_id INT UNSIGNED NOT NULL,
 provider VARCHAR(40) NOT NULL DEFAULT 'enablebanking', institution VARCHAR(190) NOT NULL DEFAULT '',
 status VARCHAR(24) NOT NULL DEFAULT 'not_connected',
 business_account_id INT UNSIGNED NULL, estate_account_id TINYINT UNSIGNED NULL,
 iban_hash CHAR(64) NULL, state_hash CHAR(64) NULL, pending_until DATETIME NULL,
 payload MEDIUMTEXT NOT NULL, consent_until DATETIME NULL,
 last_attempt_at DATETIME NULL,last_success_at DATETIME NULL,last_error_code VARCHAR(80) NOT NULL DEFAULT '',
 balance_cents BIGINT NULL,balance_type VARCHAR(20) NULL,balance_at DATETIME NULL,
 UNIQUE KEY uq_bank_connection_iban(iban_hash),UNIQUE KEY uq_bank_oauth_state(state_hash),
 FOREIGN KEY(admin_id) REFERENCES admins(id) ON DELETE RESTRICT,
 FOREIGN KEY(business_account_id) REFERENCES bank_accounts(id) ON DELETE RESTRICT,
 FOREIGN KEY(estate_account_id) REFERENCES estate_bank_accounts(id) ON DELETE RESTRICT,
 CHECK(slot IN ('sparkasse','volksbank1','volksbank2')),
 CHECK((slot='sparkasse' AND estate_account_id IS NULL) OR (slot='volksbank1' AND business_account_id IS NULL AND (estate_account_id IS NULL OR estate_account_id=1)) OR (slot='volksbank2' AND business_account_id IS NULL AND (estate_account_id IS NULL OR estate_account_id=2)))
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
INSERT IGNORE INTO app_migrations(version) VALUES('370_bank_connections');
