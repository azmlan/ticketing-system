# Task 5.4 — Priority Change Handler & sla:check Scheduled Command

## Context
`SlaService` manages event-driven transitions. Two remaining concerns: priority changes that recalculate SLA targets mid-flight, and the background command that advances elapsed time every minute for running tickets.

## Task
Implement the `TicketPriorityChanged` listener for target recalculation and the `sla:check` Artisan command scheduled every minute.

## Requirements
- Listener `HandleTicketPriorityChanged` in SLA module, subscribes to `TicketPriorityChanged` event (Tickets module fires this). On fire:
  - Load the new priority's `SlaPolicy`.
  - Update `ticket_sla.response_target_minutes` and `resolution_target_minutes` with the new policy's values.
  - Do NOT reset elapsed — only the target changes per §10.4.
  - Call `SlaService::recalculateStatus()` to re-evaluate warning/breach against new target.
  - Dispatch `SlaWarning` / `SlaBreach` events if thresholds newly crossed.
- Artisan command `sla:check` (`app/Modules/SLA/Commands/SlaCheckCommand.php`), signature `sla:check`.
  - Queries all `ticket_sla` where `is_clock_running = true` and `last_clock_start IS NOT NULL`.
  - For each: calls `BusinessHoursCalculator::minutesBetween(last_clock_start, now(), use_24x7)`, adds to current elapsed, saves, calls `recalculateStatus()`.
  - Does NOT update `last_clock_start` — it reflects when the current run period began.
  - Registered in `bootstrap/app.php` (or `Console/Kernel.php` if applicable) to run `everyMinute()`.
  - Command exits with 0 on success, logs failures per ticket without aborting the rest.
- `SlaWarning` event → `NotificationService` sends `notifications.sla_warning` to assigned tech.
- `SlaBreach` event → `NotificationService` sends `notifications.sla_breached` to assigned tech AND IT Manager per §9.3 notification matrix.

## Do NOT
- Do not reset `last_clock_start` inside `sla:check` — resetting would lose the run-period start reference.
- Do not skip tickets because they are off-hours — `BusinessHoursCalculator` handles that in the minutes count.
- Do not fire duplicate warning/breach notifications if status is already at that level — check current status before firing.
- Do not import Ticket model from Tickets module.

## Acceptance
- Pest unit `tests/Unit/Phase5/SlaCheckCommandTest.php`:
  - Running command with a `ticket_sla` that has been running 10 business minutes → elapsed incremented by ≈10.
  - Running command during off-hours → elapsed not incremented (for non-24x7 policy).
  - Command runs without exception when `ticket_sla` list is empty.
- Pest feature `tests/Feature/Phase5/PriorityChangeTest.php`:
  - Priority change from `low` to `high` → targets updated to `high` policy values, elapsed unchanged.
  - If new target puts ticket in `warning` territory → `SlaWarning` event fired.
  - If new target puts ticket in `breached` territory → `SlaBreach` event fired.
- Scheduler registration: `php artisan schedule:list` shows `sla:check` at `* * * * *`.

## References
- `SPEC.md §10.4` — priority change impact, elapsed carry-over
- `SPEC.md §10.5` — `sla:check` command behavior
- `SPEC.md §9.3` — SLA warning/breach notification recipients
