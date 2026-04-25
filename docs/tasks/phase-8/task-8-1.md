# Task 8.1 — Database: Custom Fields, Tags, Response Templates, App Settings

## Context
Phase 8 introduces 7 new tables that power the admin configuration panel. All are required before any admin UI can be built.

## Task
Create migrations and models for `custom_fields`, `custom_field_options`, `custom_field_values`, `tags`, `ticket_tag`, `response_templates`, and `app_settings`.

## Requirements
- Migration `custom_fields`: all columns per §13.2 — ULID PK, enum `field_type` (text, number, dropdown, multi_select, date, checkbox), enum `scope_type` (global, category), `scope_category_id` FK ON DELETE CASCADE NULLABLE, `is_active`, `version`, `SoftDeletes`, explicit FK indexes. (§13.2, §5.1, §5.3)
- Migration `custom_field_options`: ULID PK, FK `custom_field_id` ON DELETE CASCADE, bilingual `value_ar`/`value_en`, `sort_order`, `is_active`, `SoftDeletes`.
- Migration `custom_field_values`: ULID PK, `ticket_id` FK ON DELETE CASCADE, `custom_field_id` FK ON DELETE RESTRICT, `value` text NULLABLE; composite index on `(ticket_id, custom_field_id)`. (§13.2)
- Migration `tags`: ULID PK, `name_ar`/`name_en` varchar(100), `color` varchar(7) NULLABLE, `is_active`, `SoftDeletes`.
- Migration `ticket_tag` pivot: `ticket_id` FK ON DELETE CASCADE, `tag_id` FK ON DELETE CASCADE, UNIQUE `(ticket_id, tag_id)`, no timestamps.
- Migration `response_templates`: ULID PK, `title_ar`/`title_en`, `body_ar`/`body_en` text, `is_internal` bool DEFAULT true, `is_active`, `SoftDeletes`.
- Migration `app_settings`: ULID PK, `key` varchar(100) UNIQUE NOT NULL, `value` text NULLABLE. Seed the 9 default keys from §13.4 with their default values.
- Models in `app/Modules/Admin/Models/`: `CustomField`, `CustomFieldOption`, `CustomFieldValue`, `Tag`, `ResponseTemplate`. `AppSetting` model with static helper `get(string $key, mixed $default = null)` and `set(string $key, mixed $value)`.
- All models: `HasUlids`, fillable, casts, relationships, `scopeActive()`, `localizedName()` where bilingual.

## Do NOT
- Do not build any Livewire components or admin UI here.
- Do not touch existing migrations from Phases 1–7.
- Do not add `ticket_tag` relationship to `Ticket` model (ticket module concerns come in task 8.6).

## Acceptance
- `php artisan migrate` runs cleanly; all 7 tables exist with correct schema.
- Pest `tests/Unit/Admin/CustomFieldModelTest.php`: `scopeActive()` filters correctly; `field_type` and `scope_type` casts work; relationship to options loads.
- Pest `tests/Unit/Admin/AppSettingTest.php`: `AppSetting::get()` returns default when key missing; `AppSetting::set()` upserts correctly.
- Pest `tests/Unit/Admin/TagModelTest.php`: `scopeActive()` filters; tag attaches to ticket via pivot.
- Pest `tests/Unit/Admin/ResponseTemplateModelTest.php`: `scopeActive()` filters; `is_internal` cast works.

## References
- `SPEC.md §13.2` — full table schemas, custom_field types, versioning rules
- `SPEC.md §13.4` — app_settings keys and defaults
- `SPEC.md §5.1` — FK ON DELETE patterns
- `SPEC.md §5.3` — composite index requirements
- `SPEC.md §2.3` — ULID PK convention
