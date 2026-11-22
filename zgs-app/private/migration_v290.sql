-- Explicit recurrence proposals. Invoices are drafts; expenses become review tasks, never bookings.
CREATE TABLE IF NOT EXISTS finance_recurrences (
 id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
 kind ENUM('invoice','expense_review') NOT NULL, title VARCHAR(190) NOT NULL,
 source_invoice_id INT UNSIGNED NULL, property_id INT UNSIGNED NULL,
 frequency ENUM('weekly','biweekly','monthly','quarterly','yearly') NOT NULL,
 next_due_date DATE NOT NULL, anchor_day TINYINT UNSIGNED NOT NULL,
 due_days SMALLINT UNSIGNED NOT NULL DEFAULT 14, expected_cents BIGINT NULL,
 notes TEXT NULL, active TINYINT NOT NULL DEFAULT 1,
 row_version INT UNSIGNED NOT NULL DEFAULT 0,
 created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
 INDEX idx_finance_recurrence_due(active,next_due_date)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
CREATE TABLE IF NOT EXISTS finance_recurrence_proposals (
 token CHAR(64) PRIMARY KEY, admin_id INT UNSIGNED NOT NULL, recurrence_id INT UNSIGNED NOT NULL,
 fingerprint CHAR(64) NOT NULL, payload LONGTEXT NOT NULL,
 created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP, applied_at DATETIME NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
CREATE TABLE IF NOT EXISTS finance_recurrence_occurrences (
 id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY, recurrence_id INT UNSIGNED NOT NULL,
 scheduled_for DATE NOT NULL, invoice_id INT UNSIGNED NULL, task_id INT UNSIGNED NULL,
 created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
 UNIQUE KEY uq_finance_recurrence_occurrence(recurrence_id,scheduled_for),
 CHECK((invoice_id IS NOT NULL)+(task_id IS NOT NULL)=1)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
CREATE TABLE IF NOT EXISTS finance_recurrence_revisions (
 id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY, recurrence_id INT UNSIGNED NOT NULL,
 admin_id INT UNSIGNED NOT NULL, old_json TEXT NOT NULL, new_json TEXT NOT NULL,
 created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
 INDEX idx_finance_recurrence_revision(recurrence_id,id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
INSERT IGNORE INTO app_migrations(version) VALUES('290_finance_recurrences');
