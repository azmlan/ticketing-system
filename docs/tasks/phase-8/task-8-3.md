# Task 8.3 — Categories & Subcategories Admin CRUD

## Context
Categories and subcategories were migrated in Phase 2. Phase 8 exposes their full admin management UI with group mapping, subcategory configurability, and versioning.

## Task
Build Livewire admin components for listing, creating, editing, and soft-deleting categories and their subcategories.

## Requirements
- `app/Modules/Admin/Livewire/Categories/CategoryList.php`: paginated table, search by name AR/EN, filter active/inactive, soft-delete action (sets `deleted_at`), toggle `is_active`. (§13.1, §13.3)
- `CategoryForm.php` (shared create/edit): bilingual `name_ar`/`name_en`, group dropdown (active groups only), `is_active` toggle; on save bumps `version`. (§13.2, §13.3)
- `SubcategoryList.php` scoped to a category: paginated, search, toggle active, soft-delete.
- `SubcategoryForm.php`: bilingual names, `is_required` toggle (marks subcategory selection required on ticket form), `is_active`.
- Dropdowns on ticket-create forms use `WHERE is_active = true AND deleted_at IS NULL`; ticket-detail views show historical category name without filter. (§2.3)
- Versioning: each save to category or subcategory increments `version`; no migration of existing tickets' category references. (§13.3)
- Authorization gate: `category.manage` permission checked in each Livewire component mount and write actions. (§13.1)
- All labels, validation messages, and flash notifications through `__()`. Translation keys in `admin.php`. (§4.2)

## Do NOT
- Do not permanently delete categories — soft-delete only.
- Do not update any `tickets` rows when a category is renamed or deactivated.
- Do not import Ticket model into Admin module — use events or service interface if cross-module data is needed.

## Acceptance
- Pest `tests/Feature/Admin/CategoryCrudTest.php`: create, edit, deactivate, soft-delete flow; `version` increments on each edit; unauthorized user gets 403.
- Pest `tests/Feature/Admin/SubcategoryCrudTest.php`: subcategory scoped to category; `is_required` persists correctly.
- Active + non-deleted categories appear in ticket-create category dropdown; deactivated/deleted ones do not.
- Layout RTL-correct; all strings localized.

## References
- `SPEC.md §7.2` — category/subcategory on ticket form
- `SPEC.md §13.1` — admin section and permission
- `SPEC.md §13.3` — versioning and deactivation rules
- `SPEC.md §2.3` — ULID routing, dual-policy query rules
- `SPEC.md §5.1` — FK constraints on categories/subcategories
