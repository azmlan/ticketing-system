# Phase 1 — Foundation

**Spec reference:** [SPEC.md §6](../../SPEC.md#6-phase-1--foundation)
**Deliverable:** Running Laravel app with authentication, user profiles, role/permission system, tech profile promotion, and the base layout with full AR/EN localization.
**Exit condition:** All items in [§6.10 Phase 1 Acceptance Criteria](../../SPEC.md#610-phase-1-acceptance-criteria) pass.

## Tasks

- [x] **Task 1.1** — Bootstrap Docker stack, Laravel 12 + Livewire 3 starter kit, Horizon, Tailwind 4, `app/Modules/*` directory scaffold, connection to shared `_infra/` services (MySQL, Redis, Mailpit), `.env.example`, Pest smoke test hitting `/`.
- [x] **Task 1.2** — Migrations, models, factories for `users` (ULIDs, `is_tech`, `is_super_user`, `locale`, SoftDeletes), `departments`, `locations` (bilingual `name_ar`/`name_en`, `is_active`, `sort_order`, SoftDeletes) + schema feature tests.
- [x] **Task 1.3** — Migrations, models, factories for `permissions`, `permission_user`, `tech_profiles`; build `config/permissions.php` registry with all keys from §6.3 + seeder + seeder tests.
- [ ] **Task 1.4** — Define `AuthProviderInterface` + `EmailPasswordAuthProvider` binding; Livewire registration/login/password-reset with password policy (10 chars + complexity); Redis rate limits (5/min login IP+email, 3/hr registration, 3/hr reset); feature tests for happy/edge paths.
- [ ] **Task 1.5** — Profile edit Livewire (all registration fields + locale toggle); `SetLocaleMiddleware` applying `dir`/`lang` to `<html>`; AR/EN translation file scaffolds organized by module; logical-property RTL verification test.
- [ ] **Task 1.6** — Permission middleware (`can:<key>`), Blade `@permission` directive, Gate wiring with SuperUser bypass; authorization feature tests (grant, revoke, middleware block, Blade hide).
- [ ] **Task 1.7** — Tech promotion Livewire (`user.promote`) that creates `tech_profiles` row with `promoted_by`/`promoted_at`; `app:create-superuser` artisan command; base app layout (collapsible sidebar gated by permissions + `is_tech`, user menu with language switcher, security headers middleware); feature tests.

## Session Groupings

| Session | Tasks | Rationale |
|---------|-------|-----------|
| S1 | 1.1 | Infra bootstrap — container and Laravel baseline must work before any app code; isolated. |
| S2 | 1.2, 1.3 | DB layer bundle: user-facing schemas + permission registry share migration ordering concerns and seeding. |
| S3 | 1.4 | Auth provider interface + login/registration/reset with rate limits — full session; drives invariant §6.6. |
| S4 | 1.5, 1.6 | Cross-cutting middleware pair (locale + permission) that every feature will depend on. |
| S5 | 1.7 | Promotion flow + superuser command + layout polish closes the phase's UI surface. |

## Acceptance Gate (from SPEC §6.10)

- [ ] User can register, log in, reset password, edit profile
- [ ] Locale switches between AR and EN; layout flips RTL/LTR correctly
- [ ] Permission system functional: grant, revoke, check via middleware and Blade
- [ ] IT Manager account can be created and assigned all permissions via seeder/command
- [ ] Tech promotion flow works: promote employee, create tech profile, assign permissions
- [ ] Docker Compose stack runs with all services (PHP, Nginx, connected to shared infra MySQL, Redis, Mailpit)
- [ ] All user-facing strings are in translation files (no hardcoded strings)
- [ ] Rate limiting active on login and registration endpoints
- [ ] Security headers configured via middleware
- [ ] Module directory structure in place
