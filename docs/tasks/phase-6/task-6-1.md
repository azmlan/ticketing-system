# Task 6.1 — SearchServiceInterface + MySqlSearchDriver

## Context
`SearchServiceInterface` is a protected invariant (CLAUDE.md). All dashboard search must resolve through it — never raw FULLTEXT from components or controllers.

## Task
Define `SearchServiceInterface`, implement `MySqlSearchDriver` using MySQL FULLTEXT, add FULLTEXT migrations, and bind via service provider.

## Requirements
- Interface at `app/Modules/Shared/Contracts/SearchServiceInterface.php` with `search(string $query, array $filters = [], string $sort = 'created_at', string $direction = 'desc'): LengthAwarePaginator` per §11.4.
- FULLTEXT migrations on `tickets.subject`, `tickets.description`, and `comments.body`; prefix `2026_06_*`.
- `MySqlSearchDriver` in `app/Modules/Shared/Search/` executes FULLTEXT via `MATCH … AGAINST`; falls back to `LIKE` when query is empty.
- `$filters` must support `status`, `requester_id`, `assigned_to`, `category_id`, `group_id`, `priority`, `date_from`, `date_to`.
- Service provider binding: `app->bind(SearchServiceInterface::class, MySqlSearchDriver::class)`.
- Respects Eloquent global scopes (employee visibility) — driver receives a base query, not raw DB.

## Do NOT
- Do not call FULLTEXT from any Livewire component or controller directly.
- Do not implement `MeilisearchDriver` — that is V2.
- Do not seed or migrate anything from earlier phases.
- Do not add columns to `tickets` or `comments` not in §5.3.

## Acceptance
- Pest `tests/Unit/Search/MySqlSearchDriverTest.php`: query with results, empty query falls back to LIKE, unknown filter key ignored gracefully.
- Pest `tests/Feature/Search/SearchServiceBindingTest.php`: resolving `SearchServiceInterface` from container returns `MySqlSearchDriver`.
- `SHOW INDEX FROM tickets` includes FULLTEXT on `subject` and `description`; same for `comments`.
- Passing an empty `$query` returns all tickets (no FULLTEXT syntax error).

## References
- `SPEC.md §11.4` — interface contract + V1 driver spec
- `SPEC.md §5.3` — indexing strategy
- `SPEC.md §2.3` — global scopes for employee visibility
