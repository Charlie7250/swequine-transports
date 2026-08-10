# Project Brief

## Product name

South West Equine Services horse quotes app.

## Product goal

Build a small internal web application that replaces the current spreadsheet workflow for transport quotes and related admin. The app should reduce manual quoting friction, keep rate logic transparent, and provide a cleaner path to later automation.

## Primary users

- Internal staff at South West Equine Services

## Secondary future users

- Website visitors requesting quotes
- Admin users reviewing quote requests that originate from email or a public form

## Problem being solved

The current spreadsheet mixes rate calculation, quote drafting, pending work, completed jobs, shared load calculation, and reporting in a brittle manual workflow. The logic works well enough to support the business, but it is difficult to audit, easy to break, and hard to adapt when pricing rules change.

## V1 objective

Deliver a usable internal app that can:

- maintain weekly fuel pricing
- calculate transport quotes from route legs
- support horse-count-sensitive quoting
- support shared loads with per-customer allocation
- track quotes through a simple operational pipeline
- keep revision history and calculation explanations
- keep loading practice separate from transport quoting

## V1 non-goals

- automated email ingestion
- public self-service quoting
- full client account management
- polished marketing site pages
- advanced permissions beyond small internal staff access
- complex scheduling or route optimisation

## Success criteria

V1 is successful when staff can produce and manage real quotes in the app faster than in the spreadsheet, while understanding how each figure was calculated and being able to override specific values when needed.

## Spreadsheet-derived insight

The workbook suggests the real business workflow is built around:

- weekly fuel-driven rate changes
- distinct unloaded and loaded mileage charges
- ad hoc manual notes and manual price adjustments
- a separate loading-practice service line
- a partial shared-load method that splits some legs but not all
- manual promotion of records across quote, pending, booked, and completed states

## Known uncertainties

These need confirmation during implementation or client review:

- exact horse-count pricing beyond the current one-horse, two-horse, and shared-load patterns
- whether the under-100-mile multiplier should still apply
- exact business rules for pending versus quoted in day-to-day use
- whether loading-practice packages should ever be folded into transport quotes
- the exact circumstances for manual fixed add-ons such as waiting time or time-based extras
