# Release 1 Staging Smoke Check

Use this checklist for the controlled private staging walkthrough. It records evidence only. It does not authorise production promotion, change routing credentials, or send customer email.

## Preconditions

- Confirm the staging database and routing credentials are the staging-only configuration owned by the technical owner.
- Confirm a named staff account can sign in and an unauthenticated browser is redirected to the sign-in page.
- Confirm one active weekly fuel record and one active rate setting are available.
- Identify five representative transport enquiries, including one provider-outage or unresolved-route scenario and one reviewed or overridden scenario.
- Record the business owner and technical owner who will review the resulting evidence.

## Staging configuration

The staging environment must provide the following configuration. Values marked
as placeholders are supplied by deployment secret management and must not be
committed.

```dotenv
APP_ENV=staging
APP_KEY=base64:replace-with-a-staging-key
APP_DEBUG=false
APP_URL=https://staging.example.invalid
DB_CONNECTION=pgsql
DB_HOST=staging-postgres.internal
DB_PORT=5432
DB_DATABASE=sweq_transports_staging
DB_USERNAME=staging_app
DB_PASSWORD=replace-in-staging-secret-management
SESSION_DRIVER=file
HERE_API_KEY=replace-in-staging-secret-management
HERE_GEOCODING_URL=https://geocode.search.hereapi.com/v1/geocode
HERE_ROUTING_URL=https://router.hereapi.com/v8/routes
```

HERE is injected by staging secret management. It must never be committed,
rendered, persisted, audited, logged, or included in a screenshot. HERE setup
uses an authorised Geocoding API and Routing API v8 application, UK postcode
coverage, and a staging quota. Do not call HERE, provision accounts, deploy, or
set secrets as part of this checklist.

## Happy-path sample

For each representative enquiry:

1. Record the start time before entering the transport enquiry.
2. Record customer contact, source, date or date-to-be-arranged selection, and constraint acknowledgement.
3. Resolve the route and confirm the three named legs are present.
4. Record the route-ready time from the route result and inspect the active fuel, rate, engine total, and final total.
5. Review the issue checklist, tick the confirmation naming the displayed revision, and issue the quote.
6. Open the staff-held issued quote output. Confirm the quote reference, issued time, customer, journey, three legs, provider, totals, and any exceptions are present.
7. Use the browser print action to preview a PDF. Do not email the output from the portal.

Record each result in the evidence log with these fields:

| Quote reference | Enquiry start | Route-ready time | Issued revision and time | Route outcome | Exception reason | Operator result |
| --- | --- | --- | --- | --- | --- | --- |
| | | | | | | |

## Exception sample

1. Trigger or use a controlled provider-unavailable or unresolved route result.
2. Confirm the operator sees the exact safe message for the case:
   - invalid postcode: `The route could not be calculated because one or more postcodes need attention.`
   - provider unavailable: `Route lookup is temporarily unavailable. Your enquiry has been saved.`
   - review required: `This route needs your review before it can be used for pricing.`
   - authorised fallback action: `Use manual miles because route lookup is unavailable`
3. Confirm the enquiry remains saved.
4. Where an authorised operator uses manual miles, record the reason, changed legs, actor, and time.
5. Where an authorised operator accepts a review-required route or overrides the final total, confirm the decision and reason remain visible on the issued revision.
6. Record the exception result in the same evidence log.

## Private-output checks

1. In a signed-out browser, open an issued-quote URL and confirm the application redirects to sign-in.
2. In a signed-in staff browser, open the same output and confirm it is available.
3. Confirm there is no public quote URL, customer portal, email-send control, or email-ingestion control in this flow.

## Issued quote and separation checks

- Print a happy-path issued quote and confirm the browser print preview contains
  the issued quote reference, issue time, customer, journey, three legs, route
  provider, totals, and recorded exceptions.
- Reopen the issued quote after changing the draft workspace and confirm the
  issued output is immutable.
- Confirm loading-practice records do not appear in transport enquiry, route,
  quote revision, or routing evidence screens. Loading practice remains a
  separate service line and table.

## Dashboard evidence

After the sample, capture the Release 1 routing evidence dashboard. Record the
displayed route attempts, each non-empty route outcome, failure categories,
manual fallback rate, route-leg override rate, final-total override rate,
issued quote revisions, median enquiry-to-quote-ready time, and median
enquiry-to-issue time.

The dashboard metrics are defined as follows:

- Route attempts count qualifying `RouteResolution` rows tied to a transport
  enquiry. Empty outcomes are grouped as `unknown`.
- Failure categories include only `invalid_input`, `unresolved`,
  `provider_unavailable`, and `provider_response_invalid`. A failure detail
  code is used only when it is one of those values, otherwise the overall
  status is used.
- Rate denominators are distinct route resolution IDs referenced by a
  `JobRevision`. Each numerator is the distinct resolution IDs with one or
  more matching exception audits, so repeated audits, legs, and revisions are
  counted once.
- Turnaround records require the complete enquiry, job, revision, and route
  chain. Quote-ready uses the earliest qualifying revision. Issued uses the
  job issue time. Missing endpoints are excluded.
- A median is in whole seconds, with an even-sized sample using the rounded
  average of the middle pair. `N/A (0 records)` means no eligible complete
  chain exists yet, not zero elapsed time.

Attach the dashboard capture and completed evidence log to the staging review. Any failed smoke step requires a recorded disposition from the business owner and technical owner before promotion is considered.
