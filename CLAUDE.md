# CLAUDE.md — start here

South West Equine Services **quotes portal**: a private, internal Laravel app that replaces a
spreadsheet-driven horse-transport quoting workflow. Long-term it becomes a transporter-facing
SaaS + marketplace, but **only the private portal is in scope now.**

This file is the single entry point. Read it, then `docs/STATUS.md`, then `docs/BACKLOG.md`.
Everything else is reference.

---

## Authoritative source order (resolves doc conflicts)

The repo has three overlapping documentation layers written at different times. When they
disagree, **higher wins**:

1. **`docs/STATUS.md` + `docs/BACKLOG.md`** — live current state and the one task list. Always current.
2. **`docs/workflow/*`** — governance, canonical intent, pricing policy, active batch scope.
   `docs/workflow/intent.md` (`## Canonical Content`) owns product scope; `docs/workflow/pricing-policy.md`
   owns pricing rules.
3. **`docs/horse-quotes/strategy/*`** — vision, MVP definition, release roadmap. Good for *why*,
   but some files predate the current code (see banners) — do not act on their "current state" claims.
4. **`docs/horse-quotes/*` (planning scaffold)** — domain rules, build sequence, release-1 specs.
   Useful detail; treat its "already built / next phase" claims as historical.

**Ignore the root `docs/*.md` mirror** (`docs/project-brief.md`, `docs/domain-rules.md`, etc.).
It has drifted from `docs/horse-quotes/*` and is not maintained. Use `docs/horse-quotes/*`.

---

## Loop protocol (for continuous/autonomous prompts)

Each iteration:

1. Read `docs/STATUS.md` (current state) and `docs/BACKLOG.md` (task list).
2. Pick the **first unchecked task under "Ready for autonomous work"** whose prerequisites are met.
   If a task cites a detailed spec (e.g. a B-xxx batch scope), follow that spec.
3. Implement it. Keep changes minimal and in scope — do not widen beyond the task.
4. **Verify** with the task's stated check (see Commands). A task is not done until its check passes.
5. Tick the checkbox in `docs/BACKLOG.md`, update `docs/STATUS.md` if state changed, and commit
   with a clear message.
6. If **no** autonomous task is ready, stop and report which **human-decision gate** (see BACKLOG
   "Blocked on human decision") is holding things up. Do not invent provider choices, hosting,
   ownership, pricing values, or a visual standard — those are the user's to decide.

Never mark a task done without a passing verification. Never fabricate test results.

---

## Hard constraints (do not violate autonomously)

- **No new runtime dependencies** (Composer or npm) without explicit user approval. Pinning a
  lockfile for *existing* deps is allowed; adding a package is not.
- **Pricing logic stays in the pricing domain** (`app/Services/Pricing/*`), never in controllers
  or Blade. See `docs/workflow/pricing-policy.md`.
- **Do not change pricing rules or values** — `pricing-policy.md` r2 governs until the user
  approves an amendment. Provisional values await client confirmation.
- **Do not copy spreadsheet errors** (out-of-range refs, `#DIV/0!`, brittle summary formulas).
- **No deployment, hosting, release, secret, or fuel-record activation** work. The private MVP is
  not deploy-authorised; those are gated on user decisions.
- **Keep loading-practice quoting separate** from transport quoting.
- Tests and pricing docs stay aligned on every pricing-related change.

---

## Commands

```bash
# Host test suite (SQLite; loads sqlite extensions for that process only)
composer run test:host

# PostgreSQL integration suite (intended runtime DB) — run in the app container
docker compose exec app php vendor/bin/phpunit -c phpunit.postgresql.xml --do-not-cache-result

# Local runtime
docker compose up --build -d
docker compose exec app php artisan migrate --seed   # startup does NOT auto-migrate

# Frontend build
npm run build
```

First-time setup needs `composer install` (vendor/ is not committed). SQLite passing does **not**
prove PostgreSQL compatibility — the real runtime is PostgreSQL.

---

## The one thing that matters most

The MVP promise is: *an operator turns an inbound enquiry into an issued quote in a couple of
minutes using automatic postcode routing, transparent pricing, and controlled overrides.*
Routing (`app/Services/Routing/HereRouteDistanceAdapter.php`) and pricing
(`app/Services/Pricing/*`) are **built** but not yet operator-verified or deployed. See
`docs/STATUS.md` for exactly what is done, blocked, and next.
