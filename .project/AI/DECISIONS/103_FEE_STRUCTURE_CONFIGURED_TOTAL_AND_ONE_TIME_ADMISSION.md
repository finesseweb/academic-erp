# ADR 103 — Fee Structure Configured Total and One-Time Admission Charges

## Decision
Fee Structure cards at both University and College scope display a **Configured Total**.

The Configured Total is the sum of ACTIVE and applicable Fee Item effective amounts across all Billing Periods currently derived for the structure. Period exclusions are not counted. Period-specific amounts replace the shared/default item amount for that period.

This value is setup information only. It is not a student's demand, outstanding balance, paid amount, scholarship-adjusted amount, or clearance amount.

For a one-time admission-stage charge, use:
- Purpose: `ADMISSION`
- Collection Basis: `ONE_TIME`
- Billing Period: `One-Time Admission Charge`

Admission Fee is not compulsory. If an institution does not charge an admission-stage fee, no ADMISSION Fee Structure is required. Enrollment blocking remains item-driven through `is_enrollment_clearance_required`; it must not be inferred merely from `purpose=ADMISSION`.

Application/Form Fee remains a separate pre-admission concept and must not be represented as Admission Fee.

## Consequences
- University and College use the same total semantics.
- Recurring structures show the aggregate configured plan total while each Billing Period continues to show its own subtotal.
- One-time ADMISSION structures have one period only, so the Configured Total equals the sum of their ACTIVE Fee Items.
