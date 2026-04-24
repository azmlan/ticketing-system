# Phase 5 — SLA Engine

**Spec reference:** [SPEC.md §10](../../SPEC.md#10-phase-5--sla-engine)
**Deliverable:** Response + resolution timers, business hours calculation, clock pausing, warning/breach states, and SLA notifications.
**Exit condition:** All items in [§10.6 Phase 5 Acceptance Criteria](../../SPEC.md#106-phase-5-acceptance-criteria) pass.

## Tasks

- [x] **Task 5.1** — `sla_policies` (UNIQUE on `priority`, `use_24x7`), `ticket_sla` (UNIQUE on `ticket_id`, status enums `on_track/warning/breached`, `is_clock_running`), `sla_pause_logs` migrations/models/factories + schema tests.
- [x] **Task 5.2** — `BusinessHoursCalculator` service reading `working_days`, `business_hours_start`, `business_hours_end` from `app_settings`; computes elapsed minutes between two timestamps honoring working days + time ranges; supports `use_24x7` per-policy override; unit tests covering cross-midnight, cross-weekend, holidays-as-non-working-day, 24/7 edge cases.
- [x] **Task 5.3** — `SlaService` listening to `TicketStatusChanged`: applies clock behavior per §10.2 table (running on `awaiting_assignment`/`in_progress`, paused on hold/approval/action_required/final_approval, stopped on terminal states); accrues `response_elapsed_minutes` + `resolution_elapsed_minutes`; `response_met_at` set on first assignment; writes `sla_pause_logs` rows with `duration_minutes` on resume; unit tests over entire lifecycle.
- [ ] **Task 5.4** — Priority change handler: on ticket priority set or change, recalc `response_target_minutes`/`resolution_target_minutes` from matching `sla_policies` row, preserve accrued elapsed, re-evaluate `response_status`/`resolution_status`; NULL policy handling when priority not yet set; feature tests.
- [ ] **Task 5.5** — `sla:check` scheduled console command (every minute) scanning `is_clock_running = true` tickets, recalculating elapsed via `BusinessHoursCalculator`, updating statuses, firing `SlaWarning` / `SlaBreached` domain events (consumed by Phase-4 notifications); configurable warning threshold from `app_settings.sla_warning_threshold` (default 75); tests with `Carbon::setTestNow`.
- [ ] **Task 5.6** — SLA indicator Blade component (green/yellow/red badge from `response_status`/`resolution_status`) rendered on ticket detail and tech dashboard lists; SLA compliance display surface (percentage + breached count); RTL-correct with logical properties; feature tests.

## Session Groupings

| Session | Tasks | Rationale |
|---------|-------|-----------|
| S1 | 5.1, 5.2 | Schema + business hours calculator form the foundation; calculator has no dependencies and can be tested in isolation. |
| S2 | 5.3 | `SlaService` is the central logic (clock state + pause logs); alone, full session. |
| S3 | 5.4, 5.5 | Priority-change and scheduled check both trigger recalculation — share code paths. |
| S4 | 5.6 | UI surface bundle (badges + compliance view) wraps the phase. |

## Acceptance Gate (from SPEC §10.6)

- [ ] SLA targets configurable per priority level in admin panel
- [ ] `ticket_sla` record created on ticket creation
- [ ] Response timer stops on first tech assignment
- [ ] Resolution timer pauses/resumes correctly on status transitions
- [ ] Business hours calculation correct (skips off-hours and non-working days)
- [ ] Warning and breach states trigger correct notifications
- [ ] SLA indicators (green/yellow/red) on ticket detail and tech dashboard
- [ ] Priority change recalculates targets with elapsed time carried over
- [ ] `sla:check` command correctly transitions timers during off-hours
- [ ] SLA clock pauses logged in `sla_pause_logs` with correct durations
