-- Immutable, explicitly approved private settlement snapshots. No business accounting mutations.
ALTER TABLE estate_allocations ADD COLUMN IF NOT EXISTS allocation_from DATE NULL,
 ADD COLUMN IF NOT EXISTS allocation_to DATE NULL;
CREATE INDEX IF NOT EXISTS idx_estate_bank_amount ON estate_bank_transactions(amount_cents,booked_on,account_id);
CREATE TABLE IF NOT EXISTS estate_settings (
 id TINYINT UNSIGNED PRIMARY KEY,landlord_name VARCHAR(190) NOT NULL DEFAULT '',
 landlord_address VARCHAR(2000) NOT NULL DEFAULT '',row_version INT UNSIGNED NOT NULL DEFAULT 0
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
INSERT IGNORE INTO estate_settings(id) VALUES(1);
CREATE TABLE IF NOT EXISTS estate_costs (
 id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,building_id INT UNSIGNED NOT NULL,document_id INT UNSIGNED NOT NULL,
 source_position VARCHAR(190) NOT NULL,title VARCHAR(190) NOT NULL,
 category ENUM('operating','heating','maintenance','administration','other_non_apportionable') NOT NULL,
 amount_cents BIGINT NOT NULL,period_from DATE NOT NULL,period_to DATE NOT NULL,apportionable TINYINT NOT NULL DEFAULT 0,
 legal_basis VARCHAR(2000) NOT NULL DEFAULT '',allocation_json TEXT NOT NULL,co2_json TEXT NULL,
 allocation_document_id INT UNSIGNED NULL,duplicate_reason VARCHAR(1000) NOT NULL DEFAULT '',
 approved TINYINT NOT NULL DEFAULT 0,voided_at DATETIME NULL,void_reason VARCHAR(1000) NULL,
 row_version INT UNSIGNED NOT NULL DEFAULT 0,created_by INT UNSIGNED NOT NULL,created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
 UNIQUE KEY uq_estate_cost_position(building_id,document_id,source_position),
 INDEX idx_estate_cost_period(building_id,period_from,period_to),
 FOREIGN KEY(building_id) REFERENCES estate_buildings(id) ON DELETE RESTRICT,
 FOREIGN KEY(document_id) REFERENCES estate_documents(id) ON DELETE RESTRICT,
 FOREIGN KEY(allocation_document_id) REFERENCES estate_documents(id) ON DELETE RESTRICT,
 CHECK(period_to>=period_from),CHECK(amount_cents<>0)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
CREATE TABLE IF NOT EXISTS estate_settlement_proposals (
 token CHAR(64) PRIMARY KEY,admin_id INT UNSIGNED NOT NULL,building_id INT UNSIGNED NOT NULL,
 fingerprint CHAR(64) NOT NULL,payload LONGTEXT NOT NULL,created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
 applied_id INT UNSIGNED NULL,FOREIGN KEY(building_id) REFERENCES estate_buildings(id) ON DELETE RESTRICT
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
CREATE TABLE IF NOT EXISTS estate_settlements (
 id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,building_id INT UNSIGNED NOT NULL,
 period_from DATE NOT NULL,period_to DATE NOT NULL,as_of DATE NOT NULL,version INT UNSIGNED NOT NULL,
 proposal_token CHAR(64) NOT NULL UNIQUE,snapshot_json LONGTEXT NOT NULL,snapshot_hash CHAR(64) NOT NULL,
 supersedes_id INT UNSIGNED NULL,created_by INT UNSIGNED NOT NULL,created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
 UNIQUE KEY uq_estate_settlement_version(building_id,period_from,period_to,version),
 FOREIGN KEY(building_id) REFERENCES estate_buildings(id) ON DELETE RESTRICT,
 FOREIGN KEY(supersedes_id) REFERENCES estate_settlements(id) ON DELETE RESTRICT
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
CREATE TABLE IF NOT EXISTS estate_settlement_documents (
 id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,settlement_id INT UNSIGNED NOT NULL,lease_id INT UNSIGNED NOT NULL,
 pdf_sha256 CHAR(64) NOT NULL,pdf_size INT UNSIGNED NOT NULL,
 UNIQUE KEY uq_estate_statement(settlement_id,lease_id),
 FOREIGN KEY(settlement_id) REFERENCES estate_settlements(id) ON DELETE RESTRICT,
 FOREIGN KEY(lease_id) REFERENCES estate_leases(id) ON DELETE RESTRICT
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
INSERT IGNORE INTO app_migrations(version) VALUES('320_estate_settlements');
