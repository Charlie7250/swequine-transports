# Guided delivery

State: canonical content approved. B-001 through B-004 are accepted. B-005 verification is implementing.

Drafted: 2026-09-12.

## Records

- [Project intent](intent.md): approved canonical content, revision `r1`, followed by observations, proposals, and open questions.
- [Transport pricing policy](pricing-policy.md): approved canonical content, revision `r2`, with provisional rules and representative examples.
- [Current batch](current-batch.md): approved B-005 verification scope `r2`, with no application-change or release authority.
- [Accepted assessment batch](batches/B-001-private-mvp-readiness.md): scope `r1`, outcome `r1`.
- [Accepted local test baseline](batches/B-002-local-test-baseline.md): scope `r1`, outcome `r1`.
- [Accepted pricing-policy batch](batches/B-003-pricing-policy-and-examples.md): scope `r1`, outcome `r1`.
- [Accepted pricing-alignment batch](batches/B-004-pricing-alignment.md): scope `r1`, outcome `r1`.
- [B-005 verification evidence](evidence/B-005-private-workflow-verification/manifest.md): synthetic walkthrough, build, and finding evidence.
- [Private MVP assessment](derived/private-mvp-readiness.md): non-canonical evidence and proposed next steps from B-001.
- This index: navigation, drafting consent, approval identity, and the canonical approval ledger.

The index has no independent canonical scope. The approved relationship with existing documents appears in the intent record's canonical section.

The accepted assessment archive records its scope approval, execution evidence, and delegated acceptance.

## Drafting Consent

On 2026-09-12, the user replied:

> approved

This answered the checkpoint asking whether the understanding was correct and whether drafting only these two records was approved:

- `docs/workflow/index.md`
- `docs/workflow/intent.md`

The consent permits drafting these records. It does not approve their canonical content, implementation, deployment, or a delivery batch.

## Canonical Revision

| Scope | Revision | SHA-256 | State |
| --- | --- | --- | --- |
| `docs/workflow/intent.md`, `## Canonical Content` and its nested sections | `r1` | fc253f7246c1d90da1417618ceb8d4a1a1590d04b94b6f59e597f9d6e51b5ce8 | approved |
| `docs/workflow/pricing-policy.md`, `## Canonical Content` and its nested sections | `r1` | fb94a7040643ea42a7d5aa69b0e76555dd7bfe1c992eb59c4c63901714fb3378 | superseded by `r2` |
| `docs/workflow/pricing-policy.md`, `## Canonical Content` and its nested sections | `r2` | bb775278bc0d6ebdc1a6015b539e3694d66651088e782b3eab1648a17197a230 | approved |

The digest covers the canonical heading and content up to the next level-two heading. Line endings use LF, with one final newline and UTF-8 encoding.
Trailing blank lines are removed before that final newline. All other characters and spacing remain unchanged.

## Canonical Approval Ledger

### Intent r1 approval

- Date: 2026-09-12.
- State: approved.
- Scope: `docs/workflow/intent.md`, `## Canonical Content` and all nested sections.
- Revision: `r1`.
- SHA-256: `fc253f7246c1d90da1417618ceb8d4a1a1590d04b94b6f59e597f9d6e51b5ce8`.
- User approval wording:

> approved

This answered the explicit request to approve that canonical section, revision, and digest after presentation of the drafted records.
This is a separate approval from the earlier drafting consent. It approves no implementation batch or deployment.

### Pricing policy r1 approval

- Date: 2026-09-13.
- State: approved.
- Scope: `docs/workflow/pricing-policy.md`, `## Canonical Content` and all nested sections.
- Revision: `r1`.
- SHA-256: `fb94a7040643ea42a7d5aa69b0e76555dd7bfe1c992eb59c4c63901714fb3378`.
- User approval wording:

> approved

This answered the explicit request to approve the exact candidate revision and digest.
The policy remains provisional pending client feedback, but it governs until an approved amendment replaces it.
This approval authorises no application implementation, deployment, or release.

### Pricing policy r2 approval

- Date: 2026-09-13.
- State: approved.
- Scope: `docs/workflow/pricing-policy.md`, `## Canonical Content` and all nested sections.
- Revision: `r2`.
- SHA-256: `bb775278bc0d6ebdc1a6015b539e3694d66651088e782b3eab1648a17197a230`.
- User approval wording:

> approved

This answered the explicit request to approve the exact two-decimal manual-entry amendment and its corresponding evidence corrections.
Revision `r2` supersedes revision `r1` while retaining its other policy decisions.
This approval authorises no application implementation, deployment, or release.

## Existing References

These links provide discovery evidence and historical planning context. Their existing status labels do not establish approval within this workflow.

| Subject | Existing reference |
| --- | --- |
| Existing documentation entry point | [Horse Quotes documentation](../horse-quotes/README.md) |
| Earlier mirror relationship | [Documentation mirror](../README.md) |
| Product and domain | [Project brief](../horse-quotes/project-brief.md), [domain rules](../horse-quotes/domain-rules.md) |
| Pricing evidence | [Calculation reference](../horse-quotes/calculation-reference.md) |
| Technology and local development | [Technical decisions](../horse-quotes/technical-decisions.md), [local development](../local-development.md) |
| Commercial direction | [Future platform roadmap](../horse-quotes/strategy/future-platform-roadmap.md) |
| Release and operations planning | [Release roadmap](../horse-quotes/strategy/release-roadmap.md), [deployment and operations plan](../horse-quotes/strategy/deployment-and-operations-plan.md) |
| Earlier acceptance questions | [Release 1 open decisions](../horse-quotes/release-1/open-decisions.md) |
| Evidence checkpoint | [Beta evidence baseline](../horse-quotes/release-2/beta-evidence-baseline.md) |
| Transport-day proposal | [Transport-day pipeline](../horse-quotes/features/transport-day-pipeline.md) |

## Standing Documentation Authorisation

Recorded: 2026-09-12. This standing instruction applies to the remaining project documentation generation.
The user wrote:

> I want you to skip every gate of approval for the rest of the content docs to be generated

> assuming approval is there on each section from me

This explicitly overrides separate drafting, documentation-content, documentation-batch, and documentation-acceptance questions for this documentation pass.
It includes acceptance of the presented B-001 assessment outcome and permits subsequent records to build on it.
Records retain exact identities and distinguish delegated approval from a separate user reply.
The original canonical intent remains unchanged. This later instruction overrides its documentation approval procedure for this pass only.
This authority does not settle unanswered business questions or authorise application changes, installations, purchases, deployments, or releases.
Review and verification remain required. This is a standing instruction, not a new reusable skill.

On 2026-09-12, the user continued this standing authority for necessary project documents:

> My standing documentation approval continues: generate and update necessary project documents without asking for each documentation approval.

The continuation covers workflow records and necessary derived project documents until the user changes it.
It does not approve application implementation, dependency installation, purchases, deployment, release, or unresolved business policy.
Implementation batch approval and implementation-batch acceptance remain separate gates.

## Documentation Continuation

The following records are authorised planning documents derived from approved intent and the accepted assessment:

- [Delivery plan](derived/delivery-plan.md): stages, candidate work packages, and decision timing.
- [Build and release plan](derived/build-and-release-plan.md): verification, environment, hosting evaluation, and operational evidence.
- [Expansion architecture](derived/expansion-architecture.md): email transition and commercial boundaries for later design.
- [Private MVP assessment](derived/private-mvp-readiness.md): refreshed readiness evidence and pricing-policy comparison.

Their proposed choices remain proposals. Approval of these records does not turn unresolved product policy into confirmed requirements.
B-005 is an active verification batch.
Cliff and Sophie are named for a later `southwest equine` trial.
Its workflow scope is approved, and its minimum visual-quality gate remains deferred.
It authorises no application change, operator trial, deployment, or release.

## Documentation Identities

These file digests identify the records generated or refreshed under standing documentation authorisation.
They approve documentation continuation, not the proposed implementation or unresolved business decisions.

| Record | Revision | Full-file SHA-256 |
| --- | --- | --- |
| `derived/private-mvp-readiness.md` | `r7` | `0085747c144d635a097f4ff42888004ead580cd7682391e44e07aedde1e7db49` |
| `derived/delivery-plan.md` | `r7` | `932efacbcce4a831a98be5b64baa6cb20d298303f2d95c8d61f12ee0d5a76e32` |
| `derived/build-and-release-plan.md` | `r2` | `a2735efb2b10656cb0fab620a92666b6703ea5d92a23b539867a4c704ab78e65` |
| `derived/expansion-architecture.md` | `r1` | `05cfa836cc017ee8957f64beab840b746fb85527bf0023ba6be96fabcfd581a5` |
