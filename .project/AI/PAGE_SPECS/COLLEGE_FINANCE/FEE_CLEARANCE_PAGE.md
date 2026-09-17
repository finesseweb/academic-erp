# College Fee Clearance Page

## Route
`GET /college/{college}/fee-clearance`

## Purpose
Read-only authoritative enrollment financial gate for CONFIRMED Admissions. It answers whether all Fee Demand Items explicitly snapshotted as `Required for Enrollment Clearance` are currently cleared.

## Access
Permission: `college_fee_clearance.view`. Scope: exact College.

## Register
Filters: Academic Session, student/application/admission search, clearance state, page size.
Columns: Student, Admission, Programme, Required Amount, Cleared Items, Clearance Outstanding, State, View.

## Detail
Shows `CLEARED`, `PENDING` or `NOT_REQUIRED`, enrollment gate OPEN/BLOCKED, total clearance outstanding, and each required Demand Item with Demand No, Fee Head, original amount, current clearance outstanding and item state.

## Business rules
- Uses only non-cancelled Demand Items with `is_enrollment_clearance_required = true`.
- Uses the authoritative Student Fee Ledger transaction projection; no second accounting balance is stored.
- Non-required Fee Items may remain outstanding without blocking Enrollment clearance.
- A later Refund/Reversal/Debit Adjustment may change CLEARED back to PENDING immediately.
- No manual override or clearance approval action exists.
- No required items means `NOT_REQUIRED`, which does not block Enrollment.

## Downstream contract
Future Student Enrollment must call the Fee Clearance service and require `is_cleared = true`; it must not infer eligibility from payment mode, Demand status, total Fee Ledger balance, or a UI field.

## UI / scalability contract — 2026-09-16 refinement
- Register filter order is Academic Session → Programme Offering → student/application/admission search → clearance state → page size.
- Programme Offering is dependent on Academic Session and is loaded only for the selected College + Session. Laravel rejects/stabilizes a stale offering selection by returning to All Programme Offerings rather than allowing cross-scope filtering.
- Filtering and pagination remain server-side for scale; the page must not load the full student population into React.
- Session and Programme Offering changes refresh the register while preserving a visible theme-native loading state.
- Search, View and pagination actions use the established Lucide semantic icon standard.
- Inertia data refreshes must use the shared Spinner/loading treatment and disable duplicate interactions while pending; existing useful table content may remain visible beneath a non-destructive loading overlay.
