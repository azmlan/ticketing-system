# Task 2.7 — Ticket List, Detail, Close & Cancel

## Context
All services exist. This task delivers the two primary read views (list and detail) and the terminal ticket actions (close with reason, employee self-cancel), completing the Phase 2 acceptance gate.

## Task
Build `TicketList` and `TicketDetail` Livewire components, the close flow with the hardcoded reason dropdown, and the requester self-cancel action.

## Requirements
- **`TicketList` component** (`app/Modules/Tickets/Livewire/TicketList.php`):
  - Route: `GET /tickets` (auth middleware).
  - Employees: see only their own tickets (enforced via `EmployeeTicketScope`, not frontend filter).
  - Techs/managers: see all tickets (scope bypassed by role).
  - Columns: display number (display only), subject, status (localized label), category, assigned tech, created_at.
  - Pagination (15/page). No search in Phase 2 (deferred per §11.4 interface requirement).
- **`TicketDetail` component** (`app/Modules/Tickets/Livewire/TicketDetail.php`):
  - Route: `GET /tickets/{ulid}` — ULID only, never display_number. 404 on not found, 403 if employee accessing another user's ticket.
  - Displays: all ticket fields, attachments (download links via serve route from Task 2.5), current status, assigned tech, transfer request state.
  - Shows contextual action buttons based on role and current status (self-assign, put on hold, resume, close, cancel, request transfer).
- **Close flow:**
  - Requires `permission:ticket.close`.
  - Livewire action presents dropdown of 7 hardcoded close reasons (§7.6); "Other" reveals a free-text field (required).
  - Calls `TicketStateMachine::transition(→ closed)` with `close_reason` + `close_reason_text` persisted on ticket.
  - `closed_at` set by state machine.
- **Cancel flow:**
  - Requester only. No confirmation modal required — single confirmation wire:confirm is enough.
  - Calls `TicketStateMachine::transition(→ cancelled)`.
  - `cancelled_at` set by state machine.
- All status labels, close reasons, and button labels via `__()`. AR/EN keys in `tickets.php`.
- CSS logical properties only (`ms-*`, `me-*`, `ps-*`, `pe-*`) — no directional utility classes.

## Do NOT
- Do not accept `display_number` as a route parameter anywhere.
- Do not show action buttons the user is not authorized to use (hide them — but authorization is also enforced server-side in the service layer).
- Do not build resolution form here (Phase 3 — Escalation module owns that flow).

## Acceptance
- Pest feature tests `tests/Feature/Phase2/TicketListTest.php` and `TicketDetailTest.php`:
  - Employee list only contains their own tickets; accessing another's ULID returns 403.
  - Tech list shows all tickets.
  - Detail page loads with status in correct locale.
  - Close with reason persists `close_reason` and `closed_at`; closing without reason returns validation error.
  - "Other" reason without text returns validation error.
  - Non-authorized user cannot POST close action (403).
  - Cancel by requester sets status=`cancelled` and `cancelled_at`.
  - Cancel by non-requester returns 403.
  - `GET /tickets/TKT-0000001` (display number as param) returns 404 or 400 — never resolves a ticket.

## References
- `SPEC.md §7.3` — ticket creation flow (requester context)
- `SPEC.md §7.4` — close and cancel transitions
- `SPEC.md §7.5` — assignment display in detail view
- `SPEC.md §7.6` — close reason dropdown values
- `SPEC.md §2.3` — ULID route invariant (security)
- `SPEC.md §4.2` — localization and RTL
