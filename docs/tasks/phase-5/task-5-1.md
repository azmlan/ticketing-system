# Task 5.1 — Phase 5 Database Migrations

## Context
Phase 4 delivered the communication layer. Phase 5 adds the SLA engine. Three tables must exist before any SLA logic can run: `sla_policies`, `ticket_sla`, and `sla_pause_logs`.

## Task
Write and run all Phase 5 migrations in dependency order, creating `sla_policies`, `ticket_sla`, and `sla_pause_logs`.

## Requirements
- Migrations prefixed `2026_05_*` in dependency order. Full column specs per §10.1.
- `sla_policies`: ULID PK, `priority` enum(`low`,`medium`,`high`,`critical`) NOT NULL UNIQUE, `response_target_minutes` int unsigned NOT NULL, `resolution_target_minutes` int unsigned NOT NULL, `use_24x7` boolean NOT NULL DEFAULT false, timestamps.
- `ticket_sla`: ULID PK, `ticket_id` ULID FK NOT NULL UNIQUE ON DELETE CASCADE, `response_target_minutes` + `resolution_target_minutes` int unsigned NULLABLE, `response_elapsed_minutes` + `resolution_elapsed_minutes` int unsigned NOT NULL DEFAULT 0, `response_met_at` timestamp NULLABLE, `response_status` + `resolution_status` enum(`on_track`,`warning`,`breached`) NOT NULL DEFAULT `on_track`, `last_clock_start` timestamp NULLABLE, `is_clock_running` boolean NOT NULL DEFAULT true, timestamps.
- `sla_pause_logs`: ULID PK, `ticket_sla_id` ULID FK NOT NULL ON DELETE CASCADE, `paused_at` timestamp NOT NULL, `resumed_at` timestamp NULLABLE, `pause_status` varchar(50) NOT NULL, `duration_minutes` int unsigned NULLABLE, timestamps.
- Indexes per §5.3: `ticket_sla(response_status)`, `ticket_sla(resolution_status)`. Every FK column has its own index.
- No SoftDeletes on any of these three tables per §2.3.

## Do NOT
- Do not create Eloquent models here (Task 5.2).
- Do not seed `sla_policies` data in migrations — use a seeder/factory.
- Do not touch Phase 1–4 migrations.
- Do not add columns not listed in §10.1.

## Acceptance
- `docker compose exec app php artisan migrate:fresh --seed` runs clean.
- `SHOW CREATE TABLE sla_policies` shows 4-value priority enum, UNIQUE on `priority`.
- `SHOW CREATE TABLE ticket_sla` shows UNIQUE on `ticket_id`, nullable targets, both status enums default `on_track`.
- `SHOW CREATE TABLE sla_pause_logs` shows nullable `resumed_at`, nullable `duration_minutes`.
- Pest `tests/Feature/Phase5/MigrationStructureTest.php`: asserts all 3 tables exist, spot-checks columns, enum values, FK indexes, and absence of `deleted_at`.

## References
- `SPEC.md §10.1` — full column specs for all 3 tables
- `SPEC.md §5.1` — FK ON DELETE pattern map
- `SPEC.md §5.3` — indexing strategy
- `SPEC.md §2.3` — which tables use SoftDeletes
