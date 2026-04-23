# Task 3.2 — Escalation Models & Factories

## Context
The escalation migrations exist. This task creates the Eloquent models and factories for the Escalation module, wires up the EscalationServiceProvider, and establishes the module boundary.

## Task
Create `ConditionReport`, `ConditionReportAttachment`, and `MaintenanceRequest` Eloquent models in `app/Modules/Escalation/Models/`, their factories, and the `EscalationServiceProvider`.

## Requirements
- `ConditionReport` model: `HasUlids` trait, `fillable` for all non-auto columns, `casts` for `status` (string enum), `reviewed_at` (datetime). Relationships: `hasMany(ConditionReportAttachment::class)`, `belongsTo(User::class, 'tech_id')`, `belongsTo(User::class, 'reviewed_by')`. `ticket()` relationship resolves via raw ULID — no direct import of the Ticket model (cross-module boundary violation per CLAUDE.md). Use `belongsTo` with a string model reference only if Ticket is in the same module; otherwise expose `ticket_id` as a plain attribute and retrieve through `TicketService` in the service layer.
- `ConditionReportAttachment` model: `HasUlids`, `fillable`, `belongsTo(ConditionReport::class)`. No `updated_at` is not applicable — keep standard timestamps.
- `MaintenanceRequest` model: `HasUlids`, `fillable`, `casts` for `status` (string enum), `submitted_at` (datetime), `reviewed_at` (datetime). Relationships: `belongsTo(User::class, 'reviewed_by')`. Same cross-module boundary rule applies for the ticket relationship.
- `EscalationServiceProvider` registered in `bootstrap/providers.php`. Registers routes from `app/Modules/Escalation/Routes/`.
- Factories: `ConditionReportFactory` (use `ConditionReport::factory()->pending()` / `->approved()` states), `ConditionReportAttachmentFactory`, `MaintenanceRequestFactory`. Realistic fake data; factories do not touch the filesystem.
- All models have no `deleted_at` — no SoftDeletes trait per §2.3.

## Do NOT
- Do not import `App\Modules\Tickets\Models\Ticket` into any Escalation model — cross-module boundary violation.
- Do not import `App\Modules\Tickets\Models\Ticket` anywhere in the Escalation module; accept ticket data via service injection or ULID-only parameters.
- Do not add business logic to models — models are data containers only.
- Do not create routes or Livewire components in this task (Tasks 3.3–3.6).

## Acceptance
- Pest unit tests `tests/Unit/Phase3/EscalationModelsTest.php`:
  - `ConditionReport::factory()->create()` produces a valid DB record.
  - `ConditionReport::factory()->approved()->create()` has `status = 'approved'` and non-null `reviewed_by`.
  - `ConditionReportAttachment::factory()->create()` links to a condition report.
  - `MaintenanceRequest::factory()->create()` produces a valid record with `status = 'pending'`.
  - All fillable columns round-trip correctly (create → reload → assert).
- `EscalationServiceProvider` registered — no exception when booting the app.

## References
- `SPEC.md §8.1` — model columns and relationships
- `SPEC.md §2.3` — ULID convention, which tables have SoftDeletes
- `CLAUDE.md` — module communication rules (no cross-module model imports)
