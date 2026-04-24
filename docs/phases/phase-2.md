# Phase 2 — Core Ticketing

**Spec reference:** [SPEC.md §7](../../SPEC.md#7-phase-2--core-ticketing)
**Deliverable:** Ticket creation, status lifecycle, group/tech assignment, peer transfers, file uploads. The core product loop.
**Exit condition:** All items in [§7.7 Phase 2 Acceptance Criteria](../../SPEC.md#77-phase-2-acceptance-criteria) pass.

## Tasks

- [x] **Task 2.1** — `tickets` + `ticket_counters` migrations/models/factories; `TicketNumberGenerator` service using `DB::transaction()` + `lockForUpdate()` returning zero-padded `TKT-XXXXXXX`; unit tests including concurrent-increment race test.
- [x] **Task 2.2** — `categories` (with `group_id`, `version`), `subcategories` (with `is_required`, `version`), `groups`, `group_user` pivot migrations/models/factories + schema tests.
- [x] **Task 2.3** — `transfer_requests` (enum `pending/accepted/rejected/revoked`, `(ticket_id, status)` index) and `ticket_attachments` migrations/models/factories + schema tests.
- [x] **Task 2.4** — `TicketStateMachine` service enforcing every row of §7.4 transition table; `TicketStatusChanged` event fired on each transition; invalid transitions throw `InvalidStatusTransition`; unit tests cover all valid paths AND representative invalid transitions.
- [x] **Task 2.5** — Employee ticket creation Livewire: rich text sanitized server-side, category→group auto-assignment, `TicketNumberGenerator` hookup, status `awaiting_assignment`, requester auto-filled; ticket create rate limit 10/hr; feature tests including enumeration check (ULID-only routes, `display_number` rejected).
- [x] **Task 2.6** — File upload pipeline: MIME via magic bytes, 10MB size + 5-file-count server enforced, EXIF strip, 2048px resize, 80% JPEG compress, ULID-based path outside web root, originals discarded; authorized `FilesController` download route; upload rate limit 20/hr; feature tests.
- [x] **Task 2.7** — Assignment flows: tech self-assign (any group), Group Manager within-group assign (role-based server-side), `permission:ticket.assign` holder full override; Livewire components + policies + feature tests (including Group Manager denial for out-of-group).
- [x] **Task 2.8** — Peer transfer Livewire: request/accept/reject/revoke; app-level enforcement of one `pending` per ticket; transfer rows never deleted; feature tests covering each path + contention.
- [x] **Task 2.9** — Ticket close via `permission:ticket.close` with mandatory close reason (§7.6 hardcoded list + free-text when "Other"); requester self-cancellation (any state); employee visibility global scope (`requester_id = auth()->id()` on employee side); feature tests.

## Session Groupings

| Session | Tasks | Rationale |
|---------|-------|-----------|
| S1 | 2.1 | Display-number generator is a critical invariant (§7.2 security) and deserves focused, well-tested session. |
| S2 | 2.2, 2.3 | Remaining schema bundle — related migrations + models + factories share setup. |
| S3 | 2.4 | State machine is the hardest invariant (§7.4) — alone, full session for transition coverage. |
| S4 | 2.5, 2.6 | Ticket creation and file pipeline are tightly coupled (creation uses pipeline). |
| S5 | 2.7, 2.8 | Assignment + peer transfers share event wiring and ticket status transitions. |
| S6 | 2.9 | Close/cancel/scope polish closes the lifecycle and gates employee visibility. |

## Acceptance Gate (from SPEC §7.7)

- [x] Employee can create a ticket with all fields, including file attachments
- [x] Ticket gets correct display number and auto-assigns to correct group
- [x] Tech can self-assign, work, put on hold, resume, and resolve (with resolution form)
- [x] Peer transfer flow works: request, accept/reject, revoke
- [x] Manager override works with proper permission check
- [x] Group Manager can assign within their group only (server-side enforcement)
- [x] Status transitions enforced by state machine — invalid transitions rejected
- [x] All ticket data accessible only via ULID routes, never display number
- [x] File uploads validated, processed, and stored securely
- [x] Employee can only see their own tickets (global scope enforcement)
- [x] All strings localized (AR/EN), layout correct in both directions
