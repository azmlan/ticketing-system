# Phase 6 — Dashboards, Search & Filtering

**Spec reference:** [SPEC.md §11](../../SPEC.md#11-phase-6--dashboards-search--filtering)
**Deliverable:** Employee dashboard, tech dashboard, IT Manager dashboard with charts, full search and filtering, saved group selections.
**Exit condition:** All items in [§11.6 Phase 6 Acceptance Criteria](../../SPEC.md#116-phase-6-acceptance-criteria) pass.

## Tasks

- [x] **Task 6.1** — `SearchServiceInterface` contract + `MySqlSearchDriver` using FULLTEXT indexes on `tickets.subject`, `tickets.description`, `comments.body`; migrations to add FULLTEXT indexes; service provider binding so controllers/Livewire always resolve via DI (not direct FULLTEXT); contract tests + driver tests.
- [x] **Task 6.2** — Employee dashboard Livewire: list own tickets (global scope enforced), status filter (open/resolved/closed/cancelled), subject-only search via `SearchServiceInterface`, count summary badges, quick-submit button; feature tests asserting no cross-requester leakage.
- [x] **Task 6.3** — Tech dashboard Livewire: queue (unassigned in my groups) + my tickets (sorted by SLA urgency → priority → date) + SLA badges + pending incoming transfer requests panel + quick stats (open, resolved this week/month, SLA compliance rate); feature tests.
- [x] **Task 6.4** — Tech dashboard filter bar per §11.5 (status/priority/category/subcategory/group/assigned-to/date-range/search/sort); saved group selection persisted via `user_preferences` JSON column on `users` (or dedicated table); feature tests for filter combinations + persistence across sessions.
- [x] **Task 6.5** — IT Manager dashboard Livewire: tickets-by-status + tickets-by-category counts, created-this-week/month, avg resolution time, SLA compliance %, breached list with tech + overdue duration, escalation queue (`awaiting_approval` + `awaiting_final_approval`), unassigned count, per-tech open count, recent activity feed; feature tests asserting `permission:ticket.view-all` gating.
- [x] **Task 6.6** — Pagination (default 25, configurable) applied across all three dashboards + list views; sort controls (date newest/oldest, priority highest/lowest, last updated); RTL + AR localization verification including directional icons flipping; feature tests including snapshot of AR and EN renders.

## Session Groupings

| Session | Tasks | Rationale |
|---------|-------|-----------|
| S1 | 6.1 | Search interface is a protected invariant — isolated session for interface, driver, and contract tests. |
| S2 | 6.2, 6.3 | Employee + tech dashboards — both consume `SearchServiceInterface`; tech builds on employee patterns. |
| S3 | 6.4, 6.5 | Filter/preferences infrastructure + IT Manager dashboard (which reuses filter bar) pair well. |
| S4 | 6.6 | Pagination/sort/localization polish sweep over all dashboards. |

## Acceptance Gate (from SPEC §11.6)

- [ ] All three dashboards render with correct data and access controls
- [ ] Employee sees only own tickets
- [ ] Tech sees tickets from their groups and assigned tickets
- [ ] IT Manager sees system-wide summary stats and data tables
- [ ] Search returns relevant results from subject, description, and comments
- [ ] All filters work individually and in combination
- [ ] Group filter selections persist per user across sessions (server-side)
- [ ] Pagination works on all list views (default 25 per page)
- [ ] All dashboard views fully localized (AR/EN) and RTL-correct
