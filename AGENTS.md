# AGENTS.md — Beza Platform

## Project structure

- **All work is inside `backend/`** — a Laravel 11 modular monolith. Every command below runs from `backend/`.
- **12 modules** under `app/Modules/`: Agent, Compliance, Core, EventBus, FinancialCore, Fraud, Fx, Identity, Ledger, Merchant, Remittance, Wallet.
- Module registration is manual in `bootstrap/providers.php` (not auto-discovered).
- Cross-module communication via EventBus (RabbitMQ) — no direct inter-module calls.
- Two CI workflows: `beza-ci.yml` (SQLite + feature tests + OpenAPI drift check) and `ci.yml` (MySQL + Redis + PHPStan + Flutter).

## Critical architecture rules

- **G-002**: Every financial transaction must go through CFE. No module writes balances directly.
- **G-004**: Money = `int` (smallest currency unit) + `App\Domain\ValueObjects\Money`. Never use float.
- **G-005**: Ledger is append-only (WORM). No UPDATE/DELETE on journal entries. Use reversal entries for corrections.
- **G-007**: ULID for all primary keys.
- **G-008**: Inter-module communication via Events only.
- **G-010**: Feature test for every public endpoint.

## Setup & dev commands

```bash
cd backend
composer install
cp .env.example .env
php artisan key:generate
php artisan migrate --seed
php artisan serve
```

Full dev server (PHP serve + queue + logs + Vite):
```bash
composer dev
```

## Testing

```bash
# Run all tests (uses SQLite :memory: by default)
php artisan test --use-baseline=deprecation-baseline.xml

# Run only feature tests (CI does this)
php artisan test --testsuite=Feature --use-baseline=deprecation-baseline.xml

# Run directly with PHPUnit
php vendor/bin/phpunit
```

- Tests use `RefreshDatabase` trait and SQLite in-memory (`phpunit.xml`).
- Deprecation baseline suppresses PHP 8.5 PDO warnings from vendor code.
- Feature tests use `Illuminate\Support\Facades\Event` to fake events.
- Performance suite exists at `tests/Performance/BasicLoadTest.php`.

## Code style

- `declare(strict_types=1)` on every file.
- `final readonly class` for value objects (`app/Domain/ValueObjects/`).
- Controller → Service → Repository strict layering (G-003).
- 4-space indentation (`.editorconfig`).
- Laravel Pint would be the formatter if configured (not currently configured).

## OpenAPI spec

- Auto-generated via `php artisan openapi:generate` (the command lives in Ledger module).
- Spec file: `docs/specs/openapi.yaml` — CI checks for drift with `git diff --exit-code`.
- 54 endpoints across 10 tags.

## Scheduling

All schedules in `routes/console.php`, timezone `Asia/Damascus`:
- Ledger reconciliation daily at 00:30
- CBS reports (trial balance, balance sheet, income statement) at 01:00–02:00
- Ledger health check every 6 hours
- Feedback review weekly on Monday 09:00
