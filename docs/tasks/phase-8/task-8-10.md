# Task 8.10 — Notification Settings

## Context
The notification engine (Phase 4) fires events from the matrix in §9.3. Currently all notification types are always active. Phase 8 adds an admin UI to enable/disable individual notification types and review recent notification log entries.

## Task
Build the notification settings admin page: per-type enable/disable toggles and a read-only notification log viewer.

## Requirements
- Add `notification_enabled_{type}` keys to `app_settings` for each of the 11 notification types in the matrix (§9.3); default `true`; seeded alongside other app_settings defaults from task 8.1 migration. (§9.3, §13.1, §13.4)
- `app/Modules/Admin/Livewire/Settings/NotificationSettings.php`: lists each notification type (localized label), trigger, recipient description, enabled/disabled toggle; saving calls `AppSetting::set()` per changed key. Permission: `system.manage-notifications`. (§13.1)
- Communication module notification dispatcher checks `AppSetting::get("notification_enabled_{$type}", true)` before queuing; skips quietly if disabled. (§9.3)
- `NotificationLog.php` sub-component on the same page: paginated table of `notification_logs` rows; filter by `status` (queued/sent/failed), `type`, date range; read-only, no delete action. (§9.3)
- All strings through `__()`. (§4.2)

## Do NOT
- Do not add new columns to `notification_logs` — use `app_settings` for enable/disable state.
- Do not allow editing or re-sending notification log entries from this UI.
- Do not import `notification_logs` model into Admin module — expose it via the Communication module's service or a shared read-only query interface.

## Acceptance
- Pest `tests/Feature/Admin/NotificationSettingsTest.php`: toggling a type off sets the `app_settings` key to `false`; dispatcher skips queuing when that key is false; `system.manage-notifications` enforced; unauthorized user gets 403.
- Pest `tests/Feature/Admin/NotificationLogViewerTest.php`: log entries appear paginated; status filter returns correct subset; date range filter works.
- Disabling `ticket_created` notification causes no queued job after a ticket is created in test.

## References
- `SPEC.md §9.3` — notification matrix (11 trigger types) and notification engine
- `SPEC.md §13.1` — notification settings section and permission
- `SPEC.md §13.4` — `app_settings` key convention
- `SPEC.md §4.2` — all strings localized
