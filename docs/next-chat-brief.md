# Next Chat Brief

Use this file to start a fresh implementation chat.

## Goal

Build the first usable version of the South West Equine Services horse quotes app from the approved plan and scaffolded docs in this directory.

## Source of truth

Read these files first and treat them as the active plan:

1. `docs/horse-quotes/README.md`
2. `docs/horse-quotes/project-brief.md`
3. `docs/horse-quotes/technical-decisions.md`
4. `docs/horse-quotes/domain-rules.md`
5. `docs/horse-quotes/calculation-reference.md`
6. `docs/horse-quotes/design-direction.md`
7. `docs/horse-quotes/build-sequence.md`

## Product summary

This is a small internal web app for horse transport quotes, shared loads, loading practice, and pipeline tracking. It replaces a brittle spreadsheet workflow and must keep pricing logic transparent and easy to revise.

## Required stack

- `PHP 8.3+`
- `Laravel 13`
- `Blade`
- `PostgreSQL`
- `Docker Compose` for the default local runtime
- minimal JavaScript

## Constraints

- Standalone app first, not a WordPress plugin.
- No new dependencies without explicit user approval.
- Keep pricing deterministic and inspectable.
- Surface calculation logic clearly in UI or docs during early implementation.
- Treat inconsistent spreadsheet logic as provisional, not sacred.

## Spreadsheet-derived rules to preserve

- quote legs are priced as unloaded, loaded, then unloaded
- weekly fuel price affects rates
- loading practice stays separate in V1
- shared loads need partial-overlap support
- manual final totals must be retained alongside engine totals

## Spreadsheet-derived behaviours not to preserve

- out-of-range cell references
- brittle monthly summary formulas
- `#DIV/0!` placeholders
- hard-coded worksheet coordinate dependencies

## Recommended starting point

Begin with `Phase 1` and `Phase 2` from `build-sequence.md`, then move into `Phase 3` so the pricing engine becomes the first trustworthy system component.

## First implementation milestone

Get to this state first:

- Laravel app bootstrapped
- Docker Compose local runtime in place for the app and PostgreSQL
- docs copied into repo if needed
- database schema in place for jobs, revisions, rates, and route legs
- deterministic pricing engine under test

## Handoff note

**Start from `docs/STATUS.md` and `docs/BACKLOG.md`** — they hold the current state and the single
task list. This brief predates them and is kept for background only.

The original workbook analysis (`Pipeline and Quotes.xlsx`) is **not stored in this repository**.
Its distilled, approved pricing evidence now lives in
`docs/workflow/pricing-policy.md` and `docs/horse-quotes/calculation-reference.md`.
