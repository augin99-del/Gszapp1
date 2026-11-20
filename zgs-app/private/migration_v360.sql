-- Waste connection configuration only. Existing calendar/task/history tables remain authoritative.
ALTER TABLE waste_sources ADD COLUMN IF NOT EXISTS auto_managed TINYINT NOT NULL DEFAULT 0;
CREATE TABLE IF NOT EXISTS waste_connections (
 property_id INT UNSIGNED PRIMARY KEY,
 enabled TINYINT NOT NULL DEFAULT 0,
 address_json TEXT NOT NULL, address_hash CHAR(64) NOT NULL,
 provider_key VARCHAR(40) NOT NULL DEFAULT '',
 selection_json MEDIUMTEXT NOT NULL, offered_json MEDIUMTEXT NOT NULL, bins_json TEXT NOT NULL,
 source_id INT UNSIGNED NULL,
 status VARCHAR(24) NOT NULL DEFAULT 'confirmation',
 last_attempt_at DATETIME NULL,last_success_at DATETIME NULL,next_sync_at DATETIME NULL,
 error_code VARCHAR(80) NOT NULL DEFAULT '',pending_token CHAR(64) NULL,
 out_time TIME NULL,in_time TIME NULL,
 INDEX idx_waste_sync(enabled,next_sync_at),
 FOREIGN KEY(property_id) REFERENCES properties(id) ON DELETE RESTRICT,
 FOREIGN KEY(source_id) REFERENCES waste_sources(id) ON DELETE RESTRICT
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
CREATE TABLE IF NOT EXISTS waste_provider_cache (
 cache_key CHAR(64) PRIMARY KEY,payload MEDIUMTEXT NOT NULL,expires_at DATETIME NOT NULL,INDEX idx_waste_cache_expiry(expires_at)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
INSERT IGNORE INTO app_migrations(version) VALUES('360_waste_connections');
