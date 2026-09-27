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

## ⚠️ E1 — Loop blocker: frontend build fails under restricted egress (needs a decision)

- [x] **E1. RESOLVED (2026-09-25, user-approved).** Removed the build-time remote font fetch from
  `vite.config.js` (was `bunny('Instrument Sans')` → `fonts.bunny.net`, blocked 403 here). The app
  now uses its CSS fallback stack; `npm run build` runs offline and produces the manifest. Full
  suite is green (see C1). To restore the exact typeface later, self-host the woff2 with a local
  `@font-face` — do not reintroduce a build-time network dependency.

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
- [x] **A4. Reconcile the root `docs/*.md` mirror.** Deleted the 8 drifted mirror files
  (README, project-brief, technical-decisions, domain-rules, calculation-reference,
  design-direction, build-sequence, next-chat-brief). Updated inbound links in root `README.md`
  and `docs/workflow/index.md`. `docs/horse-quotes/*` is now the sole planning source.
- [x] **A6. Close out the B-005 governance record.** Added a dated "Post-r3 Evidence Update" to
  `docs/workflow/current-batch.md` recording that F1/F2 are resolved-in-tree, test-covered, and
  verified green (host 286, PG 15), so the governance layer no longer contradicts `STATUS.md`.
  Framed as evidence, not B-005 acceptance (which still needs a user decision).
- [x] **A5. Reconcile the calculation-reference legacy numbers.** Bannered
  `docs/horse-quotes/calculation-reference.md` as subordinate to canonical `pricing-policy.md` and
  corrected the worked-example JSON to the canonical P1 figures (six-decimal rates, one-horse
  multiplier, engine total 255.90).

### Code defects (fully specified in `docs/workflow/current-batch.md`)

- [x] **B1. F1 — visible manual-review guidance for unsupported horse counts.** Already implemented
  in tree: `app/Services/Intake/TransportEnquiryRouteReview.php` +
  `resources/views/transport-enquiries/route-review.blade.php`, covered by
  `RouteFirstTransportQuoteFlowTest` (blocked action + visible message). Green run pending **E1**
  (test renders a view). No further code change needed.
- [x] **B2. F2 — issue an explicitly-corrected-fuel draft without activating the record.** Already
  implemented: `app/Services/Quotes/IssuedQuoteChecklist.php::hasEligiblePricingContext()`, covered
  by `QuoteWorkspaceTest` + `IssuedQuoteOutputTest` (incl. the negative case). Green run pending
  **E1**. No further code change needed. (No schema change was required — provenance lives in
  `calculation_explanation.fuel_context`.)

### Verification parity (autonomous, needs the toolchain)

- [x] **C1. Host test baseline established (2026-09-25).** After the E1 build fix,
  `composer run test:host` → **286 passed, 1,716 assertions, exit 0** (in-memory SQLite). F1/F2
  view tests pass. Recorded in `STATUS.md`.
- [x] **C2. PostgreSQL integration suite green (2026-09-25).** Ran against a local PostgreSQL 16
  instance (no docker daemon here; started the `16 main` cluster, created role `sweq` + db
  `sweq_transports_test`, mapped host `postgres`→127.0.0.1). `composer run test:postgres` →
  **15 passed, 60 assertions, exit 0**. Note: this suite only covers `tests/Integration/Postgres`
  (the legacy-import workflow); the broader app suite still runs on SQLite. Full PostgreSQL parity
  for all feature paths remains a later hardening item if desired.

## Needs light user approval (not a big decision, but touches policy/deps)

- [x] **D1. Committed the frontend lockfile** (`package-lock.json`, 2026-09-25, user-approved) to
  make JS builds reproducible across loop sessions. Pins existing deps; no new packages.
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
