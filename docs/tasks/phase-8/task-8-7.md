# Task 8.7 — SLA Targets, Departments & Locations Admin

## Context
`sla_policies`, `departments`, and `locations` exist from Phases 1 and 5 but have no admin UI. Phase 8 exposes full management for all three, including business hours and SLA warning thresholds stored in `app_settings`.

## Task
Build Livewire admin components for SLA target configuration, and CRUD for departments and locations.

## Requirements
- `app/Modules/Admin/Livewire/SLA/SlaTargetList.php`: table of all `sla_policies` rows grouped by `priority` (low, medium, high, critical), showing response + resolution targets, `is_active` toggle. (§10.1, §13.1)
- `SlaTargetForm.php`: `priority` select, `response_time_minutes` and `resolution_time_minutes` integer inputs, `is_active`; each priority can have only one active policy — validate uniqueness server-side. Permission: `system.manage-sla`. (§10.1, §13.1)
- Business hours sub-form (on same SLA settings page): reads/writes `app_settings` keys `business_hours_start`, `business_hours_end`, `working_days` (checkbox group sun–fri, multi-select JSON array), `sla_warning_threshold` (1–99 integer). Saved via `AppSetting::set()`. (§13.4)
- `app/Modules/Admin/Livewire/Departments/DepartmentList.php` + `DepartmentForm.php`: bilingual `name_ar`/`name_en`, `sort_order` integer, `is_active` toggle; no soft-delete (use `is_active` only per Phase 1 spec). Permission: `system.manage-departments`. (§13.1, §6.1)
- `app/Modules/Admin/Livewire/Locations/LocationList.php` + `LocationForm.php`: same pattern as departments — bilingual names, `sort_order`, `is_active`. Permission: `system.manage-locations`. (§13.1, §6.1)
- All strings through `__()`. (§4.2)

## Do NOT
- Do not create duplicate SLA policies for the same priority — enforce uniqueness validation.
- Do not accept `display_number` in any route parameter.
- Do not soft-delete departments or locations — `is_active` toggle only.

## Acceptance
- Pest `tests/Feature/Admin/SlaTargetTest.php`: create policy per priority; duplicate active priority rejected; `system.manage-sla` enforced.
- Pest `tests/Feature/Admin/BusinessHoursTest.php`: saving business hours updates `app_settings` rows; `working_days` stored as JSON array; `sla_warning_threshold` out of 1–99 rejected.
- Pest `tests/Feature/Admin/DepartmentCrudTest.php`: CRUD; `system.manage-departments` enforced; `sort_order` persists.
- Pest `tests/Feature/Admin/LocationCrudTest.php`: same pattern.
- SLA engine reads updated `app_settings` business hours on next calculation (no cache invalidation required — `AppSetting::get()` reads DB).

## References
- `SPEC.md §10.1` — `sla_policies` schema and priority enum
- `SPEC.md §13.1` — SLA, departments, locations sections and permissions
- `SPEC.md §13.4` — `app_settings` keys for business hours
- `SPEC.md §6.1` — departments and locations from Phase 1 foundation
