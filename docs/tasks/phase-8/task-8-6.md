# Task 8.6 — Tags & Response Templates Admin CRUD

## Context
`tags`, `ticket_tag`, and `response_templates` were migrated in task 8.1. This task also wires the `ticket_tag` relationship onto the Ticket model so tags can be attached during ticket management.

## Task
Build admin CRUD for tags and response templates, and expose the tag relationship on the Ticket model for use by the ticket detail view.

## Requirements
- `app/Modules/Admin/Livewire/Tags/TagList.php`: paginated, search, color swatch preview, toggle `is_active`, soft-delete. (§13.1)
- `TagForm.php`: bilingual `name_ar`/`name_en`, hex color picker (`color` varchar(7)); validate color as 7-char hex; `is_active`. Permission: `system.manage-tags`. (§13.1, §13.2)
- `app/Modules/Admin/Livewire/ResponseTemplates/ResponseTemplateList.php`: paginated, search by title AR/EN, filter by `is_internal`, toggle `is_active`, soft-delete. (§13.1)
- `ResponseTemplateForm.php`: bilingual `title_ar`/`title_en`, rich-text editor for `body_ar` and `body_en` (server-side sanitized on save, whitelist purifier per §3.2), `is_internal` toggle, `is_active`. Permission: `system.manage-response-templates`. (§13.1, §13.2)
- Add `tags()` BelongsToMany relationship to `app/Modules/Tickets/Models/Ticket.php` via `ticket_tag` pivot — this is the only cross-module touch allowed here; the pivot table is the boundary. (§2.1 module communication rules)
- All strings through `__()`, keys in `admin.php`. (§4.2)

## Do NOT
- Do not import `ResponseTemplate` model into the Tickets module — templates are fetched through an event or service interface when a tech selects one on a comment form.
- Do not render raw HTML from `body_ar`/`body_en` without sanitization.
- Do not permanently delete tags or templates.

## Acceptance
- Pest `tests/Feature/Admin/TagCrudTest.php`: create/edit/deactivate/soft-delete; invalid hex color rejected; `system.manage-tags` enforced.
- Pest `tests/Feature/Admin/ResponseTemplateCrudTest.php`: create/edit/deactivate/soft-delete; XSS in body is stripped on save; `system.manage-response-templates` enforced.
- `Ticket::tags()` relationship returns active tags attached via pivot.
- RTL layout correct; bilingual labels render.

## References
- `SPEC.md §13.1` — tags and response templates sections, permissions
- `SPEC.md §13.2` — full schema for tags, ticket_tag, response_templates
- `SPEC.md §3.2` — rich-text sanitization requirement
- `SPEC.md §9.2` — response templates used in comment composer
- `SPEC.md §2.1` — module communication rules
