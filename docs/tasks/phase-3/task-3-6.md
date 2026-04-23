# Task 3.6 — Requester Upload & Final Approval Loop

## Context
The requester has downloaded the pre-filled DOCX, signed it offline, and must re-upload the signed copy. The approver then makes a final decision: approve (→ `resolved`), reject-resubmit (→ `action_required` loop), or reject permanently (→ `closed`). This task closes the escalation loop.

## Task
Build the signed document upload form for the requester and the final approval Livewire component for approvers, including the reject-resubmit loop with `rejection_count` tracking and fresh document re-generation.

## Requirements

### Requester Upload
- Livewire component: `app/Modules/Escalation/Livewire/UploadSignedMaintenanceRequest.php` + Blade view.
- Rendered in ticket detail when `ticket.status = 'action_required'` and `auth()->id() === ticket.requester_id`.
- Shows: policy notice explaining the requirement (localized text per §8.4), disclaimer reminder, the two download buttons ("Export in Arabic" / "Export in English") from Task 3.5.
- File upload field: accepts DOCX or PDF only. MIME validation via magic bytes — `application/pdf` or `application/vnd.openxmlformats-officedocument.wordprocessingml.document`. Max 10 MB. Stored in `storage/app/maintenance-requests/{ticket_ulid}/signed/` with ULID filename.
- On submit: validate file → store file → update `maintenance_requests.submitted_file_path` and `submitted_at = now()` and `status = 'submitted'` → call `TicketStateMachine::transition(ticket → awaiting_final_approval)`. Wrap in DB transaction.
- Authorization: `abort(403)` if caller is not the ticket's requester or ticket is not `action_required`.
- Upload rate limit: 20 uploads/hour per user (§3.5), shared with the existing upload rate limiter key.

### Final Approval
- Livewire component: `app/Modules/Escalation/Livewire/ReviewSignedMaintenanceRequest.php` + Blade view.
- Rendered in ticket detail when `ticket.status = 'awaiting_final_approval'` and `auth()->user()->can('escalation.approve')`.
- Displays: link to download the submitted signed document via an authorized serve route (`GET /escalation/maintenance-requests/{maintenanceRequest}/signed`). Shows `rejection_count` if > 0 (localized). Shows `review_notes` from any prior rejection.
- **Approve action:** Sets `maintenance_requests.status = 'approved'`, `reviewed_by`, `reviewed_at`. Calls `TicketStateMachine::transition(ticket → resolved)`.
- **Reject (resubmit) action:** Requires `review_notes` (required, max 1000 chars). Sets `maintenance_requests.status = 'rejected'`, `reviewed_by`, `reviewed_at`, `review_notes`. Increments `maintenance_requests.rejection_count`. Calls `TicketStateMachine::transition(ticket → action_required)`. This re-fires `TicketStatusChanged(action_required)` — the listener in Task 3.5 must guard against creating duplicate `maintenance_requests` records (check `whereNotNull` before creating). On resubmit, the next download generates a fresh document.
- **Reject permanently action:** Requires selecting a close reason from the hardcoded list (§7.6). Calls `TicketStateMachine::transition(ticket → closed)` with `close_reason` and optional `close_reason_text` persisted. Sets `maintenance_requests.status = 'rejected'`.
- Signed document serve route `GET /escalation/maintenance-requests/{maintenanceRequest}/signed`: gated by requester, assigned tech, or `escalation.approve`. Streams the file.
- All labels, flash messages via `__()` with keys in `resources/lang/{ar,en}/escalation.php`.

## Do NOT
- Do not accept extension-only validation for the signed upload — use magic bytes.
- Do not allow the final approval on a ticket not in `awaiting_final_approval` — server-side abort 403.
- Do not create a duplicate `maintenance_requests` record on reject-resubmit — update the existing one or guard in the listener.
- Do not use `display_number` in any route parameter.
- Do not allow reject-permanently without a close reason — validation error.

## Acceptance
- Pest feature tests `tests/Feature/Phase3/FinalApprovalTest.php`:
  - Requester uploads valid DOCX → `submitted_file_path` set, `submitted_at` set, `maintenance_requests.status = 'submitted'`, ticket status = `awaiting_final_approval`.
  - Non-requester cannot upload (403).
  - Upload with wrong MIME type (e.g., image) rejected with validation error.
  - Approve → `maintenance_requests.status = 'approved'`, ticket status = `resolved`, `reviewed_by` and `reviewed_at` set.
  - Reject (resubmit) without `review_notes` → validation error.
  - Reject (resubmit) with notes → ticket loops back to `action_required`, `rejection_count` incremented, no duplicate `maintenance_requests` record.
  - Reject permanently → ticket status = `closed`, `close_reason` persisted.
  - User without `escalation.approve` cannot access approve/reject actions (403).
  - Signed document serve route returns 403 for unrelated employee.
  - `TicketStatusChanged` event fired on all state transitions (`Event::fake()`).
  - Upload rate limit: 21st upload in an hour returns 429.

## References
- `SPEC.md §8.4` — requester upload flow, resubmit loop, rejection_count, permanent rejection
- `SPEC.md §7.4` — `awaiting_final_approval → resolved`, `awaiting_final_approval → action_required`, `Any → closed` transitions
- `SPEC.md §7.6` — close reason values for permanent rejection
- `SPEC.md §3.4` — MIME validation, file size, authorized serve pattern
- `SPEC.md §3.5` — upload rate limit
- `SPEC.md §6.3` — `escalation.approve` permission
- `SPEC.md §4.2` — localization requirement
