# Deployment And Operations Plan

## Purpose

The current repo supports local development well enough through Docker Compose, but it does not yet define a release and operations posture suitable for real staff dependence.

This document fills that gap for the near-term private portal.

## Environment model

The near-term product should use three environments:

## Local

Purpose:

- day-to-day development
- test execution
- feature validation

Characteristics:

- Docker Compose as the default path
- developer-controlled data
- no production secrets

## Staging

Purpose:

- pre-release validation
- user-acceptance walkthroughs
- smoke testing against realistic environment configuration

Characteristics:

- private access only
- production-like environment shape
- separate database from production
- realistic routing-provider integration credentials

## Private production

Purpose:

- daily staff use
- default operational quoting system once the product is ready

Characteristics:

- private access only
- separate database
- stable URL on its own subdomain
- controlled secrets and access
- backup and rollback posture in place

## Release promotion flow

Use this release path:

1. implement and verify locally
2. deploy candidate to staging
3. run staging smoke checks
4. run operator acceptance walkthroughs on staging
5. approve release
6. deploy to private production
7. run production smoke checks
8. confirm business-critical quoting flow still works

No release should go from local straight to relied-on production once staff use becomes real.

## Release ownership

Use this ownership split for near-term releases:

- business owner:
  - approves workflow readiness
  - decides whether the release is acceptable for daily staff use
- technical owner:
  - approves staging health
  - approves production deployment readiness
  - executes release and rollback steps

No staging promotion or production release should happen without both approvals.

## Secrets and environment ownership

The product needs named ownership for:

- application secrets
- database credentials
- routing-provider credentials
- mail configuration if customer-facing sending is later introduced

Working expectation:

- one named technical owner manages environment variables
- one named business owner approves production-level third-party service changes

Do not store production credentials in repo files.

## Database expectations

Minimum expectations for staging and production:

- separate database per environment
- backup schedule defined before staff dependence increases
- restore test performed before production is considered safe
- migration procedure documented

For early private production, the minimum acceptable posture is:

- automated regular backups
- named restore owner
- documented restore steps

## Health checks and smoke checks

## Environment health checks

Each non-local environment should have at least:

- application reachable check
- database connectivity check
- authentication reachable check

## Functional smoke checks

Each staging and production release should confirm:

- login works
- dashboard loads
- quote creation screen loads
- active pricing context resolves
- route lookup dependency is reachable
- quote happy path can be exercised

## Error logging and minimum monitoring

Minimum monitoring expectations before real staff reliance:

- application errors captured centrally
- routing-provider failures visible
- failed jobs visible if queues are later used
- deployment failures visible

At minimum, the team should be able to answer:

- is the app up
- is routing working
- did the latest release break quote creation

## Rollback expectations

Every release to private production should have a rollback plan covering:

- how to revert application code
- how to handle database migrations if the release fails
- whether the routing-provider integration can be disabled or bypassed temporarily
- how staff are told to use fallback quoting if the release is unstable

Rollback must be decided before production deployment, not improvised after failure.

## Staff access model for early releases

Near-term access should stay simple:

- private staff login only
- named accounts, no shared credentials in real use
- limited user count
- no public access

Role complexity can remain minimal in the first production phase, but access still needs to be intentionally managed.

## Operational dependency register

## Mandatory near-term dependencies

### Routing provider

Why it matters:

- now MVP-critical
- normal quoting flow depends on it

Failure impact:

- quote speed drops
- manual fallback usage increases
- operator trust is at risk

Required planning response:

- explicit fallback path
- outage visibility
- provider metadata stored with quote calculation context where appropriate

### Database

Why it matters:

- system of record for quotes, revisions, and pricing history

Failure impact:

- quoting and history unavailable

Required planning response:

- backups
- restore plan
- migration discipline

### Authentication

Why it matters:

- private portal must remain private

Failure impact:

- staff blocked or access risk introduced

Required planning response:

- reliable staff account management
- secure credentials outside local defaults

## Operating assumptions for Release 1 to Release 3

- local remains Docker-first
- staging is required before meaningful rollout
- production remains private
- routing provider is treated as business-critical
- support ownership is still small-team and must therefore be documented clearly

## Open operational questions

- Who owns production deployment execution?
- Who owns routing-provider account administration?
- What backup frequency is acceptable for the business?
- What level of outage can the business tolerate during quoting hours?
- What staff fallback is acceptable during a routing-provider outage?

These answers should be locked before Release 3.
