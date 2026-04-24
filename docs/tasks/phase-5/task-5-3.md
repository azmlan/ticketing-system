# Task 5.3 — SlaService: Clock Management & Business Hours

## Context
Models and bootstrap exist. This task implements the core SLA engine: business hours calculation and the clock start/pause/resume/stop logic that reacts to ticket status changes.

## Task
Implement `BusinessHoursCalculator`, `SlaService`, and the `TicketStatusChanged` listener that drives clock state transitions and elapsed time accumulation.

## Requirements
- `BusinessHoursCalculator` in `app/Modules/SLA/Services/`. Reads `business_hours_start`, `business_hours_end`, `working_days` from `app_settings` (key-value store, Phase 8 owns the admin UI — read from DB directly here). Exposes `minutesBetween(Carbon $from, Carbon $to, bool $use24x7): int` — counts only minutes falling within business hours and working days. Handles overnight boundaries and multi-day spans correctly.
- `SlaService` in `app/Modules/SLA/Services/`. Methods:
  - `startClock(TicketSla)` — sets `is_clock_running = true`, `last_clock_start = now()`.
  - `pauseClock(TicketSla, string $pauseStatus)` — flushes elapsed since `last_clock_start` into `*_elapsed_minutes`, sets `is_clock_running = false`, writes `sla_pause_logs` row with `paused_at`.
  - `resumeClock(TicketSla, string $pauseStatus)` — closes open pause log (`resumed_at = now()`, `duration_minutes` calculated), calls `startClock`.
  - `stopClock(TicketSla)` — flushes elapsed, sets `is_clock_running = false`, no pause log written.
  - `recalculateStatus(TicketSla)` — computes percentage used = elapsed / target × 100, sets `warning` at threshold (from `sla_warning_threshold` setting, default 75), `breached` at 100. Fires `SlaWarning` or `SlaBreach` event when status first crosses each threshold.
- Listener `HandleTicketStatusChanged` subscribes to `TicketStatusChanged` event. Maps status to clock action per §10.2 table (running → pause on hold/awaiting_approval/action_required/awaiting_final_approval; stop on resolved/closed/cancelled; resume on return to running status).
- Response timer: stopped when `response_met_at IS NULL` and a tech is first assigned — set `response_met_at = now()` on `TicketAssigned` event.
- All elapsed time flushed using `BusinessHoursCalculator` — respects `sla_policy.use_24x7`.
- No direct `Ticket` model import in SLA module — receive data via event payloads.

## Do NOT
- Do not calculate elapsed using wall-clock minutes — must go through `BusinessHoursCalculator`.
- Do not fire notifications directly — dispatch `SlaWarning` / `SlaBreach` events; Phase 4's `NotificationService` listens.
- Do not hardcode working-day defaults — always read from `app_settings`.
- Do not import Ticket model from Tickets module.

## Acceptance
- Pest unit `tests/Unit/Phase5/BusinessHoursCalculatorTest.php`:
  - 2-hour span entirely inside business hours → correct minutes.
  - Span crossing end-of-day → excludes off-hours.
  - Span crossing a non-working day → skips that day entirely.
  - `use_24x7 = true` → returns wall-clock minutes regardless of config.
- Pest feature `tests/Feature/Phase5/SlaClockTest.php`:
  - Status → `on_hold`: pause log created, `is_clock_running = false`, elapsed accumulated correctly.
  - Status → `in_progress` (resume): pause log `resumed_at` set, `duration_minutes` correct, clock running again.
  - Status → `resolved`: clock stopped, no new pause log.
  - First tech assignment: `response_met_at` set, response timer stops.
  - Warning threshold crossed: `SlaWarning` event fired once.
  - Breach crossed: `SlaBreach` event fired once.

## References
- `SPEC.md §10.2` — clock behavior per status table
- `SPEC.md §10.3` — business hours config, 24/7 override, warning threshold
- `SPEC.md §13.4` — `app_settings` key list
