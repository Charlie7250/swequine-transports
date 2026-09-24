# Current Batch

## State

- Identifier: `B-004-pricing-alignment`.
- State: accepted.
- Scope revision: `r1`.
- Scope SHA-256: `4e9b930453776b1e14f085d3fd76ff1a21e6817ac205b628383547196c1f87c9`.
- Proposed: 2026-09-13.
- Repository base: `c380baa0a95e9a2e81ff21182c0b492ce53f400a` with the preserved uncommitted implementation.
- Approved: 2026-09-13.
- Implementation started: 2026-09-13.
- Accepted: 2026-09-14.
- Blocker: none.

## Batch Scope

### Objective

Align transport quote calculation and revision workflows with approved [pricing policy revision r2](../pricing-policy.md#canonical-content).
Cover horse-count rates, shared loaded pricing, unsupported counts, both override paths, and corrected-fuel revision selection.
Preserve calculated evidence, historical revisions, configurable provisional values, and examples P1 through P8.

### Why This Batch Now

The [approved intent](../intent.md#private-minimum-viable-product) requires transparent automated loaded and unloaded pricing for the private minimum viable product.
The [accepted B-003 outcome](B-003-pricing-policy-and-examples.md#implementation-outcome) identifies five confirmed pricing and revision-control gaps.
The [delivery plan](../derived/delivery-plan.md#candidate-work-packages) places confirmed pricing corrections before operator-trial preparation.

Fresh inspection confirms that horse count never reaches the calculator.
Shared loaded miles therefore use the unadjusted loaded rate.
Counts through 255 reach automatic pricing, although counts above two have no approved multiplier.

The legacy workspace accepts unaudited manual totals from any authenticated staff member.
The controlled route-first path records authorised overrides, but later route repricing retains them automatically.
No workflow creates a replacement revision with an explicitly selected corrected fuel record.

Fresh `composer run test:host` execution passes 180 tests and 1,279 assertions.
Those tests preserve the current mismatches, so new behaviour needs observed failing tests before production changes.

### Included Work

- Add configurable one-horse multiplier storage beside the existing two-horse multiplier.
- Use approved provisional defaults of `1.500000` and `1.750000` for newly created settings and seeded environments.
- Keep existing rate-setting rows and their historical values unchanged.
- Require both multipliers when staff create a new rate setting.
- Pass horse count and both stored multipliers into standard and shared transport calculations.
- Apply the selected multiplier only to the base loaded rate.
- Retain the unloaded rate for every supported count.
- Record the horse count, applied multiplier, base loaded rate, and adjusted loaded rate in calculation evidence.
- Preserve the approved whole-mile, six-decimal rate, and penny line-item rounding order.
- Use the horse-adjusted loaded rate for full and shared loaded portions.
- Apply the stored shared-load percentage only after the horse-count adjustment.
- Retain equal division for shared unloaded portions and retain the existing explanation evidence.
- Keep enquiry capture available for counts above two, but block automatic quote creation with a manual-review message.
- Reject unsupported horse counts before any automatic price or partial revision persists.
- Bring the legacy workspace override under the controlled authority, category, explanation, precision, actor, time, and revision requirements.
- Treat a submitted compliant workspace override as explicit reapplication after workspace inputs change.
- Keep ordinary workspace edits available to staff without exception authority when they submit no override.
- Make general repricing clear any prior final-total override unless an authorised action explicitly reapplies it.
- Prevent route-leg changes and shared-allocation changes from silently retaining an earlier final-total override.
- Keep the prior revision and its override audit unchanged as historical evidence.
- Require positive override amounts written with exactly two decimal places on both override paths.
- Add corrected-fuel selection for the current draft revision from recorded weekly fuel history.
- Create a new revision for that selection, store the selected fuel record, reprice it, and clear any prior override.
- Leave issued, booked, completed, and historical revisions unchanged.
- Add behaviour tests that exercise policy examples P1 through P8 across calculator and workflow boundaries.
- Update affected workflow evidence after implementation, review, and verification.

### Explicitly Excluded Work

No change to canonical pricing policy, its approved examples, or its provisional status.
No client confirmation of provisional multipliers, shared percentages, extras, or other pricing rules.
No structured extra, automatic short-journey adjustment, loading-practice pricing, or vehicle-capacity decision.
No per-allocation shared percentage control beyond the stored rate-setting default.
No incomplete-enquiry workflow or automatic pricing for more than two horses.
No mutation, repricing, or migration of issued, booked, completed, or historical quote revisions.
No automatic rewrite or activation of existing rate-setting records.
No automatic activation of corrected fuel records and no change to weekly-fuel administration authority.
No live customer quote, operator trial, workbook import, production-data migration, deployment, or release.
No routing-provider, transport-day, email-ingestion, visual-overhaul, hosting, or commercial-platform work.
No dependency addition, broad refactor, or unrelated clean-up.
No reconciliation of legacy pricing documents outside the guided-delivery records listed below.

### Expected Files or Components

- `app/Services/Pricing/DeterministicPricingCalculator.php`: horse-adjusted rates and explicit override handling.
- `app/Services/Pricing/JobRevisionPricingEngine.php`: pricing inputs, unsupported-count checks, and non-carrying repricing.
- `app/Services/Quotes/QuoteWorkspaceManager.php`: controlled workspace overrides, fuel-selection revisions, and audit preservation.
- `app/Http/Controllers/JobRevisionController.php`: actor-aware workspace updates and corrected-fuel selection.
- `app/Http/Requests/SaveQuoteWorkspaceRequest.php`: conditional override authority, approved categories, and exact precision.
- `app/Http/Requests/SaveFinalTotalOverrideRequest.php`: exact two-decimal validation.
- One focused request class for corrected-fuel selection.
- `app/Models/RateSetting.php`: the added multiplier cast.
- One new rate-setting migration that leaves existing rows intact.
- `database/seeders/DatabaseSeeder.php`: approved provisional defaults for fresh seeded environments.
- `resources/views/admin/rate-settings/index.blade.php`: both configurable multiplier fields and current-setting display.
- `resources/views/job-revisions/_workspace-form.blade.php`: compliant legacy override inputs.
- `resources/views/job-revisions/show.blade.php`: corrected-fuel selection and calculation evidence.
- `resources/views/job-revisions/exceptions.blade.php`: controlled override precision guidance when needed.
- `routes/web.php`: one authenticated corrected-fuel revision action.
- Existing pricing, quote-workspace, exception, shared-run, admin, schema, and issued-output tests.
- Focused new tests only where the existing test files cannot express one behaviour clearly.
- `docs/workflow/current-batch.md`: scope, implementation outcome, evidence, and acceptance assessment.
- `docs/workflow/derived/private-mvp-readiness.md`: refreshed pricing implementation evidence.
- `docs/workflow/derived/delivery-plan.md`: factual package status after accepted implementation.

The final implementation can touch fewer files when tests prove that a listed file needs no change.
Any additional component requires an in-scope necessity or a revised batch approval.

### Dependencies

Canonical intent revision `r1` and pricing policy revision `r2` must retain their approved identities.
Accepted batches B-001 through B-003 must retain their verified scope and outcome identities.
The preserved working tree at `c380baa0a95e9a2e81ff21182c0b492ce53f400a` remains the implementation baseline.

The local PHP runtime and installed dependencies must continue supporting `composer run test:host`.
No new dependency is required.

Existing rate settings can contain the historical two-horse value `1.150000`.
This batch will not rewrite that history.
A later environment action must create and activate a new setting with approved values before operator use.
That operational action requires separate authority and is not an implementation acceptance criterion.

### Risks and Uncertainties

The repository contains 32 modified tracked files and 137 untracked files, including the accepted B-003 archive.
Most relevant implementation files are untracked, so preservation checks must cover file content rather than Git diff alone.

Horse-count adjustment affects standard quotes, shared allocations, displayed explanations, tests, and issued evidence.
One missed caller can retain the former loaded rate.

General repricing currently infers override intent from stored totals.
Changing that contract can erase an override unless every explicit override path passes its intent deliberately.
Historical revisions and audits must remain untouched while replacement revisions change.

The legacy workspace combines pricing-input changes and manual totals in one request.
Conditional authority and audit creation must remain atomic with revision creation and calculation.

Corrected-fuel selection must distinguish a selected historical record from the currently active default.
The action must not mutate or activate fuel history.

SQLite tests verify application behaviour but do not establish PostgreSQL migration compatibility.
No PostgreSQL service is available as proven verification evidence in this batch proposal.

### Acceptance Criteria

- A1: One-horse and two-horse standard examples produce policy totals `255.90` and `282.27` from configurable `1.5` and `1.75` multipliers.
- A2: Calculation evidence separates the base loaded rate from the horse-adjusted loaded rate and records the selected multiplier.
- A3: Unloaded rates remain unchanged for one and two horses.
- A4: Counts above two remain recordable as enquiries but cannot create an automatically priced revision.
- A5: Unsupported-count failure leaves no partial job, revision, route-leg, shared-allocation, or exception-audit change.
- A6: Shared example P6 uses the horse-adjusted loaded rate and produces engine and final totals of `240.87`.
- A7: Shared unloaded portions still divide equally and shared loaded portions still use the stored percentage.
- A8: Standard and shared calculations preserve the approved rounding order, including boundary examples P3 and P4.
- A9: Newly created rate settings require configurable one-horse and two-horse multipliers.
- A10: Fresh seeded environments use provisional multiplier values `1.500000` and `1.750000`.
- A11: Existing stored rate-setting rows retain their identifiers and existing values after the migration.
- A12: Both override paths reject unauthorised staff, unapproved categories, missing explanations, non-positive amounts, and non-two-decimal amounts.
- A13: Each accepted override creates a new draft revision with separate engine and final totals plus actor, category, explanation, value, and time evidence.
- A14: A compliant legacy workspace submission can explicitly reapply an override while changing pricing inputs.
- A15: General repricing never carries a prior final-total override without an explicit authorised reapplication.
- A16: Route-leg and shared-allocation changes preserve the prior revision but clear its override from the recalculated revision.
- A17: Corrected-fuel selection creates a new current draft using the chosen stored fuel record and reproduces P7 total `259.39`.
- A18: Corrected-fuel selection leaves the previous revision, active fuel record, and all issued or historical evidence unchanged.
- A19: Override example P8 retains engine total `255.90` and uses final total `250.00` with the required audit evidence.
- A20: Examples P1 through P8 have automated coverage at the appropriate calculator or workflow boundary.
- A21: Existing non-pricing behaviour and all unrelated working-tree content remain unchanged.
- A22: Final review reports no unresolved Critical or Important findings under repository severity rules.
- A23: Fresh final verification passes the complete host suite and Composer validation after the last implementation change.
- A24: Updated workflow evidence distinguishes implemented behaviour from unverified operator, PostgreSQL, deployment, and release status.

### Verification Plan

Before implementation, recalculate canonical and accepted-batch digests.
Record HEAD, complete Git status, owned-file hashes, and an aggregate fingerprint for protected content.

Use test-driven development for each observable behaviour.
Run each new test first and retain its expected failing result before changing production code.
Implement the smallest change that makes each test pass.

Exercise calculator examples P1 through P8 with fixed decimal inputs from the approved policy.
Use feature tests for persistence, authority, revision history, fuel selection, shared allocations, and rollback behaviour.
Use migration tests to verify fresh schema and preservation of existing rate-setting values.

Run focused tests during implementation.
Run `composer run test:host` after the final code change.
Run `composer validate --no-check-publish` after the final documentation change.
Run the project formatter in check or changed-file mode without restyling unrelated files.

Inspect the complete owned diff and every untracked owned file.
Request final code review against this exact batch scope.
Resolve all Critical and Important findings before continuing.
Run fresh final verification after the last review correction.

Recalculate the protected approval identities and working-tree fingerprints.
Record command outputs, exit codes, test counts, assertion counts, and verification limits in this batch.

### Expected Documentation Changes

Append factual implementation, review, verification, limitation, and criterion evidence to this batch record.
Refresh pricing observations in `docs/workflow/derived/private-mvp-readiness.md` after verified implementation.
Refresh the factual package status in `docs/workflow/derived/delivery-plan.md` after acceptance.

Do not amend canonical intent or pricing policy unless implementation exposes an unexpected material contradiction.
If that occurs, stop and propose the canonical amendment before dependent work.
Leave legacy pricing documents unchanged within this batch.

### Decisions Requiring User Input

No unresolved business decision blocks this scope.
The approved pricing policy determines the target behaviour.

On 2026-09-13, the user chose to retain the legacy workspace override.
The batch will bring it under the controlled authority, category, explanation, precision, revision, and audit rules.
This one-off design decision warrants neither a standing instruction nor a reusable skill.

Batch approval will authorise implementation of this exact scope only.
It will not authorise deployment, release, live configuration activation, operator trials, or subsequent batches.

## Approval Record

- Batch: `B-004-pricing-alignment`.
- Scope: `docs/workflow/current-batch.md`, `## Batch Scope` and all nested sections.
- Revision: `r1`.
- SHA-256: `4e9b930453776b1e14f085d3fd76ff1a21e6817ac205b628383547196c1f87c9`.
- Approval date: 2026-09-13.
- Approval state: approved.
- Execution route: direct implementation with test-driven development.
- User approval wording:

> approved

This approval authorises implementation within scope revision `r1`.
It does not authorise deployment, release, live configuration activation, operator trials, or another batch.

## Implementation Outcome

The implementation candidate aligns standard and shared transport pricing with pricing policy revision `r2`.
It applies configurable `1.500000` and `1.750000` multipliers to the base loaded rate.
It retains the unloaded rate for both supported counts.

Calculation evidence now records the horse count, selected multiplier, base loaded rate, and adjusted loaded rate.
Shared loaded portions apply the stored percentage after the horse adjustment.
Shared unloaded portions retain equal division.

Transport enquiries still accept counts above two.
Automatic quote creation and legacy workspace pricing reject those counts with the approved manual-review message.
The route-first transaction leaves no partial quote records after this rejection.

New rate settings require both configurable multipliers.
Fresh seeded environments use `1.500000` and `1.750000`.
The migration adds a nullable one-horse field and does not rewrite existing rows or activate a replacement setting.

The controlled and legacy override paths now share authority, category, explanation, precision, revision, and audit requirements.
Accepted overrides keep engine and final totals separate.
Their audits record the actor, category, explanation, calculated value, replacement value, and time.

General repricing clears stored override intent unless an authorised action passes it explicitly.
Route and shared-allocation changes preserve the overridden revision and price a new unoverridden revision.
Issue-time recalculation retains an audited override because issuing changes no pricing input.

The current draft can select any recorded weekly fuel entry for a corrected replacement revision.
That action clears an earlier override and retains the previous revision.
It does not alter the active fuel entry, issued revision marker, or issued evidence.

No dependency, deployment configuration, live setting, provider integration, or release state changed.

## Test-Driven Development Evidence

Observed RED runs covered each new behaviour before its production change.

- Standard horse pricing first returned `203.15` and lacked base loaded-rate evidence.
- Shared P6 pricing first returned `192.51` instead of `240.87`.
- The schema first lacked `rate_settings.one_horse_multiplier`.
- Engine pricing first rejected the missing horse-count input.
- Automatic pricing first accepted unsupported count `3` when the guard was temporarily disabled.
- General repricing first retained a stored `215.00` override.
- The controlled path first lost its explicit `185.00` override after that contract changed.
- Both override requests first accepted one-decimal amounts.
- Route repricing first copied the earlier final-total audit.
- The legacy path first allowed unauthorised overrides and lacked matching audit evidence.
- Corrected-fuel selection first returned HTTP `404` because no action existed.
- Shared allocation first repriced the overridden revision in place.
- Legacy workspace pricing first passed count `3` to the engine.
- Legacy reapplication first audited `255.90` instead of its new `217.77` engine total.

Each focused GREEN run passed after the smallest corresponding implementation change.
The host suite before review passed 201 tests with 1,401 assertions.

## Review Corrections

Review round one found three Important evidence gaps and no Critical issue.

- A migration upgrade-path test now preserves an existing row's identifier, name, historical multiplier, effective date, and null new field.
- A manual-fallback test now exercises three audit writes before unsupported pricing triggers the transaction rollback.
- The rollback leaves the enquiry unchanged and retains zero jobs, revisions, route legs, shared allocations, and audits.
- The pre-implementation workspace snapshot and current content now support an exact owned and protected path comparison.

The two focused correction tests pass with 5 and 11 assertions.
They add coverage for existing implementation behaviour, so neither correction changes production code.
The complete host suite after these corrections passes 203 tests with 1,417 assertions.

## Working-Tree Preservation Evidence

Git tree `5e9475e20474e18a2d3ca1333dcaaf0beb1ed2f3` captures the 232-path workspace before implementation began.
Its batch record remains approved and blocked on route selection, which dates the snapshot before the implementation state change.

The comparison identifies 35 B-004 paths.
They comprise 31 non-workflow implementation paths and four workflow records.
The two added paths are the multiplier migration and corrected-fuel request.

The 199 protected paths retain aggregate SHA-256 `0502d6ba8c23e4aaa75cda5acd22f52adbb5e92d242750b1c71345134c617a95` before and after implementation.
No protected path is added, removed, or content-different.

The aggregate hashes each sorted UTF-8 path, a null byte, and its Git-clean blob identity.
This comparison includes tracked and untracked workspace files.

The 29 baseline owned non-workflow paths have aggregate `696ee541caa0cda513e7e285a85b221f9b74766d95eb6a8bb51c043f2ffc5f2a`.
The migration and corrected-fuel request are new, bringing the current owned non-workflow set to 31 paths.
The current 31-path aggregate is `4b27806ce58815dd7c6756909f8f484a391d89254fd555539d5bf30a147e58a2`.

The accepted B-002 targets remain unchanged:

| File | Accepted and current SHA-256 |
| --- | --- |
| `composer.json` | `4bd7e538da552fb07bf6563023b00c8a4cacf87c3d5eab98547be1a81cabaf1c` |
| `README.md` | `e34af12c1fade85675e878d55bdd7255794455276ff406eec116e56ba501646c` |
| `docs/local-development.md` | `7e6bdf17989bcebb8ca672866ce00c703ca7730a0d7cf51249c81d268e52eefa` |

## Independent Review

Round one found three Important evidence gaps and no Critical issue.
The migration test, manual-fallback rollback test, and workspace fingerprint evidence resolved them.

Round two found no blocking issue and one Minor baseline-aggregate error.
The corrected record separates 29 baseline owned paths from two new paths.

Round three reviewed that correction and returned Approved.
No Critical, Important, or Minor finding remains.

## Final Verification Evidence

Final verification ran after the last implementation and review correction on 2026-09-13.

| Check | Observed result |
| --- | --- |
| Complete host suite | `composer run test:host` exited 0 with 203 tests passing and 1,417 assertions in 7,939 milliseconds. |
| Composer validation | `composer validate --no-check-publish` exited 0 and reported that `composer.json` is valid. |
| Owned PHP formatting | Pint checked every owned PHP file and exited 0. |
| Independent review | Round three returned Approved with no remaining finding. |
| Protected content | All 199 protected paths retain aggregate `0502d6ba8c23e4aaa75cda5acd22f52adbb5e92d242750b1c71345134c617a95`. |
| Approved identities | Intent `r1`, pricing policy `r2`, B-003 scope, B-003 outcome, and B-004 scope retain their recorded SHA-256 values. |
| Derived identities | Both refreshed `r3` records and both unchanged `r1` records match the index. |
| Workflow structure | All 133 local link targets resolve. No prohibited dash or trailing whitespace appears. |
| Tracked diff check | `git diff --check` produced no error. |
| Workspace state | HEAD remains `c380baa0a95e9a2e81ff21182c0b492ce53f400a`, with 34 modified tracked files and 139 untracked files. |

The local PHP process still emits the known duplicate-extension warnings.
Those warnings do not change the reported command exit codes.

## Acceptance Assessment

| Criterion | Current assessment | Evidence or remaining gate |
| --- | --- | --- |
| A1 to A3 | Satisfied | Standard calculator and engine tests reproduce P1 and P2 with separate base and adjusted rate evidence. |
| A4 and A5 | Satisfied | Enquiry acceptance and audit-writing manual fallback both preserve the enquiry and roll back every partial quote record. |
| A6 to A8 | Satisfied | Shared and rounding tests reproduce P3, P4, and P6 with the approved calculation order. |
| A9 to A11 | Satisfied | Admin, seeder, model, schema, and upgrade-path tests cover new settings and unchanged historical values. |
| A12 to A15 | Satisfied | Both override paths cover authority, categories, explanations, positive exact amounts, audits, and explicit reapplication. |
| A16 | Satisfied | Route and shared-allocation lifecycle tests preserve the earlier overridden revision. |
| A17 to A19 | Satisfied | Corrected-fuel and override tests reproduce P7 and P8 while preserving active and issued evidence. |
| A20 | Satisfied | P1 through P8 have automated coverage at calculator and workflow boundaries. |
| A21 | Satisfied | All 199 protected paths retain the same aggregate. The accepted B-002 targets also retain their exact hashes. |
| A22 | Satisfied | Review round three approved the corrected implementation and evidence with no remaining finding. |
| A23 | Satisfied | The final host suite, Composer validation, and owned PHP formatting checks all exited 0. |
| A24 | Satisfied | This record keeps PostgreSQL, browser, operator, deployment, and release evidence explicitly unverified. |

## Verification Limits

The passing suite uses in-memory SQLite through the accepted host command.
The local runtime still emits known duplicate-extension warnings.

No PostgreSQL run, browser walkthrough, live HERE check, frontend build, operator trial, deployment, restore exercise, or release occurred.
No existing rate setting was rewritten or activated.
A later authorised environment action must create and activate approved provisional values before operator use.

## Acceptance Record

Accepted on 2026-09-14.

- Batch: `B-004-pricing-alignment`.
- Approved scope revision: `r1`.
- Approved scope SHA-256: `4e9b930453776b1e14f085d3fd76ff1a21e6817ac205b628383547196c1f87c9`.
- Accepted outcome revision: `r1`.
- Accepted outcome SHA-256: `00de60879337bd95d8912188aaea62ee7c7f9d87dc78df32d6eafbb78cafafe1`.
- Implementation state: the reviewed 31-path pricing change with the recorded 199-path protected fingerprint.
- User acceptance wording:

> accepted

This acceptance covers the presented B-004 outcome only.
It grants no deployment, release, live configuration activation, operator trial, or subsequent batch approval.

## Archive Link Mapping

Archive-relative link substitutions preserve the approved scope and accepted outcome digest basis.
Reverse these substitutions before verifying historical identities.

```json
{
  "pricing-policy.md#canonical-content": "../pricing-policy.md#canonical-content",
  "intent.md#private-minimum-viable-product": "../intent.md#private-minimum-viable-product",
  "batches/B-003-pricing-policy-and-examples.md#implementation-outcome": "B-003-pricing-policy-and-examples.md#implementation-outcome",
  "derived/delivery-plan.md#candidate-work-packages": "../derived/delivery-plan.md#candidate-work-packages"
}
```
