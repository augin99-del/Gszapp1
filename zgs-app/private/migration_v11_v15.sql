-- Run once after backing up database. MariaDB 10.6+ / InnoDB.
CREATE TABLE invoice_sequences (year SMALLINT UNSIGNED PRIMARY KEY, last_number INT UNSIGNED NOT NULL DEFAULT 0) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
CREATE TABLE invoices (
 id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
 customer_id INT UNSIGNED NOT NULL,
 property_id INT UNSIGNED NULL,
 number VARCHAR(32) NULL UNIQUE,
 status ENUM('draft','issued','paid','cancelled') NOT NULL DEFAULT 'draft',
 invoice_date DATE NULL, service_date DATE NULL, due_date DATE NULL,
 recipient_name VARCHAR(190) NOT NULL, recipient_address TEXT NOT NULL,
 recipient_email VARCHAR(190) NOT NULL DEFAULT '',
 issuer_name VARCHAR(190) NOT NULL, issuer_address TEXT NOT NULL,
 issuer_tax_id VARCHAR(80) NOT NULL DEFAULT '', issuer_vat_id VARCHAR(80) NOT NULL DEFAULT '',
 issuer_iban VARCHAR(60) NOT NULL DEFAULT '', issuer_bic VARCHAR(20) NOT NULL DEFAULT '',
 issuer_bank VARCHAR(190) NOT NULL DEFAULT '',
 currency CHAR(3) NOT NULL DEFAULT 'EUR', notes TEXT NULL,
 net_total DECIMAL(13,2) NOT NULL DEFAULT 0, tax_total DECIMAL(13,2) NOT NULL DEFAULT 0,
 gross_total DECIMAL(13,2) NOT NULL DEFAULT 0,
 issued_at DATETIME NULL, paid_at DATETIME NULL, cancelled_at DATETIME NULL,
 created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP, updated_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
 CONSTRAINT fk_inv_customer FOREIGN KEY (customer_id) REFERENCES customers(id) ON DELETE RESTRICT,
 CONSTRAINT fk_inv_property FOREIGN KEY (property_id) REFERENCES properties(id) ON DELETE SET NULL,
 INDEX idx_inv_status_due(status,due_date)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
CREATE TABLE invoice_items (
 id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
 invoice_id BIGINT UNSIGNED NOT NULL,
 position SMALLINT UNSIGNED NOT NULL,
 description VARCHAR(500) NOT NULL,
 quantity DECIMAL(12,3) NOT NULL,
 unit VARCHAR(20) NOT NULL DEFAULT 'Stk',
 unit_price DECIMAL(13,4) NOT NULL,
 tax_rate DECIMAL(5,2) NOT NULL,
 net_amount DECIMAL(13,2) NOT NULL,
 tax_amount DECIMAL(13,2) NOT NULL,
 gross_amount DECIMAL(13,2) NOT NULL,
 CONSTRAINT fk_inv_item FOREIGN KEY (invoice_id) REFERENCES invoices(id) ON DELETE RESTRICT,
 UNIQUE KEY uq_inv_position(invoice_id,position)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
CREATE TABLE invoice_events (
 id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
 invoice_id BIGINT UNSIGNED NOT NULL,
 admin_id INT UNSIGNED NULL,
 event_type VARCHAR(40) NOT NULL,
 detail TEXT NULL,
 created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
 CONSTRAINT fk_inv_event FOREIGN KEY(invoice_id) REFERENCES invoices(id) ON DELETE RESTRICT,
 CONSTRAINT fk_inv_event_admin FOREIGN KEY(admin_id) REFERENCES admins(id) ON DELETE SET NULL,
 INDEX idx_inv_event(invoice_id,created_at)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
