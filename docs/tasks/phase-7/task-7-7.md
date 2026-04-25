# Task 7.7 — Export: CSV/XLSX with Dynamic Columns, Queued via Horizon

## Context
Managers can export ticket data with all standard fields, dynamic custom field columns, SLA columns, and CSAT columns. Large exports must be queued — not synchronous — and the user notified when ready.

## Task
Implement the CSV and XLSX export pipeline with dynamic column resolution and Horizon-backed queuing.

## Requirements
- `ExportTicketsJob` (queued): accepts current report filters + format (`csv` or `xlsx`), generates file, stores outside web root, notifies requesting user via database notification when ready.
- Standard columns: all core ticket fields (ULID, display_number, subject, status, priority, category, group, assigned tech, requester, created_at, resolved_at, closed_at).
- Dynamic custom field columns per §12.3: one column per active + soft-deleted custom field that has at least one value — populated or blank per ticket.
- SLA columns: response target, response actual, response status, resolution target, resolution actual, resolution status, total paused time — per §12.3.
- CSAT columns: rating (1–5 or blank), comment (or blank), submission date, status.
- CSV: standard PHP `fputcsv`; XLSX: use `maatwebsite/excel` (check if already installed; propose if not).
- Export download served via authorized controller route — ULID filename, streamed directly, deleted after download.
- Column headers respect current locale via `__()`.
- Filters identical to report filters (task 7.5); no filters = all tickets.

## Do NOT
- Do not generate exports synchronously — always dispatch to queue.
- Do not store exports inside `public/` or `storage/app/public/`.
- Do not expose real filenames or ticket IDs in the download URL — use a download token or ULID route.
- Do not include CSAT columns if the requesting user is not IT Manager.

## Acceptance
- Pest `tests/Feature/Export/ExportTicketsJobTest.php`: job dispatched on export request; CSV/XLSX file written to non-public storage; database notification created for user; re-downloading deleted file returns 404.
- Pest `tests/Feature/Export/ExportColumnTest.php`: CSV output contains dynamic custom field columns for fields with values; SLA columns present; CSAT columns absent for non-manager export.
- `Queue::assertPushed(ExportTicketsJob::class)` after triggering export via Livewire action.

## References
- `SPEC.md §12.3` — export spec (dynamic columns, SLA columns, CSAT columns, queue requirement)
- `SPEC.md §3.4` — file storage outside web root
- `SPEC.md §4.2` — localization for column headers
