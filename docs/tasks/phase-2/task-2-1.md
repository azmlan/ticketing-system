# Task 2.1 — Phase 2 Database Migrations

## Context
Phase 1 established the user/auth schema. Phase 2 introduces the core ticketing domain: 8 new tables plus the `ticket_counters` locking table.

## Task
Write and run all Phase 2 migrations in the correct dependency order, then seed minimal fixture data (groups, categories, subcategories) to support manual and automated testing.

## Requirements
- Migrations prefixed `2025_02_*` and ordered: `groups` → `categories` → `subcategories` → `tickets` → `ticket_counters` → `group_user` → `transfer_requests` → `ticket_attachments`. Refer to §7.1 for full column/constraint/index spec.
- `tickets.status` as enum of all 9 values per §7.4. `priority` NULLABLE enum.
- `ticket_counters`: single row (`id=1`, `last_number=0 bigint unsigned`). Seed this row in the migration itself.
- `transfer_requests`: composite index on `(ticket_id, status)` per §7.1.
- All FK `ON DELETE` actions explicit per §7.1 (RESTRICT / SET NULL / CASCADE — match the table spec exactly).
- Every FK column has its own explicit index.
- ULID PKs (`char(26)`) on all tables except `ticket_counters` (int PK).
- Seeder: `GroupSeeder` (2 groups, AR + EN names), `CategorySeeder` (2 categories mapped to groups), `SubcategorySeeder` (2 subcategories per category). Wire into `DatabaseSeeder`.

## Do NOT
- Do not create Eloquent models here (Task 2.2).
- Do not touch `_infra/` or existing Phase 1 migrations.
- Do not use `$table->id()` for ULID tables — use `$table->char('id', 26)->primary()`.
- Do not add `locations` or `departments` tables — those are scoped to a later phase.

## Acceptance
- `docker compose exec app php artisan migrate:fresh --seed` runs without errors.
- `SHOW CREATE TABLE tickets` shows all 9 enum status values, correct FKs, and explicit indexes.
- `ticket_counters` table contains exactly one row with `last_number = 0`.
- Pest: `tests/Feature/Phase2/MigrationStructureTest.php` asserts each table exists, spot-checks nullable columns and FK columns are present.
- Seeder produces 2 groups, 2 categories, 4 subcategories in DB.

## References
- `SPEC.md §7.1` — full schema for all 8 tables
- `SPEC.md §5.1` — FK ON DELETE pattern map
- `SPEC.md §5.3` — composite index requirements
- `SPEC.md §2.3` — ULID primary key convention
