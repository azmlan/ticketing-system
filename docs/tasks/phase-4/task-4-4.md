# Task 4.4 — Notification Engine

## Context
Comments and their events exist. This task builds the pluggable notification engine in the Communication module: event listeners, queued email jobs, locale-aware templates, retry logic, and `notification_logs` writes.

## Task
Build `NotificationService`, all event listeners, queued notification jobs, Blade email views, and wire up every trigger in the notification matrix from §9.3.

## Requirements
- `NotificationService` in `app/Modules/Communication/Services/NotificationService.php`: accepts a trigger type, resolves recipients, dispatches a queued job. Does not send email directly — delegates to a queued `SendNotificationJob`.
- `SendNotificationJob`: queued on the `notifications` Horizon queue. On execution: render email in recipient's locale (`app()->setLocale($recipient->locale)`), send via Laravel `Mail`, write a `notification_logs` record with `status = sent` and `sent_at`. On failure: update log to `status = failed`, store `failure_reason`. Configure 3 max attempts with exponential backoff (`backoff: [60, 300, 900]`) per §9.3.
- Write `notification_logs` with `status = queued` when the job is dispatched, update to `sent` or `failed` on job resolution.
- Event listeners wired in `CommunicationServiceProvider::boot()`:
  - `TicketStatusChanged` → resolve trigger from new status (ticket_created maps to `open`, ticket_assigned, ticket_resolved, ticket_closed, action_required, form_rejected — derive from transition map §7.4).
  - `CommentCreated` — no notification in Phase 4 (notifications for comments are out of scope; listener stub only).
  - `TransferRequestCreated` → `transfer_request` trigger, recipient = target tech.
- Recipient resolution per §9.3 matrix: requester for `ticket_created`/`action_required`/`form_rejected`/`ticket_closed`; assigned tech for `ticket_assigned`/`ticket_resolved`; both tech and requester for `ticket_resolved`; approvers with `escalation.approve` for `escalation_submitted`; SLA triggers deferred to Phase 5; CSAT reminder deferred to Phase 6.
- Blade email views in `resources/views/emails/notifications/{trigger_key}.blade.php` (AR and EN variants via locale, not separate files — use `__()` in the view). Minimal layout: subject line, greeting, ticket display_number + subject, body paragraph, link to ticket. Never include internal comment content.
- All email subject lines and body copy via `resources/lang/{ar,en}/notifications.php`.

## Do NOT
- Do not send emails synchronously — all notifications must be queued.
- Do not include `is_internal = true` comment content in any notification email.
- Do not import Ticket or other module models into Communication listeners — receive data from the event payload only.
- Do not implement SLA warning/breach notifications here — those are Phase 5.
- Do not implement CSAT reminder here — that is Phase 6.
- Do not hardcode recipient logic in the job — resolve recipients in `NotificationService` before dispatching.

## Acceptance
- Pest feature tests `tests/Feature/Phase4/NotificationEngineTest.php` (use `Queue::fake()` and `Mail::fake()`):
  - `TicketStatusChanged` with `open` transition → `SendNotificationJob` dispatched for requester.
  - `TicketStatusChanged` with `in_progress` (assigned) → job dispatched for assigned tech.
  - `TicketStatusChanged` with `resolved` → jobs dispatched for both tech and requester.
  - `TransferRequestCreated` → job dispatched for target tech.
  - Job failure increments `attempts`, sets `status = failed`, stores `failure_reason`.
  - Notification rendered in recipient's locale (assert translated subject line).
  - `notification_logs` record written with `status = queued` on dispatch.
  - `notification_logs` updated to `sent` after successful job execution.
  - Email body never contains internal comment content (assert with a seeded internal comment).

## References
- `SPEC.md §9.3` — notification matrix (triggers, recipients, template keys)
- `SPEC.md §9.1` — `notification_logs` schema
- `SPEC.md §4.2` — locale-aware rendering of notifications
- `SPEC.md §7.4` — ticket status transition map (for deriving trigger types from events)
