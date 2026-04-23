# Task 3.3 — Condition Report Submission

## Context
A tech working an `in_progress` ticket can escalate by submitting a Condition Report. This task builds the submission form and wires it into the state machine, transitioning the ticket to `awaiting_approval`.

## Task
Build the `SubmitConditionReport` Livewire component with all structured fields, optional file attachments (reusing `FileUploadService`), and the state machine transition to `awaiting_approval`.

## Requirements
- Livewire component: `app/Modules/Escalation/Livewire/SubmitConditionReport.php` + Blade view.
- Accessible from the ticket detail view when `ticket.status = 'in_progress'` and `auth()->user()->is_tech = true`. Button not rendered for any other status or role.
- Fields: Report Type (free text, required, 255 max), Location (dropdown of active locations — `is_active = true AND deleted_at IS NULL` — localized name), Report Date (auto-filled to today, displayed read-only), Current Condition (rich text, required, sanitized via §3.2 purifier), Condition Analysis (rich text, required, sanitized), Required Action (rich text, required, sanitized).
- File attachments: optional, up to 5 files, same validation rules as §3.4 (MIME magic bytes, 10MB limit, ULID names, stored outside web root). Reuse `FileUploadService` — do not duplicate logic. Store in `storage/app/condition-reports/{condition_report_ulid}/`. Creates `ConditionReportAttachment` records.
- On submit: wrap in DB transaction — create `ConditionReport` (status=`pending`, `tech_id = auth()->id()`, `report_date = today()`), store attachments, call `TicketStateMachine::transition(ticket → awaiting_approval)`. Rollback if state machine throws.
- Route: no dedicated GET route for the form — rendered inline in `TicketDetail` as a Livewire component. The submit action is a POST through the Livewire lifecycle.
- Rate limit: reuse the general upload rate limit (20/hr per user) from §3.5 for attachment uploads. No separate rate limit on the report submission itself.
- All validation messages and field labels via `__()` with keys in `resources/lang/{ar,en}/escalation.php`.
- Authorization enforced in the component: `abort(403)` if caller is not a tech or if ticket is not `in_progress`.

## Do NOT
- Do not allow submission if ticket is not in `in_progress` — validate both in UI (hide button) and in server action (abort 403).
- Do not set `reviewed_by`, `reviewed_at`, or `review_notes` here — those are set by the approver in Task 3.4.
- Do not render unsanitized rich text from condition report fields anywhere.
- Do not duplicate file upload logic — extend `FileUploadService` or call it directly.
- Do not import `Ticket` model in the Escalation Livewire component — receive the ticket ULID from the route and resolve via a service or pass the pre-loaded model from the parent TicketDetail component.

## Acceptance
- Pest feature tests `tests/Feature/Phase3/ConditionReportSubmissionTest.php`:
  - Authenticated tech submits valid form → `condition_reports` record created, ticket status transitions to `awaiting_assignment` (await_approval). Verify `ticket.status = 'awaiting_approval'`.
  - Form submission without `current_condition` returns validation error.
  - Non-tech user POSTing the action receives 403.
  - Submission on a ticket not in `in_progress` receives 403.
  - Attachment with wrong MIME type is rejected with validation error.
  - 6th attachment in one submit is rejected.
  - `TicketStatusChanged` event fired (`Event::fake()`).
  - DB transaction rollback: if `ConditionReport` saves but state machine throws, no partial record remains.

## References
- `SPEC.md §8.2` — condition report flow and field list
- `SPEC.md §8.1` — condition_reports and condition_report_attachments schema
- `SPEC.md §7.4` — `in_progress → awaiting_approval` state machine transition
- `SPEC.md §3.2` — rich text sanitization
- `SPEC.md §3.4` — file upload security
- `SPEC.md §4.2` — localization requirement
