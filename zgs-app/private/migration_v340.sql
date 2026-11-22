-- v1 work-week and completion identities. Additive; no historical task/lease/invoice rewrite.
ALTER TABLE recurring_jobs ADD COLUMN IF NOT EXISTS service_kind VARCHAR(20) NOT NULL DEFAULT 'auto',
 ADD COLUMN IF NOT EXISTS recurrence_mode VARCHAR(20) NOT NULL DEFAULT 'auto',
 ADD COLUMN IF NOT EXISTS allowed_weekdays_mask TINYINT UNSIGNED NULL,
 ADD COLUMN IF NOT EXISTS last_completed_on DATE NULL,
 ADD COLUMN IF NOT EXISTS completion_task_id INT UNSIGNED NULL;
ALTER TABLE tasks ADD COLUMN IF NOT EXISTS completed_on DATE NULL;
ALTER TABLE planning_settings ADD COLUMN IF NOT EXISTS workdays_mask TINYINT UNSIGNED NOT NULL DEFAULT 31,
 ADD COLUMN IF NOT EXISTS capacity_end_time TIME NOT NULL DEFAULT '17:00:00';
CREATE INDEX IF NOT EXISTS idx_planning_dedup ON planning_proposals(admin_id,fingerprint,applied_at);
CREATE TABLE IF NOT EXISTS recurring_completions (
 id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,task_id INT UNSIGNED NOT NULL,job_id INT UNSIGNED NOT NULL,
 task_version INT UNSIGNED NOT NULL,completed_on DATE NOT NULL,next_due_date DATE NOT NULL,
 recorded_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
 UNIQUE KEY uq_completion_cycle(task_id,task_version),INDEX idx_completion_job(job_id,completed_on)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
CREATE TABLE IF NOT EXISTS automation_state (
 job_key VARCHAR(40) PRIMARY KEY,last_started DATETIME NULL,last_finished DATETIME NULL,last_success DATETIME NULL,
 status VARCHAR(20) NOT NULL DEFAULT 'never',duration_ms INT UNSIGNED NULL,result_json TEXT NULL,last_error_code VARCHAR(80) NOT NULL DEFAULT ''
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
ALTER TABLE mail_drafts ADD COLUMN IF NOT EXISTS bcc_address TEXT NULL;
ALTER TABLE properties ADD COLUMN IF NOT EXISTS waste_out_offset SMALLINT NOT NULL DEFAULT -1,
 ADD COLUMN IF NOT EXISTS waste_in_offset SMALLINT NOT NULL DEFAULT 0;
INSERT IGNORE INTO app_migrations(version) VALUES('340_v1');
