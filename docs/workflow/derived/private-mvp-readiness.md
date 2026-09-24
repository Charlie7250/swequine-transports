# Private MVP readiness assessment

Status: non-canonical, rebuildable analysis, revision `r7`. Generated: 2026-09-12. Refreshed: 2026-09-19.
Purpose: identify the remaining private minimum viable product (MVP) work and propose release gates.

Canonical inputs: [intent.md](../intent.md), revision `r1`, and [pricing policy](../pricing-policy.md), revision `r2`.
Canonical SHA-256 values: `fc253f7246c1d90da1417618ceb8d4a1a1590d04b94b6f59e597f9d6e51b5ce8` and `bb775278bc0d6ebdc1a6015b539e3694d66651088e782b3eab1648a17197a230`.
Approval reference: [canonical approval ledger](../index.md#canonical-approval-ledger).
Original authorising batch: [B-001-private-mvp-readiness](../batches/B-001-private-mvp-readiness.md#approval-record), scope `r1`, SHA-256 `d2cb5da4b01b00e9fa7da79b8c445adc64623ff35c8a4e6df26d91999e434263`.
Refresh authorisation: [B-003-pricing-policy-and-examples](../batches/B-003-pricing-policy-and-examples.md#approval-record), scope `r1`, SHA-256 `d63850337996e2e11900e302812b960b9d8b1b58ebd7e4a5c29fa498cd21d195`.
Accepted implementation input: [B-004-pricing-alignment](../batches/B-004-pricing-alignment.md#acceptance-record), scope `r1`, outcome `r1`.
B-004 outcome SHA-256: `00de60879337bd95d8912188aaea62ee7c7f9d87dc78df32d6eafbb78cafafe1`.

Inspected HEAD: `c380baa0a95e9a2e81ff21182c0b492ce53f400a`.
Before B-005 evidence records, the working tree included 34 modified tracked files and 140 untracked files.
The [execution baseline](../batches/B-001-private-mvp-readiness.md#repository-baseline) identifies the aggregate fingerprint of 221 non-workflow files. HEAD alone cannot recover this implementation.
Evidence includes code reads, workbook extraction, approved examples, fresh tests, browser walkthroughs, database queries, and a frontend build.
Historical verification appears in the [B-001 evidence](../batches/B-001-private-mvp-readiness.md#verification-evidence).
The accepted [B-002 evidence](../batches/B-002-local-test-baseline.md#verification-evidence) establishes the supported host test command.

Regenerate affected sections after changes to canonical intent, pricing policy, code, tests, runtime configuration, workbook evidence, or operator feedback.
The complete automated suite passes against in-memory SQLite.
Synthetic desktop walkthroughs and a frontend production build now have technical evidence.
PostgreSQL behaviour, live routing, deployment, wider browser coverage, and operator acceptance remain unverified.

## Assessment

The implementation contains the main private MVP components, but current evidence does not establish an operator-ready product.
The local test path, provisional pricing policy, and pricing-alignment implementation candidate are now established.
Two browser defects, durable screenshot evidence, operator evidence, PostgreSQL evidence, live routing checks, and incomplete trial decisions remain obstacles.
Neither operator has used the portal, according to the [recorded user context](../intent.md#user-reported-context).
Cliff and Sophie are now named for a later trial under `southwest equine`.

The assessment does not recommend expanding into email ingestion, visual redesign, or the public network before establishing the private workflow's baseline.
Those priorities retain their status in [approved intent](../intent.md#canonical-content).

## Requirement Evidence

Each row maps an approved requirement or its private-access context to implementation evidence.
The complete 203-test suite passed against in-memory SQLite on 2026-09-17 after the browser walkthrough setup.

| Requirement | Implementation and test evidence | Verification status and remaining work |
| --- | --- | --- |
| Private operator access | [Routes](../../../routes/web.php) protect operations with authentication. [Login controller](../../../app/Http/Controllers/Auth/AuthenticatedSessionController.php) validates credentials, throttles attempts, and regenerates sessions. [Authentication tests](../../../tests/Feature/Auth/StaffAuthenticationTest.php) cover staff access. | Automated SQLite tests pass. Cliff and Sophie are named, but their accounts and real access checks remain future trial work. |
| Manual enquiry entry | [Enquiry request](../../../app/Http/Requests/ResolveTransportEnquiryRequest.php) accepts contact details, postcodes, horse count, date or date-to-be-arranged, source, and constraint acknowledgement. [Quote-flow tests](../../../tests/Feature/Quotes/RouteFirstTransportQuoteFlowTest.php) exercise entry and validation. | Synthetic browser entry and route review succeeded. Unsupported counts create no quote, but the page lacks visible manual-review handling. |
| Automatic journey lengths | [Route manager](../../../app/Services/Routing/RouteResolutionManager.php) resolves the configured depot and persists attempts. [HERE adapter](../../../app/Services/Routing/HereRouteDistanceAdapter.php) geocodes postcodes and resolves three legs. [Adapter tests](../../../tests/Feature/Routing/HereRouteDistanceAdapterTest.php) use simulated provider responses. | Simulated-provider tests pass. Live UK coverage, accuracy, credentials, and vehicle suitability remain unverified. |
| Loaded and unloaded pricing | [Calculator](../../../app/Services/Pricing/DeterministicPricingCalculator.php) applies stored horse multipliers to loaded rates. [Pricing engine](../../../app/Services/Pricing/JobRevisionPricingEngine.php) persists totals and explanations. [Pricing tests](../../../tests/Unit/Pricing/DeterministicPricingCalculatorTest.php) cover P1 through P5. | SQLite tests reproduce approved standard and rounding examples. Client confirmation of provisional values remains outstanding. |
| Weekly fuel prices with overrides | [Fuel manager](../../../app/Services/Pricing/WeeklyFuelPriceManager.php) records entries and changes the active record. [Quote manager](../../../app/Services/Quotes/QuoteWorkspaceManager.php) selects a recorded correction for a new draft revision. | Browser selection preserved both revisions and the active record. The issue checklist then blocked the inactive corrected-fuel revision. |
| Manually organised transport days | [Day controller](../../../app/Http/Controllers/TransportDayController.php) offers unassigned jobs. [Day manager](../../../app/Services/Scheduling/TransportDayManager.php) assigns, removes, and reorders jobs. [Scheduling tests](../../../tests/Feature/Scheduling/TransportDayManagerTest.php) exercise membership and order. | Synthetic browser creation, assignment, removal, and ordering succeeded without changing quote totals. Operator use remains unverified. |
| Understandable calculations and adjustments | [Quote view](../../../resources/views/job-revisions/show.blade.php) displays base and horse-adjusted rates. [Exception tests](../../../tests/Feature/Quotes/ControlledRouteExceptionsTest.php) cover controlled overrides. [Workspace tests](../../../tests/Feature/Quotes/QuoteWorkspaceTest.php) cover the legacy path. | Both browser paths retained matching controls and evidence. Operator comprehension remains unverified. |

The [transport-day view](../../../resources/views/transport-days/show.blade.php) explicitly states that grouping does not change a job's price.
It orders complete jobs rather than individual pickup and drop-off stops. Combined route optimisation and Google Maps export remain outside the private MVP.

The [issued view](../../../resources/views/job-revisions/issued.blade.php) is a staff-held record for manual customer delivery, with browser printing available.
An issued status does not prove an email was delivered. Customer wording and the manual sending process need validation during trial planning.

<a id="pricing-evidence-and-decisions"></a>

## Pricing Evidence and Approved Policy

The workbook remains evidence of business practice, with inconsistent formulas.
Source: user-supplied `Pipeline and Quotes.xlsx`, SHA-256 `f05b3fd83acb606b5c35589bb500f03a003186a1b4277fdfc18870c4db736110`.
Only pricing cells were extracted for this assessment. No customer records were copied into repository documents.

The approved [pricing policy](../pricing-policy.md#canonical-content) now governs until client feedback supports an approved amendment.

| Topic | Observed evidence | Approved provisional policy and implementation status |
| --- | --- | --- |
| Rate construction | `Rate Calc!L7:L12` supplies fuel, vehicle, labour, and speed inputs. | The approved formula matches current base-rate calculation. Production setting values still require client confirmation. |
| Horse count | `Rate Calc!F3` uses 1.5 and `F8` uses 1.75. Historical settings can store 1.15. | New settings and seeds use configurable 1.5 and 1.75 values. Current calculations apply the selected stored multiplier. |
| Rounding | `Individual Quotes!F5:M5` exposes a penny difference between line-item and final-only rounding. | Round whole-mile legs at six-decimal rates to pennies, then sum. Current standard pricing aligns. |
| Short journeys | `Rate Calc!L14` contains an inconsistent factor of 1.4. | Apply no automatic short-journey factor. Current pricing aligns. |
| Extras | Workbook notes contain manual charges without a reliable reusable structure. | Use an audited final override until client feedback establishes structured extras. Current calculations return no extras. |
| Shared loads | Workbook and earlier documents describe partial overlap and a typical 0.75 loaded percentage. | Shared loaded portions now use the horse-adjusted rate. Shared unloaded portions retain equal division. |
| Fuel corrections | Revision-specific records persist while an active record supplies new pricing contexts. | The current draft can select a recorded correction for a new revision without activating it. |
| Final overrides | Controlled exceptions retain engine totals, categories, reasons, actors, and revision evidence. | Both paths now use equivalent controls. Pricing-input changes clear overrides unless an authorised action reapplies them. |

Automated examples now reproduce P1 `255.90`, P2 `282.27`, P6 `240.87`, and P7 `259.39`.
P3 and P4 confirm the approved rounding order and short-journey treatment.
P5 and P8 retain separate engine and authorised final totals.

## Verification Limits

Host execution on 2026-09-17 reported 203 passing tests and 1,417 assertions.
The accepted B-002 command loads installed SQLite extensions for its child process only.
The default host process still exposes only `pgsql` and emits duplicate-extension warnings.

Nine synthetic desktop walkthroughs used a deterministic loopback routing fixture and disposable SQLite database.
The frontend production build succeeded with resolved package versions recorded in the [B-005 evidence](../evidence/B-005-private-workflow-verification/manifest.md).

The unsupported-count page lacks visible manual-review handling.
The corrected-fuel draft cannot pass the issue checklist while its selected correction remains inactive.
Screenshots were reviewed inline, but no durable repository image exists.

No live HERE routing check, PostgreSQL test run, restore exercise, operator trial, or timing measurement occurred.
The isolated tests use SQLite according to [phpunit.xml](../../../phpunit.xml), while the intended runtime database is PostgreSQL.
The passing SQLite suite and direct arithmetic probes do not establish production readiness.

## Documentation and Build Reconciliation

| Record or configuration | Observed discrepancy or limitation | Proposed follow-up |
| --- | --- | --- |
| [Transport-day proposal](../../horse-quotes/features/transport-day-pipeline.md) and [beta baseline](../../horse-quotes/release-2/beta-evidence-baseline.md) | They describe transport days as proposed, although routes, views, a model, and services exist. | Separate historical scope from current implementation and its unverified acceptance state. |
| [Vision](../../horse-quotes/strategy/vision-and-product-thesis.md) and [release roadmap](../../horse-quotes/strategy/release-roadmap.md) | The vision describes manual mileage as current. The roadmap also records later route-first implementation closure. | Retain historical rationale while identifying the present routing implementation and outstanding trial evidence. |
| [Local development](../../local-development.md), [README](../../../README.md), and [Compose](../../../compose.yaml) | Documents describe rebuilding for source edits or missing mounts. Compose now mounts application source directories. | Document the actual development path and distinguish source edits from frontend asset rebuilds. |
| [Operator workflow](../../horse-quotes/release-1/operator-workflow-spec.md) | It permits incomplete draft enquiries. The inspected normal entry path validates quote-ready details before saving. | Decide whether incomplete enquiry capture is required for the trial. Do not silently promote the older requirement into approved MVP scope. |
| [Calculation reference](../../horse-quotes/calculation-reference.md) | Its example lists fuel £1.53 and inputs that produce base cost £0.365736, but shows base cost £0.41 and different totals. | Treat the canonical pricing examples as authoritative. Reconcile this legacy record only within a later approved scope. |
| [Dockerfile](../../../Dockerfile) and [package.json](../../../package.json) | The image installs frontend dependencies with `npm install`. Repository inspection found a Composer lockfile but no frontend lockfile. The image runs `artisan serve`. | Establish repeatable development verification and later design the production build and serving process. |
| Repository delivery configuration | Inspection found no checked-in `.github` pipeline, `.gitlab-ci` configuration, or `Jenkinsfile`. | Decide automated checks and release promotion tooling during a later delivery batch. This says nothing about external automation. |

Existing documentation remains unchanged by this assessment. Findings identify reconciliation work without rewriting historical approval claims.

## Proposed Release Gates

These are recommendations for later approval. They create no release permission and impose no new canonical requirements.

| Gate | Proposed evidence | Remaining decisions |
| --- | --- | --- |
| Reproducible local baseline | The accepted host command runs all 203 tests. The temporary frontend build succeeds for its recorded dependency set. | PostgreSQL and a committed frontend lockfile remain later delivery concerns. |
| Private operator trial | Use approved examples and representative routes after resolving or accepting the browser findings. | Cliff and Sophie and the workflow scope are approved. Define the deferred minimum visual standard before a trial batch. |
| Controlled private release | Validate the intended database, live routing configuration, named staff access, customer output, backup restoration, deployment, rollback, and outage response. | Select hosting within £50 monthly and name technical ownership, support, recovery expectations, and release tooling. The user retains release approval. |
| Everyday reliance | Review actual quote turnaround, failures, overrides, manual work, and operator feedback before replacing the spreadsheet as the default. | Decide the sample and acceptable thresholds. The old 20-quote checkpoint, three-minute median, and 95 percent routing target remain proposals to reassess. |

The major visual overhaul remains deferred. The user must decide what limited visual quality is sufficient for the first trial.
Later email ingestion retains HayNet support through the transition described in approved intent. It is not a gate for manual-entry trials.

## Trial Decision Record

Cliff and Sophie are the named later trial operators for `southwest equine`.
This naming decision authorises no account creation, access, trial activity, deployment, or release.

Existing records support a five-enquiry sample covering standard quotes, route failure, audited exceptions, issued output, and transport-day planning.
Each operator must complete the core manual workflow and provide recorded feedback.

The approved scope also includes one shared load and one separate loading-practice scenario.
The user confirmed those two workflows on 19 September 2026.

The user deferred the minimum visual-quality gate until later.
A later trial batch remains blocked until that gate and the Important browser findings receive approved treatment.

## Recommended Next Bounded Step

The B-005 technical walkthroughs exposed two application defects and a durable screenshot evidence gap.
Their evidence appears in the [B-005 manifest](../evidence/B-005-private-workflow-verification/manifest.md).

Report these findings before any build-and-delivery batch or operator trial.
Seek the deferred visual-quality gate and an approved remediation scope before dependent work.

Do not start operator-trial preparation, deployment, release work, or another batch automatically.

## Assumptions and Remaining Coverage

No new assumptions define intended behaviour. Approved pricing policy now supplies the target behaviour for its subject.
This assessment traces the approved private workflow and relevant tests. It is not an exhaustive security, performance, concurrency, accessibility, or commercial-platform audit.
Unknown edge cases remain possible beyond the SQLite suite and synthetic pricing examples.
