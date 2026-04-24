# Task 7.3 — CSAT: Login Prompt Modal (Livewire)

## Context
After login, requesters with pending CSAT ratings see a modal prompting them to rate their experience. They can dismiss (deferred to next login) or submit. Expired records never prompt.

## Task
Build the `CsatPromptModal` Livewire component that appears post-login for requesters with pending ratings.

## Requirements
- Livewire component at `app/Modules/CSAT/Livewire/CsatPromptModal.php`; only mounts if auth user has role `requester`.
- On mount: query one `pending` rating for the authenticated user where `expires_at > now()`, ordered by oldest first. If none, component renders nothing.
- Displays: ticket `display_number`, subject, assigned tech full name, star rating input (1–5), optional comment textarea.
- **Dismiss action:** increment `dismissed_count`, do NOT change `status`; component hides for current session. Modal reappears next login.
- **Submit action:** validate rating (required, 1–5 int); save `rating`, `comment`, `status = submitted`, `submitted_at = now()`; component hides. Re-submission blocked (guard on `status !== pending`).
- All strings through `__()` with keys in `resources/lang/{ar,en}/csat.php`.
- Star rating input flips direction in RTL — use logical CSS properties.

## Do NOT
- Do not show this modal to techs, managers, or super users.
- Do not allow rating values outside 1–5.
- Do not allow submission after `status` is already `submitted` or `expired`.
- Do not hardcode any user-facing strings.

## Acceptance
- Pest `tests/Feature/CSAT/CsatPromptModalTest.php`: modal renders for requester with pending rating; hidden when no pending ratings exist; dismiss increments count without changing status; submit sets correct fields and blocks re-submit; expired rating never shown.
- Manually: login as requester with pending rating → modal appears; dismiss → gone until next login; submit → modal gone, rating saved.

## References
- `SPEC.md §12.1` — flow steps 3–4
- `SPEC.md §4.2` — RTL/localization
- `SPEC.md §6.6` — auth role checks (requester only)
