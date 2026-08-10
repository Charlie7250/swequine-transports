# Horse Quotes App Handoff

This directory is the source-of-truth scaffold for the South West Equine Services horse quotes app.

Use these files in this order:

1. `next-chat-brief.md`
2. `project-brief.md`
3. `technical-decisions.md`
4. `domain-rules.md`
5. `calculation-reference.md`
6. `design-direction.md`
7. `build-sequence.md`

## Purpose

The goal is to replace the current spreadsheet-driven quoting workflow with a small internal web app that:

- calculates horse transport quotes from postcode-based route legs
- uses current weekly fuel-card pricing with controlled overrides
- keeps pricing logic visible and easy to revise
- supports individual quotes, shared loads, loading practice, and pipeline tracking
- can later ingest emails and eventually power a website-facing quote flow

## Current status

The spreadsheet has already been analysed. The app design has been discussed and approved at a planning level. These docs are intended to let a fresh implementation chat start work without re-running the planning loop.

## Spreadsheet analysis inputs

The underlying workbook analysis artefacts live here:

- `work/spreadsheet-analysis/output/summary.json`
- `work/spreadsheet-analysis/output/contact_sheet.png`

## Important constraints

- Build the first version as a standalone internal app, not a WordPress plugin.
- Use `PHP 8.3+`, `Laravel 13`, `Blade`, `PostgreSQL`, and minimal JavaScript.
- Do not add new dependencies without explicit user approval.
- Keep pricing logic deterministic, inspectable, and documented.
- Treat spreadsheet-derived pricing rules as provisional where the workbook is inconsistent.
