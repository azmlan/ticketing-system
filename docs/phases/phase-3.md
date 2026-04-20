# Phase 3 — Escalation Workflow

**Spec reference:** [SPEC.md §8](../../SPEC.md#8-phase-3--escalation-workflow)
**Deliverable:** Complete escalation path: Condition Report submission, approval, auto-generated Maintenance Request document (AR/EN), requester signature and re-upload, and final approval with reject/resubmit loop.
**Exit condition:** All items in [§8.5 Phase 3 Acceptance Criteria](../../SPEC.md#85-phase-3-acceptance-criteria) pass.

## Tasks

- [ ] **Task 3.1** — `condition_reports` + `condition_report_attachments` + `maintenance_requests` migrations/models/factories with enums (`pending/approved/rejected` and `pending/submitted/approved/rejected`), `rejection_count`, `generated_locale`; schema tests.
- [ ] **Task 3.2** — Condition report submit Livewire (tech fills report_type/location/current_condition/analysis/required_action, attaches files via Phase-2 upload pipeline); approve/reject Livewire for `permission:escalation.approve`; state transitions `in_progress → awaiting_approval → action_required|in_progress` via `TicketStateMachine`; feature tests.
- [ ] **Task 3.3** — `MaintenanceRequestGenerator` service using `phpoffice/phpword`: generates AR (RTL) and EN (LTR) DOCX with tenant header (company name + logo from `app_settings`), ticket info, requester info, condition report analysis, hardcoded bilingual disclaimer (§8.3.1 item 7), signature block; ULID-named file path outside web root; fresh generation on each download; unit + snapshot tests.
- [ ] **Task 3.4** — Maintenance request download routes (AR + EN), requester upload Livewire accepting DOCX/PDF (MIME magic-bytes), `status → submitted`, state transition `action_required → awaiting_final_approval`; policy notice view with disclaimer; feature tests.
- [ ] **Task 3.5** — Final approval Livewire: approve → `resolved` (triggers Phase-9 resolution capture); reject-resubmit loops back to `action_required`, increments `rejection_count`, forces fresh document regeneration; reject-permanently → `closed` with mandatory reason (Phase-2 close flow); feature tests for each branch.

## Session Groupings

| Session | Tasks | Rationale |
|---------|-------|-----------|
| S1 | 3.1 | Schema bundle, isolated. |
| S2 | 3.2 | Condition report submit/approve/reject — full session for state-machine integration + auth. |
| S3 | 3.3 | DOCX generator is complex (bilingual, RTL, templating, file storage); alone. |
| S4 | 3.4, 3.5 | Requester upload + final approval flows share maintenance_request state and resubmit loop. |

## Acceptance Gate (from SPEC §8.5)

- [ ] Tech can submit a condition report with all fields and attachments
- [ ] Approver can approve/reject condition reports
- [ ] Maintenance Request document generates correctly in Arabic (RTL) and English (LTR)
- [ ] Document contains all pre-filled fields: ticket info, requester info, tech analysis, disclaimer, signature block
- [ ] Company name and logo from tenant config appear in document header
- [ ] Requester can download in either language
- [ ] Requester can upload signed document (DOCX or PDF) and submit
- [ ] Approver can approve, reject (resubmit), or reject permanently
- [ ] Reject-resubmit loop works correctly, rejection count tracks, fresh document generated on resubmit
- [ ] All escalation status transitions enforced by state machine
- [ ] Full localization (AR/EN) on all escalation views
