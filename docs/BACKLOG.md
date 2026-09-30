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

## Priority snapshot (updated 2026-09-30, after the UI re-skin)

The app now matches the approved designs in `docs/design/` and the full flow clicks through.
Recommended order from here — **decisions** are yours, **build** items a loop can take:

1. **Decide H2** — accept the approved mockups + current re-skin as the minimum visual gate for the
   operator trial (quick yes/no; the design work is done).
2. **Build R3** — honesty pass on topbar placeholders (remove the fake notification count) before
   any operator sees the app.
3. **Build R2** — mobile re-skin of the six field pages (stacked cards + sticky action bar).
4. **Decide H1** — routing provider (critical path for real quoting in a trial).
5. **Decide H4** — hosting/ownership (critical path for putting the trial live).
6. **Decide which features to green-light first** — recommended order in *Next features* below
   (date sorting → customers page → global search → notifications → job times).

Operator-trial critical path: **H2 → H1 → H4 → H5**. Everything else can run in parallel.

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

## UI re-skin to the approved designs (`docs/design/*.png`)

- [x] **R0. Phase 0 — unify the content design system** with the mockups (tokens, fonts, paper
  cards, gold buttons, tinted tables, status dots, topbar search/bell placeholders). `aa49642`.
- [x] **R1. Polish pass** — line-art horse-head `<x-brand-logo>` (sidebar + login lockup +
  sidebar watermark), login layout, gold icon chips on all 47 section headings, final-total accent
  card, depot start/finish strip on transport-day detail. `7072fa9`. Host suite 286 green.
- [ ] **R2. Mobile re-skin of the six field pages** to match the `*-mobile.png` designs: new
  enquiry, route review, route exception, quote workspace, quote exceptions, transport-day detail
  (plus shared-run builder, which also has a mobile design). Tables collapse to stacked cards with
  labelled rows; primary action moves to the existing `.mobile-action-bar` (sticky). *Verify:*
  390px screenshots vs the mobile mockups; host suite green.
- [ ] **R3. Honesty pass on placeholders before any operator trial.** The topbar bell shows a
  hard-coded "3" to match the mockup — remove the count (or show "coming soon") so operators are
  never shown fake data. Keep search visibly disabled. *Verify:* no fabricated values in the shell.
- [ ] **R4. Per-page layout refinements** still short of the mockups: enquiry form as a deliberate
  two-column "Enquiry details" card; rate-settings active card (left column too sparse); issued
  quote restyled to the `issued-quote.png` print design. Low risk, cosmetic.
- [ ] **R5. Dashboard parity check** — confirm the production `/dashboard` matches the original
  dashboard mockup now that the shared shell changed; fix any drift.

## Next features — recommended order (awaiting your go-ahead)

Full detail, data needs and effort in `docs/product/proposed-enhancements.md`. None of these is
approved scope until you say so; each then becomes a scoped item with acceptance criteria.

1. **Date sorting / range filter** (S) — the Today/Day/Week/Month controls already exist on the
   dashboard, presentation-only. Cheapest visible win.
2. **Customers page** (S–M) — `Customer` model already exists; also the foundation for search.
3. **Global search** (M) — now visible as a placeholder in every page's topbar, so operators will
   reach for it. Jobs, customers, postcodes.
4. **Notifications** (M) — bell now visible; stale pending quotes, route failures, upcoming days.
   Needs you to choose which events and thresholds.
5. **Job / stop times + ETAs** (M) — ETAs partly free from stored `duration_seconds`.
6. **Calendar view** (M) — after 1 and 5.
7. **Reporting / revenue trends** (M–L) — agree the metrics first.
8. **Later subsystems** (L, product decision each): horse records, vehicles/drivers/assignment,
   route optimisation (high value, hard — gated on H1).

## Needs light user approval (not a big decision, but touches policy/deps)

- [x] **D1. Committed the frontend lockfile** (`package-lock.json`, 2026-09-25, user-approved) to
  make JS builds reproducible across loop sessions. Pins existing deps; no new packages.
- [ ] **D2. Reconcile transport-day / beta-baseline docs** that still call transport-day
  "proposed" though routes/views/model/services exist. Low risk, but decide historical-vs-current
  framing with the user.
- [ ] **D3. Self-host the Instrument Sans font** (e.g. `@fontsource/instrument-sans` via npm) so
  body text matches the designs exactly; currently falls back to a system sans since E1. New npm
  dependency → needs your OK.
- [ ] **D4. Commit a screenshot tool for visual checks** (`playwright-core` dev dependency +
  a small script driving the pre-installed Chromium) so loops can verify pages against the
  mockups, as done manually for R0/R1. New dev dependency → needs your OK.
- [ ] **D5. Supply final brand assets** (optional): an approved logo SVG and the sidebar horse
  photo from the mockups. The inline line-art logo is a stand-in until then.

## Blocked on human decision (surface these; do NOT act autonomously)

These are the real MVP-to-deployable gates. An autonomous loop should *report* which one is next,
not guess an answer. Full context + owners in `docs/horse-quotes/release-1/open-decisions.md`.

- [ ] **H1. Choose the paid routing provider + commercial terms** (D1). Unblocks live-routing
  verification and Slice 2 sign-off.
- [ ] **H2. Define the minimum visual-quality gate** for the first operator trial. Blocks the
  Cliff & Sophie trial. **Candidate answer ready (2026-09-30):** the approved mockups in
  `docs/design/` as implemented by R0/R1 (optionally plus R2 mobile). Needs only your yes/no.
- [ ] **H3. Confirm provisional pricing values** with the client (horse multipliers 1.5/1.75,
  shared-load 0.75, extras treatment, short-journey factor). Until then pricing stays provisional.
- [ ] **H4. Name technical/business ownership, hosting (≤£50/mo), secrets, backup, rollback,
  outage response** (D12). Blocks any staging/production/deployment.
- [ ] **H5. Approve the operator trial** and its access/accounts. F1/F2 have landed; now gated on
  H2, plus H1 (live routing) and H4 (hosting) for real use.

## Explicitly out of scope now (documented, build later)

Email/HayNet ingestion, public quote intake, multi-tenant/commercial portal, marketplace, route
matching, full-day map export, major visual redesign. Do not start these. See
`docs/workflow/derived/expansion-architecture.md`.
