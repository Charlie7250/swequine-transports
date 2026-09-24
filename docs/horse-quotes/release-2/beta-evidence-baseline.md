# Release 2, Beta Evidence Baseline

Release 1 verification closure is complete. This slice captures evidence only. It does not approve the next change, deployment, or daily-reliance release.

## Purpose

Capture the first Release 2 operating evidence for the route-first transport workflow.

## What staff do

- named staff record 20 real transport quotes
- use the Beta operational evidence panel while those quotes are recorded
- review route outcomes, failure categories, original transport exception reasons, fallback rates, override rates, and quote-ready and issued turnaround medians

## Counting rules

- count exception reasons from original audit events only
- revision-history copies do not inflate counts
- empty data displays exactly `No transport exception reasons recorded.`

## 20-quote checkpoint

At the checkpoint, business and technical owners jointly record the highest-frequency blocker and exception reasons, assess the evidence, and choose the next behavioural refinement.

## Guardrails

- keep route-first transport quoting, the three-leg rule, service-layer pricing, loading-practice separation, and the existing shared-load policy
- use environment-only HERE credentials
- do not make live HERE calls
- do not add dependencies
- keep the transport-day feature proposed and out of scope
