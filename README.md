# South West Equine Services Quotes

This is the standalone internal Laravel app that replaces the spreadsheet-driven horse transport quoting workflow.

## Stack

- PHP 8.3+
- Laravel 13
- Blade
- PostgreSQL for the target runtime
- Minimal JavaScript

## Approved planning docs

The approved source-of-truth docs live in:

- `docs/horse-quotes/README.md`
- `docs/horse-quotes/project-brief.md`
- `docs/horse-quotes/technical-decisions.md`
- `docs/horse-quotes/domain-rules.md`
- `docs/horse-quotes/calculation-reference.md`
- `docs/horse-quotes/design-direction.md`
- `docs/horse-quotes/build-sequence.md`
- `docs/horse-quotes/next-chat-brief.md`

The same documents are also mirrored at `docs/*.md`.

## Current milestone

- Laravel app bootstrapped
- Internal sign-in flow in place
- Domain schema started for customers, jobs, revisions, rates, route legs, shared runs, and loading practice
- Deterministic pricing calculator started under test

## Local setup

1. Install PHP dependencies with `composer install`.
2. Copy `.env.example` to `.env`.
3. Set PostgreSQL credentials in `.env`.
4. Run `php artisan key:generate`.
5. Run `php artisan migrate --seed`.
6. Run `php artisan serve`.

Tests run against in-memory SQLite via `php artisan test`.

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
