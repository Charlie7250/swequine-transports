# Release 1 Implementation Slice Plan

## Delivery principles

These slices implement only Release 1, MVP Rescue. They start with the route contract and enquiry foundation, move through the route-first quote path, then add controlled exceptions and an issueable output. Each slice should keep pricing logic in the pricing domain, outside controllers and Blade, and preserve the separate loading-practice quote path.

No slice introduces public intake, email ingestion, marketplace capabilities, multi-transporter tenancy, advanced route matching, or broad reporting.

## Slice 1, route contract and enquiry capture foundation

### Goal

Establish the transport-enquiry state model and the routing and distance boundary before any provider-specific workflow is built.

### User value

An operator can record an inbound transport enquiry safely and knows what information is still needed before routing and pricing can happen.

### Scope

- add the documented draft enquiry and quote-ready enquiry concepts to the existing transport-quote domain;
- capture the Release 1 mandatory enquiry data and known gaps;
- preserve separate handling for loading-practice requests;
- establish the route-resolution request, result, status, three-leg representation, provider metadata, and audit shape from the route contract;
- define test fixtures for complete, incomplete, and ambiguous journey data; and
- establish operator-facing state and validation language from the UX outline.

### Out of scope

- live provider calls;
- manual-mile fallback controls;
- quote issue output;
- public customer forms or email ingestion; and
- changes to existing pricing policy.

### Dependencies

- stakeholder approval of the minimum issued-quote data and route-profile decision;
- existing transport quote, revision, depot, fuel, and rate domain models;
- agreement on provider metadata retention.

### Risks

- confusing enquiry state with existing quote or job state;
- accidentally mixing loading practice into transport enquiry capture;
- locking data fields before stakeholders confirm the minimum real-world input set.

### Verification approach

- state-transition tests for incomplete and complete enquiries;
- contract tests proving all route outcomes contain the three named leg slots;
- UI review of required-field and empty states;
- architecture review confirming no pricing logic moves into controllers or Blade.

## Slice 2, automatic routing and quote-ready path

### Goal

Connect the approved paid routing provider through the adapter and make a complete three-leg automatic route the normal route to quote readiness.

### User value

The operator can enter postcodes and obtain depot-to-pickup, pickup-to-drop-off, and drop-off-to-depot miles without working them out externally.

### Scope

- implement the approved provider adapter behind the documented route contract;
- validate and normalise postcode inputs while preserving originals;
- resolve, persist, and display the three named legs and provider metadata;
- expose resolved, warning, review-required, and hard-failure route states;
- record routing attempts and basic route outcome metrics; and
- make route resolution the default next action after complete enquiry data.

### Out of scope

- manual mile entry as a normal form field;
- route optimisation, route matching, or shared-load discovery;
- public intake and email ingestion; and
- final-total override.

### Dependencies

- Slice 1;
- a paid routing provider, commercial terms, credentials, and selected route profile;
- agreed anomaly and review policy;
- staging integration configuration.

### Risks

- provider coverage, quota, latency, or postcode ambiguity undermining operator trust;
- a provider response that cannot be safely mapped to the required leg contract;
- interface drift into provider-specific terminology.

### Verification approach

- adapter contract tests for resolved, warning, review-required, invalid-input, unresolved, unavailable, and invalid-response outcomes;
- end-to-end route flow using representative postcodes;
- failure simulation confirming safe operator messages and technical observability;
- audit inspection for request, response, and provider metadata.

## Slice 3, route-first draft quote and transparent pricing review

### Goal

Turn an eligible route into an understandable draft transport quote without exposing spreadsheet or technical complexity to the operator.

### User value

The operator sees exactly how the route drives the price and can save a reliable draft quote quickly.

### Scope

- create or update a revision-backed draft quote from a quote-ready enquiry;
- connect explicit route-leg miles to the existing pricing domain;
- present active fuel and rate context, leg miles, rate types, engine total, and final total;
- implement the defined route-first screen flow, issue checklist, and draft state labels;
- record enquiry-to-quote-ready and draft timing data; and
- preserve the existing revision and calculation explanation model.

### Out of scope

- issue confirmation and customer-facing output;
- manual route-mile and final-total exception controls;
- redesign of pricing policy or shared-load behaviour; and
- reporting beyond release measures.

### Dependencies

- Slices 1 and 2;
- active pricing contexts and existing pricing calculation interfaces;
- approved mandatory data and turnaround measurement target.

### Risks

- hiding material pricing evidence while simplifying the UI;
- calculating prices in request or view code;
- making a draft look issued or allowing issue before the checklist is complete.

### Verification approach

- pricing regression tests using fixed route legs and known expected calculations;
- end-to-end happy-path test from recorded enquiry to draft quote;
- visual and usability walkthrough with a non-technical operator;
- inspection of revision and calculation-explanation persistence.

## Slice 4, controlled routing and pricing exceptions

### Goal

Make routing failures, disputed mileage, and commercial adjustments safe, deliberate, and observable without diluting the automatic happy path.

### User value

The operator can still quote during a genuine exception, while understanding what changed and preserving the original calculation evidence.

### Scope

- add retry, correction, route acceptance, manual-mile fallback, individual leg-mile override, and final-total override paths;
- require structured reason capture and visible original versus changed values;
- distinguish a provider outage from invalid operator input;
- retain exception records on the draft and eventual issued revision;
- expose fallback and override measures for release evidence; and
- supply operator guidance for provider outage, ambiguity, and disputed mileage.

### Out of scope

- automated incident management;
- route matching or operational chaining;
- generic discounting features beyond the existing final-total authority rule; and
- broad administrative reporting.

### Dependencies

- Slices 1 to 3;
- approved reason categories and manual-mile policy;
- defined authority for price adjustments and fallback use.

### Risks

- manual miles becoming easier than automatic routing;
- overwriting provider evidence rather than preserving it;
- using a final-total override to mask route data or pricing defects.

### Verification approach

- red-green tests for every contract failure category and override audit requirement;
- UI test confirming manual fields are hidden until an exception action;
- regression tests preserving engine total and original route result;
- staged operator walkthrough of outage, ambiguity, and disputed-mileage scenarios.

## Slice 5, issueable quote output and release evidence

### Goal

Finish the primary path with an unambiguous issued transport quote and the evidence needed for staged rollout.

### User value

The operator can issue the reviewed quote confidently and the business can find the issued revision, route basis, pricing explanation, and exception history afterwards.

### Scope

- add issue confirmation against the exact draft revision;
- persist `issued_at` and show issued state in the existing pipeline model;
- provide the minimal issueable quote output required by stakeholder sign-off;
- preserve issued route, pricing, and override evidence immutably;
- implement the Release 1 smoke-check and evidence collection path; and
- run staging acceptance using representative enquiries and exceptions.

### Out of scope

- automated email sending or inbox ingestion;
- public customer delivery portal;
- follow-up automation;
- marketplace lead distribution; and
- Release 2 operational workflow changes.

### Dependencies

- Slices 1 to 4;
- approved customer-facing output and issue-channel definition;
- named business and technical release owners;
- staging environment, backup, rollback, and monitoring posture required by the acceptance criteria.

### Risks

- calling a saved draft an issued quote without a real issue record;
- changing issued evidence through later edits;
- promoting without a credible fallback or release rollback path.

### Verification approach

- end-to-end test from inbound enquiry to issued revision;
- revision-immutability regression test;
- staging smoke checks and timed operator sample;
- release-gate review against [release-1-acceptance-criteria.md](release-1-acceptance-criteria.md).

## Recommended build order after approval

Start Slice 1 only after stakeholders sign off the decisions due before it. Complete each slice's verification before beginning the next one. The ordering is intentional: the interface contract and data model must stabilise before provider integration, the automatic route must exist before polishing price review, and exception controls must not lead the interaction design.

