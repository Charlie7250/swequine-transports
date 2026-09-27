# Current Batch

## State

- Identifier: `B-005-private-workflow-verification`.
- State: implementing.
- Current scope revision: `r3`.
- Current scope SHA-256: `cd5dee610e37a3746d0f29f78b49aad9bea58c458e92769632be21ff05000952`.
- Latest approved scope revision: `r2`.
- Latest approved scope SHA-256: `12e64d6413fc4655eac32e8c0a75423e4f55d43bb0d18af06f822e5f913fc869`.
- Earlier approved scope revision: `r1`.
- Earlier approved scope SHA-256: `2a9569e7f88634817c2948200540dffca58063bb33c17dde3e1ccf37f1014495`.
- Revision `r1` proposed: 2026-09-14.
- Repository base: `c380baa0a95e9a2e81ff21182c0b492ce53f400a` with the preserved uncommitted implementation.
- Proposal baseline: 34 modified tracked files and 145 untracked files.
- Revision `r1` approved: 2026-09-14.
- Implementation started: 2026-09-14.
- Revision `r2` proposed: 2026-09-19.
- Revision `r2` approved: 2026-09-19.
- Implementation resumed: 2026-09-19.
- Revision `r3` proposed: 2026-09-19.
- Revision `r3` approved: 2026-09-19.
- Implementation route: direct implementation.
- Implementation started: 2026-09-19.
- B-005 acceptance state: not accepted.
- Pending action: complete the approved r3 implementation, review, and verification.

## Batch Scope

### Objective

Close B-005 through two bounded application fixes and repeatable technical verification.
Fix findings F1 and F2 without changing pricing policy or activating corrected fuel records.
Establish durable screenshot capture and repeat walkthroughs W1 through W9 with synthetic, non-personal data.

Retain evidence for every walkthrough and complete the required host, Composer, and frontend checks.
Keep the minimum visual-quality gate deferred to later operator-trial preparation.
That gate remains an operator-trial blocker, not a B-005 verification blocker.

### Why This Batch Now

The [r2 verification outcome](#implementation-outcome) records two Important application findings and one Important evidence finding.
F1 leaves unsupported horse counts without visible manual-review guidance.
F2 blocks issue of an explicitly corrected-fuel revision while its selected fuel record remains inactive.
F3 leaves reviewed walkthrough screenshots without durable repository files.

Cliff and Sophie are approved only as later trial operators for `southwest equine`.
The documented trial workflows are approved, while the minimum visual-quality gate remains deferred.
No operator trial can begin until that later gate is defined.

Current inspection reconfirmed the canonical, accepted B-004, B-005 r2, implementation, protected, and derived-document identities.
It also reconfirmed the repository HEAD and working-tree counts recorded under Dependencies.

### Included Work

- Reconfirm every recorded identity and the full working-tree state before application changes.
- Fix F1 so unsupported horse counts receive clear manual-review guidance on route review.
- Remove the automatic-pricing action when the enquiry cannot enter automatic pricing.
- Preserve the enquiry and prevent every partial job, revision, route-leg, allocation, or audit write.
- Fix F2 for corrections selected through the existing corrected-fuel action.
- Record enough revision evidence to distinguish an explicit correction from an unproven inactive fuel context.
- Let that explicit correction pass the issue checklist without activating its fuel record.
- Keep the active default fuel record unchanged for new pricing contexts.
- Preserve prior revisions, issued evidence, pricing evidence, and fuel history.
- Run focused test-driven development for F1 and F2 as separate behaviours.
- Establish and prove a dependency-free screenshot-capture method before repeating W1.
- Use installed Chrome with a temporary browser profile and a recorded fixed desktop viewport.
- Save reviewed PNG files directly under the r3 evidence directory.
- Create a disposable working-tree copy for SQLite data, temporary dependencies, and build artefacts.
- Serve deterministic synthetic HERE-compatible responses from a loopback-only fixture.
- Repeat W1 for enquiry entry, route review, and the one-horse P1 quote at `255.90`.
- Repeat W2 for route review and the two-horse P2 quote at `282.27`.
- Repeat W3 for the shared one-horse P6 allocation at `240.87`.
- Repeat W4 for a three-horse enquiry, visible manual-review guidance, and blocked automatic pricing.
- Repeat W5 for P7 corrected-fuel selection, issue eligibility, and total `259.39`.
- Repeat W6 through the route-backed exception form using P8 final total `250.00`.
- Repeat W7 through the legacy workspace using a synthetic pre-seeded revision.
- Repeat W8 for private issued output from a route-backed revision.
- Repeat W9 for transport-day creation, assignment, removal, and ordering.
- Retain structured data evidence and reviewed PNG evidence for every walkthrough.
- Run the complete host suite, Composer validation, and a clean frontend production build.
- Record F4 as an allowed optional optimisation warning when it remains unchanged.
- Recalculate canonical, scope, implementation, protected, evidence, and derived-document identities.
- Request final evidence, scope, and documentation review before outcome acceptance.
- Refresh only the affected workflow evidence and derived records.

### Explicitly Excluded Work

- No PostgreSQL environment, migration, compatibility, or performance work.
- No live HERE provider check, credential check, coverage claim, route-accuracy claim, or vehicle-profile decision.
- No real customer, operator, mailbox, quote, or journey data.
- No account creation, operator access, training, or operator-trial activity.
- No live rate, fuel, account, secret, feature, or provider configuration activation.
- No deployment, hosting, recovery, rollback, promotion, release, or production artefact publication.
- No pricing-policy change, provisional-value confirmation, or fuel-record activation.
- No visual redesign, styling correction, responsive redesign, accessibility remediation, or visual-quality decision.
- No email ingestion, HayNet integration, mailbox access, commercial portal, or public-platform work.
- No dependency addition, package-range change, committed lockfile, or package-manifest change.
- No schema change, unless the user approves a revised scope after evidence proves it necessary.
- No unrelated application change, test change, refactor, or documentation clean-up.

### Expected Files or Components

- `app/Services/Intake/TransportEnquiryRouteReview.php`: F1 eligibility and manual-review presentation data.
- `resources/views/transport-enquiries/route-review.blade.php`: visible F1 guidance and unavailable pricing action.
- `tests/Feature/Quotes/RouteFirstTransportQuoteFlowTest.php`: focused F1 feature coverage.
- `app/Services/Quotes/QuoteWorkspaceManager.php`: explicit corrected-fuel selection evidence.
- `app/Services/Quotes/IssuedQuoteChecklist.php`: F2 issue eligibility for proven explicit corrections.
- `tests/Feature/Quotes/QuoteWorkspaceTest.php`: corrected-fuel revision and history coverage.
- `tests/Feature/Quotes/IssuedQuoteOutputTest.php`: corrected-fuel issue-checklist and issue coverage.
- `docs/workflow/evidence/B-005-private-workflow-verification/manifest.md`: retained r2 evidence and r3 evidence index.
- `docs/workflow/evidence/B-005-private-workflow-verification/r3/`: r3 transcripts, identities, database evidence, and capture procedure.
- `docs/workflow/evidence/B-005-private-workflow-verification/r3/screenshots/`: reviewed synthetic PNG evidence for W1 through W9.
- `docs/workflow/current-batch.md`: r3 scope, outcome, findings, review, and criterion assessment.
- `docs/workflow/derived/private-mvp-readiness.md`: refreshed defect and verification status.
- `docs/workflow/derived/delivery-plan.md`: refreshed package-three status and later trial blocker.
- `docs/workflow/derived/build-and-release-plan.md`: refreshed host and frontend verification evidence.
- `docs/workflow/index.md`: current state and recalculated derived-document identities.

Implementation can touch fewer listed application files when the focused tests prove that they are unnecessary.
Any additional application, test, schema, configuration, or package path needs explicit scope approval before modification.
Temporary databases, browser profiles, fixture servers, dependency trees, and build outputs must remain outside the repository.

### Dependencies

- Canonical intent `r1` must retain SHA-256 `fc253f7246c1d90da1417618ceb8d4a1a1590d04b94b6f59e597f9d6e51b5ce8`.
- Pricing policy `r2` must retain SHA-256 `bb775278bc0d6ebdc1a6015b539e3694d66651088e782b3eab1648a17197a230`.
- Accepted B-004 scope `r1` must retain SHA-256 `4e9b930453776b1e14f085d3fd76ff1a21e6817ac205b628383547196c1f87c9`.
- Accepted B-004 outcome `r1` must retain SHA-256 `00de60879337bd95d8912188aaea62ee7c7f9d87dc78df32d6eafbb78cafafe1`.
- Approved B-005 scope `r1` must retain SHA-256 `2a9569e7f88634817c2948200540dffca58063bb33c17dde3e1ccf37f1014495`.
- Approved B-005 scope `r2` must retain SHA-256 `12e64d6413fc4655eac32e8c0a75423e4f55d43bb0d18af06f822e5f913fc869`.
- The accepted 31-path implementation aggregate starts at `4b27806ce58815dd7c6756909f8f484a391d89254fd555539d5bf30a147e58a2`.
- The remaining 198-path protected aggregate starts at `0de4b697522de2146d6f072e9cb6dff15d8360432b37edcd5943ce4760b58c87`.
- The original 199-path protected snapshot reconstructs to `0502d6ba8c23e4aaa75cda5acd22f52adbb5e92d242750b1c71345134c617a95`.
- Repository HEAD remains `c380baa0a95e9a2e81ff21182c0b492ce53f400a`.
- The proposal baseline contains 34 modified tracked files and 145 untracked files.
- The host provides PHP 8.5.3, Composer 2.9.5, Node.js 22.13.1, and npm 10.9.2.
- Existing Composer dependencies support `composer run test:host`.
- The repository has no frontend lockfile, `node_modules`, or `public/build`.
- Chrome 153.0.8010.48 is installed locally.
- The r3 capture procedure must prove durable PNG output before W1 begins.

### Risks and Uncertainties

- The application remains mainly uncommitted work above two historical commits.
- Content fingerprints must identify the candidate more precisely than HEAD.
- Current revisions store a selected fuel record but no dedicated correction-provenance field.
- This scope assumes that existing revision evidence can record explicit selection without a schema change.
- If that assumption fails, implementation must stop for an approved scope revision.
- Chrome is installed, but durable DevTools capture into the repository has not been proved in this workspace.
- If the capture proof fails, walkthrough execution must stop before W1.
- The loopback routing fixture verifies only the current request and response contract.
- It does not establish live provider behaviour, UK route accuracy, or vehicle suitability.
- W7 still needs a synthetic pre-seeded legacy revision because the interface exposes no creation route.
- The missing frontend lockfile prevents a reproducible dependency identity.
- A successful build proves only the resolved temporary dependency set recorded for r3.
- External font retrieval can fail independently from application assets.
- Browser evidence covers one desktop browser and one recorded viewport.
- It does not establish operator comprehension, accessibility, responsive quality, or wider browser compatibility.
- Shared-run and transport-day walkthroughs reuse synthetic records.
- The manifest must record prerequisites so that one failure cannot hide later coverage.
- Screenshots can expose entered data, local paths, or diagnostics.
- Every retained image must pass a privacy and secret review.
- F4 can remain as a Minor optional optimisation warning.
- No dependency addition is authorised to remove that warning.

### Acceptance Criteria

- A1: Every prerequisite identity matches the exact SHA-256 value recorded under Dependencies before implementation.
- A2: F1 focused coverage fails first because unsupported counts lack visible manual-review handling.
- A3: W4 then shows clear manual-review guidance and offers no automatic-pricing action.
- A4: W4 preserves the enquiry and creates no job, revision, route leg, allocation, or exception audit.
- A5: F2 focused coverage fails first because the explicit inactive correction cannot pass issue checks.
- A6: The explicit corrected-fuel revision then passes the issue checklist and can be issued.
- A7: F2 leaves the selected correction inactive and leaves the active default unchanged.
- A8: F2 preserves prior revisions, issued evidence, calculation evidence, and fuel history.
- A9: An inactive fuel context without proven explicit selection remains ineligible for issue.
- A10: Focused RED and GREEN outputs exist for F1 and F2, with no production change before each RED result.
- A11: The capture proof creates a readable PNG at the documented repository path before W1.
- A12: W1 through W9 repeat with synthetic data, disposable SQLite, and the loopback fixture.
- A13: W1, W2, W3, W5, W6, W7, W8, and W9 reproduce their recorded expected behaviours.
- A14: Every walkthrough has structured evidence, supporting record identifiers, database evidence, and one or more reviewed PNG files.
- A15: Every retained image records its file hash, dimensions, capture time, browser version, viewport, and synthetic scenario identifier.
- A16: The complete host suite, Composer validation, and frontend production build exit successfully after the final application change.
- A17: The frontend evidence records resolved versions, output hashes, and any unchanged `fontaine` warning.
- A18: Final identities cover canonical scopes, batch scopes, implementation paths, protected paths, evidence files, and derived documents.
- A19: Final evidence, scope, and documentation reviews report no unresolved Critical or Important finding.
- A20: Updated records keep the deferred visual-quality gate as an operator-trial blocker only.
- A21: Updated records make no operator-acceptance, deployment, release, activation, or production-readiness claim.
- A22: No excluded path or activity enters the r3 outcome.

### Test-Driven Implementation Requirements

Implement F1 and F2 as separate test-driven changes.
Write each focused feature test before its production change and observe the expected failure.
Retain the command, exit code, failing assertion, and failure reason for each RED result.

The F1 test must load route review for an unsupported count.
It must assert visible manual-review guidance and the absence of the automatic-pricing action.
A separate request assertion must retain the existing transactional no-write guarantee.

The F2 test must select an inactive correction through the existing correction action.
It must then complete the issue request and assert the issued revision.
It must also assert that neither fuel record changes activation state.
It must prove that unselected inactive context remains blocked.

Write only the production code needed for the focused GREEN results.
Run the affected focused test files after each change.
Run the complete host suite after the final application change.

### Browser and Build Verification

Create a disposable copy after the final application change.
Exclude `.git`, `.env`, runtime data, prior generated assets, and repository dependency trees from the copy process.
Create a fresh SQLite database and application key inside that copy.

Serve the recorded deterministic route fixture on loopback only.
Use synthetic staff and customer identities under reserved domains.
Record the fixture source, response data, endpoint, and SHA-256.

Launch installed Chrome with a temporary profile and fixed desktop viewport.
Use Chrome DevTools screenshot capture to create PNG files at controlled evidence paths.
Prove that method with a readable test capture before W1.
Stop before W1 if the method cannot retain a genuine browser capture.

Run W1 through W9 with fresh records or documented prerequisites.
Capture each required final state and any intermediate state needed to prove the workflow.
Query the disposable database where the interface cannot prove persistence or non-persistence.

Run `composer run test:host` after the final application change.
Run `composer validate --no-check-publish`.
Run `npm install --ignore-scripts --no-package-lock` only in the disposable copy.
Run `npm run build` in that copy.
Record versions, exit codes, counts, warnings, generated assets, sizes, and hashes.

### Evidence Requirements

Keep all existing r2 evidence unchanged as historical evidence.
Place new evidence under `docs/workflow/evidence/B-005-private-workflow-verification/r3/`.
Use stable walkthrough names in every screenshot filename.

For each walkthrough, record inputs, actions, expected results, observed results, and supporting database identifiers.
Retain one or more reviewed PNG files that prove the material browser state.
Record rejected captures without retaining images that contain secrets or personal data.

Retain focused RED and GREEN transcripts for both fixes.
Retain the complete host, Composer, frontend, repository-status, fixture, and identity transcripts.
Record every command's working directory, environment boundary, exit code, and relevant result counts.

Calculate SHA-256 for every retained evidence file.
Calculate one sorted evidence-set aggregate from repository-relative paths and Git-clean blob identities.
Record screenshot dimensions and verify that each PNG decodes successfully.

Record the final application change set and every untracked path.
Recalculate the implementation aggregate after adding the authorised r3 application paths.
Recalculate the protected aggregate after removing only those authorised paths.
Record added, removed, and changed path lists beside both aggregates.

Request one final review for evidence integrity, one for scope compliance, and one for documentation wording.
Resolve all Critical and Important findings before presenting the outcome for acceptance.

### Expected Documentation Changes

Preserve the approved r1 and r2 scope approval records and all r2 findings as history.
Add r3 evidence links and factual results to the existing B-005 manifest.
Append the r3 implementation outcome, review evidence, verification evidence, and criterion assessment to this batch record.

Refresh F1, F2, F3, browser, build, and trial-blocker status in `docs/workflow/derived/private-mvp-readiness.md`.
Refresh package-three status and its next dependency in `docs/workflow/derived/delivery-plan.md`.
Refresh final host and frontend evidence in `docs/workflow/derived/build-and-release-plan.md`.
Recalculate the unchanged expansion-architecture identity without changing that document unless evidence requires a factual correction.
Update `docs/workflow/index.md` with the active state and every derived-document full-file identity.

Do not amend canonical intent or pricing policy within r3.
If evidence conflicts with approved canonical content, stop and propose the affected amendment.

### Decisions Requiring User Input

No unresolved product decision blocks approval of this technical scope.
The user must explicitly approve revision `r3` before implementation begins.
The user must separately accept the reviewed r3 outcome before B-005 can become accepted.
The minimum visual-quality gate remains deferred until later operator-trial preparation.

### Approval Boundaries

Revision `r3` is a proposal and carries no implementation authority until explicit approval.
Its approval will supersede r2 only for remaining B-005 execution.
It will not erase or reinterpret the approved r1 and r2 historical records.

Approval will cover only the two application fixes, focused tests, synthetic walkthroughs, durable evidence, verification, and documentation listed above.
It will not authorise any excluded work.
It will not accept the implementation outcome.

B-005 acceptance will require the reviewed outcome, fresh final verification, exact identities, and a separate explicit user decision.
The deferred visual-quality gate will not block B-005 acceptance.
It will continue to block operator-trial approval and execution.

## Approval Record

- Batch: `B-005-private-workflow-verification`.
- Scope: `docs/workflow/current-batch.md`, `## Batch Scope` and all nested sections.
- Revision: `r1`.
- SHA-256: `2a9569e7f88634817c2948200540dffca58063bb33c17dde3e1ccf37f1014495`.
- Approval date: 2026-09-14.
- Approval state: approved.
- Execution route: direct technical verification without application changes.
- User approval wording:

> approved

This approval authorises only the verification and documentation work in scope revision `r1`.
It authorises no defect fix, PostgreSQL work, live provider check, operator trial, deployment, release, or subsequent batch.

## Scope Revision Approval

- Batch: `B-005-private-workflow-verification`.
- Revision: `r2`.
- SHA-256: `12e64d6413fc4655eac32e8c0a75423e4f55d43bb0d18af06f822e5f913fc869`.
- Proposed: 2026-09-19.
- Approved: 2026-09-19.
- Approval state: approved.
- User wording that authorised this draft only:

> go ahead

- User approval wording:

> approved

Revision `r2` resolves only the protected-fingerprint conflict created by the required build-plan documentation update.
It permits that single workflow file change and protects the remaining 198 paths.
It also preserves the accepted 31-path application implementation aggregate.

The approved revision `r1` remains the historical execution authority for work before revision `r2`.
Revision `r2` resumes only the B-005 verification and documentation work in its scope.
It authorises no application change, deployment, release, activation, or operator trial.

## Scope Revision r3 Approval

- Batch: `B-005-private-workflow-verification`.
- Revision: `r3`.
- SHA-256: `cd5dee610e37a3746d0f29f78b49aad9bea58c458e92769632be21ff05000952`.
- Proposed: 2026-09-19.
- Approved: 2026-09-19.
- Approval state: approved.
- User approval wording:

> Assumed its approved and carry on

Revision `r3` proposes only the bounded fixes, repeated walkthroughs, durable evidence, verification, and documentation in its Batch Scope.
It preserves the approved r1 and r2 approval records as history.
Its approval authorises only that Batch Scope.
It authorises no deployment, release, activation, access, or operator trial.

The sections below record execution against revision `r2` only.
They remain historical evidence and do not assess proposed revision `r3`.

## Implementation Outcome

B-005 performed technical verification without changing application behaviour.
The [evidence manifest](evidence/B-005-private-workflow-verification/manifest.md) records every walkthrough, database check, build result, and finding.

Nine browser walkthroughs used synthetic data in a disposable SQLite environment.
All route responses came from a recorded loopback fixture.

Standard one-horse, two-horse, shared, corrected-fuel, both override, issued-output, and transport-day workflows produced their expected stored totals.
The complete host suite and frontend production build also passed.

Three Important findings prevent batch acceptance:

1. The unsupported-count rejection displays no manual-review message.
2. The corrected-fuel draft cannot pass the issue checklist while its selected correction remains inactive.
3. Reviewed inline screenshots could not be retained as durable repository image files.

No finding was fixed, masked, or absorbed into this batch.

## Acceptance Assessment

| Criterion | State | Evidence or remaining work |
| --- | --- | --- |
| A1 | Satisfied | Intent `r1`, pricing policy `r2`, and accepted B-004 scope and outcome match their recorded identities. |
| A2 | Satisfied | HEAD, full status, owned fingerprint, protected fingerprint, and generated evidence are recorded in the manifest. |
| A3 | Satisfied | Runtime data used a disposable SQLite database with synthetic records only. |
| A4 | Satisfied | The recorded loopback fixture supplied every routing response. |
| A5 | Satisfied | W1 stored P1 engine and final totals of `255.90`. |
| A6 | Satisfied | W2 stored P2 engine and final totals of `282.27`. |
| A7 | Satisfied | W3 stored P6 allocation-one totals of `240.87`, with 162 full and 34 split miles. |
| A8 | Unsatisfied | W4 created no quote, but displayed no manual-review error or instruction. |
| A9 | Satisfied | W5 stored `259.39`, retained `255.90`, and preserved the active fuel record. |
| A10 | Satisfied | W6 retained complete route-backed override evidence. |
| A11 | Satisfied | W7 retained equivalent legacy-workspace override evidence. |
| A12 | Satisfied | W8 showed the selected revision, route, pricing, final total, and exception evidence. |
| A13 | Satisfied | W9 demonstrated creation, assignment, removal, and ordering without changing quote totals. |
| A14 | Unsatisfied | Structured evidence exists, but durable screenshot files do not. |
| A15 | Satisfied | The temporary installation, resolved versions, lock digest, build output, and asset hashes are recorded. |
| A16 | Satisfied | The host suite passed 203 tests and 1,417 assertions. Composer validation also passed. |
| A17 | Satisfied | Every observed failure and build warning has a recorded finding. |
| A18 | Satisfied | No finding caused an application, test, configuration, schema, or package change. |
| A19 | Partial | Cliff and Sophie and the trial workflows are approved. The visual-quality gate remains deferred. |
| A20 | Pending | Final evidence and wording review must complete after documentation changes. |
| A21 | Satisfied | The 31-path aggregate and remaining 198-path protected aggregate retain their approved revision `r2` values. |
| A22 | Satisfied | Updated records distinguish technical verification from operator acceptance, deployment, release, and production readiness. |

B-005 remains implementing because A8, A14, A19, and A20 remain unsatisfied.
Finding F2 also blocks a corrected-fuel issue workflow.

## Verification Evidence

The browser used a 1280 by 720 CSS-pixel viewport at device pixel ratio 1.
Walkthroughs ran on 14 September 2026, with W9 removal completed on 17 September 2026.

The route fixture SHA-256 is `32748afe8738f60e7172a3a51877d45e71c7146c55470e3a3840135de6972d14`.
It supplied 10, 90, and 96 quoted miles with route identifier `b005-synthetic-route`.

`npm run build` exited 0 on 17 September 2026.
Vite 8.3.0 transformed three modules and completed in 820 milliseconds.

`composer run test:host` exited 0 on 17 September 2026.
It passed all 203 tests with 1,417 assertions in 19,699 milliseconds.

`composer validate --no-check-publish` exited 0 and reported a valid `composer.json`.
The known duplicate-extension warnings remained visible.

## Findings and Changed Assumptions

### F1 Important: unsupported counts lack visible manual-review handling

The pricing guard prevents unsupported automatic quotes and preserves the enquiry.
The route-review page supplies no visible error or next instruction.

This contradicts the expected manual-review experience and leaves A8 unsatisfied.

### F2 Important: inactive corrected fuel blocks quote issue

The corrected-fuel workflow preserves revisions and active-record state as intended.
The issue checklist then treats the selected inactive correction as invalid current context.

This prevents issue of that corrected draft without activating the correction.

### F3 Important: durable screenshots are missing

The browser tool emitted reviewed inline captures for every walkthrough.
It supplied no supported repository export path for those captures.

This leaves A14 unsatisfied.

### F4 Minor: optional font fallback optimisation is unavailable

The production build succeeds but reports that optional package `fontaine` is absent.
No dependency addition is authorised or necessary for the recorded build result.

### Corrected documentation contradictions

The build plan still claimed that host SQLite support and database-backed health were unverified.
Fresh evidence disproved that statement for the accepted host test command.

The workflow index also claimed that no implementation batch was active.
This record and the index now identify B-005 as implementing.

The stale local-development mount description remains outside B-005 scope.
No unrelated documentation clean-up occurred.

## Remaining Work and Missed Cases

No PostgreSQL run, live HERE check, real-data exercise, responsive review, accessibility review, or operator trial occurred.
No deployment, hosting, recovery, rollback, activation, or release work occurred.

The Important application findings need a separately approved remediation batch or approved B-005 revision.
The screenshot finding needs an approved evidence method or a criterion revision.

Cliff and Sophie will be the later trial operators for `southwest equine`.
The trial workflows listed below are approved for that later trial.
The minimum visual-quality gate is deferred until later.

These decisions authorise no account creation, trial, redesign, deployment, or release.

## Trial Decision Record

Recorded: 2026-09-19.

The user named Cliff and Sophie as the later trial operators under the business name `southwest equine`.
No accounts, access, trial activity, or operator data are authorised by this decision.

Existing documentation supports this approved trial scope:

1. Each operator signs in separately and completes the private manual workflow.
2. The sample contains at least five representative transport enquiries across standard one-horse and two-horse work.
3. The sample includes one unresolved route or provider-failure response.
4. The sample includes one audited route exception and one audited final-total override.
5. The trial covers corrected-fuel selection after finding F2 receives an approved treatment.
6. The trial covers issued output and browser print preview without sending customer email.
7. The trial covers one shared load with separate customer allocations.
8. The trial covers transport-day creation, assignment, ordering, and removal.
9. The trial covers loading practice separately from transport quoting.
10. Evidence records timings, route outcomes, override reasons, corrections, and feedback from both operators.

Items 1 to 6, 8, and 10 follow existing release and delivery records.
The user approved items 7 and 9 for inclusion on 19 September 2026.

The user deferred the minimum visual-quality decision.
No operator trial can begin until that gate is defined and the Important browser findings receive approved treatment.

User wording:

> Named trial operators will be Cliff and Sophie under southwest equine (their business name)

> The trial reqs should be mentioned in docs, otherwise make sensible suggestions, and the visual we will do later on

Workflow approval wording:

> approve

## Documentation Changes

- Added the B-005 manifest, repository status, build transcript, host transcript, and screenshot capture index.
- Refreshed the private MVP readiness assessment to revision `r7`.
- Refreshed the delivery plan to revision `r7`.
- Refreshed the build and release plan to revision `r2`.
- Refreshed the workflow index state and derived-document identities.

## Outcome Status

No outcome is ready for acceptance while Important findings and pending criteria remain.
No acceptance record or accepted outcome identity exists for B-005.

## Post-r3 Evidence Update (2026-09-25)

This is a factual evidence update, not a B-005 acceptance. Acceptance still requires a separate
explicit user decision, and the deferred visual-quality gate still blocks any operator trial.

Findings F1 and F2 are now resolved in the working tree, with test coverage, and verified green:

- **F1** — `app/Services/Intake/TransportEnquiryRouteReview.php` sets `requiresManualPricingReview`
  for horse counts above two and withholds the automatic-pricing action;
  `resources/views/transport-enquiries/route-review.blade.php` shows the manual-review message.
  Covered by `tests/Feature/Quotes/RouteFirstTransportQuoteFlowTest.php` (blocked action + visible
  guidance). This satisfies the intent of criteria A8/A3-A4 at the code level.
- **F2** — `app/Services/Quotes/IssuedQuoteChecklist.php::hasEligiblePricingContext()` accepts an
  explicit corrected-fuel selection (`calculation_explanation.fuel_context.selection_type ==
  explicit_correction` matching the stored `weekly_fuel_price_id`) without activating the record;
  the active default is unchanged and an unselected inactive context stays blocked. Covered by
  `tests/Feature/Quotes/QuoteWorkspaceTest.php` and `tests/Feature/Quotes/IssuedQuoteOutputTest.php`
  (including the negative case). No schema change was required.

Verification on 2026-09-25: `composer run test:host` → 286 passed, 1,716 assertions, exit 0;
`composer run test:postgres` → 15 passed, 60 assertions, exit 0. The frontend build now runs
offline after the E1 fix (removed the build-time remote font from `vite.config.js`).

F3 (durable screenshots) is unrelated to these two fixes and is not addressed by this update.
Live current state is tracked in `docs/STATUS.md`; the single task list is `docs/BACKLOG.md`.
