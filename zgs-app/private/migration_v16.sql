-- Run once after migration_v11_v15.sql. Back up the database first.
CREATE TABLE work_logs (
 id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
 property_id INT UNSIGNED NOT NULL,
 task_id INT UNSIGNED NULL,
 performed_on DATE NOT NULL,
 duration_minutes SMALLINT UNSIGNED NOT NULL,
 description VARCHAR(500) NOT NULL,
 notes TEXT NULL,
 created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
 CONSTRAINT fk_worklog_property FOREIGN KEY (property_id) REFERENCES properties(id) ON DELETE RESTRICT,
 CONSTRAINT fk_worklog_task FOREIGN KEY (task_id) REFERENCES tasks(id) ON DELETE SET NULL,
 INDEX idx_worklog_property_date (property_id,performed_on),
 INDEX idx_worklog_date (performed_on)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
