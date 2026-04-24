# Task 7.1 — CSAT: Migration, Model & Expire Command

## Context
CSAT records are created on ticket resolution and expire after 7 days. The `csat_ratings` table and scheduled expire command are the foundation all CSAT flows depend on.

## Task
Create the `csat_ratings` migration, `CsatRating` model, and the `csat:expire` artisan command.

## Requirements
- Migration: `csat_ratings` table per §12.1 — all columns, ULID PK, UNIQUE on `ticket_id`, FK ON DELETE constraints, enum `(pending, submitted, expired)`.
- `CsatRating` model in `app/Modules/CSAT/Models/` with `HasUlids`, fillable, casts (`expires_at`, `submitted_at` as datetime; `rating` as int), relationships to `Ticket`, requester `User`, tech `User`.
- Scopes: `pending()`, `expired()`, `submitted()`.
- `CsatServiceProvider` registers the module; add to `bootstrap/providers.php`.
- `csat:expire` command: sets `status = 'expired'` for all `pending` rows where `expires_at <= now()`; idempotent; logs count of expired records.
- Register `csat:expire` in `routes/console.php` to run daily.

## Do NOT
- Do not trigger any email or event from this task — those come in task 7.2.
- Do not create Livewire components here.
- Do not touch the `tickets` table or `TicketStateMachine`.

## Acceptance
- `docker compose exec app php artisan migrate` runs cleanly; `csat_ratings` table exists with correct schema.
- Pest `tests/Unit/CSAT/CsatRatingModelTest.php`: `pending()` scope returns only pending rows; `expired()` scope returns only expired; rating cast works.
- Pest `tests/Feature/CSAT/CsatExpireCommandTest.php`: expired rows transition to `expired` status; already-expired rows not double-processed; submitted rows untouched.
- `php artisan csat:expire` exits zero.

## References
- `SPEC.md §12.1` — full `csat_ratings` schema and flow
- `SPEC.md §5.1` — FK ON DELETE patterns
- `SPEC.md §2.3` — ULID PK convention
