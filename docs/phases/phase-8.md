# Phase 8 — Admin Configuration Panel

**Spec reference:** [SPEC.md §13](../../SPEC.md#13-phase-8--admin-configuration-panel)
**Deliverable:** Central admin area for managing all configurable entities.
**Exit condition:** All items in [§13.5 Phase 8 Acceptance Criteria](../../SPEC.md#135-phase-8-acceptance-criteria) pass.

## Tasks

- [x] **Task 8.1** — Admin shell: layout, section navigation dynamically gated per §13.1 permission matrix; Categories & Subcategories CRUD (with `group_id` mapping, subcategory `is_required`, `version` bump on change per §13.3); soft-delete/deactivate behavior; feature tests covering versioning rules.
- [x] **Task 8.2** — Groups CRUD (bilingual name, `is_active`, soft-delete) with member management (add/remove techs from `group_user`, enforced by `group.manage-members`) and Group Manager assignment (`manager_id`, enforced by `group.manage-manager`); feature tests per permission.
- [x] **Task 8.3** — `custom_fields` + `custom_field_options` + `custom_field_values` migrations/models; Custom Fields CRUD Livewire supporting all 6 types (text/number/dropdown/multi_select/date/checkbox), scope global vs per-category, required flag, display_order, options CRUD for dropdown types, soft-delete, `version` bump; feature tests.
- [x] **Task 8.4** — Custom field rendering in ticket create/detail/edit: respects scope (global shown always, category-scoped shown when matching category selected), writes `custom_field_values` rows, versioning rules (existing tickets retain original values, new tickets see current definitions); feature tests covering `is_active=false` + soft-deleted fields in existing tickets.
- [ ] **Task 8.5** — SLA Targets CRUD (one row per priority, `response_target_minutes`, `resolution_target_minutes`, `use_24x7`); business hours + working days config + `sla_warning_threshold` form writing to `app_settings`; `system.manage-sla` gating; feature tests.
- [ ] **Task 8.6** — Tags CRUD (bilingual + hex color + `is_active` + soft-delete) with `ticket_tag` pivot binding on ticket detail; Response Templates CRUD (bilingual title + body, `is_internal` default) + usage integration in comments component from Phase 4; feature tests.
- [ ] **Task 8.7** — Departments + Locations CRUD (bilingual name, `sort_order`, `is_active`, soft-delete); both consumed from Phase-1 profile form and Phase-2 ticket creation form; feature tests.
- [ ] **Task 8.8** — User Management admin: list users with filters, view tech profile, promote employee (`user.promote`) reusing Phase-1 promotion flow, grant/revoke permissions (`user.manage-permissions`) with Redis cache invalidation per Phase 10.4; feature tests per permission.
- [ ] **Task 8.9** — Tenant Branding Livewire (IT Manager only): `company_name`, logo upload (image pipeline from Phase 2), `primary_color`, `secondary_color` written to `app_settings`; base layout reads `app_settings` and applies company name in header + logo + CSS custom properties for colors; feature tests + visual smoke.

## Session Groupings

| Session | Tasks | Rationale |
|---------|-------|-----------|
| S1 | 8.1 | Admin shell + Categories CRUD establishes navigation pattern used by all subsequent sections. |
| S2 | 8.2, 8.7 | Groups + Departments/Locations are similar bilingual CRUDs; share code patterns. |
| S3 | 8.3 | Custom fields schema + CRUD is complex (6 types, options, scoping, versioning) — alone. |
| S4 | 8.4, 8.5 | Custom field rendering on tickets + SLA config — both consume schemas from prior sessions. |
| S5 | 8.6, 8.8 | Tags + Response Templates (small CRUDs) paired with User Management for session balance. |
| S6 | 8.9 | Tenant branding closes the phase; touches layout and `app_settings`. |

## Acceptance Gate (from SPEC §13.5)

- [ ] All admin sections accessible with correct permission checks
- [ ] Categories: CRUD with group mapping, subcategory required/optional, versioning
- [ ] Groups: CRUD with member management, group manager assignment
- [ ] Custom fields: all 6 types work, scoping, versioning, soft-delete
- [ ] Tags: CRUD with color, active/inactive
- [ ] Response templates: CRUD with bilingual content and default type
- [ ] SLA targets configurable per priority, business hours config, warning threshold
- [ ] Departments and locations: CRUD with bilingual names and sort order
- [ ] User management: promote employee, create tech profile, grant/revoke permissions
- [ ] Tenant branding: company name, logo, colors applied to layout
- [ ] All admin views fully localized (AR/EN) and RTL-correct
