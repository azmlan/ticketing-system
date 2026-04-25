# Task 7.4 — CSAT: Ticket Detail View + Visibility Rules

## Context
Requesters can also rate from the ticket detail page (not just the login modal). Visibility of ratings varies strictly by role — enforce server-side, not just UI.

## Task
Add CSAT rating submission to the ticket detail view and enforce per-role visibility rules for all CSAT data.

## Requirements
- Add a CSAT section to the ticket detail Livewire component (or a dedicated `CsatRatingSection` sub-component).
- Requester sees: pending rating form (star + comment, submittable); submitted rating read-only with their rating/comment; no section if expired.
- Assigned tech sees: submitted rating + comment for tickets assigned to them — read-only. No section if pending or expired.
- IT Manager sees: all submitted ratings on any ticket — read-only.
- Other techs (not assigned): CSAT section not rendered at all.
- Super user follows IT Manager visibility.
- Submission from ticket detail follows same validation as modal (rating 1–5, guards against re-submission, update `submitted_at`).
- All strings through `__()`.

## Do NOT
- Do not allow a tech who is not the assigned tech to see CSAT data.
- Do not allow re-rating after submission from any entry point.
- Do not trust role checks from the frontend — enforce in the Livewire component's `authorize()` or policy.

## Acceptance
- Pest `tests/Feature/CSAT/CsatVisibilityTest.php`: requester sees own pending form; assigned tech sees submitted rating; non-assigned tech sees nothing; manager sees all; expired rating hidden from all non-manager.
- Pest `tests/Feature/CSAT/CsatTicketDetailSubmitTest.php`: submission from ticket detail page saves correctly; duplicate submission attempt returns validation error.

## References
- `SPEC.md §12.1` — visibility table and flow steps 4–5
- `SPEC.md §3.2` — server-side authorization enforcement
- `SPEC.md §6.6` — role definitions
