# Task 2.5 — File Upload Pipeline

## Context
Ticket creation accepts up to 5 attachments. The upload pipeline has strict security requirements: magic-byte MIME validation, image processing, and serving via an authorized route — not direct web root access.

## Task
Implement `FileUploadService` covering validation, image processing, ULID-based storage, and an authorized file-serve route. Wire it into the `CreateTicket` component from Task 2.4.

## Requirements
- `app/Modules/Tickets/Services/FileUploadService.php` — method `store(UploadedFile $file, Ticket $ticket, User $uploader): TicketAttachment`
- **Validation (server-side, not extension):**
  - MIME type detected from magic bytes (use `finfo` or equivalent — not `$file->getClientMimeType()`).
  - Allowed MIME types: `image/jpeg`, `image/png`, `image/gif`, `image/webp`, `application/pdf`, `application/msword`, `application/vnd.openxmlformats-officedocument.wordprocessingml.document`.
  - Max size: 10 MB per file. Max count: 5 per ticket creation action. Both enforced in service, not only in Livewire.
- **Image processing** (for image/* MIME types only):
  - Strip EXIF metadata (Intervention Image or `exif_read_data` + re-encode).
  - Resize to max 2048px on longest edge (preserve aspect ratio).
  - Re-encode as JPEG at 80% quality. Discard original.
- **Storage:** Files stored in `storage/app/tickets/{ticket_ulid}/` with ULID filename (no extension in path; mime_type column is source of truth). Outside web root. `file_path` column stores the relative path from `storage/app/`.
- **Serve route:** `GET /tickets/{ticket}/attachments/{attachment}` — gated by policy (only requester, assigned tech, or users with `ticket.view_all`). Streams file with correct `Content-Type` from `mime_type` column. Never uses original filename in path.
- Rate limit: 20 uploads per hour per user (§3.5).
- `TicketAttachment` record created with `original_name`, `file_path`, `file_size`, `mime_type`, `uploaded_by`.

## Do NOT
- Do not store files in `public/` or any web-accessible path.
- Do not trust `$file->getClientMimeType()` or file extension for MIME detection.
- Do not discard the original filename — store it in `original_name` for display only.
- Do not skip EXIF stripping for images, even if library not yet installed (install it).

## Acceptance
- Pest feature tests `tests/Feature/Phase2/FileUploadTest.php`:
  - Valid image upload creates `TicketAttachment`, file on disk, EXIF stripped, dimensions ≤ 2048px.
  - File with `.jpg` extension but PDF magic bytes is rejected.
  - 6th file in one action rejected (count limit).
  - File over 10MB rejected.
  - Unauthorized user cannot access serve route (403).
  - Authorized user can download file via serve route.
  - 21st upload in an hour is rate-limited (429).

## References
- `SPEC.md §3.4` — file upload security requirements (full spec)
- `SPEC.md §3.5` — upload rate limit
- `SPEC.md §7.3` — attachment context in ticket creation
