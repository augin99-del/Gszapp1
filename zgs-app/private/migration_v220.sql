-- Phase 3; additive, repeatable MariaDB 10.6+. Run via migrate_phase3.php.
CREATE TABLE IF NOT EXISTS waste_sources (
 id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY, property_id INT UNSIGNED NOT NULL,
 name VARCHAR(190) NOT NULL, address VARCHAR(255) NOT NULL,
 source_date DATE NOT NULL, valid_until DATE NOT NULL,
 checked_at DATETIME NULL, last_import_at DATETIME NULL,
 last_error VARCHAR(255) NOT NULL DEFAULT '',
 UNIQUE KEY uq_waste_source(property_id,name),
 FOREIGN KEY(property_id) REFERENCES properties(id) ON DELETE RESTRICT
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
CREATE TABLE IF NOT EXISTS waste_events (
 id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY, property_id INT UNSIGNED NOT NULL,
 collection_date DATE NOT NULL, fraction VARCHAR(40) NOT NULL, address VARCHAR(255) NOT NULL,
 status ENUM('importiert','geprueft','unklar','veraltet') NOT NULL DEFAULT 'importiert',
 event_hash CHAR(64) NOT NULL, created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
 UNIQUE KEY uq_waste_event(property_id,collection_date,fraction),
 FOREIGN KEY(property_id) REFERENCES properties(id) ON DELETE RESTRICT
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
CREATE TABLE IF NOT EXISTS waste_origins (
 event_id INT UNSIGNED NOT NULL, source_id INT UNSIGNED NOT NULL,
 external_id VARCHAR(255) NOT NULL, original_title VARCHAR(500) NOT NULL,
 original_start VARCHAR(100) NOT NULL, record_hash CHAR(64) NOT NULL,
 imported_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
 PRIMARY KEY(event_id,source_id,external_id), INDEX idx_origin_uid(source_id,external_id),
 FOREIGN KEY(event_id) REFERENCES waste_events(id) ON DELETE RESTRICT,
 FOREIGN KEY(source_id) REFERENCES waste_sources(id) ON DELETE RESTRICT
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
CREATE TABLE IF NOT EXISTS waste_imports (
 token CHAR(64) PRIMARY KEY, admin_id INT UNSIGNED NOT NULL, source_id INT UNSIGNED NOT NULL,
 file_hash CHAR(64) NOT NULL, fingerprint CHAR(64) NOT NULL, payload LONGTEXT NOT NULL,
 created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP, applied_at DATETIME NULL,
 FOREIGN KEY(source_id) REFERENCES waste_sources(id) ON DELETE RESTRICT
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
CREATE TABLE IF NOT EXISTS waste_task_links (
 event_id INT UNSIGNED NOT NULL, direction ENUM('raus','rein') NOT NULL,
 task_id INT UNSIGNED NOT NULL, PRIMARY KEY(event_id,direction),
 FOREIGN KEY(event_id) REFERENCES waste_events(id) ON DELETE RESTRICT,
 FOREIGN KEY(task_id) REFERENCES tasks(id) ON DELETE RESTRICT
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
CREATE TABLE IF NOT EXISTS waste_history (
 id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY, admin_id INT UNSIGNED NOT NULL,
 event_type VARCHAR(40) NOT NULL, detail TEXT NOT NULL,
 created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
ALTER TABLE tasks ADD COLUMN IF NOT EXISTS waste_key VARCHAR(100) NULL;
CREATE UNIQUE INDEX IF NOT EXISTS uq_task_waste ON tasks(waste_key);
ALTER TABLE planning_settings
 ADD COLUMN IF NOT EXISTS route_start_address VARCHAR(255) NOT NULL DEFAULT '',
 ADD COLUMN IF NOT EXISTS route_end_address VARCHAR(255) NOT NULL DEFAULT '';
CREATE TABLE IF NOT EXISTS routing_locations (
 location_key VARCHAR(100) PRIMARY KEY, address VARCHAR(255) NOT NULL,
 latitude DECIMAL(10,7) NULL, longitude DECIMAL(10,7) NULL,
 source VARCHAR(190) NOT NULL, quality VARCHAR(40) NOT NULL,
 checked_at DATETIME NOT NULL, UNIQUE KEY uq_route_address(address)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
CREATE TABLE IF NOT EXISTS routing_cache (
 cache_key CHAR(64) PRIMARY KEY, payload LONGTEXT NOT NULL,
 expires_at DATETIME NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
INSERT IGNORE INTO app_migrations(version) VALUES('220_phase3');
