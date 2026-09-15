# ADR 130 — Student Benefit Assignment, Sanction and Fee Demand Adjustment

## Decision
Scholarship / Concession / Waiver master setup does not directly mutate a Fee Demand. An operational student-benefit record must exist between Scheme Setup and financial effect.

Canonical flow:

`ACTIVE Benefit Scheme → Student/Demand Selection → Eligibility Resolution → Assignment/Application → Approval/Sanction (or Automatic Sanction) → Benefit Item Snapshot → Fee Demand Adjustment → Net Outstanding`

## Scope and authority
- Operational assignment is College-side because the authoritative Admission and Fee Demand belong to the College.
- Both applicable University-owned and College-owned ACTIVE schemes can be consumed.
- University scheme scope is Academic Session + optional Program Template.
- College scheme scope is exact College Program Offering.
- Reservation/category eligibility consumes the authoritative Admission seat-allocation snapshot; it does not recalculate reservation.
- Physical and horizontal reservation categories are eligible inputs.

## Financial integrity
- Original `fee_demands.total_amount` and `fee_demand_items.amount` are never overwritten by scholarship/concession/waiver.
- Approved benefit is posted through `fee_student_benefits` + `fee_student_benefit_items` snapshots.
- `fee_demands.adjusted_amount` increases by sanctioned amount and `outstanding_amount` is recalculated.
- Benefit posting is item/head scoped and cannot exceed remaining unadjusted eligible amount.
- Multiple benefits may stack only against the remaining eligible base, in approval order. Total approved benefits can never exceed the original covered Fee Item amount.
- PENDING/REJECTED/CANCELLED records do not affect Fee Demand financials.
- APPROVED benefit cannot be directly cancelled; future financial Reversal workflow is required.

## Approval policy
- `MANUAL` scheme creates PENDING assignment requiring `college_fee_student_benefit.approve` or reject authority.
- Sanction may be lower than calculated benefit but never higher.
- `AUTOMATIC` scheme creates the auditable assignment and immediately sanctions/posts the calculated eligible amount; it never silently edits the demand without a benefit transaction.

## RBAC
Separate College permissions:
- `college_fee_student_benefit.view`
- `college_fee_student_benefit.assign`
- `college_fee_student_benefit.approve`
- `college_fee_student_benefit.reject`
- `college_fee_student_benefit.cancel`

Scheme Setup permissions do not grant sanction authority.

## Test Data Cleanup
Student Benefit / Sanction is listed before Fee Demand and Scheme setup. Clean-up deletes benefit-item snapshots first. Approved test benefits may be cleaned only before payment activity; cleanup reverses the test adjustment from the Fee Demand. Full Academic Test Reset removes benefit transactions before Scheme and Demand parents.
