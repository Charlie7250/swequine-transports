# Design Direction

## Design goal

The app should feel premium, calm, and practical. It is an internal operational tool first, but it should still reflect the South West Equine Services brand cleanly enough that parts of it could later face customers.

## Brand cues from the supplied assets

The supplied materials suggest:

- a strong deep navy base
- a warm gold accent
- monochrome black, white, and grey logo variants
- an elegant horse-line logo and script wordmark

The tone is more refined and equestrian than generic admin SaaS.

## Visual direction

Use a restrained palette:

- primary: deep navy
- accent: warm gold
- background support: off-white, soft stone, muted grey
- text: charcoal or near-black on light surfaces

Avoid:

- bright tech-startup colours
- purple-heavy defaults
- dashboard clutter
- excessive gradients or glossy effects

## UI principles

- prioritise clarity over decoration
- keep the quote workflow linear and obvious
- make calculation details visible without overwhelming the operator
- use clear section breaks for customer details, route legs, pricing, and status
- make important numbers easy to scan

## Layout direction

V1 should favour:

- a clean top bar with logo treatment
- a narrow, readable content width for forms
- card or panel sections with strong spacing
- obvious side-by-side comparison between calculated and final quoted totals where relevant
- a collapsible calculation explanation panel

## Typography direction

The UI should feel slightly more polished than default Bootstrap-style admin panels. Keep typography simple and legible. The logo already carries the expressive brand personality, so the application typography can stay restrained.

## Temporary build-stage transparency

During early implementation, the interface should intentionally surface more internal logic than a final customer-facing product would. This includes:

- active weekly fuel source
- resolved unloaded and loaded rates
- per-leg mile counts
- rate type applied to each leg
- manual override indicators
- engine total versus final total

This is a feature, not clutter, while pricing is still being validated.

## Public-facing future

If a later public quote page is added, it can become softer and more brand-led. The internal app should remain operational and efficient first.
