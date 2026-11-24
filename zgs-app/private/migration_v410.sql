-- GSZ 410: Bauprojekte (z. B. Sanierung eines eigenen Hauses). Rein additiv, wiederholbar.
-- Nur über gszMigrate410() ausführen. Kosten sind eine Übersicht – Buchhaltung bleibt in Lexware.
CREATE TABLE IF NOT EXISTS projects (
 id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
 title VARCHAR(190) NOT NULL,
 building_id INT UNSIGNED NULL,
 status ENUM('planung','laeuft','fertig') NOT NULL DEFAULT 'planung',
 start_date DATE NULL,
 target_date DATE NULL,
 budget_cents BIGINT NULL,
 notes TEXT NULL,
 created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
 row_version INT UNSIGNED NOT NULL DEFAULT 1,
 KEY idx_projects_building(building_id),
 CONSTRAINT fk_projects_building FOREIGN KEY(building_id) REFERENCES estate_buildings(id) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
CREATE TABLE IF NOT EXISTS project_contractors (
 id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
 name VARCHAR(190) NOT NULL,
 trade VARCHAR(120) NOT NULL DEFAULT '',
 phone VARCHAR(80) NOT NULL DEFAULT '',
 email VARCHAR(190) NOT NULL DEFAULT '',
 notes VARCHAR(500) NOT NULL DEFAULT '',
 created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
CREATE TABLE IF NOT EXISTS project_trades (
 id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
 project_id INT UNSIGNED NOT NULL,
 name VARCHAR(120) NOT NULL,
 contractor_id INT UNSIGNED NULL,
 status ENUM('offen','geplant','laeuft','fertig') NOT NULL DEFAULT 'offen',
 start_date DATE NULL,
 end_date DATE NULL,
 budget_cents BIGINT NULL,
 warranty_until DATE NULL,
 notes VARCHAR(1000) NOT NULL DEFAULT '',
 sort_order INT NOT NULL DEFAULT 0,
 row_version INT UNSIGNED NOT NULL DEFAULT 1,
 KEY idx_project_trades_project(project_id,sort_order),
 CONSTRAINT fk_project_trades_project FOREIGN KEY(project_id) REFERENCES projects(id) ON DELETE CASCADE,
 CONSTRAINT fk_project_trades_contractor FOREIGN KEY(contractor_id) REFERENCES project_contractors(id) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
CREATE TABLE IF NOT EXISTS project_files (
 id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
 project_id INT UNSIGNED NOT NULL,
 trade_id INT UNSIGNED NULL,
 kind ENUM('foto','dokument') NOT NULL,
 phase ENUM('','vorher','waehrend','nachher') NOT NULL DEFAULT '',
 unit_label VARCHAR(80) NOT NULL DEFAULT '',
 caption VARCHAR(190) NOT NULL DEFAULT '',
 stored_name VARCHAR(80) NOT NULL,
 original_name VARCHAR(200) NOT NULL DEFAULT '',
 mime_type VARCHAR(80) NOT NULL,
 size_bytes INT UNSIGNED NOT NULL,
 created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
 UNIQUE KEY uq_project_files_stored(stored_name),
 KEY idx_project_files_project(project_id,kind),
 CONSTRAINT fk_project_files_project FOREIGN KEY(project_id) REFERENCES projects(id) ON DELETE CASCADE,
 CONSTRAINT fk_project_files_trade FOREIGN KEY(trade_id) REFERENCES project_trades(id) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
CREATE TABLE IF NOT EXISTS project_events (
 id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
 project_id INT UNSIGNED NOT NULL,
 trade_id INT UNSIGNED NULL,
 contractor_id INT UNSIGNED NULL,
 appointment_id INT UNSIGNED NULL,
 day DATE NOT NULL,
 start_time TIME NOT NULL,
 end_time TIME NOT NULL,
 title VARCHAR(190) NOT NULL,
 created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
 UNIQUE KEY uq_project_events_appointment(appointment_id),
 KEY idx_project_events_project(project_id,day),
 CONSTRAINT fk_project_events_project FOREIGN KEY(project_id) REFERENCES projects(id) ON DELETE CASCADE,
 CONSTRAINT fk_project_events_trade FOREIGN KEY(trade_id) REFERENCES project_trades(id) ON DELETE SET NULL,
 CONSTRAINT fk_project_events_contractor FOREIGN KEY(contractor_id) REFERENCES project_contractors(id) ON DELETE SET NULL,
 CONSTRAINT fk_project_events_appointment FOREIGN KEY(appointment_id) REFERENCES planning_appointments(id) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
CREATE TABLE IF NOT EXISTS project_costs (
 id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
 project_id INT UNSIGNED NOT NULL,
 trade_id INT UNSIGNED NULL,
 contractor_id INT UNSIGNED NULL,
 kind ENUM('angebot','rechnung') NOT NULL,
 title VARCHAR(190) NOT NULL,
 amount_cents BIGINT NOT NULL,
 doc_date DATE NULL,
 due_date DATE NULL,
 accepted TINYINT(1) NOT NULL DEFAULT 0,
 paid_on DATE NULL,
 file_id INT UNSIGNED NULL,
 notes VARCHAR(500) NOT NULL DEFAULT '',
 created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
 row_version INT UNSIGNED NOT NULL DEFAULT 1,
 KEY idx_project_costs_project(project_id,kind),
 CONSTRAINT fk_project_costs_project FOREIGN KEY(project_id) REFERENCES projects(id) ON DELETE CASCADE,
 CONSTRAINT fk_project_costs_trade FOREIGN KEY(trade_id) REFERENCES project_trades(id) ON DELETE SET NULL,
 CONSTRAINT fk_project_costs_contractor FOREIGN KEY(contractor_id) REFERENCES project_contractors(id) ON DELETE SET NULL,
 CONSTRAINT fk_project_costs_file FOREIGN KEY(file_id) REFERENCES project_files(id) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
CREATE TABLE IF NOT EXISTS project_items (
 id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
 project_id INT UNSIGNED NOT NULL,
 trade_id INT UNSIGNED NULL,
 contractor_id INT UNSIGNED NULL,
 kind ENUM('todo','mangel') NOT NULL DEFAULT 'todo',
 title VARCHAR(190) NOT NULL,
 unit_label VARCHAR(80) NOT NULL DEFAULT '',
 due_date DATE NULL,
 photo_file_id INT UNSIGNED NULL,
 done_at DATETIME NULL,
 created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
 row_version INT UNSIGNED NOT NULL DEFAULT 1,
 KEY idx_project_items_project(project_id,done_at),
 CONSTRAINT fk_project_items_project FOREIGN KEY(project_id) REFERENCES projects(id) ON DELETE CASCADE,
 CONSTRAINT fk_project_items_trade FOREIGN KEY(trade_id) REFERENCES project_trades(id) ON DELETE SET NULL,
 CONSTRAINT fk_project_items_contractor FOREIGN KEY(contractor_id) REFERENCES project_contractors(id) ON DELETE SET NULL,
 CONSTRAINT fk_project_items_photo FOREIGN KEY(photo_file_id) REFERENCES project_files(id) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
INSERT IGNORE INTO app_migrations(version) VALUES('410_projekte');
