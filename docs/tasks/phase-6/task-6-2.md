# Task 6.2 — Employee Dashboard Livewire Component

## Context
Task 6.1 delivered `SearchServiceInterface`. The employee dashboard is the simplest consumer — own tickets only, subject search, status filter.

## Task
Build the employee dashboard Livewire component with ticket list, status filter, subject search, count badges, and quick-submit button.

## Requirements
- Livewire component `EmployeeDashboard` in `app/Modules/Tickets/Livewire/`; route under `tickets.dashboard.employee`.
- List shows authenticated user's own tickets only — Eloquent global scope enforced, not a manual `where('requester_id')` bypass per §2.3.
- Status filter: all / open / resolved / closed / cancelled (multi-select or tabs); default: all.
- Search: subject text only via `SearchServiceInterface`, passing `requester_id` filter.
- Count summary badges: open, resolved, closed, cancelled — counts reflect current filter state or totals.
- Quick-submit button links to `tickets.create`.
- SLA indicator badge on each ticket row (reuse Phase 5 component).
- Gate: `auth()->check()` — any authenticated user can reach own dashboard; no special permission needed.
- Fully bilingual per §4.2; RTL logical properties only.

## Do NOT
- Do not show tickets from other requesters under any filter combination.
- Do not call FULLTEXT directly — always via `SearchServiceInterface`.
- Do not add pagination here (Task 6.6 adds it across all dashboards).
- Do not add tech-only fields (assigned-to, group) to this view.

## Acceptance
- Pest `tests/Feature/Phase6/EmployeeDashboardTest.php`:
  - Requester sees only own tickets; another user's ticket never appears.
  - Status filter returns correct subset.
  - Search on subject returns match; search on description returns nothing (subject-only).
  - Count badges match DB state.
  - Unauthenticated request redirects to login.
- View renders without errors in AR locale with RTL dir.

## References
- `SPEC.md §11.1` — employee dashboard spec
- `SPEC.md §11.4` — search interface usage
- `SPEC.md §2.3` — global scopes
- `SPEC.md §4.2` — localization requirements
