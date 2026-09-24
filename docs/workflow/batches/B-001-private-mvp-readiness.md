# Current Batch

## State

- Identifier: `B-001-private-mvp-readiness`.
- State: accepted.
- Scope revision: `r1`.
- Scope SHA-256: d2cb5da4b01b00e9fa7da79b8c445adc64623ff35c8a4e6df26d91999e434263.
- Proposed: 2026-09-12.
- Approved: 2026-09-12. Assessment execution began after recording the approval below.
- Known verification limitations: the host lacks the SQLite driver, and Docker configuration and daemon access were denied.

## Batch Scope

### Objective

Produce one evidence-backed assessment of the remaining private minimum viable product (MVP) work and proposed release gates.
Use the [approved private MVP](../intent.md#private-minimum-viable-product) as the assessment boundary.

### Why This Batch Now

The programme intent is approved, but operator trials have not begun and implementation evidence differs from some documentation.
A bounded assessment will identify the next decisions and changes without treating existing code or historical release labels as acceptance evidence.

### Included Work

- Trace manual enquiry entry through distance resolution, pricing, fuel overrides, and transport-day organisation in the current implementation and tests.
- Map each approved private MVP requirement to implementation evidence, available verification, remaining gaps, and unresolved policy decisions.
- Compare relevant pricing behaviour with the supplied workbook and existing domain records, preserving disagreements as questions.
- Run existing isolated tests where the available runtime permits. Record runtime limitations separately from observed application failures.
- Identify relevant documentation contradictions and the records that need later reconciliation.
- Propose acceptance gates for an operator trial and private release, including the decisions needed for deployment and support.
- Recommend the next bounded delivery step, with dependencies and questions that must be resolved before its approval.

### Explicitly Excluded Work

No application changes, defect fixes, runtime repairs, dependency installation, data migration, live routing calls, mailbox access, or deployment.
No visual redesign, email integration, automatic backlog matching, public network implementation, or subscription architecture design.
No hosting vendor selection or paid service commitments. Hosting requirements can be listed as unresolved release dependencies.
No changes to approved canonical content or existing historical documents.

### Expected Files or Components

Read relevant application services, controllers, views, database definitions, tests, runtime configuration, and existing documentation.
Create only `docs/workflow/derived/private-mvp-readiness.md` as the assessment output.
Update this batch's factual outcome sections and the workflow index's navigation as required by guided-delivery.

### Dependencies

Use [intent revision r1](../index.md#canonical-approval-ledger) as the canonical input.
The current working tree and supplied workbook are evidence inputs. The example email provides context for the deferred ingestion boundary.
An unavailable test runtime does not prevent assessment, but it must leave the affected behaviour explicitly unverified.

### Risks and Uncertainties

Most expanded functionality is uncommitted, so the assessment must identify the working files alongside the base commit.
Existing tests can leave behaviour uncovered, and passing tests cannot establish operator acceptance or live-provider suitability.
Historical host tests lacked SQLite support, and Docker access failed. Recheck availability without changing runtime configuration.
Pricing policy remains unresolved in several areas. Report those gaps without selecting business rules.

### Acceptance Criteria

- A1: Every private MVP requirement has a cited implementation assessment and a clear verification status.
- A2: Test attempts record commands, dates, exit codes, relevant results, and environment limitations without implying unavailable checks passed.
- A3: Pricing-policy gaps and relevant documentation contradictions identify their source evidence and required follow-up decisions.
- A4: Proposed operator-trial and private-release gates remain recommendations, with unresolved ownership and operational dependencies visible.
- A5: The assessment recommends one bounded next step and identifies its prerequisite decisions.
- A6: The output identifies its canonical revision, digest, approval reference, repository state, generation date, and causes of potential staleness.
- A7: Changes remain confined to the assessment and workflow metadata. Approved canonical content and unrelated repository files remain unchanged.

### Verification Plan

Inspect `git status --short --untracked-files=all` and `git diff` to establish the current evidence boundary, including untracked files.
Check available database drivers with `php -r 'echo json_encode(PDO::getAvailableDrivers());'`.
Run `php vendor/bin/phpunit --do-not-cache-result` with the existing isolated test configuration if available.
If an accessible, current app container exists, the equivalent test command is `docker compose exec -T app php vendor/bin/phpunit --do-not-cache-result`.
Do not start or rebuild services for this assessment. Record inaccessible or stale container evidence as a limitation.

Review the final assessment against A1 through A7. Verify cited local paths and approval digests with the available Python runtime.
Compare before-and-after file hashes to verify the change boundary. Request an independent review of the assessment before presenting it for batch acceptance.

### Expected Documentation Changes

Create the single derived assessment with rebuildable, non-canonical status and input provenance.
Record findings, verification evidence, remaining work, and acceptance assessment in this batch outside its approved scope.
Link the assessment from the workflow index. Propose any later canonical amendment separately.

### Decisions Requiring User Input

Approval of this exact assessment scope is required before execution.
No other product or architecture decision is required to conduct the assessment. Its output must surface decisions needed before implementation.

## Approval Record

- Batch: `B-001-private-mvp-readiness`.
- Scope: `docs/workflow/current-batch.md`, `## Batch Scope` and all nested sections.
- Revision: `r1`.
- SHA-256: `d2cb5da4b01b00e9fa7da79b8c445adc64623ff35c8a4e6df26d91999e434263`.
- Approval date: 2026-09-12.
- Approval state: approved.
- User approval wording:

> approved

This answered the explicit request to approve the identified assessment batch, scope revision, and digest.
The approval permits this assessment only. Outcome acceptance and any subsequent delivery batch remain separate gates.

## Repository Baseline

Proposal inspection date: 2026-09-12.
Base commit: `c380baa0a95e9a2e81ff21182c0b492ce53f400a`.
Pre-existing work includes modified tracked files and untracked application, test, configuration, and documentation files described in [repository observations](../intent.md#repository-observations).
The existing workflow records are also uncommitted. Capture their exact state at execution before producing the assessment.
No accepted batch archives exist at proposal time. This is the first batch identifier.

Execution baseline on 2026-09-12 retains the same HEAD and pre-existing changes.
The 221 non-workflow repository files have aggregate SHA-256 `d559fb79e5eafe3391f7fc4464dc23ecc496c6f9a80dd76e8b7fbbcba57928b7`.
The aggregate hashes sorted unique Git-listed tracked and untracked paths outside `docs/workflow/`, followed by a null byte and each file's bytes.
Missing files contribute `MISSING` instead of bytes. The path encoding is UTF-8.
The full intent record has SHA-256 `a24d429343b729e2707ec7c64dac059be2b276b3e2cdfffdec6dba54c48a6ff3` and remains outside this batch's write scope.

## Implementation Outcome

Outcome revision: `r1`. Recorded: 2026-09-12.
The delivered artefact is [private-mvp-readiness.md](../derived/private-mvp-readiness.md).
Its full-file SHA-256 is `d4f434abf16b7df5d2af1556a3b9b34713418efb1883488465c93f6396e38ac2`.
The assessed implementation remains the uncommitted working tree identified in Repository Baseline, rather than HEAD alone.

The assessment traces the private workflow, compares pricing evidence, identifies documentation contradictions, and proposes release gates and one next delivery step.
The work added the assessment and updated only this batch's metadata and outcome sections plus index navigation.
No product or architecture decisions were made. No scope deviations occurred.

## Acceptance Assessment

| Criterion | Assessment | Evidence |
| --- | --- | --- |
| A1 | Satisfied | [Requirement evidence](../derived/private-mvp-readiness.md#requirement-evidence) maps private access, entry, distances, pricing, fuel controls, transport days, and explanation requirements. |
| A2 | Satisfied | Verification Evidence below records the test command, process exits, totals, all distinct errors, and runtime limitations. |
| A3 | Satisfied | [Pricing evidence](../derived/private-mvp-readiness.md#pricing-evidence-and-decisions) and [documentation reconciliation](../derived/private-mvp-readiness.md#documentation-and-build-reconciliation) identify sources and follow-up decisions. |
| A4 | Satisfied | [Proposed release gates](../derived/private-mvp-readiness.md#proposed-release-gates) distinguish recommendations from approval and retain ownership and operational questions. |
| A5 | Satisfied | [Recommended next step](../derived/private-mvp-readiness.md#recommended-next-bounded-step) proposes a reproducible local test baseline and names its runtime and access decisions. |
| A6 | Satisfied | The assessment's opening metadata identifies canonical and batch inputs, approval references, repository state, generation date, limitations, and regeneration triggers. |
| A7 | Satisfied | Record verification confirmed both approved digests, the entire intent file, and all 221 non-workflow repository files remain unchanged. |

Satisfaction here concerns the assessment deliverable. It does not claim that the application satisfies the private MVP or release criteria.

## Verification Evidence

All commands ran on 2026-09-12 from `C:/Users/charl/code/sweq-transports`.
PowerShell launched the installed PHP runtime. Bundled Python performed read-only extraction, process-output summarisation, and document verification.

| Check | Command or method | Observed result |
| --- | --- | --- |
| Repository state | `git status --short --untracked-files=all`, `git diff --stat`, and targeted file reads | HEAD unchanged. The pre-existing diff contains 31 modified tracked files, plus untracked expanded functionality and documentation. |
| Isolated test suite | `php vendor/bin/phpunit --do-not-cache-result`, captured using Python subprocess | PHP exit 2. PowerShell tool wrapper exit 1. Report: 180 tests, 34 passes, 176 assertions, 146 errors, 15,586 milliseconds. |
| Error classification | Parse the test runner's JSON and group every error by message | All 146 errors report the same missing SQLite driver. No other distinct error message appeared. |
| Database drivers | `php -r 'echo json_encode(PDO::getAvailableDrivers());'` | Exit 0, output `["pgsql"]`. Duplicate-extension warnings remain. |
| Container availability | `docker compose ps --format json` | Exit 1. Docker configuration and daemon access denied. No container tests ran and no service was started. |
| Workbook evidence | Python standard-library ZIP/XML extraction of named pricing cells, followed by independent Decimal arithmetic | Exit 0. Source hash matches the assessment. Sample total: £530.74 when rounded at the end, £530.75 when rounded per leg. |
| Direct pricing probe | PHP standard-input programme below | Exit 0 after correcting omitted required probe metadata. Both horse-count inputs return £530.75. Manual final £500.00 retains engine £530.75. |

The common test error was:

```text
could not find driver (Connection: sqlite, Database: :memory:, SQL: select exists (select 1 from "main".sqlite_master where name = 'migrations' and type = 'table') as "exists")
```

Duplicate PHP extension warnings name `curl`, `fileinfo`, `mbstring`, `openssl`, `pdo_pgsql`, `pgsql`, and `zip`.
These warnings and the driver failure remain unresolved. They do not establish application defects.

The first calculator probe omitted `week_commencing` and failed with the expected required-input exception, tool exit 1.
Correcting the temporary probe inputs produced the results below. No application code changed in response.

The successful probe used a PowerShell single-quoted here-string containing this programme, piped to `php`:

```php
<?php
require 'vendor/autoload.php';
$calculator = new App\Services\Pricing\DeterministicPricingCalculator;
$input = [
    'week_commencing' => '2026-05-03', 'fuel_source' => 'workbook_reference',
    'fuel_price_per_litre_inc_vat' => '1.67184',
    'miles_per_gallon' => '22', 'litres_per_gallon' => '4.54',
    'maintenance_per_mile' => '0.05',
    'unloaded_add_on_per_mile' => '0.5555555555555556',
    'loaded_add_on_per_mile' => '0.8064516129032258',
    'legs' => [
        ['label' => 'depot_to_pickup', 'miles' => 15, 'rate_type' => 'unloaded'],
        ['label' => 'pickup_to_dropoff', 'miles' => 240, 'rate_type' => 'loaded'],
        ['label' => 'dropoff_to_depot', 'miles' => 240, 'rate_type' => 'unloaded'],
    ],
];
foreach ([1, 2] as $horseCount) {
    $quote = $calculator->calculate(array_merge($input, ['horse_count' => $horseCount, 'two_horse_multiplier' => '1.75']));
    echo json_encode(['horse_count_input' => $horseCount, 'multiplier_input' => '1.75', 'engine_total' => $quote['engine_total'], 'leg_amounts' => array_column($quote['legs'], 'amount')]), PHP_EOL;
}
$quote = $calculator->calculate(array_merge($input, ['manual_final_total' => '500.00']));
echo json_encode(['override_engine_total' => $quote['engine_total'], 'override_final_total' => $quote['final_total']]), PHP_EOL;
```

Record verification used the bundled Python runtime with a standard-input programme to check local links, section digests, file hashes, and scope boundaries.
It exited with code 0: 85 local links checked, both approved scope digests unchanged, the entire intent file unchanged, and 221 non-workflow files unchanged.
Independent review approved the assessment against A1 through A7, with no Critical or Important findings.
The Minor finding concerned stale runtime metadata. The State section now records the observed SQLite and Docker limitations.

## Remaining Work and Missed Cases

Application test health remains unresolved. No live routing, browser usability, PostgreSQL execution, frontend build, deployment, restoration, or operator timing evidence was produced.
These are disclosed assessment limitations rather than unreported successes.
No mandatory assessment content is known to be missing. Independent review found no blocking assessment gaps.
The assessment is not exhaustive, and unknown application edge cases can remain.

## Debt and Follow-Ups

No known implementation debt was introduced because no application changes occurred.
Pre-existing gaps and proposed follow-ups are owned by the assessment's [pricing](../derived/private-mvp-readiness.md#pricing-evidence-and-decisions), [reconciliation](../derived/private-mvp-readiness.md#documentation-and-build-reconciliation), and [release-gate](../derived/private-mvp-readiness.md#proposed-release-gates) sections.
The recommended next direction is linked from A5. It carries no approval for subsequent work.
No review findings have been deferred at this stage.

## Documentation Changes

- Added `docs/workflow/derived/private-mvp-readiness.md`.
- Updated `docs/workflow/current-batch.md` with scope approval, execution evidence, and outcome assessment outside Batch Scope.
- Updated `docs/workflow/index.md` with execution state and assessment navigation.

No existing historical document or canonical intent text was amended.

## Acceptance Record

Accepted on 2026-09-12 under the user's standing documentation approval recorded in the workflow index.
This records delegated acceptance, not a separate affirmative reply to the outcome digest.
Acceptance concerns this assessment and the working-tree fingerprint in Repository Baseline. It grants no application release approval.
Outcome SHA-256: `15e0685a52d67021483e4214f6a754ad2bf561dc466d56879db837934545d830`.

## Archive Link Mapping

Archive-relative link substitutions preserve the original approval and outcome digest basis.
Reverse these substitutions before verifying historical digests.

```json
{
  "intent.md#private-minimum-viable-product": "../intent.md#private-minimum-viable-product",
  "index.md#canonical-approval-ledger": "../index.md#canonical-approval-ledger",
  "intent.md#repository-observations": "../intent.md#repository-observations",
  "derived/private-mvp-readiness.md": "../derived/private-mvp-readiness.md",
  "derived/private-mvp-readiness.md#requirement-evidence": "../derived/private-mvp-readiness.md#requirement-evidence",
  "derived/private-mvp-readiness.md#pricing-evidence-and-decisions": "../derived/private-mvp-readiness.md#pricing-evidence-and-decisions",
  "derived/private-mvp-readiness.md#documentation-and-build-reconciliation": "../derived/private-mvp-readiness.md#documentation-and-build-reconciliation",
  "derived/private-mvp-readiness.md#proposed-release-gates": "../derived/private-mvp-readiness.md#proposed-release-gates",
  "derived/private-mvp-readiness.md#recommended-next-bounded-step": "../derived/private-mvp-readiness.md#recommended-next-bounded-step"
}
```

The assessment links now target this archive. Its historical full-file hash predates that navigation-only change.
Assessment SHA-256 after archive link relocation: `aca0e03be2db1c737cbb8aea2164a0f10db0b359bd409f9425156f5cd73fae4e`.
