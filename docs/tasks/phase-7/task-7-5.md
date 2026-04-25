# Task 7.5 — Reporting: Infrastructure + 6 Core Reports

## Context
Phase 7 requires 12 report types. All render as data tables with shared filters. This task builds the report infrastructure and the first 6 core reports.

## Task
Build the reporting infrastructure (service interface, shared filter state, Livewire report page) and implement 6 core report types.

## Requirements
- `ReportServiceInterface` at `app/Modules/Reporting/Contracts/` with `run(string $type, array $filters): Collection|LengthAwarePaginator`.
- Shared filter set for all reports: date range (required), category, priority, group, tech, status — per §12.2.
- `ReportPage` Livewire component with filter bar (reuses existing filter components where possible) and a pluggable table section.
- Implement 6 report types as separate classes under `app/Modules/Reporting/Reports/`:
  1. `TicketVolumeReport` — tickets created per day/week/month
  2. `TicketsByStatusReport` — current status distribution with counts
  3. `TicketsByCategoryReport` — volume per category
  4. `TicketsByPriorityReport` — volume per priority
  5. `AvgResolutionTimeReport` — mean time to resolution, trended by period
  6. `TeamWorkloadReport` — current open tickets per tech
- Each report implements a shared `ReportInterface` with `headers(): array` and `run(array $filters): Collection`.
- `ReportServiceProvider` binds `ReportServiceInterface`; report type resolved by string key.
- All column headers via `__()`.

## Do NOT
- Do not render charts — V1 is data tables only per §12.2.
- Do not implement CSAT or SLA reports here — those are in task 7.6.
- Do not skip the date range filter — it is required for all reports.
- Do not hardcode strings.

## Acceptance
- Pest `tests/Unit/Reporting/TicketVolumeReportTest.php`: returns correct grouped counts for a given date range.
- Pest `tests/Feature/Reporting/ReportPageTest.php`: IT Manager can load the report page; required date range validation fires; filter combination returns narrowed results.
- Each of the 6 report types returns a non-empty collection with expected column keys when seeded data is present.

## References
- `SPEC.md §12.2` — all 12 report types and filter spec
- `SPEC.md §6.6` — IT Manager role (reporting access)
- `SPEC.md §4.2` — localization
