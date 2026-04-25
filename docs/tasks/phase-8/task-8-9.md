# Task 8.9 — Tenant Branding & App Settings

## Context
`app_settings` was migrated in task 8.1 with default keys seeded. Phase 8 exposes a branding/settings page for the IT Manager to configure company identity and session behavior, and applies these values to the layout.

## Task
Build the tenant branding settings Livewire component and wire `app_settings` values into the layout (header, emails).

## Requirements
- `app/Modules/Admin/Livewire/Settings/BrandingSettings.php`: form for `company_name` (text), logo upload, `primary_color` (hex, validated), `secondary_color` (hex, validated), `session_timeout_hours` (integer 1–24). Saves via `AppSetting::set()`. Permission: IT Manager role check only (no specific permission string — see §13.1). (§13.1, §13.4)
- Logo upload: MIME validated via magic bytes (not extension), max 2MB, stored outside web root with ULID filename, served via authorized route `/admin/logo`; resize to max 256px edge, compress 80% JPEG, discard original. (§3.4)
- Admin layout from task 8.2: header reads `AppSetting::get('company_name')` and logo URL from `AppSetting::get('logo_path')`; Tailwind CSS vars injected inline for `primary_color` and `secondary_color` as `--color-primary` / `--color-secondary`. (§13.4)
- Email layout (shared mail template): uses `company_name` and logo from `AppSetting` so all notification emails reflect tenant branding. (§13.4)
- `session_timeout_hours` value applied at middleware level — read from `AppSetting` in `SessionTimeoutMiddleware` that extends session lifetime on each request. (§13.4)
- All strings through `__()`. (§4.2)

## Do NOT
- Do not allow non-IT-Manager users to access this page — return 403.
- Do not serve uploaded logo files directly from web root.
- Do not store EXIF data or original upload file.

## Acceptance
- Pest `tests/Feature/Admin/BrandingSettingsTest.php`: saving updates `app_settings` rows; non-IT-Manager gets 403; invalid hex color rejected; `session_timeout_hours` outside 1–24 rejected.
- Pest `tests/Feature/Admin/LogoUploadTest.php`: valid image stores outside web root with ULID name; malicious file (wrong MIME) rejected; file larger than 2MB rejected; logo accessible via authorized route and returns 403 for guests.
- Company name appears in admin layout header after saving.
- Email notification rendered in tests uses company name from `app_settings`.

## References
- `SPEC.md §13.1` — tenant branding, IT Manager only
- `SPEC.md §13.4` — `app_settings` keys and defaults
- `SPEC.md §3.4` — file upload security requirements
- `SPEC.md §9.3` — email notification layout
