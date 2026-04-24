# Session Context — Ticketing System Phase 3 → Phase 5

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

- ✅ **3.1** — condition_reports, condition_report_attachments, maintenance_requests migrations; schema tests
- ✅ **3.2** — ConditionReport, ConditionReportAttachment, MaintenanceRequest models + factories; EscalationServiceProvider
- ✅ **3.3** — SubmitConditionReport Livewire + ConditionReportService + ConditionReportFileService + Location model
- ✅ **3.4** — ReviewConditionReport Livewire + ConditionReportApprovalService + ConditionReportAttachmentController + serve route
- ✅ **3.5** — MaintenanceRequestService (phpoffice/phpword DOCX generation, AR RTL + EN LTR), GenerateMaintenanceRequestDocxJob, GenerateMaintenanceRequestOnActionRequired listener, MaintenanceRequestController (download route); migration making generated_file_path + generated_locale nullable
- ✅ **3.6** — UploadSignedMaintenanceRequest Livewire + ReviewSignedMaintenanceRequest Livewire + SignedDocumentController + MaintenanceRequestApprovalService + SignedMaintenanceRequestService; TicketStateMachine updated to allow escalation.approve to close tickets

### Phase 4 (ALL DONE ✅)

- ✅ **4.1** — comments + notification_logs + response_templates migrations; Comment/NotificationLog/ResponseTemplate models + factories; InternalCommentScope; CommunicationServiceProvider registered
- ✅ **4.2** — AddComment Livewire (communication.add-comment); internal/public toggle; server-side 403 for employees; response-template pre-fill; rate limit 30/hr; CommentCreated event; body sanitized; InternalCommentScope enforces visibility at query level; integrated into show-ticket.blade.php
- ✅ **4.3** — NotificationService, SendNotificationJob, event listeners (TicketCreated, TicketStatusChanged, TicketAssigned, CommentCreated, TransferRequestCreated), locale-aware mailables, notification_logs lifecycle
- ✅ **4.4** — plain-text email fallback for all notification triggers

### Phase 5 (IN PROGRESS)

- ✅ **5.1** — sla_policies, ticket_sla, sla_pause_logs migrations; SlaPolicy, TicketSla, SlaPauseLog models + factories; SlaServiceProvider registered; lang stubs ar+en/sla.php
- ✅ **5.2** — BusinessHoursCalculator reading working_days/business_hours_start/business_hours_end from app_settings; minutesBetween() with use_24x7 override; graceful fallback defaults (Sun–Thu 08:00–16:00); 15 unit tests covering all edge cases

## Key file locations (phase 5, so far)

```
database/migrations/
  2026_04_24_000001_create_sla_policies_table.php
  2026_04_24_000002_create_ticket_sla_table.php
  2026_04_24_000003_create_sla_pause_logs_table.php

app/Modules/SLA/
  Models/SlaPolicy.php
  Models/TicketSla.php
  Models/SlaPauseLog.php
  Services/BusinessHoursCalculator.php    ← minutesBetween(Carbon, Carbon, bool)
  Providers/SlaServiceProvider.php

database/factories/
  SlaPolicyFactory.php    ← low/medium/high/critical states
  TicketSlaFactory.php    ← paused/warning/breached/withoutTargets states
  SlaPauseLogFactory.php  ← resumed state

resources/lang/{ar,en}/sla.php

tests/Feature/Phase5/
  MigrationStructureTest.php    ← 8 pass / 13 MySQL-only skipped

tests/Unit/Phase5/
  SlaModelsTest.php                  ← 20 tests
  BusinessHoursCalculatorTest.php    ← 15 tests
```

## Test count (after phase-5 task 5.2)
**460 passed, 39 skipped (MySQL-only schema checks), 0 failed**

## Infrastructure notes
- `app_settings` table not yet created (Phase 8); BusinessHoursCalculator wraps DB read in try-catch → falls back to Sun–Thu 08:00–16:00 defaults
- Tests that need app_settings rows create the table inline with `DB::statement('CREATE TABLE IF NOT EXISTS ...')`
- Migration date prefix for Phase 5: `2026_04_24_*`
- BusinessHoursCalculator day-of-week map: 0=sun, 1=mon, 2=tue, 3=wed, 4=thu, 5=fri, 6=sat (Carbon convention)

## Key file locations (phase 4)

```
database/migrations/
  2026_04_23_000001_create_comments_table.php         ← FULLTEXT guarded for SQLite
  2026_04_23_000002_create_notification_logs_table.php
  2026_04_23_000003_create_response_templates_table.php

app/Modules/Communication/
  Events/CommentCreated.php
  Livewire/AddComment.php
  Models/Comment.php                    ← InternalCommentScope global scope
  Models/NotificationLog.php
  Models/ResponseTemplate.php           ← SoftDeletes + active() local scope
  Models/Scopes/InternalCommentScope.php
  Providers/CommunicationServiceProvider.php

tests/Feature/Phase4/
  MigrationStructureTest.php   ← 19 pass / 12 MySQL-only skipped
  CommentsTest.php             ← 15 tests

tests/Unit/Phase4/
  CommunicationModelsTest.php  ← 22 tests
```

## Critical SPEC-over-task-file overrides (still relevant from earlier phases)
- Users have `full_name` (not `name_ar`/`name_en`) and `phone` (not `mobile`)
- `permissions` table column is `group_key` (not `group`)
- InternalCommentScope: wraps with auth()->check() guard so queue/CLI contexts see all comments unfiltered
- TicketStateMachine::assertCanClose allows escalation.approve in addition to ticket.close and is_super_user
