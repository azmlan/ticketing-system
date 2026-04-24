# Session Context — Ticketing System Phase 3

## Branch convention
`feature/phase-N-task-X.Y-short-name` per task

## Docker command prefix
```bash
docker compose exec app php artisan ...
```

## Completed tasks

### Phase 1 (all done — see git log)

### Phase 2 (all done — see git log)

### Phase 3 (ALL DONE ✅)

- ✅ **3.1 (task file)** — condition_reports, condition_report_attachments, maintenance_requests migrations; schema tests
- ✅ **3.2 (task file)** — ConditionReport, ConditionReportAttachment, MaintenanceRequest models + factories; EscalationServiceProvider
- ✅ **3.3 (task file)** — SubmitConditionReport Livewire + ConditionReportService + ConditionReportFileService + Location model
- ✅ **3.4 (task file)** — ReviewConditionReport Livewire + ConditionReportApprovalService + ConditionReportAttachmentController + serve route
- ✅ **3.5 (task file)** — MaintenanceRequestService (phpoffice/phpword DOCX generation, AR RTL + EN LTR), GenerateMaintenanceRequestDocxJob, GenerateMaintenanceRequestOnActionRequired listener, MaintenanceRequestController (download route); migration making generated_file_path + generated_locale nullable
- ✅ **3.6 (task file)** — UploadSignedMaintenanceRequest Livewire (requester PDF/DOCX upload, magic-bytes, action_required → awaiting_final_approval) + ReviewSignedMaintenanceRequest Livewire (approve → resolved, reject-resubmit → action_required + rejection_count++, reject-permanently → closed) + SignedDocumentController (serve route) + MaintenanceRequestApprovalService + SignedMaintenanceRequestService; TicketStateMachine updated to allow escalation.approve to close tickets

> **Note on task file vs phase-3.md numbering**: task files 3.1–3.5 map to phase-3.md tasks 3.1–3.3. Task file 3.6 covers phase-3.md tasks 3.4 (requester upload) + 3.5 (final approval).

## Key file locations (phase 3)

```
database/migrations/
  2026_04_22_000001_create_condition_reports_table.php
  2026_04_22_000002_create_condition_report_attachments_table.php
  2026_04_22_000003_create_maintenance_requests_table.php
  2026_04_22_000004_make_maintenance_request_generated_file_path_nullable.php

app/Modules/Escalation/
  Models/ConditionReport.php
  Models/ConditionReportAttachment.php
  Models/MaintenanceRequest.php
  Services/ConditionReportService.php
  Services/ConditionReportFileService.php
  Services/ConditionReportApprovalService.php
  Services/MaintenanceRequestService.php          ← phpword DOCX generation
  Services/SignedMaintenanceRequestService.php    ← requester PDF/DOCX upload
  Services/MaintenanceRequestApprovalService.php ← approve/reject-resubmit/reject-permanently
  Jobs/GenerateMaintenanceRequestDocxJob.php
  Listeners/GenerateMaintenanceRequestOnActionRequired.php
  Controllers/ConditionReportAttachmentController.php
  Controllers/MaintenanceRequestController.php    ← download route
  Controllers/SignedDocumentController.php        ← signed doc serve route
  Livewire/SubmitConditionReport.php
  Livewire/ReviewConditionReport.php
  Livewire/UploadSignedMaintenanceRequest.php
  Livewire/ReviewSignedMaintenanceRequest.php
  Providers/EscalationServiceProvider.php
  Routes/web.php

resources/views/livewire/escalation/
  submit-condition-report.blade.php
  review-condition-report.blade.php
  upload-signed-maintenance-request.blade.php
  review-signed-maintenance-request.blade.php

resources/views/livewire/tickets/show-ticket.blade.php

resources/lang/{ar,en}/escalation.php

tests/Feature/Phase3/
  MigrationStructureTest.php           ← 10 pass / 10 MySQL-only skipped
  ConditionReportSubmissionTest.php    ← 11 tests
  ConditionReportApprovalTest.php      ← 16 tests
  MaintenanceRequestGenerationTest.php ← 12 tests
  FinalApprovalTest.php               ← 22 tests (NEW)

tests/Unit/Phase3/
  EscalationModelsTest.php             ← 18 tests
  MaintenanceRequestServiceTest.php    ← 8 tests
```

## Test count (after task-file 3.6)
**355 passed, 15 skipped (MySQL-only schema checks), 0 failed**
+22 new tests from FinalApprovalTest.

## Infrastructure notes
- `phpoffice/phpword ^1.4` installed — generates DOCX via PHP temp file → Storage::disk('local')->put()
- Download routes: `GET /escalation/tickets/{ticketId}/maintenance-request/download/{locale}`
- Signed serve route: `GET /escalation/maintenance-requests/{maintenanceRequest}/signed`
- `generated_file_path` and `generated_locale` are now nullable (pre-job creation state)
- Listener guards against duplicate records on reject-resubmit (whereExists check; job still fires for fresh DOCX)
- `app_settings` table not yet created (Phase 8); getAppSetting() wraps in try-catch → returns null gracefully
- DOCX bidi/RTL via paragraph `['alignment' => 'right', 'bidi' => true]` + font `['rtl' => true]`
- TicketStateMachine::assertCanClose now allows escalation.approve in addition to ticket.close and is_super_user
- Livewire Testing\File: use tmpfile() resource (not string path) as second constructor arg; for magic-bytes content write to resource before passing

## Critical SPEC-over-task-file overrides (phase 2, still relevant)
- Users have `full_name` (not `name_ar`/`name_en`) and `phone` (not `mobile`)
- `permissions` table column is `group_key` (not `group`)
- Migration date prefix for Phase 3: `2026_04_22_*`
