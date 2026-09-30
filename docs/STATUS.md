# STATUS — current state and next action

**This file is the live source of truth for "where are we right now."** Update it whenever state
changes. Last updated: 2026-09-30.

For the task list see [`BACKLOG.md`](BACKLOG.md). For how to work the loop see
[`/CLAUDE.md`](../CLAUDE.md).

---

## One-line state

Private quoting portal: **core code built (routing + pricing + workflow), host suite green (286),
and the UI now matches the approved designs** in `docs/design/` — the whole flow clicks through as
a styled app. F1/F2 resolved. Reaching an operator trial is gated on human decisions (visual gate
H2 — answer ready, routing provider H1, hosting/ownership H4) plus the mobile re-skin (R2).

## UI state (2026-09-30)

- Approved designs: 22 mockups in `docs/design/` (16 desktop screens + mobile variants), produced
  from `docs/design/page-design-prompts.md`.
- Implemented: R0 design-system unification + R1 polish (brand logo, login lockup, section icons,
  final-total accent, depot strip). Verified desktop + mobile by screenshot.
- Remaining: R2 mobile field pages, R3 remove fake notification count, R4 small layout gaps,
  R5 dashboard parity. Topbar **search and notifications are visual-only placeholders** — the
  features themselves are ranked under *Next features* in `BACKLOG.md`.

## What is built (confirmed in code)

- Internal staff auth and route protection.
- Domain schema: customers, jobs, revisions, weekly fuel prices, rate settings, transport legs,
  shared runs + allocations, loading-practice quotes (19 migrations).
- Deterministic pricing engine in `app/Services/Pricing/*` (7 services), with calculation
  explanations and separate engine/final totals.
- **Automatic routing** via `app/Services/Routing/HereRouteDistanceAdapter.php` +
  `RouteResolutionManager.php` (three-leg depot→pickup→drop-off→depot).
- Route-first quote flow, controlled route/final-total overrides, shared-load allocation,
  transport-day grouping, issued-quote output.
- ~47 test files.

> Note: the `strategy/vision-and-product-thesis.md` claim that "route mileage is still manual" is
> **stale** — routing is implemented. Trust this file over it.

## Verification (2026-09-25, this cloud sandbox — GREEN)

- `composer install` → OK; `.env` + `APP_KEY` set. `npm ci` reproducible via committed lockfile.
- `npm run build` → **succeeds offline** after the E1 fix (removed the build-time remote font).
  Produces `public/build/manifest.json`.
- `composer run test:host` → **286 passed, 1,716 assertions, exit 0** (in-memory SQLite). Includes
  the F1 and F2 view tests. No failures.

- `composer run test:postgres` → **15 passed, 60 assertions, exit 0** against a local PostgreSQL 16
  instance (covers `tests/Integration/Postgres` — the legacy-import workflow). The broader app suite
  still runs on SQLite; full PostgreSQL parity for all feature paths is a later hardening item.

**Not verified:** live HERE routing (needs provider — H1), wider browsers, deployment, operator use.
`vendor/`, `node_modules/`, `public/build/` and `.env` are gitignored; first-time setup needs
`composer install` + `npm ci`. Local PostgreSQL for C2 is set up per BACKLOG C2 (not committed).

Earlier B-005 evidence (2026-09-17) recorded 203 passing and reproduced P1 255.90, P2 282.27,
P6 240.87, P7 259.39 via synthetic browser walkthroughs.

## Known defects

- **F1 — RESOLVED in tree (2026-09-25 review).** `TransportEnquiryRouteReview.php` sets
  `requiresManualPricingReview` for counts >2 and blocks the accept action; the route-review view
  shows "Automatic transport pricing supports one or two horses. Counts above two require manual
  review." Covered by `RouteFirstTransportQuoteFlowTest` (asserts blocked action + visible text).
  Full green run pending E1 (view test needs the build).
- **F2 — RESOLVED in tree (2026-09-25 review).** `IssuedQuoteChecklist::hasEligiblePricingContext()`
  accepts an explicit corrected-fuel selection without activating the record; the active default is
  untouched and unselected inactive context stays blocked. Covered by `QuoteWorkspaceTest` and
  `IssuedQuoteOutputTest` (incl. negative case). Full green run pending E1.
  > The `docs/workflow/current-batch.md` B-005 narrative still lists F1/F2 as open because it was
  > written mid-r3; the r3 fixes have since landed. That governance record needs a closing note.
- **F4** (Minor) — build warns optional package `fontaine` is absent. No fix authorised; leave as-is.
- **E1 (blocker)** — frontend build fails under restricted egress (font host 403). See above.

## Blocked on human decision (no autonomous path — do NOT guess)

- **Routing provider + commercial terms** (open decision D1) → until chosen, no live-routing verification.
- **Hosting within £50/mo, technical ownership, secrets, backup/rollback** (D12) → no deployment/staging/production.
- **Minimum visual-quality gate** — deferred, undefined; blocks the operator trial.
- **Client confirmation of provisional pricing values** (horse multipliers, shared-load %, extras).
- **Operator trial** (Cliff & Sophie under "southwest equine") — approved in principle, blocked by
  the visual gate and F1/F2.

See `docs/horse-quotes/release-1/open-decisions.md` (D1–D12) for the full decision set with owners.

## Next action

See the **Priority snapshot** at the top of `BACKLOG.md`. In short:

- **Your decisions:** H2 (accept the mockups as the trial's visual gate — quick yes/no), then H1
  (routing provider) and H4 (hosting/ownership) — the operator-trial critical path — and which
  *Next features* to green-light first (recommended: date sorting, customers page, global search).
- **Autonomous (loop-ready):** R3 placeholder honesty pass, R2 mobile re-skin, R4/R5 polish.
  Do not start *Next features* until approved.
