# Entfernt mit Version 380

## Datenbanktabellen (Migration 380, `private/migration_v380.sql`)

Ausschließlich Tabellen, die nur zum Finanzblock gehörten (24). Alle übrigen Tabellen – insbesondere alle `estate_*`,
`mail_*`, `waste_*`, `tasks`, `properties`, `customers`, `documents` – bleiben erhalten.

| Bereich | Tabellen |
|---|---|
| Angebote/Rechnungen | `invoices`, `invoice_items`, `invoice_events`, `invoice_sequences`, `offer_sequences` |
| Offene Posten/Mahnungen | `invoice_reminders`, `invoice_reminder_proposals` |
| EÜR/Belege/Zahlungen | `finance_payments`, `finance_receipts`, `receipt_sequences` |
| Wiederkehrende Finanzbuchungen | `finance_recurrences`, `finance_recurrence_occurrences`, `finance_recurrence_proposals`, `finance_recurrence_revisions` |
| Kasse/Anlagen | `cash_accounts`, `cash_entries`, `asset_register`, `asset_events`, `asset_plan_rows`, `asset_sequences` |
| Geschäftskonto (Sparkasse) | `bank_accounts`, `bank_transactions`, `bank_imports` |
| Live-Bankanbindung | `bank_connections` |

Zusätzlich gelöst (keine Tabelle gelöscht): der Fremdschlüssel `mail_messages.invoice_id → invoices` (die Spalte bleibt,
wird nicht mehr benutzt). Gelöscht wird die Zeile `automation_state.job_key='bank'` (Status des entfernten Cron-Jobs).
Alte Einstellungen `banking_*`, `invoice_*`, `allow_invoice_issue`, `allow_unvalidated_xml`, `bank_import_max_rows`
werden aus der verschlüsselten Konfiguration entfernt (inkl. eines evtl. gespeicherten Bankanbieter-Schlüssels).
Die Versionseinträge früherer Migrationen (`240_finance_basis` …) bleiben im Verlauf `app_migrations` stehen.

Bestehende Dokumente der Kategorie „Rechnung“ im Modul Dokumente bleiben erhalten. Private Dateien unter
`private/uploads/invoices/` bzw. `receipts/` (Testdaten) löscht das Update nicht; sie können per Dateimanager entfernt werden.

## Programmdateien

Diese Dateien entfernt das Update auch vom Server (`private/obsolete_files_380.txt`):

```
public/accounting.php        public/bank.php              public/bank_callback.php     public/bank_connections.php
public/cash_assets.php       public/finance_bundle.php    public/finance_export.php    public/finance_recurrences.php
public/invoice.php           public/invoice_csv.php       public/invoice_export.php    public/invoice_send.php
public/invoices.php          public/offers.php            public/receivables.php       public/reminder_download.php
public/assets.css            public/planning.css          public/gsz-dashboard.css     public/gsz-dashboard.js
public/app-unified.css
private/banking_connection_provider.php  private/banking_connections.php  private/business_documents.php
private/cash_assets.php      private/finance_core.php     private/finance_export_service.php
private/finance_recurrences.php          private/finance_service.php      private/invoice_archive.php
private/invoice_lib.php      private/invoice_pdf.php      private/receivables.php      private/waste_widget.php
```

Gemeinsam genutzter Code wurde vorher herausgelöst und bleibt: Geldbeträge → `private/money.php`,
CSV/ZIP-Export → `private/export_core.php`, PDF-Erzeugung (Nebenkosten, Arbeitsnachweise) → `private/pdf_document.php`,
CSV-/CAMT-Importkern → `private/bank_formats.php` (nur noch für die Immobilienkonten).

## Tests

Entfernt (prüften ausschließlich den Finanzblock): `test_asset_classification_database.php`, `test_banking_connections.php`,
`test_banking_connections_browser.cjs`, `test_business_documents.php`, `test_cash_assets*.php/.cjs` (5),
`test_finance_core.php`, `test_finance_database.php`, `test_finance_recurrences*.php/.cjs` (3),
`test_receivables_database.php`, `test_receivables_browser.cjs`, `prepare_receivables_browser.php`,
`test_final_bank_concurrency.cjs`, `test_final_bank_review_browser.cjs`, `test_final_applied_import_browser.cjs`.
`test_invoice_pdf.php` wurde zu `test_pdf_document.php` (gemeinsame PDF-Erzeugung).

## Dokumentation

Alle früheren Markdown-/Textdateien aus dem Hauptordner liegen unverändert in `docs/archiv/` (Historie).
