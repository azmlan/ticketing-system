# Phase 10 — Hardening, Audit & Production Readiness

**Spec reference:** [SPEC.md §15](../../SPEC.md#15-phase-10--hardening-audit--production-readiness)
**Deliverable:** Comprehensive audit logging, security hardening, performance optimization, and production deployment configuration.
**Exit condition:** All items in [§15.6 Phase 10 Acceptance Criteria](../../SPEC.md#156-phase-10-acceptance-criteria) pass.

## Tasks

- [ ] **Task 10.1** — `audit_logs` migration (ULID PK, `actor_id`, `action`, `target_type`, `target_id` char(26), `before_value` JSON, `after_value` JSON, `metadata` JSON, `ip_address`, `user_agent`, `created_at` only — no `updated_at`/`deleted_at`); `AuditLogger` service; event listeners covering every action in §15.1 matrix (ticket, transfer, escalation, user, admin, comment, CSAT, SLA); append-only enforcement (assertion tests + no update/delete code paths); redaction rules (no passwords, no comment body, no CSAT comment text); tests.
- [ ] **Task 10.2** — Audit Log Viewer Livewire gated by `system.view-audit-log`: filters (action type multi-select, date range, actor, target type), pagination (50/page), detail modal showing before/after JSON, read-only (no edit/delete UI actions); feature tests including filter combinations + permission denial.
- [ ] **Task 10.3** — Security hardening sweep per §15.3 checklist: verify all HTTP security headers, rate limits (login/register/reset/ticket-create/comment/upload/API), CSRF on state changes, session cookie flags + Redis backing + regen on login, sanitization on all rich text inputs, file upload pipeline invariants, authorization middleware on every route, production error pages (no stack traces), no debug routes, `composer audit` + `npm audit` clean; automated tests where possible, checklist document where manual.
- [ ] **Task 10.4** — Performance pass: Redis cache for permission lookups (per-user key, invalidated on grant/revoke from Phase 8.8), category/subcategory lists (invalidated on admin change), `app_settings` (invalidated on update); N+1 query elimination via eager loading across all Livewire + controllers (verified in dev with Debugbar); verify composite indexes from §5.3 present in production schema; tests asserting cache hit/miss behavior.
- [ ] **Task 10.5** — Production Docker Compose profile: no phpMyAdmin, no Mailpit, no debug tools; PHP 8.4-fpm with opcache + `display_errors=Off`; Nginx with TLS/SSL + GZIP + static-asset cache headers; MySQL 8.0 tuned `innodb_buffer_pool_size` + slow query log; Redis `maxmemory` + persistence; Horizon under supervisor; `config:cache` + `route:cache` + `view:cache` + `event:cache` in build; daily backup cron; env-var driven secrets.
- [ ] **Task 10.6** — Deployment verification: backup + restore tested against production profile; post-deployment smoke per §18.2 automated as Pest browser-level or feature suite covering registration → login → locale switch → RTL render → ticket create → escalation → email receipt → SLA tick → CSAT prompt → report render → export → audit entry; run book updated.

## Session Groupings

| Session | Tasks | Rationale |
|---------|-------|-----------|
| S1 | 10.1 | Audit subsystem is central (matrix of ~30 actions + redaction invariants); alone. |
| S2 | 10.2, 10.3 | Audit viewer + security hardening checklist are complementary reviews; fit one session. |
| S3 | 10.4 | Performance pass (caching + N+1 + index verification) is focused enough for one session. |
| S4 | 10.5, 10.6 | Production profile + end-to-end verification sweep closes the phase; share deployment context. |

## Acceptance Gate (from SPEC §15.6)

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
