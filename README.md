# Ticketing System

Internal IT support ticketing system — Laravel 12 + Livewire 3 modular monolith.  
Single-tenant deployments. AR (RTL) + EN localization.

## Prerequisites

- Docker & Docker Compose
- Shared `_infra/` services running (MySQL 8.0, Redis 7, Mailpit, phpMyAdmin)

```bash
cd /path/to/_infra && docker compose up -d
```

## Getting Started

```bash
# 1. Copy environment config
cp .env.example .env

# 2. Build and start the stack (app + Nginx; connects to shared _infra)
docker compose up -d --build

# 3. Generate application key
docker compose exec app php artisan key:generate

# 4. Run migrations
docker compose exec app php artisan migrate

# 5. Seed the database
docker compose exec app php artisan db:seed
```

App is available at **http://localhost:8001**

## Daily Commands

```bash
# Start stack
docker compose up -d --build

# Reset database
docker compose exec app php artisan migrate:fresh --seed

# Run test suite (Pest)
docker compose exec app php artisan test

# Run a single test by name
docker compose exec app php artisan test --filter=SmokeTest

# Code formatting (Pint)
docker compose exec app vendor/bin/pint

# Queue worker dashboard (Horizon)
docker compose exec app php artisan horizon

# Create SuperUser (after seeding)
docker compose exec app php artisan app:create-superuser
```

## Stack

| Layer | Technology |
|-------|-----------|
| Language | PHP 8.4-fpm |
| Framework | Laravel 12 |
| Frontend | Livewire 3 + Alpine.js 3 |
| CSS | Tailwind CSS 4 |
| Database | MySQL 8.0 (shared `_infra/`) |
| Cache / Queue | Redis 7 (shared `_infra/`) |
| Queue Monitor | Laravel Horizon 5 |
| Auth | Laravel Sanctum 4 |
| Mail (dev) | Mailpit (shared `_infra/`) |
| Web Server | Nginx alpine |

## Module Structure

```
app/Modules/
  Shared/         Kernel: User, permissions, base traits, middleware
  Auth/           Registration, login, password reset
  Tickets/        Core ticketing, lifecycle, state machine
  Assignment/     Group assignment, self-assign, peer transfers
  Escalation/     Condition reports, maintenance requests, approvals
  Communication/  Comments, notification engine
  SLA/            SLA timers, business hours, warning/breach logic
  CSAT/           Post-resolution feedback
  Precedent/      Resolution capture, auto-suggest, linking
  Reporting/      Reports, CSV/XLSX export
  Admin/          Admin configuration panel
  Audit/          Audit logging
```

## Shared Infrastructure

MySQL, Redis, phpMyAdmin, and Mailpit are provided by a shared `_infra/` directory.  
**Do not add these services to this `docker-compose.yml`.** Connect via the `shared-dev` external network.

| Service | URL |
|---------|-----|
| App | http://localhost:8001 |
| phpMyAdmin | http://localhost:8080 |
| Mailpit | http://localhost:8025 |
