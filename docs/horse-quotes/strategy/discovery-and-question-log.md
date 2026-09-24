# Discovery And Question Log

## Purpose

This document structures product discovery so the next releases are driven by real operator behaviour, not only by inferred spreadsheet logic.

It should be used for:

- owner interviews
- staff interviews
- workflow observation
- sample enquiry analysis
- future platform strategy questions

## Discovery goals

The immediate discovery goals are:

- understand the real inbound enquiry path
- understand what slows quoting down today
- identify which details are usually known at first contact versus gathered later
- understand when and why manual overrides happen
- map the difference between a quote that is merely saved and a quote that is actually ready to send

## Stakeholder interview pack

## Business owner interview

Use these questions first with the business owner, then repeat the same themes with any other person who influences quoting policy, customer response standards, or release decisions.

1. Which enquiry sources bring the best customers today?
2. Which enquiry sources are most time-consuming?
3. What details do you usually have at first contact?
4. What details are most often missing?
5. How often do you already know the route pattern from memory?
6. When do you trust a quote immediately, and when do you double-check it?
7. What makes you override a calculated price?
8. What kinds of quotes are quickest today?
9. What kinds of quotes are most annoying today?
10. What makes you lose a job because quoting took too long?
11. What makes a quote hard to issue confidently?
12. What does “ready to send” mean in practice?
13. Which later pipeline stages matter most to daily work?
14. What would make you stop using the spreadsheet completely?
15. What would make you trust this portal enough to use it as the default?

## Staff user interview

Use these questions with any staff member handling quotes or follow-up.

1. What are the first steps when a new enquiry arrives?
2. Which systems or tabs do you open during quoting?
3. Which route details are easiest to get wrong?
4. What information do customers often give badly?
5. When do you need to pause and ask follow-up questions?
6. What part of the current app feels slower than expected?
7. Which fields feel easy, and which feel like admin overhead?
8. What part of the quote do customers question most often?
9. What would help you send a quote faster with more confidence?
10. What errors or exceptions do you worry about most?

## Workflow-shadowing checklist

Observe at least five real or realistic quoting sessions and capture:

- enquiry source
- timestamp the enquiry arrived
- timestamp quoting work started
- timestamp the quote was ready
- timestamp it was actually issued
- data initially available
- data missing at the start
- whether route lookup was straightforward
- whether manual override was used
- whether a shared-load question arose
- whether the quote stalled
- what caused the stall

## Sample enquiry capture template

Use this structure for example enquiries:

| Field | Value |
| --- | --- |
| Enquiry ID | |
| Date | |
| Source channel | Word of mouth / Social / HayNet / Direct / Other |
| Customer name | |
| Contact method | |
| Pickup postcode | |
| Drop-off postcode | |
| Horse count | |
| Requested date | |
| Flexibility window | |
| Special constraints | |
| Is loading-practice related? | |
| Is shared-load possibility mentioned? | |
| Missing information at first contact | |
| Quote issued? | |
| Time to issue | |
| Manual override used? | |
| Manual route fallback used? | |
| Notes | |

## Open questions

These questions still need answers before later phases can be designed tightly.

## Pricing and quoting

- What minimum quote details are genuinely required before staff can price confidently?
- How often does horse count materially change the quote in practice?
- Which extras are common enough to deserve structured fields?
- When should a quote remain provisional rather than fully issued?
- How often are fixed customer-specific adjustments used?

## Routing and mileage

- Which routes most often need manual judgement even if automation exists?
- How often do postcodes alone fail to describe the real pickup and drop-off?
- What level of routing-provider mismatch is acceptable before staff lose trust?
- Which route failures should block quoting entirely, and which should allow fallback?
- Does the business need route-history reuse for repeat journeys?

## Shared-load operations

- How often are shared loads priced proactively versus discovered later?
- Who decides whether a genuinely shared section exists?
- What signals would help identify shared-load opportunities earlier?
- At what point does shared-load planning become worth the extra complexity in the UI?

## Quote issue and follow-up

- What operational steps happen immediately after a quote is issued?
- What information must appear on the customer-facing quote output?
- What makes a quote move from quoted to pending versus straight to booked?
- What reminders or follow-up visibility are missing today?

## Future marketplace and subscriptions

- Which transporter pain is strongest: lead generation, quoting speed, route utilisation, or admin load?
- What would make a transporter pay for listing only?
- What would make a transporter upgrade to premium portal features?
- What would make transporters share availability or route intent data?
- What trust, compliance, and quality controls would be needed before open marketplace matching?

## Research questions for later route-matching work

- How should the system represent a transporter’s intended route on a given date?
- What is the minimum data needed to answer “who is already travelling near this journey?”
- How should shared-load opportunity differ from general lead matching?
- How should transporter privacy and competitive sensitivity be handled around route visibility?
- What threshold defines “near” for journey matching, in miles, time, and route geometry?

## Working assumptions to log and revisit

- Transporter businesses remain the paying customer
- route automation is mandatory for MVP
- marketplace work is documented but not approved for immediate build
- staging is required before private production rollout

## Decision log

Use this table as planning decisions are made.

| Date | Owner | Decision | Rationale |
| --- | --- | --- | --- |
| 2026-08-21 | Product planning | Reframe the current app as pre-MVP | Manual route-mile entry means the main quoting promise is not yet delivered |
| 2026-08-21 | Product planning | Set automatic route mileage as an MVP gate | Quote speed and operator usability depend on it |
| 2026-08-21 | Product planning | Treat transporter businesses as the paying customer | Near-term and long-term value are both transporter-led |
| 2026-08-21 | Product planning | Use a portal-now, SaaS-ready-seams approach | Current priority is the private operator portal, but future expansion should stay viable |
| 2026-08-21 | Product planning | Plan for private staging plus private production | Release discipline is needed before daily staff reliance |

Add to this log whenever a question is resolved in a way that changes product or delivery direction.
