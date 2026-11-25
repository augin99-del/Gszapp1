-- GSZ 420: Anfragen (Website/Telefon/WhatsApp), cookiefreie Website-Zahlen, Einstellungen (Notdienst-Status, Bewertungslink).
-- Rein additiv, wiederholbar. Nur über gszMigrate420() ausführen. Website-Zahlen sind reine Tageszähler ohne Personendaten.
CREATE TABLE IF NOT EXISTS leads (
 id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
 source ENUM('website','telefon','whatsapp','empfehlung','sonstiges') NOT NULL DEFAULT 'website',
 status ENUM('neu','kontaktiert','angebot','auftrag','abgesagt') NOT NULL DEFAULT 'neu',
 service VARCHAR(120) NOT NULL DEFAULT '',
 object_type VARCHAR(120) NOT NULL DEFAULT '',
 name VARCHAR(190) NOT NULL DEFAULT '',
 company VARCHAR(190) NOT NULL DEFAULT '',
 phone VARCHAR(80) NOT NULL DEFAULT '',
 email VARCHAR(190) NOT NULL DEFAULT '',
 place VARCHAR(190) NOT NULL DEFAULT '',
 message TEXT NULL,
 source_page VARCHAR(255) NOT NULL DEFAULT '',
 note VARCHAR(1000) NOT NULL DEFAULT '',
 mail_message_id BIGINT UNSIGNED NULL,
 property_id INT UNSIGNED NULL,
 task_id INT UNSIGNED NULL,
 review_requested_at DATETIME NULL,
 created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
 updated_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
 row_version INT UNSIGNED NOT NULL DEFAULT 1,
 UNIQUE KEY uq_leads_mail(mail_message_id),
 KEY idx_leads_status(status,created_at),
 CONSTRAINT fk_leads_mail FOREIGN KEY(mail_message_id) REFERENCES mail_messages(id) ON DELETE SET NULL,
 CONSTRAINT fk_leads_property FOREIGN KEY(property_id) REFERENCES properties(id) ON DELETE SET NULL,
 CONSTRAINT fk_leads_task FOREIGN KEY(task_id) REFERENCES tasks(id) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
CREATE TABLE IF NOT EXISTS web_events (
 day DATE NOT NULL,
 page VARCHAR(190) NOT NULL,
 event ENUM('view','call','whatsapp','form') NOT NULL,
 hits INT UNSIGNED NOT NULL DEFAULT 0,
 PRIMARY KEY(day,page,event)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
CREATE TABLE IF NOT EXISTS app_settings (
 setting_key VARCHAR(64) NOT NULL PRIMARY KEY,
 setting_value VARCHAR(2000) NOT NULL DEFAULT '',
 updated_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
INSERT IGNORE INTO app_migrations(version) VALUES('420_anfragen');
