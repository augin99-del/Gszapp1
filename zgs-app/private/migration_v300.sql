-- Local cash register and explicitly reviewed manual asset schedules. No bank or historical payment mutation.
CREATE TABLE IF NOT EXISTS cash_accounts (
 id TINYINT UNSIGNED PRIMARY KEY, name VARCHAR(190) NOT NULL,
 opening_on DATE NULL, opening_cents BIGINT NOT NULL DEFAULT 0,
 opening_note VARCHAR(500) NOT NULL DEFAULT '', initialized_by INT UNSIGNED NULL,
 initialized_at DATETIME NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
INSERT IGNORE INTO cash_accounts(id,name) VALUES(1,'Betriebskasse GSZ');
CREATE TABLE IF NOT EXISTS cash_entries (
 id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY, account_id TINYINT UNSIGNED NOT NULL DEFAULT 1,
 occurred_on DATE NOT NULL, kind ENUM('payment','deposit','withdrawal','correction') NOT NULL,
 amount_cents BIGINT NOT NULL, description VARCHAR(500) NOT NULL,
 payment_id BIGINT UNSIGNED NULL UNIQUE, reversal_of BIGINT UNSIGNED NULL UNIQUE,
 request_key CHAR(64) NOT NULL UNIQUE, admin_id INT UNSIGNED NOT NULL,
 created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
 FOREIGN KEY(account_id) REFERENCES cash_accounts(id) ON DELETE RESTRICT,
 FOREIGN KEY(payment_id) REFERENCES finance_payments(id) ON DELETE RESTRICT,
 FOREIGN KEY(reversal_of) REFERENCES cash_entries(id) ON DELETE RESTRICT,
 INDEX idx_cash_day(account_id,occurred_on,id), CHECK(amount_cents<>0)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
CREATE TABLE IF NOT EXISTS asset_sequences (
 year SMALLINT UNSIGNED PRIMARY KEY, last_number INT UNSIGNED NOT NULL DEFAULT 0
) ENGINE=InnoDB;
CREATE TABLE IF NOT EXISTS asset_register (
 id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY, number VARCHAR(40) NULL UNIQUE,
 name VARCHAR(190) NOT NULL, acquired_on DATE NOT NULL, in_service_on DATE NOT NULL,
 acquisition_cents BIGINT NOT NULL, residual_cents BIGINT NOT NULL DEFAULT 0,
 document_id BIGINT UNSIGNED NOT NULL, property_id INT UNSIGNED NULL,
 notes TEXT NULL, status ENUM('draft','approved','retired') NOT NULL DEFAULT 'draft',
 review_reference VARCHAR(500) NOT NULL DEFAULT '', reviewed_by INT UNSIGNED NULL,
 reviewed_at DATETIME NULL, retired_on DATE NULL, retirement_reason VARCHAR(500) NULL,
 row_version INT UNSIGNED NOT NULL DEFAULT 0, request_key CHAR(64) NOT NULL UNIQUE,
 created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
 FOREIGN KEY(document_id) REFERENCES documents(id) ON DELETE RESTRICT,
 FOREIGN KEY(property_id) REFERENCES properties(id) ON DELETE RESTRICT,
 INDEX idx_asset_status(status,acquired_on), CHECK(acquisition_cents>0),
 CHECK(residual_cents>=0 AND residual_cents<=acquisition_cents)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
CREATE TABLE IF NOT EXISTS asset_plan_rows (
 id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY, asset_id INT UNSIGNED NOT NULL,
 year SMALLINT UNSIGNED NOT NULL, amount_cents BIGINT NOT NULL,
 UNIQUE KEY uq_asset_year(asset_id,year),
 FOREIGN KEY(asset_id) REFERENCES asset_register(id) ON DELETE RESTRICT,
 CHECK(amount_cents>0)
) ENGINE=InnoDB;
CREATE TABLE IF NOT EXISTS asset_events (
 id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY, asset_id INT UNSIGNED NOT NULL,
 admin_id INT UNSIGNED NOT NULL, action VARCHAR(40) NOT NULL, detail_json TEXT NOT NULL,
 created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
 FOREIGN KEY(asset_id) REFERENCES asset_register(id) ON DELETE RESTRICT,
 INDEX idx_asset_events(asset_id,id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
INSERT IGNORE INTO app_migrations(version) VALUES('300_cash_assets');
