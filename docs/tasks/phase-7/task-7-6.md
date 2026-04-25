# Task 7.6 — Reporting: 6 Advanced Reports (SLA, Escalation, CSAT)

## Context
Builds on the reporting infrastructure from task 7.5. Implements the remaining 6 reports that require cross-module data: tech performance, escalation, SLA compliance/breach, and CSAT aggregates.

## Task
Implement the 6 remaining report types, wiring cross-module data through service interfaces (not direct model imports).

## Requirements
- Implement 6 report classes under `app/Modules/Reporting/Reports/`:
  1. `TechPerformanceReport` — resolved count + avg CSAT + SLA compliance % per tech
  2. `EscalationSummaryReport` — triggered/approved/rejected counts per period
  3. `SlaComplianceReport` — % within SLA, breakdown by priority
  4. `SlaBreachReport` — breached tickets with tech, priority, target vs actual resolve time
  5. `CsatOverviewReport` — avg rating by period, response rate, rating distribution (1–5 counts)
  6. `CsatByTechReport` — avg rating per tech, number of ratings, 3 lowest-rated tickets per tech
- Cross-module data (SLA, escalation) accessed via their existing service interfaces — no direct model imports from SLA or Escalation modules.
- All 6 registered in `ReportServiceProvider` key map.
- All column headers via `__()` with keys in `resources/lang/{ar,en}/reports.php`.
- All reports respect the shared filter set from task 7.5 (date range required).

## Do NOT
- Do not import `App\Modules\SLA\Models\*` or `App\Modules\Escalation\Models\*` directly — use service interfaces.
- Do not render charts.
- Do not make CSAT data visible in reports to non-managers.
- Do not skip localization.

## Acceptance
- Pest `tests/Unit/Reporting/SlaBreachReportTest.php`: returns only breached tickets within date range; paused time subtracted from resolution duration.
- Pest `tests/Unit/Reporting/CsatByTechReportTest.php`: avg rating computed correctly; only submitted ratings counted.
- Pest `tests/Feature/Reporting/AdvancedReportPageTest.php`: all 6 report types render without error for an IT Manager with seeded data; non-manager cannot access.

## References
- `SPEC.md §12.2` — report type definitions
- `SPEC.md §11.3` — SLA service interface (cross-module access pattern)
- `SPEC.md §12.1` — CSAT visibility rules
- `SPEC.md §2.3` — module communication rules (no cross-module model imports)
