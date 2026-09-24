# Delivery plan

Status: non-canonical, rebuildable planning record, revision `r7`. Generated: 2026-09-12. Refreshed: 2026-09-19.
Authorised under the [standing documentation instruction](../index.md#standing-documentation-authorisation).
Canonical inputs: [intent](../intent.md#canonical-content), revision `r1`, and [pricing policy](../pricing-policy.md#canonical-content), revision `r2`.
Canonical SHA-256 values: `fc253f7246c1d90da1417618ceb8d4a1a1590d04b94b6f59e597f9d6e51b5ce8` and `bb775278bc0d6ebdc1a6015b539e3694d66651088e782b3eab1648a17197a230`.
Approval identity: [canonical ledger](../index.md#canonical-approval-ledger).
Evidence input: [accepted assessment](../batches/B-001-private-mvp-readiness.md), scope `r1`, outcome `r1`.
Assessment outcome SHA-256: `15e0685a52d67021483e4214f6a754ad2bf561dc466d56879db837934545d830`.
Additional evidence input: [accepted local test baseline](../batches/B-002-local-test-baseline.md), scope `r1`, outcome `r1`.
Repository baseline: `c380baa0a95e9a2e81ff21182c0b492ce53f400a`, plus the preserved 221-file non-workflow fingerprint.
Uncommitted application work is part of that baseline. HEAD alone does not identify the assessed implementation.
The accepted B-002 baseline ran all 180 tests successfully against in-memory SQLite on 2026-09-13.
Pricing implementation input: [accepted B-004-pricing-alignment](../batches/B-004-pricing-alignment.md#acceptance-record), scope `r1`, outcome `r1`.
B-004 outcome SHA-256: `00de60879337bd95d8912188aaea62ee7c7f9d87dc78df32d6eafbb78cafafe1`.
Verification after first-review corrections ran 203 tests with 1,417 assertions against in-memory SQLite.
The B-005 technical walkthroughs used synthetic data, disposable SQLite, and a local routing fixture.
They verified the frontend build and most private workflows while exposing two application defects.
Regenerate after changes to intent, implementation, runtime, operator feedback, or the decisions discussed below.
Recommendations describe candidate work, not implementation permission or verified behaviour.

## Confirmed direction

[Canonical intent](../intent.md#canonical-content) owns scope and priorities: private portal, management software for other transporters, then the public network.
The private product starts with manual entry, automated distances and pricing, fuel controls, and manually organised transport days.
Email ingestion follows the private minimum viable product (MVP). Visual overhaul remains documented but deferred.
The approved pricing policy supplies provisional rules and examples until client feedback supports an approved amendment.

## Proposed stages

Stage names below avoid reusing historical release numbers that imply completed operator acceptance.
They describe evidence milestones, not dates or promises.

| Stage | Useful outcome | Evidence before moving forward |
| --- | --- | --- |
| Local baseline | Repeatable verification of the existing code | Supported runtime, executable tests, recorded application findings, repeatable frontend build |
| Private trial | Both operators can assess the complete manual workflow | Agreed pricing examples, journey checks, fuel and override walkthroughs, transport-day exercise, recorded feedback |
| Private reliance | The portal can become the everyday quoting record | User release approval, operational ownership, restore evidence, outage fallback, acceptable operator experience |
| Email assistance | Existing leads become reviewable enquiries with less typing | Safe import, duplicate handling, correction workflow, measured time saved, continued HayNet support |
| Commercial portal | Another business can use the management product independently | Business isolation, configurable pricing, onboarding, support ownership, subscription decisions |
| Public network | Customers find transporters and manage enquiry availability | Listing-only and bundled access, response visibility policy, customer selection and closure, moderation decisions |

Email assistance can precede commercial expansion once the private workflow has reliable evidence.
Automatic backlog matching and full-day map export remain optional improvements whose value needs operator evidence.
The major visual overhaul needs a separately scoped design before wider commercial use.

## Candidate work packages

These packages derive from [assessment findings](private-mvp-readiness.md). They are not tickets or approved code designs.

| Order | Bounded outcome | Prerequisite | Verification |
| --- | --- | --- | --- |
| 1 | Reproducible local test baseline | Accepted in B-002 | Run the recorded host command and preserve its environment limits |
| 2 | Agreed pricing policy and examples | Approved pricing policy revision `r2` | Recompute its eight representative examples and preserve client-confirmation notes |
| 3 | Verify corrected pricing and private workflows | Accepted B-004 outcome and approved B-005 verification scope | Synthetic browser evidence, database evidence, host tests, and frontend build |
| 4 | Repeatable build and delivery process | Runtime baseline and chosen release environment | Rebuild from recorded source and dependencies, then rehearse promotion and recovery |
| 5 | Operator trial and feedback record | Confirmed workflow scope, access, example journeys, and minimum visual standard | Capture outcomes from Cliff and Sophie without treating code presence as acceptance |
| 6 | Controlled private release | Satisfied operational evidence and user release approval | Release checks and recorded business fallback |

Application failures discovered in package 1 become separate scoped work. Environment repair does not authorise pricing changes.
Package 2 can use synthetic examples without importing personal customer records into the repository.
Package 3 now has technical browser evidence for the accepted implementation candidate.
The unsupported-count page lacks visible manual-review handling.
The corrected-fuel draft also fails the issue checklist while its selected correction remains inactive.

Durable screenshot files remain missing, although every final state received inline visual review.
These findings block package 4 and any operator trial until the user approves their treatment.
No package authorises deployment merely because earlier documentation is approved.

## Decisions at the point of need

| Before | Decision | Decision owner |
| --- | --- | --- |
| Runtime work | Host or container verification path and access | User with technical implementer |
| Pricing changes | Follow [pricing policy revision r2](../pricing-policy.md#canonical-content). Amend it when client feedback changes a rule. | User informed by operators |
| Operator trial | Cliff and Sophie and the workflow scope are approved. Access and minimum visual quality remain | User informed by operators |
| Private release | Hosting, technical ownership, acceptable outage and data loss | User, with technical owner still unnamed |
| Email design | Import review, attachments, corrections, retention, fuel activation authority | User informed by operators |
| Commercial design | Business isolation, entitlements, pricing, onboarding and support | User |
| Public launch | Transporter verification, request visibility, selection, closure, payment boundaries | User |

## Approved Trial Scope

Cliff and Sophie are the named later trial operators for `southwest equine`.
Their naming authorises no accounts, access, trial activity, deployment, or release.

The existing release records support this approved sample:

- At least five representative transport enquiries across standard one-horse and two-horse work.
- One unresolved route or provider-failure response.
- One audited route exception and one audited final-total override.
- One corrected-fuel selection after finding F2 receives approved treatment.
- Issued output and browser print preview without customer email.
- One shared load with separate customer allocations.
- Transport-day creation, assignment, ordering, and removal.
- One loading-practice scenario kept separate from transport quoting.

Both operators must complete the core manual workflow.
Evidence must record timings, route outcomes, exception reasons, corrections, and operator feedback.

The approved scope includes shared loads and loading practice as separate trial workflows.
The minimum visual-quality gate is deferred and must be defined before the trial batch.

## Evidence to collect

Record enquiry source, manual quoting time, route failures, override reasons, day-planning effort, and operator corrections during trials.
Define the measurement start and end consistently before comparing times.
The historical 20-quote sample is a candidate, not a newly approved threshold.
Measure accepted work by source before proposing retirement of HayNet ingestion. An imported email count does not measure won business.
Neither the observation period nor the majority threshold beyond the user's stated direction is settled.

## Documentation maintenance

Keep confirmed product scope in intent, implementation findings in the assessment, and active implementation evidence in its batch record.
Update affected observations after code changes. Retain historical verification dates and label superseded evidence.
Use the [build and release plan](build-and-release-plan.md) for delivery evidence and the [expansion architecture](expansion-architecture.md) for deferred design boundaries.
