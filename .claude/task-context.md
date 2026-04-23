# Session Context — Ticketing System Phase 2

## Branch convention
`feature/phase-2-task-X.Y-short-name` per task

## Docker command prefix
```bash
docker compose exec app php artisan ...
```

## Completed tasks

### Phase 1 (all done)
- ✅ 1.1 — Docker + Laravel 12 bootstrap, Pest, Horizon, Sanctum, module scaffold
- ✅ 1.2 — users, departments, locations migrations + models + factories + tests
- ✅ 1.3 — permissions, permission_user, tech_profiles migrations + models + PermissionSeeder + tests
- ✅ 1.4 — AuthProviderInterface + EmailPasswordAuthProvider + Livewire auth flows + Redis rate limits + password policy + tests/Feature/Auth/
- ✅ 1.5 — Profile edit + SetLocaleMiddleware + AR/EN translation scaffolds + tests/Feature/Profile/
- ✅ 1.6 — PermissionMiddleware, Gate definitions, Blade directives, SuperUser bypass, 403 view
- ✅ 1.7 — PromoteToTech, CreateSuperUser command, SecurityHeaders, app layout, locale toggle

### Phase 2
- ✅ 2.1 — 8 migrations (groups→categories→subcategories→tickets→ticket_counters→group_user→transfer_requests→ticket_attachments), GroupSeeder/CategorySeeder/SubcategorySeeder, MigrationStructureTest
- ✅ 2.2 — 6 models (Ticket, TicketAttachment, TransferRequest, Category, Subcategory, Group), TicketStatus/TicketPriority enums, EmployeeTicketScope, 4 factories, 28 Unit/Models tests
- ✅ 2.3 — TicketStateMachine, InvalidTicketTransitionException (HTTP 422), TicketStatusChanged event, TicketsServiceProvider, 23 Unit/Services tests

## Phase 2 key file locations
```
database/migrations/
  2026_04_21_000001_create_groups_table.php
  2026_04_21_000002_create_categories_table.php
  2026_04_21_000003_create_subcategories_table.php
  2026_04_21_000004_create_tickets_table.php          ← FULLTEXT MySQL-only gated
  2026_04_21_000005_create_ticket_counters_table.php  ← seeds id=1,last_number=0 inline
  2026_04_21_000006_create_group_user_table.php
  2026_04_21_000007_create_transfer_requests_table.php
  2026_04_21_000008_create_ticket_attachments_table.php

database/seeders/
  GroupSeeder.php        ← 2 groups (AR/EN)
  CategorySeeder.php     ← 2 categories, one per group
  SubcategorySeeder.php  ← 2 subcategories per category (4 total)

tests/Feature/Phase2/MigrationStructureTest.php  ← 15 pass, 5 MySQL-only skipped
tests/Pest.php  ← Feature/Phase2 added to RefreshDatabase list
```

## Phase 1 key file locations
```
app/Modules/Shared/Models/
  User.php, Department.php, Location.php, Permission.php, TechProfile.php

app/Modules/Auth/
  Contracts/AuthProviderInterface.php
  Providers/EmailPasswordAuthProvider.php
  Routes/web.php

config/permissions.php   ← 19 permission keys
config/role_bundles.php
config/rate_limits.php
database/seeders/PermissionSeeder.php
```

## Critical SPEC-over-task-file overrides
- Users have `full_name` (not `name_ar`/`name_en`) and `phone` (not `mobile`)
- `users.department_id` / `users.location_id` → ON DELETE SET NULL (not RESTRICT)
- `permissions` table column is `group_key` (not `group`)
- `permission_user` — composite PK (user_id, permission_id), no separate ULID PK
- `tech_profiles.promoted_by` → ON DELETE RESTRICT, NOT NULL
- No SoftDeletes on tech_profiles or permissions
- Migration date prefix for Phase 2: `2026_04_21_*` (not `2025_02_*` as in task files — needed to run after Phase 1)

## Infrastructure notes
- Laravel 12 uses `bootstrap/app.php` (NOT app/Http/Kernel.php)
- Module routes loaded via `Route::middleware('web')->group(...)` in service provider
- Tests use SQLite in-memory (phpunit.xml); MySQL-only assertions use `markTestSkipped`
- `withoutVite()` called globally in TestCase::setUp()

## Test count
181 tests, 475 assertions — all passing (5 MySQL-only skipped)

## Next task
**Task 2.4** — docs/tasks/phase-2/task-2-4.md
TicketStateMachine already done in 2.3 (task file numbering shifted). Check task-2-4.md.
