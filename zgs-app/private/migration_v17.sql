-- Run once after migration_v16.sql. Back up database before applying.
CREATE TABLE work_log_photos (
 id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
 work_log_id BIGINT UNSIGNED NOT NULL,
 stored_name CHAR(36) NOT NULL UNIQUE,
 original_name VARCHAR(255) NOT NULL,
 mime_type VARCHAR(32) NOT NULL,
 size_bytes INT UNSIGNED NOT NULL,
 label ENUM('before','after','other') NOT NULL DEFAULT 'other',
 created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
 CONSTRAINT fk_photo_worklog FOREIGN KEY(work_log_id) REFERENCES work_logs(id) ON DELETE RESTRICT,
 INDEX idx_photo_worklog (work_log_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
