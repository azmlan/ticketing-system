# Task 7.2 — CSAT: Record Creation on Resolution + Resolution Email

## Context
When a ticket reaches `resolved` status, a `csat_ratings` row must be created and the requester notified via email. This is purely event-driven — no direct coupling to `TicketStateMachine`.

## Task
Create a `TicketResolved` event listener that creates the CSAT record and dispatches a resolution notification email to the requester.

## Requirements
- Listen to the existing `TicketStatusChanged` event (or create `TicketResolved` if not yet present); fire only when new status is `resolved`.
- Create `csat_ratings` row: `status = 'pending'`, `expires_at = now() + 7 days`, `tech_id` = assigned tech at resolution time. Skip if row already exists (idempotent).
- Send `TicketResolvedMail` (Mailable) to the requester — includes ticket `display_number`, subject, assigned tech name, and a link to the ticket detail page. Per §12.1: this is a notification, NOT a survey link.
- Mail uses the requester's locale (`users.locale`) for translation via `__()`.
- Queue the mail via Horizon — do not send synchronously.
- Both `ar` and `en` translation keys added under `resources/lang/{ar,en}/csat.php`.

## Do NOT
- Do not bypass `TicketStateMachine` to set ticket status.
- Do not include a rating link or survey URL in the email.
- Do not create a CSAT record for tickets closed as `duplicate` or `cancelled` — resolved only.
- Do not send email synchronously.

## Acceptance
- Pest `tests/Feature/CSAT/CsatRecordCreationTest.php`: resolving a ticket creates exactly one `csat_ratings` row with correct `tech_id`, `expires_at` ≈ 7 days, `status = pending`; second resolution event does not create a duplicate.
- Pest `tests/Feature/CSAT/TicketResolvedMailTest.php`: mail queued, contains `display_number` and tech name, rendered in correct locale, no survey URL present.
- `Queue::assertPushed(SendQueuedMailable::class)` passes after resolution.

## References
- `SPEC.md §12.1` — CSAT flow step 1–2
- `SPEC.md §4.2` — localization requirements
- `SPEC.md §7.4` — state machine (do not bypass)
