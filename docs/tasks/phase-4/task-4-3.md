# Task 4.3 — Comments UI

## Context
The `Comment` model and `InternalCommentScope` exist. This task builds the `AddComment` Livewire component that lets techs post public or internal comments on a ticket, with optional response template pre-fill.

## Task
Build the `AddComment` Livewire component, integrate it into the ticket detail view, and enforce all visibility and rate-limiting rules.

## Requirements
- Livewire component `app/Modules/Communication/Livewire/AddComment.php` + Blade view.
- Receives `$ticketUlid` as a mount parameter — resolves ticket via service, never imports Ticket model directly.
- Fields: `body` (rich text, required, max 10,000 chars per §3.1, sanitized server-side via §3.2 purifier before storage), `is_internal` (boolean toggle, DEFAULT true per §9.2).
- Toggle UI: visible to techs only. Employees cannot post internal comments and the toggle is not rendered for them.
- Response template selector: dropdown of `ResponseTemplate::active()` records, localized title (AR or EN per authenticated user's locale). On selection, pre-fills `body` with the template's locale-appropriate `body_*` field and sets `is_internal` to match the template's default. Tech edits before posting.
- On submit: enforce rate limit 30 comments/hr per user (Redis-backed, `config/rate_limits.php`) per §3.5. Persist sanitized `body`, `is_internal`, `user_id`, `ticket_id`. Dispatch `CommentCreated` event (used by notification engine in Task 4.4).
- Display existing comments in a timeline below the form. Employee view: only `is_internal = false` comments shown (enforced by `InternalCommentScope` at query level, not just Blade `@if`). Internal comments rendered with a distinct background and an "Internal" label (localized). Comments are never paginated in Phase 4 — load all.
- Authorization: `abort(403)` if unauthenticated. Employees may only post public comments; reject `is_internal = true` from an employee server-side (not just UI).
- All labels, placeholders, validation messages via `__()` keys in `resources/lang/{ar,en}/communication.php`.

## Do NOT
- Do not render comment `body` HTML without passing through the §3.2 sanitizer — stored content is pre-sanitized but render with `{!! $comment->body !!}` only.
- Do not hide internal comments from employees using only a Blade `@if` — the scope must filter at the query level.
- Do not fire notifications directly from the component — dispatch `CommentCreated` and let the listener handle it (Task 4.4).
- Do not paginate comments — full list in Phase 4.
- Do not import the Ticket model directly into the Livewire component.

## Acceptance
- Pest feature tests `tests/Feature/Phase4/CommentsTest.php`:
  - Tech posts internal comment → `is_internal = true` record created, `CommentCreated` fired.
  - Tech posts public comment → `is_internal = false` record created.
  - Employee posts comment → automatically stored as `is_internal = false` regardless of payload.
  - Employee POSTing with `is_internal = true` receives 403.
  - Employee fetching comment list never sees internal comments.
  - Body exceeding 10,000 chars returns validation error.
  - 31st comment within an hour returns 429.
  - Unsanitized HTML (e.g., `<script>`) is stripped before storage.
  - Template pre-fill: selecting a template sets body and `is_internal` to template defaults.

## References
- `SPEC.md §9.2` — comment types, visibility, template behavior
- `SPEC.md §3.1` — comment length limit (10,000 chars)
- `SPEC.md §3.2` — rich text sanitization whitelist
- `SPEC.md §3.5` — comment rate limit (30/hr per user)
- `SPEC.md §4.2` — localization requirement
