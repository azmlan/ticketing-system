# Task 6.6 — Pagination, Sort Controls & Localization Verification

## Context
Tasks 6.2–6.5 built all three dashboards without pagination. This task applies consistent pagination and sort controls across every list view and verifies RTL/AR correctness.

## Task
Add paginated list views (default 25) and sort controls to all dashboards; verify and fix any RTL or AR translation gaps.

## Requirements
- Default page size 25, configurable via `config/ticketing.php` `dashboard.per_page`; no hardcoded `25` in components.
- Pagination applied to: employee ticket list, tech queue, tech my-tickets, manager breached list, manager team workload table, manager activity feed.
- Sort controls on each list: date created (newest/oldest), priority (highest/lowest), last updated — using the same sort state already on `TechDashboard`; add to `EmployeeDashboard` and `ManagerDashboard`.
- Livewire pagination resets to page 1 on filter or sort change (`$this->resetPage()`).
- **RTL sweep:** All three dashboard Blade views must use logical CSS properties (`ms-*`, `me-*`, `ps-*`, `pe-*`, `start-*`, `end-*`) — no `ml-*`, `mr-*`, `left-*`, `right-*`.
- **Directional icons:** Chevron/arrow icons use `rtl:rotate-180` or Alpine `$store.locale` to flip in RTL.
- **AR translations:** Every string in all three dashboard views must have a key in `resources/lang/ar/tickets.php` and `en/tickets.php`.
- Pest snapshot test: render each dashboard in EN and AR locale, assert no untranslated string literal appears in output.

## Do NOT
- Do not add client-side pagination (Livewire server-side only).
- Do not add a UI control to change page size (config only).
- Do not introduce directional `margin-left`/`margin-right` Tailwind classes anywhere in dashboard views.
- Do not modify non-dashboard views in this task.

## Acceptance
- Pest `tests/Feature/Phase6/PaginationTest.php`:
  - List with 30 tickets returns 25 on page 1, 5 on page 2.
  - Changing sort resets to page 1.
  - Changing filter resets to page 1.
- Pest `tests/Feature/Phase6/LocalizationTest.php`:
  - AR render contains no English string literals outside user-generated content fields.
  - EN and AR renders both pass without Blade errors.
- Manual smoke: `dir="rtl"` layout shows chevrons flipped and spacing correct.

## References
- `SPEC.md §11.6` — pagination acceptance criterion
- `SPEC.md §4.2` — RTL and localization rules
- `SPEC.md §11.5` — sort options
