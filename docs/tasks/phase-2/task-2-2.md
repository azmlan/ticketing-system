# Task 2.2 — Core Models & Relationships

## Context
Migrations exist. This task wires Eloquent models with correct traits, casts, relationships, and the employee global scope that gates ticket visibility.

## Task
Create Eloquent models for all Phase 2 tables in their respective modules, define all relationships, and apply the employee-visibility global scope to `Ticket`.

## Requirements
- Models and their module locations:
  - `app/Modules/Tickets/Models/Ticket.php` — `HasUlids`, `SoftDeletes`, status/priority cast to enum or string, global scope `EmployeeTicketScope` (employee sees only their own tickets; tech/manager sees all — enforce via `Shared` role check, not frontend hiding)
  - `app/Modules/Tickets/Models/TicketAttachment.php` — `HasUlids`, belongs to `Ticket`
  - `app/Modules/Tickets/Models/TransferRequest.php` — `HasUlids`, belongs to `Ticket`, `fromUser`, `toUser`
  - `app/Modules/Admin/Models/Category.php` — `HasUlids`, `SoftDeletes`, `is_active` scope, has many `Subcategory`, belongs to `Group`
  - `app/Modules/Admin/Models/Subcategory.php` — `HasUlids`, `SoftDeletes`, belongs to `Category`
  - `app/Modules/Admin/Models/Group.php` — `HasUlids`, `SoftDeletes`, `is_active` scope, `belongsToMany` User via `group_user`, has many `Category`
- `Ticket` relationships: `requester`, `assignedTo`, `category`, `subcategory`, `group`, `attachments`, `transferRequests`
- `EmployeeTicketScope`: applied automatically; employees scoped to `requester_id = auth()->id()`; roles with `ticket.view_all` permission bypass scope.
- No cross-module model imports. `Ticket` refers to `Category`/`Group` only via their Admin module — acceptable since Admin is a shared-data module (document this in a one-line comment if not obvious).
- `Category` and `Group` provide a `localizedName()` helper returning `name_ar` or `name_en` based on `app()->getLocale()`.
- Bilingual display columns (`name_ar`, `name_en`) cast as `string`, never guarded.

## Do NOT
- Do not put business logic or state transitions in models (that's `TicketStateMachine` in Task 2.3).
- Do not add admin CRUD UI — models only.
- Do not hardcode strings; any model-level labels go through `__()`.

## Acceptance
- Pest unit tests in `tests/Unit/Models/` cover: `EmployeeTicketScope` hides other users' tickets from an employee, tech bypasses scope, `localizedName()` returns correct locale, `Category` active scope filters correctly.
- `php artisan model:show Ticket` resolves without errors.
- Factory for `Ticket`, `Category`, `Subcategory`, `Group` present and usable in tests.

## References
- `SPEC.md §7.1` — column definitions and relationships
- `SPEC.md §2.3` — ULID, dual policy (is_active + deleted_at), query rules
- `SPEC.md §4.2` — bilingual column convention
