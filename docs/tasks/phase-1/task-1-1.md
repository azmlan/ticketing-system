# Task 1.1 — Docker + Laravel 12 Bootstrap

## Context
First task of Phase 1. No app code exists yet; only `SPEC.md`, `CLAUDE.md`, and `_infra/` (shared, do not touch). This task stands up the runnable baseline everything else builds on.

## Task
Bootstrap a Laravel 12 + Livewire 3 app running in Docker, connected to the shared `_infra/` services, with the modular monolith directory scaffold and a passing Pest smoke test.

## Requirements
- `docker-compose.yml` at repo root: app (PHP 8.4-fpm), Nginx alpine — no MySQL/Redis/Mailpit/phpMyAdmin services (those live in `_infra/`, joined via external network). Stack per CLAUDE.md.
- Laravel 12 + Livewire 3 starter kit, Horizon 5, Sanctum 4, Tailwind 4. PHP 8.4. Versions pinned in `composer.json` / `package.json`.
- Pest installed; `tests/Feature/` and `tests/Unit/` directories exist.
- `app/Modules/` scaffold with empty dirs for: `Shared`, `Auth`, `Tickets`, `Assignment`, `Escalation`, `Communication`, `SLA`, `CSAT`, `Precedent`, `Reporting`, `Admin`, `Audit` (each with `.gitkeep`). Module boundaries per CLAUDE.md.
- `.env.example` committed: DB host/port/credentials pointing at `_infra/` MySQL, Redis, Mailpit. Real `.env` gitignored.
- `config/database.php`, `config/queue.php`, `config/cache.php`, `config/session.php`, `config/mail.php` wired to env vars — no hardcoded hosts.
- `resources/lang/{ar,en}/` directories created (empty placeholders fine; Task 1.5 fills them).
- `README.md` section on starting the stack (reuse CLAUDE.md commands).

## Do NOT
- Do not modify `_infra/` or add MySQL/Redis/Mailpit/phpMyAdmin to this compose file.
- Do not seed data, add migrations, or scaffold auth beyond the starter kit defaults.
- Do not install chart, WebSocket, or real-time packages (deferred to V2 per CLAUDE.md).
- Do not commit `.env`, `vendor/`, `node_modules/`, build artifacts.
- Do not run `migrate:fresh` — no migrations exist yet.

## Acceptance
- `docker compose up -d --build` brings up app + nginx and joins `_infra/` network; `docker compose exec app php -v` shows 8.4.
- `docker compose exec app php artisan test` runs green with one Pest smoke test in `tests/Feature/SmokeTest.php` asserting `GET /` returns 200.
- `docker compose exec app php artisan horizon:status` works (queue connection resolves against shared Redis).
- All 12 module directories exist under `app/Modules/`.
- `composer audit` and `npm audit` run without high-severity findings (document exceptions if any).

## References
- `SPEC.md §2.1` — module layout
- `SPEC.md §6.1` — project setup
- `CLAUDE.md` — Stack, Commands, Architecture sections
