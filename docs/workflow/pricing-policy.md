# Transport pricing policy

Canonical revision: `r2`.

State: approved on 2026-09-13.

Approval identity and history appear in the [workflow index](index.md#canonical-approval-ledger).
The accepted [B-003 batch](batches/B-003-pricing-policy-and-examples.md#approval-record) authorised this documentation work, not application implementation.

## Canonical Content

### Authority and status

This provisional policy governs private minimum viable product transport pricing until approved client feedback replaces it.
The user selected evidence-backed recommendations for unresolved rules and confirmed the horse-count and rounding choices.

No rule in this record changes application behaviour by itself.
A separately approved implementation batch must compare the application with this policy and address confirmed differences.

### Standard quote structure

A standard transport quote has three customer-pricing legs in this order:

1. Depot to pickup, charged at the unloaded rate.
2. Pickup to drop-off, charged at the applicable loaded rate.
3. Drop-off to depot, charged at the unloaded rate.

Operational chaining does not reduce these customer-pricing legs.
Only the shared-load policy can reduce charges for genuinely shared portions.

### Rate construction

Calculate the vehicle base cost per mile from the active fuel price, litres per gallon, miles per gallon, and maintenance per mile.
Add the unloaded labour amount to produce the unloaded rate.
Add the loaded labour amount to produce the base loaded rate.

Round the base cost, unloaded rate, and base loaded rate to six decimal places after each calculation.
Rate-setting values remain configurable and retain their effective-date history.

### Horse-count pricing

Keep the unloaded rate unchanged for every supported horse count.
Apply the horse-count multiplier only to the base loaded rate.

| Horse count | Loaded-rate rule | Automatic quote status |
| --- | --- | --- |
| One | Base loaded rate multiplied by `1.5` | Supported |
| Two | Base loaded rate multiplied by `1.75` | Supported |
| Above two | No approved multiplier | Manual review required |

Round the multiplied loaded rate to six decimal places before pricing the loaded leg.
Store both multipliers as configurable settings rather than code constants.
Client feedback must confirm or amend these provisional values before private reliance.

### Rounding

Round each positive route distance to the nearest whole mile before pricing.
Round a half mile upwards.

Use six decimal places for calculated rates.
Multiply each rounded leg distance by its applicable six-decimal rate.
Round each leg amount to two decimal places using half-up rounding.

Sum the rounded leg amounts to produce the transport subtotal.
Do not replace this order with one final rounding of unrounded leg amounts.
Round monetary extras and shared portions as their sections specify.
Store engine and final totals to two decimal places.

Require manual extra effects and final-total replacement amounts in pounds and pence with exactly two decimal places.
Reject greater precision instead of rounding it silently.
Calculate the engine total before applying any manual extra or final override.
Use the entered replacement amount as the final total without another calculation stage.

### Short journeys

Apply no automatic short-journey multiplier or minimum charge.
The workbook's under-100-mile factor of `1.4` remains unconfirmed evidence.

If an operator needs a different commercial total for a short journey, use the final-total override process.
The explanation must identify the short-journey reason.

### Extras

Apply no automatic or structured extra while the business has not confirmed extra types and calculation rules.
Examples include waiting time, overnight work, tolls, and other manual fees.

An authorised operator can include an extra through a final-total override.
The explanation must name the extra and its monetary effect.
The engine total remains the result without that extra.

### Shared loads

Start each customer allocation from that customer's standard three-leg pricing structure.
Split only the portions that are genuinely shared.
Charge every solo portion at the customer's full applicable rate.

For a shared unloaded portion, divide its charge equally by the recorded number of customers sharing that portion.
The divisor must be at least two.

For a shared loaded portion, charge each customer `0.75` of that customer's horse-count-adjusted loaded rate.
Apply the percentage to the shared loaded miles only.
Round each full and shared portion to two decimal places before summing its allocation leg.

Keep `0.75` as a configurable default.
A change to the default affects future pricing contexts only.
An exceptional customer total uses the final-total override process until a later policy approves per-allocation percentages.

Every shared allocation must record full miles, shared miles, the divisor or percentage, and an operator explanation.
Pricing policy does not establish vehicle capacity or operational route fitness.

### Fuel-price corrections

Use the active weekly fuel record when creating a new calculated revision.
Store the selected fuel record on that revision.

Do not mutate a fuel record that an existing revision uses.
Record a correction as a new fuel record.
A corrected draft requires a new calculated revision that explicitly selects the corrected fuel record.

Issued, booked, completed, and historical revisions retain their original fuel evidence and totals.
They do not reprice automatically when the active fuel record changes.
Any revised customer quote follows the normal draft, review, and issue process.

### Final-total overrides

Restrict final-total overrides to staff whom the business owner has explicitly authorised for quote exceptions.
Apply an override only to the current draft revision.
The replacement total must be positive.

Require one of these reason categories:

- Commercial adjustment.
- Customer agreement.
- Goodwill adjustment.

Require an explanation that states why the override applies.
If the override represents an extra, the explanation must name its monetary effect.

Create a new revision for the override.
Retain the engine total, final total, category, explanation, actor, and time as separate evidence.
Use the final total for customer output and revenue reporting.

Do not carry an override silently after route, horse-count, rate, fuel, shared-allocation, or extra inputs change.
The authorised operator must review and apply it again to the recalculated draft.
Never mutate an issued or historical revision to introduce an override.

### Representative example inputs

Examples P1 through P8 use synthetic customers and no personal data.
Their rate constants support arithmetic verification only.
They do not approve production rate-setting values.

| Input | Value |
| --- | ---: |
| Fuel price per litre | `1.5300` |
| Litres per gallon | `4.5400` |
| Miles per gallon | `22.0000` |
| Maintenance per mile | `0.050000` |
| Unloaded labour amount per mile | `0.5555555556` |
| Loaded labour amount per mile | `0.8064516129` |

The common resolved rates are:

| Calculation | Unrounded result | Six-decimal result |
| --- | ---: | ---: |
| Base cost | `(1.53 × 4.54 ÷ 22) + 0.05` | `0.365736` |
| Unloaded rate | `0.365736 + 0.5555555556` | `0.921292` |
| Base loaded rate | `0.365736 + 0.8064516129` | `1.172188` |
| One-horse loaded rate | `1.172188 × 1.5` | `1.758282` |
| Two-horse loaded rate | `1.172188 × 1.75` | `2.051329` |

### P1: standard one-horse quote

Use rounded leg distances of `10`, `90`, and `96` miles.

| Leg | Calculation | Amount |
| --- | ---: | ---: |
| Depot to pickup | `10 × 0.921292` | `9.21` |
| Pickup to drop-off | `90 × 1.758282` | `158.25` |
| Drop-off to depot | `96 × 0.921292` | `88.44` |

Engine total: `255.90`.
Final total: `255.90`.
Explanation: standard three-leg quote, one horse, with no shared portion or override.

### P2: standard two-horse quote

Use the same distances as P1 and apply the two-horse loaded rate.

| Leg | Calculation | Amount |
| --- | ---: | ---: |
| Depot to pickup | `10 × 0.921292` | `9.21` |
| Pickup to drop-off | `90 × 2.051329` | `184.62` |
| Drop-off to depot | `96 × 0.921292` | `88.44` |

Engine total: `282.27`.
Final total: `282.27`.
Explanation: standard three-leg quote with the provisional two-horse multiplier.

### P3: line-item rounding boundary

Use a one-horse quote with rounded distances of `5`, `10`, and `12` miles.

| Leg | Unrounded amount | Rounded amount |
| --- | ---: | ---: |
| Depot to pickup | `4.606460` | `4.61` |
| Pickup to drop-off | `17.582820` | `17.58` |
| Drop-off to depot | `11.055504` | `11.06` |

Engine total: `33.25` from the rounded line items.
A final-only rounding method produces `33.24` from the raw sum `33.244784` and is not approved.
Final total: `33.25`.

### P4: short-journey boundary

Use one horse and compare total three-leg distances of `99` and `100` miles.

| Distances | Rounded line items | Engine total | Short-journey adjustment |
| --- | --- | ---: | --- |
| `10`, `50`, `39` | `9.21`, `87.91`, `35.93` | `133.05` | None |
| `10`, `50`, `40` | `9.21`, `87.91`, `36.85` | `133.97` | None |

Each final total equals its engine total unless an authorised override is recorded.

### P5: unstructured extra

Start from P1 with engine total `255.90`.
Assume that an operator agrees a `25.00` waiting-time charge.

The provisional policy has no structured extra calculation.
Record an authorised commercial-adjustment override to `280.90`.
The explanation states `Waiting time adds 25.00`.

Engine total: `255.90`.
Final total: `280.90`.

### P6: partial shared allocation

Use one horse, shared-load percentage `0.75`, and the common rates.

| Leg | Full calculation | Shared calculation | Leg amount |
| --- | ---: | ---: | ---: |
| Depot to pickup | `6 × 0.921292 = 5.53` | `4 × 0.921292 ÷ 2 = 1.84` | `7.37` |
| Pickup to drop-off | `60 × 1.758282 = 105.50` | `30 × 1.758282 × 0.75 = 39.56` | `145.06` |
| Drop-off to depot | `96 × 0.921292 = 88.44` | `0.00` | `88.44` |

Full-charge miles: `162`.
Shared-charge miles: `34`.
Engine total: `240.87`.
Final total: `240.87`.
The explanation identifies the overlap and each allocation rule.

### P7: corrected fuel record

Start from P1, then create a corrected fuel record with price `1.6000` per litre.
The corrected six-decimal rates are `0.380182`, `0.935738`, `1.186634`, and `1.779951` for one loaded horse.

| Leg | Calculation | Amount |
| --- | ---: | ---: |
| Depot to pickup | `10 × 0.935738` | `9.36` |
| Pickup to drop-off | `90 × 1.779951` | `160.20` |
| Drop-off to depot | `96 × 0.935738` | `89.83` |

The original revision retains engine and final totals of `255.90`.
The corrected draft has engine and final totals of `259.39`.

### P8: final-total override

Start from P1 with engine total `255.90`.
An authorised operator records a customer-agreement override to `250.00` with an explanation.

Engine total: `255.90`.
Final total: `250.00`.
Any later pricing-input change requires explicit review and reapplication of the override.

## User Decision Evidence

On 2026-09-12, the user confirmed the one-horse and two-horse multiplier interpretation.
The user selected the recommended rounding rule.
The user then authorised recommended provisional rules until client feedback is available.

The user approved revision `r1` on 2026-09-13.
The user approved the exact two-decimal manual-entry amendment as revision `r2` on 2026-09-13.
The workflow index records both approval identities.

## Workbook Evidence

The recorded workbook SHA-256 is `f05b3fd83acb606b5c35589bb500f03a003186a1b4277fdfc18870c4db736110`.
The workbook is not stored in the repository and was not available during this drafting pass.

Earlier extraction records the following evidence:

- `Rate Calc!L7:L12` contains fuel, vehicle, labour, and speed inputs.
- `Rate Calc!F3` uses `1.5`, which the user provisionally identified with one-horse loaded pricing.
- `Rate Calc!F8` uses `1.75`, which the user provisionally selected for two-horse loaded pricing.
- `Rate Calc!L14` contains an inconsistent under-100-mile factor of `1.4`.
- `Individual Quotes!F5:M5` exposes a penny difference between rounding methods.
- `Shared Load calculator!G4:G5` contains shared-load arithmetic.

These observations inform the policy but do not override explicit user decisions.

## Repository Observations

The current calculator derives six-decimal rates, rounds route legs to whole miles, rounds each leg to pennies, then sums those amounts.
It does not receive horse count from the pricing engine.
The stored two-horse multiplier does not affect standard quote calculations.

The current calculator applies no short-journey rule and returns an empty extras list.
Its shared-allocation path uses a configurable loaded percentage and an unloaded divisor.
The current default shared-load percentage is `0.75`.

The route-first exception path restricts final overrides to authorised staff.
It creates a revision, stores actor and reason evidence, and retains the engine total separately.
Pricing revisions retain assigned fuel and rate records.

These facts describe current implementation only.
The post-approval comparison must identify every mismatch before any implementation proposal.

## Current-Behaviour Comparison

Inspection and direct calculator probes used the working tree at commit `c380baa0a95e9a2e81ff21182c0b492ce53f400a` plus its uncommitted files.
The comparison describes evidence after policy approval and does not authorise corrections.

| Policy area | Current repository behaviour | Comparison |
| --- | --- | --- |
| Standard structure | The calculator requires the approved three legs and rate types. | Aligned. |
| Base rates | The calculator applies the approved fuel and rate formula with six-decimal rate rounding. | Aligned before horse-count adjustment. |
| Horse count | Revisions store horse count, but the pricing engine does not pass it to the calculator. Counts through 255 can enter the workflow. | Mismatch. One-horse, two-horse, and unsupported counts currently receive the same loaded rate. |
| Monetary rounding | Standard legs use whole miles, six-decimal rates, penny-rounded legs, then a sum of leg amounts. | Aligned. |
| Short journeys | The calculator applies no under-100-mile multiplier. | Aligned with the provisional no-adjustment rule. |
| Extras | Standard calculations return an empty extras list. A controlled final override can represent a manual extra. | Aligned with the provisional no-structured-extra rule. |
| Shared loads | Unloaded portions use a divisor. Loaded shared portions use the configured percentage. | Partial. The loaded amount lacks the required horse-count multiplier. |
| Fuel evidence | A revision retains its assigned fuel record. Otherwise, pricing selects the active record and then stores it. | Partial. Existing revision evidence remains stable, but no workflow explicitly selects a corrected fuel record for a replacement revision. |
| Controlled final overrides | The route-first exception path restricts overrides, requires a category and explanation, creates a revision, and records audit evidence. Route-leg repricing duplicates the prior final total and override evidence. | Partial. The controlled evidence exists, but a pricing-input change can retain the override without explicit review and reapplication. |
| Generic workspace overrides | The older workspace request accepts a manual total from any authenticated user without a controlled category. Existing values can carry into another revision. | Mismatch for authority, categories, audit evidence, and explicit reapplication. |

The direct probe produced these totals:

| Example | Approved policy total | Current calculator total | Cause |
| --- | ---: | ---: | --- |
| P1, one horse | `255.90` | `203.15` | Missing `1.5` loaded-rate multiplier. |
| P2, two horses | `282.27` | `203.15` | Missing `1.75` loaded-rate multiplier. |
| P6, shared allocation | `240.87` | `192.51` | Shared loaded miles use the unadjusted loaded rate. |

The probe also confirmed that one-horse and two-horse inputs currently produce the same standard total.
The calculator returned an empty extras list.

## Open Client Confirmation

Client feedback must confirm or amend the provisional horse multipliers, absence of a short-journey factor, extras treatment, and shared-load default.
It must also confirm final-override practice and fuel-correction expectations.

Until that feedback produces an approved amendment, this candidate becomes authoritative only after exact user approval.
