# Technical Decisions

## Chosen stack

- Backend: `PHP 8.3+`
- Framework: `Laravel 13`
- Rendering: `Blade`
- Database: `PostgreSQL`
- Frontend behaviour: minimal JavaScript only where the workflow benefits from it
- Hosting shape: standalone app on its own subdomain

## Why this stack

- It is simple to host.
- It suits a small internal operational tool.
- It leaves room for later website embedding or adjacent WordPress use.
- Laravel provides a strong default structure without requiring a heavy frontend framework.

## What not to build first

- Do not build this as a WordPress plugin.
- Do not start with a SPA architecture.
- Do not design around email automation before the internal quoting flow works.
- Do not bury pricing logic in prompts or opaque AI calls.

## Architecture shape

Use a single Laravel application with clear service boundaries inside it:

- auth and staff access
- quoting domain
- pricing engine
- route and distance adapter
- reporting
- future email ingestion boundary

## External boundaries

The system will eventually need:

- a maps or routing provider for postcode distance lookup
- an email ingestion mechanism for weekly fuel-rate emails and incoming quote emails

These should be modelled as boundaries from the start, but kept hard-coded or stubbed in early development where practical.

## Data persistence

PostgreSQL should store:

- jobs
- revisions
- route legs
- calculated outputs
- calculation explanations
- fuel-price history
- settings
- customer and quote metadata

## Authentication

V1 should use a basic internal staff login. Keep it simple. The first release does not need role complexity beyond what is required to keep the app private.

## Embedding strategy

The app should stand alone first. Later options:

- link from an existing website
- embed a controlled public quote screen in an iframe
- expose a narrow quote-entry flow on a future website

## Developer tooling stance

Current-docs helpers such as Context7 can be used during implementation as a developer aid. They are not part of the runtime design.

## Dependency policy

No new runtime or development dependency should be added without explicit user approval. Favour Laravel defaults, the standard library, or a small local implementation where reasonable.
