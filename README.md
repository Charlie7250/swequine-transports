# South West Equine Services Quotes

This is the standalone internal Laravel app that replaces the spreadsheet-driven horse transport quoting workflow.

## Stack

- PHP 8.3+ for the application
- PHP 8.5 in the default Docker app image
- Laravel 13
- Blade
- PostgreSQL for the target runtime
- Docker Compose for the default local runtime
- Minimal JavaScript

## Where to start

- `CLAUDE.md` — the single entry point (read first).
- `docs/STATUS.md` — live current state.
- `docs/BACKLOG.md` — the one task list.

Planning-detail source of truth lives in `docs/horse-quotes/*` (project brief, technical
decisions, domain rules, calculation reference, design direction, build sequence). Governance,
canonical intent, and pricing policy live in `docs/workflow/*`.

## Current milestone

- Laravel app bootstrapped
- Internal sign-in flow in place
- Domain schema started for customers, jobs, revisions, rates, route legs, shared runs, and loading practice
- Deterministic pricing calculator started under test

## Local setup

Docker Compose is the default local runtime. You do not need a host PostgreSQL service for the normal bootstrap path.

1. Copy `.env.example` to `.env`.
2. Start the local stack with `docker compose up --build -d`.
3. Run `docker compose exec app php artisan migrate --seed`.
4. Open `http://localhost:8000`.

The Docker path builds the PHP dependencies and frontend assets into the app image. After PHP, Blade, JavaScript, or Composer changes, rerun `docker compose up --build -d` so the container picks up the updated code.

For a clean Docker bootstrap, `docker compose exec app php artisan migrate --seed` creates:

- `ops@sweq.local`
- password: `password`

Create a real staff user separately before any non-local deployment.

## Direct host runtime

If you want to run Laravel directly on your machine instead of through Docker:

1. Install PHP dependencies with `composer install`.
2. Copy `.env.example` to `.env`.
3. Set PostgreSQL credentials in `.env`.
4. Run `php artisan key:generate`.
5. Run `php artisan migrate --seed`.
6. Run `php artisan serve`.

Tests use in-memory SQLite.
On the current Windows host, run `composer run test:host` to load the installed SQLite extensions for that test process only.
This command does not install extensions or edit the host PHP settings.
In the app container, run `docker compose exec app php vendor/bin/phpunit --do-not-cache-result`.
SQLite test success does not establish PostgreSQL compatibility.

Run PostgreSQL integration checks inside the app container:

```text
docker compose exec app php vendor/bin/phpunit -c phpunit.postgresql.xml --do-not-cache-result
```

The runtime rejects SQLite outside standard automated tests.
Container startup does not run migrations or seeders.

Use `docs/legacy-sqlite-migration-runbook.md` for the controlled legacy import.

## Domain notes

- Transport pricing uses three explicit route legs: unloaded, loaded, then unloaded.
- Weekly fuel price and rate settings are modelled separately so pricing stays visible and easy to revise.
- Manual final totals remain separate from engine totals for audit and reporting.
- Spreadsheet inconsistencies are not copied into the schema or pricing logic.

## Pricing engine

The first pricing service lives in `app/Services/Pricing/DeterministicPricingCalculator.php`.

It currently resolves:

- active fuel-derived base cost per mile
- unloaded and loaded rates
- whole-mile leg rounding
- per-leg amounts
- explanation payload shape
- engine versus final total handling
