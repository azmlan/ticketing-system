# Phase 4 — Communication Layer

**Spec reference:** [SPEC.md §9](../../SPEC.md#9-phase-4--communication-layer)
**Deliverable:** Comments (public/internal) and notification engine with email delivery.
**Exit condition:** All items in [§9.4 Phase 4 Acceptance Criteria](../../SPEC.md#94-phase-4-acceptance-criteria) pass.

## Tasks

- [x] **Task 4.1** — `comments` migration/model/factory with `is_internal` (default `true`) + global/local scopes enforcing internal-comment filtering at the query level for employee-side contexts; `notification_logs` migration/model/factory with `queued/sent/failed` enum; leak tests asserting internal comments never appear in employee-visible queries.
- [x] **Task 4.2** — Comments Livewire: rich text editor with same sanitization as ticket description, public/internal toggle (default internal), response-template pre-fill that respects template's default `is_internal`; comment rate limit 30/hr per user; feature tests including internal-vs-public visibility for each role.
- [x] **Task 4.3** — Notification engine: `NotificationDispatcher` service + event listeners for every trigger in §9.3 matrix (ticket_created, ticket_assigned, escalation_submitted, escalation_updated, action_required, form_rejected, ticket_resolved, ticket_closed, sla_warning, sla_breached, transfer_request, csat_reminder); queued via Horizon; 3-attempt retry with exponential backoff; every attempt persisted to `notification_logs`; tests with `Queue::fake()`.
- [x] **Task 4.4** — Mailable classes per notification type rendering in recipient's `users.locale`; AR + EN email template files organized by module; plain-text fallback; recipient resolution per trigger (e.g., escalation_submitted → all users with `permission:escalation.approve`); tests with `Mail::fake()` covering locale + recipient matrix.

## Session Groupings

| Session | Tasks | Rationale |
|---------|-------|-----------|
| S1 | 4.1 | Schema + internal-visibility global scope is a security invariant; worth an isolated, well-tested session. |
| S2 | 4.2 | Comment UI with rate limit and response templates — full surface; fits one session. |
| S3 | 4.3, 4.4 | Notification engine + Mailables are tightly coupled — dispatcher renders via Mailables and stores logs. |

## Acceptance Gate (from SPEC §9.4)

- [ ] Public and internal comments work with correct visibility enforcement
- [ ] Internal comments never leak to employee-side views or emails
- [ ] Response templates can be selected, auto-fill, and edited before posting
- [ ] Email notifications fire for all triggers in the matrix
- [ ] Notifications rendered in recipient's preferred language
- [ ] Failed notifications retried (3x) and logged
- [ ] Comment rate limiting active (30/hour per user)
