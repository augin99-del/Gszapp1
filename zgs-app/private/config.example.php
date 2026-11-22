<?php
// All keys can be overridden by GSZ_<UPPERCASE_KEY> server environment variables.
return [
  'audit_enabled' => true,
  'maintenance' => false,
  'allow_web_update' => false, // temporary, authenticated owner + reauthentication + backup evidence
  'automation_schedule_enabled' => false, // enable only after real hosting cron setup
  'automation_interval_minutes' => 30,
  'automation_scheduled_jobs' => 'tasks,planning,reminders', // mail only with --mail-sync; configured v360 property calendars also run in the base job
  'seo_provider' => 'none', // no live ranking API enabled; verified CSV import works offline

  'session_idle_seconds' => 1800,
  'mail_adapter'=>'auto',
  'mail_enabled' => false, // explicit sync/read and remote mutations only after setup
  'mail_signature' => '',
  'mail_sent_folder' => '',
  'mail_trash_folder' => '',
  'mail_archive_folder' => '',
  'mail_sync_limit' => 50,
  'waste_sync_config_json' => '', // private explicit source-ID/HTTPS-host/URL/fraction mapping; no invented defaults

  'routing_cache_ttl' => 3600, // seconds, clamped 60..86400
  'routing_max_points' => 25, // clamped 2..25; one matrix request per preview max
  'routing_enabled' => false, // only after explicit operator approval; no public demos
  'routing_osrm_endpoint' => '', // dedicated self-hosted/licensed HTTPS service

  'db_port' => 3306,
  'db_socket' => '', // Optional, local isolated tests only
  'allow_mail_send' => false, // Enable only after explicit release and a test-account check
  'db_host' => 'DATENBANK_HOST_VON_STRATO',
  'db_name' => 'DATENBANKNAME',
  'db_user' => 'DATENBANKBENUTZER',
  'db_pass' => 'DATENBANKPASSWORT',
  // Erst nach HTTPS und Hostingpruefung auf dem Server konfigurieren.
  'mail_host' => 'imap.strato.de', // STRATO IMAP-Hostname laut Kundenbereich
  'mail_port' => 993,
  'mail_user' => '',
  'mail_pass' => '',
  'smtp_transport' => 'phpmailer', // pinned LGPL SMTP component; native fallback available
  'smtp_host' => 'smtp.strato.de', // STRATO SMTP-Hostname aus Kundenbereich
  'smtp_port' => 465, // SSL/TLS;587 STARTTLS nur bei bestätigtem Serverprofil
  'mail_from' => '', // Verifizierte Absenderadresse des Postfachs
  'setup_key' => 'HIER_EINEN_LANGEN_ZUFAELLIGEN_EINMALCODE_EINTRAGEN',
];
