-- GSZ fresh install ONLY. Never execute on an existing installation.
-- Take backup before running. Do not import migration_v03/v04/v05 separately.

-- SOURCE: schema.sql
CREATE TABLE IF NOT EXISTS admins (id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY, email VARCHAR(190) NOT NULL UNIQUE, password_hash VARCHAR(255) NOT NULL, created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
CREATE TABLE IF NOT EXISTS customers (id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY, name VARCHAR(190) NOT NULL, email VARCHAR(190) NOT NULL DEFAULT '', phone VARCHAR(80) NOT NULL DEFAULT '', notes TEXT NULL, created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
CREATE TABLE IF NOT EXISTS properties (id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY, customer_id INT UNSIGNED NULL, name VARCHAR(190) NOT NULL, address VARCHAR(255) NOT NULL DEFAULT '', contact_name VARCHAR(190) NOT NULL DEFAULT '', contact_phone VARCHAR(80) NOT NULL DEFAULT '', contact_email VARCHAR(190) NOT NULL DEFAULT '', notes TEXT NULL, created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP, CONSTRAINT fk_properties_customer FOREIGN KEY (customer_id) REFERENCES customers(id) ON DELETE SET NULL) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
CREATE TABLE IF NOT EXISTS tasks (id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY, property_id INT UNSIGNED NULL, title VARCHAR(190) NOT NULL, due_date DATE NULL, done TINYINT(1) NOT NULL DEFAULT 0, created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP, CONSTRAINT fk_tasks_property FOREIGN KEY (property_id) REFERENCES properties(id) ON DELETE SET NULL) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
CREATE TABLE IF NOT EXISTS recurring_jobs (
 id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
 property_id INT UNSIGNED NULL,
 title VARCHAR(190) NOT NULL,
 frequency ENUM('weekly','biweekly','monthly','quarterly','seasonal') NOT NULL DEFAULT 'weekly',
 notes TEXT NULL,
 active TINYINT(1) NOT NULL DEFAULT 1,
 last_generated DATE NULL,
 created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
 CONSTRAINT fk_recurring_property FOREIGN KEY (property_id) REFERENCES properties(id) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
ALTER TABLE recurring_jobs ADD COLUMN next_due_date DATE NULL;
ALTER TABLE tasks ADD COLUMN recurring_job_id INT UNSIGNED NULL, ADD COLUMN scheduled_for DATE NULL;
ALTER TABLE tasks ADD CONSTRAINT fk_tasks_recurring FOREIGN KEY (recurring_job_id) REFERENCES recurring_jobs(id) ON DELETE SET NULL;
ALTER TABLE tasks ADD UNIQUE KEY uq_recurring_occurrence (recurring_job_id, scheduled_for);

-- SOURCE: migration_v07.sql
ALTER TABLE tasks
 ADD COLUMN priority ENUM('low','normal','high','urgent') NOT NULL DEFAULT 'normal',
 ADD COLUMN start_time TIME NULL,
 ADD COLUMN duration_minutes SMALLINT UNSIGNED NULL,
 ADD COLUMN sort_order SMALLINT UNSIGNED NOT NULL DEFAULT 0;
CREATE INDEX idx_tasks_daily_plan ON tasks (due_date,done,sort_order,start_time);

-- SOURCE: migration_v11_v15.sql
CREATE TABLE invoice_sequences (year SMALLINT UNSIGNED PRIMARY KEY, last_number INT UNSIGNED NOT NULL DEFAULT 0) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
CREATE TABLE invoices (
 id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
 customer_id INT UNSIGNED NOT NULL,
 property_id INT UNSIGNED NULL,
 number VARCHAR(32) NULL UNIQUE,
 status ENUM('draft','issued','paid','cancelled') NOT NULL DEFAULT 'draft',
 invoice_date DATE NULL, service_date DATE NULL, due_date DATE NULL,
 recipient_name VARCHAR(190) NOT NULL, recipient_address TEXT NOT NULL,
 recipient_email VARCHAR(190) NOT NULL DEFAULT '',
 issuer_name VARCHAR(190) NOT NULL, issuer_address TEXT NOT NULL,
 issuer_tax_id VARCHAR(80) NOT NULL DEFAULT '', issuer_vat_id VARCHAR(80) NOT NULL DEFAULT '',
 issuer_iban VARCHAR(60) NOT NULL DEFAULT '', issuer_bic VARCHAR(20) NOT NULL DEFAULT '',
 issuer_bank VARCHAR(190) NOT NULL DEFAULT '',
 currency CHAR(3) NOT NULL DEFAULT 'EUR', notes TEXT NULL,
 net_total DECIMAL(13,2) NOT NULL DEFAULT 0, tax_total DECIMAL(13,2) NOT NULL DEFAULT 0,
 gross_total DECIMAL(13,2) NOT NULL DEFAULT 0,
 issued_at DATETIME NULL, paid_at DATETIME NULL, cancelled_at DATETIME NULL,
 created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP, updated_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
 CONSTRAINT fk_inv_customer FOREIGN KEY (customer_id) REFERENCES customers(id) ON DELETE RESTRICT,
 CONSTRAINT fk_inv_property FOREIGN KEY (property_id) REFERENCES properties(id) ON DELETE SET NULL,
 INDEX idx_inv_status_due(status,due_date)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
CREATE TABLE invoice_items (
 id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
 invoice_id BIGINT UNSIGNED NOT NULL,
 position SMALLINT UNSIGNED NOT NULL,
 description VARCHAR(500) NOT NULL,
 quantity DECIMAL(12,3) NOT NULL,
 unit VARCHAR(20) NOT NULL DEFAULT 'Stk',
 unit_price DECIMAL(13,4) NOT NULL,
 tax_rate DECIMAL(5,2) NOT NULL,
 net_amount DECIMAL(13,2) NOT NULL,
 tax_amount DECIMAL(13,2) NOT NULL,
 gross_amount DECIMAL(13,2) NOT NULL,
 CONSTRAINT fk_inv_item FOREIGN KEY (invoice_id) REFERENCES invoices(id) ON DELETE RESTRICT,
 UNIQUE KEY uq_inv_position(invoice_id,position)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
CREATE TABLE invoice_events (
 id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
 invoice_id BIGINT UNSIGNED NOT NULL,
 admin_id INT UNSIGNED NULL,
 event_type VARCHAR(40) NOT NULL,
 detail TEXT NULL,
 created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
 CONSTRAINT fk_inv_event FOREIGN KEY(invoice_id) REFERENCES invoices(id) ON DELETE RESTRICT,
 CONSTRAINT fk_inv_event_admin FOREIGN KEY(admin_id) REFERENCES admins(id) ON DELETE SET NULL,
 INDEX idx_inv_event(invoice_id,created_at)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- SOURCE: migration_v16.sql
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

-- SOURCE: migration_v17.sql
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

-- SOURCE: migration_v19.sql
CREATE TABLE documents (
 id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
 customer_id INT UNSIGNED NULL,
 property_id INT UNSIGNED NULL,
 category ENUM('invoice','worklog','contract','correspondence','other') NOT NULL DEFAULT 'other',
 title VARCHAR(190) NOT NULL,
 original_name VARCHAR(255) NOT NULL,
 stored_name CHAR(32) NOT NULL UNIQUE,
 mime_type VARCHAR(80) NOT NULL,
 size_bytes INT UNSIGNED NOT NULL,
 sha256 CHAR(64) NOT NULL,
 notes TEXT NULL,
 created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
 CONSTRAINT fk_docs_customer FOREIGN KEY(customer_id) REFERENCES customers(id) ON DELETE RESTRICT,
 CONSTRAINT fk_docs_property FOREIGN KEY(property_id) REFERENCES properties(id) ON DELETE RESTRICT,
 INDEX idx_docs_created(created_at),
 INDEX idx_docs_customer(customer_id),
 INDEX idx_docs_property(property_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
