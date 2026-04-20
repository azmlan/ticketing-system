# Ticketing System — V1 Implementation Specification

> **Version:** 1.0  
> **Date:** April 2026  
> **Scope:** Web Application Only  
> **Auth:** Email + Password (SSO-ready architecture)  
> **Localization:** Arabic (primary, RTL) + English  
> **Architecture:** Modular Monolith  
> **Target:** Claude Code CLI — each phase is a self-contained, deployable unit

---

## 1. Executive Summary & V1 Scope

A generic internal IT support ticketing system, built as a **resellable product** with single-tenant deployments per buyer company. Each deployment is a separate instance with its own database. Employees submit maintenance/support requests. Technicians are assigned to resolve them. A special escalation workflow exists for certain device repairs requiring multi-level approval.

### 1.1 What V1 Includes

- Web application only (Laravel 12 + Livewire 3 + Tailwind CSS 4)
- Email + password authentication (architecture supports future SSO swap via AuthProviderInterface)
- Full ticket lifecycle: standard path and escalation path
- Permission-based access control with pre-defined role bundles
- Category → Group auto-assignment with self-assign and manager-assign
- Peer transfer requests between technicians
- Escalation workflow: Condition Report (DB form) + auto-generated Maintenance Request document (AR/EN, pre-filled, signed offline, re-uploaded)
- SLA engine with response + resolution timers, business hours, clock pausing
- CSAT post-resolution feedback (login-required, prompted on next login)
- Precedent system tier 1 (structured resolution capture, auto-suggest, linking)
- Reporting (12 pre-defined report types) and export (CSV/XLSX)
- Full Arabic (RTL) + English localization from day one
- Admin configuration panel (categories, groups, custom fields, SLA targets, etc.)
- Comprehensive audit logging
- Single-tenant deployment model with per-tenant branding

### 1.2 What V1 Excludes (Deferred to V2+)

- SSO provider integrations (Microsoft Entra ID, Google Workspace, Okta)
- Flutter mobile application (Android & iOS)
- Asset management module
- Precedent system tiers 2 and 3
- Location-based auto-filtering for technicians
- In-app and push notifications
- Deployment automation tooling

### 1.3 Deployment Model

Single-tenant: each buyer company gets a separate instance with its own database. No shared infrastructure between tenants. Each deployment is independently configured for auth provider, email provider, branding, SLA policies, and business hours.

---

## 2. Architecture

### 2.1 Modular Monolith

The application follows a **modular monolith** architecture. One Laravel app, one deployment, one database — but the code is organized by **business domain**, not by technical layer.

#### Why Modular Monolith

- **Not microservices:** Solo/small team, single-tenant, moderate scale. Microservices would add deployment complexity, network latency, distributed debugging, and zero benefit at this scale.
- **Not vanilla Laravel MVC:** The default Laravel structure (`app/Models`, `app/Http/Controllers`, `app/Services`) becomes unmanageable past ~30 models and ~50 controllers. This system has ~20+ tables — it will cross that threshold.
- **Modular monolith** keeps the simplicity of a single deployment while organizing code into clean, bounded domains that can be independently understood, tested, and potentially extracted later.

#### Module Structure

```
app/
  Modules/
    Shared/              # Shared kernel — User model, Permission system, base classes
      Models/
      Services/
      Traits/
      Middleware/
    Auth/                # Authentication, registration, password reset
      Controllers/
      Services/
      Livewire/
      Routes/
      Lang/
    Tickets/             # Core ticketing — creation, lifecycle, state machine
      Controllers/
      Models/
      Services/
      StateMachine/
      Livewire/
      Routes/
      Lang/
    Assignment/          # Group assignment, self-assign, peer transfers, manager override
      Controllers/
      Models/
      Services/
      Livewire/
      Routes/
      Lang/
    Escalation/          # Condition reports, maintenance requests, approval workflow
      Controllers/
      Models/
      Services/
      Livewire/
      Routes/
      Lang/
    Communication/       # Comments, notification engine
      Controllers/
      Models/
      Services/
      Notifications/
      Events/
      Livewire/
      Routes/
      Lang/
    SLA/                 # SLA timers, business hours, warning/breach logic
      Models/
      Services/
      Listeners/
      Commands/
      Lang/
    CSAT/                # Post-resolution feedback system
      Controllers/
      Models/
      Services/
      Livewire/
      Routes/
      Lang/
    Precedent/           # Resolution capture, auto-suggest, linking
      Controllers/
      Models/
      Services/
      Livewire/
      Routes/
      Lang/
    Reporting/           # Reports, charts, CSV/XLSX export
      Controllers/
      Services/
      Livewire/
      Routes/
      Lang/
    Admin/               # Admin configuration panel for all manageable entities
      Controllers/
      Livewire/
      Routes/
      Lang/
    Audit/               # Audit logging system
      Models/
      Services/
      Listeners/
      Livewire/
      Routes/
      Lang/
```

#### Module Communication Rules

1. **Modules communicate through service interfaces and events, never by directly importing another module's models.**
   - The Tickets module fires `TicketStatusChanged` — the SLA module and Communication module listen. They do not import each other's classes.
   - If Module A needs data from Module B, it calls Module B's service interface, never its models directly.

2. **Shared kernel** (`Shared/` module) contains the User model, Permission system, base traits, and middleware. All modules may depend on Shared. Shared depends on nothing.

3. **No circular dependencies.** If Module A depends on Module B, Module B must NOT depend on Module A. Use events to break cycles.

4. **Each module owns its migrations, routes, translations, and Livewire components.** Migrations are prefixed by module name for ordering clarity (e.g., `2026_04_01_000001_create_tickets_table`).

5. **Service providers:** Each module has its own service provider that registers routes, event listeners, and bindings. All module service providers are registered in `config/app.php`.

### 2.2 Tech Stack (V1 Final)

| Layer | Technology | Version | Notes |
|-------|-----------|---------|-------|
| Language | PHP | 8.4-fpm | |
| Framework | Laravel | ^12.0 | |
| Frontend | Livewire | ^3.0 | Server-driven reactivity |
| JS | Alpine.js | ^3.x | UI micro-interactions only |
| CSS | Tailwind CSS | ^4.0 | CSS-first config (no config file) |
| Database | MySQL | 8.0 | **Shared infra — do not change.** Provided by `_infra/` directory. |
| Cache/Queue | Redis | 7-alpine | **Shared infra — do not change.** Provided by `_infra/` directory. |
| Queue Monitor | Laravel Horizon | ^5.x | Redis queue dashboard |
| Auth | Laravel Sanctum | ^4.x | Session-based web auth (token auth ready for future API) |
| Email (prod) | Resend | — | Pluggable provider interface; 3k emails/mo free tier |
| Email (dev) | Mailpit | latest | **Shared infra — do not change.** Provided by `_infra/` directory. |
| Web Server | Nginx | alpine | Reverse proxy + PHP-FPM gateway |
| Dev DB GUI | phpMyAdmin | latest | **Shared infra — do not change.** Provided by `_infra/` directory. Dev only — never expose in production. |

> **Real-time deferred:** Laravel Echo + Reverb (WebSocket) is not included in V1. All UI updates require page refresh or Livewire polling. Echo/Reverb can be added later without architectural changes.

#### Shared Infrastructure Note

MySQL 8.0, Redis 7-alpine, phpMyAdmin, and Mailpit are provided by a shared `_infra/` directory used by other applications. **These container versions must not be changed.** The ticketing system's Docker Compose file must connect to these existing services, not spin up its own instances.

#### Compatibility Verification

All shared infra versions have been cross-checked against the full stack:

| Component | MySQL 8.0 | Redis 7 | Status |
|-----------|-----------|---------|--------|
| Laravel 12 | ✅ MySQL 8.0+ is the documented minimum | ✅ Redis 6.2+ supported | No issues |
| Livewire 3 | ✅ No direct DB dependency — uses Laravel's DB layer | ✅ No direct Redis dependency | No issues |
| Laravel Horizon 5.x | N/A | ✅ Requires Redis (no minimum version beyond what Laravel supports) | No issues |
| Laravel Sanctum 4.x | ✅ Uses Laravel's session/token tables — no MySQL version-specific features | ✅ Sessions via Redis — standard commands only | No issues |
| ULID primary keys | ✅ Stored as `char(26)` — works on any MySQL version | N/A | No issues |
| FULLTEXT search | ✅ InnoDB FULLTEXT available since MySQL 5.6 | N/A | No issues |

> ⚠️ **MySQL 8.0 EOL warning:** MySQL 8.0 reached end-of-life in April 2026. It will no longer receive security updates from Oracle. This is acceptable for now since the shared infra is locked, but plan to coordinate an upgrade to MySQL 8.4 LTS across all apps when feasible. No code changes will be required — Laravel 12 works identically on 8.0 and 8.4.

### 2.3 Critical Technical Decisions

#### Primary Keys: ULID

All models use **ULID** (Universally Unique Lexicographically Sortable Identifier) as the primary key. Laravel supports this natively via the `HasUlids` trait.

- ULIDs are ordered (timestamp-prefixed), avoiding the index fragmentation problem of UUID v4 in MySQL.
- The sequential display number (`TKT-0000001`) is a separate column for human readability only.

> ⚠️ **SECURITY:** API endpoints MUST only accept ULID identifiers, never sequential display numbers. Display numbers are for UI/email display only. Accepting display numbers in API routes enables ticket enumeration attacks.

#### Search Architecture

Full-text search is implemented behind a `SearchServiceInterface` contract.

- **V1 implementation:** `MySqlSearchDriver` using MySQL FULLTEXT indexes.
- **Future upgrade:** `MeilisearchDriver` via Laravel Scout — drop-in replacement without touching any calling code.
- The interface defines: `search(string $query, array $filters): LengthAwarePaginator`
- This abstraction is critical for bilingual AR/EN content where MySQL FULLTEXT has known limitations.

#### Authentication Architecture

V1 uses email + password with Laravel Sanctum session-based authentication.

- Auth layer is built behind a contract (`AuthProviderInterface`).
- V1 implements `EmailPasswordAuthProvider`.
- Future SSO providers (Entra ID, Google, Okta) implement the same interface — zero changes to business logic.
- Password requirements: minimum 10 characters, uppercase + lowercase + number + special character.
- Passwords hashed with bcrypt (Laravel default).
- Session timeout: configurable per deployment, default 8 hours.

#### Soft-Delete & Active Status Policy

The system uses **two independent mechanisms** for different purposes:

| Mechanism | Column | Purpose | Example |
|-----------|--------|---------|---------|
| **Soft-delete** | `deleted_at` (Laravel `SoftDeletes` trait) | Entity is permanently removed from the system. Hidden everywhere. Kept only so existing records that reference it can still display historical data. | Deleting a category that old tickets reference. |
| **Active flag** | `is_active` (boolean, default `true`) | Entity is temporarily disabled. Not available for new selections but not gone. Can be reactivated at any time. | IT Manager pauses a category during reorganization. |

**Rules:**
- `is_active = false`: entity hidden from dropdowns and new forms, but existing records display it normally. Can be reactivated.
- `deleted_at IS NOT NULL`: entity hidden from everything except historical display on existing records. Cannot be undone from UI (only database recovery).
- Queries for "available options" (dropdowns, assignment targets) filter by: `WHERE is_active = true AND deleted_at IS NULL`.
- Queries for "display existing data" (ticket detail view showing the category it was created with) do NOT filter by `is_active` or `deleted_at`.

**Tables that use BOTH `is_active` and `deleted_at`:**
- `categories`, `subcategories`, `groups`, `departments`, `locations`, `custom_fields`, `custom_field_options`

**Tables that use ONLY `deleted_at`:**
- `users` (deactivated accounts)

**Tables that use NEITHER (append-only or permanent):**
- `audit_logs` (immutable, no deletes ever)
- `permissions` (seeded, not user-manageable)
- `ticket_attachments`, `condition_report_attachments` (file records tied to tickets)
- `sla_pause_logs`, `notification_logs` (historical records)

---

## 3. Security Requirements (Cross-Cutting)

**These requirements apply to EVERY phase. They are not optional and must not be deferred.**

### 3.1 Input Validation

- All user input validated server-side using Laravel Form Requests.
- Client-side validation is a UX convenience only — never a security measure.
- String length enforcement: subject (255 max), description (50,000 max), comments (10,000 max).
- Enum fields (status, priority, resolution type) validated against allowed values.
- Foreign key references (category_id, group_id) validated for existence.

### 3.2 HTML Sanitization

> ⚠️ **SECURITY:** All rich text input (ticket descriptions, comments, resolution steps) MUST be sanitized server-side before storage using a whitelist-based HTML purifier (e.g., `htmlpurifier` or `Sterilize`).

**Whitelist allows:** bold, italic, underline, text color, font size, alignment, ordered/unordered lists, tables, links, embedded video (whitelisted domains only).

**Stripped:** inline images, JavaScript, event handlers, iframes (except whitelisted video domains), `<script>`, `<style>`, `<object>`, `<embed>`, `<form>`, data URIs.

Raw HTML is NEVER rendered unsanitized.

### 3.3 Authorization

- Every controller action checks permissions via middleware or policy classes.
- Eloquent global scopes enforce employee visibility (employees see only their own tickets).
- Group Manager assignment scoping enforced server-side, not just UI.
- Permission checks at controller level AND in Livewire components.
- No security-sensitive logic relies on frontend-only checks.

### 3.4 File Upload Security

> ⚠️ **SECURITY:** All file uploads follow these rules:

1. Server-side MIME type validation using file signatures (magic bytes), not file extension.
2. File size enforcement server-side (10MB max per file).
3. File count enforcement server-side (5 per upload action).
4. Files stored **outside** the web root with ULID-based randomized names.
5. File access via a controller route that checks authorization (user must be requester, assigned tech, or have `ticket.view-all` permission).
6. Image processing pipeline: strip EXIF → resize to 2048px max edge → compress to 80% JPEG. **Original discarded.**
7. DOCX and PDF validation for signed maintenance request uploads (validated by MIME type, not extension).
8. Rate limited: 20 uploads/hour per user.
9. Allowed file types — Attachments: JPEG, PNG, HEIC, PDF, DOCX, XLSX. Signed maintenance request: DOCX or PDF only.

### 3.5 Rate Limiting

> ⚠️ **SECURITY:** Rate limiting applied to all endpoints. Redis-backed for distributed counting.

| Endpoint | Limit | Scope |
|----------|-------|-------|
| Login attempts | 5/minute | Per IP + per email |
| Registration | 3/hour | Per IP |
| Password reset | 3/hour | Per email |
| Ticket creation | 10/hour | Per user |
| Comment creation | 30/hour | Per user |
| File uploads | 20/hour | Per user |
| General API | 60/minute | Per user |

Failed login tracking is per-IP AND per-account to prevent both brute force and credential stuffing.

### 3.6 CSRF & Session Security

- All state-changing requests require CSRF token validation (Laravel default).
- Sessions use HttpOnly, Secure, SameSite=Lax cookies.
- Session data stored in Redis (not filesystem).
- Session ID regenerated on login.

### 3.7 HTTP Security Headers

Applied via middleware to every response:

- `Content-Security-Policy`: strict policy, nonce-based for Livewire inline scripts if needed.
- `X-Content-Type-Options: nosniff`
- `X-Frame-Options: DENY`
- `Strict-Transport-Security: max-age=31536000; includeSubDomains`
- `Referrer-Policy: strict-origin-when-cross-origin`
- `Permissions-Policy`: restrict camera, microphone, geolocation.

### 3.8 Data Protection

- All database queries use parameterized statements (Eloquent/Query Builder default).
- No raw SQL queries without parameter binding.
- Sensitive data (passwords) never logged, never in API responses.
- Audit log entries never contain password values.
- File paths never user-controllable — all paths generated server-side.

---

## 4. Localization Requirements (Cross-Cutting)

**Localization is a day-one requirement, not a retrofit. Every phase must comply.**

### 4.1 Supported Languages

| Language | Code | Direction | Role |
|----------|------|-----------|------|
| Arabic | ar | RTL | Primary / Default |
| English | en | LTR | Secondary |

### 4.2 Implementation Rules

- Every user-facing string goes through Laravel's `__()` translation helper or `@lang` Blade directive.
- Translation files: `resources/lang/ar/*.php` and `resources/lang/en/*.php`, organized by module domain (`tickets.php`, `auth.php`, `admin.php`, `notifications.php`, etc.).
- Default application locale: `ar` (set in `config/app.php`).
- User language preference stored in `users.locale` column (default `'ar'`).
- Locale set per-request via middleware based on authenticated user's preference.
- All email notifications rendered in the recipient's preferred language.
- Admin UI is also fully bilingual — same rules apply.
- All admin-managed entities (categories, subcategories, departments, locations, custom fields, tags, response templates) have bilingual name columns: `name_ar` and `name_en`.

### 4.3 RTL Layout Rules

- Use CSS logical properties throughout: `margin-inline-start/end`, `padding-inline-start/end`, `inset-inline-start/end`, `border-inline-start/end` — never `margin-left/right` for directional spacing.
- HTML `<html>` tag sets `dir="rtl"` or `dir="ltr"` and `lang` attribute based on user locale.
- Tailwind CSS v4 logical property utilities: `ms-*`, `me-*`, `ps-*`, `pe-*`.
- Icon mirroring: directional icons (arrows, chevrons) must flip in RTL.
- Tables: column order does NOT reverse in RTL (data tables keep consistent column order).
- Form labels and inputs: labels appear on the inline-start side.
- Numbers and dates: displayed in Western Arabic numerals (0-9) in both locales for consistency.

### 4.4 Content Language vs UI Language

User-generated content (ticket subjects, descriptions, comments) can be in any language regardless of the user's UI language preference. The UI language only affects system labels, buttons, navigation, and system-generated messages. No automatic translation of user content.

---

## 5. Database Conventions (Cross-Cutting)

### 5.1 Foreign Key ON DELETE Behavior

Every foreign key relationship has an explicit ON DELETE strategy. **No FK is left to implicit database defaults.**

| Pattern | ON DELETE | When Used |
|---------|-----------|-----------|
| **RESTRICT** | Prevent deletion | When the parent must exist for data integrity. Used for: `tickets.requester_id → users`, `tickets.category_id → categories`, `comments.user_id → users`, `audit_logs.actor_id → users` |
| **SET NULL** | Set FK to NULL | When the relationship is optional and the child record should survive. Used for: `tickets.assigned_to → users`, `tickets.subcategory_id → subcategories`, `groups.manager_id → users`, `tickets.department_id → departments`, `tickets.location_id → locations` |
| **CASCADE** | Delete children | When children are meaningless without the parent. Used for: `ticket_attachments.ticket_id → tickets`, `condition_report_attachments.condition_report_id → condition_reports`, `custom_field_values.ticket_id → tickets`, `permission_user.user_id → users`, `permission_user.permission_id → permissions`, `group_user.group_id → groups`, `group_user.user_id → users` |

**Important:** Because the system uses soft-deletes (`deleted_at`) on most parent entities, ON DELETE CASCADE/RESTRICT rarely fires at the database level. The application layer handles soft-delete cascading logic. The FK constraints serve as a safety net against hard-delete bugs.

### 5.2 Composite Unique Constraints

| Table | Unique Constraint | Purpose |
|-------|-------------------|---------|
| `permission_user` | `(user_id, permission_id)` | Prevent duplicate permission grants |
| `group_user` | `(group_id, user_id)` | Prevent duplicate group membership |
| `csat_ratings` | `(ticket_id)` | One CSAT rating per ticket |
| `resolutions` | `(ticket_id)` | One resolution record per ticket |
| `tech_profiles` | `(user_id)` | One tech profile per user |
| `tickets` | `(display_number)` | Unique display number |
| `permissions` | `(key)` | Unique permission key |

### 5.3 Indexing Strategy

**Automatic indexes:**
- Primary keys (ULID) — auto-indexed
- Foreign keys — explicitly indexed in every migration
- Unique constraints — auto-indexed

**Additional indexes for query performance:**

| Table | Index | Justification |
|-------|-------|---------------|
| `tickets` | `(status)` | Status filtering on all dashboards |
| `tickets` | `(priority)` | Priority filtering and SLA lookups |
| `tickets` | `(status, group_id)` | Tech dashboard: tickets by status in my groups |
| `tickets` | `(status, assigned_to)` | Tech dashboard: my tickets by status |
| `tickets` | `(requester_id, created_at)` | Employee dashboard: my tickets sorted by date |
| `tickets` | `(created_at)` | Date range filtering and reporting |
| `tickets` | `(group_id)` | Group filtering |
| `tickets` | `(display_number)` | Display number lookup (for showing in UI, NOT for API routing) |
| `tickets` | FULLTEXT `(subject)` | Text search on subject |
| `tickets` | FULLTEXT `(description)` | Text search on description |
| `comments` | `(ticket_id, created_at)` | Comments timeline per ticket |
| `comments` | FULLTEXT `(body)` | Text search on comments |
| `audit_logs` | `(action, created_at)` | Audit log filtering by action type and date |
| `audit_logs` | `(target_type, target_id)` | Audit log: all actions on a specific entity |
| `audit_logs` | `(actor_id, created_at)` | Audit log: all actions by a specific user |
| `transfer_requests` | `(ticket_id, status)` | Active transfer request lookup per ticket |
| `csat_ratings` | `(tech_id, status)` | CSAT reporting per tech |
| `csat_ratings` | `(status, expires_at)` | Pending CSAT lookup for login prompt |
| `ticket_sla` | `(response_status)` | SLA dashboard: breached/warning tickets |
| `ticket_sla` | `(resolution_status)` | SLA dashboard: breached/warning tickets |
| `custom_field_values` | `(ticket_id)` | All custom field values for a ticket |
| `custom_field_values` | `(custom_field_id)` | Export: all values for a specific field |
| `resolutions` | `(linked_resolution_id)` | Usage count tracking for linked resolutions |

### 5.4 Table Naming

- All tables use **snake_case** plural names (Laravel convention).
- Pivot tables use singular names in alphabetical order: `group_user`, `permission_user`.
- Module-specific tables are NOT prefixed by module name (they live in one shared database).

---

## 6. Phase 1 — Foundation

**Deliverable:** Running Laravel app with authentication, user profiles, role/permission system, tech profile promotion, and the base layout with full AR/EN localization.

### 6.1 Project Setup

- Laravel 12 fresh install with Livewire 3 starter kit
- Docker Compose dev environment: PHP 8.4-fpm, Nginx (connects to shared infra: MySQL 8.0, Redis 7, Mailpit, phpMyAdmin)
- Tailwind CSS 4 configuration (ships with Livewire starter kit)
- Laravel Horizon installed and configured for Redis queues
- Git repository initialized with `.gitignore`, `.env.example`
- Module directory structure created per Section 2.1

### 6.2 Database: Users & Auth

**Table: `users`**

| Column | Type | Constraints | Notes |
|--------|------|-------------|-------|
| id | ULID | PK | `HasUlids` trait |
| full_name | varchar(255) | NOT NULL | |
| email | varchar(255) | NOT NULL, UNIQUE | |
| password | varchar(255) | NOT NULL | bcrypt hashed |
| employee_number | varchar(50) | NULLABLE | Optional identifier |
| department_id | ULID FK | NULLABLE, ON DELETE SET NULL | |
| location_id | ULID FK | NULLABLE, ON DELETE SET NULL | |
| phone | varchar(50) | NULLABLE | |
| locale | varchar(5) | NOT NULL, DEFAULT 'ar' | 'ar' or 'en' |
| is_tech | boolean | NOT NULL, DEFAULT false | Separates employees from tech/admin |
| email_verified_at | timestamp | NULLABLE | |
| remember_token | varchar(100) | NULLABLE | |
| deleted_at | timestamp | NULLABLE | SoftDeletes |
| created_at | timestamp | | |
| updated_at | timestamp | | |

**Table: `tech_profiles`**

| Column | Type | Constraints | Notes |
|--------|------|-------------|-------|
| id | ULID | PK | |
| user_id | ULID FK | NOT NULL, UNIQUE, ON DELETE CASCADE | One profile per user |
| specialization | varchar(255) | NULLABLE | e.g., "Network", "Hardware", "Software" |
| job_title_ar | varchar(255) | NULLABLE | |
| job_title_en | varchar(255) | NULLABLE | |
| internal_notes | text | NULLABLE | IT Manager notes about the tech |
| promoted_at | timestamp | NOT NULL | When promotion happened |
| promoted_by | ULID FK | NOT NULL, ON DELETE RESTRICT | Who promoted them |
| created_at | timestamp | | |
| updated_at | timestamp | | |

**Table: `departments`**

| Column | Type | Constraints | Notes |
|--------|------|-------------|-------|
| id | ULID | PK | |
| name_ar | varchar(255) | NOT NULL | |
| name_en | varchar(255) | NOT NULL | |
| is_active | boolean | NOT NULL, DEFAULT true | |
| sort_order | int | NOT NULL, DEFAULT 0 | |
| deleted_at | timestamp | NULLABLE | SoftDeletes |
| created_at | timestamp | | |
| updated_at | timestamp | | |

**Table: `locations`**

| Column | Type | Constraints | Notes |
|--------|------|-------------|-------|
| id | ULID | PK | |
| name_ar | varchar(255) | NOT NULL | |
| name_en | varchar(255) | NOT NULL | |
| is_active | boolean | NOT NULL, DEFAULT true | |
| sort_order | int | NOT NULL, DEFAULT 0 | |
| deleted_at | timestamp | NULLABLE | SoftDeletes |
| created_at | timestamp | | |
| updated_at | timestamp | | |

**Table: `permissions`**

| Column | Type | Constraints | Notes |
|--------|------|-------------|-------|
| id | ULID | PK | |
| key | varchar(100) | NOT NULL, UNIQUE | e.g., 'ticket.assign' |
| name_ar | varchar(255) | NOT NULL | |
| name_en | varchar(255) | NOT NULL | |
| group_key | varchar(100) | NOT NULL | For UI grouping: 'ticket', 'escalation', 'group', 'category', 'user', 'system' |
| created_at | timestamp | | |
| updated_at | timestamp | | |

**Table: `permission_user`**

| Column | Type | Constraints | Notes |
|--------|------|-------------|-------|
| user_id | ULID FK | ON DELETE CASCADE | |
| permission_id | ULID FK | ON DELETE CASCADE | |
| | | UNIQUE (user_id, permission_id) | Composite unique |

### 6.3 Permission Registry

All permissions are seeded from `config/permissions.php`. Not user-editable. The seeder runs on every deployment.

**Ticket Management:**
- `ticket.assign` — Assign/reassign any ticket to any tech (global)
- `ticket.close` — Close any ticket from any state (mandatory reason)
- `ticket.view-all` — View all tickets across all groups
- `ticket.manage-priority` — Change ticket priority after creation
- `ticket.delete` — Permanently delete a ticket

**Escalation & Approval:**
- `escalation.approve` — Approve/reject condition reports and final signed maintenance requests

**Group Management:**
- `group.manage` — Create/edit/delete groups
- `group.manage-manager` — Assign/remove group manager role
- `group.manage-members` — Add/remove techs from groups

**Category Management:**
- `category.manage` — Create/edit/delete categories and subcategories, manage category→group mapping

**User & Account Management:**
- `user.promote` — Promote an employee to tech/admin
- `user.manage-permissions` — Grant/revoke permissions for other users
- `user.view-directory` — View employee directory and contact details

**System Administration:**
- `system.view-audit-log` — View audit logs
- `system.manage-notifications` — Configure notification templates and settings
- `system.manage-departments` — Manage the department dropdown list
- `system.manage-locations` — Manage the location/site dropdown list
- `system.manage-tags` — Create/edit/delete ticket tags
- `system.manage-response-templates` — Create/edit/delete comment response templates
- `system.manage-custom-fields` — Create/edit/deactivate custom fields
- `system.view-reports` — View reports and export ticket data
- `system.manage-sla` — Configure SLA targets, business hours, warning thresholds

### 6.4 Pre-defined Role Bundles

These are convenience methods that bulk-assign permission sets — NOT database entities.

| Role | Permissions | Notes |
|------|-------------|-------|
| Technician | `ticket.view-all`, `user.view-directory` | Self-assign and escalation submission are implicit for all techs |
| Group Manager | Technician + `group.manage-members` | Can assign tickets within their group only (role-based logic, not `ticket.assign` permission) |
| IT Manager | All permissions | Full system administration |

### 6.5 Implicit Behaviors (Not Permissions)

Available by default, no permission assignment needed:
- **Self-assign** — any tech can grab an unassigned ticket
- **Escalation submission** — any tech can submit a condition report
- **Commenting** — anyone involved in a ticket can comment
- **Peer transfer** — any tech can send/receive transfer requests
- **Cancel ticket** — requester only, from any state

### 6.6 Authentication Flow

- **Registration:** full_name, email, password, confirm password, employee_number (optional), department (dropdown), location (dropdown), phone (optional)
- **Login:** email + password, rate limited (5 attempts/min per IP + per email)
- **Password reset:** email-based token flow, token expires in 1 hour
- **Profile edit:** all registration fields editable, plus language preference toggle (AR/EN)
- **Session:** Redis-backed, HttpOnly + Secure + SameSite=Lax cookies, regenerated on login

> ⚠️ **SECURITY:** Password policy: minimum 10 characters, uppercase + lowercase + number + special character. bcrypt hashing. Failed login tracking per IP AND per email.

### 6.7 Tech Promotion Flow

When IT Manager (or anyone with `user.promote`) promotes an employee:

1. Employee's `is_tech` flag set to `true`
2. A `tech_profiles` record is created with:
   - Specialization (optional dropdown or free text)
   - Job title AR/EN (optional)
   - Internal notes (optional)
   - `promoted_at` timestamp
   - `promoted_by` FK to promoting user
3. Permissions can then be assigned to the user

Demotion: set `is_tech = false`. Tech profile record is kept for historical reference. Permissions can be revoked individually or bulk-revoked.

### 6.8 Base Layout

- App layout with sidebar navigation (collapsible), top bar with user menu
- Sidebar items dynamically shown based on user permissions and `is_tech` flag
- Language switcher in user menu (toggles locale, saves to profile, reloads page)
- All layout strings in translation files
- RTL/LTR applied via middleware setting `dir` and `lang` on `<html>` tag
- Tailwind logical property utilities used throughout

### 6.9 SuperUser

- Seeded via artisan command: `php artisan app:create-superuser`
- Not creatable through UI
- Has a `is_super_user` flag on the `users` table (boolean, default false)
- Bypasses all permission checks
- Can access all deployments (for multi-deployment management)
- Used for initial IT Manager setup and platform-level configuration
- Not visible to buyer organizations

### 6.10 Phase 1 Acceptance Criteria

- [ ] User can register, log in, reset password, edit profile
- [ ] Locale switches between AR and EN; layout flips RTL/LTR correctly
- [ ] Permission system functional: grant, revoke, check via middleware and Blade
- [ ] IT Manager account can be created and assigned all permissions via seeder/command
- [ ] Tech promotion flow works: promote employee, create tech profile, assign permissions
- [ ] Docker Compose stack runs with all services (PHP, Nginx, connected to shared infra MySQL, Redis, Mailpit)
- [ ] All user-facing strings are in translation files (no hardcoded strings)
- [ ] Rate limiting active on login and registration endpoints
- [ ] Security headers configured via middleware
- [ ] Module directory structure in place

---

## 7. Phase 2 — Core Ticketing

**Deliverable:** Ticket creation, status lifecycle, group/tech assignment, peer transfers, file uploads. The core product loop.

### 7.1 Database: Tickets & Assignment

**Table: `tickets`**

| Column | Type | Constraints | Notes |
|--------|------|-------------|-------|
| id | ULID | PK | |
| display_number | varchar(20) | NOT NULL, UNIQUE | Format: TKT-0000001 |
| subject | varchar(255) | NOT NULL | |
| description | text | NOT NULL | HTML, sanitized before storage |
| status | enum | NOT NULL | awaiting_assignment, in_progress, on_hold, awaiting_approval, action_required, awaiting_final_approval, resolved, closed, cancelled |
| priority | enum | NULLABLE | low, medium, high, critical. NULL until tech sets it. |
| category_id | ULID FK | NOT NULL, ON DELETE RESTRICT | |
| subcategory_id | ULID FK | NULLABLE, ON DELETE SET NULL | |
| group_id | ULID FK | NOT NULL, ON DELETE RESTRICT | Auto-set from category mapping |
| assigned_to | ULID FK | NULLABLE, ON DELETE SET NULL | |
| requester_id | ULID FK | NOT NULL, ON DELETE RESTRICT | |
| location_id | ULID FK | NULLABLE, ON DELETE SET NULL | From submission form |
| department_id | ULID FK | NULLABLE, ON DELETE SET NULL | From submission form |
| close_reason | varchar(100) | NULLABLE | From hardcoded dropdown |
| close_reason_text | text | NULLABLE | Free text for "Other" |
| incident_origin | varchar(20) | NOT NULL, DEFAULT 'web' | |
| resolved_at | timestamp | NULLABLE | |
| closed_at | timestamp | NULLABLE | |
| cancelled_at | timestamp | NULLABLE | |
| deleted_at | timestamp | NULLABLE | SoftDeletes |
| created_at | timestamp | | |
| updated_at | timestamp | | |

**Table: `categories`**

| Column | Type | Constraints | Notes |
|--------|------|-------------|-------|
| id | ULID | PK | |
| name_ar | varchar(255) | NOT NULL | |
| name_en | varchar(255) | NOT NULL | |
| group_id | ULID FK | NOT NULL, ON DELETE RESTRICT | Auto-assignment target |
| is_active | boolean | NOT NULL, DEFAULT true | |
| sort_order | int | NOT NULL, DEFAULT 0 | |
| version | int | NOT NULL, DEFAULT 1 | Incremented on changes |
| deleted_at | timestamp | NULLABLE | SoftDeletes |
| created_at | timestamp | | |
| updated_at | timestamp | | |

**Table: `subcategories`**

| Column | Type | Constraints | Notes |
|--------|------|-------------|-------|
| id | ULID | PK | |
| category_id | ULID FK | NOT NULL, ON DELETE CASCADE | |
| name_ar | varchar(255) | NOT NULL | |
| name_en | varchar(255) | NOT NULL | |
| is_required | boolean | NOT NULL, DEFAULT false | Whether subcategory is required for this category |
| is_active | boolean | NOT NULL, DEFAULT true | |
| sort_order | int | NOT NULL, DEFAULT 0 | |
| version | int | NOT NULL, DEFAULT 1 | |
| deleted_at | timestamp | NULLABLE | SoftDeletes |
| created_at | timestamp | | |
| updated_at | timestamp | | |

**Table: `groups`**

| Column | Type | Constraints | Notes |
|--------|------|-------------|-------|
| id | ULID | PK | |
| name_ar | varchar(255) | NOT NULL | |
| name_en | varchar(255) | NOT NULL | |
| manager_id | ULID FK | NULLABLE, ON DELETE SET NULL | Optional group manager |
| is_active | boolean | NOT NULL, DEFAULT true | |
| deleted_at | timestamp | NULLABLE | SoftDeletes |
| created_at | timestamp | | |
| updated_at | timestamp | | |

**Table: `group_user`**

| Column | Type | Constraints | Notes |
|--------|------|-------------|-------|
| group_id | ULID FK | ON DELETE CASCADE | |
| user_id | ULID FK | ON DELETE CASCADE | |
| | | UNIQUE (group_id, user_id) | Composite unique |

**Table: `transfer_requests`**

| Column | Type | Constraints | Notes |
|--------|------|-------------|-------|
| id | ULID | PK | |
| ticket_id | ULID FK | NOT NULL, ON DELETE CASCADE | |
| from_user_id | ULID FK | NOT NULL, ON DELETE RESTRICT | |
| to_user_id | ULID FK | NOT NULL, ON DELETE RESTRICT | |
| status | enum | NOT NULL | pending, accepted, rejected, revoked |
| responded_at | timestamp | NULLABLE | |
| created_at | timestamp | | |
| updated_at | timestamp | | |

**Index:** `(ticket_id, status)` for active transfer lookup.
**Constraint:** Application-level enforcement of one `pending` request per ticket at a time.

**Table: `ticket_attachments`**

| Column | Type | Constraints | Notes |
|--------|------|-------------|-------|
| id | ULID | PK | |
| ticket_id | ULID FK | NOT NULL, ON DELETE CASCADE | |
| original_name | varchar(255) | NOT NULL | Original filename for display |
| file_path | varchar(500) | NOT NULL | Server path (ULID-based name) |
| file_size | int unsigned | NOT NULL | Bytes |
| mime_type | varchar(100) | NOT NULL | Validated server-side |
| uploaded_by | ULID FK | NOT NULL, ON DELETE RESTRICT | |
| created_at | timestamp | | |
| updated_at | timestamp | | |

### 7.2 Display Number Generation

Sequential display number formatted as `TKT-0000001`.

**Implementation:** A dedicated `ticket_counters` table with a single row and row-level locking:

```
ticket_counters: id (int PK, always 1), last_number (bigint unsigned, default 0)
```

On ticket creation: `UPDATE ticket_counters SET last_number = last_number + 1 WHERE id = 1` with `DB::transaction()` and `lockForUpdate()`. The returned value is zero-padded and prefixed.

> ⚠️ **SECURITY:** The display number is NEVER used in URLs, API calls, or route parameters. All programmatic references use the ULID. Display numbers are for UI/email only.

### 7.3 Ticket Creation Flow

1. Employee fills: Subject, Description (rich text), Category (dropdown), Subcategory (conditional dropdown), Location, Department, Attachments (optional)
2. System auto-populates: requester (logged-in user), display number, status (`awaiting_assignment`), incident origin (`web`), group (from category→group mapping), created_at
3. Priority is NOT shown on submission form — set by tech later
4. File attachments: up to 5 files, 10MB each, validated server-side (MIME + size)
5. Images processed: EXIF stripped, resized to 2048px max, compressed to 80% JPEG. Original discarded.
6. Files stored with ULID-based names outside web root

### 7.4 Status Transitions (State Machine)

Ticket status transitions are enforced by a `TicketStateMachine` service class in the Tickets module. Invalid transitions throw an exception. **The state machine is the ONLY way to change ticket status — no direct column updates anywhere.**

| From | To | Triggered By | Conditions |
|------|-----|-------------|------------|
| awaiting_assignment | in_progress | System | Tech assigned (self-assign or manual) |
| in_progress | on_hold | Tech | Manual, any time while assigned |
| on_hold | in_progress | Tech | Resume from hold |
| in_progress | awaiting_approval | System | Tech submits Condition Report |
| awaiting_approval | action_required | System | Approver approves Condition Report |
| awaiting_approval | in_progress | Approver | Approver rejects Condition Report |
| action_required | awaiting_final_approval | System | Employee uploads signed Maintenance Request |
| awaiting_final_approval | resolved | Approver | Final form approved |
| awaiting_final_approval | action_required | Approver | Form rejected (resubmit) |
| in_progress | resolved | Tech | Standard resolution (must fill resolution form) |
| Any | closed | permission:ticket.close | Mandatory close reason |
| Any | cancelled | Requester only | Self-cancellation |

> ⚠️ **CRITICAL:** Every transition fires a `TicketStatusChanged` event. The SLA module, Communication module, Audit module, and CSAT module all listen to this event. This is the primary inter-module communication mechanism.

### 7.5 Assignment Logic

**Auto-assignment to group:**
- On ticket creation, `group_id` is set from the `categories.group_id` mapping.

**Individual assignment:**
- **Self-assign:** Any tech grabs an unassigned ticket from any group's queue. Triggers status → `in_progress`.
- **Group Manager:** Assigns tickets within their group to individual techs. Scoped by role logic (not `ticket.assign` permission). Enforced server-side.
- **IT Manager / `permission:ticket.assign`:** Assigns any ticket to any tech.

**Peer Transfer:**
- Tech A sends transfer request to Tech B. One active (pending) request per ticket.
- To request a different tech, Tech A must revoke current request first.
- Tech A continues working while request is pending — no blocking.
- Tech B accepts → ticket reassigned. Tech B rejects → no change.
- Transfer requests are never deleted — historical record.

**Manager Override:**
- IT Manager or `permission:ticket.assign` holders reassign any ticket instantly.
- No acceptance workflow — direct transfer, logged in audit.

### 7.6 Close Reasons (Hardcoded)

When closing a ticket, a close reason must be selected:
- Duplicate ticket
- Not an IT issue
- Cannot reproduce
- Out of scope
- Requester unresponsive
- Resolved externally
- Other (free text required when selected)

### 7.7 Phase 2 Acceptance Criteria

- [ ] Employee can create a ticket with all fields, including file attachments
- [ ] Ticket gets correct display number and auto-assigns to correct group
- [ ] Tech can self-assign, work, put on hold, resume, and resolve (with resolution form)
- [ ] Peer transfer flow works: request, accept/reject, revoke
- [ ] Manager override works with proper permission check
- [ ] Group Manager can assign within their group only (server-side enforcement)
- [ ] Status transitions enforced by state machine — invalid transitions rejected
- [ ] All ticket data accessible only via ULID routes, never display number
- [ ] File uploads validated, processed, and stored securely
- [ ] Employee can only see their own tickets (global scope enforcement)
- [ ] All strings localized (AR/EN), layout correct in both directions

---

## 8. Phase 3 — Escalation Workflow

**Deliverable:** Complete escalation path: Condition Report submission, approval, auto-generated Maintenance Request document (AR/EN), requester signature and re-upload, and final approval with reject/resubmit loop.

### 8.1 Database: Escalation

**Table: `condition_reports`**

| Column | Type | Constraints | Notes |
|--------|------|-------------|-------|
| id | ULID | PK | |
| ticket_id | ULID FK | NOT NULL, ON DELETE CASCADE | |
| report_type | varchar(255) | NOT NULL | |
| location_id | ULID FK | NULLABLE, ON DELETE SET NULL | From admin-configured list |
| report_date | date | NOT NULL | Auto-filled |
| current_condition | text | NOT NULL | |
| condition_analysis | text | NOT NULL | |
| required_action | text | NOT NULL | |
| tech_id | ULID FK | NOT NULL, ON DELETE RESTRICT | Submitting tech |
| status | enum | NOT NULL, DEFAULT 'pending' | pending, approved, rejected |
| reviewed_by | ULID FK | NULLABLE, ON DELETE SET NULL | |
| reviewed_at | timestamp | NULLABLE | |
| review_notes | text | NULLABLE | |
| created_at | timestamp | | |
| updated_at | timestamp | | |

**Table: `condition_report_attachments`**

| Column | Type | Constraints | Notes |
|--------|------|-------------|-------|
| id | ULID | PK | |
| condition_report_id | ULID FK | NOT NULL, ON DELETE CASCADE | |
| original_name | varchar(255) | NOT NULL | |
| file_path | varchar(500) | NOT NULL | |
| file_size | int unsigned | NOT NULL | |
| mime_type | varchar(100) | NOT NULL | |
| created_at | timestamp | | |
| updated_at | timestamp | | |

**Table: `maintenance_requests`**

| Column | Type | Constraints | Notes |
|--------|------|-------------|-------|
| id | ULID | PK | |
| ticket_id | ULID FK | NOT NULL, ON DELETE CASCADE | |
| generated_file_path | varchar(500) | NOT NULL | System-generated DOCX |
| generated_locale | varchar(5) | NOT NULL | 'ar' or 'en' — language of the generated document |
| submitted_file_path | varchar(500) | NULLABLE | Signed version uploaded by requester |
| submitted_at | timestamp | NULLABLE | |
| status | enum | NOT NULL, DEFAULT 'pending' | pending, submitted, approved, rejected |
| reviewed_by | ULID FK | NULLABLE, ON DELETE SET NULL | |
| reviewed_at | timestamp | NULLABLE | |
| review_notes | text | NULLABLE | |
| rejection_count | int unsigned | NOT NULL, DEFAULT 0 | |
| created_at | timestamp | | |
| updated_at | timestamp | | |

### 8.2 Condition Report Flow

1. Tech opens escalation form from ticket detail view
2. Fills structured fields: report type, location, current condition, analysis, required action
3. Optionally attaches evidence photos/documents (same upload security rules as ticket attachments)
4. Submits → ticket status transitions to `awaiting_approval` via state machine
5. Approver (`permission:escalation.approve`) sees report, approves or rejects:
   - **Approve** → ticket moves to `action_required`, maintenance request workflow triggered
   - **Reject** → ticket returns to `in_progress`, tech can resubmit or resolve normally

### 8.3 Maintenance Request Document

When ticket enters `action_required`, the system auto-generates a pre-filled Word document (DOCX). **No admin-managed templates.** The document structure is hardcoded in the application.

#### 8.3.1 Document Content (Pre-filled from system data)

The generated document contains the following sections:

1. **Header:** Company name + logo (from tenant config / `app_settings`)
2. **Document title:** "Maintenance Request" (localized)
3. **Ticket information:** Display number, creation date, category, subcategory
4. **Requester information:** Full name, employee number, department, location
5. **Issue description:** Ticket subject and description (plain text extraction from HTML)
6. **Technical analysis** (from approved Condition Report):
   - Current condition
   - Condition analysis
   - Required action / suggested solution
   - Assigned technician name
7. **Disclaimer (hardcoded, bilingual):**
   - AR: "بناءً على البيانات أعلاه، تخلي وحدة الدعم الفني مسؤوليتها بالكامل عن أي إجراءات ناتجة. كما تحتفظ الوحدة بالصلاحية الكاملة لاتخاذ أي إجراءات تراها مناسبة."
   - EN: "Based on the above data, the Technical Support Unit fully disclaims responsibility for any resulting actions. Additionally, the Unit reserves full authority to take any necessary measures it deems appropriate."
8. **Signature block:** Requester name (pre-filled), signature line (blank — filled offline), date line (blank)

#### 8.3.2 Language Options

The requester sees **two download buttons**:
- **Export in Arabic** — generates RTL document with all labels, headers, and disclaimer in Arabic
- **Export in English** — generates LTR document with all labels, headers, and disclaimer in English

User-generated content (ticket subject, description, condition report text) is included as-is in both versions — it's not translated, only the document structure/labels change.

The `generated_locale` column in `maintenance_requests` records which language was generated.

#### 8.3.3 Document Generation

- Generated server-side using a PHP DOCX library (e.g., `phpoffice/phpword`)
- File stored with ULID-based name outside web root (same security rules as all file uploads)
- A new document is generated on each download (always reflects current data)
- The `generated_file_path` stores the path of the most recently generated version

### 8.4 Requester Upload & Submission Flow

1. Ticket enters `action_required` → `maintenance_requests` record created with `status = 'pending'`
2. Requester sees a policy notice explaining the requirement and the disclaimer
3. Requester chooses **Export in Arabic** or **Export in English** → system generates and downloads the pre-filled DOCX
4. Requester prints, signs, and scans (or digitally signs) the document
5. Requester uploads the signed version (DOCX or PDF accepted) and clicks Submit
6. Ticket moves to `awaiting_final_approval`
7. Approver reviews the signed document:
   - **Approve** → ticket moves to `resolved`
   - **Reject (resubmit)** → ticket loops back to `action_required`, `rejection_count` incremented, requester must re-download (fresh generation), re-sign, and re-upload
   - **Reject permanently** → ticket moves to `closed` with mandatory reason

> ⚠️ **CRITICAL:** On resubmit, the system generates a fresh document reflecting any updated data. The old generated file is kept for audit purposes but is not reused.

### 8.5 Phase 3 Acceptance Criteria

- [ ] Tech can submit a condition report with all fields and attachments
- [ ] Approver can approve/reject condition reports
- [ ] Maintenance Request document generates correctly in Arabic (RTL) and English (LTR)
- [ ] Document contains all pre-filled fields: ticket info, requester info, tech analysis, disclaimer, signature block
- [ ] Company name and logo from tenant config appear in document header
- [ ] Requester can download in either language
- [ ] Requester can upload signed document (DOCX or PDF) and submit
- [ ] Approver can approve, reject (resubmit), or reject permanently
- [ ] Reject-resubmit loop works correctly, rejection count tracks, fresh document generated on resubmit
- [ ] All escalation status transitions enforced by state machine
- [ ] Full localization (AR/EN) on all escalation views

---

## 9. Phase 4 — Communication Layer

**Deliverable:** Comments (public/internal) and notification engine with email delivery.

### 9.1 Database: Comments & Notifications

**Table: `comments`**

| Column | Type | Constraints | Notes |
|--------|------|-------------|-------|
| id | ULID | PK | |
| ticket_id | ULID FK | NOT NULL, ON DELETE CASCADE | |
| user_id | ULID FK | NOT NULL, ON DELETE RESTRICT | |
| body | text | NOT NULL | HTML, sanitized before storage |
| is_internal | boolean | NOT NULL, DEFAULT true | Default to internal for safety |
| created_at | timestamp | | |
| updated_at | timestamp | | |

**Table: `notification_logs`**

| Column | Type | Constraints | Notes |
|--------|------|-------------|-------|
| id | ULID | PK | |
| recipient_id | ULID FK | NOT NULL, ON DELETE CASCADE | |
| type | varchar(100) | NOT NULL | e.g., 'ticket_created', 'sla_warning' |
| channel | varchar(20) | NOT NULL, DEFAULT 'email' | |
| ticket_id | ULID FK | NULLABLE, ON DELETE SET NULL | |
| subject | varchar(500) | NOT NULL | |
| body_preview | varchar(500) | NULLABLE | |
| status | enum | NOT NULL | queued, sent, failed |
| sent_at | timestamp | NULLABLE | |
| failure_reason | text | NULLABLE | |
| attempts | int unsigned | NOT NULL, DEFAULT 0 | |
| created_at | timestamp | | |
| updated_at | timestamp | | |

### 9.2 Comments

- Two types: **public** (visible to requester + tech) and **internal** (tech-side only).
- Clear visual indicator: internal comments get a distinct background color and "Internal" label.
- **Default to internal** when composing — tech must explicitly toggle to public.
- Rich text editor: same capabilities as ticket description, same sanitization.
- Response templates: tech selects a pre-saved template, it pre-fills the comment, tech edits before posting.
- Templates have a default type (public/internal) that pre-sets the toggle.

> ⚠️ **SECURITY:** Internal comments are NEVER exposed to employee-side views, API responses for employees, or notification emails to requesters. The `is_internal` flag is enforced at the query level (explicit `where` clause or scope), not just view level.

### 9.3 Notification Engine

Built as a pluggable provider architecture in the Communication module:
- **Trigger logic:** Determined by event listeners (e.g., `TicketStatusChanged`, `CommentCreated`, `TransferRequestCreated`)
- **Recipient resolution:** Per trigger type (see matrix below)
- **Template rendering:** In recipient's locale using translation files
- **Queuing:** All notifications queued via Redis/Horizon for async delivery
- **Retry:** 3 attempts with exponential backoff on failure
- **Logging:** Every notification attempt logged in `notification_logs`

**Notification Matrix:**

| Trigger | Recipient | Template Key |
|---------|-----------|-------------|
| Ticket created | Requester | notifications.ticket_created |
| Ticket assigned | Tech | notifications.ticket_assigned |
| Escalation submitted | Approver(s) with `escalation.approve` | notifications.escalation_submitted |
| Escalation approved/rejected | Tech | notifications.escalation_updated |
| Action required (form) | Requester | notifications.action_required |
| Form rejected (resubmit) | Requester | notifications.form_rejected |
| Ticket resolved | Tech + Requester | notifications.ticket_resolved |
| Ticket closed | Requester | notifications.ticket_closed |
| SLA warning | Assigned tech | notifications.sla_warning |
| SLA breached | Assigned tech + IT Manager | notifications.sla_breached |
| Transfer request received | Target tech | notifications.transfer_request |
| CSAT reminder | Requester | notifications.csat_reminder |

### 9.4 Phase 4 Acceptance Criteria

- [ ] Public and internal comments work with correct visibility enforcement
- [ ] Internal comments never leak to employee-side views or emails
- [ ] Response templates can be selected, auto-fill, and edited before posting
- [ ] Email notifications fire for all triggers in the matrix
- [ ] Notifications rendered in recipient's preferred language
- [ ] Failed notifications retried (3x) and logged
- [ ] Comment rate limiting active (30/hour per user)

---

## 10. Phase 5 — SLA Engine

**Deliverable:** Response + resolution timers, business hours calculation, clock pausing, warning/breach states, and SLA notifications.

### 10.1 Database: SLA

**Table: `sla_policies`**

| Column | Type | Constraints | Notes |
|--------|------|-------------|-------|
| id | ULID | PK | |
| priority | enum | NOT NULL, UNIQUE | low, medium, high, critical |
| response_target_minutes | int unsigned | NOT NULL | |
| resolution_target_minutes | int unsigned | NOT NULL | |
| use_24x7 | boolean | NOT NULL, DEFAULT false | Override business hours for this priority |
| created_at | timestamp | | |
| updated_at | timestamp | | |

**Table: `ticket_sla`**

| Column | Type | Constraints | Notes |
|--------|------|-------------|-------|
| id | ULID | PK | |
| ticket_id | ULID FK | NOT NULL, UNIQUE, ON DELETE CASCADE | |
| response_target_minutes | int unsigned | NULLABLE | NULL until priority set |
| resolution_target_minutes | int unsigned | NULLABLE | NULL until priority set |
| response_elapsed_minutes | int unsigned | NOT NULL, DEFAULT 0 | |
| resolution_elapsed_minutes | int unsigned | NOT NULL, DEFAULT 0 | |
| response_met_at | timestamp | NULLABLE | When first tech assigned |
| response_status | enum | NOT NULL, DEFAULT 'on_track' | on_track, warning, breached |
| resolution_status | enum | NOT NULL, DEFAULT 'on_track' | on_track, warning, breached |
| last_clock_start | timestamp | NULLABLE | When clock last started/resumed |
| is_clock_running | boolean | NOT NULL, DEFAULT true | |
| created_at | timestamp | | |
| updated_at | timestamp | | |

**Table: `sla_pause_logs`**

| Column | Type | Constraints | Notes |
|--------|------|-------------|-------|
| id | ULID | PK | |
| ticket_sla_id | ULID FK | NOT NULL, ON DELETE CASCADE | |
| paused_at | timestamp | NOT NULL | |
| resumed_at | timestamp | NULLABLE | |
| pause_status | varchar(50) | NOT NULL | Status that caused the pause |
| duration_minutes | int unsigned | NULLABLE | Calculated on resume |
| created_at | timestamp | | |
| updated_at | timestamp | | |

### 10.2 SLA Service

`SlaService` listens to `TicketStatusChanged` events and manages clock state.

**Clock behavior per status:**

| Status | Clock | Reason |
|--------|-------|--------|
| awaiting_assignment | Running | Ticket needs pickup |
| in_progress | Running | Tech actively working |
| on_hold | **Paused** | External factor |
| awaiting_approval | **Paused** | Waiting for approver |
| action_required | **Paused** | Waiting for employee |
| awaiting_final_approval | **Paused** | Waiting for final approver |
| resolved | **Stopped** | Timer complete |
| closed | **Stopped** | Timer complete |
| cancelled | **Stopped** | Timer complete |

**Response timer:** Starts on ticket creation, stops when first tech is assigned (self-assign or manual).

**Resolution timer:** Starts on ticket creation, stops on `resolved`. Excludes paused time.

**Elapsed time = sum of running periods only.**

### 10.3 Business Hours

- SLA timers count **business hours only** by default.
- Business hours configured per deployment in `app_settings`:
  - Working days (e.g., Sun-Thu for Saudi Arabia)
  - Start time and end time (e.g., 08:00-16:00)
- SLA calculation skips non-business hours.
- 24/7 mode available per SLA policy (`use_24x7` flag) — critical priority often uses this.
- **Warning threshold:** Default 75%, configurable per deployment.

### 10.4 Priority Change Impact

When a ticket's priority changes:
- SLA targets are recalculated from the new priority's policy.
- Elapsed time carries over — only the target changes.
- Warning/breach state is re-evaluated against the new target.

### 10.5 Scheduled SLA Check

Laravel scheduled command `sla:check` runs every minute via cron:
- Scans all tickets with `is_clock_running = true`
- Recalculates elapsed time against business hours
- Updates `response_status` and `resolution_status`
- Fires SLA warning/breach notifications as needed

### 10.6 Phase 5 Acceptance Criteria

- [ ] SLA targets configurable per priority level in admin panel
- [ ] `ticket_sla` record created on ticket creation
- [ ] Response timer stops on first tech assignment
- [ ] Resolution timer pauses/resumes correctly on status transitions
- [ ] Business hours calculation correct (skips off-hours and non-working days)
- [ ] Warning and breach states trigger correct notifications
- [ ] SLA indicators (green/yellow/red) on ticket detail and tech dashboard
- [ ] Priority change recalculates targets with elapsed time carried over
- [ ] `sla:check` command correctly transitions timers during off-hours
- [ ] SLA clock pauses logged in `sla_pause_logs` with correct durations

---

## 11. Phase 6 — Dashboards, Search & Filtering

**Deliverable:** Employee dashboard, tech dashboard, IT Manager dashboard with charts, full search and filtering, saved group selections.

### 11.1 Employee Dashboard

- List of own tickets with status indicator badges
- Quick-submit button for new ticket
- Filter by status: open / resolved / closed / cancelled
- Ticket count summary (e.g., 3 open, 12 resolved)
- Search: subject text only

### 11.2 Tech Dashboard

- **Ticket queue:** Unassigned tickets in groups I belong to (self-assign candidates)
- **My tickets:** Assigned to me, sorted by SLA urgency (breached → warning → on track), then priority, then date
- **SLA indicator badges** on each ticket (green/yellow/red)
- **Pending transfer requests:** Incoming transfer requests awaiting my response
- **Group filter panel:** Multi-select groups, saved selections persist server-side per user (in a `user_preferences` JSON column or dedicated table)
- **Quick stats:** My open tickets, resolved this week/month, my SLA compliance rate

### 11.3 IT Manager Dashboard

- **Summary stats:** Tickets by status (counts), tickets by category (counts), tickets created this week/month, avg resolution time
- **SLA section:** Compliance rate (percentage), current breached count, breached tickets list with tech + overdue duration
- **Escalation queue:** Tickets in `awaiting_approval` or `awaiting_final_approval`
- **Unassigned tickets count** across all groups
- **Team workload:** Open tickets per tech (counts)
- **Recent activity feed:** Latest ticket actions system-wide

> **Charts deferred:** All dashboard data is presented as summary stats, counts, and data tables in V1. Charting library (ApexCharts, Chart.js, or similar) can be added later without structural changes — the data queries are already in place.

### 11.4 Search Implementation

**SearchServiceInterface contract:**

```php
interface SearchServiceInterface
{
    public function search(string $query, array $filters = [], string $sort = 'created_at', string $direction = 'desc'): LengthAwarePaginator;
}
```

**V1 driver:** `MySqlSearchDriver` using MySQL FULLTEXT on `tickets.subject`, `tickets.description`, and `comments.body`.

**Future:** `MeilisearchDriver` via Laravel Scout — implements the same interface.

### 11.5 Tech Dashboard Filters

| Filter | Type | Notes |
|--------|------|-------|
| Status | Multi-select | Filter by one or more statuses |
| Priority | Multi-select | Filter by priority level |
| Category | Dropdown | Single select |
| Subcategory | Dropdown | Dependent on selected category |
| Group | Multi-select | Saved per user, server-side |
| Assigned To | Dropdown | Specific tech or "Unassigned" |
| Date range | Date picker | Filter by creation date |
| Search | Free text | Fulltext across subject, description, comments |

**Sort options:** Date created (newest/oldest), priority (highest/lowest), last updated.

**Employee dashboard filters:** Status, date range. Search: subject text only. Employee only sees own tickets.

### 11.6 Phase 6 Acceptance Criteria

- [ ] All three dashboards render with correct data and access controls
- [ ] Employee sees only own tickets
- [ ] Tech sees tickets from their groups and assigned tickets
- [ ] IT Manager sees system-wide summary stats and data tables
- [ ] Search returns relevant results from subject, description, and comments
- [ ] All filters work individually and in combination
- [ ] Group filter selections persist per user across sessions (server-side)
- [ ] Pagination works on all list views (default 25 per page)
- [ ] All dashboard views fully localized (AR/EN) and RTL-correct

---

## 12. Phase 7 — Reporting, Export & CSAT

**Deliverable:** 12 pre-defined report types with filters, CSV/XLSX export with dynamic columns, and CSAT feedback system.

### 12.1 CSAT Implementation

**CSAT requires login. No anonymous access.**

**Database: `csat_ratings`**

| Column | Type | Constraints | Notes |
|--------|------|-------------|-------|
| id | ULID | PK | |
| ticket_id | ULID FK | NOT NULL, UNIQUE, ON DELETE CASCADE | One per resolved ticket |
| requester_id | ULID FK | NOT NULL, ON DELETE RESTRICT | |
| tech_id | ULID FK | NOT NULL, ON DELETE RESTRICT | Assigned tech at time of resolution |
| rating | tinyint unsigned | NULLABLE | 1-5, NULL until submitted |
| comment | text | NULLABLE | |
| status | enum | NOT NULL, DEFAULT 'pending' | pending, submitted, expired |
| expires_at | timestamp | NOT NULL | 7 days from resolution |
| submitted_at | timestamp | NULLABLE | |
| dismissed_count | int unsigned | NOT NULL, DEFAULT 0 | |
| created_at | timestamp | | |
| updated_at | timestamp | | |

**Flow:**

1. Ticket resolved → `csat_ratings` record created with `status = 'pending'`, `expires_at = now + 7 days`
2. Email notification sent to requester informing them ticket is resolved (NOT a survey link — just a notification with ticket details and tech name)
3. Next requester login → modal/banner prompt: "Rate your experience with [Tech Name] for ticket [TKT-XXXXX]"
   - Shows: ticket display number, subject, assigned tech name, star rating (1-5), optional comment field
   - Requester can **dismiss** → `dismissed_count` incremented, modal returns next login
   - Requester can **submit** → `status = 'submitted'`, `submitted_at` set, rating + comment saved
4. Requester can also navigate to ticket detail view and submit rating there anytime
5. After submission: read-only, no re-rating
6. After 7 days without submission: scheduled command sets `status = 'expired'`, no more prompts

**Scheduled command:** `csat:expire` runs daily, sets expired ratings.

**Visibility:**

| Who | What They See |
|-----|---------------|
| Requester | Own submitted rating on ticket detail (read-only) |
| Assigned tech | Rating + comment on tickets assigned to them (read-only) |
| IT Manager | All ratings. Aggregated metrics on dashboard and reports. |
| Other techs | Cannot see ratings on tickets not assigned to them |

### 12.2 Reporting

12 pre-defined report types. All support common filters: date range (required), category, priority, group, tech, status. **V1 renders all reports as data tables — no charts.** Chart visualizations can be layered on top later.

| Report | Display | Data Source |
|--------|---------|-------------|
| Ticket Volume | Data table | Tickets created per day/week/month |
| Tickets by Status | Summary table | Current status distribution with counts |
| Tickets by Category | Summary table | Volume per category |
| Tickets by Priority | Summary table | Volume per priority |
| Avg Resolution Time | Data table | Mean time to resolution, trended by period |
| Tech Performance | Data table | Resolved count + avg CSAT + SLA compliance per tech |
| Team Workload | Summary table | Current open tickets per tech |
| Escalation Summary | Data table | Triggered/approved/rejected per period |
| SLA Compliance | Data table | % within SLA, breakdown by priority |
| SLA Breaches | Data table | Breached tickets with tech, priority, target vs actual |
| CSAT Overview | Data table | Avg rating by period, response rate, rating distribution |
| CSAT by Tech | Data table | Avg rating per tech, number of ratings, lowest-rated tickets |

### 12.3 Export

- **Formats:** CSV and XLSX
- Respects current report filters. No filters = all tickets.
- One row per ticket with all standard fields.
- **Dynamic columns for custom fields:** One column per active + soft-deleted field that has values.
- **SLA columns:** Response target, response actual, response status, resolution target, resolution actual, resolution status, total paused time.
- **CSAT columns:** Rating (1-5 or blank), comment (or blank), submission date, status.
- **Large exports queued** via Horizon — user notified when download ready.

### 12.4 Phase 7 Acceptance Criteria

- [ ] CSAT modal appears on login for pending ratings, shows correct tech name and ticket info
- [ ] CSAT can be dismissed and reappears next login
- [ ] CSAT stops prompting after expiry (7 days)
- [ ] CSAT can be submitted from modal or ticket detail view
- [ ] All 12 report types render with correct data tables
- [ ] Report filters work in combination
- [ ] CSV and XLSX exports include all standard + dynamic columns
- [ ] Large exports queued and download link provided
- [ ] CSAT visibility rules enforced per role

---

## 13. Phase 8 — Admin Configuration Panel

**Deliverable:** Central admin area for managing all configurable entities.

### 13.1 Admin Sections

| Section | Capabilities | Permission |
|---------|-------------|------------|
| Categories & Subcategories | CRUD, subcategory required/optional, group mapping, versioned | `category.manage` |
| Groups | CRUD, member management, group manager assignment | `group.manage`, `group.manage-members`, `group.manage-manager` |
| Custom Fields | CRUD, type/scope/required/order, dropdown options, deactivate/soft-delete | `system.manage-custom-fields` |
| SLA Targets | Response + resolution per priority, business hours, warning threshold | `system.manage-sla` |
| Tags | CRUD ticket tags | `system.manage-tags` |
| Response Templates | CRUD comment templates with default public/internal type | `system.manage-response-templates` |
| Departments | CRUD (name AR/EN, sort order, active/inactive) | `system.manage-departments` |
| Locations/Sites | CRUD (name AR/EN, sort order, active/inactive) | `system.manage-locations` |
| User Management | View users, promote to tech, grant/revoke permissions | `user.promote`, `user.manage-permissions` |
| Notification Settings | Configure notification templates | `system.manage-notifications` |
| Tenant Branding | Company name, logo, primary/secondary colors | IT Manager only |

### 13.2 Custom Fields

**Table: `custom_fields`**

| Column | Type | Constraints | Notes |
|--------|------|-------------|-------|
| id | ULID | PK | |
| name_ar | varchar(255) | NOT NULL | |
| name_en | varchar(255) | NOT NULL | |
| field_type | enum | NOT NULL | text, number, dropdown, multi_select, date, checkbox |
| is_required | boolean | NOT NULL, DEFAULT false | |
| scope_type | enum | NOT NULL, DEFAULT 'global' | global, category |
| scope_category_id | ULID FK | NULLABLE, ON DELETE CASCADE | Only when scope_type = 'category' |
| display_order | int | NOT NULL, DEFAULT 0 | |
| is_active | boolean | NOT NULL, DEFAULT true | |
| version | int | NOT NULL, DEFAULT 1 | |
| deleted_at | timestamp | NULLABLE | SoftDeletes |
| created_at | timestamp | | |
| updated_at | timestamp | | |

**Table: `custom_field_options`**

| Column | Type | Constraints | Notes |
|--------|------|-------------|-------|
| id | ULID | PK | |
| custom_field_id | ULID FK | NOT NULL, ON DELETE CASCADE | |
| value_ar | varchar(255) | NOT NULL | |
| value_en | varchar(255) | NOT NULL | |
| sort_order | int | NOT NULL, DEFAULT 0 | |
| is_active | boolean | NOT NULL, DEFAULT true | |
| deleted_at | timestamp | NULLABLE | SoftDeletes |
| created_at | timestamp | | |
| updated_at | timestamp | | |

**Table: `custom_field_values`**

| Column | Type | Constraints | Notes |
|--------|------|-------------|-------|
| id | ULID | PK | |
| ticket_id | ULID FK | NOT NULL, ON DELETE CASCADE | |
| custom_field_id | ULID FK | NOT NULL, ON DELETE RESTRICT | |
| value | text | NULLABLE | Text representation regardless of field type |
| created_at | timestamp | | |
| updated_at | timestamp | | |

**Index:** Composite index on `(ticket_id, custom_field_id)`.

**Table: `tags`**

| Column | Type | Constraints | Notes |
|--------|------|-------------|-------|
| id | ULID | PK | |
| name_ar | varchar(100) | NOT NULL | |
| name_en | varchar(100) | NOT NULL | |
| color | varchar(7) | NULLABLE | Hex color for UI display |
| is_active | boolean | NOT NULL, DEFAULT true | |
| deleted_at | timestamp | NULLABLE | SoftDeletes |
| created_at | timestamp | | |
| updated_at | timestamp | | |

**Table: `ticket_tag` (pivot)**

| Column | Type | Constraints | Notes |
|--------|------|-------------|-------|
| ticket_id | ULID FK | ON DELETE CASCADE | |
| tag_id | ULID FK | ON DELETE CASCADE | |
| | | UNIQUE (ticket_id, tag_id) | |

**Table: `response_templates`**

| Column | Type | Constraints | Notes |
|--------|------|-------------|-------|
| id | ULID | PK | |
| title_ar | varchar(255) | NOT NULL | |
| title_en | varchar(255) | NOT NULL | |
| body_ar | text | NOT NULL | HTML content |
| body_en | text | NOT NULL | HTML content |
| is_internal | boolean | NOT NULL, DEFAULT true | Default comment type when template is used |
| is_active | boolean | NOT NULL, DEFAULT true | |
| deleted_at | timestamp | NULLABLE | SoftDeletes |
| created_at | timestamp | | |
| updated_at | timestamp | | |

### 13.3 Versioning Rules

- Changes to categories, subcategories, and custom fields affect **NEW tickets only**.
- Existing tickets retain the values they were created with.
- **Deactivated** (`is_active = false`): Hidden from new forms, existing tickets display values read-only.
- **Deleted** (`deleted_at` set): Soft-delete, same behavior as deactivated. Cannot be undone from UI.
- **Renamed:** Existing tickets show the new name (label change is non-destructive).
- **Changed dropdown options:** New options on new tickets, existing tickets keep original values.

### 13.4 Tenant Configuration

**Table: `app_settings`**

| Column | Type | Constraints | Notes |
|--------|------|-------------|-------|
| id | ULID | PK | |
| key | varchar(100) | NOT NULL, UNIQUE | |
| value | text | NULLABLE | JSON-encoded for complex values |
| created_at | timestamp | | |
| updated_at | timestamp | | |

**Settings keys:**
- `company_name` — Displayed in header, emails
- `logo_path` — Company logo file path
- `primary_color` — Hex color
- `secondary_color` — Hex color
- `business_hours_start` — e.g., "08:00"
- `business_hours_end` — e.g., "16:00"
- `working_days` — JSON array, e.g., `["sun","mon","tue","wed","thu"]`
- `sla_warning_threshold` — Integer percentage, default 75
- `session_timeout_hours` — Default 8

### 13.5 Phase 8 Acceptance Criteria

- [ ] All admin sections accessible with correct permission checks
- [ ] Categories: CRUD with group mapping, subcategory required/optional, versioning
- [ ] Groups: CRUD with member management, group manager assignment
- [ ] Custom fields: all 6 types work, scoping, versioning, soft-delete
- [ ] Tags: CRUD with color, active/inactive
- [ ] Response templates: CRUD with bilingual content and default type
- [ ] SLA targets configurable per priority, business hours config, warning threshold
- [ ] Departments and locations: CRUD with bilingual names and sort order
- [ ] User management: promote employee, create tech profile, grant/revoke permissions
- [ ] Tenant branding: company name, logo, colors applied to layout
- [ ] All admin views fully localized (AR/EN) and RTL-correct

---

## 14. Phase 9 — Precedent System

**Deliverable:** Structured resolution capture on resolve, auto-suggest past resolutions, and resolution linking with usage counting.

### 14.1 Database: Precedent

**Table: `resolutions`**

| Column | Type | Constraints | Notes |
|--------|------|-------------|-------|
| id | ULID | PK | |
| ticket_id | ULID FK | NOT NULL, UNIQUE, ON DELETE CASCADE | One per resolved ticket |
| summary | varchar(500) | NOT NULL | |
| root_cause | varchar(500) | NULLABLE | |
| steps_taken | text | NOT NULL | HTML, sanitized |
| parts_resources | text | NULLABLE | |
| time_spent_minutes | int unsigned | NULLABLE | |
| resolution_type | enum | NOT NULL | known_fix, workaround, escalated_externally, other |
| linked_resolution_id | ULID FK | NULLABLE, ON DELETE SET NULL | Self-referencing: "I used this same fix" |
| link_notes | text | NULLABLE | Additional notes when linking |
| usage_count | int unsigned | NOT NULL, DEFAULT 0 | Incremented when others link to this |
| created_by | ULID FK | NOT NULL, ON DELETE RESTRICT | |
| created_at | timestamp | | |
| updated_at | timestamp | | |

**Index:** `(linked_resolution_id)` for usage count tracking.

### 14.2 Resolution Capture

When a tech resolves a ticket (status → `resolved`), a resolution form modal appears. **Resolution is required to complete the resolve action.**

**Fields:**
- Summary (short text, required)
- Root cause (short text, optional)
- Steps taken (rich text, required)
- Parts/resources used (text, optional)
- Time spent (duration, optional)
- Resolution type (required dropdown: Known Fix, Workaround, Escalated Externally, Other)

**Alternatively:** Tech can **link** to an existing resolution instead of writing new steps.
- Select from auto-suggest list
- Add optional link notes (e.g., "same fix but also restarted the service")
- Cannot write new steps if linking — it's one or the other
- Linking increments `usage_count` on the linked resolution

### 14.3 Auto-Suggest

When tech opens a ticket, the system queries resolutions from tickets with the **same category + subcategory** (exact match).

**Rules:**
- Only from `resolved` tickets (not closed/cancelled)
- Sorted by: `usage_count` DESC, then `created_at` DESC
- Displayed in a collapsible panel on ticket detail view
- Shows: summary, resolution type badge, steps taken (truncated), date resolved, usage count badge
- Custom field values from the original ticket shown for context

### 14.4 Phase 9 Acceptance Criteria

- [ ] Resolution form appears on resolve and blocks completion until filled
- [ ] All resolution fields work, including rich text steps (sanitized)
- [ ] Resolution linking works: suggestions appear, linking increments usage count
- [ ] Auto-suggest panel shows relevant resolutions on ticket detail
- [ ] Suggestions sorted correctly (usage count DESC, then date DESC)
- [ ] Linked resolutions show relationship in ticket detail view
- [ ] Resolution type dropdown works with all 4 options
- [ ] Custom field values from original ticket visible in resolution context

---

## 15. Phase 10 — Hardening, Audit & Production Readiness

**Deliverable:** Comprehensive audit logging, security hardening, performance optimization, and production deployment configuration.

### 15.1 Audit Logging

**Table: `audit_logs`**

| Column | Type | Constraints | Notes |
|--------|------|-------------|-------|
| id | ULID | PK | |
| actor_id | ULID FK | NOT NULL, ON DELETE RESTRICT | |
| action | varchar(100) | NOT NULL | e.g., 'ticket.created', 'permission.granted' |
| target_type | varchar(100) | NOT NULL | e.g., 'ticket', 'user', 'category' |
| target_id | char(26) | NOT NULL | ULID of target entity |
| before_value | JSON | NULLABLE | State before change |
| after_value | JSON | NULLABLE | State after change |
| metadata | JSON | NULLABLE | Additional context per action type |
| ip_address | varchar(45) | NOT NULL | IPv4 or IPv6 |
| user_agent | varchar(500) | NULLABLE | |
| created_at | timestamp | NOT NULL | |

**No `updated_at` column. No `deleted_at` column. Audit logs are IMMUTABLE — append-only, no updates, no deletes.**

**Audited Actions:**

| Domain | Actions |
|--------|---------|
| Ticket | created, status_changed, priority_changed, assigned, reassigned, closed, cancelled, deleted |
| Transfer | request_created, request_revoked, request_accepted, request_rejected |
| Escalation | condition_report_submitted, condition_report_approved, condition_report_rejected, maintenance_request_generated, maintenance_request_submitted, maintenance_request_approved, maintenance_request_rejected |
| User | created, promoted, demoted, permissions_granted, permissions_revoked |
| Admin | category_created, category_updated, category_deleted, group_created, group_updated, custom_field_created, custom_field_updated, sla_policy_updated, tag_created, tag_updated, response_template_created, response_template_updated, department_updated, location_updated, app_setting_updated |
| Comment | created (metadata only — comment body NOT stored in audit) |
| CSAT | rating_submitted |
| SLA | sla_breached (automated) |

> ⚠️ **CRITICAL:** Audit entries NEVER contain password values, even in before/after JSON. Comment body is NOT stored in audit log — only metadata (ticket_id, is_internal, author). CSAT comment text is NOT stored in audit — only rating value.

### 15.2 Audit Log Viewer

- Accessible with `system.view-audit-log` permission
- Filters: action type, date range, actor, target type
- Paginated (default 50 per page)
- Read-only — no edit, no delete actions in the UI

### 15.3 Security Hardening Checklist

- [ ] All HTTP security headers configured and verified (Section 3.7)
- [ ] Rate limiting active and tested on all endpoints (Section 3.5)
- [ ] CSRF protection verified on all state-changing requests
- [ ] Session security verified: HttpOnly, Secure, SameSite, Redis-backed, regenerated on login
- [ ] HTML sanitization verified on all rich text inputs (Section 3.2)
- [ ] File upload security verified: MIME validation, size limits, path randomization (Section 3.4)
- [ ] SQL injection: all queries use parameterized statements
- [ ] Authorization: every route has appropriate middleware/policy
- [ ] Error handling: production shows generic errors, no stack traces, no SQL in responses
- [ ] Logging: sensitive data excluded from all log channels
- [ ] Dependencies: `composer audit` and `npm audit` clean
- [ ] No debug routes or dev tools exposed in production
- [ ] `.env` file not accessible via web

### 15.4 Performance Optimization

- **Indexes:** All indexes from Section 5.3 verified in production schema
- **Eager loading:** All Livewire components and controllers verified — no N+1 queries (use Laravel Debugbar in dev)
- **Redis caching:**
  - Permission lookups cached per user (invalidated on grant/revoke)
  - Category/subcategory lists cached (invalidated on admin changes)
  - App settings cached (invalidated on update)
- **Queue optimization:** Heavy operations via Horizon: exports, SLA checks, notification dispatch, image processing
- **Pagination:** All list views paginated (default 25, configurable)
- **Database:** Verify composite indexes for common query patterns

### 15.5 Production Deployment

**Docker Compose production profile:**
- No phpMyAdmin, no Mailpit, no debug tools
- PHP 8.4-fpm with production php.ini (opcache enabled, display_errors off)
- Nginx with TLS/SSL, GZIP compression, static asset cache headers
- MySQL 8.0 with tuned `innodb_buffer_pool_size`, slow query log enabled (upgrade to 8.4 LTS when shared infra allows)
- Redis with `maxmemory` policy and persistence
- Horizon with supervisor for queue workers
- Laravel: `config:cache`, `route:cache`, `view:cache`, `event:cache`
- Logging: production log level (error), daily rotation
- Backup: database backup cron (daily), file storage backup

**Environment variables for all secrets:**
- DB credentials, Redis connection, email provider API key, app key

### 15.6 Phase 10 Acceptance Criteria

- [ ] Audit log captures all defined actions with correct before/after values
- [ ] Audit log viewable with filtering by action type, date range, actor
- [ ] Audit log is truly immutable — no update/delete possible
- [ ] All security hardening checklist items verified
- [ ] No N+1 queries in any component (verified via Debugbar)
- [ ] All heavy operations run async via queue
- [ ] Production Docker Compose starts cleanly with all services
- [ ] Database backup and restore tested
- [ ] Application runs with caches enabled (config, route, view, event)
- [ ] Error pages show generic messages in production (no stack traces)

---

## 16. Appendix A — Complete Table List

| Phase | Tables | Count |
|-------|--------|-------|
| Phase 1 | users, tech_profiles, departments, locations, permissions, permission_user | 6 |
| Phase 2 | tickets, ticket_counters, categories, subcategories, groups, group_user, transfer_requests, ticket_attachments | 8 |
| Phase 3 | condition_reports, condition_report_attachments, maintenance_requests | 3 |
| Phase 4 | comments, notification_logs | 2 |
| Phase 5 | sla_policies, ticket_sla, sla_pause_logs | 3 |
| Phase 7 | csat_ratings | 1 |
| Phase 8 | custom_fields, custom_field_options, custom_field_values, tags, ticket_tag, response_templates, app_settings | 7 |
| Phase 9 | resolutions | 1 |
| Phase 10 | audit_logs | 1 |
| **Total** | | **32** |

---

## 17. Appendix B — Route Map

All routes use ULID parameters. All state-changing routes require CSRF. All routes have appropriate permission middleware.

| Group | Key Routes | Auth/Permission |
|-------|-----------|----------------|
| Auth | POST /register, POST /login, POST /logout, POST /forgot-password, POST /reset-password | Guest / Auth |
| Profile | GET /profile, PUT /profile, PUT /profile/locale | Auth |
| Tickets | GET /tickets, POST /tickets, GET /tickets/{ulid} | Auth + scoped visibility |
| Assignment | POST /tickets/{ulid}/assign, POST /tickets/{ulid}/self-assign | permission:ticket.assign or tech |
| Transfers | POST /transfers, PUT /transfers/{ulid}/accept, PUT /transfers/{ulid}/reject, DELETE /transfers/{ulid} | Auth (tech) |
| Escalation | POST /tickets/{ulid}/condition-report, PUT /condition-reports/{ulid}/approve, PUT /condition-reports/{ulid}/reject | Auth (tech) / permission:escalation.approve |
| Maintenance Request | GET /tickets/{ulid}/maintenance-request/{locale}, POST /tickets/{ulid}/maintenance-request-upload | Auth (requester) |
| Comments | POST /tickets/{ulid}/comments | Auth (involved parties) |
| Files | GET /files/{ulid} | Auth (involved parties or ticket.view-all) |
| Admin | CRUD for categories, groups, custom fields, SLA, templates, tags, users, departments, locations, branding | Respective admin permissions |
| Reports | GET /reports/{type}, GET /exports/{type} | permission:system.view-reports |
| CSAT | GET /csat/pending, POST /csat/{ulid}, PUT /csat/{ulid}/dismiss | Auth (requester, own only) |
| Audit | GET /audit-logs | permission:system.view-audit-log |

> ⚠️ **SECURITY:** Every route accepting an identifier MUST validate it as a valid ULID format before database lookup. Display numbers (TKT-0000001) are NEVER accepted as route parameters.

---

## 18. Appendix C — Deployment Checklist

### 18.1 Pre-Deployment

- [ ] All environment variables configured and validated
- [ ] `APP_DEBUG=false`, `APP_ENV=production`
- [ ] `APP_KEY` generated and securely stored
- [ ] Database migrations run successfully
- [ ] Permission seeder executed
- [ ] SuperUser account created via `php artisan app:create-superuser`
- [ ] Initial IT Manager account created and configured
- [ ] Email provider configured and tested
- [ ] SSL/TLS certificate installed
- [ ] DNS configured

### 18.2 Post-Deployment Verification

- [ ] Registration and login flow works
- [ ] Locale switching works (AR ↔ EN)
- [ ] RTL layout renders correctly in Arabic
- [ ] Ticket creation → resolution flow works end-to-end
- [ ] Escalation flow works end-to-end
- [ ] Email notifications received
- [ ] SLA timers running correctly
- [ ] CSAT prompt appears on login after ticket resolution
- [ ] Reports render with data
- [ ] Export generates downloadable CSV/XLSX
- [ ] Audit logs capturing actions
- [ ] File uploads and downloads working
- [ ] Security headers present in responses
- [ ] No debug information exposed

### 18.3 Buyer Onboarding

1. Deploy new instance with buyer's domain/subdomain
2. Configure tenant settings: company name, logo, brand colors
3. Configure auth provider (email+password for V1)
4. Configure email provider (Resend API key or SMTP)
5. Configure business hours (working days, start/end times)
6. Set SLA targets per priority level
7. Create IT Manager account
8. IT Manager creates categories, groups, and initial team structure
9. IT Manager creates custom fields if needed
10. Test end-to-end flow before going live

---

*This document is the single source of truth for V1 implementation. Each phase is self-contained and designed to be executed sequentially via Claude Code CLI.*
