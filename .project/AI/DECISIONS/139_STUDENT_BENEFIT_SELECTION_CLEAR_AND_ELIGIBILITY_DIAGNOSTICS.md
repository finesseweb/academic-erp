# ADR 139 — Student Benefit Selection Clearing and Eligibility Diagnostics

**Date:** 2026-09-08  
**Status:** Implemented — QA required

## Context
During Student Benefits QA, an Individual Fee Demand selected for inspection appeared visually "added" even though no scholarship/concession/waiver had been assigned. The screen also labelled schemes only as `Not eligible`, hiding the backend reason. Bulk Assignment similarly returned an empty eligible-student cohort without explaining whether candidates failed category rules, Fee Head coverage, remaining eligible amount, or were already assigned.

## Decision
1. An Individual Fee Demand selection is staged UI context only. Provide explicit `Clear` / `Clear selection` controls and automatically clear stale selected-demand/scheme state when the user edits the search query.
2. Do not relax eligibility to make a scheme appear assignable. Expose the existing backend eligibility reason in Individual mode.
3. When every scoped Individual scheme is ineligible, show a compact reason summary explaining the exact backend checks that failed.
4. Bulk Assignment continues to return only eligible candidates for controlled selection. While scanning scoped Fee Demands, aggregate ineligibility reasons and return them with the cohort summary.
5. A Bulk zero-result state must distinguish scanned, ineligible and already-assigned records and show aggregated exclusion reasons.
6. Individual and Bulk must continue to use the same authoritative eligibility rules: scheme/session/program/offering scope, candidate Reservation Category snapshot when configured, actual Fee Head coverage in the selected demand, remaining unadjusted eligible amount, active-duplicate protection, and final submit-time revalidation.

## Consequences
- QA users can immediately distinguish a temporary selection from a persisted Student Benefit record.
- `Not eligible` becomes explainable without opening logs or guessing from configuration.
- Bulk remains safe and compact while providing enough diagnostics to correct scheme or Fee Demand setup.
- No financial, RBAC, lifecycle or gross Fee Demand mutation rule changes.
