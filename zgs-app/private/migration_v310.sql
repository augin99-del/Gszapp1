-- Owner-only real estate, strictly separate from GSZ customers/properties/bank/finance tables.
CREATE TABLE IF NOT EXISTS estate_mutex (id TINYINT UNSIGNED PRIMARY KEY) ENGINE=InnoDB;
INSERT IGNORE INTO estate_mutex VALUES(1);
CREATE TABLE IF NOT EXISTS estate_buildings (
 id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,name VARCHAR(190) NOT NULL,address VARCHAR(255) NOT NULL,
 notes TEXT NULL,row_version INT UNSIGNED NOT NULL DEFAULT 0,created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
CREATE TABLE IF NOT EXISTS estate_units (
 id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,building_id INT UNSIGNED NOT NULL,label VARCHAR(190) NOT NULL,
 area_units INT UNSIGNED NOT NULL,notes TEXT NULL,row_version INT UNSIGNED NOT NULL DEFAULT 0,
 UNIQUE KEY uq_estate_unit_label(building_id,label),
 FOREIGN KEY(building_id) REFERENCES estate_buildings(id) ON DELETE RESTRICT,CHECK(area_units>0)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
CREATE TABLE IF NOT EXISTS estate_tenants (
 id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,name VARCHAR(190) NOT NULL,email VARCHAR(190) NOT NULL DEFAULT '',
 phone VARCHAR(80) NOT NULL DEFAULT '',address VARCHAR(2000) NOT NULL DEFAULT '',notes TEXT NULL,
 row_version INT UNSIGNED NOT NULL DEFAULT 0,created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
CREATE TABLE IF NOT EXISTS estate_leases (
 id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,unit_id INT UNSIGNED NOT NULL,tenant_id INT UNSIGNED NULL,
 occupancy_kind ENUM('tenant','owner') NOT NULL DEFAULT 'tenant',starts_on DATE NOT NULL,ends_on DATE NULL,
 due_day TINYINT UNSIGNED NOT NULL DEFAULT 3,proration ENUM('manual','calendar_days') NOT NULL DEFAULT 'manual',
 operating_mode ENUM('advance','flat','included','unconfirmed') NOT NULL DEFAULT 'unconfirmed',
 heating_mode ENUM('advance','flat','included','unconfirmed') NOT NULL DEFAULT 'unconfirmed',
 deposit_cents BIGINT NOT NULL DEFAULT 0,notes TEXT NULL,row_version INT UNSIGNED NOT NULL DEFAULT 0,
 FOREIGN KEY(unit_id) REFERENCES estate_units(id) ON DELETE RESTRICT,
 FOREIGN KEY(tenant_id) REFERENCES estate_tenants(id) ON DELETE RESTRICT,
 INDEX idx_estate_lease_unit(unit_id,starts_on,ends_on),
 CHECK(due_day BETWEEN 1 AND 28),CHECK(deposit_cents>=0),CHECK(ends_on IS NULL OR ends_on>=starts_on),
 CHECK((occupancy_kind='tenant' AND tenant_id IS NOT NULL) OR (occupancy_kind='owner' AND tenant_id IS NULL))
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
CREATE TABLE IF NOT EXISTS estate_terms (
 id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,lease_id INT UNSIGNED NOT NULL,valid_from DATE NOT NULL,
 rent_cents BIGINT NOT NULL,operating_cents BIGINT NOT NULL,heating_cents BIGINT NOT NULL,
 created_by INT UNSIGNED NOT NULL,created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
 UNIQUE KEY uq_estate_term(lease_id,valid_from),FOREIGN KEY(lease_id) REFERENCES estate_leases(id) ON DELETE RESTRICT,
 CHECK(rent_cents>=0 AND operating_cents>=0 AND heating_cents>=0)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
CREATE TABLE IF NOT EXISTS estate_rent_charges (
 id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,lease_id INT UNSIGNED NOT NULL,month DATE NOT NULL,due_on DATE NOT NULL,
 rent_cents BIGINT NOT NULL,operating_cents BIGINT NOT NULL,heating_cents BIGINT NOT NULL,
 terms_hash CHAR(64) NOT NULL,created_by INT UNSIGNED NOT NULL,created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
 UNIQUE KEY uq_estate_charge(lease_id,month),FOREIGN KEY(lease_id) REFERENCES estate_leases(id) ON DELETE RESTRICT,
 CHECK(rent_cents>=0 AND operating_cents>=0 AND heating_cents>=0)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
CREATE TABLE IF NOT EXISTS estate_charge_adjustments (
 id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,charge_id INT UNSIGNED NOT NULL,
 component ENUM('rent','operating','heating') NOT NULL,amount_cents BIGINT NOT NULL,reason VARCHAR(1000) NOT NULL,
 request_key CHAR(64) NOT NULL UNIQUE,created_by INT UNSIGNED NOT NULL,created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
 FOREIGN KEY(charge_id) REFERENCES estate_rent_charges(id) ON DELETE RESTRICT,CHECK(amount_cents<>0)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
CREATE TABLE IF NOT EXISTS estate_bank_accounts (
 id TINYINT UNSIGNED PRIMARY KEY,alias VARCHAR(190) NOT NULL,reference VARCHAR(190) NOT NULL DEFAULT '',
 row_version INT UNSIGNED NOT NULL DEFAULT 0,CHECK(id IN(1,2))
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
INSERT IGNORE INTO estate_bank_accounts(id,alias) VALUES(1,'Volksbank-Konto 1'),(2,'Volksbank-Konto 2');
CREATE TABLE IF NOT EXISTS estate_bank_transactions (
 id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,account_id TINYINT UNSIGNED NOT NULL,
 source_key CHAR(64) NOT NULL,record_hash CHAR(64) NOT NULL,external_id VARCHAR(190) NOT NULL,
 booked_on DATE NOT NULL,value_on DATE NOT NULL,amount_cents BIGINT NOT NULL,currency CHAR(3) NOT NULL,
 purpose VARCHAR(2000) NOT NULL,counterparty VARCHAR(500) NOT NULL,
 UNIQUE KEY uq_estate_bank_source(account_id,source_key),INDEX idx_estate_bank_day(account_id,booked_on),
 FOREIGN KEY(account_id) REFERENCES estate_bank_accounts(id) ON DELETE RESTRICT,CHECK(currency='EUR')
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
CREATE TABLE IF NOT EXISTS estate_proposals (
 token CHAR(64) PRIMARY KEY,kind ENUM('rent','bank') NOT NULL,admin_id INT UNSIGNED NOT NULL,
 fingerprint CHAR(64) NOT NULL,payload LONGTEXT NOT NULL,created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,applied_at DATETIME NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
CREATE TABLE IF NOT EXISTS estate_payments (
 id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,bank_transaction_id BIGINT UNSIGNED NULL UNIQUE,
 paid_on DATE NOT NULL,amount_cents BIGINT NOT NULL,description VARCHAR(1000) NOT NULL,
 request_key CHAR(64) NOT NULL UNIQUE,created_by INT UNSIGNED NOT NULL,created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
 FOREIGN KEY(bank_transaction_id) REFERENCES estate_bank_transactions(id) ON DELETE RESTRICT,CHECK(amount_cents<>0)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
CREATE TABLE IF NOT EXISTS estate_allocations (
 id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,payment_id BIGINT UNSIGNED NOT NULL,lease_id INT UNSIGNED NOT NULL,
 charge_id INT UNSIGNED NULL,component ENUM('rent','operating','heating','deposit') NOT NULL,
 amount_cents BIGINT NOT NULL,reversal_of BIGINT UNSIGNED NULL UNIQUE,reason VARCHAR(1000) NOT NULL DEFAULT '',
 request_key CHAR(64) NOT NULL UNIQUE,created_by INT UNSIGNED NOT NULL,created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
 FOREIGN KEY(payment_id) REFERENCES estate_payments(id) ON DELETE RESTRICT,
 FOREIGN KEY(lease_id) REFERENCES estate_leases(id) ON DELETE RESTRICT,
 FOREIGN KEY(charge_id) REFERENCES estate_rent_charges(id) ON DELETE RESTRICT,
 FOREIGN KEY(reversal_of) REFERENCES estate_allocations(id) ON DELETE RESTRICT,
 CHECK(amount_cents<>0),CHECK((component='deposit' AND charge_id IS NULL) OR(component<>'deposit' AND charge_id IS NOT NULL))
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
CREATE TABLE IF NOT EXISTS estate_transfers (
 id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,outgoing_id BIGINT UNSIGNED NOT NULL UNIQUE,incoming_id BIGINT UNSIGNED NOT NULL UNIQUE,
 reason VARCHAR(1000) NOT NULL,created_by INT UNSIGNED NOT NULL,created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
 FOREIGN KEY(outgoing_id) REFERENCES estate_bank_transactions(id) ON DELETE RESTRICT,
 FOREIGN KEY(incoming_id) REFERENCES estate_bank_transactions(id) ON DELETE RESTRICT
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
CREATE TABLE IF NOT EXISTS estate_documents (
 id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,building_id INT UNSIGNED NULL,lease_id INT UNSIGNED NULL,
 original_name VARCHAR(200) NOT NULL,mime_type VARCHAR(100) NOT NULL,sha256 CHAR(64) NOT NULL,
 size_bytes INT UNSIGNED NOT NULL,created_by INT UNSIGNED NOT NULL,created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
 FOREIGN KEY(building_id) REFERENCES estate_buildings(id) ON DELETE RESTRICT,
 FOREIGN KEY(lease_id) REFERENCES estate_leases(id) ON DELETE RESTRICT
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
CREATE TABLE IF NOT EXISTS estate_expenses (
 id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,transaction_id BIGINT UNSIGNED NOT NULL UNIQUE,building_id INT UNSIGNED NOT NULL,
 unit_id INT UNSIGNED NULL,category VARCHAR(190) NOT NULL,notes TEXT NULL,document_id INT UNSIGNED NULL,
 created_by INT UNSIGNED NOT NULL,created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
 FOREIGN KEY(transaction_id) REFERENCES estate_bank_transactions(id) ON DELETE RESTRICT,
 FOREIGN KEY(building_id) REFERENCES estate_buildings(id) ON DELETE RESTRICT,
 FOREIGN KEY(unit_id) REFERENCES estate_units(id) ON DELETE RESTRICT,
 FOREIGN KEY(document_id) REFERENCES estate_documents(id) ON DELETE RESTRICT
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
CREATE TABLE IF NOT EXISTS estate_history (
 id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,entity VARCHAR(40) NOT NULL,entity_id BIGINT UNSIGNED NOT NULL,
 action VARCHAR(40) NOT NULL,changes_json MEDIUMTEXT NOT NULL,admin_id INT UNSIGNED NOT NULL,
 created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,INDEX idx_estate_history(entity,entity_id,id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
INSERT IGNORE INTO app_migrations(version) VALUES('310_estate_core');
