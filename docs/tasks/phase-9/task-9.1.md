# Task 9.1 — Resolutions Schema, Model & Factory

## Context
Phase 9 introduces structured resolution capture. This task lays the data foundation before any UI is built.

## Task
Create the `resolutions` migration, model, factory, and relationship tests for the Precedent module.

## Requirements
- Migration: `resolutions` table per §14.1 — ULID PK, `ticket_id` UNIQUE FK (ON DELETE CASCADE), `summary` varchar(500) NOT NULL, `root_cause` varchar(500) NULLABLE, `steps_taken` text NOT NULL, `parts_resources` text NULLABLE, `time_spent_minutes` int unsigned NULLABLE, `resolution_type` enum(`known_fix`,`workaround`,`escalated_externally`,`other`) NOT NULL, `linked_resolution_id` ULID FK self-reference (ON DELETE SET NULL) NULLABLE, `link_notes` text NULLABLE, `usage_count` int unsigned NOT NULL DEFAULT 0, `created_by` ULID FK → `users` (ON DELETE RESTRICT)
- Index on `(linked_resolution_id)` per §14.1
- `Resolution` model in `app/Modules/Precedent/Models/` with `HasUlids`, casts, fillable list
- `belongsTo` / `hasOne` relationships: `ticket()`, `creator()`, `linkedResolution()` (self-ref), `linkedBy()` (inverse hasMany)
- `ResolutionFactory` covering all enum values and both linked/unlinked states
- Module service provider registered in `bootstrap/providers.php`

## Do NOT
- Add any Livewire components or controllers — schema only
- Touch `TicketStateMachine` — that comes in Task 9.2
- Use `left`/`right` CSS properties
- Hardcode user-facing strings

## Acceptance
- `php artisan migrate` runs cleanly with the new table
- Unit tests:
  - `Resolution` can be created via factory for each `resolution_type`
  - `ticket()` relation resolves to the correct `Ticket`
  - `linkedResolution()` self-ref resolves when set, returns null when not
  - `linkedBy()` returns all resolutions that point to this one
  - UNIQUE constraint on `ticket_id` rejects duplicate insert
  - `linked_resolution_id` is set to NULL when referenced resolution is deleted (ON DELETE SET NULL verified)

## Reference
SPEC.md §14.1
