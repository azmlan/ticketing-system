# Task 2.4 — Ticket Creation Form

## Context
Models and the state machine exist. This task builds the employee-facing Livewire form that creates tickets, generates the display number, and auto-assigns to the correct group.

## Task
Build the `CreateTicket` Livewire component with all required fields, display number generation via row-level lock, and group auto-assignment from the category mapping.

## Requirements
- Livewire component: `app/Modules/Tickets/Livewire/CreateTicket.php` + Blade view.
- Fields: Subject (text), Description (rich text — Trix or equivalent, sanitized server-side before storage — whitelist purifier, §3.2), Category (dropdown of active categories, localized name), Subcategory (conditional — loads on category change, hidden if category has none, required if `subcategory.is_required = true`), Location (text/dropdown per §7.3), Department (text/dropdown per §7.3), Attachments (handled in Task 2.5 — leave a wire:model hook here).
- Display number generation in a DB transaction with `lockForUpdate()` on `ticket_counters` per §7.2. Extracted to `TicketCounterService` — not inlined in the component.
- `group_id` auto-set from `categories.group_id` — never shown or editable by employee.
- `requester_id` = `auth()->id()`. `status` = `awaiting_assignment`. `incident_origin` = `web`. Priority not on form.
- Route: `GET /tickets/create` (employee middleware). ULID in all redirects — never `display_number`.
- Rate limit: 10 ticket creations per hour per user (Redis-backed, §3.5).
- All validation messages and field labels via `__()` with keys in `resources/lang/{ar,en}/tickets.php`.
- After successful creation redirect to `GET /tickets/{ulid}` (detail view — stub is fine in Task 2.4; full view in Task 2.7).

## Do NOT
- Do not set priority on ticket creation.
- Do not accept `location_id` / `department_id` as ULIDs yet — plain text fields are acceptable for Phase 2 if no Location/Department model exists (store as FK null, add a note).
- Do not render unsanitized description HTML anywhere.
- Do not use `display_number` as a route parameter.

## Acceptance
- Pest feature test `tests/Feature/Phase2/CreateTicketTest.php`:
  - Authenticated employee can POST valid data and ticket is created with correct `status`, `requester_id`, `group_id`.
  - `display_number` is formatted `TKT-0000001` and increments correctly on concurrent creation (use DB transaction + lock).
  - Missing required fields return validation errors.
  - Unauthenticated POST returns 302 to login.
  - 11th creation within an hour is rate-limited (429).
  - Description with `<script>` is stored stripped.
- Subcategory dropdown updates when category changes (Livewire wire test).

## References
- `SPEC.md §7.2` — display number generation and locking
- `SPEC.md §7.3` — ticket creation flow and field list
- `SPEC.md §3.2` — rich text sanitization
- `SPEC.md §3.5` — rate limits
- `SPEC.md §4.2` — localization requirement
