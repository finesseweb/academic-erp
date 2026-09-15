# ADR 104 — Period-first Fee Item applicability is expressed by presence

Date: 2026-09-04
Status: Accepted

## Decision
In the period-first Fee Structure UI, an explicit `Applicable in this billing period` checkbox is no longer shown.

The user-facing rule is:

- Fee Item exists inside a Billing Period = the charge is applicable in that period.
- Fee Item is not added to a Billing Period = the charge is not applicable there.
- A different amount in another period is entered directly in that period.
- Zero, negative and fake values must never be used to represent non-applicability.

The normalized backend storage may continue to use period amount / exclusion records for compatibility, but those are implementation details and must not leak into the normal Fee Setup UX.

This rule applies consistently to University and College Fee Management.

## Why
After ADR 102 moved the UI to `Fee Structure -> Billing Periods -> Fee Items`, a second applicability checkbox became redundant and confusing. The period container itself already supplies the applicability context.

## Consequences
- Edit Fee Item dialogs contain Billing Period, Fee Head and Amount, without an applicability checkbox.
- For recurring structures, adding/reusing a Fee Head inside a period explicitly makes that period applicable and stores that period's amount.
- Future Fee Demand must treat only effective Fee Items present/applicable for the resolved Billing Period as charge lines.
- One-time Admission Fee remains one Billing Period (`One-Time Admission Charge`).
