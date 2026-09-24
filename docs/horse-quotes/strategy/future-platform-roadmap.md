# Future Platform Roadmap

## Purpose

This document captures where the product can go after the private single-transporter portal is genuinely useful.

It is a vision and planning reference, not approval to start building the platform now.

## Long-term narrative

The likely product journey is:

1. private portal for one transporter business
2. polished quoting and workflow product that proves everyday value
3. transporter-facing software offer
4. transporter listing and lead product
5. route-aware transporter network with premium operational tooling

The critical idea is that the portal becomes the retention engine, while the listing and lead network becomes the acquisition engine.

## Marketplace shape

The future marketplace should optimise for transporter acquisition and retention first.

That means the platform should become attractive to transporters because it helps them:

- get found
- receive relevant enquiries
- quote faster
- trust their route-based pricing
- eventually spot work that aligns with existing journeys

Horse owners and transport customers matter, but the product should not sacrifice transporter-side quality in pursuit of generic marketplace volume.

## Future platform pillars

## 1. Transporter profiles

Potential scope:

- business profile
- service areas
- licensing and compliance signals
- horsebox type and capability
- journey types served
- review or trust markers

Value:

- supports listing-tier acquisition
- improves lead quality

## 2. Coverage areas and route intent

Potential scope:

- typical regions served
- specific date-based travel intent
- return-leg visibility
- areas of regular demand

Value:

- foundation for route-aware lead matching
- foundation for spare-capacity and shared-journey concepts

## 3. Inbound lead routing

Potential scope:

- enquiry submission
- eligibility or relevance filters
- routing enquiries to matching transporters
- lead visibility rules by entitlement tier

Value:

- direct commercial bridge from demand to transporter value

## 4. Route proximity and spare-capacity matching

Potential scope:

- identifying transporters already travelling near the requested journey
- surfacing possible efficiency gains
- future shared-load opportunity discovery

Value:

- clear differentiation from simple directories
- stronger premium tier value

## 5. Subscription entitlements

Potential scope:

- listing entitlement
- quote-tool entitlement
- analytics entitlement
- route-matching entitlement

Value:

- monetisation structure aligned with product depth

## Future domain expansion candidates

These concepts should remain documented as platform seams:

- transporter account
- organisation or tenant record
- listing entitlement
- premium quoting entitlement
- route availability intent
- lead-to-transporter matching concept
- transporter analytics

## Documentation-level future contracts

## Route and distance adapter contract

The future implementation should follow this contract.

### Inputs

- depot postcode
- pickup postcode
- drop-off postcode
- journey context
- operator override state

### Outputs

- three resolved legs
- provider metadata
- resolved miles
- failure state
- override trace

### Failure modes

- provider unavailable
- ambiguous postcode
- unsupported route
- operator override required

## Inbound enquiry model

The private portal should eventually treat inbound enquiries as a first-class concept.

### Source channels

- word of mouth
- social media
- HayNet
- direct contact

### Minimum enquiry data required to quote

- contactable customer
- pickup postcode
- drop-off postcode
- horse count or equivalent minimum pricing signal
- enough journey context to understand whether a quote is actually possible

### Draft enquiry versus quote-ready job

Draft enquiry:

- useful lead exists
- key information is still missing
- not safe to quote yet

Quote-ready job:

- minimum journey details are present
- route can be resolved or consciously overridden
- operator can produce a quote without guessing core facts

### Blocked enquiry state

An enquiry is blocked when:

- route-defining information is missing
- contact or scheduling context is too incomplete
- route provider cannot resolve the journey and manual fallback is not appropriate

## Boundaries between documented vision and approved build

## Documented vision

These are approved for planning discussion:

- multi-transporter direction
- listing tier and premium tier concept
- route/date matching concept
- transporter retention logic
- entitlement seams

## Not approved for build yet

These are not automatically approved implementation work:

- multi-tenant architecture build-out
- public marketplace launch
- subscription billing implementation
- open lead-routing engine
- route-matching engine
- public self-service quote flow

## What must be true before future-platform work begins

- the private portal is already a trusted daily product
- automatic routing is already normal
- quote workflow is fast enough to create real operational value
- transporters would plausibly pay for the tooling independently of the marketplace
- the data model is mature enough to support route intent and entitlement concepts without major rework

## Strategic caution

The biggest platform risk is trying to become a marketplace before becoming a great transporter tool.

If the operator workflow is weak, the later marketplace has no durable retention layer.

The order therefore matters:

- first, become useful
- then, become valuable to more transporters
- only then, become a network
