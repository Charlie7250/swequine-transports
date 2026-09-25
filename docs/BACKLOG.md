# BACKLOG — the one task list

This is the **single authoritative backlog.** It supersedes the four older, overlapping task
decompositions, which remain as *reference specs* the tasks below point into:

- `docs/horse-quotes/build-sequence.md` — Phases 1–11 (mostly built)
- `docs/horse-quotes/release-1/implementation-slice-plan.md` — Slices 1–5
- `docs/workflow/derived/delivery-plan.md` — work packages 1–6
- `docs/workflow/current-batch.md` — active batch B-005 (detailed F1/F2/F3 specs live here)

**How to use (loop):** take the first unchecked task under *Ready for autonomous work* whose
prerequisites are met, implement it per its spec, run its verification, tick the box, update
`STATUS.md`, commit. When nothing is ready, report the blocking human decision. See `/CLAUDE.md`.

Tasks are `[ ]` todo, `[~]` in progress, `[x]` done. Keep this file honest.

---

## Ready for autonomous work

Ordered by priority. Each is self-contained and verifiable without a human decision.

### Documentation & loop-readiness (do first — makes later loops reliable)

- [x] **A1. Add entry point + live state + backlog.** Create `/CLAUDE.md`, `docs/STATUS.md`,
  `docs/BACKLOG.md`. *Verify:* files exist and cross-link. (This task.)
- [x] **A2. Banner the stale strategy/planning docs** so no loop acts on their "current state."
  Added top notes to `docs/horse-quotes/strategy/vision-and-product-thesis.md` and
  `docs/horse-quotes/strategy/release-roadmap.md` pointing to `docs/STATUS.md`.
- [x] **A3. Fix broken handoff references.** Repointed both `next-chat-brief.md` files and
  `docs/horse-quotes/README.md` away from the non-existent `work/spreadsheet-analysis/*` paths to
  `docs/STATUS.md` / `docs/BACKLOG.md` and the canonical pricing docs.
- [ ] **A4. Reconcile the root `docs/*.md` mirror.** It has drifted from `docs/horse-quotes/*`
  (6/7 files differ). Either delete the root mirror and update inbound links
  (`docs/workflow/index.md`, root `README.md`), or regenerate it verbatim from `docs/horse-quotes/*`.
  Recommend **delete**. *Verify:* no drifted duplicate remains; links resolve.
- [ ] **A5. Reconcile the calculation-reference legacy numbers.** `docs/horse-quotes/calculation-reference.md`
  shows base cost £0.41 / totals that disagree with canonical `pricing-policy.md` (base cost
  0.365736). Correct the legacy doc to match the canonical examples, or banner it as superseded by
  `docs/workflow/pricing-policy.md`. *Verify:* no contradictory pricing example presented as current.

### Code defects (fully specified in `docs/workflow/current-batch.md`)

- [ ] **B1. Fix F1 — visible manual-review guidance for unsupported horse counts.**
  Route-review must show a clear manual-review message and offer no automatic-pricing action for
  counts >2, while creating no job/revision/leg/allocation/audit write. Spec + acceptance (A2–A4,
  A8) in `current-batch.md`. Test-first: `tests/Feature/Quotes/RouteFirstTransportQuoteFlowTest.php`.
  *Verify:* new failing test first, then `composer run test:host` green.
- [ ] **B2. Fix F2 — allow issuing an explicitly-corrected-fuel draft without activating the record.**
  A draft that explicitly selected a corrected fuel record passes the issue checklist; the record
  stays inactive; the active default is unchanged; an *un*selected inactive context stays blocked.
  Spec + acceptance (A5–A9) in `current-batch.md`. Tests:
  `QuoteWorkspaceTest.php`, `IssuedQuoteOutputTest.php`. *Verify:* failing test first, then
  `composer run test:host` green. **Check the schema assumption first** (revisions may lack a
  correction-provenance field — see current-batch "Risks"); if a schema change is needed, stop and
  flag rather than widening scope.

### Verification parity (autonomous, needs the toolchain)

- [ ] **C1. Re-establish the host test baseline in this environment.** Run `composer install`, then
  `composer run test:host`; record pass/fail counts in `STATUS.md`. *Verify:* recorded result.
- [ ] **C2. Run the PostgreSQL integration suite** (`composer run test:postgres` in the container)
  and record whether the app is PostgreSQL-clean, since SQLite passing does not prove it.
  *Verify:* recorded result; open follow-up tasks for any failures.

## Needs light user approval (not a big decision, but touches policy/deps)

- [ ] **D1. Commit a frontend lockfile** (`package-lock.json`) to make the JS build reproducible.
  This pins *existing* deps (no new packages) but changes package management — confirm with user
  before committing. Flagged in `private-mvp-readiness.md`.
- [ ] **D2. Reconcile transport-day / beta-baseline docs** that still call transport-day
  "proposed" though routes/views/model/services exist. Low risk, but decide historical-vs-current
  framing with the user.

## Blocked on human decision (surface these; do NOT act autonomously)

These are the real MVP-to-deployable gates. An autonomous loop should *report* which one is next,
not guess an answer. Full context + owners in `docs/horse-quotes/release-1/open-decisions.md`.

- [ ] **H1. Choose the paid routing provider + commercial terms** (D1). Unblocks live-routing
  verification and Slice 2 sign-off.
- [ ] **H2. Define the minimum visual-quality gate** for the first operator trial (currently
  deferred/undefined). Blocks the Cliff & Sophie trial.
- [ ] **H3. Confirm provisional pricing values** with the client (horse multipliers 1.5/1.75,
  shared-load 0.75, extras treatment, short-journey factor). Until then pricing stays provisional.
- [ ] **H4. Name technical/business ownership, hosting (≤£50/mo), secrets, backup, rollback,
  outage response** (D12). Blocks any staging/production/deployment.
- [ ] **H5. Approve the operator trial** and its access/accounts once H2 and the F1/F2 fixes land.

## Explicitly out of scope now (documented, build later)

Email/HayNet ingestion, public quote intake, multi-tenant/commercial portal, marketplace, route
matching, full-day map export, major visual redesign. Do not start these. See
`docs/workflow/derived/expansion-architecture.md`.
