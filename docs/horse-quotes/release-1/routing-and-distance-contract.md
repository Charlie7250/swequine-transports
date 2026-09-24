# Routing And Distance Contract

## Purpose

This contract defines the Release 1 boundary between the transport quoting domain and a paid routing provider. It documents an interface and audit expectation, not a provider-specific implementation.

The adapter resolves the three pricing-authoritative transport legs from postcodes. It must keep routing concerns outside controllers and Blade. Pricing consumes the resolved route record through the quoting domain; neither a controller nor a view calculates mileage or applies rate logic.

The contract applies only to transport quotes. Loading-practice quotes do not call this route contract as part of their Release 1 flow.

## Route request

The calling quote workflow supplies one `RouteResolutionRequest` for a single transport enquiry or quote revision.

| Input | Requirement | Notes |
| --- | --- | --- |
| `resolution_id` | Required | Stable identifier for this routing attempt. |
| `enquiry_id` or `quote_revision_id` | Required | Links routing evidence to the business record without making the provider responsible for quote state. |
| `depot_postcode` | Required | Configured operational start and end location. |
| `pickup_postcode` | Required | Customer-supplied pickup location, retained in its original form. |
| `drop_off_postcode` | Required | Customer-supplied drop-off location, retained in its original form. |
| `requested_at` | Required | UTC timestamp for audit and provider diagnostics. |
| `route_profile` | Required | The approved vehicle and routing profile. Its exact provider setting is an open decision. |
| `request_context` | Required | Non-secret correlation information, such as application version and operator-triggered flag. |

The request must preserve the raw postcode inputs. A normalised postcode or provider geocoding result supplements, but never replaces, the original customer-provided value.

## Required three-leg representation

Each successful resolution returns exactly these named legs in this order:

| `leg_type` | Origin | Destination | Pricing role |
| --- | --- | --- | --- |
| `depot_to_pickup` | Depot | Pickup | Unloaded outward journey |
| `pickup_to_drop_off` | Pickup | Drop-off | Loaded journey |
| `drop_off_to_depot` | Drop-off | Depot | Unloaded return journey |

Each `ResolvedRouteLeg` must include:

- `leg_type` from the table above;
- `origin_input` and `destination_input`;
- `origin_resolved` and `destination_resolved`, where the provider supplies them;
- `status`;
- `distance_metres` as returned or normalised from the provider;
- `quoted_miles`, rounded to whole miles for the existing transport-pricing rule;
- `duration_seconds` when available, for operator context and diagnostics only;
- `provider_route_id` or equivalent provider correlation reference when available;
- `warnings` as structured codes with human-readable messages; and
- `failure` detail when the leg is not usable.

The quoting domain must price the explicit `quoted_miles` per leg. It must not re-create a total distance from a provider response or infer leg roles from an array position.

## Route result

A `RouteResolutionResult` represents one attempt and includes:

| Output | Requirement |
| --- | --- |
| `resolution_id` | Required and equal to the request identifier. |
| `overall_status` | Required status from the enum below. |
| `legs` | Required array containing all three named legs, including failed legs where relevant. |
| `provider_metadata` | Required, subject to failure-stage availability. |
| `normalisation_metadata` | Required when any input is normalised, corrected, or geocoded. |
| `warnings` | Required array, empty when there are no warnings. |
| `failure` | Required for hard-failure results, absent otherwise. |
| `resolved_at` | Required UTC timestamp. |
| `operator_action_required` | Required boolean derived from the status. |
| `pricing_eligible` | Required boolean. True only when the result may create a quote-ready enquiry without manual fallback. |

## Status values

| Status | Meaning | `pricing_eligible` | Operator action |
| --- | --- | --- | --- |
| `resolved` | All three legs resolved with no material warning. | Yes | Review normally. |
| `resolved_with_warning` | All three legs resolved, but a non-blocking condition needs visibility. | Yes | Read warning and continue, unless business judgement requires review. |
| `operator_review_required` | All three legs resolved, but provider or application evidence means an explicit route decision is required before pricing. | No | Accept, correct input and retry, or use fallback. |
| `invalid_input` | A required route input is missing or cannot be validated. | No | Correct or collect the input. |
| `unresolved` | One or more legs could not be resolved from otherwise valid inputs. | No | Correct and retry, or use approved fallback. |
| `provider_unavailable` | Provider authentication, quota, service, network, or timeout failure prevents resolution. | No | Retry later or use approved fallback. |
| `provider_response_invalid` | The provider response cannot be trusted or mapped to the contract. | No | Do not use it. Escalate and use approved fallback only if necessary. |

The adapter must never report `resolved` when any of the three required legs is absent, invalid, or lacks a whole-mile value for transport pricing.

## Severity classification

### Hard failure

A hard failure prevents automatic route pricing. It occurs for `invalid_input`, `unresolved`, `provider_unavailable`, and `provider_response_invalid`, or if any required leg is incomplete. It does not automatically prevent an eventual quote, because the controlled manual-mile fallback may be used when the business chooses to proceed.

### Warning

A warning is visible but does not itself require a route override. Typical warning categories are provider normalisation of a postcode, a route returned with provider advisory data, or a configured mileage anomaly threshold being crossed while all legs remain valid. The threshold values and their policy owner are recorded as open decisions.

### Operator review required

This state means a route exists but must not silently become the pricing basis. It applies when the provider reports location ambiguity, the application detects an unresolved material anomaly under an approved policy, or an operator is being asked to rely on a route whose location resolution is not sufficiently certain. The operator must accept the route explicitly, correct the input and retry, or use a documented fallback.

## Failure modes and expected workflow response

| Failure mode | Contract status | Required UI response | Audit requirement |
| --- | --- | --- | --- |
| Missing or malformed postcode | `invalid_input` | Mark the specific input and explain that all three legs require valid postcodes. | Input snapshot and validation code. |
| No route for one leg | `unresolved` | Identify the failed leg and offer correction, retry, or exception handling. | Provider response, leg failure code, retry history. |
| Timeout, outage, quota, or authentication failure | `provider_unavailable` | State that the provider could not be reached. Do not blame the operator's postcode without evidence. | Provider error category, request time, latency, retry history. |
| Incomplete or inconsistent provider response | `provider_response_invalid` | State that the route result cannot be used and offer retry or approved fallback. | Sanitised response fingerprint, validation failure detail. |
| Ambiguous resolution | `operator_review_required` | Show both the entered and resolved location context and require a decision. | Input, normalisation data, decision and operator identity. |

## Override behaviour

There are two distinct overrides. They must not be combined or hidden.

### Route-mile override

A route-mile override changes one or more `quoted_miles` values used by the pricing engine. It is permitted only through the exception path. It must retain the original provider result where one exists.

Each overridden leg requires:

- original route result and original `quoted_miles`, when available;
- replacement whole-mile value;
- a structured reason category and free-text explanation when the category alone is insufficient;
- operator identity and UTC timestamp; and
- whether the action was fallback because automation failed, or an adjustment to an otherwise resolved route.

A manual fallback must result in a complete three-leg record. It may retain provider-resolved legs and replace only failed or disputed legs, but no leg may be silently inferred.

### Final-total override

A final-total override is a pricing-domain decision after the engine calculation. It does not alter route evidence or mileage. It retains the engine total and records the authoritative final total, reason, operator identity, and time. Customer-facing output uses the final total, while audit and reporting retain both values.

## Provider metadata requirements

Every resolution attempt must retain sufficient non-secret metadata to explain and diagnose its result:

- provider name and product or service tier;
- provider API or data version where available;
- provider request or transaction identifier where available;
- routing profile and material routing options used;
- request timestamp, response timestamp, and latency;
- provider response status or error category;
- raw distance unit and the conversion used;
- normalisation, geocoding, and selected-location evidence where available;
- provider route identifiers per leg where available; and
- a sanitised response fingerprint or retained raw response according to the data-retention decision.

Provider credentials, API keys, and other secrets must never appear in quote records, audit logs, screenshots, or user-facing messages.

## Audit and retention requirements

A route result used for a draft or issued quote must be reproducible as evidence even if a provider later changes its data. The audit record must retain:

- request input snapshot and original postcode values;
- the complete three-leg result or complete failure record;
- provider metadata and status classification;
- all warnings and review decisions;
- every route-mile override and final-total override;
- the route data used by the pricing revision; and
- the actor and time for all operator decisions.

Issued quote evidence is immutable. A correction after issue must produce a later revision; it must not rewrite the route or pricing evidence of the issued revision.

