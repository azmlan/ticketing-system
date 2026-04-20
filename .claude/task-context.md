# Session Context — Ticketing System Phase 1

## Branch
`feature/phase-1-task-1-1-bootstrap` (all phase-1 tasks commit here)

## Docker command prefix
```bash
docker compose -f /Users/abdulaziz/projects/ticketing-system/docker-compose.yml exec app
```
Example: `... exec app php artisan test`

## Completed tasks
- ✅ 1.1 — Docker + Laravel 12 bootstrap, Pest, Horizon, Sanctum, module scaffold
- ✅ 1.2 — users, departments, locations migrations + models + factories + tests
- ✅ 1.3 — permissions, permission_user, tech_profiles migrations + models + PermissionSeeder + tests
- ✅ 1.4 — AuthProviderInterface + EmailPasswordAuthProvider + Livewire auth flows + Redis rate limits + password policy + tests/Feature/Auth/
- ✅ 1.5 — Profile edit + SetLocaleMiddleware + AR/EN translation scaffolds + tests/Feature/Profile/

## Key file locations
```
app/Modules/Shared/Models/
  User.php          ← Authenticatable, HasUlids, SoftDeletes
  Department.php
  Location.php
  Permission.php
  TechProfile.php

app/Modules/Shared/Middleware/
  SetLocaleMiddleware.php  ← reads user.locale, shares $dir/$lang via View::share()

app/Modules/Auth/
  Contracts/AuthProviderInterface.php
  Providers/EmailPasswordAuthProvider.php
  Providers/AuthServiceProvider.php   ← bound to bootstrap/providers.php; uses Route::middleware('web')
  Livewire/Register.php
  Livewire/Login.php
  Livewire/PasswordResetRequest.php
  Livewire/PasswordReset.php
  Livewire/Profile.php
  Routes/web.php  ← /register /login /password/reset /profile /logout; all wrapped in web middleware

database/migrations/
  2026_04_20_000001_create_departments_table.php
  2026_04_20_000002_create_locations_table.php
  2026_04_20_000003_create_users_table.php
  2026_04_20_000004_create_permissions_table.php
  2026_04_20_000005_create_permission_user_table.php
  2026_04_20_000006_create_tech_profiles_table.php

config/permissions.php   ← 19 permission keys, source of truth
config/role_bundles.php  ← technician / group_manager / it_manager arrays
config/rate_limits.php   ← login/register/password_reset limits

database/seeders/PermissionSeeder.php  ← idempotent upsert by key

resources/lang/{ar,en}/
  auth.php / profile.php / common.php / validation.php  ← full key parity, both locales

resources/views/
  layouts/guest.blade.php   ← uses $dir/$lang from View::share
  layouts/app.blade.php     ← uses $dir/$lang from View::share
  livewire/auth/{register,login,password-reset-request,password-reset,profile}.blade.php

tests/
  Pest.php  ← RefreshDatabase in Feature/Schema, Feature/Auth, Feature/Profile
  TestCase.php  ← withoutVite() in setUp()
  Feature/Schema/   ← 38 schema tests
  Feature/Auth/     ← 21 auth tests (ContainerTest, LoginTest, PasswordResetTest, RegistrationTest)
  Feature/Profile/  ← 17 profile/locale/translation-parity tests
```

## Critical SPEC-over-task-file overrides (apply to all future tasks)
- Users have `full_name` (not `name_ar`/`name_en`) and `phone` (not `mobile`)
- `users.department_id` / `users.location_id` → ON DELETE SET NULL (not RESTRICT)
- `permissions` table column is `group_key` (not `group` — reserved word)
- `permission_user` has no separate ULID PK; composite PK (user_id, permission_id)
- `tech_profiles.promoted_by` → ON DELETE RESTRICT, NOT NULL (per SPEC §6.2)
- No SoftDeletes on tech_profiles or permissions (SPEC §2.3 "Tables that use NEITHER")

## Infrastructure notes
- Laravel 12 uses `bootstrap/app.php` (NOT app/Http/Kernel.php) for middleware registration
- Module routes loaded via `Route::middleware('web')->group(...)` in service provider (NOT loadRoutesFrom) to pick up full web stack (session, CSRF, SetLocaleMiddleware)
- CSRF is NOT auto-disabled in tests for these routes — POST tests must provide matching `_token` in session + request body, e.g.: `$this->withSession(['_token' => 'x'])->post(url, ['_token' => 'x'])`
- `withoutVite()` is called globally in `TestCase::setUp()` to allow full-page rendering tests
- `bootstrap/providers.php` registers: AppServiceProvider, HorizonServiceProvider, AuthServiceProvider

## Test count so far
77 tests, 194 assertions — all passing

## Next task
**Task 1.6** — docs/tasks/phase-1/task-1-6.md
Permission middleware + Blade directives (@permission/@unlesspermission) + Gate definitions +
SuperUser bypass + custom 403 view + tests/Feature/Authorization/
