# Task 8.5 — Custom Fields Admin CRUD

## Context
`custom_fields`, `custom_field_options`, and `custom_field_values` were migrated in task 8.1. This task builds the full admin UI for managing custom fields including all 6 types, scoping, ordering, and dropdown option management.

## Task
Build Livewire admin components for creating, editing, reordering, and soft-deleting custom fields and their dropdown options.

## Requirements
- `app/Modules/Admin/Livewire/CustomFields/CustomFieldList.php`: paginated list, search, `display_order` drag-reorder (Livewire event on drop updates all `display_order` values in one query), toggle `is_active`, soft-delete. (§13.1, §13.2)
- `CustomFieldForm.php`: bilingual `name_ar`/`name_en`, `field_type` select (text, number, dropdown, multi_select, date, checkbox), `scope_type` select (global / category); when scope is `category`, show category dropdown (active, non-deleted); `is_required`; on create sets `version = 1`; on edit bumps `version`. (§13.2, §13.3)
- `CustomFieldOptionList.php` (inline, shown when `field_type` is dropdown or multi_select): add / edit / reorder / soft-delete options; bilingual `value_ar`/`value_en`, `sort_order`. (§13.2)
- Versioning: changing `field_type` on an existing field is **blocked** if any `custom_field_values` reference it — show validation error. (§13.3)
- Deactivated fields: hidden from new ticket forms; existing ticket-detail views display the stored value read-only. (§13.3)
- Soft-deleted fields: same behavior as deactivated; `deleted_at` set via soft-delete, not recoverable from UI. (§13.3)
- Permission: `system.manage-custom-fields` checked in every component mount and write action. (§13.1)
- All strings through `__()`. (§4.2)

## Do NOT
- Do not allow `field_type` change when the field has existing values.
- Do not hard-delete options that are referenced by `custom_field_values` — soft-delete only.
- Do not import `Ticket` model — cross-module data via `CustomFieldValue` model owned by Admin module.

## Acceptance
- Pest `tests/Feature/Admin/CustomFieldCrudTest.php`: create all 6 types; edit name bumps version; changing type on a field with values returns validation error; soft-delete hides field from active scope.
- Pest `tests/Feature/Admin/CustomFieldOptionTest.php`: add/remove options; reorder updates `sort_order`; soft-deleted option excluded from active scope.
- Unauthorized user gets 403 on all actions.
- Display order persists correctly after drag-reorder.

## References
- `SPEC.md §13.1` — custom fields section and permission
- `SPEC.md §13.2` — full schema for custom_fields, custom_field_options, custom_field_values
- `SPEC.md §13.3` — versioning and deactivation rules
- `SPEC.md §5.3` — composite index on custom_field_values
