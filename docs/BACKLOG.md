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

- [ ] **E1. Make the frontend build work in the loop environment.** `vite.config.js` fetches the
  `Instrument Sans` font from `fonts.bunny.net` at build time; this session's egress policy denies
  that host (403), so `npm run build` fails, no `public/build/manifest.json` is produced, and ~79
  view-rendering tests 500. **This blocks all loop verification of any view.** Two clean fixes, one
  is the user's to pick:
  - **Code:** self-host the font (bundle the woff2 + local `@font-face`) or drop the `fonts` block
    from `vite.config.js` so the build runs offline (small visual impact until fonts are self-hosted;
    fonts are in the deferred visual layer, so confirm before changing).
  - **Environment:** allowlist `fonts.bunny.net` in the session's network policy — keeps the design
    unchanged, no code change. (Do not attempt to bypass the 403; it is an org policy denial.)
  Until E1 is resolved, treat C1/C2 view-test failures as expected infra, not regressions.

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

- [~] **C1. Host test baseline in this environment.** Done 2026-09-25: `composer install` OK, key
  set, `composer run test:host` → **206 passed / 79 failed**; all 79 are the missing-Vite-manifest
  infra cause (E1), not logic. Recorded in `STATUS.md`. Reaches full green once E1 is resolved.
- [ ] **C2. Run the PostgreSQL integration suite** (`composer run test:postgres` in the container)
  and record whether the app is PostgreSQL-clean, since SQLite passing does not prove it.
  *Verify:* recorded result; open follow-up tasks for any failures. (Also gated by E1 for view tests.)

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
