-- GSZ Phase 1, once AFTER the complete v1.9.2 structure. Back up first.
-- Additive only; MariaDB DDL auto-commits. Never rerun after partial failure.
ALTER TABLE properties ADD COLUMN seed_key VARCHAR(80) NULL,
 ADD UNIQUE KEY uq_property_seed (seed_key);
ALTER TABLE recurring_jobs
 ADD COLUMN seed_key VARCHAR(100) NULL,
 ADD COLUMN duration_minutes SMALLINT UNSIGNED NULL,
 ADD COLUMN anchor_day TINYINT UNSIGNED NULL,
 ADD COLUMN season_start_month TINYINT UNSIGNED NULL,
 ADD COLUMN season_end_month TINYINT UNSIGNED NULL,
 ADD COLUMN rule_source ENUM('manual','routine_unverified','special_weekly') NOT NULL DEFAULT 'manual',
 ADD UNIQUE KEY uq_job_seed (seed_key);
-- Retain the current date as the initial monthly anchor for pre-existing rules.
UPDATE recurring_jobs SET anchor_day=DAY(next_due_date)
 WHERE next_due_date IS NOT NULL AND frequency IN ('monthly','quarterly');
CREATE TABLE app_migrations (
 version VARCHAR(40) PRIMARY KEY,
 applied_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
INSERT INTO app_migrations(version) VALUES ('200_phase1');
