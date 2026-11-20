-- Einmalig NACH v0.4 ausfuehren. Vorher Backup erstellen.
ALTER TABLE recurring_jobs ADD COLUMN next_due_date DATE NULL;
ALTER TABLE tasks ADD COLUMN recurring_job_id INT UNSIGNED NULL, ADD COLUMN scheduled_for DATE NULL;
ALTER TABLE tasks ADD CONSTRAINT fk_tasks_recurring FOREIGN KEY (recurring_job_id) REFERENCES recurring_jobs(id) ON DELETE SET NULL;
ALTER TABLE tasks ADD UNIQUE KEY uq_recurring_occurrence (recurring_job_id, scheduled_for);
-- Bestehende Vorlagen erhalten noch keinen Termin: diesen im Modul einmalig setzen.
