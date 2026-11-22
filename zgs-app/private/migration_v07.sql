-- EINMALIG nach v0.6 ausführen. Vorher Datenbank-Backup erstellen.
-- MariaDB 11.8
ALTER TABLE tasks
 ADD COLUMN priority ENUM('low','normal','high','urgent') NOT NULL DEFAULT 'normal',
 ADD COLUMN start_time TIME NULL,
 ADD COLUMN duration_minutes SMALLINT UNSIGNED NULL,
 ADD COLUMN sort_order SMALLINT UNSIGNED NOT NULL DEFAULT 0;
CREATE INDEX idx_tasks_daily_plan ON tasks (due_date,done,sort_order,start_time);
