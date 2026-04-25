# Task 9.3 — Resolution Linking (Existing Resolution Path)

## Context
Instead of writing new steps, a tech can link to an existing resolution. This is an alternative path inside the same modal from Task 9.2, with XOR validation between the two paths.

## Task
Extend the `Precedent/ResolveModal` Livewire component to support linking an existing resolution, enforcing XOR logic and atomically incrementing `usage_count`.

## Requirements
- "Link existing resolution" toggle inside the same modal (replaces `steps_taken` form section when active)
- When linking mode is active: show a searchable list of resolutions from tickets with matching `category_id` + `subcategory_id` (same query as auto-suggest §14.3); display summary + resolution type badge + usage count for each candidate
- `link_notes` optional text field available in linking mode
- XOR validation: if `linked_resolution_id` is set, `steps_taken` must be null/empty and vice versa; both present or both absent (when `steps_taken` required) → validation error
- On submit (linking path): DB transaction — `TicketStateMachine::transition` + `Resolution::create([linked_resolution_id => ..., link_notes => ...])` + `DB::table('resolutions')->increment('usage_count')` on the target row; all atomic
- `usage_count` increment must be atomic (use `increment()` or raw SQL `+1`, not read-then-write)
- `linked_resolution_id` stored as ULID; target resolution looked up by ULID — no `display_number`
- All new strings through `__()`

## Do NOT
- Allow `steps_taken` and `linked_resolution_id` to both be set on the same row
- Increment `usage_count` outside of the transaction
- Skip `TicketStateMachine` for the transition

## Acceptance
- Feature tests:
  - Submitting with `linked_resolution_id` set and `steps_taken` empty creates `Resolution` row with FK and increments target's `usage_count` by 1
  - Submitting with both `linked_resolution_id` and `steps_taken` populated returns XOR validation error
  - Submitting with neither (when required) returns validation error
  - If the selected resolution is later deleted (SET NULL), existing linked rows are not broken (FK nullable confirmed)
  - Concurrent increment test: two simultaneous link submissions both increment correctly (atomic DB increment)
  - Non-existent `linked_resolution_id` ULID → validation error

## Reference
SPEC.md §14.2, §14.1, §7.4
