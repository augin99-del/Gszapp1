-- GSZ 440: Push-Benachrichtigungen (Web Push, eigene VAPID-Schlüssel, kein Fremddienst) und DWD-Unwetterwarnungen für den Winterdienst.
-- Rein additiv, wiederholbar. Nur über gszMigrate440() ausführen.
CREATE TABLE IF NOT EXISTS push_subscriptions (
 id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
 admin_id INT UNSIGNED NULL,
 endpoint_hash CHAR(64) NOT NULL,
 endpoint VARCHAR(1000) NOT NULL,
 p256dh VARCHAR(200) NOT NULL,
 auth VARCHAR(64) NOT NULL,
 label VARCHAR(120) NOT NULL DEFAULT '',
 created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
 last_ok_at DATETIME NULL,
 fail_count SMALLINT UNSIGNED NOT NULL DEFAULT 0,
 last_error VARCHAR(190) NOT NULL DEFAULT '',
 UNIQUE KEY uq_push_subscriptions_endpoint(endpoint_hash),
 CONSTRAINT fk_push_subscriptions_admin FOREIGN KEY(admin_id) REFERENCES admins(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
CREATE TABLE IF NOT EXISTS push_events (
 event_key VARCHAR(120) NOT NULL PRIMARY KEY,
 kind VARCHAR(20) NOT NULL,
 title VARCHAR(190) NOT NULL,
 body VARCHAR(500) NOT NULL DEFAULT '',
 url VARCHAR(255) NOT NULL DEFAULT '',
 delivered SMALLINT UNSIGNED NOT NULL DEFAULT 0,
 created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
 KEY idx_push_events_created(created_at)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
CREATE TABLE IF NOT EXISTS weather_alerts (
 alert_id VARCHAR(190) NOT NULL PRIMARY KEY,
 event_code SMALLINT UNSIGNED NOT NULL DEFAULT 0,
 event VARCHAR(80) NOT NULL,
 headline VARCHAR(190) NOT NULL DEFAULT '',
 description VARCHAR(2000) NOT NULL DEFAULT '',
 severity VARCHAR(20) NOT NULL DEFAULT '',
 onset DATETIME NULL,
 expires DATETIME NULL,
 area VARCHAR(190) NOT NULL DEFAULT '',
 winter TINYINT(1) NOT NULL DEFAULT 0,
 fetched_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
 planned_at DATETIME NULL,
 KEY idx_weather_alerts_expires(expires)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
INSERT IGNORE INTO app_migrations(version) VALUES('440_push');
