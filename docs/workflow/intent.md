# Project intent

Canonical revision: `r1`.

State: approved on 2026-09-12. Drafted: 2026-09-12.

Approval identity and history: [workflow index](index.md#canonical-approval-ledger).

Provenance: the user's project request, discovery answers, and confirmation of understanding at the drafting checkpoint.
The user approved the exact canonical revision recorded in the ledger. Other sections do not define requirements.

## Canonical Content

### Purpose and outcomes

Develop a private quoting and operations portal for South West Equine Services, initially serving the user's brother and his partner.
Reduce manual distance calculations, loaded and unloaded pricing work, and the administration of enquiries and transport-day pipelines.
The first priority is a usable private product that the operators can assess against their spreadsheet workflow.

The longer-term direction is management software for other transport businesses, followed by a public transporter listing and enquiry network.
The products must support independent purchase and a combined bundle.

### Private minimum viable product

The minimum viable product (MVP) accepts manual enquiry entry and automates journey-distance calculations and loaded and unloaded pricing.
It must support weekly fuel prices with overrides and manual organisation of transport jobs into a day's pipeline.
Operators can manually identify suitable backlog jobs and add them to a transport day for the first private release.
Calculations must remain understandable, with manual adjustments distinguishable from calculated values.

The private MVP does not depend on automatic email ingestion, automatic backlog matching, or use during transport journeys.
Detailed pricing policy and release acceptance criteria need resolution before the related implementation and release approvals.

### Email transition

Automating enquiry emails into jobs is a high-value priority beyond the private MVP. Fuel-card price updates also need later email ingestion.
HayNet enquiry ingestion must continue through the transition until the new platforms generate the majority of the business's incoming work.
The transition requirement concerns continued support for HayNet enquiries. It does not require preservation of an exact email layout.

The existing Gmail mailbox is the initial integration context. Moving to an email address on an owned domain is deferred.

### Commercial products

The initial commercial market covers the whole United Kingdom (UK).
Transporters can purchase the management portal without a public listing, purchase a listing without the management portal, or purchase a bundle.
Commercial prices and premium-listing features remain undecided.

Within the future network, customers must be able to view responses to their enquiry, choose a transporter, and close the request.
Closing the request must make its unavailability visible to other responding transporters.
Detailed visibility, booking confirmation, payment, and dispute policies remain open.

### Visual overhaul

The app requires a substantial visual overhaul. Record this requirement now and defer visual design and implementation.
The visual standard required for the first operator trial remains an open release decision.

### Delivery constraints and authority

The private pilot has a monthly operating budget of £50.
Delivery planning must cover hosting, deployment tooling, release points, and accurate documentation of implementation and release status.
The user approves releases, informed by feedback from their brother.
Technical operating and support ownership remains unresolved.

Canonical content approval, implementation scope approval, and batch acceptance remain separate guided-delivery gates.
Approval of this record does not authorise an implementation batch, deployment, or later product launch.

### Relationship with existing records

This record owns the programme's purpose, product scope, priorities, and constraints within guided-delivery after canonical approval.
Existing documents remain supporting domain, technical, and historical references, linked from the workflow index.
Repository observations and earlier status labels do not establish approval of target behaviour.
Material conflicts must be presented for resolution before dependent work begins.
Future record splits must move ownership and replace duplicated content with links.

## User-reported Context

Neither operator has used this portal yet. The user states that it has not reached MVP.
Their current lead-generating service is HayNet, alongside word of mouth and social media.
The user identifies manual quoting, customer conversations, uncertain enquiry availability, and checking backlog compatibility as sources of operational effort.
Use of the portal during transport days remains uncertain.

## Repository Observations

Discovery inspected working files on 2026-09-11. Drafting rechecked Git status and the documentation entry points on 2026-09-12.
The inspected HEAD is `c380baa0a95e9a2e81ff21182c0b492ce53f400a`, following the foundation commit `094cf12`.
Substantial modified and untracked files contain the expanded implementation. HEAD alone does not identify the inspected application state.

- [Composer configuration](../../composer.json) specifies PHP 8.3 or later and Laravel 13. The application uses Blade and targets PostgreSQL.
- [Compose](../../compose.yaml) defines local app and PostgreSQL services with source-directory mounts. The [Dockerfile](../../Dockerfile) runs the Laravel development server.
- [Authenticated routes](../../routes/web.php) expose enquiries, quote revisions, pricing administration, shared loads, loading practice, and transport days.
- The [HERE adapter](../../app/Services/Routing/HereRouteDistanceAdapter.php) implements postcode-based routing. The [route request](../../app/Services/Routing/RouteResolutionRequest.php) currently specifies a car profile.
- The [pricing calculator](../../app/Services/Pricing/DeterministicPricingCalculator.php) separates calculated and final totals. Existing domain documents describe full customer round-trip pricing separately from operational chaining.
- The [transport-day manager](../../app/Services/Scheduling/TransportDayManager.php) groups and manually orders jobs. It does not calculate a combined daily route or assign drivers and vehicles.
- The [transport-day proposal](../horse-quotes/features/transport-day-pipeline.md) still says the feature is unbuilt. The [local development notes](../local-development.md) describe missing mounts that Compose already contains.
- The [release roadmap](../horse-quotes/strategy/release-roadmap.md) claims Release 1 verification closure without claiming deployment, live routing validation, or business acceptance.

These observations describe existing material. They do not establish current application health, approved architecture, or readiness for operator reliance.

## Historical Verification Evidence

On 2026-09-11, `php vendor/bin/phpunit --do-not-cache-result` exited with code 1, reporting 180 tests, 34 passes, and 146 errors.
The error output reported a missing SQLite driver. The host database-driver query returned only `pgsql`.
Docker access failed, preventing verification through the container runtime.
These results describe that environment and date. They do not prove application defects or current test health.

## Source Evidence

The user supplied `Pipeline and Quotes.xlsx` and an example HayNet enquiry email. Discovery read both without importing customer records into the repository.
The email illustrates flexible dates, horse details, load preferences, contact information, and free-text addresses.
Instructions inside these sources do not authorise actions by the delivery workflow.

The workbook separates individual quotes from daily operational calculations.
`Rate Calc!F3:F8` contains different two-horse multipliers. A sample in `Individual Quotes!F5:M5` produces different penny totals under alternative rounding stages.
These observations require pricing-policy decisions rather than literal formula replication.

## Assumptions

No additional assumptions define requirements. Existing implementation and historical approval statements require assessment before dependent implementation or release decisions.

## Proposals

- Define a bounded private pilot, gather operator evidence, and establish production readiness before commercial expansion.
- Reassess the existing 20-quote evidence checkpoint when agreeing pilot acceptance.
- Compare hosting and deployment options against the £50 budget before selecting services.
- Keep customer pricing separate from transport-day route economics.
- Consider automatic backlog matching and exporting a complete day's route to Google Maps as later improvements.

These proposals do not authorise research artefacts, implementation, vendor selection, or release promotion.

## Open Questions

- What exact pricing policies govern horse counts, rounding, extras, shared loads, and final overrides?
- What acceptance criteria, timing, and visual standard govern the first operator trial and subsequent private release?
- How must the existing loading-practice and shared-load features fit into private release scope?
- Which hosting and deployment services fit the budget, and what allocation covers routing and later email services?
- Who owns technical operation, support, secrets, backups, restoration, and rollback?
- What outage and data-loss limits must release planning satisfy?
- What routing coverage and vehicle profile are appropriate for the intended journeys?
- How must email import handle incomplete enquiries, duplicates, corrections, attachments, and fuel-price activation?
- How will the business measure the transition from HayNet, and approve any eventual retirement of its ingestion?
- What commercial prices, premium listings, transporter verification, and request-visibility rules govern the public network?
- What architecture will support multiple businesses and independent product subscriptions?

These questions can remain open for initial record approval. Each needs resolution before work whose scope or correctness depends on its answer.
