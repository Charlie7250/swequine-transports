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

## Last recorded verification (not re-run this session)

From B-005 evidence, 2026-09-17:
- `composer run test:host`: **203 tests, 1,417 assertions passing** on in-memory SQLite.
- Frontend production build (`npm run build`): succeeded.
- 9 synthetic browser walkthroughs on disposable SQLite + a loopback routing fixture: expected
  totals reproduced (P1 255.90, P2 282.27, P6 240.87, P7 259.39).

**Not verified:** PostgreSQL runtime, live HERE routing, wider browsers, deployment, operator use.
`vendor/` is not committed; a fresh run needs `composer install`.

## Known defects (autonomous fixes available — see BACKLOG)

- **F1** — an unsupported horse count (>2) correctly blocks an automatic quote but shows **no
  visible manual-review message** on the route-review page. Full spec:
  `docs/workflow/current-batch.md` (B-005 r3, F1).
- **F2** — a corrected-fuel draft **cannot pass the issue checklist** while its selected corrected
  fuel record is inactive, so a correction can't be issued without activating the record. Full spec:
  same file, F2.
- **F3** — durable screenshot capture for walkthrough evidence was previously unavailable (tooling
  gap, may already be solvable here). Full spec: same file, F3.
- **F4** (Minor) — build warns that optional package `fontaine` is absent. No fix authorised;
  leave as-is.

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
