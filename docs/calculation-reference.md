# Calculation Reference

## Purpose

This document captures what the spreadsheet appears to be doing so the pricing engine can be built transparently and revised safely.

## Rate source sheet

The main transport pricing logic comes from the `Rate Calc` sheet.

The workbook shows:

- `Miles Per Gallon = 22`
- `Litres Per Gallon = 4.54`
- `Maintenance Per mile = 0.05`
- `Pay per hour = 25`
- `Avg Speed unloaded = 45`
- `Avg Speed loaded = 31`

The sheet also stores weekly fuel values and derives a cost-per-mile base from them.

## Derived rate pattern

The spreadsheet calculates:

1. a fuel-driven cost-per-mile base
2. an unloaded per-mile rate
3. a loaded per-mile rate
4. a two-horse rate
5. a shared-load rate

The relevant pattern is:

- base cost per mile = fuel consumption per mile plus maintenance
- unloaded rate = base cost per mile plus unloaded add-on
- loaded rate = base cost per mile plus loaded add-on
- two-horse rate = loaded rate multiplied above the single-horse loaded rate
- shared-load rate = some split version of the higher-rate journey, depending on which legs are shared

## Workbook formula evidence

From the workbook:

- unloaded add-on is stored as `0.5555555555555556`
- loaded add-on is stored as `0.8064516129032258`
- weekly base cost is driven from fuel price and the `22 mpg / 4.54 litres` inputs
- weekly loaded and unloaded rates are then derived from that base

In the `Individual Quotes` sheet, the workbook prices:

- depot to pickup using the unloaded rate
- pickup to drop-off using the loaded rate
- drop-off to depot using the unloaded rate
- total = sum of those three charges

This is visible in formulas shaped like:

- `home to pickup = miles * unloaded rate`
- `pickup to drop-off = miles * loaded rate`
- `drop-off to home = miles * unloaded rate`

## Likely deterministic V1 formula

Use this as the initial transport engine:

1. Determine route legs in miles.
2. Round each leg to whole miles.
3. Load the active weekly fuel input.
4. Derive the weekly base cost per mile from fuel and fixed vehicle assumptions.
5. Derive the active unloaded and loaded rates.
6. Price each leg by rate type.
7. Sum the legs.
8. Apply explicit extras or overrides only when entered.
9. Store both raw calculated total and final quoted total.

## Proposed explanation payload

Each calculated revision should store a structured explanation payload that can be shown in the UI and reused in docs or AI tooling.

Suggested shape:

```json
{
  "fuel_context": {
    "week_commencing": "2026-08-10",
    "source": "manual_texaco_entry",
    "price_per_litre_inc_vat": 1.53
  },
  "rate_inputs": {
    "miles_per_gallon": 22,
    "litres_per_gallon": 4.54,
    "maintenance_per_mile": 0.05,
    "unloaded_add_on": 0.5555555556,
    "loaded_add_on": 0.8064516129
  },
  "resolved_rates": {
    "base_cost_per_mile": 0.41,
    "unloaded_rate_per_mile": 0.97,
    "loaded_rate_per_mile": 1.22
  },
  "legs": [
    {
      "label": "depot_to_pickup",
      "miles": 10,
      "rate_type": "unloaded",
      "rate_per_mile": 0.97,
      "amount": 9.7
    },
    {
      "label": "pickup_to_dropoff",
      "miles": 90,
      "rate_type": "loaded",
      "rate_per_mile": 1.22,
      "amount": 109.8
    },
    {
      "label": "dropoff_to_depot",
      "miles": 96,
      "rate_type": "unloaded",
      "rate_per_mile": 0.97,
      "amount": 93.12
    }
  ],
  "extras": [],
  "overrides": [],
  "engine_total": 212.62,
  "final_total": 212.62
}
```

## Shared-load interpretation

The `Shared Load calculator` shows a partial sharing pattern rather than a simple whole-trip split.

Observed behaviour:

- some unloaded legs are halved
- the loaded shared leg may be charged in full or split depending on where the overlap begins
- total per customer is the sum of that customer's allocated leg amounts

This supports the decision to model shared runs as a parent object plus explicit per-customer allocations.

## Loading-practice pricing

The workbook shows these fixed values:

- within 15 miles: `30`
- within 25 miles: `45`
- within 50 miles: `90`
- over 50 miles: `POA`
- on-site hourly rate: `25`
- handling and loading livery per day: `35`
- per week: `220`
- per fortnight: `410`

V1 should keep these configurable in admin settings, not hard-coded in views.

## Pricing clarity rule

Any pricing change must update all of:

- code
- tests
- this document
- the visible explanation output

If one of those is missing, the pricing change is incomplete.

## Known weak spots from the workbook

- Horse count is not consistently wired into every visible quote line.
- Manual extras appear in notes rather than in structured fields.
- Shared-load logic exists, but is not represented as a reusable domain model.
- Summary reporting is hand-assembled and should not be treated as a system-of-record design.
