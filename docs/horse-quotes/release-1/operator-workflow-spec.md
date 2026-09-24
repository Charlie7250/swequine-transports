# Operator Workflow Specification

## Purpose and scope

This specification defines the one Release 1 transport-quote workflow that must be fast enough for daily staff use. It covers a manually recorded inbound enquiry from an existing channel, such as direct contact, social media, word of mouth, or HayNet. It does not introduce public intake, email ingestion, marketplace behaviour, or loading-practice quoting.

The workflow serves a horse transport business. The operator must not need to calculate normal route mileage outside the portal or understand spreadsheet pricing logic to issue a quote.

## Core state definitions

| State | Meaning | What it is not | Permitted next outcome |
| --- | --- | --- | --- |
| Draft enquiry | An inbound transport request recorded with whatever information is currently known. It may be incomplete. | A calculated or sendable quote. | Collect missing data, resolve the route, or remain blocked. |
| Quote-ready enquiry | A transport enquiry with the mandatory quote inputs, an allowable route outcome, and an active pricing context. | An issued quote or a customer commitment. | Create or update a draft quote. |
| Draft quote | A saved, revision-backed transport quote with a pricing explanation. It has not been issued to the customer. | A quote that has been sent or committed. | Review, revise, override with an audit trail, or issue. |
| Issued quote | The reviewed quote revision recorded as issued, with an issue time and immutable route, pricing, and override evidence. | A booking or acceptance. | Follow the existing pipeline to pending, booked, lost, or another later operational state. |

Only transport enquiries and transport quotes use these states. A loading-practice request remains a separate service and must not be silently converted into a transport quote.

## Mandatory data before issue

An operator may save an incomplete draft enquiry, but an issued transport quote requires all of the following:

- customer name and at least one usable contact method;
- enquiry source, including `Other` where the source is not listed;
- pickup postcode and drop-off postcode;
- horse count;
- requested date or an explicitly selected `date to be arranged` state;
- any known special constraint that materially changes the quote, or an explicit `none known` confirmation;
- a configured depot postcode and active fuel and rate contexts;
- all three transport legs in one permitted route state;
- a transparent calculation explanation, including leg miles, rate types, engine total, and final total;
- an operator review confirmation; and
- where automation is not used unchanged, the required override or fallback reason and audit record.

The exact customer-facing wording and any policy for provisional quotes are open decisions in [open-decisions.md](open-decisions.md). Until those are approved, an operator must not treat missing scheduling context as an unrecorded assumption.

## Normal happy path

| Step | Operator sees | Operator decides and does | System confirms and records | State after step |
| --- | --- | --- | --- | --- |
| 1. Start enquiry | A `New transport enquiry` entry point and a clear note that loading practice is separate. | Selects transport quote and records the inbound source. | Creates a draft enquiry with a timestamp and operator identity. | Draft enquiry |
| 2. Capture essentials | A short customer and journey form, with postcode fields before any mileage fields. | Records customer contact, pickup and drop-off postcodes, horse count, requested date or date-to-be-arranged, and known constraints. | Validates data as it is entered and shows which required inputs are still missing. | Draft enquiry or ready to route |
| 3. Resolve route | A prominent `Resolve route and miles` action, depot context, and the entered journey. | Checks the locations and triggers automatic route resolution. | Requests all three legs through the routing adapter and displays a clear route state. | Quote-ready enquiry if acceptable |
| 4. Review calculated route | Three named legs with miles, provider context, and any warning or review flag. | Confirms the route is credible for this journey. | Saves the route evidence and makes the calculation path available. | Quote-ready enquiry |
| 5. Create draft quote | A transparent pricing review, not an opaque final number. | Reviews active fuel and rates, each leg's rate type and miles, engine total, and final total. | Creates or updates a revision-backed draft quote and its explanation. | Draft quote |
| 6. Handle exception, if needed | A contained exception panel, only when the route needs review or pricing must change. | Applies an allowed route or final-total override and gives the required reason. | Keeps the engine total and original route result visible, records actor, time, reason, and changed values. | Draft quote |
| 7. Issue quote | An issue checklist with all mandatory information and any exception badges. | Confirms the reviewed revision is the quote to issue. | Marks that revision issued, records `issued_at`, preserves its calculation and route snapshot, and makes the issued state visible in the pipeline. | Issued quote |

The normal path contains no manual leg-mile entry. If all valid route inputs resolve cleanly, the operator should move from Step 2 to Step 7 without leaving the portal for mapping work.

## Blocked states

A blocked state stops an operator from creating a quote-ready enquiry or issuing a draft quote until the stated action is taken.

| Blocker | What the operator sees | Required operator action | Permitted outcome |
| --- | --- | --- | --- |
| Required enquiry information missing | Inline field message and a short list of missing information. | Add the data or save the enquiry as a draft while following up. | Remains draft until complete. |
| Pickup or drop-off postcode invalid | A postcode-specific message and the entered value retained for correction. | Correct the postcode or ask the customer for a usable postcode. | Re-run route resolution. |
| Depot or active pricing context unavailable | A configuration blocker, not a generic route error. | Escalate to the responsible staff or technical owner. | No quote-ready state or issue action. |
| No complete route result | A leg-level explanation of which leg failed and why the normal route cannot be priced. | Correct information, retry later, or explicitly enter the exception path if appropriate. | Remains blocked or moves to approved fallback. |
| Route needs operator review | A visible review banner describing the affected leg or provider condition. | Confirm the result, correct inputs, or use an auditable fallback. | Quote-ready only after an explicit review outcome. |
| Draft quote has unresolved mandatory data | An issue checklist identifying each missing item. | Complete the missing data. | Remains draft quote. |

## Fallback and exception states

Fallback is an exception path, not an alternative normal workflow. The operator must choose it deliberately after the system has explained why automatic routing cannot be used unchanged.

| Exception | Normal route status | Operator decision | Required record | Result |
| --- | --- | --- | --- | --- |
| Provider outage or timeout | Hard failure | Use manual leg miles only if the business needs to quote before the provider recovers. | Provider failure detail, all three manually entered leg miles, reason, operator, time. | Quote-ready enquiry through fallback. |
| Ambiguous or poor postcode | Operator-review-required or hard failure | Correct the postcode, select a verified location where that capability exists, or use approved manual miles. | Original input, corrected value or reason, route status. | Re-resolve or fallback. |
| Credible but disputed provider mileage | Warning or operator-review-required | Accept provider miles or override one or more legs. | Original provider result, changed legs, reason, operator, time. | Draft quote with an exception badge. |
| Known operational exception | Resolved, warning, or review-required | Override one or more legs only where the usual route is not an appropriate pricing representation. | Reason, route evidence, changed miles, operator, time. | Draft quote with an exception badge. |
| Commercial price adjustment | Any permitted route state | Override only the final quoted total, not the route history. | Engine total, final total, reason, operator, time. | Draft quote with a pricing-override badge. |

Manual miles must represent every required leg. A partial manual fallback may coexist with provider-resolved legs only when the resulting three-leg record is complete and each changed leg has its own audit evidence.

## Operator confirmation rules

The operator confirms four different things, each separately visible in the workflow:

1. Enquiry completeness, the recorded data accurately reflects the inbound request or its known gaps.
2. Route credibility, the three-leg route may be used for pricing, or its exception evidence explains why not.
3. Quote review, the displayed pricing explanation and final total are the intended commercial quote.
4. Issue, this specific draft revision has been issued externally or is now the business record intended for issue.

An issue confirmation must identify the exact quote revision. A later change creates or updates a later draft revision and does not silently alter the issued evidence.

## Workflow measures

Release 1 must record enough information to calculate:

- elapsed time from enquiry start to quote-ready enquiry;
- elapsed time from enquiry start to issued quote;
- route resolution attempts, outcomes, and failure categories;
- manual route fallback rate;
- route-leg override rate;
- final-total override rate; and
- the most common blocker and exception reasons.

These measures are release evidence, not broad analytics work. They are required to decide whether the portal is becoming faster and more trustworthy than the spreadsheet.

