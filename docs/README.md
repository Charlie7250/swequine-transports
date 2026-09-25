# Horse Quotes Working Mirror

> **⚠️ This root `docs/*.md` mirror has drifted and is not maintained.** For current work start at
> `/CLAUDE.md`, then `docs/STATUS.md` and `docs/BACKLOG.md`. For planning detail use
> `docs/horse-quotes/*` directly — it is the source of truth. This mirror is slated for removal
> (backlog task A4).

This directory mirrors the approved planning scaffold for day-to-day repo browsing.

The source of truth is `docs/horse-quotes/`.

Use the source-of-truth files in this order:

1. `horse-quotes/next-chat-brief.md`
2. `horse-quotes/project-brief.md`
3. `horse-quotes/technical-decisions.md`
4. `horse-quotes/domain-rules.md`
5. `horse-quotes/calculation-reference.md`
6. `horse-quotes/design-direction.md`
7. `horse-quotes/build-sequence.md`

## Purpose

The goal is to replace the current spreadsheet-driven quoting workflow with a small internal web app that:

- calculates horse transport quotes from postcode-based route legs
- uses current weekly fuel-card pricing with controlled overrides
- keeps pricing logic visible and easy to revise
- supports individual quotes, shared loads, loading practice, and pipeline tracking
- can later ingest emails and eventually power a website-facing quote flow

## Current status

The spreadsheet has already been analysed. The app design has been discussed and approved at a planning level. These mirrored docs are kept in-repo for convenience, but implementation should cite `docs/horse-quotes/*` as the authoritative copy.

## Spreadsheet analysis inputs

The original workbook (`Pipeline and Quotes.xlsx`) is **not stored in this repository**. Its
distilled, approved pricing evidence lives in `docs/workflow/pricing-policy.md` and
`docs/horse-quotes/calculation-reference.md`.

## Important constraints

- Build the first version as a standalone internal app, not a WordPress plugin.
- Use `PHP 8.3+`, `Laravel 13`, `Blade`, `PostgreSQL`, and minimal JavaScript.
- Do not add new dependencies without explicit user approval.
- Keep pricing logic deterministic, inspectable, and documented.
- Treat spreadsheet-derived pricing rules as provisional where the workbook is inconsistent.
