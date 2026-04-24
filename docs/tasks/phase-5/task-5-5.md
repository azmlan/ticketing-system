# Task 5.5 — SLA UI: Admin Config & Ticket Detail Indicators

## Context
The SLA engine runs in the background. This task surfaces it to users: an admin config panel to edit SLA targets and business hours, and green/yellow/red SLA badges on the ticket detail view and tech dashboard queue.

## Task
Build the `SlaSettings` Livewire admin component and inject SLA status badges into the ticket detail and tech dashboard ticket lists.

## Requirements
- Livewire component `app/Modules/SLA/Livewire/SlaSettings.php` + Blade view. Requires `system.manage-sla` permission; `abort(403)` otherwise.
- Editable fields:
  - Per-priority targets (4 rows): `response_target_minutes`, `resolution_target_minutes`, `use_24x7` toggle.
  - Business hours: `business_hours_start` (time), `business_hours_end` (time), `working_days` (multi-checkbox: sun–sat).
  - Warning threshold: integer 1–99 (maps to `sla_warning_threshold` in `app_settings`).
- On save: update `sla_policies` rows (upsert by priority), update `app_settings` keys. Fire `sla_policy_updated` audit event per §15.4 audit log. Flash localized success message.
- SLA badge component (Blade partial or anonymous component): accepts `$ticketSla`, renders a colored pill — green (`on_track`), yellow (`warning`), red (`breached`). Shows worse of `response_status` / `resolution_status`. Tooltip shows elapsed vs target in minutes (or hours if > 60). Hidden if `ticket_sla` row doesn't exist.
- Integrate badge into ticket detail view (SLA module injects via a Blade `@section` or Livewire slot — do not modify the Tickets module Blade directly, use a view composer or slot).
- Integrate badge into the tech dashboard ticket list (Phase 6 will build the dashboard; place a `@yield('sla-badge')` placeholder now if the dashboard view doesn't exist yet, or inject via the same mechanism).
- All labels via `__()` in `resources/lang/{ar,en}/sla.php`. RTL-safe — use logical CSS properties.

## Do NOT
- Do not directly import Ticket or TicketSla models from other modules in Blade views — pass data via view composers or Livewire properties.
- Do not hardcode working-day names — use translation keys.
- Do not skip audit logging on SLA policy updates.
- Do not add chart libraries — SLA badge is text/color only (Phase 6 charts are deferred to V2 per CLAUDE.md).

## Acceptance
- Pest feature `tests/Feature/Phase5/SlaSettingsTest.php`:
  - User without `system.manage-sla` gets 403 on the settings route.
  - Saving new targets updates `sla_policies` rows and `app_settings` keys.
  - Saving fires `sla_policy_updated` audit event.
  - Invalid threshold (e.g., 0 or 100) returns validation error.
- Browser/manual: badge shows green on `on_track`, yellow on `warning`, red on `breached`. Localized strings render in AR and EN. Component renders correctly in RTL layout.

## References
- `SPEC.md §10.3` — business hours config, warning threshold
- `SPEC.md §10.6` — Phase 5 acceptance criteria
- `SPEC.md §13.4` — `app_settings` key list
- `SPEC.md §15.4` — audit event `sla_policy_updated`
- `SPEC.md §4.2` — localization requirement
