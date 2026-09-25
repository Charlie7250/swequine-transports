# Release Roadmap

> **⚠️ Read `docs/STATUS.md` for current state before acting.** This roadmap frames releases well,
> but its status notes predate the current code (routing is now implemented) and its release
> numbering overlaps other task lists. `docs/BACKLOG.md` is the single authoritative task list.

## Roadmap purpose

This roadmap resets the project around real product readiness.

The current app has useful internal foundations, but the roadmap now assumes:

- MVP has not yet been reached
- route automation is the primary rescue priority
- releases should be framed around operator value and deployment readiness, not just implementation slices

## Release 1, MVP Rescue

## Goal

Turn the product into a believable quoting MVP by making automatic routing and fast operator quoting the centre of the workflow.

## Status

Release 1 verification closure is complete. The route-first transport workflow and release evidence baseline are documented, but this roadmap does not claim staging, production, deployment, live HERE use, credentials, or business-owner acceptance.

## User value

An operator can take an inbound enquiry and produce a usable quote quickly, without manually calculating route miles in the normal case.

## Scope

- integrate a paid routing provider through a route and distance adapter boundary
- resolve the standard three quote legs automatically from postcode inputs
- rebuild the quote creation happy path around:
  - enquiry details
  - postcode capture
  - automatic route resolution
  - transparent pricing review
  - issueable quote outcome
- move manual leg-mile entry into explicit fallback and exception handling
- make the route result and override trace visible enough to trust
- define routing failure states and operator responses
- capture the minimum metrics needed to evaluate quote speed and fallback rates

## Out of scope

- public quote intake
- automated email ingestion
- tenant model for multiple transport businesses
- advanced route matching
- broad reporting expansion beyond what helps the quoting flow

## Release gate

- automatic routing works for the normal quote path
- manual leg-mile entry is no longer the default
- a real inbound enquiry can become an issued quote quickly
- operator fallback path exists for routing exceptions
- business owner accepts the product as closer to default quoting behaviour than the spreadsheet

## Release approval owner

- go or no-go owner: business owner for workflow acceptance, technical owner for deployment readiness

## Dependency risks

- routing provider selection and commercial terms
- postcode quality from incoming enquiries
- operator trust in provider mileage output
- UI scope drift into non-critical workflow areas

## Deployment expectation

- remains private
- can be demonstrated in a stable environment
- should be exercised in staging before any production reliance

## Release 2, Beta Evidence Baseline

## Goal

Capture the first real operating evidence from named staff using the Beta operational evidence panel.

## User value

The business can see route outcomes, exception patterns, and turnaround medians from real quotes before choosing the next behavioural refinement.

## Scope

- named staff record 20 real transport quotes
- review route outcomes, failure categories, original transport exception reasons, fallback rates, override rates, quote-ready medians, and issued medians
- record the highest-frequency blocker and exception reasons at the 20-quote checkpoint
- use original audit events only for exception counts
- preserve route-first transport quoting, the three-leg rule, service-layer pricing, loading-practice separation, the existing shared-load policy, and environment-only HERE credentials
- keep transport-day proposed and out of scope for this slice

## Out of scope

- the next change approval
- deployment approval
- daily-reliance release
- transport-day feature work

## Checkpoint

At the 20-quote checkpoint, business and technical owners jointly record the highest-frequency blocker and exception reasons, assess the evidence, and explicitly choose the next behavioural refinement.

## Release gate

- the evidence baseline is complete and readable
- exception counts use original audit events only
- the checkpoint does not approve the next change, deployment, or daily-reliance release

## Dependency risks

- low sample size
- inconsistent categorisation of exceptions
- false confidence from revision-history copies
- operational drift into transport-day work

## Deployment expectation

- evidence only
- no release promotion implied
- daily reliance is not yet assumed

## Release 3, Production Readiness

## Goal

Make the private portal operationally safe and supportable as the system the business depends on.

## User value

The team can trust the product day to day, and releases can be managed without fear that small changes will break quoting operations.

## Scope

- formalise private staging and private production environment model
- define release promotion process
- define rollback process
- define backup expectations
- define ownership for secrets and environment changes
- define minimum monitoring and error visibility
- define routing-provider outage response
- create internal runbooks for:
  - staff onboarding
  - quote exceptions
  - routing fallback
  - issue escalation

## Out of scope

- external customer-facing rollout
- marketplace monetisation launch
- advanced tenant architecture implementation

## Release gate

- private production environment is supportable
- critical operational dependencies are documented
- rollback path is clear
- backups are defined
- staff onboarding does not depend on engineering memory

## Release approval owner

- go or no-go owner: business owner for production reliance, technical owner for release execution, rollback readiness, and environment safety

## Dependency risks

- under-specified production ownership
- hidden environment assumptions from local-only Docker development
- routing-provider dependency without operational fallback
- support load concentrated in one person

## Deployment expectation

- private production environment live
- private staging environment used for promotion
- release checklist followed before deployment

## Later platform track

This track is intentionally documented, not approved for immediate build.

## Goal

Evolve the private portal into a transporter-facing SaaS and marketplace that helps transporters win more work and operate more efficiently.

## User value

Transporter businesses can attract leads, quote faster, and eventually benefit from route-aware network effects.

## Scope themes

- transporter listing product
- transporter acquisition and retention strategy
- route/date matching concepts
- tenant-aware architecture seams
- subscription packaging evolution
- transporter account and entitlement model
- future lead-routing logic

## Out of scope for current build

- immediate multi-tenant rebuild
- public self-service release
- route-matching implementation
- subscription billing implementation

## Release gate for any future platform phase

- the private portal already works as a trusted daily product
- transporter-side product differentiation is clear
- listing-only and premium-tooling tiers are both understood
- route data model is mature enough to support matching concepts

## Dependency risks

- trying to launch a marketplace before the operator portal is strong
- weak transporter retention if workflow tooling is not good enough
- premature tenant architecture complexity

## Deployment expectation

- separate from the immediate portal release stream
- requires a later dedicated platform strategy and build approval
