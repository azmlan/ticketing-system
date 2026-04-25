# Task 8.4 — Groups Admin CRUD & Member Management

## Context
Groups and the `group_user` pivot were created in Phase 2. Phase 8 exposes full admin management: CRUD, adding/removing members, and assigning a group manager.

## Task
Build Livewire admin components for managing groups, their members, and group manager assignment.

## Requirements
- `app/Modules/Admin/Livewire/Groups/GroupList.php`: paginated table, search by name AR/EN, toggle `is_active`, soft-delete action. (§13.1)
- `GroupForm.php` (create/edit): bilingual `name_ar`/`name_en`, `is_active` toggle; manager dropdown searches active tech users; saving emits no cross-module event unless manager changes. (§13.1)
- `GroupMembers.php`: lists current members (name, role), add member by searching active employees and techs, remove member; requires `group.manage-members` permission. (§13.1)
- Manager assignment in `GroupForm`: `manager_id` must be an active tech user with a `TechProfile`; requires `group.manage-manager` permission. (§13.1)
- Permission split: `group.manage` for CRUD; `group.manage-members` for add/remove members; `group.manage-manager` for manager assignment — check in each component action. (§13.1)
- All strings through `__()`, keys in `admin.php`. (§4.2)

## Do NOT
- Do not import `Ticket` or `Assignment` module models.
- Do not soft-delete a group that still has open tickets assigned to it — show a validation error instead; the spec does not define auto-reassignment.
- Do not permanently delete groups.

## Acceptance
- Pest `tests/Feature/Admin/GroupCrudTest.php`: create, edit, deactivate, soft-delete; user without `group.manage` gets 403.
- Pest `tests/Feature/Admin/GroupMembersTest.php`: add/remove member updates `group_user` pivot; `group.manage-members` required; assigning a non-tech as manager is rejected.
- Group manager must have an active `TechProfile` — validated server-side.
- All labels localized; RTL layout correct.

## References
- `SPEC.md §13.1` — groups section, permission matrix
- `SPEC.md §5.1` — `group_user` pivot FK constraints
- `SPEC.md §2.3` — ULID-only routing
- `SPEC.md §6.3` — tech profile requirements
