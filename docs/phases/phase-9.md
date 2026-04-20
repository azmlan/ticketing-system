# Phase 9 — Precedent System

**Spec reference:** [SPEC.md §14](../../SPEC.md#14-phase-9--precedent-system)
**Deliverable:** Structured resolution capture on resolve, auto-suggest past resolutions, and resolution linking with usage counting.
**Exit condition:** All items in [§14.4 Phase 9 Acceptance Criteria](../../SPEC.md#144-phase-9-acceptance-criteria) pass.

## Tasks

- [ ] **Task 9.1** — `resolutions` migration/model/factory with UNIQUE on `ticket_id`, `resolution_type` enum (`known_fix/workaround/escalated_externally/other`), self-referencing `linked_resolution_id` FK (ON DELETE SET NULL) with index, `usage_count` default 0; schema + relationship tests.
- [ ] **Task 9.2** — Resolution capture modal Livewire triggered on `in_progress → resolved` transition; blocks completion until filled; fields: summary (required), root_cause (optional), steps_taken (sanitized rich text, required), parts_resources (optional), time_spent_minutes (optional), resolution_type (required dropdown); integrates with `TicketStateMachine` so transition is atomic with resolution row creation; feature tests including blocked resolution without form.
- [ ] **Task 9.3** — Linking alternative in the same modal: tech can pick an existing resolution instead of writing steps; linking increments `usage_count` on the target row atomically; XOR validation (cannot submit both `steps_taken` and `linked_resolution_id`); feature tests for both paths.
- [ ] **Task 9.4** — Auto-suggest collapsible panel on ticket detail: queries resolutions from resolved tickets with matching `category_id` + `subcategory_id` (exact match), sorted `usage_count DESC, created_at DESC`; displays summary, resolution type badge, truncated steps, resolved date, usage count badge, custom field values from source ticket for context; RTL-correct layout; feature tests over sort + filter behavior.

## Session Groupings

| Session | Tasks | Rationale |
|---------|-------|-----------|
| S1 | 9.1 | Schema + self-FK indexing, isolated. |
| S2 | 9.2, 9.3 | Capture and linking share the same modal + XOR validation rules; one session. |
| S3 | 9.4 | Auto-suggest panel + context panel is self-contained UI surface. |

## Acceptance Gate (from SPEC §14.4)

- [ ] Resolution form appears on resolve and blocks completion until filled
- [ ] All resolution fields work, including rich text steps (sanitized)
- [ ] Resolution linking works: suggestions appear, linking increments usage count
- [ ] Auto-suggest panel shows relevant resolutions on ticket detail
- [ ] Suggestions sorted correctly (usage count DESC, then date DESC)
- [ ] Linked resolutions show relationship in ticket detail view
- [ ] Resolution type dropdown works with all 4 options
- [ ] Custom field values from original ticket visible in resolution context
