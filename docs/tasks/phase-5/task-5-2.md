# Task 5.2 — SLA Models, Seeders & TicketSla Bootstrap

## Context
Migrations exist. This task wires up Eloquent models, seeds default SLA policies, and ensures every new ticket gets a `ticket_sla` row on creation.

## Task
Create `SlaPolicy`, `TicketSla`, and `SlaPauseLog` models, seed four default policies, and hook ticket creation to bootstrap a `ticket_sla` record.

## Requirements
- Models under `app/Modules/SLA/Models/`. All use `HasUlids`. No SoftDeletes per §2.3.
- `SlaPolicy`: fillable columns, `priority` cast to string enum, relationships: `hasMany(TicketSla)` (via targets copied at creation, not FK).
- `TicketSla`: `response_status` + `resolution_status` cast to enum. `hasMany(SlaPauseLog)`. `belongsTo` ticket via `TicketServiceInterface` — no direct `Ticket` model import.
- `SlaPauseLog`: fillable columns, timestamps only.
- `DatabaseSeeder` calls `SlaSeeder` which upserts 4 rows into `sla_policies` by priority (do not duplicate on re-seed). Suggested defaults: low 480/2880, medium 240/1440, high 60/480, critical 30/240 minutes. `critical.use_24x7 = true`.
- On `TicketCreated` event: listener in SLA module creates `ticket_sla` row — copies `response_target_minutes` and `resolution_target_minutes` from the matching `SlaPolicy` (via ticket's priority). Sets `is_clock_running = true`, `last_clock_start = now()`.
- If ticket has no priority at creation, targets remain NULL until priority is set (§10.4).
- All translations in `resources/lang/{ar,en}/sla.php`.

## Do NOT
- Do not import `App\Modules\Tickets\Models\Ticket` inside the SLA module — listen to events only.
- Do not run `sla:check` logic here — that is Task 5.4.
- Do not hardcode string literals — all user-facing strings through `__()`.

## Acceptance
- Pest `tests/Feature/Phase5/SlaBootstrapTest.php`:
  - Creating a ticket with priority `high` → `ticket_sla` row exists with targets copied from `sla_policies` where `priority = high`.
  - Creating a ticket with no priority → `ticket_sla` row exists with NULL targets.
  - `is_clock_running = true` and `last_clock_start` is set on creation.
  - Re-running seeder does not duplicate `sla_policies` rows.
- `SlaSeeder` upserts cleanly: 4 rows, correct defaults, `critical.use_24x7 = true`.

## References
- `SPEC.md §10.1` — model column specs
- `SPEC.md §10.2` — response/resolution timer start behavior
- `SPEC.md §10.4` — priority change and NULL targets
- `SPEC.md §2.3` — SoftDeletes policy
