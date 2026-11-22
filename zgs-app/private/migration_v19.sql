-- Einmalig nach migration_v17.sql ausfuehren. Datenbank vorher sichern.
CREATE TABLE documents (
 id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
 customer_id INT UNSIGNED NULL,
 property_id INT UNSIGNED NULL,
 category ENUM('invoice','worklog','contract','correspondence','other') NOT NULL DEFAULT 'other',
 title VARCHAR(190) NOT NULL,
 original_name VARCHAR(255) NOT NULL,
 stored_name CHAR(32) NOT NULL UNIQUE,
 mime_type VARCHAR(80) NOT NULL,
 size_bytes INT UNSIGNED NOT NULL,
 sha256 CHAR(64) NOT NULL,
 notes TEXT NULL,
 created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
 CONSTRAINT fk_docs_customer FOREIGN KEY(customer_id) REFERENCES customers(id) ON DELETE RESTRICT,
 CONSTRAINT fk_docs_property FOREIGN KEY(property_id) REFERENCES properties(id) ON DELETE RESTRICT,
 INDEX idx_docs_created(created_at),
 INDEX idx_docs_customer(customer_id),
 INDEX idx_docs_property(property_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
