# ADR 156 — Student Benefit Register grouped by Admission/Application

Date: 2026-09-09
Status: Implemented, QA Pending

## Context
The Student Benefit Register was already compacted in ADR 146, but the parent-row grouping key remained `fee_demand_id`. This still produced duplicate-looking rows when the same admitted student had benefits against different Fee Demands/billing periods (for example Admission Initial and Semester 1), even though the Application/Admission was the same. The duplication was especially confusing when one benefit was assigned individually and another through Bulk Assignment.

## Decision
The Student Benefit Register parent row is now grouped by the canonical Admission/Application identity, not by Fee Demand and not by benefit creation mode.

- Primary grouping key: `admissions.id`.
- Fallback grouping key: application number only when an admission identifier is unavailable.
- Individual and Bulk-created benefits for the same admission are intentionally shown under the same parent row.
- Parent row continues to show Student, Discipline, Age, Admission/Application, Programme, aggregate Benefit count, aggregate Benefit amount and combined Status.
- Parent row summarizes the number of distinct Fee Demands and the visible billing periods.
- `Benefits (N)` child rows preserve the exact Scheme, Fee Demand, Billing Period, assignment Source (`INDIVIDUAL` / `BULK` or persisted application mode), Type, Amount, Status and action/detail workflow.
- Approval, rejection, removal, installment adjustment, eligibility and financial posting remain benefit/demand-specific and are not merged financially.

## Pagination and filtering
Server pagination counts Admission/Application parent groups, not individual benefits or Fee Demands. Existing Session, Programme Offering, Discipline, Billing Period, Scheme, Status and Search filters remain authoritative. If a Billing Period/Scheme/Status filter is applied, only matching child benefits participate in the displayed parent group and aggregates.

## Invariants
- Register grouping is presentation/operational organization only; it does not alter Fee Demand ownership or financial records.
- Gross Fee Demand remains immutable from Student Benefits.
- Benefits remain attached to their exact Fee Demand and Fee Demand Item(s).
- Batch is not introduced into this pre-enrollment-capable register; lifecycle-aware Session/Programme/Discipline context remains the standard.
- High-volume student registers should avoid repeating one parent identity solely because multiple transactional child records exist.

## QA
1. Same Application/Admission, Admission Initial benefit + Semester 1 benefit => one parent row.
2. One benefit assigned Individual and one Bulk => same parent row.
3. Parent Benefits count and Total Benefit equal visible child rows.
4. Expand shows each child Demand/Period and Source separately.
5. Billing Period filter narrows children and parent aggregates correctly.
6. Approval/removal/installment adjustment remains scoped to the selected child benefit only.
