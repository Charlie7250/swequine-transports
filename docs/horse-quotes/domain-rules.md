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

## Beta evidence baseline

Release 2 uses a 20-quote evidence checkpoint.

- Named staff record 20 real transport quotes through the Beta operational evidence panel.
- Review route outcomes, failure categories, original transport exception reasons, fallback rates, override rates, and quote-ready and issued turnaround medians.
- Exception reason counts use original audit events only. Revision-history copies do not inflate counts.
- Empty exception data displays exactly `No transport exception reasons recorded.`
- At the checkpoint, business and technical owners jointly record the highest-frequency blocker and exception reasons, assess the evidence, and choose the next behavioural refinement.
- This baseline does not approve the next change, deployment, or daily-reliance release.
- The transport-day feature remains proposed and out of scope for this slice.

## Depot and home rule

Assume one configurable depot postcode in V1. This acts as the operational start and end point for transport calculations unless a revision-level override is later approved.

This rule is pricing-authoritative, not only operationally descriptive:

- every transport quote is calculated as if the vehicle leaves the depot or home, completes the quote's pickup and drop-off, and returns to the depot or home
- this remains true even when the real-world journey is appended onto another job in the pipeline
- operational chaining must not reduce a client's quoted transport total unless the shared-load rules below explicitly split a genuinely shared portion

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
- the shared-load percentage applied to any split loaded portion

## Shared-load business rule

The working business assumption is:

- if a client is tagged onto an existing job, they still pay their own full applicable section
- shared portions are split only where the route is genuinely shared
- the model must handle partial overlap, not only 50/50 entire-trip sharing
- the shared loaded portion should use a configurable percentage of the loaded rate per client, with `0.75` as the current typical starting point
- operators must be able to change that shared-load percentage through settings rather than code when business practice changes

## Loading practice

Loading practice remains a separate service in V1.

It should not be forced into the transport quote flow.

Known workbook package structure:

- within 15 miles: fixed package
- within 25 miles: fixed package plus hourly on-site rate
- within 50 miles: fixed package plus hourly on-site rate
- over 50 miles: POA
- handling and loading livery: day, week, and fortnight prices

### Known limitation: travel miles are entered manually

Loading practice pricing currently uses a manually entered `travel_miles` value
(`LoadingPracticePricingCalculator::normaliseTravelMiles`); it is not resolved from a
routing provider. This is acceptable for now because loading practice is a low-volume,
separate service, but it means the distance band and any mileage-driven charge depend on
the operator typing the correct figure. When routing is wired in for transport quotes,
consider whether loading practice should reuse the same distance resolution rather than
staying on manual miles.

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
