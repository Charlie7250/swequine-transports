# Feature: Transport day pipeline (parent runs with ordered child jobs)

Status: proposed (not yet built)

## Summary

Give the pipeline a parent/child shape. A single day's transport is one parent record (a
"run" / "transport day") that contains one or more individual transport jobs, shown in an
explicit order. Staff can see a day at a glance, see the jobs inside it, and reorder those
jobs within the day.

This is a viewing and organisation feature first. Grouping jobs under a parent does **not**
by itself mean their pricing is shared or split. Shared/split pricing already exists as a
separate concept and stays untouched here; whether a day's jobs should share cost is a
later question (see "Explicitly out of scope").

## Motivation

The current dashboard pipeline lists every transport job as a flat row with a status. That
answers "what jobs exist" but not "what is happening on a given day". Operationally the
work is planned as days: a driver does a run that strings several jobs together in a
sequence. Without a parent, staff cannot see a day as a unit, cannot see the order jobs
will be done in, and cannot reorder them.

## Concept

- A **transport day / run** is the parent. It has a date, an optional name, an optional
  depot, and notes.
- A run **contains ordered child jobs**. Each child is an existing transport job; the run
  defines the sequence they appear in.
- A job belongs to at most one run at a time. Jobs not yet assigned to a run remain
  visible as unassigned pipeline items.
- Reordering a run's jobs changes only their sequence, not their pricing.

## Existing building blocks to reuse

The data model already has most of this:

- `SharedRun` (`app/Models/SharedRun.php`) has `run_date`, `name`, `depot_postcode`,
  `notes`. This is the natural parent record for a transport day.
- `SharedRunAllocation` (`app/Models/SharedRunAllocation.php`) links a `SharedRun` to a
  `JobRevision` and today carries charge-split fields (`full_charge_miles`,
  `split_charge_miles`, `total_charge`, `allocation_explanation`).

What is missing for this feature is an explicit **ordering** of jobs within a run, and a UI
that renders the run -> jobs hierarchy.

### Data options for ordering

Pick one during design (do not decide here):

1. Add a `sequence` column to the run-membership so each job has a position in the day.
   `SharedRunAllocation` already models run membership; adding `sequence` there is the
   smallest change, but that table is currently charge-oriented, so consider whether
   membership and charge-splitting should be separated first.
2. Introduce a lighter "run membership" record (run_id, job_id/job_revision_id, sequence)
   distinct from charge allocation, so a job can sit in a day without implying any shared
   charge. This keeps "grouped for the day" and "shares cost" as independent facts.

Option 2 aligns better with "grouping does not mean shared pricing", at the cost of a new
small table. Resolve in the feature's own brainstorming step.

## UI

- The dashboard pipeline gains a run-grouped view: each run is a parent row (date, name,
  job count, combined status summary), expandable to its child jobs in sequence.
- Within a run, staff can reorder jobs (drag or up/down controls) and the new order
  persists.
- Staff can move a job into a run, out of a run, or between runs.
- Jobs with no run still appear (an "Unassigned" group or the existing flat list).

## Explicitly out of scope (revisit later)

- Recalculating or sharing/splitting price across a run's jobs. Pricing stays per job.
  The existing shared-load / split-charge model is a separate feature and is not changed
  by this work.
- Routing or sequence optimisation (suggesting the best order). Ordering here is manual.
- Driver/vehicle assignment to a run.

## Open questions

- Does a run group jobs or job revisions? Grouping the job (not a specific revision) is
  probably right so the run survives re-quoting; confirm against how the pipeline picks a
  lifecycle revision today (`TransportDashboardBuilder::pipelineRevision`).
- Should a run have its own status, or is its status derived from its child jobs?
- Can a job be in a future run and still be `draft`, or must it reach a certain status
  before it can be scheduled into a day?
