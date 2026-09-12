# Intake Parent Capacity Validation Fix — 2026-08-25

## Correct Rule
Program Approved Capacity is compared only with top-level Discipline capacities.

Specialization child capacities are contained inside the Discipline capacity and must never be added again.

Example:
- English Discipline = 60
  - Literature = 20
  - Linguistics = 20

The Program contribution from English remains 60, not 100.

## Edit Validation
When reducing Program Approved Capacity in DISCIPLINE mode:
`sum(ACTIVE top-level DISCIPLINE allocations) <= new approved capacity`

Child Specialization allocations are ignored for this Program-level comparison.

This rule must also be followed by future Admission, Reservation, reporting, cleanup previews and capacity dashboards to prevent hierarchical double counting.
