# Task 1.5 — Profile Edit + Locale Middleware + AR/EN Scaffolds

## Context
Auth flows from Task 1.4 are in place. Users now need a way to edit their details and toggle locale; the whole app needs the middleware that applies locale + `dir`/`lang` attributes on every request.

## Task
Build the profile-edit Livewire component, the `SetLocaleMiddleware`, and the AR/EN translation file scaffold organized by module.

## Requirements
- Livewire `Profile` component in `app/Modules/Auth/Livewire/` (or `Shared/` — choose the module that owns the user model's UI surface) covering all registration fields from Task 1.4: `name_ar`, `name_en`, `email`, `mobile`, `department_id`, `location_id`, plus `locale` toggle and an optional change-password block. Email updates require current-password confirmation.
- `SetLocaleMiddleware` registered on the web group: reads authenticated user's `users.locale`; falls back to session, then `config('app.locale')` (`ar`); calls `App::setLocale(...)`; exposes `dir` (`rtl`/`ltr`) and `lang` via shared view data or a view composer so the base layout applies `<html dir="..." lang="...">`.
- Translation file scaffold under `resources/lang/{ar,en}/` organized by module domain: `auth.php`, `validation.php`, `profile.php`, `common.php` minimum. Both locales must have the same key set — a test asserts parity.
- Logical-property CSS verification: ensure profile layout uses `ms-*` / `me-*` / `ps-*` / `pe-*` / `start-*` / `end-*` exclusively per CLAUDE.md. Tailwind 4 config enables logical-property utilities.
- Directional icons (arrows/chevrons) flip in RTL (use `rotate-180` under `[dir="rtl"]` or Tailwind logical variants).
- Dropdowns for `department_id` / `location_id` populated with `is_active = true AND deleted_at IS NULL` rows from Task 1.2; show `name_ar` when locale is `ar`, else `name_en`.
- User-facing strings use `__()` / `@lang` — no hardcoded strings in the component or views.

## Do NOT
- Do not build the permission middleware / `@permission` directive here — that's Task 1.6.
- Do not wire the base layout's sidebar / user menu yet — that's Task 1.7 (profile component must still render standalone).
- Do not accept `is_tech` / `is_super_user` edits from this form — those are admin-only paths.
- Do not use physical-direction classes (`ml-*`, `mr-*`, `left-*`, `right-*`) for directional spacing — logical properties only.
- Do not store locale anywhere other than `users.locale` (no separate session key, no cookie-only persistence).

## Acceptance
- Pest feature tests in `tests/Feature/Profile/`:
  - Authenticated user can update `name_ar`, `name_en`, `mobile`, `department_id`, `location_id`; DB row reflects changes.
  - Toggling locale `ar → en` persists to `users.locale`; next request returns `<html dir="ltr" lang="en">`.
  - Anonymous request to `/profile` redirects to login.
  - Email update without current-password confirmation is rejected.
  - Attempting to edit another user's profile is denied.
  - Translation parity test: `array_keys(__('auth', locale: 'ar'))` equals `array_keys(__('auth', locale: 'en'))` — same for every scaffolded file.
  - Middleware test: request with user whose `locale=ar` hits a test route → response has `dir="rtl"` in rendered view.
- No strings in `app/Modules/**/*.php` or Blade views match `[A-Z][a-z]+` literal (enforce via a grep-based test or convention check).

## References
- `SPEC.md §4` — localization strategy
- `SPEC.md §6.2` — user profile fields
- `CLAUDE.md` — Localization & RTL (logical properties rule)
