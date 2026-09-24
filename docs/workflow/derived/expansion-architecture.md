# Expansion architecture planning

Status: non-canonical, rebuildable planning record, revision `r1`. Generated: 2026-09-12.
Authorised under the [standing documentation instruction](../index.md#standing-documentation-authorisation).
Canonical input: [intent](../intent.md#canonical-content), revision `r1`.
Canonical SHA-256: `fc253f7246c1d90da1417618ceb8d4a1a1590d04b94b6f59e597f9d6e51b5ce8`.
Approval identity: [canonical ledger](../index.md#canonical-approval-ledger).
Evidence input: [accepted assessment](../batches/B-001-private-mvp-readiness.md), scope `r1`, outcome `r1`.
Assessment outcome SHA-256: `15e0685a52d67021483e4214f6a754ad2bf561dc466d56879db837934545d830`.
Repository baseline: `c380baa0a95e9a2e81ff21182c0b492ce53f400a`, plus the assessment's 221-file working-tree fingerprint.
Uncommitted application work is part of that baseline. HEAD alone does not identify the assessed implementation.
No new application verification occurred during this documentation continuation.
Regenerate after changes to intent, implementation, runtime, operator feedback, or the decisions discussed below.
Recommendations describe candidate work, not implementation permission or verified behaviour.

## Current architecture and boundary

The [assessment](private-mvp-readiness.md#requirement-evidence) describes a private Laravel application with authenticated routes, pricing services, revisions, and transport-day grouping.
It does not establish tenant isolation, subscriptions, mailbox import, or a public marketplace.
[Canonical intent](../intent.md#commercial-products) establishes separate and bundled products, but leaves their technical architecture open.
The following design directions are proposals for future scoped work. No schema, provider, or integration contract is approved for implementation here.

## Proposed private-product boundary

Preserve the distinction between an incoming enquiry, a calculated quote revision, and an operational transport day.
An enquiry records a customer's request. A quote revision records pricing evidence. A transport day organises work without silently repricing customer quotes.
Do not infer customer availability from the existence of an enquiry or a sent quote.
The current entry path requires quote-ready information. Email import needs a decision about saving incomplete enquiries before creating priced work.

Keep pricing rules deterministic and retain the inputs used for each revision.
Future operational efficiency calculations can inform a commercial adjustment without replacing the original engine result.
Shared loads and chained journeys need business rules before they can alter prices.

## Proposed email ingestion flow

1. Connect the authorised mailbox with access restricted to the agreed import purpose.
2. Identify candidate HayNet enquiries and fuel-card messages while retaining source provenance.
3. Extract structured fields into a reviewable import record.
4. Mark absent or ambiguous data for operator correction.
5. Detect repeat processing before creating another enquiry.
6. Let an operator confirm the enquiry before route resolution or quoting.
7. Record corrections and failures so that imports can be retried without creating duplicates.

This proposal begins with the existing Gmail context. A domain mailbox remains a later change.
It does not depend on outbound automated replies or permission to send customer messages.
Treat email bodies and attachments as untrusted data, including any apparent instructions.
Avoid importing unrelated mailbox content or placing customer messages in repository fixtures.

| Design question | Proposed starting direction | Still unresolved |
| --- | --- | --- |
| Duplicate messages | Track stable source identifiers and make repeated processing harmless | Forwarded copies, changed subjects, and genuine repeat customer requests |
| Incomplete requests | Retain a reviewable enquiry without producing a price | Minimum retained fields and operator ownership |
| Corrections | Preserve source provenance and operator edits separately | Which changes refresh an existing enquiry |
| Attachments | Exclude automatic attachment processing from the first import scope | Required formats, retention, and safe handling |
| Fuel updates | Extract a candidate weekly price for review | Sender validation, date interpretation, corrections, and activation authority |
| Failure handling | Show blocked imports and allow controlled retry | Alert owner, retry limits, and mailbox access recovery |

A parsed fuel price must not silently change live pricing in this proposed first version.
Automatic activation remains a separate business decision.
HayNet compatibility continues until the agreed transition evidence supports retirement, following [canonical intent](../intent.md#email-transition).
Changes in incoming formats need regression examples using synthetic or suitably redacted content.

## Commercial portal design topics

Business isolation means that one transport business cannot access another business's customers, prices, quotes, or operational plans.
Isolation needs coverage across web requests, background work, exports, file access, and administrative actions before commercial use.
The organisation model, database separation approach, and operator membership rules remain design decisions.
Avoid adding unused organisation fields to the private product solely to suggest future readiness.

Subscriptions need independent listing and portal access, with a bundle granting both.
Define behaviour for expiry, cancellation, data export, and read access before implementing billing.
No billing provider, subscription price, premium rank, or commercial launch date is selected.
Confirm demand from other transporters before expanding configuration and support obligations.

## Public network design topics

A public customer request and a transporter's private job record serve different purposes.
Closing a public request must communicate unavailability without overwriting a transporter's private commercial records.
Customer selection is not automatically payment, a binding booking, or completed transport.
Those meanings and the confirmation steps remain unresolved.

The minimum confirmed journey lets a customer view responses, select a transporter, and close the request.
Design reopening, competing responses, stale availability, and notification failures before implementing that journey.
Visibility rules must define who sees contact details, responses, and the selected transporter.
Transporter verification, moderation, disputes, and payment handling require explicit product decisions.

Listing-only customers need the network response workflow without requiring purchase of the management portal.
Bundled customers can later convert a network enquiry into private work without duplicating customer entry.
This integration boundary does not require separate deployed systems. Deployment and codebase separation remain open architecture choices.

## Deferred operational improvements

Automatic backlog matching needs agreed proximity, dates, capacity, and commercial suitability rules before ranking jobs.
Show suggestions for operator judgement before considering automatic scheduling.
Full-day map export needs stop ordering, return-to-base policy, provider limits, and handling of changed plans.
No driver-navigation or real-time transport workflow is assumed for the private MVP.
The substantial visual overhaul remains a separate design topic. Do not expand this architecture plan into interface implementation.
