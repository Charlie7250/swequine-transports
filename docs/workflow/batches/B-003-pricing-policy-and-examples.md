# Current Batch

## State

- Identifier: `B-003-pricing-policy-and-examples`.
- State: accepted.
- Scope revision: `r1`.
- Scope SHA-256: `d63850337996e2e11900e302812b960b9d8b1b58ebd7e4a5c29fa498cd21d195`.
- Outcome revision: `r1`.
- Outcome SHA-256: `75cd3bc510f5d546991f70900be325f88dc77d708da462603e8d0614596bbf4c`.
- Proposed: 2026-09-12.
- Approved: 2026-09-12. Documentation work began after recording the approval below.
- Accepted: 2026-09-13.
- Blocker: none.

## Batch Scope

### Objective

Establish an approved transport pricing policy and a representative set of exact examples for the private minimum viable product.
Separate business decisions from workbook evidence and current repository behaviour.
Produce no application change while pricing policy remains unsettled.

### Why This Batch Now

[Approved intent](../intent.md#private-minimum-viable-product) makes transparent automated pricing part of the private minimum viable product.
Neither operator uses the portal, so existing behaviour has no operator-validation authority.

The [accepted readiness assessment](../derived/private-mvp-readiness.md#pricing-evidence-and-decisions) identifies unresolved horse-count, rounding, short-journey, shared-load, fuel, and override rules.
The [delivery plan](../derived/delivery-plan.md#candidate-work-packages) places agreed pricing policy directly after the accepted local test baseline.

Fresh verification runs the existing 180-test suite successfully.
Current code still ignores horse count, rounds miles before pricing, rounds each leg, leaves extras empty, and applies no short-journey rule.
Shared loads use configurable allocation rules, while final-total exceptions retain the engine total.
These are repository observations, not approved business rules.

### Included Work

- Gather focused user decisions, informed by the operators, for the pricing topics listed below.
- Define supported horse counts and the exact pricing treatment for each supported count.
- Define mileage, rate, line-item, engine-total, and final-total rounding stages.
- Decide whether a short-journey adjustment exists, including its trigger distance and calculation basis.
- Define allowed extras, their calculation method, and their position within the engine total.
- Define shared-load charging for loaded and unloaded overlap, defaults, permitted adjustments, and required explanations.
- Define final-total override authority, required evidence, effect on reporting, and treatment after repricing or reissue.
- Define how fuel-price corrections affect draft, issued, booked, and historical quote revisions.
- Create synthetic examples that cover every approved rule and important boundary.
- Record exact inputs, intermediate calculations, engine totals, final totals, and expected explanations for each example.
- Compare the approved policy and examples with current implementation and tests without changing either.
- Create a canonical pricing-policy record and update workflow navigation after explicit content approval.
- Refresh only the affected pricing sections in the readiness assessment and delivery plan.
- Record review, verification, limitations, decisions, and follow-ups in this batch.

### Explicitly Excluded Work

No application source, test, migration, configuration, dependency, user-interface, or seed-data change.
No activation of new rates, fuel prices, extras, multipliers, or production records.
No operator trial, live quote, customer-data import, workbook import, or retrospective repricing.
No loading-practice policy beyond identifying its separate boundary from transport pricing.
No routing, visual overhaul, email ingestion, commercial portal, public network, hosting, deployment, or release work.
No decision about production rate values unless the representative examples require clearly labelled synthetic constants.

### Expected Files or Components

- `docs/workflow/pricing-policy.md`: new canonical policy and representative examples, initially proposed and later approved by exact identity.
- `docs/workflow/index.md`: navigation and the canonical approval ledger after explicit pricing-policy approval.
- `docs/workflow/derived/private-mvp-readiness.md`: refreshed pricing-gap observations and implementation comparison.
- `docs/workflow/derived/delivery-plan.md`: mark the policy package by its factual outcome and retain later packages as proposals.
- `docs/workflow/current-batch.md`: approved scope, factual execution evidence, outcome, and acceptance history.

No other file is expected to change.
After acceptance, archive this record as `docs/workflow/batches/B-003-pricing-policy-and-examples.md` and return the current batch to idle.

### Dependencies

Canonical intent revision `r1` and accepted batches B-001 and B-002 must retain their verified identities.
The user must provide or confirm pricing decisions, informed by the two operators where business practice requires their knowledge.

Workbook evidence comes from the recorded extraction of `Pipeline and Quotes.xlsx`, SHA-256 `f05b3fd83acb606b5c35589bb500f03a003186a1b4277fdfc18870c4db736110`.
The workbook file is not present in the repository or current local search results.
Any decision requiring unrecorded workbook detail pauses until the user supplies that detail or the workbook again.

Synthetic examples must use explicit fixed inputs.
Their constants do not become production rate values unless the user separately approves that meaning.

### Risks and Uncertainties

Workbook formulas conflict, so copying a formula can encode an accidental rule.
Existing documents contain authoritative-sounding historical statements that guided delivery has not approved as current pricing policy.
Current code can bias decisions because it already embodies rounding and shared-load behaviour.

Horse-count rules can interact with shared loads, extras, short journeys, and vehicle capacity.
Rounding order can change quoted totals by pennies and can compound across shared allocations.
Fuel corrections can conflict with the audit requirement that issued revisions retain their original pricing evidence.

Operator practice can include exceptions that the recorded workbook evidence does not show.
The batch must label unknown cases rather than inventing a general rule.
The substantial pre-existing working tree must remain intact.

### Acceptance Criteria

- A1: Each included pricing topic has one explicit approved rule, or an explicit decision that no automatic rule applies.
- A2: Horse-count policy states every supported count, the affected charge components, and behaviour outside the supported range.
- A3: Rounding policy states the precision and order for miles, rates, line items, engine totals, extras, shares, and final totals.
- A4: Short journeys, extras, and shared loads have exact triggers, calculations, adjustment authority, and explanation requirements.
- A5: Fuel corrections and final overrides preserve an explicit distinction between calculated evidence and customer-facing totals.
- A6: Representative examples cover standard, multi-horse, rounding-boundary, short-journey, extra, shared-load, fuel-correction, and final-override cases.
- A7: Every example records exact inputs, intermediate values, engine total, final total, and the policy clauses that it exercises.
- A8: Independent arithmetic reproduces every expected example total exactly, with disagreements resolved before acceptance.
- A9: A current-behaviour comparison identifies each aligned rule, mismatch, absent capability, and uncertain case without treating code as authority.
- A10: The canonical pricing-policy scope receives explicit user approval with revision and SHA-256 identity.
- A11: Application files, tests, configuration, dependencies, legacy documents, and unrelated uncommitted work remain unchanged.
- A12: Final review finds no unresolved Critical or Important issue under the repository severity rules.
- A13: Workflow records state evidence limits and later implementation needs without claiming pricing behaviour has changed.

### Verification Plan

Recalculate the approved canonical and archived batch digests before work begins.
Record the current commit, complete Git status, expected-file hashes, and a protected-file aggregate fingerprint.

Trace each policy statement to explicit user input, recorded workbook evidence, or a labelled synthetic assumption.
Use a calculation table that exposes every rounding and adjustment stage.
Recompute each example independently from its stated inputs without using current application output as the expected result.

Run the current calculator against compatible examples only to describe present behaviour.
Classify every difference as an observation, not a defect, until the policy is approved.

Validate local Markdown links and recalculate all protected scope and full-file identities.
Inspect the complete documentation diff and the protected-file fingerprint.
Run `composer validate --no-check-publish` and `composer run test:host` after the final documentation change.
Request final review, resolve blocking findings, then run fresh final verification.

### Expected Documentation Changes

Add one canonical pricing-policy record containing the approved rules and exact representative examples.
Update the workflow index with its navigation, revision, digest, and approval wording.
Refresh pricing-related observations in the readiness assessment and package status in the delivery plan.
Keep historical workbook descriptions as evidence and leave legacy documentation unchanged in this batch.

### Decisions Requiring User Input

The batch will ask one to three related questions per round.
Questions will cover only policy that repository evidence cannot decide.

- Supported horse counts, capacity limits, and the price treatment for each count.
- Exact rounding order and precision.
- Short-journey trigger and adjustment, or confirmation that no automatic adjustment applies.
- Allowed extras and whether each belongs inside the calculated engine total.
- Shared-load calculations for loaded and unloaded overlap, plus adjustment authority.
- Fuel-correction treatment across revision states.
- Final-total override authority, evidence, categories, and lifecycle.

Approval of this batch authorises policy discovery and documentation only.
It does not answer these questions or authorise application implementation.

## Approval Record

- Batch: `B-003-pricing-policy-and-examples`.
- Scope: `docs/workflow/current-batch.md`, `## Batch Scope` and all nested sections.
- Revision: `r1`.
- SHA-256: `d63850337996e2e11900e302812b960b9d8b1b58ebd7e4a5c29fa498cd21d195`.
- Approval date: 2026-09-12.
- Approval state: approved.
- Execution route: direct documentation work.
- User approval wording:

> approved

This approves policy discovery and documentation within scope `r1`.
It does not settle pricing decisions or authorise application implementation.

## Repository Baseline

Inspection date: 2026-09-12.
Base commit: `c380baa0a95e9a2e81ff21182c0b492ce53f400a`.
The working tree has 32 modified tracked files and 135 untracked files.

The accepted B-002 target hashes and protected 218-file fingerprint still match their recorded values.
Fresh `composer run test:host` exited 0 with 180 tests, 180 passes, 1,279 assertions, and 5,467 milliseconds.
Fresh `composer validate --no-check-publish` exited 0.
The default PHP process still exposes only PostgreSQL through PHP Data Objects and emits the recorded duplicate-extension warnings.

No repository file changed during recovery and evidence checks before this proposal.

## Execution Decisions

On 2026-09-12, the user directed:

> Recommended rounding

The approved rounding recommendation uses whole route miles, six-decimal rates, two-decimal line items, then a sum of rounded line items.

The user then directed:

> recommended for all until I can get feedback from client

This is a standing instruction for provisional pricing decisions within this batch.
Use the evidence-backed recommendation until client feedback supports an approved amendment.
It does not authorise application changes or turn repository behaviour into business authority.

The user confirmed these provisional horse-count rules:

- One-horse loaded legs use the base loaded rate multiplied by `1.5`.
- Two-horse loaded legs use the base loaded rate multiplied by `1.75`.
- Unloaded legs remain unchanged.
- Counts above two require manual review.
- The multipliers remain configurable and require later client confirmation.

On 2026-09-13, the user approved the exact pricing-policy revision `r2` amendment.
Its canonical SHA-256 is `bb775278bc0d6ebdc1a6015b539e3694d66651088e782b3eab1648a17197a230`.
The user replied:

> approved

The amendment requires exactly two decimal places for manual extra effects and replacement totals.
It rejects greater precision and fixes the calculation order around manual overrides.
This decision needs neither another standing instruction nor a reusable skill.

## Implementation Outcome

Documentation work began on 2026-09-12 against approved scope `r1`.
The user approved canonical pricing-policy revision `r1` on 2026-09-13.
The user approved revision `r2` on 2026-09-13 to define exact precision and ordering for manual monetary entries.

The new policy defines the standard quote structure, rate construction, horse-count treatment, rounding, short journeys, extras, shared loads, fuel corrections, and final overrides.
It includes eight synthetic examples with exact inputs, intermediate calculations, engine totals, final totals, and explanation requirements.

The current-behaviour comparison identifies five confirmed application gaps:

- Standard pricing ignores the approved one-horse and two-horse loaded-rate multipliers.
- Shared loaded pricing uses the unadjusted loaded rate.
- The older generic workspace override path lacks the approved authority, category, audit, and reapplication controls.
- Route-leg repricing can retain a controlled final override without explicit review and reapplication.
- No workflow explicitly selects a corrected fuel record for a replacement quote revision.

The readiness assessment and delivery plan now use the approved pricing policy and accepted local test evidence.
No application file, test, configuration, dependency, seed data, legacy document, runtime setting, service, or customer record changed.
No scope deviation occurred.

## Acceptance Assessment

| Criterion | Assessment | Evidence or remaining gate |
| --- | --- | --- |
| A1 | Satisfied | Every included topic has an explicit rule, including no automatic short-journey or structured-extra rule. |
| A2 | Satisfied | One and two horses have exact loaded-rate multipliers. Counts above two require manual review. |
| A3 | Satisfied | The policy defines every rounding stage and requires exact two-decimal manual monetary entries. |
| A4 | Satisfied | Short journeys, extras, and shared loads have exact treatment, authority, and explanation requirements. |
| A5 | Satisfied | Fuel corrections create records and revisions. Final overrides retain the engine total and audit evidence. |
| A6 | Satisfied | Examples P1 through P8 cover every required case. |
| A7 | Satisfied | Each example records its inputs, intermediate arithmetic, totals, and policy purpose. |
| A8 | Satisfied | Independent decimal arithmetic reproduced every displayed total exactly. |
| A9 | Satisfied | The comparison records aligned rules, partial alignment, confirmed mismatches, and current direct-probe totals. |
| A10 | Satisfied | The user approved policy revisions `r1` and `r2`. The index records both exact identities. |
| A11 | Satisfied | Final checks preserved all protected application files and the accepted B-002 target identities. |
| A12 | Satisfied | Review round three approved the corrected records with no findings. |
| A13 | Satisfied | Every record states that application pricing has not changed and needs a separate approved batch. |

The result now awaits explicit user acceptance.

## Verification Evidence

All checks use `C:/Users/charl/code/sweq-transports`.

| Check | Observed result |
| --- | --- |
| Canonical pricing-policy identity | Revision `r1` retained `fb94a7040643ea42a7d5aa69b0e76555dd7bfe1c992eb59c4c63901714fb3378`. Approved revision `r2` recalculated as `bb775278bc0d6ebdc1a6015b539e3694d66651088e782b3eab1648a17197a230`. |
| B-003 scope identity | Revision `r1` retained `d63850337996e2e11900e302812b960b9d8b1b58ebd7e4a5c29fa498cd21d195`. |
| Independent example arithmetic | P1 through P8 totals reproduced exactly with decimal half-up rounding. |
| Current calculator probe | P1 and P2 both returned `203.15`. P6 returned `192.51`. The extras list was empty. |
| Protected application content | The 218-file aggregate retained `e03f7a27d0229aeb46fc765166c6d3e74b5dd64a8f2852953f637ebe3b02d972` after drafting. |
| Documentation structure | Eight examples are present. All 127 local workflow links resolve, including their heading anchors. No prohibited dash characters or trailing whitespace appeared. |
| Historical identities | Intent `r1`, B-001 scope and outcome `r1`, and B-002 scope and outcome `r1` retain their recorded digests after reversing archive link substitutions. |
| Derived-document identities | The four derived full-file digests match the workflow index. |
| B-002 target files | `composer.json`, `README.md`, and `docs/local-development.md` retain their accepted post-batch digests. |
| Composer validation | `composer validate --no-check-publish` exited 0 and reported that `composer.json` is valid. Existing duplicate-extension warnings remain. |
| Complete test suite | `composer run test:host` exited 0 with 180 tests passing and 1,279 assertions in 38,010 milliseconds. |
| Independent review | Round one found three Important issues and one Minor issue. Round two found one Important identity issue. Round three approved all corrections with no findings. |
| Working-tree preservation | HEAD remains `c380baa0a95e9a2e81ff21182c0b492ce53f400a`, with 32 tracked modifications and 136 untracked files. |

## Remaining Work and Missed Cases

Only explicit user acceptance remains within this batch.
Client feedback has not confirmed the provisional multipliers, short-journey choice, extras treatment, shared-load default, fuel corrections, or final-override practice.

The batch does not implement its approved policy.
No browser walkthrough, PostgreSQL run, live routing call, operator trial, deployment, or release occurred.
Unknown business cases and application edge cases remain possible beyond the recorded evidence.

## Debt and Follow-Ups

This batch introduces no known application debt.
It confirms pre-existing pricing gaps for a later implementation proposal.
That proposal must include corrected-fuel revision selection and explicit override reapplication after pricing-input changes.

Legacy calculation and domain documents retain historical or authoritative-sounding statements that can conflict with the canonical policy.
They remain unchanged under this batch's explicit exclusion.

Client feedback requires a canonical pricing-policy amendment before it changes the governing rule.
Operator-trial, loading-practice, routing-fitness, visual, hosting, support, outage, and recovery decisions remain deferred.

## Documentation Changes

- Added `docs/workflow/pricing-policy.md` with approved canonical revisions `r1` and `r2`, plus non-canonical evidence sections.
- Updated `docs/workflow/index.md` with navigation, approval identity, and derived-document identities.
- Refreshed `docs/workflow/derived/private-mvp-readiness.md` to revision `r2`.
- Refreshed `docs/workflow/derived/delivery-plan.md` to revision `r2`.
- Updated this batch with approval, decisions, implementation evidence, review results, verification, and the acceptance assessment.

The build and release plan, expansion architecture, canonical intent, accepted archives, and legacy documents remain unchanged.

## Acceptance Record

Accepted on 2026-09-13.

- Batch: `B-003-pricing-policy-and-examples`.
- Approved scope revision: `r1`.
- Approved scope SHA-256: `d63850337996e2e11900e302812b960b9d8b1b58ebd7e4a5c29fa498cd21d195`.
- Accepted outcome revision: `r1`.
- Accepted outcome SHA-256: `75cd3bc510f5d546991f70900be325f88dc77d708da462603e8d0614596bbf4c`.
- Implementation state: the preserved working tree at `c380baa0a95e9a2e81ff21182c0b492ce53f400a`, with the recorded B-002 target and protected-file identities.
- User acceptance wording:

> accepted

This acceptance covers the presented B-003 outcome only.
It grants no approval for application implementation, deployment, release, or another batch.


## Archive Link Mapping

Archive-relative link substitutions preserve the approved scope and accepted outcome digest basis.
Reverse these substitutions before verifying historical identities.

```json
{
  "intent.md#private-minimum-viable-product": "../intent.md#private-minimum-viable-product",
  "derived/private-mvp-readiness.md#pricing-evidence-and-decisions": "../derived/private-mvp-readiness.md#pricing-evidence-and-decisions",
  "derived/delivery-plan.md#candidate-work-packages": "../derived/delivery-plan.md#candidate-work-packages"
}
```

