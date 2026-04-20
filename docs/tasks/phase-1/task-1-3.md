# Task 1.3 — Permissions, Tech Profiles, Permission Registry

## Context
User schema from Task 1.2 exists. Authorization is permission-based (no role column). This task delivers the granular permission system and the tech profile join every later feature checks.

## Task
Create `permissions`, `permission_user`, `tech_profiles` tables + models + factories; build the `config/permissions.php` registry enumerating every permission key in `SPEC.md §6.3`; write a seeder that inserts all permissions and produces a tested, ready baseline.

## Requirements
- `permissions` table: ULID PK, `key` string unique (e.g. `ticket.create`, `ticket.view-all`), `name_ar`, `name_en`, `description_ar`, `description_en`, `group` string (e.g. `ticket`, `user`, `system`) for admin UI grouping later, timestamps. No soft-deletes (permissions are fixed registry entries).
- `permission_user` pivot: ULID PK, `user_id` + `permission_id` FKs (`ON DELETE CASCADE`), `granted_by` FK → `users` nullable (`ON DELETE SET NULL`), `granted_at` timestamp, UNIQUE (`user_id`, `permission_id`), indexed per `SPEC.md §5.3`.
- `tech_profiles` table: ULID PK, `user_id` FK UNIQUE (`ON DELETE CASCADE`), `promoted_by` FK → `users` nullable (`ON DELETE SET NULL`), `promoted_at` timestamp, `is_available` bool default true, timestamps, SoftDeletes.
- `config/permissions.php` registry exporting an array of every key listed in `SPEC.md §6.3` (ticket.*, user.*, group.*, system.*, admin.*) with `name_ar/en`, `description_ar/en`, `group`. This file is the source of truth; the seeder reads it.
- `PermissionSeeder` (idempotent — upsert by `key`) seeding the registry on every run.
- Models in `app/Modules/Shared/Models/` with relationships (`User::permissions()`, `User::techProfile()`, `Permission::users()`).
- Pre-defined role bundles from `SPEC.md §6.4` encoded as arrays referenced by the seeder (not inserted as rows — just reusable constants for Task 1.7's superuser command and Phase 8 promotion flow).

## Do NOT
- Do not implement the permission middleware/Gate here — that's Task 1.6.
- Do not create a `roles` table — bundles are config constants, not DB rows.
- Do not seed users or assign permissions in this task.
- Do not hardcode permission keys in code — everything reads from `config/permissions.php`.
- Do not add Redis caching yet — Phase 10.4 handles permission lookup cache.

## Acceptance
- `php artisan migrate && php artisan db:seed --class=PermissionSeeder` succeeds.
- Pest feature tests:
  - Seeder idempotent — running twice does not duplicate rows.
  - Every key in `config/permissions.php` has a matching DB row after seeding.
  - Granting a permission to a user creates a `permission_user` row with `granted_by` / `granted_at` populated; duplicate grant fails UNIQUE.
  - `User::permissions()` eager-loads the attached permissions.
  - Creating a `tech_profile` for a user links correctly; second profile for same user fails UNIQUE.
  - Deleting a user cascades `permission_user` and `tech_profiles` rows.
- All permission keys from `SPEC.md §6.3` present in the config file (assert with a test iterating the section list).

## References
- `SPEC.md §6.3` — permission registry (full key list)
- `SPEC.md §6.4` — role bundles
- `SPEC.md §5.1, §5.3` — FK + composite index patterns
- `CLAUDE.md` — DB Conventions
