# Next Chat Brief

Use this file to start a fresh implementation chat.

## Goal

Build the Release 2 Beta Evidence Baseline for the South West Equine Services horse quotes app from the approved plan and scaffolded docs in this directory.

## Source of truth

Read these files first and treat them as the active plan:

1. `docs/horse-quotes/README.md`
2. `docs/horse-quotes/project-brief.md`
3. `docs/horse-quotes/technical-decisions.md`
4. `docs/horse-quotes/domain-rules.md`
5. `docs/horse-quotes/calculation-reference.md`
6. `docs/horse-quotes/design-direction.md`
7. `docs/horse-quotes/build-sequence.md`
8. `docs/horse-quotes/release-2/beta-evidence-baseline.md`

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
- each client quote is always priced as a full depot or home round trip, even when operationally appended onto another job
- weekly fuel price affects rates
- loading practice stays separate in V1
- shared loads need partial-overlap support
- shared loaded portions use a configurable percentage of the loaded rate, with `0.75` as the typical current starting point
- manual final totals must be retained alongside engine totals

Release 1 verification closure is complete. The current slice is measurement-led, not a new staging or production approval.

## Spreadsheet-derived behaviours not to preserve

- out-of-range cell references
- brittle monthly summary formulas
- `#DIV/0!` placeholders
- hard-coded worksheet coordinate dependencies

## Recommended starting point

Begin with `Phase 11` from `build-sequence.md` and keep the evidence capture aligned with `release-2/beta-evidence-baseline.md`.

## First implementation milestone

Get to this state first:

- the Beta operational evidence panel is available to named staff
- 20 real transport quotes have been recorded against the Release 2 baseline
- route outcomes, failure categories, original exception reasons, fallback and override rates, and turnaround medians are visible
- the 20-quote checkpoint has a jointly recorded next behavioural refinement

## Handoff note

**Start from `docs/STATUS.md` and `docs/BACKLOG.md`** — they hold the current state and the single
task list. This brief predates them and is kept for background only.

The original workbook analysis (`Pipeline and Quotes.xlsx`) is **not stored in this repository**.
Its distilled, approved pricing evidence now lives in
`docs/workflow/pricing-policy.md` and `docs/horse-quotes/calculation-reference.md`.
