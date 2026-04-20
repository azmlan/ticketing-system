# CLAUDE.md

## Project
Generic internal IT ticketing system — Laravel 12 + Livewire 3 modular monolith. Resellable product, single-tenant deployments, Saudi education sector primary market. Full AR (RTL) + EN from day one.

## Stack
PHP 8.4-fpm, Laravel 12, Livewire 3, Alpine 3, Tailwind 4, MySQL 8.0, Redis 7, Sanctum 4, Horizon 5, Nginx alpine, Docker.

> **Shared infra (`_infra/`):** MySQL, Redis, phpMyAdmin, Mailpit. **Never touch. Never change versions.** This app connects to existing services — it does not spin up its own.

## Commands
```bash
# Always run inside the app container — never bare php/composer/npm on host
docker compose up -d --build                               # Start stack
docker compose exec app php artisan migrate:fresh --seed   # Reset DB
docker compose exec app php artisan test                   # Pest test suite
docker compose exec app php artisan test --filter=Name     # Single test
docker compose exec app vendor/bin/pint                    # Format
docker compose exec app php artisan horizon                # Queue worker (dev)
docker compose exec app php artisan app:create-superuser   # SuperUser (seeded only)
```

## Architecture — Modular Monolith

### Module Layout
All domain code lives under `app/Modules/`. One Laravel app, one DB, but code is organized by business domain. See `SPEC.md §2.1` for the full list.

```
app/Modules/
  Shared/      # Kernel: User, permissions, base traits, middleware
  Auth/ Tickets/ Assignment/ Escalation/ Communication/
  SLA/ CSAT/ Precedent/ Reporting/ Admin/ Audit/
```

### Module Communication Rules — STRICT
1. **No cross-module model imports.** Module A talks to Module B via its service interface or via events. Never `use App\Modules\B\Models\Something` from Module A.
2. **Shared is the only universal dependency.** All modules may depend on `Shared/`. `Shared/` depends on nothing.
3. **No circular deps.** If A depends on B, B must NOT depend on A. Break cycles with events.
4. **Each module owns** its migrations, routes, translations, Livewire components, service provider.
5. **Migrations prefixed by module** for ordering clarity.

## Non-Negotiable Invariants — Do NOT Violate

1. **`TicketStateMachine` is the ONLY path to change `tickets.status`.** No direct column updates, no `$ticket->status = 'x'; $ticket->save()` anywhere. Invalid transitions throw. (`SPEC.md §7.4`)
2. **ULID-only in routes, APIs, and form params.** `display_number` (TKT-0000001) is UI/email text only — never accepted as a route parameter. Accepting it = ticket enumeration vulnerability. (`SPEC.md §2.3, §7.2`)
3. **All rich text sanitized server-side before storage.** Whitelist-based purifier. Ticket descriptions, comments, resolution steps, condition reports. Client-side validation is UX only. (`SPEC.md §3.2`)
4. **Search goes through `SearchServiceInterface`.** Never call MySQL FULLTEXT directly from controllers/Livewire. V1 uses `MySqlSearchDriver`; V2 swaps to Meilisearch via the same interface. (`SPEC.md §2.3, §11.4`)
5. **Auth goes through `AuthProviderInterface`.** V1 is `EmailPasswordAuthProvider`. V2 SSO swaps drop-in. No auth logic in controllers. (`SPEC.md §2.3, §6.6`)
6. **Every user-facing string through `__()` or `@lang`.** No hardcoded strings. Ever. Both `ar/*.php` and `en/*.php` files updated together. (`SPEC.md §4.2`)

## Database Conventions

- **Primary keys:** ULID via `HasUlids` trait. Stored as `char(26)`.
- **Dual policy:** `is_active` (temporary disable, reversible) + `deleted_at` (permanent, historical reference only). See `SPEC.md §2.3` for which tables use which.
- **Query rule:**
    - "Available options" (dropdowns, new forms) → `WHERE is_active = true AND deleted_at IS NULL`
    - "Display existing data" (ticket detail showing its category) → NO filter, show historical value.
- **FKs have explicit ON DELETE.** Never implicit. Pattern map in `SPEC.md §5.1`.
- **Bilingual columns:** Admin-managed entities have `name_ar` + `name_en` (both NOT NULL).
- **Indexes:** Every FK indexed explicitly. Composite indexes per `SPEC.md §5.3`.
- **Table naming:** snake_case plural. Pivots: singular, alphabetical (`group_user`, `permission_user`).

## Localization & RTL

- Default locale: `ar`. `users.locale` column persists preference.
- Locale set per-request via middleware from authenticated user's preference.
- Translation files organized by module domain: `resources/lang/{ar,en}/tickets.php`, etc.
- **CSS logical properties only.** Use `ms-*`, `me-*`, `ps-*`, `pe-*`, `start-*`, `end-*`. Never `ml-*`, `mr-*`, `left-*`, `right-*` for directional spacing.
- `<html dir>` and `<html lang>` set from middleware.
- Directional icons (arrows, chevrons) flip in RTL.
- Numbers and dates: Western Arabic numerals (0-9) in both locales.
- User-generated content not translated — UI language only affects system chrome.

## Security — Apply to Every Feature

- **File uploads:** MIME via magic bytes (not extension), size enforced server-side (10MB), count server-side (5/action), stored outside web root with ULID filename, served via authorized controller route. EXIF stripped, images resized to 2048px max edge, compressed 80% JPEG, original discarded. (`SPEC.md §3.4`)
- **Rate limits (Redis-backed):** Login 5/min per IP+email, registration 3/hr per IP, password reset 3/hr per email, ticket create 10/hr per user, comments 30/hr per user, uploads 20/hr per user, general API 60/min per user. (`SPEC.md §3.5`)
- **Authorization everywhere.** Controller middleware AND Livewire component checks AND Eloquent global scopes for employee visibility. Never rely on frontend hiding.
- **Headers via middleware:** CSP, X-Content-Type-Options, X-Frame-Options, HSTS, Referrer-Policy, Permissions-Policy. (`SPEC.md §3.7`)
- **CSRF on all state changes.** Sessions: HttpOnly + Secure + SameSite=Lax, Redis-backed, regenerated on login.
- **Never log passwords.** Never return them in responses. Never include in audit before/after JSON.

## Testing — Gate, Not Afterthought

- Framework: **Pest**
- Location: `tests/Feature/`, `tests/Unit/`
- Every task has tests as part of acceptance — no exceptions.
- Run before every commit: `docker compose exec app php artisan test`
- Feature tests cover: routes + middleware + permissions + state transitions + validation + sanitization.
- Unit tests cover: services, state machine transitions, SLA calculations.

## Commit Discipline

- After completing a task: summarize changes, propose commit message, **wait for explicit approval** ("approve" / "yes" / "commit") before running `git commit`.
- Never commit directly to `main`.
- Branch naming: `feature/phase-N-task-X-Y-short-name`, `fix/short-name`.
- One task = one commit (unless task is split 1a/1b — then 2 commits).

## What Claude Code Must NOT Do

- **Do not modify** `_infra/` or anything under it.
- **Do not bypass** `TicketStateMachine` with direct status updates.
- **Do not accept** `display_number` as a route parameter.
- **Do not hardcode** user-facing strings.
- **Do not render** unsanitized rich text.
- **Do not import** another module's models directly — use events or service interfaces.
- **Do not add** real-time features, WebSockets, or chart libraries — deferred to V2.
- **Do not install** packages without surfacing them in the task summary for approval.
- **Do not run** `migrate:fresh` on anything the user hasn't explicitly asked for.
- **Do not commit** until told.

## Reference
`SPEC.md` is the single source of truth. When a task file cites a section (e.g., `§6.3`), open that section in `SPEC.md` — do not infer.
