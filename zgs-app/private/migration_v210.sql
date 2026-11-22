-- Phase 2: MariaDB 10.6+, rerunnable after interrupted DDL. Requires v200.
-- Run preflight private/migrate_phase2.php (CLI) rather than importing blindly.
ALTER TABLE tasks ADD COLUMN IF NOT EXISTS plan_locked TINYINT(1) NOT NULL DEFAULT 0,
 ADD COLUMN IF NOT EXISTS notes TEXT NULL,
 ADD COLUMN IF NOT EXISTS planned_order SMALLINT UNSIGNED NOT NULL DEFAULT 0,
 ADD COLUMN IF NOT EXISTS window_start DATE NULL,
 ADD COLUMN IF NOT EXISTS window_end DATE NULL;
CREATE INDEX IF NOT EXISTS idx_tasks_plan_window ON tasks(done,due_date,plan_locked);
CREATE TABLE IF NOT EXISTS planning_settings (
 id TINYINT UNSIGNED PRIMARY KEY,
 start_time TIME NOT NULL DEFAULT '10:30:00',
 target_time TIME NOT NULL DEFAULT '16:00:00',
 warning_time TIME NOT NULL DEFAULT '17:00:00',
 buffer_minutes SMALLINT UNSIGNED NOT NULL DEFAULT 0,
 evening_destination VARCHAR(190) NOT NULL DEFAULT ''
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
INSERT IGNORE INTO planning_settings(id) VALUES(1);
CREATE TABLE IF NOT EXISTS planning_days (
 day DATE PRIMARY KEY, start_time TIME NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
CREATE TABLE IF NOT EXISTS planning_appointments (
 id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY, day DATE NOT NULL,
 start_time TIME NOT NULL, end_time TIME NOT NULL,
 title VARCHAR(190) NOT NULL, address VARCHAR(255) NOT NULL DEFAULT '',
 INDEX idx_appointment_day(day,start_time)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
CREATE TABLE IF NOT EXISTS planning_proposals (
 token CHAR(64) PRIMARY KEY, admin_id INT UNSIGNED NOT NULL,
 fingerprint CHAR(64) NOT NULL, payload LONGTEXT NOT NULL,
 created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
 applied_at DATETIME NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
INSERT IGNORE INTO app_migrations(version) VALUES('210_phase2');
