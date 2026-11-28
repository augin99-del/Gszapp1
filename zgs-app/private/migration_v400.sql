-- GSZ 400: Ferienwohnung (Airbnb/Booking-Kalender → Reinigungen). Rein additiv, wiederholbar.
-- Nur über gszMigrate400() ausführen. Gespeichert werden nur Daten (An-/Abreise), keine Gastnamen oder Buchungscodes.
CREATE TABLE IF NOT EXISTS stay_units (
 property_id INT UNSIGNED NOT NULL PRIMARY KEY,
 checkout_time TIME NOT NULL DEFAULT '10:00:00',
 checkin_time TIME NOT NULL DEFAULT '15:00:00',
 duration_minutes SMALLINT UNSIGNED NOT NULL DEFAULT 60,
 enabled TINYINT(1) NOT NULL DEFAULT 1,
 last_sync_at DATETIME NULL,
 created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
 row_version INT UNSIGNED NOT NULL DEFAULT 1,
 CONSTRAINT fk_stay_units_property FOREIGN KEY(property_id) REFERENCES properties(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
CREATE TABLE IF NOT EXISTS stay_calendars (
 id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
 property_id INT UNSIGNED NOT NULL,
 source ENUM('airbnb','booking') NOT NULL,
 url_cipher TEXT NOT NULL,
 url_hint VARCHAR(80) NOT NULL DEFAULT '',
 last_fetch_at DATETIME NULL,
 last_status ENUM('new','ok','error') NOT NULL DEFAULT 'new',
 last_error VARCHAR(255) NOT NULL DEFAULT '',
 stays_found SMALLINT UNSIGNED NOT NULL DEFAULT 0,
 stays_json MEDIUMTEXT NULL,
 created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
 KEY idx_stay_calendars_property(property_id),
 CONSTRAINT fk_stay_calendars_unit FOREIGN KEY(property_id) REFERENCES stay_units(property_id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
CREATE TABLE IF NOT EXISTS stay_cleanings (
 id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
 property_id INT UNSIGNED NOT NULL,
 clean_date DATE NOT NULL,
 turnover TINYINT(1) NOT NULL DEFAULT 0,
 task_id INT UNSIGNED NULL,
 status ENUM('planned','cancelled') NOT NULL DEFAULT 'planned',
 updated_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
 UNIQUE KEY uq_stay_cleanings_day(property_id,clean_date),
 UNIQUE KEY uq_stay_cleanings_task(task_id),
 CONSTRAINT fk_stay_cleanings_unit FOREIGN KEY(property_id) REFERENCES stay_units(property_id) ON DELETE CASCADE,
 CONSTRAINT fk_stay_cleanings_task FOREIGN KEY(task_id) REFERENCES tasks(id) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
INSERT IGNORE INTO app_migrations(version) VALUES('400_ferienwohnung');
