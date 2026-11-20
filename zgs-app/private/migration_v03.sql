-- Einmalig NACH der v0.2 Einrichtung in derselben MariaDB ausfuehren.
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
