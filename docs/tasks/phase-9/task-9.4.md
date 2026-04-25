# Task 9.4 — Auto-Suggest Panel on Ticket Detail

## Context
When a tech views an open ticket, a collapsible panel surfaces past resolutions from tickets with the same category + subcategory, helping them resolve faster without leaving the ticket.

## Task
Build the `Precedent/AutoSuggestPanel` Livewire component and embed it in the ticket detail view.

## Requirements
- Livewire component `Precedent/AutoSuggestPanel` accepting `$ticket` (ULID-keyed)
- Query: `resolutions` joined through `tickets` WHERE `tickets.category_id = :cat AND tickets.subcategory_id = :subcat AND tickets.status = 'resolved'` — excludes closed/cancelled per §14.3
- Sort: `resolutions.usage_count DESC, resolutions.created_at DESC`
- Each result card shows per §14.3: summary, `resolution_type` badge (translated label), `steps_taken` truncated to ~200 chars with "show more" toggle, resolved date, `usage_count` badge
- Custom field values from the source ticket displayed beneath each card for context (query `custom_field_values` for that ticket_id)
- Collapsible panel — collapsed by default, Alpine `x-show` toggle; remembers open state in Alpine `$persist` (localStorage)
- Zero results: show empty state message (localized), do not hide the panel toggle
- Panel only visible to techs + IT managers; hidden from employees entirely
- RTL-correct layout: `ps-*`/`pe-*`/`ms-*`/`me-*` only; badges and counts align correctly in both directions
- All strings through `__()`

## Do NOT
- Call MySQL FULLTEXT directly — this is a filtered relational query, but still route through a service method for testability
- Show cancelled/closed ticket resolutions
- Accept `display_number` in any query parameter
- Use `left`/`right`/`ml`/`mr` CSS properties

## Acceptance
- Feature tests:
  - Panel renders only for techs/managers; employee sees nothing
  - Results include only `resolved` status tickets matching category + subcategory exactly
  - Results exclude tickets from a different category even if subcategory matches
  - Sort order: higher `usage_count` first; same count sorted by `created_at` DESC
  - Custom field values from source ticket appear on the result card
  - Zero matching resolutions → empty state rendered without errors
  - Panel renders correctly when `subcategory_id` is null (no results returned, no crash)

## Reference
SPEC.md §14.3, §14.4
