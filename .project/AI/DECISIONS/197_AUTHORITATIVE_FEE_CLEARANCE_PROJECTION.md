# ADR 197 — Authoritative Fee Clearance Projection

## Status
IMPLEMENTED / OWNER QA PASS / CLOSED — 2026-09-16

## Decision
Fee Clearance is the authoritative financial gate between the Fee Phase and Student Enrollment. It is **derived**, not manually approved and not stored as a mutable `cleared` flag.

For one CONFIRMED Admission + Academic Session, only non-cancelled `fee_demand_items` whose immutable snapshot has `is_enrollment_clearance_required = true` participate in the gate. Their current liability is derived from the same authoritative transactions exposed by the Student Fee Ledger: Demand, approved Benefit, active Late Fine, posted Payment allocation, manual Debit/Credit Adjustment, Payment Reversal, Adjustment Reversal and posted Refund.

States:
- `PENDING`: at least one required item has positive current liability.
- `CLEARED`: required items exist and every required item has zero/non-positive current liability.
- `NOT_REQUIRED`: no active required item exists for the Admission + Session. This does not block Enrollment.

`CLEARED` and `NOT_REQUIRED` both expose `is_cleared = true` to the future Student Enrollment service. `PENDING` exposes false.

A later refund, payment reversal or debit adjustment automatically re-opens the gate because clearance is recalculated from authoritative financial history. No stale clearance approval can survive a financial correction.

## Scope and RBAC
College-scoped read access uses `college_fee_clearance.view`. SUPER_ADMIN and COLLEGE_ADMIN receive the permission by migration. The Fee Management sidebar exposes Fee Clearance only when the user has this permission.

## Non-goals
- No Student record or enrollment is created in this milestone.
- No manual clearance override/approval is introduced.
- No duplicate clearance accounting table is introduced.

## Owner QA gate
1. Required item unpaid -> PENDING / enrollment gate BLOCKED.
2. Partial payment -> PENDING with exact remaining required-item liability.
3. Full payment or valid Benefit/Credit Adjustment reducing every required item to zero -> CLEARED / gate OPEN.
4. Refund, receipt reversal or Debit Adjustment after clearance -> PENDING again.
5. Non-clearance-required outstanding items do not block this gate.
6. No required items -> NOT_REQUIRED / gate OPEN.
7. Cancelled demands do not participate.
8. RBAC hides sidebar and direct URL returns 403 without `college_fee_clearance.view`.
9. Reconcile each required-item amount with Student Fee Ledger entries.

Student Enrollment must not begin until this owner QA is accepted.
