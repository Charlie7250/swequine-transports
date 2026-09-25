# STATUS — current state and next action

**This file is the live source of truth for "where are we right now."** Update it whenever state
changes. Last updated: 2026-09-25.

For the task list see [`BACKLOG.md`](BACKLOG.md). For how to work the loop see
[`/CLAUDE.md`](../CLAUDE.md).

---

## One-line state

Private quoting portal: **core code built (routing + pricing + workflow), not yet operator-verified
or deployed.** Two known code defects (F1, F2) and several human-decision gates block MVP sign-off.

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

## Verification (re-run 2026-09-25 in this cloud sandbox)

- `composer install` → OK; `.env` created and `APP_KEY` generated.
- `composer run test:host` → **206 passed, 79 failed** (286 tests). **All 79 failures trace to a
  single infrastructure cause, not code:** the Vite manifest is missing, so every Blade-rendering
  test returns 500. No genuine logic failure was found.
- `npm run build` → **fails**: `vite.config.js` fetches the `Instrument Sans` font from
  `fonts.bunny.net` at build time, and this environment's egress policy denies that host (403).
  Without the build there is no `public/build/manifest.json`, hence the 79 view-test failures.

⚠️ **Top loop-readiness blocker (E1 in BACKLOG):** the frontend build requires outbound font
egress that is blocked here, so a continuous loop in this environment cannot build assets or run
view tests. Fix by self-hosting/removing the build-time remote font **or** allowlisting
`fonts.bunny.net` in the session network policy. Needs a user decision.

From earlier B-005 evidence, 2026-09-17 (a build environment where fonts were reachable):
- `composer run test:host`: 203 tests, 1,417 assertions passing on in-memory SQLite.
- Frontend build succeeded; 9 synthetic browser walkthroughs reproduced P1 255.90, P2 282.27,
  P6 240.87, P7 259.39.

**Not verified:** PostgreSQL runtime, live HERE routing, wider browsers, deployment, operator use.
`vendor/` and `node_modules/` are not committed; a fresh run needs `composer install`.

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

Work `BACKLOG.md` top-down. The first ready autonomous tasks are the F1 and F2 fixes (fully
specified) and the remaining doc reconciliation. Everything that would reach a real MVP *release*
is gated on the human decisions above — surface them, don't invent them.
