# ADR 126 — Scholarship / Concession / Waiver Foundation

## Status
IMPLEMENTED — QA PENDING

## Decision
Fee Demand remains the immutable gross-charge snapshot. Scholarship, concession and waiver are separate auditable financial benefits and must never rewrite the original demand amount.

The Fee domain now has a configurable Benefit Scheme foundation at both University and College scope. University schemes are visible read-only to Colleges. A College may define local schemes for its own Program Offering.

A scheme defines: Session/scope, benefit type (Scholarship/Concession/Waiver), calculation (Fixed/Percentage), optional percentage cap, applicable Fee Heads, eligibility mode (Open or Reservation Category), approval mode (Automatic/Manual), description and lifecycle.

Reservation Category is only an eligibility input from the authoritative Admission/Reservation domain. Fee Management must not recalculate reservation, roster, seat consumption or candidate category. No category implies a hard-coded discount; the configured scheme is authoritative.

New schemes are INACTIVE. Active schemes are immutable until deactivated. Activation requires at least one ACTIVE applicable Fee Head and, for category-based schemes, at least one ACTIVE Reservation Category.

## Boundaries
This ADR creates policy/setup only. It does not yet sanction a benefit to a candidate, reduce outstanding balance, create a ledger entry, collect payment, or grant Fee Clearance. Those belong to the next student benefit allocation/approval and financial ledger milestones.

## Next
Implement Candidate/Student Benefit Allocation + Approval against an existing Fee Demand, snapshotting the scheme and creating an auditable adjustment without mutating gross demand.
