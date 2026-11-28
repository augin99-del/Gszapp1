-- GSZ 430: Mieter-Meldungen per QR-Code, Hausverwaltungs-Portal, Helfer-Zugang, Schlüsseldienst-Einsätze.
-- Rein additiv, wiederholbar. Nur über gszMigrate430() ausführen. Keine Standortdaten, kein Tracking.
CREATE TABLE IF NOT EXISTS property_links (
 property_id INT UNSIGNED NOT NULL PRIMARY KEY,
 report_token CHAR(32) NULL,
 portal_token CHAR(32) NULL,
 report_since DATETIME NULL,
 portal_since DATETIME NULL,
 UNIQUE KEY uq_property_links_report(report_token),
 UNIQUE KEY uq_property_links_portal(portal_token),
 CONSTRAINT fk_property_links_property FOREIGN KEY(property_id) REFERENCES properties(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
CREATE TABLE IF NOT EXISTS defect_reports (
 id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
 property_id INT UNSIGNED NOT NULL,
 location VARCHAR(120) NOT NULL DEFAULT '',
 message VARCHAR(2000) NOT NULL,
 contact VARCHAR(190) NOT NULL DEFAULT '',
 photo_name VARCHAR(40) NULL,
 photo_mime VARCHAR(32) NULL,
 status ENUM('neu','uebernommen','erledigt','verworfen') NOT NULL DEFAULT 'neu',
 note_id INT UNSIGNED NULL,
 handled_at DATETIME NULL,
 created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
 KEY idx_defect_reports_property(property_id,created_at),
 KEY idx_defect_reports_status(status,created_at),
 CONSTRAINT fk_defect_reports_property FOREIGN KEY(property_id) REFERENCES properties(id) ON DELETE CASCADE,
 CONSTRAINT fk_defect_reports_note FOREIGN KEY(note_id) REFERENCES quick_notes(id) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
CREATE TABLE IF NOT EXISTS worker_accounts (
 worker_id INT UNSIGNED NOT NULL PRIMARY KEY,
 login VARCHAR(60) NOT NULL,
 password_hash VARCHAR(255) NOT NULL,
 active TINYINT(1) NOT NULL DEFAULT 1,
 failed_logins SMALLINT UNSIGNED NOT NULL DEFAULT 0,
 locked_until DATETIME NULL,
 last_login_at DATETIME NULL,
 created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
 UNIQUE KEY uq_worker_accounts_login(login),
 CONSTRAINT fk_worker_accounts_worker FOREIGN KEY(worker_id) REFERENCES operations_workers(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
ALTER TABLE work_logs ADD COLUMN IF NOT EXISTS worker_id INT UNSIGNED NULL, ADD COLUMN IF NOT EXISTS started_at DATETIME NULL, ADD COLUMN IF NOT EXISTS ended_at DATETIME NULL;
ALTER TABLE work_logs ADD INDEX IF NOT EXISTS idx_work_logs_worker(worker_id,performed_on);
CREATE TABLE IF NOT EXISTS lock_jobs (
 id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
 job_date DATE NOT NULL,
 job_time TIME NULL,
 place VARCHAR(120) NOT NULL DEFAULT '',
 kind ENUM('zugefallen','abgeschlossen','abgebrochen','schloss','zylinder','sonstiges') NOT NULL DEFAULT 'zugefallen',
 amount_cents INT UNSIGNED NOT NULL DEFAULT 0,
 payment ENUM('bar','karte','ueberweisung','rechnung','offen') NOT NULL DEFAULT 'bar',
 channel ENUM('telefon','whatsapp','website','google','empfehlung','sonstiges') NOT NULL DEFAULT 'telefon',
 customer VARCHAR(190) NOT NULL DEFAULT '',
 phone VARCHAR(80) NOT NULL DEFAULT '',
 note VARCHAR(1000) NOT NULL DEFAULT '',
 lead_id INT UNSIGNED NULL,
 note_id INT UNSIGNED NULL,
 review_requested_at DATETIME NULL,
 created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
 row_version INT UNSIGNED NOT NULL DEFAULT 1,
 KEY idx_lock_jobs_date(job_date),
 UNIQUE KEY uq_lock_jobs_note(note_id),
 CONSTRAINT fk_lock_jobs_lead FOREIGN KEY(lead_id) REFERENCES leads(id) ON DELETE SET NULL,
 CONSTRAINT fk_lock_jobs_note FOREIGN KEY(note_id) REFERENCES quick_notes(id) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
INSERT IGNORE INTO app_migrations(version) VALUES('430_service');
