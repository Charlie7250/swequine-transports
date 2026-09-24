# Horse Quotes Strategy Layer

This directory is the strategy and product-planning layer for the South West Equine Services horse quotes app.

It exists because the current implementation and the original `Phase 1` to `Phase 10` sequence are no longer enough to describe where this product needs to go.

The current product has solid pricing-domain foundations, but it is still pre-MVP in operator terms. Staff must still enter route miles manually, which means the app does not yet deliver the main time-saving outcome it needs to replace the spreadsheet workflow day to day.

Use this strategy layer when the question is any of:

- What makes this a real MVP, rather than an internal prototype?
- Which operator workflow matters most?
- What should the next releases prioritise?
- What must be true before a real staff rollout?
- How should this portal evolve into a transporter-facing SaaS and marketplace product?

The existing files in `docs/horse-quotes/*.md` remain useful for:

- domain rules
- pricing assumptions
- technical stack and application shape
- implementation guardrails
- reference details from the earlier planning pass

Until those files are reconciled later, read the strategy layer first, then use the existing planning set as a supporting implementation reference.

## Source of truth order

Read these files in this order:

1. `vision-and-product-thesis.md`
2. `mvp-reset-and-usability-gate.md`
3. `release-roadmap.md`
4. `deployment-and-operations-plan.md`
5. `benchmark-review.md`
6. `discovery-and-question-log.md`
7. `future-platform-roadmap.md`

## Planning stance

The current planning stance is:

- Near-term product: private single-transporter portal for one operator team
- Paying customer now: one horse transport business
- MVP gate: inbound enquiry to usable quote quickly, using automatic route-mileage calculation from postcodes
- Manual leg-mile entry: fallback only, not the normal workflow
- Long-term product: transporter-facing SaaS plus marketplace
- Long-term paying customer: transporter businesses, not horse owners

## Relationship to the current app

What the current app already does well:

- keeps pricing logic out of the views
- models revisions and auditability clearly
- separates transport quotes from loading-practice quotes
- keeps shared-load pricing as an explicit domain concept

What the current app still lacks for MVP:

- automatic route mileage from postcode inputs
- operator-speed quote creation
- clear enquiry-to-quote workflow for real inbound requests
- staging and production release posture
- release gates tied to actual daily staff use

## Key assumption

This strategy layer assumes a paid routing provider becomes mandatory before MVP sign-off. A postcode heuristic or permanent manual-mile workflow is not sufficient for the core quoting product.
