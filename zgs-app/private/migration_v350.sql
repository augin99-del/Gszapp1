-- Additive FINAL integration. Existing operational/financial rows remain unchanged.
-- Preserve existing ENUM positions and append a daily interval.
ALTER TABLE recurring_jobs MODIFY COLUMN frequency ENUM('weekly','biweekly','monthly','quarterly','seasonal','daily') NOT NULL DEFAULT 'weekly';
ALTER TABLE bank_accounts ADD COLUMN IF NOT EXISTS import_iban_hash CHAR(64) NULL;
ALTER TABLE estate_bank_accounts ADD COLUMN IF NOT EXISTS import_iban_hash CHAR(64) NULL;
CREATE UNIQUE INDEX IF NOT EXISTS uq_bank_iban_hash ON bank_accounts(import_iban_hash);
CREATE UNIQUE INDEX IF NOT EXISTS uq_estate_iban_hash ON estate_bank_accounts(import_iban_hash);
CREATE TABLE IF NOT EXISTS seo_keywords (
 id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
 keyword VARCHAR(190) NOT NULL, location VARCHAR(190) NOT NULL,
 active TINYINT NOT NULL DEFAULT 1,created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
 UNIQUE KEY uq_seo_keyword(keyword,location)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
CREATE TABLE IF NOT EXISTS seo_runs (
 id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,keyword_id INT UNSIGNED NOT NULL,
 measured_at DATETIME NOT NULL,source VARCHAR(190) NOT NULL,search_depth SMALLINT UNSIGNED NOT NULL,
 run_hash CHAR(64) NOT NULL,file_hash CHAR(64) NOT NULL,created_by INT UNSIGNED NOT NULL,
 created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
 UNIQUE KEY uq_seo_measurement(keyword_id,measured_at,source),
 INDEX idx_seo_history(keyword_id,measured_at),
 CONSTRAINT fk_seo_keyword FOREIGN KEY(keyword_id) REFERENCES seo_keywords(id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
CREATE TABLE IF NOT EXISTS seo_points (
 run_id BIGINT UNSIGNED NOT NULL,point_key VARCHAR(64) NOT NULL,
 latitude_e6 INT NOT NULL,longitude_e6 INT NOT NULL,rank_position SMALLINT UNSIGNED NOT NULL,
 competitor VARCHAR(190) NOT NULL DEFAULT '',competitor_rank SMALLINT UNSIGNED NULL,
 PRIMARY KEY(run_id,point_key),UNIQUE KEY uq_seo_coordinate(run_id,latitude_e6,longitude_e6),
 CONSTRAINT fk_seo_run FOREIGN KEY(run_id) REFERENCES seo_runs(id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
CREATE TABLE IF NOT EXISTS seo_imports (
 token CHAR(64) PRIMARY KEY,admin_id INT UNSIGNED NOT NULL,keyword_id INT UNSIGNED NOT NULL,
 payload MEDIUMTEXT NOT NULL,created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,applied_at DATETIME NULL,
 INDEX idx_seo_pending(admin_id,applied_at)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
INSERT IGNORE INTO app_migrations(version) VALUES('350_final');
