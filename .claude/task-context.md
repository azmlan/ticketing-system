# Session Context — Ticketing System Phase 3 → Phase 7

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

### Phase 5 (ALL DONE ✅)

- ✅ **5.1** — sla_policies, ticket_sla, sla_pause_logs migrations; SlaPolicy, TicketSla, SlaPauseLog models + factories; SlaServiceProvider registered; lang stubs ar+en/sla.php
- ✅ **5.2** — BusinessHoursCalculator reading working_days/business_hours_start/business_hours_end from app_settings; minutesBetween() with use_24x7 override; graceful fallback defaults (Sun–Thu 08:00–16:00); 15 unit tests covering all edge cases
- ✅ **5.3** — SlaService listening to TicketStatusChanged; clock running/paused/stopped per §10.2; accrues elapsed; response_met_at on first assignment; sla_pause_logs with duration_minutes on resume
- ✅ **5.4** — Priority change handler: recalcs response/resolution targets, preserves elapsed, re-evaluates statuses, fires SlaWarning/SlaBreach events
- ✅ **5.5** — sla:check scheduled command (every minute); scans is_clock_running=true tickets; recalcs elapsed via BusinessHoursCalculator; fires SlaWarning/SlaBreached; configurable warning threshold from app_settings
- ✅ **5.6** — SlaStatusBadge Blade component (green/yellow/red); rendered on ticket detail + ticket list SLA column; compliance summary (% + breached count) on list header; RTL-correct (logical props); 12 feature tests

### Phase 6 (ALL DONE ✅)

- ✅ **6.1** — SearchServiceInterface + MySqlSearchDriver; 19 tests
- ✅ **6.2** — EmployeeDashboard Livewire; 16 feature tests
- ✅ **6.3** — TechDashboard Livewire; 20 feature tests
- ✅ **6.4** — Filter bar (status/priority/category/group/tech/date); 20 tests
- ✅ **6.5** — ManagerDashboard Livewire; 16 feature tests
- ✅ **6.6** — Pagination + sort controls; 14 + 17 tests

### Phase 7 (in progress)

- ✅ **7.1** — csat_ratings migration/model/factory; CsatExpireCommand; HandleCsatOnResolution listener; CsatPromptModal + CsatRatingSection Livewire; CsatServiceProvider; 47 tests
- ✅ **7.2** — TicketResolvedMail (ShouldQueue, locale-aware, tech name, no survey URL); listener dispatches on first creation; ar+en csat.email.* keys; 7 tests
- ✅ **7.3** — Reporting module: ReportInterface, ReportServiceInterface, BaseReport, TicketVolumeReport, TicketsByStatusReport, TicketsByCategoryReport, TicketsByPriorityReport, ReportService, ReportPage Livewire (#[Layout]), routes, ar+en reports.php; Pest.php updated; 22 tests (6 unit + 16 feature)

## Key file locations (phase 7)

```
app/Modules/CSAT/
  Models/CsatRating.php                  ← pending/submitted/expired scopes
  Listeners/HandleCsatOnResolution.php   ← creates record + dispatches TicketResolvedMail
  Mail/TicketResolvedMail.php            ← ShouldQueue, locale from requester, tech name
  Livewire/CsatPromptModal.php           ← login modal (requesters only)
  Livewire/CsatRatingSection.php         ← ticket detail section (role-based viewMode)
  Commands/CsatExpireCommand.php         ← csat:expire scheduled daily
  Providers/CsatServiceProvider.php

resources/views/emails/csat/
  ticket_resolved.blade.php              ← HTML email (tech name, no survey URL)
  text/ticket_resolved.blade.php         ← plain-text version

app/Modules/Reporting/
  Contracts/ReportInterface.php          ← headers(): array; run(array): Collection
  Contracts/ReportServiceInterface.php   ← run(string type, array): Collection
  Reports/BaseReport.php                 ← applyFilters(Builder, array): Builder
  Reports/TicketVolumeReport.php         ← GROUP BY DATE(created_at)
  Reports/TicketsByStatusReport.php      ← GROUP BY status
  Reports/TicketsByCategoryReport.php    ← LEFT JOIN categories, GROUP BY category
  Reports/TicketsByPriorityReport.php    ← GROUP BY priority
  Services/ReportService.php             ← registry, run(), headers(), types()
  Livewire/ReportPage.php                ← #[Layout], permission gate, filter state
  Providers/ReportingServiceProvider.php
  Routes/web.php                         ← GET /reports → reports.index

resources/lang/{ar,en}/reports.php      ← types/filters/columns/labels/validation
resources/lang/{ar,en}/csat.php         ← email.* section added
resources/views/livewire/reports/report-page.blade.php

tests/Unit/Reporting/TicketVolumeReportTest.php   ← 6 tests
tests/Feature/Reporting/ReportPageTest.php         ← 16 tests
tests/Feature/CSAT/TicketResolvedMailTest.php      ← 7 tests
```

## Test count (after phase-7 task 7.3)
**684 passed, 43 skipped (MySQL-only schema checks), 0 failed**

## Notes
- Reporting queries use `DB::table('tickets')` to bypass EmployeeTicketScope — always system-wide
- ReportPage aborts 403 in mount() with `is_super_user || hasPermission('system.view-reports')`
- Pest.php updated: Feature/Reporting + Unit/Reporting added to directory lists
- TicketResolvedMail: `$this->locale()` in constructor sets locale for queued job rendering
- TicketsByCategory uses COALESCE(name_ar/name_en, 'Uncategorised') for null categories

## Infrastructure notes
- `app_settings` table not yet created (Phase 8); BusinessHoursCalculator falls back to Sun–Thu 08:00–16:00
- PermissionServiceProvider registers all config/permissions.php keys as Gate abilities
- EmployeeTicketScope: passes through for is_super_user, is_tech, or ticket.view-all
