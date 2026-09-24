# Build Sequence

## Objective

This sequence is the implementation order for the first usable version of the horse quotes app.

The order matters. Do foundations first, then pricing truth, then workflows, then reporting and hardening.

## Phase 1, repository and project bootstrap

Deliverables:

- initialise Laravel 13 project
- add Dockerfile and Docker Compose local runtime for the app and PostgreSQL
- configure PostgreSQL connection
- set up environment example file
- set up base auth for internal staff access
- create app shell layout in Blade
- create base design tokens from the agreed navy and gold direction
- copy these docs into the working repo if the implementation starts elsewhere

Acceptance focus:

- app boots locally through Docker Compose
- staff can sign in
- docs are present in the repo

## Phase 2, domain skeleton

Deliverables:

- migrations for customers, jobs, job revisions, weekly fuel prices, rate settings
- migrations for transport legs, shared runs, shared run allocations
- migrations for loading-practice quotes
- Eloquent models and relationships
- seed data shape for one depot postcode and initial rate settings

Acceptance focus:

- schema matches the approved domain model
- data relationships support revision history and shared loads

## Phase 3, pricing engine first

Deliverables:

- deterministic rate calculator service
- weekly fuel rate resolution logic
- unloaded and loaded rate derivation
- leg-based quote calculator
- calculation explanation payload generation
- tests that prove the current spreadsheet pattern for representative examples

Acceptance focus:

- the engine can price a standard single quote from three route legs
- explanation output is readable
- pricing rules live in code and tests, not only in UI code

## Phase 4, rate settings and admin truth

Deliverables:

- weekly fuel price admin screen
- rate settings admin screen
- support for a configurable shared-load percentage within rate settings
- support for a manual override of active weekly fuel input
- history view for weekly fuel entries

Acceptance focus:

- operator can update the weekly rate without touching code
- the active rate used by a quote is visible

## Phase 5, quote workspace MVP

Deliverables:

- create and edit quote workflow
- customer details entry
- horse count entry
- pickup and drop-off postcode entry
- route leg resolution through a maps adapter boundary
- manual mileage override per leg
- engine total and final total display
- quote issue action and status transitions

Acceptance focus:

- staff can create a real quote without using the spreadsheet
- the app shows how each figure was produced

## Phase 6, shared-load builder

Deliverables:

- parent shared-run workflow
- attach multiple customer allocations
- represent full-charge and split-charge legs
- apply the active shared-load percentage to genuinely shared loaded portions
- show allocation reasoning in the calculation explanation

Acceptance focus:

- partial overlap can be represented cleanly
- each customer allocation produces its own auditable total
- appended operational jobs do not bypass the full-round-trip pricing rule for each client quote

## Phase 7, loading-practice module

Deliverables:

- separate loading-practice quote flow
- package pricing configuration
- POA handling
- manual amount capture where required

Acceptance focus:

- loading practice is usable without polluting the transport quote workflow

## Phase 8, pipeline and reporting

Deliverables:

- dashboard or pipeline view by status
- revision history view
- issued, booked, completed date handling
- simple reporting for quote value, booked value, and completed revenue

Acceptance focus:

- operators can track live work without using spreadsheet tabs
- reporting is based on structured records, not hand-picked formulas

## Phase 9, bounded gauntlet loop

For each completed slice:

1. restate the exact objective
2. compare implementation against these docs
3. run reviewer passes with fixed roles
4. fix real issues only
5. rerun tests and verification
6. update docs if any rule changed

Reviewer roles:

- implementation reviewer
- pricing and domain reviewer
- UX simplicity reviewer
- break-it reviewer

Exit criteria for a slice:

- behaviour matches the written rules
- no critical findings remain
- calculation explanation is clear
- workflow is usable by a non-technical staff user

## Phase 10, release hardening

Deliverables:

- remove or hide any temporary debug-only UI that is no longer useful
- refine empty states and validation messages
- prepare a path for future email ingestion
- prepare a path for future public quote intake

Acceptance focus:

- the internal team can rely on the app day to day

## Phase 11, beta evidence baseline

Deliverables:

- named staff record 20 real transport quotes using the Beta operational evidence panel
- capture route outcomes, failure categories, original exception reasons, fallback rates, override rates, quote-ready medians, and issued medians
- record the highest-frequency blocker and exception reasons at the 20-quote checkpoint
- document the next behavioural refinement chosen by the business and technical owners

Acceptance focus:

- the evidence baseline is complete and readable
- exception counts use original audit events only
- the checkpoint does not approve the next change, deployment, or daily-reliance release
- transport-day remains proposed and out of scope for this slice

## Implementation guardrails

- Do not add dependencies without approval.
- Do not bury pricing in controllers or templates.
- Do not copy spreadsheet errors into the data model.
- Do not start public-site work before the internal tool is sound.
- Keep docs, tests, and pricing logic aligned on every pricing change.
