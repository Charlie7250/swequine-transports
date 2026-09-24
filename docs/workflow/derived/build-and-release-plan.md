# Build and release plan

Status: non-canonical, rebuildable planning record, revision `r2`. Generated: 2026-09-12. Refreshed: 2026-09-17.
Authorised under the [standing documentation instruction](../index.md#standing-documentation-authorisation).
Canonical input: [intent](../intent.md#canonical-content), revision `r1`.
Canonical SHA-256: `fc253f7246c1d90da1417618ceb8d4a1a1590d04b94b6f59e597f9d6e51b5ce8`.
Approval identity: [canonical ledger](../index.md#canonical-approval-ledger).
Evidence input: [accepted assessment](../batches/B-001-private-mvp-readiness.md), scope `r1`, outcome `r1`.
Assessment outcome SHA-256: `15e0685a52d67021483e4214f6a754ad2bf561dc466d56879db837934545d830`.
Repository baseline: `c380baa0a95e9a2e81ff21182c0b492ce53f400a`, plus the assessment's 221-file working-tree fingerprint.
Uncommitted application work is part of that baseline. HEAD alone does not identify the assessed implementation.
Implementation input: [accepted B-004-pricing-alignment](../batches/B-004-pricing-alignment.md#acceptance-record), scope `r1`, outcome `r1`.
Verification input: [B-005 private workflow evidence](../evidence/B-005-private-workflow-verification/manifest.md), scope `r1`.
Regenerate after changes to intent, implementation, runtime, operator feedback, or the decisions discussed below.
Recommendations describe candidate work, not implementation permission or verified behaviour.

## Current evidence

The accepted host command loads SQLite extensions for its child test process.
It passed 203 tests with 1,417 assertions on 17 September 2026.

The default host process still exposes only `pgsql` and emits known duplicate-extension warnings.
No production or staging environment has been verified.
[Compose](../../../compose.yaml) supplies local PostgreSQL and source mounts. Its development settings do not establish a production configuration.
The [Dockerfile](../../../Dockerfile) builds frontend assets with `npm install` and starts the Laravel development server.
The [Composer configuration](../../../composer.json) contains setup and test commands. Setup includes migrations, so it is not a read-only verification command.
The assessment found no frontend lockfile or checked-in delivery pipeline.
The B-005 temporary frontend installation resolved current versions within the existing ranges.
Its production build succeeded, but that result is not reproducible without the recorded temporary lock identity.
The [historical operations plan](../../horse-quotes/strategy/deployment-and-operations-plan.md) provides context, not proof that its environments or controls exist.

## Proposed build process

1. Identify the source revision and any uncommitted changes before testing.
2. Select one supported local runtime and record its versions and database extensions.
3. Run the existing isolated suite with `php vendor/bin/phpunit --do-not-cache-result`.
4. Record failures separately as environment problems or reproduced application defects.
5. Define reproducible frontend dependency installation after deciding and recording its lockfile policy.
6. Run `npm run build` within the approved runtime and record the result.
7. Check the candidate against PostgreSQL before release, using a disposable database isolated from customer data.
8. Build a release artefact tied to the verified source and dependencies.

The B-005 batch completed steps 1 through 6 against a disposable local copy.
It did not complete PostgreSQL verification, artefact promotion, deployment, recovery, or release work.
Do not reuse local credentials, development defaults, or customer databases for isolated verification.
A production serving configuration needs design and validation before deployment.
An automated pipeline can later perform the same checks. Its provider and access model remain undecided.

## B-005 build evidence

The disposable environment used Node.js 22.13.1 and npm 10.9.2.
The temporary lockfile SHA-256 was `24569429c04a346f56c782b3414f95504958ae72ada016d29d22aac05804f7bc`.

Resolved versions were Vite 8.3.0, Tailwind CSS 4.3.3, and Laravel Vite Plugin 3.2.0.
The full version list appears in the [evidence manifest](../evidence/B-005-private-workflow-verification/manifest.md#frontend-production-build).

`npm run build` exited 0 on 17 September 2026.
It generated stable CSS, font, and manifest hashes for the recorded dependency set.

The build reports that optional package `fontaine` is absent.
This limits optimised font fallbacks but does not fail the build.

No package manifest, repository lockfile, application source, or repository runtime configuration changed during this verification.
The disposable `.env` and fixture endpoints changed only temporary runtime configuration.

## Proposed environments and promotion

| Environment | Purpose | Required separation in the proposed design |
| --- | --- | --- |
| Local | Development and isolated checks | Synthetic data and local credentials |
| Staging | Candidate walkthroughs and integration checks | Separate database, secrets, and access from production |
| Private production | Real enquiries and operator records | Named accounts, controlled configuration, backups and support |

Promote the verified artefact through staging before private production. Record the source, environment, migration plan, checks, and release decision.
Staging can be temporary if it preserves separation and supports the required checks. Its cost belongs in the budget calculation.
The user retains release approval. Documentation authorisation does not bypass this operational decision.

## Hosting evaluation before selection

No vendor, tariff, contract, or production architecture is selected here. Current provider research belongs in the hosting decision work.
Compare managed Laravel hosting, managed application platforms, and a maintained virtual server against the same requirements.
Managed offerings can reduce administration. A virtual server makes operating-system maintenance part of the technical owner's work.
Neither approach has demonstrated fitness within this project's budget yet.

| Evaluation area | Evidence needed |
| --- | --- |
| Runtime | Supported application runtime, required extensions, build method, and PostgreSQL compatibility |
| Operations | Deployment logs, recovery access, backups, restore procedure, and secret handling |
| Cost | Hosting, database, backups, routing usage, domain, staging, taxes, and contingency |
| Access | Private staff access, account recovery, and ownership of service accounts |
| Growth | Later worker execution, scheduled imports, storage, and business isolation options |
| Exit | Exportable database and files, documented recovery outside the chosen service |

The confirmed ceiling is £50 monthly for the private pilot, not a verified hosting quotation.
Use measured routing demand when calculating variable costs. Do not silently exclude routing or backups from the total.
Choose the provider only after comparing current official prices and confirming technical ownership.

## Release evidence record

Record each candidate's source identity, build identity, dependency versions, test results, configuration changes, and migration effects.
Include these functional checks: login, enquiry entry, route review, quote calculation, fuel selection, override evidence, issued output, and transport-day ordering.
Use representative UK journeys and verify the routing profile's suitability before claiming transport-route fitness.
The current car profile is an implementation observation, not approval of vehicle suitability.
Record operator findings and the user's release decision separately from technical checks.

## Recovery and support planning

Name an owner for deployment, secrets, backups, restoration, alerts, and operator support before private reliance.
Agree tolerable outage and data loss before choosing backup frequency and retention.
Demonstrate restoration into a separate environment. Verify representative quotes, pricing history, and staff access after restoration.
Define how operators fall back to their existing workflow and later reconcile work entered during an outage.

A previous application artefact alone does not reverse a database migration.
Review migration compatibility and define whether recovery uses code rollback, a forward correction, or restoration.
Record the associated data-loss implications before release approval.
Monitor application availability, quote failures, routing failures, and backup failures without logging unnecessary customer content.
Alert ownership and response times remain unresolved.

## Documentation reconciliation

Update local development instructions after the chosen runtime works and its exact commands have been verified.
Update historical transport-day and routing descriptions with dated implementation status, preserving their original planning context.
Replace contradictory calculation examples only after business pricing decisions establish expected values.
A release label must distinguish implemented, tested, operator-accepted, deployed, and relied upon.
