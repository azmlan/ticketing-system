# Task 1.7 — Tech Promotion + Create-SuperUser Command + Base Layout

## Context
Closes Phase 1. Auth, permissions, profile, and enforcement are live. Missing: the path to promote an employee to tech, a reproducible way to bootstrap the first SuperUser, and the shared layout every authenticated page renders into.

## Task
Ship the tech promotion Livewire flow, the `app:create-superuser` artisan command, and the base application layout with a permission-gated sidebar, language switcher, and security headers middleware.

## Requirements
- Livewire `PromoteToTech` component gated by `permission:user.promote` (middleware + in-component check). Form selects a user whose `is_tech=false`, confirms, and in one DB transaction:
  - Sets `users.is_tech = true`.
  - Inserts a `tech_profiles` row with `promoted_by = auth()->id()`, `promoted_at = now()`, `is_available = true`.
  - Does NOT grant any tech-only permissions automatically — the Phase 8 admin UI handles permission assignment. Keep scope small.
- `app:create-superuser` artisan command (`app/Modules/Shared/Console/Commands/CreateSuperUserCommand.php`): interactive prompts for name_ar/name_en/email/password/department_id/location_id; creates user with `is_super_user=true`, `is_tech=true`; creates `tech_profiles` row; does NOT insert `permission_user` rows (SuperUser bypass covers all checks). Idempotent by email (prompts to overwrite if exists).
- Base Blade layout `resources/views/layouts/app.blade.php`:
  - `<html dir="..." lang="...">` from locale middleware (Task 1.5).
  - Collapsible sidebar with nav items gated by `@permission('<key>')` and/or `@if(auth()->user()->is_tech)`.
  - Top bar: user menu with name, profile link, logout, language switcher (posts to a locale-toggle route).
  - Uses Tailwind 4 logical-property utilities throughout. Directional icons flip in RTL.
- `SecurityHeadersMiddleware` on web group per `SPEC.md §3.7`: `Content-Security-Policy`, `X-Content-Type-Options: nosniff`, `X-Frame-Options: SAMEORIGIN`, `Strict-Transport-Security` (prod only), `Referrer-Policy: strict-origin-when-cross-origin`, `Permissions-Policy` (camera/mic/geolocation off).
- Locale toggle route `POST /locale/{ar|en}` authenticated, CSRF-protected, updates `users.locale`, redirects back.

## Do NOT
- Do not grant tech permissions from the promotion flow (admin assigns them in Phase 8).
- Do not expose the create-superuser command via HTTP or seeders — CLI only.
- Do not rely on frontend hiding for sidebar items — permission checks must be server-rendered via `@permission`.
- Do not add dashboards, ticket links, or phase-2+ routes to the sidebar (just placeholder links + profile + logout for now).
- Do not send password via CLI flag argument (security) — always prompt interactively with hidden input.

## Acceptance
- Pest feature tests in `tests/Feature/`:
  - User with `user.promote` can promote an employee; `is_tech=true`, `tech_profiles` row exists with correct `promoted_by`/`promoted_at`.
  - User without `user.promote` gets 403.
  - Promoting an already-tech user fails with a localized validation message.
  - `app:create-superuser` via `Artisan::call()` with input creates user with `is_super_user=true, is_tech=true` and `tech_profiles` row.
  - Running the command twice with same email prompts overwrite and does not duplicate rows.
  - Locale toggle route updates `users.locale` and redirects; unauthenticated → redirect to login.
  - Security headers present on every web response (assert header list per §3.7).
  - Base layout renders sidebar item gated by `@permission('ticket.view-all')` only when the user has that permission.
- `docker compose exec app php artisan app:create-superuser` succeeds end-to-end manually.
- Phase 1 Acceptance Gate checklist in `docs/phases/phase-1.md` fully green.

## References
- `SPEC.md §6.7` — tech promotion flow
- `SPEC.md §6.8` — base layout
- `SPEC.md §6.9` — SuperUser semantics
- `SPEC.md §3.7` — security headers
- `CLAUDE.md` — Commands (`app:create-superuser`), Security (headers)
