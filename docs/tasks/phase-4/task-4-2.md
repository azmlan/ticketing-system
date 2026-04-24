# Task 4.2 — Communication Models & Factories

## Context
Phase 4 migrations exist. This task creates the Eloquent models, factories, and the `CommunicationServiceProvider` that establishes the Communication module boundary.

## Task
Create `Comment`, `NotificationLog`, and `ResponseTemplate` Eloquent models in `app/Modules/Communication/Models/`, their factories, and the `CommunicationServiceProvider`.

## Requirements
- `Comment` model: `HasUlids`, fillable for non-auto columns, `casts` for `is_internal` (boolean). Global scope `InternalCommentScope` — when the authenticated user is an employee (not tech, not superuser), automatically appends `WHERE is_internal = false`. Relationships: `belongsTo(User::class)`. `ticket()` resolved via raw ULID — no direct import of the Ticket model (cross-module boundary violation per CLAUDE.md).
- `NotificationLog` model: `HasUlids`, fillable, `casts` for `status` (string enum: queued/sent/failed), `sent_at` (datetime). Relationships: `belongsTo(User::class, 'recipient_id')`. Same cross-module boundary rule for ticket.
- `ResponseTemplate` model: `HasUlids`, `SoftDeletes`, fillable, `casts` for `is_internal` + `is_active` (boolean). Scope `active()` → `WHERE is_active = true AND deleted_at IS NULL`. No relationships needed at this stage.
- `CommunicationServiceProvider` registered in `bootstrap/providers.php`. Registers routes from `app/Modules/Communication/Routes/`.
- Factories: `CommentFactory` (states: `->public()` sets `is_internal=false`, `->internal()` default), `NotificationLogFactory` (states: `->sent()`, `->failed()`), `ResponseTemplateFactory` (realistic bilingual titles and bodies).
- No business logic in models — data containers only.

## Do NOT
- Do not import `App\Modules\Tickets\Models\Ticket` into any Communication model.
- Do not add visibility filtering to `NotificationLog` or `ResponseTemplate` — `InternalCommentScope` is only on `Comment`.
- Do not create Livewire components or notification jobs here (Tasks 4.3–4.4).
- Do not add rate-limiting logic to models.

## Acceptance
- Pest unit tests `tests/Unit/Phase4/CommunicationModelsTest.php`:
  - `Comment::factory()->create()` produces a DB record with `is_internal = true`.
  - `Comment::factory()->public()->create()` has `is_internal = false`.
  - Authenticated as an employee, `Comment::all()` returns zero internal comments (scope active).
  - Authenticated as a tech, `Comment::all()` includes internal comments (scope bypassed).
  - `NotificationLog::factory()->failed()->create()` has `status = 'failed'`.
  - `ResponseTemplate::factory()->create()` has non-null `title_ar`, `title_en`, `body_ar`, `body_en`.
  - `ResponseTemplate::active()->get()` excludes soft-deleted and inactive records.
- `CommunicationServiceProvider` boots without exception.

## References
- `SPEC.md §9.1` — model columns
- `SPEC.md §9.2` — `InternalCommentScope` visibility rule
- `SPEC.md §2.3` — ULID convention, SoftDeletes
- `CLAUDE.md` — module communication rules (no cross-module model imports)
