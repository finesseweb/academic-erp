# College Installment Plans Page

Documentation Status: APPROVED
Implementation Status: NOT_STARTED
Implementation Approval: REQUIRED
Review Status: NOT_APPLICABLE


## Purpose
Allow authorized College finance users to create and manage installment schedules over approved payable fees.

## Route
/admin/colleges/:collegeId/finance/installment-plans

## Permissions
- fee.installment_plan.view
- fee.installment_plan.create
- fee.installment_plan.update
- fee.installment_plan.approve
- fee.installment.assign

## Scope
Assigned College

## List
- Plan Name
- Academic Session
- Program / Semester applicability
- Number of installments
- Effective dates
- Status
- Approval status

## Plan Fields
- Plan Name
- Fee Structure
- Academic Session
- Program
- Semester / Term
- Category / Admission Type if applicable
- Eligibility
- Number of Installments
- Amount or Percentage per Installment
- Due Date per Installment
- Grace Period
- Late Fee Rule
- Minimum First Installment
- Partial Payment Allowed
- Advance Payment Allowed
- Effective Dates
- Notes

## Rules
- Installment totals reconcile to approved payable amount.
- Installment plan cannot modify UNIVERSITY_FIXED amounts.
- Published schedules with financial activity are not silently overwritten.
- Rescheduling preserves prior versions/history.
- Maker-checker applies where University policy requires it.

## Realtime
REST only.

## Current Implemented Demand-Item Schedule Contract - 2026-09-12

- Installments are created against an existing `fee_demand_item`, not as reusable plan masters in the currently implemented phase.
- Actual endpoints live under `/college/{college}/fee-demands/.../installments` and `/college/{college}/fee-installments/bulk`.
- The Demand Item already carries the academic billing/source period and Standard Due Date snapshot.
- An installment schedule redistributes the remaining item amount into auditable installment amounts/due dates; it does not change the academic period or source liability.
- Benefit approval may proportionally reconcile untouched installment schedules under the documented adjustment policy.
- Implemented status: demand-item installment execution is `IMPLEMENTED / OWNER_QA_REQUIRED`; reusable plan/version/rescheduling features in the earlier specification remain future scope.

## Stable allocation + paid-floor invariant — QA hardened 2026-09-14
New schedules persist their original allocation percentage and source mode. Later benefit/adjustment rebalancing should use that stable distribution as the proportional basis. Rebalancing must always satisfy both invariants: schedule total = current Fee Head net liability, and each installment amount >= already-paid amount. If a proportional share breaches a paid floor, fix that installment at its paid amount and redistribute the remaining liability across the remaining schedule rows.

## Stable allocation basis
The schedule's original installment proportions are persistent business data. Adjustment and reversal workflows may change the effective installment amounts, but they must always recalculate from the original proportions and never from previously adjusted amounts. Paid principal is an immutable floor. A passed due date does not remove an installment or move its liability; it only makes the remaining balance overdue.
