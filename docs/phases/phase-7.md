# Phase 7 — Reporting, Export & CSAT

**Spec reference:** [SPEC.md §12](../../SPEC.md#12-phase-7--reporting-export--csat)
**Deliverable:** 12 pre-defined report types with filters, CSV/XLSX export with dynamic columns, and CSAT feedback system.
**Exit condition:** All items in [§12.4 Phase 7 Acceptance Criteria](../../SPEC.md#124-phase-7-acceptance-criteria) pass.

## Tasks

- [x] **Task 7.1** — `csat_ratings` migration/model/factory with UNIQUE on `ticket_id`, `status` enum `pending/submitted/expired`, `expires_at` = resolved + 7 days; listener on `TicketStatusChanged` → resolved that creates the row; `csat:expire` scheduled daily command flipping expired rows; feature tests.
- [x] **Task 7.2** — CSAT flows: login-time modal/banner Livewire listing pending ratings (dismiss increments `dismissed_count`, submit saves rating 1-5 + comment); ticket detail view submission (any time); visibility rules per §12.1 (requester sees own, assigned tech sees rating + comment, IT Manager sees all, other techs see nothing); feature tests per role.
- [x] **Task 7.3** — Reports — Volume bundle: Ticket Volume, Tickets by Status, Tickets by Category, Tickets by Priority. Shared `ReportsController` + filter component (date range required + category + priority + group + tech + status) + `permission:system.view-reports` gating; data-table Livewire rendering; feature tests per report.
- [ ] **Task 7.4** — Reports — Performance bundle: Avg Resolution Time, Tech Performance (resolved count + avg CSAT + SLA compliance), Team Workload (current open per tech), Escalation Summary (triggered/approved/rejected per period); feature tests per report.
- [ ] **Task 7.5** — Reports — SLA + CSAT bundle: SLA Compliance (% within SLA broken down by priority), SLA Breaches (with tech + target vs actual), CSAT Overview (avg rating by period + response rate + distribution), CSAT by Tech (avg + count + lowest-rated tickets); feature tests per report.
- [ ] **Task 7.6** — Export synchronous path: CSV + XLSX writers with standard ticket columns + dynamic columns for every `custom_fields` row (active + soft-deleted where values exist) + SLA columns + CSAT columns; honors active report filters; feature tests over column mapping.
- [ ] **Task 7.7** — Large export queued via Horizon job; on completion, in-app + email notification with download link to temporary signed URL; tests with `Queue::fake()` + `Storage::fake()`.

## Session Groupings

| Session | Tasks | Rationale |
|---------|-------|-----------|
| S1 | 7.1, 7.2 | CSAT schema + flows are a cohesive feature; keep them in one session. |
| S2 | 7.3 | Volume bundle establishes the reports scaffolding + filter component. |
| S3 | 7.4 | Performance bundle reuses scaffolding; separate to keep session scope sized. |
| S4 | 7.5 | SLA + CSAT reports close out the 12-report set. |
| S5 | 7.6, 7.7 | Synchronous export + async export are two sides of the same feature — share writer code. |

## Acceptance Gate (from SPEC §12.4)

- [ ] CSAT modal appears on login for pending ratings, shows correct tech name and ticket info
- [ ] CSAT can be dismissed and reappears next login
- [ ] CSAT stops prompting after expiry (7 days)
- [ ] CSAT can be submitted from modal or ticket detail view
- [ ] All 12 report types render with correct data tables
- [ ] Report filters work in combination
- [ ] CSV and XLSX exports include all standard + dynamic columns
- [ ] Large exports queued and download link provided
- [ ] CSAT visibility rules enforced per role
