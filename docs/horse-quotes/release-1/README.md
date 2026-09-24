# Release 1, MVP Rescue Planning Set

This directory turns the Horse Quotes strategy reset into build-ready product and engineering planning for Release 1, MVP Rescue.

Release 1 exists to make the private portal a believable quoting MVP for one horse transport business. Its normal path is postcode-driven: an operator records an inbound transport enquiry, resolves the three route legs automatically, reviews transparent pricing, handles an exception only when needed, and issues a quote.

The product remains pre-MVP until that automatic route-mileage path is live and usable in the main flow. Manual entry of leg miles is an auditable exception, never the normal way to quote.

Release 1 delivered the route-first transport workflow and its release evidence baseline. This directory stays as the Release 1 planning set, and it does not claim staging, production, deployment, live HERE use, credentials, or business-owner acceptance.

The Release 1 staging smoke runbook is intentionally unchanged because its commands and operator-facing messages did not change.

## Source-of-truth relationship

The strategy documents remain the governing layer. This directory does not change their product direction or approve later platform work.

Read the strategy layer in its documented order, then use this directory for Release 1 delivery detail:

1. [Strategy README](../strategy/README.md)
2. [Vision and product thesis](../strategy/vision-and-product-thesis.md)
3. [MVP reset and usability gate](../strategy/mvp-reset-and-usability-gate.md)
4. [Release roadmap](../strategy/release-roadmap.md)
5. [Deployment and operations plan](../strategy/deployment-and-operations-plan.md)

The older Horse Quotes planning documents remain supporting references for domain rules and application guardrails. In particular, pricing logic stays outside controllers and Blade, transport quotes stay separate from loading-practice quotes, and the transport quote keeps its explicit depot-to-pickup, pickup-to-drop-off, and drop-off-to-depot legs.

## Documents in this set

| Document | Purpose |
| --- | --- |
| [operator-workflow-spec.md](operator-workflow-spec.md) | Defines the single operator workflow and its states from inbound enquiry to issued quote. |
| [routing-and-distance-contract.md](routing-and-distance-contract.md) | Defines the routing adapter boundary, route-result states, exceptions, overrides, and audit record. |
| [quote-creation-ux-outline.md](quote-creation-ux-outline.md) | Defines the route-first screens, UI states, messages, and non-technical operator experience. |
| [release-1-acceptance-criteria.md](release-1-acceptance-criteria.md) | Defines product, UX, operational, technical, staging, and private-production gates. |
| [implementation-slice-plan.md](implementation-slice-plan.md) | Sequences the Release 1 delivery work without expanding into later releases. |
| [staging-smoke-check.md](staging-smoke-check.md) | Defines the private staging walkthrough, evidence log, output privacy checks, and dashboard capture. |
| [open-decisions.md](open-decisions.md) | Records decisions requiring stakeholder sign-off before build work begins. |

## Release 1 boundaries

In scope is a private staff-facing transport-quoting workflow for one transporter business. The paying customer remains the transport business and the operator is the primary user.

Out of scope is public quote intake, email ingestion, a marketplace, multiple-transporter tenancy, advanced route matching, and broad reporting. These are documented in strategy only as later seams. Loading-practice quotes remain a separate service and are not combined with the Release 1 transport quote flow.
