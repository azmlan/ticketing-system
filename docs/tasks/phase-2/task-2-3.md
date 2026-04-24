# Task 2.3 — TicketStateMachine

## Context
The state machine is a non-negotiable invariant (CLAUDE.md). All status changes must flow through it — no direct column updates anywhere in the codebase.

## Task
Implement `TicketStateMachine` in the Tickets module, enforce all valid transitions per §7.4, throw on invalid, and fire `TicketStatusChanged` on every successful transition.

## Requirements
- `app/Modules/Tickets/Services/TicketStateMachine.php` — single public method `transition(Ticket $ticket, string $toStatus, User $actor): void`
- Transition table encoded as a const map (from → allowed tos); invalid transition throws `InvalidTicketTransitionException` (custom exception, HTTP 422).
- Valid transitions per §7.4 (reproduce the full table in the map — do not infer):
  - `awaiting_assignment` → `in_progress`
  - `in_progress` → `on_hold`, `awaiting_approval`, `resolved`
  - `on_hold` → `in_progress`
  - `awaiting_approval` → `action_required`, `in_progress`
  - `action_required` → `awaiting_final_approval`
  - `awaiting_final_approval` → `resolved`, `action_required`
  - Any → `closed` (requires `permission:ticket.close`)
  - Any → `cancelled` (requester only, enforced inside state machine)
- After a valid transition: update `tickets.status` (and `resolved_at`, `closed_at`, `cancelled_at` timestamps if applicable) then fire `TicketStatusChanged` event with `$ticket`, `$fromStatus`, `$toStatus`, `$actor`.
- `TicketStatusChanged` event in `app/Modules/Tickets/Events/`. Listeners registered in Tickets module service provider (listeners are stubs for now — other modules wire theirs in later phases).
- Actor authorization checked inside state machine for `closed` and `cancelled` transitions — not delegated to the caller.

## Do NOT
- Do not allow any other code path to write `tickets.status` directly — if you touch a controller or Livewire component that might, add a comment flagging it as forbidden.
- Do not implement listener logic for SLA, Communication, Audit modules here (stubs only).
- Do not expose `transition()` via a route — it is a service called by other services/components.

## Acceptance
- Pest unit tests in `tests/Unit/Services/TicketStateMachineTest.php`:
  - All valid transitions succeed and fire `TicketStatusChanged` event (use `Event::fake()`).
  - Every invalid transition throws `InvalidTicketTransitionException`.
  - `closed` transition fails for user without `ticket.close` permission.
  - `cancelled` transition fails for non-requester.
  - Timestamps (`resolved_at`, etc.) set correctly on terminal transitions.
- `grep -r "->status\s*=" app/` (excluding state machine file) returns zero results.

## References
- `SPEC.md §7.4` — full transition table and event requirement
- `CLAUDE.md` — Non-Negotiable Invariant #1
