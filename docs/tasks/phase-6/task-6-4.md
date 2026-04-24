# Task 6.4 — Tech Dashboard Filter Bar + Saved Group Preferences

## Context
Task 6.3 delivered the tech dashboard layout. This task wires the full filter bar and persists group selections server-side via a `user_preferences` JSON column (or dedicated table).

## Task
Add all tech dashboard filters per §11.5, implement server-side preference persistence for group selections, and ensure all filters work in combination.

## Requirements
- Add `preferences` JSON column to `users` table (migration `2026_06_*`); nullable, no default.
- Filter bar as Livewire properties on `TechDashboard`: status (multi), priority (multi), category (single), subcategory (dependent dropdown), group (multi), assigned_to (dropdown + "Unassigned" option), date_from/date_to, free-text search via `SearchServiceInterface` per §11.5.
- Group selection auto-saved to `users.preferences->tech_dashboard.groups` on change; restored on component mount.
- Subcategory dropdown empties and reloads when category changes.
- Free-text search uses `SearchServiceInterface` with all active filters passed as `$filters`.
- Sort options: `created_at desc/asc`, `priority desc/asc`, `updated_at desc` — applied to both queue and my-tickets lists.
- All filter combinations must produce correct intersected results (no OR leakage between separate filters).

## Do NOT
- Do not store preferences in a cookie or localStorage — server-side only.
- Do not create a dedicated preferences table unless the JSON column approach is technically blocked; note the decision.
- Do not call FULLTEXT directly.
- Do not add pagination (Task 6.6).

## Acceptance
- Pest `tests/Feature/Phase6/TechDashboardFilterTest.php`:
  - Each filter tested in isolation returns correct subset.
  - Two filters combined (e.g., status + priority) return intersection.
  - Group preference saved in DB and restored on next mount.
  - Subcategory resets when category changes.
  - Date range filter excludes tickets outside the range.
- `users.preferences` column exists in schema after migration.

## References
- `SPEC.md §11.5` — full filter spec + sort options
- `SPEC.md §11.2` — group persistence requirement
- `SPEC.md §11.4` — search usage
- `SPEC.md §5.1` — migration FK patterns
