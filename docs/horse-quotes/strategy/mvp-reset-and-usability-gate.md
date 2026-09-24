# MVP Reset And Usability Gate

## Revised MVP definition

The revised MVP is not:

- a pricing engine in isolation
- a domain-correct quote editor that still needs manual leg-mile entry
- a broad internal tool with unfinished operator flow

The revised MVP is:

A private staff-facing quoting portal that lets an operator turn an inbound transport enquiry into a usable issued quote quickly, using automatic postcode-based route mileage resolution, transparent pricing, and controlled overrides.

## Single primary success path

The main success path for MVP is:

1. enquiry arrives from an inbound source
2. operator records the minimum quote details
3. system resolves the journey into three quote legs automatically
4. system prices the quote transparently
5. operator reviews the explanation
6. operator optionally applies a manual override with a reason
7. operator issues the quote

If this path is slow, fragile, or dependent on manual leg-mile entry, the product is not ready.

## MVP-critical capabilities

The following capabilities are mandatory before MVP sign-off.

## 1. Postcode-based route lookup

The operator must be able to enter the relevant postcodes and trigger route resolution without manually calculating mileage first.

Required outcome:

- route resolution happens inside the product
- the operator is not expected to use external maps manually for normal quoting

## 2. Automatic three-leg mileage resolution

The system must resolve:

- depot to pickup
- pickup to drop-off
- drop-off to depot

Required outcome:

- the quote path is generated automatically
- the route remains compatible with the current pricing rules
- the operator can still inspect each leg separately

## 3. Transparent price calculation

The operator must see enough information to trust the quote:

- active fuel context
- active rate context
- leg miles
- rate type per leg
- engine total
- final total

Required outcome:

- the workflow is faster without becoming opaque

## 4. Manual override with audit trail

Manual intervention remains necessary in real operations, but it must become a controlled exception path.

Required outcome:

- override is allowed
- override reason is captured
- engine total remains visible
- final quoted total remains authoritative for customer-facing output

## 5. Issueable quote output

The product must end the main flow with something the business can actually send or rely on operationally.

Required outcome:

- the operator can issue the quote
- issued state is visible in the pipeline
- the issued quote remains tied to the revision and explanation record

## Not-MVP-yet checklist

If any of the statements below are still true, the product is not yet at MVP:

- staff must manually enter all three leg miles in the normal happy path
- operators routinely leave the app to work out route miles elsewhere
- quoting speed still depends on remembering spreadsheet logic
- a new staff user cannot confidently create a quote without deep training
- manual overrides are doing the work that automation should do
- quotes are saved as drafts, but not clearly issued in the real workflow sense
- routing-provider failures have no clear fallback path

## Normal path versus fallback path

## Normal path

The normal path is:

- enter journey and customer details
- resolve route automatically
- review price
- issue quote

## Fallback path

The fallback path is only for exceptions:

- routing provider unavailable
- postcode ambiguity
- disputed mileage
- known operational exception

In fallback mode:

- manual leg-mile entry is permitted
- system must record that automation was bypassed
- operator reason must be captured where practical

Manual leg-mile entry must not remain the default.

## Usability gate for internal beta

Internal beta can start when:

- automatic route mileage is live for the main quote path
- one operator can reliably produce real quotes from inbound requests
- the override path is understandable
- critical quote blockers are visible in the UI
- quote issue flow is usable without engineering support

Evidence expected:

- timed walkthroughs using real example enquiries
- operator feedback from daily use
- observed failure reasons logged during quoting

## Usability gate for production release

Private production release can happen when:

- the internal team can rely on the product daily
- quote generation is faster than the spreadsheet in real use
- routing failures and override cases have a defined operating path
- quote records are trusted for follow-up and pipeline handling
- the business owner is comfortable using the system as the default quoting tool

Evidence expected:

- real enquiries processed through the app
- measured quote turnaround improvement
- low confusion around the primary flow
- acceptable rate of manual fallback usage

## Working metrics for the next release cycle

The next release cycle should capture these metrics even if only manually at first:

- time from enquiry start to quote-ready draft
- time from enquiry start to issued quote
- percentage of quotes that use manual override
- percentage of quotes that use manual leg-mile fallback
- routing failure rate
- top repeated reasons for blocked or slow quotes

These metrics matter more than adding additional admin features in the immediate next phase.
