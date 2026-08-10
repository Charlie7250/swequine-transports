# Domain Rules

## High-level domain model

The app should model these concepts explicitly:

- customer
- job
- job revision
- transport leg
- weekly fuel price
- rate settings
- shared run
- shared run allocation
- loading-practice quote
- calculation explanation

## Job status model

The agreed status model for transport jobs is:

- draft
- quoted
- pending
- booked
- completed
- lost

## Status meanings

- `draft`: work-in-progress quote, not yet issued
- `quoted`: quote has been issued externally
- `pending`: awaiting customer decision or follow-up
- `booked`: accepted and operationally committed
- `completed`: journey completed
- `lost`: quote not won or no longer active

## Revision model

Each job should have versioned revisions.

- One job record represents the business opportunity or booking.
- Each meaningful change creates a new revision.
- One revision is the current working revision.
- One revision may be the issued revision.
- One revision may be the accepted revision if the quote is booked.

## Reporting dates

Use these fields for reporting:

- quoted reporting based on `issued_at`
- booked reporting based on `booked_at`
- completed reporting based on `completed_at`

## Depot and home rule

Assume one configurable depot postcode in V1. This acts as the operational start and end point for transport calculations unless a revision-level override is later approved.

## Leg structure

Transport pricing should be based on explicit legs:

- depot to pickup
- pickup to drop-off
- drop-off to depot

In future there may be more complex multi-stop journeys, but V1 should at least support a clear leg-based structure so shared loads and manual overrides can be represented properly.

## Horse-count behaviour

The sheet clearly distinguishes between:

- unloaded travel
- loaded travel
- at least one two-horse rate
- shared-load logic

V1 should support:

- single-horse quote
- multi-horse quote using an explicit horse count field
- shared-load quote with per-customer allocation

Any pricing assumptions beyond what the workbook clearly shows should be marked provisional and easy to revise.

## Shared-load model

Use a parent `shared_run` with one or more linked `shared_run_allocations`.

Each allocation belongs to a customer-facing job revision and stores:

- which legs are charged fully
- which legs are split
- the chargeable miles and amounts for that allocation
- the explanation of why those miles were assigned

## Shared-load business rule

The working business assumption is:

- if a client is tagged onto an existing job, they still pay their own full applicable section
- shared portions are split only where the route is genuinely shared
- the model must handle partial overlap, not only 50/50 entire-trip sharing

## Loading practice

Loading practice remains a separate service in V1.

It should not be forced into the transport quote flow.

Known workbook package structure:

- within 15 miles: fixed package
- within 25 miles: fixed package plus hourly on-site rate
- within 50 miles: fixed package plus hourly on-site rate
- over 50 miles: POA
- handling and loading livery: day, week, and fortnight prices

## Manual authority rules

There are three levels of control:

- global weekly fuel input
- per-quote or per-revision pricing overrides where allowed
- manual final quoted total override

If a manual final total is entered:

- it becomes authoritative for customer-facing output
- it becomes authoritative for revenue reporting
- the engine-calculated total must still be retained for audit and comparison

## Mileage and fallback rules

Working V1 assumption:

- each leg mileage is rounded to whole miles for quoting
- each leg may be manually overridden if map data is missing or disputed

## Under-100-mile rule

The workbook contains a `Trips under 100 miles` multiplier of `1.4`, but the current quote sheets do not apply it consistently.

V1 should not apply this rule until the client confirms whether it is active business policy.

## Spreadsheet inconsistencies that must not be copied

Do not recreate these behaviours in the app:

- references to out-of-range cells such as `Monthly Bookings (June)!M596`
- brittle hand-picked totals in `Monthly Totals`
- `#DIV/0!` placeholder logic
- hidden dependence on fixed spreadsheet cell coordinates
