# Task 3.1 — Phase 3 Database Migrations

## Context
Phase 2 established the core ticketing schema. Phase 3 adds 3 escalation tables that power the condition report and maintenance request workflows.

## Task
Write and run all Phase 3 migrations in the correct dependency order, creating `condition_reports`, `condition_report_attachments`, and `maintenance_requests`.

## Requirements
- Migrations prefixed `2025_03_*` in dependency order: `condition_reports` → `condition_report_attachments` → `maintenance_requests`. Full column specs per §8.1.
- `condition_reports`: status enum of `pending`, `approved`, `rejected` (NOT NULL, DEFAULT `pending`). All text fields (`current_condition`, `condition_analysis`, `required_action`) are NOT NULL. `reviewed_by` nullable FK, `reviewed_at` nullable timestamp, `review_notes` nullable text.
- `condition_report_attachments`: ON DELETE CASCADE on `condition_report_id` FK per §5.1. No ULID on attachment ID check spec — it has a ULID PK per §8.1.
- `maintenance_requests`: status enum of `pending`, `submitted`, `approved`, `rejected`. `generated_locale` varchar(5) NOT NULL. `submitted_file_path` nullable. `rejection_count` unsigned int NOT NULL DEFAULT 0. `reviewed_by` nullable FK ON DELETE SET NULL per §5.1.
- All FK `ON DELETE` actions explicit per §5.1 (CASCADE for ticket-owned attachments, SET NULL for nullable user FKs, CASCADE for ticket FKs on maintenance_requests and condition_reports).
- Every FK column has its own explicit index.
- ULID PKs (`char(26)`) on all 3 tables — no `$table->id()`.

## Do NOT
- Do not create Eloquent models here (Task 3.2).
- Do not touch Phase 1 or Phase 2 migrations.
- Do not add `deleted_at` to any of these tables — none use SoftDeletes per §2.3.
- Do not seed fixture data — factories cover test data needs.

## Acceptance
- `docker compose exec app php artisan migrate:fresh --seed` runs without errors after adding these migrations.
- `SHOW CREATE TABLE condition_reports` shows the 3-value status enum, correct nullable columns, and indexed FK columns.
- `SHOW CREATE TABLE maintenance_requests` shows the 4-value status enum, `rejection_count` column, and `generated_locale` column.
- Pest: `tests/Feature/Phase3/MigrationStructureTest.php` asserts all 3 tables exist, spot-checks enum values, nullable columns, and FK column presence.

## References
- `SPEC.md §8.1` — full column and constraint spec for all 3 escalation tables
- `SPEC.md §5.1` — FK ON DELETE pattern map
- `SPEC.md §2.3` — ULID primary key convention and which tables use SoftDeletes
