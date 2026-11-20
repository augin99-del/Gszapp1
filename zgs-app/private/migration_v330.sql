-- Immutable bytes accepted by SMTP; NULL for historical drafts (never reconstruct).
ALTER TABLE mail_drafts
 ADD COLUMN IF NOT EXISTS smtp_raw MEDIUMBLOB NULL,
 ADD COLUMN IF NOT EXISTS smtp_raw_sha256 CHAR(64) NOT NULL DEFAULT '',
 ADD COLUMN IF NOT EXISTS sent_copy_folder VARCHAR(190) NOT NULL DEFAULT '',
 ADD COLUMN IF NOT EXISTS sent_copy_uidvalidity BIGINT UNSIGNED NOT NULL DEFAULT 0,
 ADD COLUMN IF NOT EXISTS sent_copy_uid BIGINT UNSIGNED NULL,
 ADD COLUMN IF NOT EXISTS sent_copy_uncertain TINYINT NOT NULL DEFAULT 0;
INSERT IGNORE INTO app_migrations(version) VALUES('330_mail_recovery');
