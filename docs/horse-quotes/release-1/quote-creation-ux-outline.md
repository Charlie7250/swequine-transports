# Quote Creation UX Outline

## Experience goal

Release 1 must let a non-technical operator turn an inbound transport enquiry into an issued quote quickly and confidently. The interface should lead with customer and postcode information, then automatic routing, then a plain-language pricing review. It should not present manual leg-mile fields until an exception has been identified.

The design is for a private portal used by a horse transport business. It is not a public quote form and it does not add email ingestion, marketplace concepts, or loading-practice quoting to the transport flow.

## Flow at a glance

```text
Transport enquiry
  -> Capture essentials
  -> Resolve route and miles
  -> Review route and price
  -> Handle exception only if needed
  -> Issue quote
```

The user sees this as one guided flow with a visible progress state. Drafts can be saved at any point, but only an eligible route and complete quote data unlock the issue action.

## Screen and state outline

| Screen or state | Purpose | Required content | Primary action |
| --- | --- | --- | --- |
| New enquiry | Select the service and capture the inbound source. | `Transport quote` selected by default only when the operator started from transport quoting; visible separate loading-practice option or route away from this flow; source selector. | `Continue to enquiry details` |
| Enquiry details | Capture the minimum information required to route and quote. | Customer name, contact method, pickup postcode, drop-off postcode, horse count, requested date or date-to-be-arranged, constraints, completeness indicator. | `Resolve route and miles` when route inputs are valid. |
| Route resolving | Make an external dependency visible without exposing technical detail. | Progress state naming the three legs and a safe retry control after failure. | No duplicate submission while a request is active. |
| Route review | Show the route that will drive pricing. | Depot context, three named legs, rounded miles, provider source, warnings, review status, last resolution time. | `Use this route for quote` when eligible. |
| Price review | Explain the calculated quote before issue. | Active fuel and rate context, each leg's miles and rate type, engine total, final total, any override badges, issue checklist. | `Save draft quote` or `Issue quote`. |
| Exception handling | Contain manual decisions so they are visible and deliberate. | Failure or review explanation, retry and correction actions, manual leg-mile controls only when needed, reason capture, original route values where present. | `Apply exception and recalculate`. |
| Issued quote confirmation | Confirm the exact issued revision. | Quote reference, issued time, final total, route and override summary, next-pipeline visibility. | `View issued quote` or `Start another enquiry`. |

## Route-first interaction rules

- The enquiry form places pickup and drop-off postcodes before any route or price values.
- The default action after valid essential data is `Resolve route and miles`.
- The system shows depot-to-pickup, pickup-to-drop-off, and drop-off-to-depot separately. It does not show only a combined distance.
- The normal route review uses system-resolved whole miles. Manual mileage controls are absent until an exception action is intentionally opened.
- The route and pricing review remain on the same operator journey, with enough context that a staff member does not need a separate spreadsheet or map window.
- The issue action is disabled until the issue checklist is complete, with a direct link to the missing field or route condition.
- A draft save never looks like an issued quote. State labels must use the words `Draft enquiry`, `Quote-ready`, `Draft quote`, and `Issued quote` consistently.

## Required empty states

| Context | Required message | Required action |
| --- | --- | --- |
| No enquiries yet | `No transport enquiries have been recorded yet.` | `Create transport enquiry` |
| New draft with no essential details | `Add customer and journey details to calculate a route.` | Focus customer and postcode fields. |
| No route result yet | `Enter valid pickup and drop-off postcodes, then resolve the route.` | `Resolve route and miles` stays unavailable until inputs are valid. |
| No draft quote yet | `A draft quote will be created after a route is accepted and pricing is available.` | Route back to route review. |
| No issued quotes in the current view | `No quotes have been issued in this view.` | Link to draft or current enquiry list. |

## Required validation messages

Messages use plain language, name the field or next action, and preserve the operator's input.

| Condition | Message |
| --- | --- |
| Customer name missing | `Enter the customer's name before issuing a quote.` |
| Contact method missing | `Add at least one way to contact the customer before issuing a quote.` |
| Pickup postcode missing | `Enter the pickup postcode to calculate the depot-to-pickup and pickup-to-drop-off legs.` |
| Pickup postcode invalid | `Check the pickup postcode. It could not be used to calculate the route.` |
| Drop-off postcode missing | `Enter the drop-off postcode to calculate the pickup-to-drop-off and drop-off-to-depot legs.` |
| Drop-off postcode invalid | `Check the drop-off postcode. It could not be used to calculate the route.` |
| Horse count missing or invalid | `Enter the number of horses for this transport quote.` |
| Date context missing | `Enter the requested date or select date to be arranged before issuing a quote.` |
| Constraints not acknowledged | `Confirm whether any special transport constraints are known before issuing a quote.` |
| Pricing context unavailable | `This quote cannot be priced because the active fuel or rate context is unavailable. Ask the responsible staff member to update it.` |
| Incomplete route | `All three route legs are required before this quote can be priced.` |
| Override reason missing | `Explain why the route or final total is being overridden before continuing.` |

## Required routing messages

| Route state | Message | Visible choices |
| --- | --- | --- |
| Resolving | `Calculating depot to pickup, pickup to drop-off, and drop-off to depot.` | Wait state only. |
| Resolved | `Route calculated from the entered postcodes.` | Review legs and continue. |
| Resolved with warning | `Route calculated, but review the highlighted detail before continuing.` | Read warning, continue, or correct and resolve again. |
| Operator review required | `This route needs your review before it can be used for pricing.` | Accept route, correct details and retry, or use exception handling. |
| Invalid input | `The route could not be calculated because one or more postcodes need attention.` | Correct highlighted inputs and retry. |
| Unresolved leg | `The route could not be calculated for [leg name]. Check the postcode or use exception handling if the business needs to quote now.` | Correct and retry, or open exception handling. |
| Provider unavailable | `Route lookup is temporarily unavailable. Your enquiry has been saved.` | Retry, save draft, or open approved manual-mile fallback. |
| Provider response invalid | `The route result cannot be used safely. Retry the lookup or use approved exception handling.` | Retry, save draft, or open exception handling. |

The actual provider name may appear in a compact source label for confidence, but internal error codes, credentials, raw provider payloads, and technical stack traces must not appear in operator-facing messages.

## Override visibility and exception controls

An override must be visible in three places:

1. In the route or price review where it is made, alongside original and replacement values.
2. In the draft quote summary, as a concise badge such as `Manual route miles` or `Final total overridden`.
3. In the issued quote record, with a route and pricing audit summary available to staff.

Manual leg-mile fields appear only after one of these deliberate actions:

- `Use manual miles because route lookup is unavailable`;
- `Adjust a disputed route leg`; or
- `Handle a known operational route exception`.

The exception panel must state that the original automated route stays recorded and that a reason is required. It must never reset the price review silently.

## Speed and confidence design rules

- Prefer a short, ordered form over a dense technical editor.
- Keep the next required action obvious and singular.
- Show progress and saved state after every meaningful action.
- Keep pricing explanation close to the final total so staff can answer basic customer questions.
- Use descriptive leg labels, not internal identifiers.
- Separate blockers from warnings: a warning does not look like an error, and a blocker explains how to proceed.
- Retain entered values after validation or provider failure.
- Make it clear when the operator is changing a draft, reviewing a calculation, or issuing a customer-facing quote.

