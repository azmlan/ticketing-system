# Session Context — Ticketing System Phase 3 → Phase 8

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

### Phase 7 (ALL DONE ✅)

- ✅ **7.1** — csat_ratings migration/model/factory; CsatExpireCommand; HandleCsatOnResolution listener; CsatPromptModal + CsatRatingSection Livewire; CsatServiceProvider; 47 tests
- ✅ **7.2** — TicketResolvedMail (ShouldQueue, locale-aware, tech name, no survey URL); listener dispatches on first creation; ar+en csat.email.* keys; 7 tests
- ✅ **7.3** — Reporting module: ReportInterface, ReportServiceInterface, BaseReport, TicketVolumeReport, TicketsByStatusReport, TicketsByCategoryReport, TicketsByPriorityReport, ReportService, ReportPage Livewire (#[Layout]), routes, ar+en reports.php; Pest.php updated; 22 tests (6 unit + 16 feature)
- ✅ **7.4** — Performance bundle: AvgResolutionTimeReport (resolved_at filter axis, SQLite/MySQL compatible), TechPerformanceReport (resolved count + avg CSAT + SLA compliance %), TeamWorkloadReport (open non-terminal tickets per tech), EscalationSummaryReport (condition_reports triggered/approved/rejected per day); ReportService registry + ar+en translations updated; 40 tests (28 unit + 12 feature)
- ✅ **7.5** — SLA + CSAT bundle: SlaComplianceReport (% within SLA by priority, warning=within SLA, critical-first sort), SlaBreachesReport (breached tickets with tech + target vs actual hours), CsatOverviewReport (avg rating + response rate + per-star distribution by date), CsatByTechReport (avg + count + lowest rating per tech, ordered by avg ASC); ReportService now has all 12 types; ar+en translations updated; 40 tests (28 unit + 12 feature)
- ✅ **7.6** — Synchronous export: ExportService (23-column JOIN query — standard + SLA + CSAT + dynamic custom fields with Schema::hasTable guard), CsvWriter (UTF-8 BOM + fputcsv streamed), XlsxWriter (phpspreadsheet, bold headers, auto-size, temp file), ExportController (permission-gated GET /reports/export?format=csv|xlsx), CSV/XLSX buttons on report page forwarding live filter state; 19 feature tests; new package: phpoffice/phpspreadsheet ^5.7
- ✅ **7.7** — Queued export via Horizon: notifications + ticket_exports migrations; TicketExport model (ULID PK, filters JSON, include_csat flag, status pending/ready/failed); ExportTicketsJob (ShouldQueue, writes CSV/XLSX to local disk at exports/{ulid}.{ext}, fires ExportReadyNotification via database + mail); ExportController::download() (ownership check, 404 on missing file, deleteFileAfterSend); queueExport() Livewire action (dispatches job, sets exportQueued flag); ExportService + ExportController updated to gate CSAT columns on is_super_user or ticket.view-all; 21 tests (ExportTicketsJobTest + ExportColumnTest)

### Phase 8 (ALL DONE ✅)

- ✅ **8.1 (admin shell)** — Admin layout, section navigation gated per §13.1, Categories & Subcategories CRUD with versioning, soft-delete/deactivate; commit 6bd8979
- ✅ **8.1 (db schema)** — Migrations: custom_fields (6-type enum, scope, version, SoftDeletes), custom_field_options, custom_field_values (composite index), tags, ticket_tag pivot (no timestamps, UNIQUE), app_settings (seeded 9 defaults from §13.4); Models: CustomField, CustomFieldOption, CustomFieldValue, Tag, AppSetting (static get/set); ResponseTemplate.localizedName() added; Phase5 tests fixed for seeded app_settings; Pest.php updated for Unit/Admin; 39 new unit tests
- ✅ **8.2** — GroupIndex Livewire (CRUD, bilingual, is_active toggle, soft-delete, gated by group.manage); GroupMembersIndex Livewire (add/remove techs via group.manage-members, set manager_id via group.manage-manager, OR-gate on mount); routes /admin/groups + /admin/groups/{group}/members; sidebar nav wired; AR+EN translations (37 keys); 29 feature tests; commit 0f4f0a3
- ✅ **8.3** — CustomFieldIndex Livewire (list all 6 types, search, display_order reorder, toggle active, soft-delete, inline options panel for dropdown/multi_select); field_type change blocked when values exist; category-scoped fields with category dropdown; version bumps on every save; route /admin/custom-fields gated by system.manage-custom-fields; AR+EN translations (50 keys each); sidebar nav link wired; 31 feature tests (23 CRUD + 8 options)
- ✅ **8.4** — Custom field rendering on ticket create form (global always shown, category-scoped on category match); 6-type inputs in create-ticket.blade.php; values written to custom_field_values on submit; ShowTicket reads all values including deactivated/soft-deleted fields via withTrashed (§13.3); dropdown/multi_select labels resolved via option lookup withTrashed; inactive fields marked with indicator; Ticket.customFieldValues() HasMany added; AR+EN tickets.php keys; Pest.php Phase8 dir added; 22 feature tests
- ✅ **8.7** — DepartmentIndex + LocationIndex Livewire CRUDs (bilingual, sort_order, is_active, soft-delete, search); Shared models get scopeActive() + localizedName(); admin routes /admin/departments + /admin/locations; sidebar nav wired; AR+EN admin.php (19 keys each); CreateTicket gets department_id + location_id fields + validation + save; create-ticket.blade.php dropdowns; AR+EN tickets.php keys; 44 feature tests (17+17+10); commit 9c93228
- ✅ **8.8** — UserList + UserDetail Livewire (OR-gate: user.promote OR user.manage-permissions); paginated list with search (name/email), role filter (employee/tech/it_manager), status filter (active/deleted); detail page: profile, tech profile, promote-to-tech action (confirmation modal, creates TechProfile, dispatches UserPromotedToTech event), permission grant/revoke panel (grouped checkboxes, sync, is_super_user blocked); admin nav link wired; AR+EN admin.php (38 keys each); 34 feature tests (25 UserManagement + 9 PermissionManagement); commit ef24a21
- ✅ **8.9** — BrandingSettings Livewire (IT Manager only, is_super_user gate); company_name, primary_color, secondary_color, session_timeout_hours (1–24) saved to app_settings; logo upload (magic-bytes MIME, 2MB, 256px resize, JPEG re-encode, ULID path, old logo deleted); LogoController (auth check inline, 403 for guests); SessionTimeoutMiddleware (prepended to web group, reads session_timeout_hours from app_settings); admin layout reads company_name + logo + CSS vars (--color-primary/--color-secondary); email base partial shows company_name; branding nav wired; AR+EN admin.php (17 keys each); 27 feature tests (19 BrandingSettings + 8 LogoUpload); commit bf34286

## Key file locations (phase 8 — task 8.9 Tenant Branding)

```
app/Modules/Admin/Livewire/Settings/BrandingSettings.php  ← IT Manager only; WithFileUploads; storeLogo() with magic-bytes check
app/Modules/Admin/Controllers/LogoController.php          ← serves logos/; abort_unless(auth, 403)
app/Modules/Shared/Middleware/SessionTimeoutMiddleware.php ← prepended to web; Config::set('session.lifetime', hours*60)

resources/views/livewire/admin/settings/branding-settings.blade.php
resources/views/layouts/admin.blade.php  ← company_name, logo img, --color-primary/--color-secondary CSS vars
resources/views/emails/notifications/partials/base.blade.php  ← company_name header + primary color on CTA button

app/Modules/Admin/Routes/web.php  ← /admin/branding (auth) + /admin/logo (no auth, checked in controller)
bootstrap/app.php  ← prepend SessionTimeoutMiddleware to web group
resources/lang/{ar,en}/admin.php  ← branding section (17 keys each)

tests/Feature/Admin/BrandingSettingsTest.php  ← 19 tests
tests/Feature/Admin/LogoUploadTest.php        ← 8 tests
```

## Test count (after phase-8 task 8.9)
**1116 passed, 44 skipped, 0 failed** (+27 from task 8.9)

## Key file locations (phase 8 — task 8.8 User Management)

```
app/Modules/Admin/Events/UserPromotedToTech.php       ← event: user + promotedBy
app/Modules/Admin/Livewire/Users/UserList.php         ← paginated list; search + role + status filters; OR-gate
app/Modules/Admin/Livewire/Users/UserDetail.php       ← profile/tech profile/promote/permissions panels

resources/views/livewire/admin/users/user-list.blade.php
resources/views/livewire/admin/users/user-detail.blade.php

app/Modules/Admin/Routes/web.php  ← /admin/users + /admin/users/{user}
resources/views/layouts/admin.blade.php  ← nav link uses OR-gate (user.promote || user.manage-permissions)
resources/lang/{ar,en}/admin.php  ← users section (38 keys each)

tests/Feature/Admin/UserManagementTest.php       ← 25 tests (list filters, promote flow, event, nav)
tests/Feature/Admin/PermissionManagementTest.php ← 9 tests (grant/revoke/sync, IT Manager block, 403)
```

## Test count (after phase-8 task 8.8)
**1089 passed, 44 skipped, 0 failed** (+34 from task 8.8)

## Key file locations (phase 8 — task 8.7 Departments + Locations)

```
app/Modules/Admin/Livewire/Departments/
  DepartmentIndex.php  ← Departments CRUD (system.manage-departments): create/edit/toggleActive/delete; sort_order; bilingual

app/Modules/Admin/Livewire/Locations/
  LocationIndex.php    ← Locations CRUD (system.manage-locations): same pattern

app/Modules/Shared/Models/Department.php  ← scopeActive() + localizedName() added
app/Modules/Shared/Models/Location.php   ← scopeActive() + localizedName() added

resources/views/livewire/admin/departments/department-index.blade.php
resources/views/livewire/admin/locations/location-index.blade.php

app/Modules/Tickets/Livewire/CreateTicket.php  ← department_id + location_id added (optional, validated, saved)
resources/views/livewire/tickets/create-ticket.blade.php  ← dept + location dropdowns (shown when records exist)

app/Modules/Admin/Routes/web.php  ← /admin/departments + /admin/locations
resources/views/layouts/admin.blade.php  ← sidebar nav links wired
resources/lang/{ar,en}/admin.php  ← departments (19 keys) + locations (19 keys)
resources/lang/{ar,en}/tickets.php  ← create.department, create.location, select_department, select_location

tests/Feature/Admin/DepartmentCrudTest.php     ← 17 tests
tests/Feature/Admin/LocationCrudTest.php       ← 17 tests
tests/Feature/Phase8/Task87DeptLocationTicketTest.php ← 10 tests
```

## Test count (after phase-8 task 8.7)
**1055 passed, 44 skipped, 0 failed** (+44 from task 8.7)

## Key file locations (phase 8 — task 8.6 Tags + Response Templates)

```
app/Modules/Admin/Livewire/Tags/
  TagIndex.php  ← Tags CRUD (system.manage-tags): create/edit/toggleActive/delete; hex color validation

app/Modules/Admin/Livewire/ResponseTemplates/
  ResponseTemplateIndex.php  ← Response Templates CRUD (system.manage-response-templates): bilingual, is_internal filter, RichTextSanitizer on body save

resources/views/livewire/admin/tags/
  tag-index.blade.php       ← list with color swatch, inline form

resources/views/livewire/admin/response-templates/
  response-template-index.blade.php  ← list with type badges, inline form with bilingual body textareas

app/Modules/Tickets/Models/Ticket.php  ← tags() BelongsToMany via ticket_tag pivot added

app/Modules/Admin/Routes/web.php  ← /admin/tags + /admin/response-templates routes
resources/views/layouts/admin.blade.php  ← sidebar nav links wired for tags + response-templates
resources/lang/{ar,en}/admin.php  ← tags section (18 keys) + response_templates section (21 keys)

tests/Feature/Admin/TagCrudTest.php            ← 17 tests
tests/Feature/Admin/ResponseTemplateCrudTest.php ← 20 tests
```

## Test count (after phase-8 task 8.6)
**1011 passed, 44 skipped, 0 failed** (+37 from task 8.6)

*(see task 8.7 section above for current count)*

## Key file locations (phase 8 — task 8.5 SLA settings)

```
app/Modules/Admin/Livewire/Sla/
  SlaSettingsIndex.php  ← two-section page: SLA targets (4 priorities via DB::table) + business hours config (AppSetting)

resources/views/livewire/admin/sla/
  sla-settings-index.blade.php

app/Modules/Admin/Routes/web.php  ← /admin/sla-settings (can:system.manage-sla → admin.sla.settings)
resources/views/layouts/admin.blade.php  ← SLA sidebar link wired to route('admin.sla.settings')
resources/lang/{ar,en}/admin.php  ← sla_settings section (34 keys each)

tests/Feature/Admin/SlaSettingsTest.php  ← 19 tests (route access + load + save + validation)
```

## Test count (after phase-8 task 8.5)
**974 passed, 44 skipped, 0 failed** (+19 from SlaSettingsTest)

## Key file locations (phase 8 — task 8.4 custom field rendering)

```
app/Modules/Tickets/Livewire/
  CreateTicket.php  ← applicableCustomFields() (global + category-scoped active); 6-type validation; saveCustomFieldValues() after create
  ShowTicket.php    ← buildCustomFieldDisplay(): loads CustomFieldValue with field withTrashed; resolves dropdown/multi_select labels via CustomFieldOption.withTrashed

app/Modules/Tickets/Models/Ticket.php  ← customFieldValues(): HasMany added

resources/views/livewire/tickets/
  create-ticket.blade.php  ← custom fields section with 6 input types
  show-ticket.blade.php    ← custom field display section (label, value, inactive indicator)

resources/lang/{ar,en}/tickets.php
  tickets.custom_fields.select_option
  tickets.show.custom_fields
  tickets.show.field_inactive

tests/Feature/Phase8/Task84CustomFieldRenderingTest.php  ← 22 tests
```

## Key file locations (phase 8 — task 8.3 custom fields)

```
app/Modules/Admin/Livewire/CustomFields/
  CustomFieldIndex.php    ← CRUD (system.manage-custom-fields): create/edit/toggleActive/delete/reorder; inline options panel

resources/views/livewire/admin/custom-fields/
  custom-field-index.blade.php

tests/Feature/Admin/CustomFieldCrudTest.php   ← 23 tests (all 6 types, versioning, type-change block, reorder)
tests/Feature/Admin/CustomFieldOptionTest.php ← 8 tests (add/edit/delete/reorder options, active scope, 403)

Routes: /admin/custom-fields (can:system.manage-custom-fields → admin.custom-fields.index)
```

## Key file locations (phase 8 — task 8.2 groups)

```
app/Modules/Admin/Livewire/Groups/
  GroupIndex.php          ← CRUD (group.manage): create/edit/toggleActive/delete
  GroupMembersIndex.php   ← Members (group.manage-members) + manager (group.manage-manager); OR-gate

resources/views/livewire/admin/groups/
  group-index.blade.php
  group-members-index.blade.php

tests/Feature/Admin/GroupsCrudTest.php  ← 29 tests

Routes: /admin/groups (can:group.manage), /admin/groups/{group}/members (auth only; OR checked in mount)
```

## Key file locations (phase 8 — admin schema)

```
app/Modules/Admin/Models/
  CustomField.php         ← field_type enum (6), scope_type enum, scope_category_id FK, version, SoftDeletes
  CustomFieldOption.php   ← custom_field_id FK, bilingual value_ar/value_en, sort_order, SoftDeletes
  CustomFieldValue.php    ← ticket_id + custom_field_id FKs, composite index (no SoftDeletes)
  Tag.php                 ← bilingual name (100), hex color, is_active, SoftDeletes
  AppSetting.php          ← static get(key, default) / set(key, value); seeded 9 defaults

app/Modules/Communication/Models/ResponseTemplate.php  ← localizedName() added

database/migrations/
  2026_08_00_000001_create_custom_fields_table.php
  2026_08_00_000002_create_custom_field_options_table.php
  2026_08_00_000003_create_custom_field_values_table.php
  2026_08_00_000004_create_tags_table.php
  2026_08_00_000005_create_ticket_tag_table.php
  2026_08_00_000006_create_app_settings_table.php  ← seeds 9 keys

database/factories/
  CustomFieldFactory.php       ← text(), dropdown(), categoryScoped(), inactive(), required()
  CustomFieldOptionFactory.php
  CustomFieldValueFactory.php
  TagFactory.php               ← inactive()

tests/Unit/Admin/
  CustomFieldModelTest.php      ← 14 tests
  AppSettingTest.php            ← 13 tests (incl. seed assertions)
  TagModelTest.php              ← 8 tests (incl. pivot uniqueness)
  ResponseTemplateModelTest.php ← 9 tests (tests Communication model)
```

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
  Reports/AvgResolutionTimeReport.php    ← date filter on resolved_at; avg hours
  Reports/TechPerformanceReport.php      ← resolved count + avg CSAT + SLA compliance %
  Reports/TeamWorkloadReport.php         ← open (non-terminal) tickets per tech
  Reports/EscalationSummaryReport.php    ← condition_reports triggered/approved/rejected per day
  Reports/SlaComplianceReport.php        ← % within SLA by priority (warning=within, critical-first sort)
  Reports/SlaBreachesReport.php          ← breached tickets with tech + target vs actual hours
  Reports/CsatOverviewReport.php         ← avg rating + response rate + star distribution by date
  Reports/CsatByTechReport.php           ← avg + count + lowest rating per tech (ordered by avg ASC)
  Services/ReportService.php             ← registry (12 types), run(), headers(), types()
  Models/TicketExport.php                ← ULID PK, filters JSON, include_csat, status, expires_at
  Jobs/ExportTicketsJob.php              ← ShouldQueue; writes to local disk; fires ExportReadyNotification
  Notifications/ExportReadyNotification.php ← database + mail via; toDatabase/toMail
  Services/ExportService.php             ← exportHeaders(bool includeCsat) + exportRows(filters, includeCsat)
  Writers/CsvWriter.php                  ← UTF-8 BOM + fputcsv streamed download
  Writers/XlsxWriter.php                 ← phpspreadsheet, bold header, auto-size; writeTempFile() for job
  Controllers/ExportController.php       ← export() + download(); CSAT gated on ticket.view-all
  Livewire/ReportPage.php                ← queueExport(format) Livewire action; exportQueued flag
  Providers/ReportingServiceProvider.php
  Routes/web.php                         ← GET /reports + GET /reports/export + GET /reports/exports/{export}/download

resources/lang/{ar,en}/reports.php      ← types/filters/columns/labels/validation + export.* section
resources/lang/{ar,en}/csat.php         ← email.* section added
resources/views/livewire/reports/report-page.blade.php  ← CSV + XLSX download buttons

tests/Unit/Reporting/TicketVolumeReportTest.php        ← 6 tests
tests/Unit/Reporting/SlaComplianceReportTest.php       ← 7 tests
tests/Unit/Reporting/SlaBreachesReportTest.php         ← 7 tests
tests/Unit/Reporting/CsatOverviewReportTest.php        ← 7 tests
tests/Unit/Reporting/CsatByTechReportTest.php          ← 7 tests
tests/Feature/Reporting/ReportPageTest.php             ← 16 tests
tests/Feature/Reporting/SlaCsatBundleReportPageTest.php ← 12 tests
tests/Feature/Reporting/ExportTest.php                 ← 19 tests (updated: CSAT tests now use super user)
tests/Feature/Export/ExportTicketsJobTest.php          ← 11 tests
tests/Feature/Export/ExportColumnTest.php              ← 8 tests (1 skipped — custom_fields not yet seeded)
tests/Feature/CSAT/TicketResolvedMailTest.php          ← 7 tests
```

## Test count (after phase-8 task 8.4)
**955 passed, 44 skipped (MySQL-only schema checks), 0 failed**

## Notes
- Reporting queries use `DB::table('tickets')` to bypass EmployeeTicketScope — always system-wide
- ReportPage aborts 403 in mount() with `is_super_user || hasPermission('system.view-reports')`
- TicketResolvedMail: `$this->locale()` in constructor sets locale for queued job rendering
- TicketsByCategory uses COALESCE(name_ar/name_en, 'Uncategorised') for null categories
- `app_settings` is now seeded by migration (9 defaults from §13.4); Phase5 tests updated accordingly
- ResponseTemplate lives in Communication module; Admin CRUD will manage via that namespace
- AppSetting::get() returns null (not default) when key exists but value IS null
- ticket_tag pivot: no timestamps, UNIQUE (ticket_id, tag_id), both FKs ON DELETE CASCADE
- custom_field_values: ON DELETE RESTRICT on custom_field_id (values must be cleared before field hard-delete)

## Infrastructure notes
- PermissionServiceProvider registers all config/permissions.php keys as Gate abilities
- EmployeeTicketScope: passes through for is_super_user, is_tech, or ticket.view-all
- Pest.php: Unit/Admin added to TestCase + RefreshDatabase directory list
