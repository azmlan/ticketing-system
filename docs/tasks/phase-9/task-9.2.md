# Task 9.2 — Resolution Capture Modal (New Resolution Path)

## Context
When a tech transitions a ticket from `in_progress → resolved`, they must fill a resolution form before the state transition completes. This task wires the modal to the state machine atomically.

## Task
Build the Livewire resolution capture modal that intercepts the resolve action and atomically creates a `Resolution` row alongside the `TicketStateMachine` transition.

## Requirements
- Livewire component `Precedent/ResolveModal` rendered inside the ticket detail view (Phase 2/3 surface)
- Modal opens when tech clicks "Resolve" — replaces the immediate transition call
- Fields per §14.2: `summary` (required), `root_cause` (optional), `steps_taken` rich text (required, server-side sanitized via whitelist purifier per §3.2), `parts_resources` (optional), `time_spent_minutes` integer optional), `resolution_type` dropdown (required, 4 options per §14.1 enum)
- Modal cannot be dismissed while in progress — must fill all required fields or cancel
- On submit: wrap DB transaction — `TicketStateMachine::transition($ticket, 'resolved')` + `Resolution::create([...])` in one atomic unit; if either fails, both roll back
- On cancel: modal closes, ticket remains `in_progress`, no `Resolution` row created
- `steps_taken` rendered as sanitized HTML in any future display — never raw
- All labels and options through `__()` (AR + EN translation keys)
- Gate: only the assigned tech (or `ticket.resolve` permission holder) may submit

## Do NOT
- Bypass `TicketStateMachine` for the status transition
- Allow modal close without explicit cancel action
- Accept `display_number` as any identifier
- Skip server-side sanitization on `steps_taken`

## Acceptance
- Feature tests:
  - Clicking resolve opens modal; ticket status unchanged until form submitted
  - Submitting valid form creates `Resolution` row and sets ticket status to `resolved`
  - Submitting with missing required fields (`summary`, `steps_taken`, `resolution_type`) returns validation errors and does not transition status
  - Transaction rollback: if `TicketStateMachine::transition` would throw (wrong state), no `Resolution` row is created
  - Non-assigned tech / no permission → 403
  - `steps_taken` stored sanitized (script tags stripped)

## Reference
SPEC.md §14.2, §7.4, §3.2
