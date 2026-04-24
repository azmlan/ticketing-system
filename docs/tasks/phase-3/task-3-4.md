# Task 3.4 — Condition Report Approval

## Context
Once a condition report is submitted the ticket waits in `awaiting_approval`. This task builds the approver-facing review UI and the approve/reject actions that drive the two diverging state machine paths.

## Task
Build the `ReviewConditionReport` Livewire component for users with `permission:escalation.approve`, implementing approve (→ `action_required`) and reject (→ `in_progress`) transitions via the state machine.

## Requirements
- Livewire component: `app/Modules/Escalation/Livewire/ReviewConditionReport.php` + Blade view.
- Accessible from the ticket detail view when `ticket.status = 'awaiting_approval'` and `auth()->user()->can('escalation.approve')`. Hidden otherwise.
- Display the full condition report: report type, location (localized), report date, current condition (sanitized HTML render), condition analysis (sanitized HTML render), required action (sanitized HTML render), submitted by tech (name), submitted at timestamp. List any attached files with authorized download links (same serve-route pattern as §3.4 for ticket attachments — a separate serve route for condition report attachments: `GET /escalation/condition-reports/{conditionReport}/attachments/{attachment}`).
- **Approve action:** Sets `condition_reports.status = 'approved'`, `reviewed_by = auth()->id()`, `reviewed_at = now()`. Calls `TicketStateMachine::transition(ticket → action_required)`. This also triggers the maintenance request creation (Task 3.5 hooks into this event — do not call the document generator directly here; fire `TicketStatusChanged` via state machine and let the listener handle it).
- **Reject action:** Requires `review_notes` (mandatory text, max 1000 chars). Sets `condition_reports.status = 'rejected'`, `reviewed_by`, `reviewed_at`, `review_notes`. Calls `TicketStateMachine::transition(ticket → in_progress)`.
- Both actions wrapped in DB transactions. Both set `reviewed_by` and `reviewed_at` atomically with the status update.
- Authorization in the component: `abort(403)` if actor lacks `escalation.approve` or ticket is not `awaiting_approval`.
- All labels and flash messages via `__()` with keys in `resources/lang/{ar,en}/escalation.php`.
- Condition report attachment serve route gated by: actor is the submitting tech, or has `escalation.approve`, or has `ticket.view-all`.

## Do NOT
- Do not call the document generation service from this component (Task 3.5 listens to `TicketStatusChanged` and handles it).
- Do not allow approving your own condition report — the submitting tech (`tech_id`) cannot be the reviewer. Enforce in service logic, not only in UI.
- Do not show the review UI to the requester (employee) at any point.
- Do not render condition report rich text fields without the sanitized HTML purifier — run through the same purifier before rendering, even though content was purified on save.

## Acceptance
- Pest feature tests `tests/Feature/Phase3/ConditionReportApprovalTest.php`:
  - User with `escalation.approve` approves → `condition_reports.status = 'approved'`, ticket status = `action_required`, `reviewed_by` and `reviewed_at` set.
  - User with `escalation.approve` rejects with notes → status = `rejected`, ticket status = `in_progress`, `review_notes` persisted.
  - Reject without `review_notes` returns validation error.
  - User without `escalation.approve` permission cannot access the approve action (403).
  - Submitting tech cannot approve their own condition report (403 or validation error).
  - Ticket not in `awaiting_approval` rejects both actions (403).
  - `TicketStatusChanged` event fired on both approve and reject (`Event::fake()`).
  - Condition report attachment serve route returns 403 for unauthorized user.

## References
- `SPEC.md §8.2` — approve/reject outcomes and state machine paths
- `SPEC.md §7.4` — `awaiting_approval → action_required` and `awaiting_approval → in_progress` transitions
- `SPEC.md §6.3` — `escalation.approve` permission
- `SPEC.md §3.4` — authorized file serve pattern
- `SPEC.md §4.2` — localization requirement
