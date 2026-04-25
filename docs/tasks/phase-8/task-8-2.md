# Task 8.2 — Admin Module Scaffold: Layout, Navigation & Service Provider

## Context
The Admin module has stub files only. Before any admin CRUD can be built, it needs a service provider, routes, layout, and permission-guarded navigation.

## Task
Wire up the Admin module service provider, admin layout Blade component, sidebar navigation, and permission middleware guards for all admin sections.

## Requirements
- `AdminServiceProvider`: register module routes from `app/Modules/Admin/Routes/web.php`; register translations from `app/Modules/Admin/Lang/`; add to `bootstrap/providers.php`. (§2.1)
- Route group: prefix `/admin`, middleware `auth` + `verified`, named prefix `admin.`. Each sub-group gated by its permission (use `can:` middleware per §13.1 table). (§3.1, §13.1)
- Admin layout: `resources/views/components/layouts/admin.blade.php` — sidebar nav, `<html dir>` + `<html lang>` from middleware, branding slot (company name + logo from `AppSetting`), user menu, RTL-correct with logical CSS properties. (§4.1, §4.2)
- Sidebar nav entries: Categories, Groups, Custom Fields, SLA Targets, Tags, Response Templates, Departments, Locations, Users, Notification Settings, Branding & Settings. Each entry only rendered when the authenticated user has the corresponding permission. (§13.1)
- Translation keys in `resources/lang/{ar,en}/admin.php` for all nav labels and section titles — no hardcoded strings. (§4.2)
- 403 blade view for permission denied redirects within admin.

## Do NOT
- Do not build any CRUD components in this task.
- Do not add real-time features or WebSocket integration.
- Do not render unsanitized content in the layout.

## Acceptance
- Visiting `/admin` while unauthenticated redirects to login.
- Pest `tests/Feature/Admin/AdminAccessTest.php`: users without any admin permission see a 403 on all `/admin/*` routes; IT Manager sees the full nav; scoped-permission users see only their sections.
- Nav labels render in AR and EN and switch on locale change.
- Layout uses only `ms-*`, `me-*`, `ps-*`, `pe-*` Tailwind classes — no `ml-*`/`mr-*`.

## References
- `SPEC.md §2.1` — module layout and service provider pattern
- `SPEC.md §3.1` — middleware stack
- `SPEC.md §4.1, §4.2` — RTL layout and localization requirements
- `SPEC.md §13.1` — admin sections and their permissions
