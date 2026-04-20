# Task 1.4 — AuthProviderInterface + Email/Password Auth + Rate Limits

## Context
User + permission schema exist. V1 uses email/password; V2 swaps to SSO via the same interface. This task enforces invariant #5 from CLAUDE.md (all auth flows through `AuthProviderInterface`).

## Task
Define `AuthProviderInterface`, bind `EmailPasswordAuthProvider` as the V1 implementation, and build Livewire registration / login / password-reset flows that go through the provider, with Redis-backed rate limits per `SPEC.md §3.5`.

## Requirements
- `app/Modules/Auth/Contracts/AuthProviderInterface.php` with methods: `register(array $data): User`, `attempt(string $email, string $password): bool`, `logout(): void`, `sendPasswordReset(string $email): void`, `resetPassword(string $token, string $newPassword): bool`.
- `app/Modules/Auth/Providers/EmailPasswordAuthProvider.php` implementing the interface. Binding in `AuthServiceProvider` (or module service provider).
- Livewire components in `app/Modules/Auth/Livewire/`: `Register`, `Login`, `PasswordResetRequest`, `PasswordReset`. All resolve the provider from the container — no direct calls to `Auth::` facade beyond the provider itself.
- Registration fields per `SPEC.md §6.2`/§6.6: `name_ar`, `name_en`, `email`, `mobile`, `department_id`, `location_id`, `password`, `password_confirmation`, `locale` (default `ar`). `is_tech`/`is_super_user` default false — employees register as employees.
- Password policy: min 10 chars, requires upper + lower + number + symbol. Enforced via Laravel's `Password::min(10)->letters()->mixedCase()->numbers()->symbols()` rule + a custom reusable rule class if needed.
- Redis rate limits (via `RateLimiter::for()` or middleware):
  - Login: 5 attempts / minute per IP + email combination.
  - Registration: 3 / hour per IP.
  - Password reset request: 3 / hour per email.
- Session cookie flags: HttpOnly, Secure, SameSite=Lax; Redis-backed; regenerate session ID on successful login (`request()->session()->regenerate()`).
- Password reset email routed through Laravel mail (delivered via Mailpit in dev); token TTL 60 min.
- All user-facing strings in `resources/lang/{ar,en}/auth.php`.

## Do NOT
- Do not call `Auth::attempt()` / `Auth::register()` directly from Livewire — go through the provider.
- Do not store plaintext passwords, log passwords, or include them in any response.
- Do not implement SSO, OAuth, or magic-link flows (deferred to V2).
- Do not build the profile-edit UI here — that's Task 1.5.
- Do not hardcode rate-limit values — pull from `config/auth.php` or a dedicated config.

## Acceptance
- Pest feature tests in `tests/Feature/Auth/`:
  - Registration with valid data creates a user with `is_tech=false`, `is_super_user=false`, locale persisted.
  - Registration with weak password (9 chars / no symbol / etc.) rejected with validation errors.
  - Login with correct credentials succeeds; session ID regenerated.
  - Login with wrong password fails; 6th attempt within 1 min blocked with 429.
  - 4th registration from the same IP within 1 hour returns 429.
  - Password reset email dispatched to Mailpit (assert via `Mail::fake()`); reset link accepts valid token and updates password hash.
  - Logout invalidates session.
  - Container resolves `AuthProviderInterface` to `EmailPasswordAuthProvider`.

## References
- `SPEC.md §2.3` — AuthProviderInterface invariant
- `SPEC.md §3.5` — rate limit values
- `SPEC.md §3.6` — password policy
- `SPEC.md §6.6` — authentication flow
- `CLAUDE.md` — Security section
