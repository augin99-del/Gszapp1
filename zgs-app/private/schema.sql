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

-- v0.5: Bei Neuinstallation in tasks direkt mit anlegen; fuer bestehende Tabellen migration_v05.sql ausfuehren.
-- Hinweis: schema.sql oben definiert tasks bereits. Folgende ALTER-Befehle nur fuer die Neuinstallation nach dem Schema ausfuehren.
ALTER TABLE recurring_jobs ADD COLUMN next_due_date DATE NULL;
ALTER TABLE tasks ADD COLUMN recurring_job_id INT UNSIGNED NULL, ADD COLUMN scheduled_for DATE NULL;
ALTER TABLE tasks ADD CONSTRAINT fk_tasks_recurring FOREIGN KEY (recurring_job_id) REFERENCES recurring_jobs(id) ON DELETE SET NULL;
ALTER TABLE tasks ADD UNIQUE KEY uq_recurring_occurrence (recurring_job_id, scheduled_for);
