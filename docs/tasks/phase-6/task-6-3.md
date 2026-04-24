# Task 6.3 — Tech Dashboard Livewire Component

## Context
Task 6.2 delivered the employee dashboard. The tech dashboard is more complex: two ticket lists (queue + mine), SLA-sorted, transfer panel, and quick stats.

## Task
Build the tech dashboard Livewire component with queue, my-tickets, SLA sorting, pending transfer panel, and quick stats.

## Requirements
- Livewire component `TechDashboard` in `app/Modules/Tickets/Livewire/`; route under `tickets.dashboard.tech`.
- Gate: `permission:ticket.view-assigned` — tech role only.
- **Ticket queue:** Unassigned tickets in groups the authenticated tech belongs to; sorted by priority then date.
- **My tickets:** Assigned to authenticated tech; sorted SLA urgency first (breached → warning → on_track), then priority, then created_at per §11.2.
- **SLA indicator badges** on every row (reuse Phase 5 badge component).
- **Pending transfer requests panel:** Incoming transfer requests where tech is the target; accept/decline actions fire existing Transfer events.
- **Quick stats:** My open tickets count, resolved this week, resolved this month, my SLA compliance rate (% of closed tickets where `resolution_status = on_track`).
- Group filter persisted per user (server-side) — implement state now; persistence mechanism delivered in Task 6.4.
- Fully bilingual; RTL logical properties only.

## Do NOT
- Do not show tickets outside the tech's groups in the queue.
- Do not implement the full filter bar here (Task 6.4).
- Do not add pagination (Task 6.6).
- Do not import Ticket model from outside Tickets module.

## Acceptance
- Pest `tests/Feature/Phase6/TechDashboardTest.php`:
  - Queue contains only unassigned tickets from tech's groups.
  - My tickets sorted: breached before warning before on_track.
  - Pending transfer panel shows requests targeting this tech only.
  - Quick stats counts match DB state.
  - User without `ticket.view-assigned` gets 403.
- AR locale renders without layout breakage (spot-check RTL).

## References
- `SPEC.md §11.2` — tech dashboard spec
- `SPEC.md §11.5` — sort options
- `SPEC.md §10` — SLA status values
- `SPEC.md §4.2` — localization
