# Task 4.1 — Phase 4 Database Migrations

## Context
Phase 3 delivered the escalation workflow. Phase 4 adds the communication layer: threaded comments and a notification audit trail. The `response_templates` table is also created here because Phase 4.3 depends on it; the admin CRUD ships in Phase 8.

## Task
Write and run all Phase 4 migrations in dependency order, creating `comments`, `notification_logs`, and `response_templates`.

## Requirements
- Migrations prefixed `2025_04_*` in dependency order. Full column specs per §9.1 and §13.2.
- `comments`: ULID PK, `ticket_id` FK ON DELETE CASCADE, `user_id` FK ON DELETE RESTRICT, `body` text NOT NULL (sanitized HTML stored here), `is_internal` boolean NOT NULL DEFAULT true, standard timestamps. FULLTEXT index on `body`. Composite index `(ticket_id, created_at)` per §5.3.
- `notification_logs`: ULID PK, `recipient_id` FK ON DELETE CASCADE, `ticket_id` FK NULLABLE ON DELETE SET NULL, `type` varchar(100) NOT NULL, `channel` varchar(20) NOT NULL DEFAULT 'email', `subject` varchar(500) NOT NULL, `body_preview` varchar(500) NULLABLE, `status` enum(`queued`,`sent`,`failed`) NOT NULL, `sent_at` NULLABLE timestamp, `failure_reason` text NULLABLE, `attempts` int unsigned NOT NULL DEFAULT 0, standard timestamps. Index on `(recipient_id, created_at)`, index on `(status, created_at)`.
- `response_templates`: ULID PK, `title_ar` + `title_en` varchar(255) NOT NULL, `body_ar` + `body_en` text NOT NULL, `is_internal` boolean NOT NULL DEFAULT true, `is_active` boolean NOT NULL DEFAULT true, `deleted_at` timestamp NULLABLE (SoftDeletes), standard timestamps. Per §13.2.
- All FK ON DELETE actions explicit per §5.1. Every FK column has its own index.

## Do NOT
- Do not create Eloquent models here (Task 4.2).
- Do not touch Phase 1–3 migrations.
- Do not add `deleted_at` to `comments` or `notification_logs` — neither uses SoftDeletes per §2.3.
- Do not seed template data — factories handle test data.

## Acceptance
- `docker compose exec app php artisan migrate:fresh --seed` runs clean.
- `SHOW CREATE TABLE comments` shows `is_internal` DEFAULT 1, FULLTEXT index on `body`, RESTRICT on `user_id` FK.
- `SHOW CREATE TABLE notification_logs` shows 3-value status enum, nullable `ticket_id`, nullable `sent_at`.
- `SHOW CREATE TABLE response_templates` shows bilingual columns, `deleted_at`, `is_active`.
- Pest `tests/Feature/Phase4/MigrationStructureTest.php`: asserts all 3 tables exist, spot-checks columns, enum values, and FK indexes.

## References
- `SPEC.md §9.1` — `comments` and `notification_logs` full column spec
- `SPEC.md §13.2` — `response_templates` column spec
- `SPEC.md §5.1` — FK ON DELETE pattern map
- `SPEC.md §5.3` — composite index strategy
- `SPEC.md §2.3` — which tables use SoftDeletes
