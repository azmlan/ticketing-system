# Task 8.8 — User Management Admin

## Context
Users, tech profiles, and the permissions system exist from Phase 1. The Auth module has a `PromoteToTech` Livewire component stub. Phase 8 consolidates all user management into the admin panel.

## Task
Build the admin user management section: list users, promote employees to tech, and grant or revoke permissions.

## Requirements
- `app/Modules/Admin/Livewire/Users/UserList.php`: paginated user table (name, email, role, department, active status), search by name/email, filter by role (employee/tech/IT manager), filter active/inactive. Permission: `user.promote` or `user.manage-permissions` (either grants read access). (§13.1)
- `UserDetail.php`: view a single user's profile, current permissions list, tech profile status. Actions available depend on caller's permissions.
- Promote to tech action (requires `user.promote`): creates a `TechProfile` record if none exists, sets `users.role = 'tech'`; show confirmation modal; emits `UserPromotedToTech` event. (§6.3, §13.1)
- Permission grant/revoke panel (requires `user.manage-permissions`): lists all permissions from `permissions` table, checkboxes for the viewed user, saves diff to `permission_user` pivot; IT Manager cannot have their own permissions revoked via this UI. (§6.5, §13.1)
- IT Manager role is set at deployment time only — this UI cannot promote a user to IT Manager. Show informational note. (§6.5)
- All user-facing strings through `__()`. (§4.2)

## Do NOT
- Do not allow bulk permission changes — one user at a time.
- Do not expose passwords, hashed or otherwise, in any response or log.
- Do not import models from Tickets, Assignment, or other domain modules.

## Acceptance
- Pest `tests/Feature/Admin/UserManagementTest.php`: list filters work; user without `user.promote` or `user.manage-permissions` gets 403; promoting creates `TechProfile` and updates role; `UserPromotedToTech` event dispatched.
- Pest `tests/Feature/Admin/PermissionManagementTest.php`: grant adds to `permission_user`; revoke removes; revoking IT Manager's own permissions is blocked; `user.manage-permissions` enforced.
- Promoted tech immediately appears in group member search and ticket assignment flows.

## References
- `SPEC.md §6.3` — tech profile schema
- `SPEC.md §6.5` — permissions and roles
- `SPEC.md §13.1` — user management admin section and permissions
- `SPEC.md §2.3` — ULID-only routing (user ULID in route, not email)
