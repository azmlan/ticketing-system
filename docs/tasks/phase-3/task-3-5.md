# Task 3.5 — Maintenance Request Document Generation

## Context
When a condition report is approved the ticket enters `action_required` and the system must auto-generate a pre-filled DOCX in both Arabic (RTL) and English (LTR). The requester downloads the document, signs it offline, and re-uploads it in Task 3.6.

## Task
Build `MaintenanceRequestService` to generate the DOCX via `phpoffice/phpword`, create the `maintenance_requests` record, expose download endpoints for both locales, and wire the generation to the `TicketStatusChanged` event listener.

## Requirements
- Install `phpoffice/phpword` (surface in task summary for approval before adding).
- `app/Modules/Escalation/Services/MaintenanceRequestService.php` — method `generate(string $ticketUlid, string $locale): MaintenanceRequest`. Locale must be `'ar'` or `'en'` — throw `InvalidArgumentException` otherwise.
- **Document content** per §8.3.1: header with company name + logo (from `app_settings` keys `company_name` and `logo_path` — §13.4), document title localized, ticket info (display number, creation date, category, subcategory), requester info (full name, employee number, department, location), issue description (plain-text extraction from HTML — strip tags, no raw HTML in DOCX), technical analysis from the approved condition report (current condition, condition analysis, required action, assigned tech name), bilingual disclaimer (hardcoded AR/EN text per §8.3.1), signature block (requester name pre-filled, blank signature line, blank date line).
- **Arabic document:** RTL writing direction via PhpWord `setRTL(true)` on sections. All labels and structure text in Arabic.
- **English document:** LTR. All labels and structure text in English.
- User-generated content (ticket subject, description, condition report text) is included as-is in both versions — not translated.
- Storage: ULID-based filename, stored in `storage/app/maintenance-requests/{ticket_ulid}/`, outside web root. `generated_file_path` updated on `MaintenanceRequest` record. On resubmit, a new file is generated — old file kept on disk (never deleted), new path replaces `generated_file_path`.
- Listener: `app/Modules/Escalation/Listeners/GenerateMaintenanceRequestOnActionRequired.php` — listens to `TicketStatusChanged`; fires only when `$event->newStatus === 'action_required'`. Creates `maintenance_requests` record with `status = 'pending'` before generating. Dispatches the generation as a **queued job** (Horizon) — do not block the request thread. The listener creates the DB record synchronously; the job generates the DOCX and updates `generated_file_path`.
- Download endpoints: `GET /escalation/tickets/{ticket}/maintenance-request/download/{locale}` where `locale` ∈ `{ar, en}`. Accessible by requester, assigned tech, or `escalation.approve` holders. Triggers a fresh document generation (always reflects current data per §8.3.3). Streams the file as a download response with `Content-Type: application/vnd.openxmlformats-officedocument.wordprocessingml.document`.
- `generated_locale` on the record stores the locale of the most recent generation.
- The requester's ticket detail view shows two download buttons ("Export in Arabic" / "Export in English") when `ticket.status = 'action_required'` and `maintenance_requests.status = 'pending'` or `'rejected'`.

## Do NOT
- Do not embed raw HTML from ticket description or condition report into the DOCX — strip all HTML tags to plain text before inserting.
- Do not generate the document synchronously on the main request thread for the approval action — use a queued job.
- Do not delete old generated files on resubmit — keep all versions for audit.
- Do not accept locale values other than `'ar'` or `'en'`.
- Do not use the `display_number` as a route parameter; always use ULID.

## Acceptance
- Pest feature tests `tests/Feature/Phase3/MaintenanceRequestGenerationTest.php`:
  - `TicketStatusChanged` event with `action_required` dispatches the generation job (use `Queue::fake()`).
  - `MaintenanceRequest` record is created synchronously (status=`pending`) before the job runs.
  - Download route with `locale=ar` returns a file response with correct Content-Type; accessible by requester.
  - Download route with `locale=en` returns a file response.
  - Download route returns 403 for an unrelated employee.
  - Invalid locale returns 404 or 422.
  - On second generation (resubmit scenario), old `generated_file_path` is replaced and old file remains on disk.
- Unit test `tests/Unit/Phase3/MaintenanceRequestServiceTest.php`:
  - `generate()` with `locale=ar` produces a file on disk at the expected path.
  - `generate()` with `locale=en` produces a different file at the expected path.
  - `generate()` with invalid locale throws `InvalidArgumentException`.
  - Plain-text extraction from HTML is verified (no `<p>`, `<strong>`, etc. in output).

## References
- `SPEC.md §8.3` — full document content spec, language options, and generation rules
- `SPEC.md §8.3.1` — exact section list and hardcoded disclaimer text
- `SPEC.md §8.3.3` — generation implementation notes (fresh on each download, keep old files)
- `SPEC.md §13.4` — `app_settings` keys for company_name and logo_path
- `SPEC.md §3.4` — file storage and authorized serve pattern
- `SPEC.md §7.4` — `action_required` trigger condition
