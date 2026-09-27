# Proposed enhancements (beyond today's MVP)

Status: **proposals, not approved scope.** These are features surfaced by the dashboard redesign
mockup that don't exist in the app yet but are genuinely worth building. Recording them so they're
not lost, and so the design + build work can pick them up deliberately rather than by accident.

Relationship to the design pack: `docs/design/page-design-prompts.md` deliberately **excludes** these
(its scope is "today's backend only") so generated screens stay wireable. When a feature here is
approved, move it into the real scope and design it in.

Bigger commercial/platform direction (email ingestion, marketplace, multi-tenant) lives in
`docs/horse-quotes/strategy/future-platform-roadmap.md`. This doc is the near/mid-term **operator
UX** enhancement list.

---

## Triage at a glance

Effort is rough: **S** = mostly wiring existing data · **M** = new fields/model + UI · **L** = new
subsystem or external dependency.

| # | Enhancement | Operator value | Data / schema needed | Effort | Depends on |
| --- | --- | --- | --- | --- | --- |
| 1 | Date sorting + range filter (Today/Day/Week/Month) | Focus on the right day's work | None — controls already exist, presentation-only | **S** | A "date-state contract" (URL/query state) |
| 2 | Customers page (list + detail) | See a customer's history & contacts | `Customer` model already exists | **S–M** | — |
| 3 | Global search (jobs, customers, postcodes) | Jump straight to a record | Search query across existing tables (no index needed at this size) | **M** | — |
| 4 | Notifications | Notice quotes going stale, route failures, upcoming days | New `notifications` table + trigger rules | **M** | Rule definitions |
| 5 | Job / stop times (scheduled + ETAs) | Plan a day realistically | New time fields on transport-day membership; ETAs can use existing `duration_seconds` | **M** | Date/day model (1), provider durations |
| 6 | Calendar view of transport days | See the schedule at a glance | Reads existing `TransportDay.run_date` | **M** | 1, ideally 5 |
| 7 | Reporting / revenue trends | Understand commercial performance over time | Period snapshots or on-the-fly aggregation of revision totals | **M–L** | Agreed metrics |
| 8 | Horse records | Carry per-horse detail, not just a count | New `Horse` model linked to jobs/customers | **L** | Product decision |
| 9 | Vehicles + drivers + assignment | Assign a run to a vehicle/driver; capacity checks | New `Vehicle`/`Driver` models + transport-day assignment | **L** | Fleet policy |
| 10 | Route optimisation + full-day map/export | Cut planning time; export a day to Google Maps | Combined-day routing / provider optimisation | **L** | Routing provider (H1), 5 |

---

## Priority proposals (operator-named)

### 1. Date sorting + range filter  — effort S
The dashboard already renders **Previous / Next / Today / Day / Week / Month** controls, but they are
**presentation-only** (`resources/views/components/dashboard-date-controls.blade.php`, plain buttons,
no wiring) — the design contract explicitly defers this to "a separate approved date-state contract."
- **Build:** define query/URL state (e.g. `?period=week&anchor=2026-04-14`), have the dashboard read
  model filter transport days/jobs by that window, and reflect the active control.
- **Value:** the single highest-leverage, lowest-cost win — the UI is already drawn.
- **Note:** the operational-evidence page already uses explicit 7-day windows; reuse that windowing.

### 3. Global search  — effort M
- **What:** a top-bar search over **jobs (by customer/postcode/reference), customers, and shared
  runs**. (Not "horses" — no horse records exist; see #8.)
- **Build:** a search endpoint querying existing tables with `LIKE`/full-text; a results dropdown or
  page. At current data volumes no dedicated search index is needed; revisit if it grows.
- **Value:** removes the "which transport day was that job on?" hunt. The app currently has **no**
  way to jump to a record except via the dashboard/transport-day/shared-run links.

### 4. Notifications  — effort M
- **What:** a bell + count surfacing time-sensitive operational events, e.g.: a **pending** quote
  awaiting a customer response past N days; a **route resolution failure** needing manual review; an
  **upcoming transport day** tomorrow; a **corrected-fuel** draft not yet issued.
- **Build:** a `notifications` table (type, subject ref, read_at) + rules that generate them (a
  scheduled job or model events). Start read-only/dismissible; no external push needed for the
  private tool.
- **Value:** turns the app from passive record into something that prompts the next action — directly
  serves the "win work without dropping enquiries" thesis.
- **Decision needed:** which events qualify, and the staleness thresholds (ties to the release
  metrics discussion in `release-1-acceptance-criteria.md`).

### 5. Job / stop times (scheduled times + ETAs)  — effort M
- **What:** the mockup showed 06:30 / 08:00 stop times. Today **jobs store no times**
  (design contract, "Data limits"). Two layers:
  - **Scheduled times:** planned start + per-stop times on a transport-day membership.
  - **ETAs (cheaper):** route legs already persist `RouteResolutionLeg.duration_seconds`, so a day's
    running time can be estimated from stored provider durations without new input.
- **Build:** add time fields to the transport-day↔job pivot (`transport_day_sequence`); optionally an
  ETA rollup from existing durations.
- **Value:** makes the schedule realistic and printable for the driver.
- **Sequence:** pairs naturally with the date/day work (#1) and a calendar (#6).

---

## Secondary proposals

### 2. Customers page  — effort S–M
The `Customer` model already exists (name, contact name, email, phone, postcode, notes — used by the
loading-practice flow). A customers **index + detail** (their jobs, quotes, loading-practice history)
is a natural, low-friction addition and a prerequisite for a richer global search and CRM-lite work.

### 6. Calendar view  — effort M
A month/week calendar of transport days (and, with #5, timed jobs). Reads existing `run_date`; best
built after date-state (#1) and times (#5) exist.

### 7. Reporting / revenue trends  — effort M–L
The mockup's "Total Revenue ↑12%" needs a comparison baseline the app doesn't keep. Options: compute
period-over-period from issued/accepted/completed revision totals on the fly, or store periodic
snapshots. Extends the existing `operational-evidence` page rather than a new subsystem. **Decide the
metrics first** (what "revenue" counts, which statuses, which window) — see the delivery-plan/metrics
discussion.

---

## Larger / product-decision proposals

These need an explicit product decision before design, and several are genuine subsystems.

- **8. Horse records** — a `Horse` model (name, details, owner) linked to jobs would enable the
  mockup's "Horses" nav and per-horse detail. Meaningful schema + UX work; decide whether the
  business actually tracks individual horses vs. a per-job count.
- **9. Vehicles, drivers & assignment** — fleet models plus transport-day assignment and capacity
  checks. Needed for real day-of operations; larger, and interacts with scheduling and times (#5).
- **10. Route optimisation & full-day map/export** — **high value, hard, explicitly later.** Ordering
  a day's stops optimally (and exporting the combined route to Google Maps) could save real planning
  time — arguably one of the most valuable eventual features — but it is a genuine subsystem: it needs
  combined-day routing, the provider's matrix/optimisation capability, and interacts with times (#5)
  and vehicle capacity (#9). The intent doc already flags "export a complete day's route to Google
  Maps" as a later improvement. **Gated on the routing-provider decision (H1).** Keep "Optimise route"
  out of designs until it's a scoped batch of its own — do not let it creep in as a decorative button.

---

## Sequencing notes

- **Quick wins first:** #1 (date sorting), #2 (customers page) reuse data that already exists.
- **#3 search and #4 notifications** are the biggest UX uplift for moderate effort and unblock the
  "active tool" feel.
- **#5 times → #6 calendar** form a natural pair; do times before calendar.
- **#8–#10** are subsystems — treat each as its own scoped batch with a product decision, and note
  #10 depends on the routing provider (H1) not yet chosen.
- Nothing here is approved by being written down. Each should become a scoped item (with data model +
  acceptance) before build, the same way the workflow batches operate.
