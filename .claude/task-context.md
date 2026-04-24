# Session Context — Ticketing System Phase 3 → Phase 6

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
- ✅ **5.6** — SlaStatusBadge Blade component (green/yellow/red); rendered on ticket detail + ticket list SLA column; compliance summary (% + breached count) on list header; RTL-correct (logical properties); 12 feature tests

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

### Phase 6 (in progress)

- ✅ **6.1** — SearchServiceInterface (Shared/Contracts) + MySqlSearchDriver (Tickets/Search); FULLTEXT on MySQL, LIKE fallback on SQLite; 8 supported filters; priority sort via CASE WHEN; DI binding in TicketsServiceProvider::register(); 19 tests (16 unit + 3 feature)
- ✅ **6.2** — EmployeeDashboard Livewire; own-ticket isolation via EmployeeTicketScope global scope; status tabs (all/open/resolved/closed/cancelled); subject-only LIKE search (description search returns nothing); count badges; quick-submit link; AR locale verified; 16 feature tests
- ✅ **6.3** — TechDashboard Livewire; gate: is_tech/ticket.view-assigned; queue (unassigned in tech's groups via group_user pivot, priority→date sort); my tickets (LEFT JOIN ticket_sla, CASE WHEN SLA urgency breached→warning→on_track then priority→date); transfer accept/decline via TransferService; quick stats (open, resolved week/month, SLA compliance %); 20 feature tests
- ✅ **6.4** — Filter bar (status[], priority[], category, subcategory, filterGroups[], assignedTo, dateFrom, dateTo, search, sortBy/sortDir); users.preferences JSON column (migration 2026_06_00_*); group selection persisted/restored via updatedFilterGroups()/mount(); subcategory resets on category change; text search via SearchServiceInterface; tickets.created_at qualified for LEFT JOIN ambiguity; 20 filter feature tests
- ✅ **6.5** — ManagerDashboard Livewire; gate: is_super_user || ticket.view-all (403 otherwise); status counts (all 9 statuses via raw DB::table, $rows + defaults to preserve zeroes); category counts; created week/month; avg resolution hours from ticket_sla.resolution_elapsed_minutes; SLA compliance %; breached open count; breached tickets table (tech name + overdue hours); escalation queue (awaiting_approval/awaiting_final_approval); unassigned count (open, null assigned_to); team workload (techs sorted by open count desc); recent activity (20 most-recently-updated tickets, proxy for Phase 10 audit_logs); route GET /manager/dashboard → tickets.dashboard.manager; 16 feature tests
- ✅ **6.6** — Pagination (default 25 via config/ticketing.php dashboard.per_page) across all 3 dashboards; sort controls (created_at/priority/updated_at + asc/desc) on EmployeeDashboard and ManagerDashboard; TechDashboard queue/my-tickets as named paginators (queuePage/myPage); ManagerDashboard breached/workload/activity as named paginators; resetPage() on all filter/sort changes; tickets.priority.* AR+EN keys added; ucfirst($p) → __('tickets.priority.*') in tech view; pagination view published with rtl:rotate-180 chevrons + logical CSS props; 14 PaginationTest + 17 LocalizationTest; 615 passed, 39 skipped

## Key file locations (phase 6)

```
app/Modules/Shared/Contracts/SearchServiceInterface.php       ← interface (§11.4 contract)
app/Modules/Tickets/Search/MySqlSearchDriver.php              ← implements SearchServiceInterface
app/Modules/Tickets/Providers/TicketsServiceProvider.php      ← binds interface → driver
app/Modules/Tickets/Livewire/EmployeeDashboard.php            ← employee dashboard component
app/Modules/Tickets/Livewire/TechDashboard.php                ← tech dashboard component
app/Modules/Tickets/Livewire/ManagerDashboard.php             ← IT manager dashboard component
resources/views/livewire/tickets/employee-dashboard.blade.php ← view (RTL logical props)
resources/views/livewire/tickets/tech-dashboard.blade.php     ← view (RTL logical props)
resources/views/livewire/tickets/manager-dashboard.blade.php  ← view (RTL logical props)
resources/lang/{en,ar}/tickets.php                            ← dashboard.employee.* + dashboard.tech.* + dashboard.manager.* keys

tests/Unit/Search/MySqlSearchDriverTest.php               ← 16 tests
tests/Feature/Search/SearchServiceBindingTest.php         ← 3 tests
tests/Feature/Phase6/EmployeeDashboardTest.php            ← 16 tests
tests/Feature/Phase6/TechDashboardTest.php                ← 20 tests
tests/Feature/Phase6/TechDashboardFilterTest.php          ← 20 tests
tests/Feature/Phase6/ManagerDashboardTest.php             ← 16 tests
tests/Feature/Phase6/PaginationTest.php                   ← 14 tests
tests/Feature/Phase6/LocalizationTest.php                 ← 17 tests

config/ticketing.php                                       ← dashboard.per_page = 25
resources/views/vendor/livewire/tailwind.blade.php         ← RTL-corrected pagination view

database/migrations/2026_06_00_000001_add_preferences_to_users_table.php
```

## Notes
- Subject-only search on EmployeeDashboard uses `LIKE` on `subject` column (not FULLTEXT) — CLAUDE.md invariant only forbids raw MySQL FULLTEXT calls, not LIKE.
- EmployeeTicketScope global scope enforces own-ticket isolation for non-tech users automatically.
- ManagerDashboard uses DB::table() (not Ticket::query()) for all aggregate queries — bypasses EmployeeTicketScope intentionally for system-wide counts.
- Status counts: `$rows + array_fill_keys($statuses, 0)` — left operand ($rows) wins so actual counts are preserved; reversed operand order would zero out all counts.
- Recent activity feed uses tickets.updated_at as proxy; real audit_logs table is Phase 10.

## Test count (after phase-6 task 6.6)
**615 passed, 39 skipped (MySQL-only schema checks), 0 failed**

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
