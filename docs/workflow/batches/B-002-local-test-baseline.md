# Current Batch

## State

- Identifier: `B-002-local-test-baseline`.
- State: accepted.
- Scope revision: `r1`.
- Scope SHA-256: `0b848516a254192a9209e781fc0cc1c5a7b707e95eec1cf0c981245f173a3bfb`.
- Outcome revision: `r1`.
- Outcome SHA-256: `00a41fe89b9e3666d03b6fc62a7f2d1f8e2824a802ce41bacea77d9805c772a7`.
- Proposed: 2026-09-12.
- Blocker: none.

## Batch Scope

### Objective

Provide a tracked Windows host command that runs the existing isolated PHPUnit suite without Docker or host-wide PHP settings changes.
Record the resulting baseline without treating test success as private minimum viable product readiness.

### Why This Batch Now

[Approved intent](../intent.md#private-minimum-viable-product) prioritises the private quoting and transport-day portal for two operators.
The [accepted assessment](../derived/private-mvp-readiness.md#recommended-next-bounded-step) recommends a reproducible local test baseline before product corrections or release promotion.

Fresh checks found that PHP Data Objects (PDO) still exposes only PostgreSQL under the default host settings.
The installed PHP distribution already contains the disabled SQLite extension binaries.
An explicit extension command ran all 180 tests successfully, so dependency installation and Docker startup are unnecessary for this batch.

### Included Work

- Add a `test:host` Composer script for the verified Windows host command.
- Load the existing `pdo_sqlite` and `sqlite3` extensions only for the child test process.
- Keep the existing PHPUnit configuration and its in-memory SQLite database unchanged.
- Update the README and local development record with the exact command, prerequisites, and limits.
- Run the existing suite, record its actual outcome, and classify any failures without fixing them.
- Record review, verification, limitations, decisions, and follow-ups in this batch.

### Explicitly Excluded Work

No application source, tests, database migrations, pricing policy, operator trial, or visual changes.
No email ingestion, commercial expansion, public network work, automatic matching, or full-day route export.
No dependency installation or update, PHP installation change, global `php.ini` edit, Docker build, container startup, or service purchase.
No PostgreSQL suite, frontend build, browser walkthrough, live routing call, deployment, or release.
No business decision becomes settled through this tooling batch.

### Expected Files or Components

- `composer.json`: add the host test script without changing dependencies.
- `README.md`: replace the inaccurate generic host test command with the verified path and limitation.
- `docs/local-development.md`: record the same host test path without reconciling unrelated development documentation.
- `docs/workflow/current-batch.md`: retain the approved scope and record factual progress and results.
- `docs/workflow/index.md`: retain navigation and the continued standing documentation authority.

After acceptance, archive this record as `docs/workflow/batches/B-002-local-test-baseline.md` and return the current batch to idle.

### Dependencies

The current host provides PHP 8.5.3, Composer dependencies in `vendor`, and the SQLite extension binaries.
The command depends on those binaries remaining available through PHP's configured extension directory.
The existing `phpunit.xml` supplies the in-memory SQLite test settings.

Docker is not a dependency for this batch.
The daemon now responds outside the workspace sandbox, but no project services run.

### Risks and Uncertainties

The command establishes the current Windows host path, not every developer platform.
It cannot install missing extension binaries on another machine.
The default host settings also emit duplicate extension warnings, which this batch records but does not repair.
SQLite success does not establish PostgreSQL compatibility, live routing fitness, browser usability, or operator acceptance.
The working tree contains substantial pre-existing uncommitted work, including both expected documentation targets.

### Acceptance Criteria

- A1: `composer run test:host` executes the existing suite with in-memory SQLite on the current host without Docker.
- A2: A fresh run records its exit code, test count, assertion count, duration, and every distinct failure class.
- A3: The command does not edit host PHP settings, start services, or install dependencies.
- A4: README and local development guidance identify the verified command, prerequisites, and SQLite versus PostgreSQL limitation.
- A5: Application source, application tests, dependencies, canonical intent, and unrelated uncommitted work remain unchanged.
- A6: Final review finds no unresolved Critical or Important issue under the repository severity rules.
- A7: Workflow records state actual results, limitations, decisions, and follow-ups without claiming minimum viable product readiness.

### Verification Plan

Before editing, run `composer run test:host` and confirm that the script is absent.
This configuration and documentation batch uses that observed failure instead of adding an application behaviour test.

After editing, run `composer validate --no-check-publish` and `composer run test:host`.
Query default PDO drivers again to confirm that host-wide settings remain unchanged.
Inspect `git diff --check` and the complete diffs for the three non-workflow target files.

Recalculate the protected 218-file fingerprint and confirm that unrelated non-workflow files remain unchanged.
Request a review of the final batch change, resolve all blocking findings, then run fresh final verification.

### Expected Documentation Changes

Update only the test instructions in README and local development guidance.
Keep the accepted B-001 assessment as historical evidence rather than rewriting its dated result.
Record fresh evidence in this batch and retain unresolved business decisions in canonical intent.

### Decisions Requiring User Input

Approve this exact batch scope and authorise direct implementation.
No pricing, trial, visual, operational, or commercial policy decision is required for this batch.

## Approval Record

- Batch: `B-002-local-test-baseline`.
- Scope: `docs/workflow/current-batch.md`, `## Batch Scope` and all nested sections.
- Revision: `r1`.
- SHA-256: `0b848516a254192a9209e781fc0cc1c5a7b707e95eec1cf0c981245f173a3bfb`.
- Approval date: 2026-09-12.
- Approval state: approved.
- Implementation route: direct implementation.
- User approval wording:

> approved and auth'd

This approved the identified batch scope and direct implementation route.
It did not authorise excluded work, deployment, release, or unresolved business policy.

## Repository Baseline

Inspection date: 2026-09-12.
Base commit: `c380baa0a95e9a2e81ff21182c0b492ce53f400a`.
The working tree has 31 modified tracked files and 134 untracked files.

Before this proposal, the three expected non-workflow targets had these SHA-256 values:

| File | SHA-256 |
| --- | --- |
| `composer.json` | `710a681214aa5ee88bd267612e630b6d297faec1c57da5581e31795085833ef9` |
| `README.md` | `0c33f119cb4287f13bb3d01c68fe94d86c16e9abaa2816dd25ecbae020428e32` |
| `docs/local-development.md` | `f2b70e2c8e0869a686814f954ce6d8b1e11ca2d87e28b979a77faf098a2754e0` |

The other 218 non-workflow files have aggregate SHA-256 `e03f7a27d0229aeb46fc765166c6d3e74b5dd64a8f2852953f637ebe3b02d972`.
The aggregate uses sorted Git-listed paths, UTF-8 path bytes, a null separator, and each file's bytes.

Fresh runtime evidence from the same working tree:

| Check | Result |
| --- | --- |
| Default PDO drivers | Exit 0, output `["pgsql"]`. |
| Default PHPUnit command | Exit 1, with 180 tests, 34 passes, 176 assertions, and 146 missing-driver errors. |
| Explicit SQLite extension command | Exit 0, with 180 passes, 1,279 assertions, and 10,974 milliseconds. |
| Docker inside the sandbox | Access denied to the configuration and daemon pipe. |
| Docker outside the sandbox | `docker compose ps --format json` exited 0 and reported no running project services. |

Both PHPUnit runs emitted the same pre-existing duplicate-extension warnings.
No repository file, PHP setting, dependency, container, or service changed during these checks.

## Implementation Outcome

Implementation began on 2026-09-12 against approved scope `r1`.
The change adds the `test:host` Composer script and updates the two approved test-guidance locations.
The script enables the installed SQLite extensions for its child PHP process only.

No application source, application test, dependency, lockfile, host setting, container, or service changed.
No business or architecture decision was made.
No scope deviation occurred.

## Acceptance Assessment

| Criterion | Final assessment | Evidence |
| --- | --- | --- |
| A1 | Satisfied | `composer run test:host` exited 0 and ran the existing in-memory SQLite suite without Docker. |
| A2 | Satisfied | Final verification recorded 180 passes, 1,279 assertions, 5,277 milliseconds, and no failure class. |
| A3 | Satisfied | The script uses process arguments only. Default PDO drivers remain `["pgsql"]`. |
| A4 | Satisfied | README and local development guidance state the command, installed-binary prerequisite, and PostgreSQL limitation. |
| A5 | Satisfied | Targeted hashes changed only for the three approved non-workflow files. The protected 218-file fingerprint remains unchanged. |
| A6 | Satisfied | Independent review approved the final implementation with no Critical or Important findings. |
| A7 | Satisfied | This record distinguishes tooling evidence from minimum viable product readiness and records limitations below. |

The reviewed and verified result is ready for implementation-batch acceptance.

## Verification Evidence

All commands ran on 2026-09-12 from `C:/Users/charl/code/sweq-transports`.

| Check | Command | Observed result |
| --- | --- | --- |
| Before-edit proving failure | `composer run test:host` | Exit 1. Composer reported that the script was undefined. |
| Composer configuration | `composer validate --no-check-publish` | Exit 0. Composer reported that `composer.json` is valid. |
| First implementation run | `composer run test:host` | Exit 0. All 180 tests passed with 1,279 assertions in 5,435 milliseconds. |
| Final complete suite | `composer run test:host` | Exit 0. All 180 tests passed with 1,279 assertions in 5,277 milliseconds. |
| Default host drivers | `php -r "echo json_encode(PDO::getAvailableDrivers(), JSON_UNESCAPED_SLASHES), PHP_EOL;"` | Exit 0, output `["pgsql"]`. No host-wide setting changed. |
| Preserved files | Recorded aggregate fingerprint method | All 218 protected non-workflow files retain digest `e03f7a27d0229aeb46fc765166c6d3e74b5dd64a8f2852953f637ebe3b02d972`. |
| Changed-file boundary | Targeted hashes and diff inspection | Only the approved sections of `composer.json`, `README.md`, and `docs/local-development.md` changed. |
| Independent review | Guided review of the final implementation | Approved with no Critical, Important, or Minor findings and no recommendations. |

Composer and PHP still emit pre-existing duplicate-extension warnings.
Target hashes after implementation are recorded below.

| File | SHA-256 |
| --- | --- |
| `composer.json` | `4bd7e538da552fb07bf6563023b00c8a4cacf87c3d5eab98547be1a81cabaf1c` |
| `README.md` | `e34af12c1fade85675e878d55bdd7255794455276ff406eec116e56ba501646c` |
| `docs/local-development.md` | `7e6bdf17989bcebb8ca672866ce00c703ca7730a0d7cf51249c81d268e52eefa` |

Independent review inspected the approved configuration, documentation, workflow record, and PHPUnit settings.
It returned Approved with no Critical, Important, or Minor findings and no recommendations.

## Remaining Work and Missed Cases

No required work remains within this batch.
This batch does not test PostgreSQL, Docker execution, frontend assets, browser behaviour, live routing, deployment, or operator workflows.
Unknown application edge cases remain possible beyond the existing suite.

## Debt and Follow-Ups

This batch introduces no known application debt.
The pre-existing duplicate-extension warnings remain a host configuration follow-up.
PostgreSQL verification remains a later build and release concern.
Pricing policy and operator-trial decisions remain unresolved in [canonical intent](../intent.md#open-questions).

## Documentation Changes

- Updated README host and container test guidance.
- Updated the host test section in `docs/local-development.md`.
- Updated the workflow index with the continued documentation authority and active batch state.
- Updated this batch with approval, implementation, and verification evidence.

The accepted B-001 assessment and approved canonical intent remain unchanged.

## Acceptance Record

Accepted on 2026-09-12.

- Batch: `B-002-local-test-baseline`.
- Approved scope revision: `r1`.
- Approved scope SHA-256: `0b848516a254192a9209e781fc0cc1c5a7b707e95eec1cf0c981245f173a3bfb`.
- Accepted outcome revision: `r1`.
- Accepted outcome SHA-256: `00a41fe89b9e3666d03b6fc62a7f2d1f8e2824a802ce41bacea77d9805c772a7`.
- Implementation state: the working tree identified by the target hashes and protected-file fingerprint above.
- User acceptance wording:

> approved

This acceptance covers the presented B-002 outcome only.
It grants no approval for deployment, release, or another implementation batch.

## Archive Link Mapping

Archive-relative link substitutions preserve the approved scope and accepted outcome digest basis.
Reverse these substitutions before verifying historical identities.

```json
{
  "intent.md#private-minimum-viable-product": "../intent.md#private-minimum-viable-product",
  "derived/private-mvp-readiness.md#recommended-next-bounded-step": "../derived/private-mvp-readiness.md#recommended-next-bounded-step",
  "intent.md#open-questions": "../intent.md#open-questions"
}
```
