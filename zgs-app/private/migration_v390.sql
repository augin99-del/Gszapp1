-- GSZ 390: Merkliste (schnelle To-dos und „Rechnungen offen“). Rein additiv, wiederholbar.
-- Nur über gszMigrate390() ausführen (Einrichtung, geschütztes Update oder migrate_gesamt.php).
-- Rechnungen selbst entstehen weiter in Lexware; hier steht nur die Erinnerung, dass eine Rechnung fehlt.
CREATE TABLE IF NOT EXISTS quick_notes (
 id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
 kind ENUM('todo','invoice') NOT NULL DEFAULT 'todo',
 title VARCHAR(190) NOT NULL,
 property_id INT UNSIGNED NULL,
 due_date DATE NULL,
 material VARCHAR(500) NOT NULL DEFAULT '',
 material_done TINYINT(1) NOT NULL DEFAULT 0,
 note VARCHAR(500) NOT NULL DEFAULT '',
 photo_name VARCHAR(40) NULL,
 photo_mime VARCHAR(32) NULL,
 task_id INT UNSIGNED NULL,
 source_note_id INT UNSIGNED NULL,
 done_at DATETIME NULL,
 created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
 row_version INT UNSIGNED NOT NULL DEFAULT 1,
 UNIQUE KEY uq_quick_notes_task(task_id),
 UNIQUE KEY uq_quick_notes_source(source_note_id),
 KEY idx_quick_notes_open(kind,done_at),
 KEY idx_quick_notes_property(property_id),
 CONSTRAINT fk_quick_notes_property FOREIGN KEY(property_id) REFERENCES properties(id) ON DELETE SET NULL,
 CONSTRAINT fk_quick_notes_task FOREIGN KEY(task_id) REFERENCES tasks(id) ON DELETE SET NULL,
 CONSTRAINT fk_quick_notes_source FOREIGN KEY(source_note_id) REFERENCES quick_notes(id) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
INSERT IGNORE INTO app_migrations(version) VALUES('390_notizen');
