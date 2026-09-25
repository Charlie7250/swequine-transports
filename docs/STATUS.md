# STATUS — current state and next action

**This file is the live source of truth for "where are we right now."** Update it whenever state
changes. Last updated: 2026-09-25.

For the task list see [`BACKLOG.md`](BACKLOG.md). For how to work the loop see
[`/CLAUDE.md`](../CLAUDE.md).

---

## One-line state

Private quoting portal: **core code built (routing + pricing + workflow) and the full host suite is
green (286 tests) after the build fix.** F1/F2 are resolved. Remaining MVP-to-deploy work is gated
on human decisions (routing provider, hosting/ownership, visual standard, pricing sign-off) plus
PostgreSQL verification.

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

**Not verified:** PostgreSQL runtime (`composer run test:postgres` in container — backlog C2), live
HERE routing, wider browsers, deployment, operator use. `vendor/`, `node_modules/`, `public/build/`
and `.env` are gitignored; first-time setup needs `composer install` + `npm ci`.

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

The autonomous doc + defect + build lane is clear and the suite is green. The next ready autonomous
task is **C2 — PostgreSQL integration run** (`composer run test:postgres` in the container), the one
runtime not yet verified. After that, everything reaching a real MVP *release* is gated on the human
decisions above (H1–H5) — surface them, don't invent them.
