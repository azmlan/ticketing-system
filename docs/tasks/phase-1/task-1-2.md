# Task 1.2 — Users, Departments, Locations Schema

## Context
App skeleton exists from Task 1.1. No domain tables yet. This task lays down the user identity and bilingual reference tables every later phase reads from.

## Task
Create migrations, models, and factories for `users`, `departments`, and `locations` matching the schema rules in `SPEC.md §6.2` and the DB conventions in CLAUDE.md.

## Requirements
- `users` table: ULID PK (`char(26)`, `HasUlids`), email unique, password, `name_ar`, `name_en`, `mobile`, `department_id` FK → `departments` (`ON DELETE RESTRICT`), `location_id` FK → `locations` (`ON DELETE RESTRICT`), `is_tech` bool default false, `is_super_user` bool default false, `locale` enum `ar|en` default `ar`, standard timestamps, `deleted_at` (SoftDeletes).
- `departments` + `locations` tables: ULID PK, `name_ar` NOT NULL, `name_en` NOT NULL, `sort_order` int default 0, `is_active` bool default true, timestamps, `deleted_at` (SoftDeletes).
- Every FK indexed explicitly; no implicit `ON DELETE`.
- Eloquent models in `app/Modules/Shared/Models/` with `HasUlids`, `SoftDeletes`, casts for `is_tech`/`is_super_user`/`is_active`/`locale`, relationship methods (`department()`, `location()`, `users()`).
- Factories for all three models using `fake()->locale('ar_SA')` for `name_ar` and `fake()->locale('en_US')` for `name_en` where applicable.
- Migrations prefixed by module for ordering (per CLAUDE.md).

## Do NOT
- Do not add `role` column — use `is_tech`/`is_super_user` bools + permission table (Task 1.3).
- Do not add fields beyond `SPEC.md §6.2` (no `phone_ext`, `avatar`, etc.).
- Do not create seeders yet (Task 1.3 handles permission seeder; user seeding lands with `app:create-superuser` in Task 1.7).
- Do not cross-reference `tech_profiles` here (that's Task 1.3).
- Do not use `bigint` IDs anywhere — ULID only.

## Acceptance
- `docker compose exec app php artisan migrate` runs clean on empty DB.
- Pest feature tests in `tests/Feature/Schema/`:
  - Create user with all required fields — succeeds.
  - Omit `name_ar` or `name_en` — fails (NOT NULL).
  - Soft-delete user → `deleted_at` populated, `User::withTrashed()->find($id)` returns the row.
  - `department()` / `location()` relationships resolve.
  - Delete department referenced by a user — blocked by `RESTRICT`.
  - Factory produces valid rows for all three models.
- `php artisan migrate:rollback` reverses cleanly.

## References
- `SPEC.md §6.2` — users/departments/locations schema
- `SPEC.md §5.1` — FK ON DELETE pattern map
- `CLAUDE.md` — Database Conventions (dual policy, bilingual columns, ULIDs)
