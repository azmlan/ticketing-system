# Task 6.5 — IT Manager Dashboard Livewire Component

## Context
Tasks 6.2–6.4 delivered employee and tech dashboards. The IT Manager dashboard needs system-wide visibility, gated by a separate permission.

## Task
Build the IT Manager dashboard Livewire component with summary stats, SLA section, escalation queue, team workload, and recent activity feed.

## Requirements
- Livewire component `ManagerDashboard` in `app/Modules/Tickets/Livewire/`; route under `tickets.dashboard.manager`.
- Gate: `permission:ticket.view-all` — manager/admin role only per §11.3.
- **Summary stats:** Ticket counts by status (open, in_progress, resolved, closed, cancelled), by category, created this week, created this month, avg resolution time in hours (from `ticket_sla.resolution_elapsed_minutes`).
- **SLA section:** Compliance rate % (resolution_status = on_track / total closed), current breached count, table of breached tickets with assigned tech name and overdue duration in hours.
- **Escalation queue:** Tickets in `awaiting_approval` or `awaiting_final_approval`; link to ticket detail.
- **Unassigned count:** Tickets with no `assigned_to` across all groups.
- **Team workload:** Table of techs with open ticket count each; sorted by count desc.
- **Recent activity feed:** Latest 20 audit log entries system-wide (reuse Audit module events).
- All counts computed in the component's `mount`/`render`; no raw queries in the Blade view.
- Fully bilingual per §4.2; RTL logical properties only.

## Do NOT
- Do not add charting libraries — counts and tables only per §11.3 note.
- Do not add pagination to the stats section (data is summary rows, not paginated lists).
- Do not bypass the `permission:ticket.view-all` gate.
- Do not import models from outside the Tickets or SLA modules — use service calls.

## Acceptance
- Pest `tests/Feature/Phase6/ManagerDashboardTest.php`:
  - Summary counts match seeded DB state.
  - Breached tickets list shows only tickets with `resolution_status = breached`.
  - Escalation queue contains only `awaiting_approval` / `awaiting_final_approval` tickets.
  - Team workload counts match assignments.
  - User without `ticket.view-all` gets 403.
- AR locale renders without errors; directional icons flip.

## References
- `SPEC.md §11.3` — IT Manager dashboard full spec
- `SPEC.md §10` — SLA status values and ticket_sla schema
- `SPEC.md §7.4` — ticket status values (for escalation states)
- `SPEC.md §4.2` — localization
