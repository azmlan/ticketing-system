# Task 1.6 — Permission Middleware + Blade Directive + Gate + SuperUser Bypass

## Context
Permissions exist as data (Task 1.3). Nothing currently enforces them. This task delivers the enforcement layer every later feature will rely on.

## Task
Wire Laravel Gates to the permission table, ship a `permission:<key>` route middleware and a `@permission` Blade directive, and implement the SuperUser global bypass.

## Requirements
- `AuthServiceProvider::boot()` (or a dedicated `PermissionServiceProvider` in `app/Modules/Shared/`) uses `Gate::before()` to short-circuit to `true` when `$user->is_super_user === true` — SuperUser bypasses every permission check per `SPEC.md §6.9`.
- `Gate::define('<key>', fn($user) => $user->hasPermission('<key>'))` registered dynamically for every key in `config/permissions.php` (loop the registry).
- `User::hasPermission(string $key): bool` method checking the `permission_user` relationship. Does NOT hit the Redis cache yet — Phase 10.4 adds caching (explicit TODO comment referencing §15.4 is fine).
- `PermissionMiddleware` registered as `permission` in `app/Http/Kernel.php`: usage `->middleware('permission:ticket.create')`. Returns 403 for authenticated users without the permission; redirects to login for guests.
- `@permission('<key>') ... @endpermission` Blade directive rendering only if `auth()->user()?->can('<key>')` is true. Also `@unlesspermission` for the negated form.
- Error pages: custom 403 view in `resources/views/errors/403.blade.php` with localized message, no stack trace.

## Do NOT
- Do not cache permission lookups in Redis here (Phase 10.4 owns that, invalidated on grant/revoke).
- Do not bypass checks for `is_tech=true` — tech status is not a permission substitute.
- Do not introduce a `roles` abstraction — permission keys are the only authorization primitive.
- Do not apply the middleware to routes yet beyond a test route — Phase 2+ apply it to real routes.
- Do not leak permission denial details in production (403 shows generic copy; audit logging of denials lands in Phase 10).

## Acceptance
- Pest feature tests in `tests/Feature/Authorization/`:
  - User with permission `X` passes `permission:X` middleware on a test route (200).
  - User without permission `X` hits `permission:X` → 403 with generic view.
  - Guest hits permission-gated route → redirect to login.
  - SuperUser (`is_super_user=true`) with zero `permission_user` rows passes `permission:anything` on every registered key.
  - `@permission('X')` Blade block renders when permission present, omitted when absent; inverse for `@unlesspermission`.
  - Granting then revoking permission is immediately reflected in the same request-cycle test (cache concern deferred).
  - `Gate::allows('<every key from config>')` resolves without throwing for an authenticated user.

## References
- `SPEC.md §6.3` — permission registry
- `SPEC.md §6.9` — SuperUser bypass semantics
- `SPEC.md §3.7` — authorization enforcement layers
- `CLAUDE.md` — Security (authorization everywhere)
