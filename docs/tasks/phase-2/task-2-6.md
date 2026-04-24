# Task 2.6 — Assignment & Peer Transfer

## Context
A ticket starts as `awaiting_assignment`. This task implements all paths that move it to an individual tech: self-assign, group manager assign, IT manager/`ticket.assign` direct assign, and the peer transfer request lifecycle.

## Task
Build the assignment and peer transfer services plus Livewire actions, all flowing through `TicketStateMachine` and enforcing the exact authorization rules from §7.5.

## Requirements
- `app/Modules/Assignment/Services/AssignmentService.php` — three methods:
  - `selfAssign(Ticket $ticket, User $tech)`: tech grabs unassigned ticket from any group; sets `assigned_to`, calls `TicketStateMachine::transition(→ in_progress)`. Tech must have `tech` role.
  - `managerAssign(Ticket $ticket, User $manager, User $tech)`: group manager assigns within their own group (`$manager->managedGroup->id === $ticket->group_id`); IT manager or `ticket.assign` permission can assign across any group. Sets `assigned_to` and transitions. No acceptance step.
  - `reassign(Ticket $ticket, User $actor, User $newTech)`: requires `ticket.assign` permission or IT Manager role; direct reassign, no transfer workflow; fires `TicketStatusChanged` via state machine.
- `app/Modules/Assignment/Services/TransferService.php`:
  - `request(Ticket $ticket, User $fromTech, User $toTech)`: creates `TransferRequest` (status=`pending`); rejects if a pending request already exists for this ticket.
  - `accept(TransferRequest $tr, User $actor)`: actor must be `to_user_id`; updates request to `accepted`, reassigns ticket `assigned_to`, sets `responded_at`.
  - `reject(TransferRequest $tr, User $actor)`: actor must be `to_user_id`; sets status=`rejected`.
  - `revoke(TransferRequest $tr, User $actor)`: actor must be `from_user_id`; sets status=`revoked`. Only allowed while status=`pending`.
  - Transfer records are never deleted.
- Livewire actions wired in `TicketDetail` component (stub component from Task 2.7 is fine — use `dispatch` or direct call pattern).
- All inter-module calls from Assignment to Tickets use `TicketStateMachine` — no direct `$ticket->status =`.
- Authorization: every service method throws `AuthorizationException` on mismatch — not silently ignored.
- All user-facing strings (flash messages, errors) via `__()` with keys in `resources/lang/{ar,en}/tickets.php`.

## Do NOT
- Do not give the `request()` action any blocking effect on Tech A's work — they continue on the ticket while transfer is pending.
- Do not import `Ticket` model from Tickets module into Assignment module — receive as injected parameter typed against a contract or pass ULID and resolve inside Tickets module. (Boundary: Assignment calls `TicketStateMachine` which lives in Tickets; cross-module via service call is acceptable here — document it.)
- Do not build admin reassign UI — only the service logic and Livewire hooks.

## Acceptance
- Pest feature tests `tests/Feature/Phase2/AssignmentTest.php` and `TransferTest.php`:
  - Self-assign transitions ticket to `in_progress`; non-tech cannot self-assign (403).
  - Group manager cannot assign to tech outside their group (403); IT manager can.
  - Second pending transfer request for same ticket rejected.
  - Accept → ticket `assigned_to` updated, `TransferRequest.status = accepted`.
  - Reject → `assigned_to` unchanged.
  - Revoke fails if status is not `pending`.
  - `TicketStatusChanged` event fired on all successful assignment transitions (`Event::fake()`).

## References
- `SPEC.md §7.5` — full assignment and transfer logic
- `SPEC.md §7.4` — state transitions triggered by assignment
