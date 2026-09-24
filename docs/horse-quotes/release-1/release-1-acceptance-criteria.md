# Release 1 Acceptance Criteria

## Purpose

These gates decide whether Release 1, MVP Rescue, is suitable for staging and then a controlled private-production pilot. They do not declare the product ready for broad rollout, public access, marketplace work, or default daily operational reliance. The latter remains subject to the later Release 2 and Release 3 strategy gates.

Release 1 is a failure if automatic route mileage is not present in the normal quote path. A spreadsheet-like workflow that depends on manual leg entry remains pre-MVP.

## Product acceptance criteria

| ID | Criterion | Evidence |
| --- | --- | --- |
| P1 | An operator can record an inbound transport enquiry from an existing channel and distinguish it from a loading-practice request. | Walkthrough using representative enquiries. |
| P2 | Valid depot, pickup, and drop-off postcodes resolve the three named transport legs automatically through the routing boundary. | Contract-level and end-to-end route-result evidence. |
| P3 | The normal flow has no manual leg-mile entry. | UX walkthrough and automated UI coverage where available. |
| P4 | The operator can see each leg's rounded miles, rate type, active fuel context, active rate context, engine total, and final total before issue. | Price-review walkthrough against a known calculation fixture. |
| P5 | An eligible enquiry can become a revision-backed draft quote and then an issued quote. | End-to-end happy-path walkthrough with stored records. |
| P6 | Issuing records the exact issued revision and `issued_at`, without changing earlier route or pricing evidence. | Revision and audit inspection. |
| P7 | Manual route-mile fallback and final-total overrides are exception-only, reasoned, and auditable. | Exception walkthrough and audit inspection. |
| P8 | Transport quoting remains distinct from loading-practice quoting. | Navigation and domain-boundary review. |

## UX acceptance criteria

| ID | Criterion | Evidence |
| --- | --- | --- |
| U1 | A non-technical operator can identify the next action at each stage without engineering help. | Moderated walkthrough with at least one representative operator. |
| U2 | The interface uses the four agreed states consistently: Draft enquiry, Quote-ready enquiry, Draft quote, and Issued quote. | Screen and state review. |
| U3 | Empty states, inline validation, route failure messages, warnings, and review-required states are present as defined in the UX outline. | Test matrix and visual review. |
| U4 | Route warnings are distinguishable from blockers, and manual-mile fields are not shown in the normal resolved state. | UI state walkthrough. |
| U5 | Operator-entered enquiry data is retained after route validation or provider failure. | Failure-path test. |
| U6 | All override badges and the underlying route and pricing evidence remain visible before and after issue. | Draft and issued quote inspection. |

## Operational acceptance criteria

| ID | Criterion | Evidence |
| --- | --- | --- |
| O1 | The team has exercised the happy path with at least five realistic enquiries representing the normal business mix. | Timed walkthrough log. |
| O2 | Each realistic test records start time, quote-ready time, issued time, route outcome, and any exception reason. | Release evidence log. |
| O3 | The system exposes counts and rates for route resolution attempts, failures, manual-mile fallback, leg-mile override, and final-total override for the release sample. | Release metric view or exported operational record. |
| O4 | Provider outage and postcode ambiguity have documented operator responses that staff can follow. | Runbook-style walkthrough of the defined fallback state. |
| O5 | The business owner accepts the primary workflow as closer to default quoting behaviour than the spreadsheet for the representative sample. | Recorded business-owner approval. |

## Technical acceptance criteria

| ID | Criterion | Evidence |
| --- | --- | --- |
| T1 | Routing is accessed through a documented adapter boundary, not calculated in controllers or Blade. | Architecture and code review against the contract. |
| T2 | A route result always represents the three explicit named legs, including complete failure detail where resolution fails. | Contract tests and persistence inspection. |
| T3 | The adapter records required provider metadata without recording secrets. | Audit-record inspection and secret scan. |
| T4 | Route status, warnings, review decisions, retries, manual route-mile changes, and final-total overrides are auditable against the quote revision. | Audit-path tests. |
| T5 | Pricing logic remains in the pricing domain. Routing supplies miles and evidence; controllers and Blade do not contain pricing rules. | Architecture and code review. |
| T6 | Existing transport revision and issued-quote integrity are preserved when a quote is revised after issue. | Regression tests. |
| T7 | Provider failures are observable to the technical owner and do not expose technical details to operators. | Staging failure simulation and logging inspection. |

## Measurable release targets

The strategy sets the desired experience as an issued quote in a couple of minutes, but it does not set numeric thresholds. Before Slice 3 begins, the business and technical owners must sign off the measurement window and thresholds in [open-decisions.md](open-decisions.md). The recommended starting targets are:

| Measure | Recommended Release 1 target | Measurement rule |
| --- | --- | --- |
| Happy-path turnaround | Median at or below 3 minutes from recorded complete enquiry to issued quote. | Excludes time waiting for a customer to provide missing information. |
| Automatic routing success | At least 95 percent of valid, representative staged enquiries resolve all three legs without manual-mile fallback. | Sample contains at least 20 route attempts once provider selection is complete. |
| Manual-mile fallback visibility | 100 percent of manual-mile fallback events have an operator, time, reason, and all changed leg values. | Measured across all staging and pilot records. |
| Override visibility | 100 percent of leg-mile and final-total overrides remain visible on the issued revision. | Measured across all override records. |
| Failure classification | 100 percent of failed route attempts have a contract status and failure category. | Measured across all route failures. |

These are proposed operational targets, not silently assumed business policy. If stakeholders choose different values, this table and the release evidence must be updated before staging approval.

## Staging entry gate

A Release 1 candidate may enter private staging only when all of the following are true:

- P1 to P8, U3 to U5, and T1 to T7 have passed in local verification;
- routing-provider credentials are configured in staging without being stored in the repository;
- staging uses a separate database and production-like routing integration configuration;
- the application, database connection, authentication, quote creation screen, active pricing context, and route lookup can be smoke tested;
- at least one intentional provider-failure test has been performed, with the operator message and technical observability reviewed;
- the fallback path cannot become the default UI path; and
- the technical owner approves staging health.

## Staging exit and private-production pilot gate

A candidate may move from staging to controlled private production only when all of the following are true:

- the staging happy-path sample and exception sample have been completed;
- the agreed measurable targets have been assessed and any miss has explicit business-owner and technical-owner disposition;
- the business owner accepts the operator workflow after a staging walkthrough;
- the technical owner confirms logging, rollback steps, and routing-provider outage response are available;
- only private named staff accounts can access the release;
- a backup, restore owner, and documented migration/rollback procedure exist for the target environment; and
- both the business owner and technical owner approve promotion.

Private production at this point is a controlled pilot. Release 1 alone is not permission to make the portal the sole default daily system. That later decision needs the Release 2 daily-use evidence and Release 3 production-readiness posture from the strategy layer.

## Explicit non-go conditions

Do not promote to staging if:

- any normal quote path requires manual leg-mile entry;
- any successful route can omit one of the three required legs;
- route status and provider failures are invisible or unclassified;
- pricing logic is placed in a controller or Blade view;
- route or final-total overrides lack an audit trail;
- a transport quote can be confused with a loading-practice quote; or
- local verification cannot demonstrate the happy path and a routing exception.

Do not promote to private production if:

- the business owner or technical owner withholds approval;
- staging smoke checks or operator walkthroughs fail;
- no controlled response exists for provider outage, ambiguous postcode, and disputed mileage;
- route failures or overrides cannot be measured in the pilot evidence;
- routing data, price explanation, or issued-revision evidence cannot be trusted after issue;
- access is public or uses uncontrolled shared credentials; or
- backup, rollback, or restore ownership is undefined.

