# Dashboard Prototype Design Contract

Status: approved visual direction, integrated production dashboard

## Scope

This contract records the approved prototype and its production dashboard integration.

The supplied South West Equine Services mockup defines the composition, hierarchy, navigation pattern, colour, typography roles, density, and route presentation.

The repository defines the supported data, terminology, states, actions, and relationships. The prototype must not imply unsupported product behaviour.

## Neutral data and behaviour brief

### Core records

- A `TransportDay` stores a run date, optional name, optional depot postcode, and optional notes.
- A `TransportDay` owns ordered `Job` records through `transport_day_sequence`.
- A `Job` belongs to one customer and at most one transport day.
- A job can remain unassigned. A lost job cannot be assigned to a transport day.
- Staff can add, remove, and reorder jobs within a transport day.
- Reordering changes operational sequence only. It does not change pricing or shared-load allocation.
- Job states are `draft`, `quoted`, `pending`, `booked`, `completed`, and `lost`.
- Quoted and pending jobs use the issued revision for display and reporting.
- Booked and completed jobs use the accepted revision for display and reporting.
- Draft and lost jobs use the current working revision when one exists.
- A job revision stores the horse count, pickup postcode, drop-off postcode, pricing totals, and pricing evidence.
- A route resolution can store three ordered legs, quoted miles, and duration seconds.
- The pricing route starts at the configured depot, visits pickup and drop-off, then returns to the depot.
- Authoritative customer-facing value uses `final_total` when present. Otherwise, it uses `engine_total`.
- Quote revisions, route attempts, overrides, and operational evidence remain available in existing detail and administration views.

### Supported operator actions

- Start a new transport quote.
- Open the transport-day list and create a transport day.
- Open a transport day, add an eligible job, remove a job, or change its order.
- Open a lifecycle revision for an existing job.
- Move quotes through the existing lifecycle actions on revision views.
- Manage weekly fuel inputs and rate settings on existing administration pages.

### Data limits

- Jobs do not store scheduled stop times.
- The domain stores customer names and journey postcodes, not horse names or place display names.
- Transport days do not store vehicle or driver assignments.
- The application has no route optimisation action.
- The application has no global search capability.
- The application does not store a route-level status.
- Route totals require aggregation from the lifecycle revision's route legs.
- The current reporting builder exposes lifetime values. It does not expose seven-day revenue trends.

## Reference-to-real-data mapping

| Reference element | User need it represents | Available real data/source | Proposed adaptation |
| --- | --- | --- | --- |
| Today's routes and jobs | Understand today's workload immediately | `TransportDay.run_date`, ordered jobs | Show today's transport-day count and assigned-job count. The fixture includes one populated day. |
| Pending quotes | See customer decisions that need follow-up | `Job.status = pending`, `issued_at`, issued revision | Show the current pending count. |
| Upcoming work | See committed work that needs planning | Booked jobs, transport-day membership, run date | Count booked jobs from tomorrow through seven days ahead. Do not include unscheduled drafts. |
| Recently completed jobs | Confirm recent delivery activity | `completed_at`, accepted revision | Show a seven-day completed count with an explicit date window. |
| Revenue or quoted value | Understand current commercial value | Issued, accepted, and completed revision totals | Use quoted value in the prototype. Do not show growth percentages because no comparison metric exists. |
| Transport days | Plan work as daily parent records | `TransportDay` | Use collapsible day rows with the run date, optional name, job count, miles, and duration when available. |
| Ordered jobs and route stops | Follow the working sequence | `transport_day_sequence`, lifecycle revision | Render numbered stops between depot start and finish markers. |
| Start and finish locations | Understand the operational boundary | Day depot postcode or configured rate-setting depot | Label both ends as `Depot` with the postcode. Do not call it home without a stored designation. |
| Mileage | Estimate route scale | Stored `RouteLeg.miles` and `manual_miles` | Sum the selected revisions' authoritative route legs. Prefer manual overrides and mark incomplete totals as unavailable. |
| Estimated duration | Estimate route effort | `RouteResolutionLeg.duration_seconds` | Sum complete provider durations independently from mileage. Do not infer duration from mileage. |
| Customer information | Identify each job | `Customer.name` and contact fields | Show the customer name. Keep contact details on the job or quote detail view. |
| Horse information | Understand carrying requirements | `JobRevision.horse_count` | Show one horse or the numeric horse count. Do not invent horse names. |
| Collection and drop-off | Understand the journey | Pickup and drop-off postcodes | Present both endpoints within each ordered job. Do not invent stop types beyond pickup and drop-off. |
| Job and quote statuses | Identify progress and next action | Configured job statuses | Use restrained status pills with the repository labels. |
| New quote action | Start the most common workflow | `quotes.create` | Place `New quote` in the header only. |
| View job action | Inspect lifecycle evidence and take state actions | `jobs.revisions.show` | Use a job-detail action when a real revision identifier exists. Prototype controls remain labelled as preview actions. |
| Add job action | Assign unassigned work | Transport-day add-job endpoint | Surface unassigned work beside the schedule. Route the integrated action through a transport-day detail view. |
| Optimise route | Reduce route planning effort | No supported capability | Omit it. Manual reordering remains available on transport-day detail pages. |
| Global search | Find jobs, customers, or horses | No search endpoint or index | Omit it. Use a clear page title and primary action in the header. |
| Sidebar sections from the mockup | Navigate the operational product | Dashboard, quotes, transport days, loading practice, fuel, rates | Use only real destinations. Do not add unsupported Customers, Horses, Vehicles, or Reports pages. |
| Notifications | Notice urgent changes | No notification model | Omit the notification bell and count. |

## Prototype design

The prototype uses a reusable operator shell with a fixed desktop sidebar and a compact mobile navigation control.

The main region contains a greeting, a primary action, five supported summary cards, upcoming transport days, and unassigned work.

The first transport day opens by default. Later days remain collapsed. A quiet day shows a calm empty state inside the same component.

Each expanded day renders depot start, numbered jobs, and depot finish. Each job shows the customer, horse count, journey postcodes, status, and quoted value.

The prototype fixture covers mixed statuses, long content, one-horse and two-horse jobs, an unassigned job, missing route information, and a quiet day.

## Focused refinement contract

- The page title is time neutral. The supporting introduction uses the signed-in user's first name when available.
- Summary wording states the timeframe or operational scope for every count and financial value.
- Job rows describe collection and drop-off using stored journey postcodes.
- Each job row exposes a visible action with hover and keyboard focus treatment.
- Quoted and pending jobs remain in the scheduled fixture. The scheduling service permits every status except `lost`.
- `New quote` remains the page's primary action and no longer appears as a duplicate sidebar destination.
- Weekly fuel and rate settings sit under an administration heading in the shared sidebar.
- The unsupported decorative sidebar shape is removed because the repository contains no approved brand artwork.
- The schedule header includes previous, next, Today, Day, Week, and Month controls for visual evaluation.
- Date and period controls remain presentation-only in both the prototype and production dashboard. Wiring requires a separate approved date-state contract.

## Component contract

- `operator-shell` owns the common header, sidebar, mobile navigation, and content frame.
- `operator-sidebar` owns the allowed destinations and active state.
- `dashboard-summary-card` owns one compact operational metric.
- `transport-day-card` owns collapsed and expanded day presentation.
- `route-stop` owns depot and job timeline rows.
- `job-status` owns the six supported status treatments.

These components must accept data through Blade attributes. They must not query models.

## Responsive contract

- Desktop keeps the sidebar visible and constrains the main content width.
- Narrow laptop and tablet widths reduce card columns and route-row density.
- Mobile replaces the fixed sidebar with an accessible menu and stacks route details.
- Long names and locations wrap without hiding status or actions.
- Native `details` and `summary` elements provide collapsible days without JavaScript.

## Production integration contract

- The fixture-backed prototype remains available at its authenticated preview route.
- Production `/dashboard` uses a dedicated `OperatorDashboardBuilder` read model.
- The read model selects lifecycle revisions and applies explicit seven-day windows.
- The dashboard uses real jobs, transport days, route evidence, customers, and statuses.
- The authenticated layout owns one shared sidebar and top bar across production pages.
- Operational evidence remains available under Administration and does not dominate the dashboard.
- Unsupported metrics and actions do not appear.
- Desktop, narrow, and mobile production renders receive visual review before hand-off.

## Integration boundary

Production integration now uses a dedicated dashboard read model. It selects lifecycle revisions, aggregates complete route evidence, and applies explicit date windows.

The integration preserves existing detail and administration routes. Operational evidence now has a dedicated administration page.

The date range, Today, Day, Week, and Month controls remain visual-only. Their query and URL state will be wired in a later approved change.
