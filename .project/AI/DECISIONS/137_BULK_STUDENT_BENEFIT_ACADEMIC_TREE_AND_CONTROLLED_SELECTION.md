# ADR 137 — Bulk Student Benefit Academic Tree and Controlled Selection

## Decision
College Student Benefits supports both **Individual** and **Bulk Assignment**. Bulk assignment is a controlled-selection workflow, not automatic entitlement.

The bulk workflow is:

`Select ACTIVE Scheme → resolve eligible Fee Demands → Degree Level → Degree → Discipline → Student selection → final eligibility re-check → Assign/Sanction`

## Academic hierarchy
Eligible students are grouped in a collapsed tree so large cohorts remain manageable:

`Degree Level → Degree → Discipline → Students`

Each hierarchy level displays an explicit label (`Degree Level`, `Degree`, `Discipline`) and a student count. Parent checkboxes are selection conveniences only; they do not determine eligibility. An operator may select a whole group and then deselect individual students.

Student rows show enough context to prevent mistaken selection: Candidate, Application/Admission reference, Fee Demand/billing period, Candidate Reservation Category snapshot, Eligible Base, and Calculated Benefit.

## Eligibility versus sanction
Eligibility means the student may be considered under the configured scheme; it does **not** mean the institution must award the benefit. This is especially important for OPEN/merit-type schemes where many students can be considered but only a chosen subset may be sanctioned.

Reservation-category schemes continue to consume only the snapshotted Candidate Reservation Category from ADR 132/136. Physical seat bucket does not define scholarship identity.

## Bulk safety
- Only ACTIVE University/College schemes in the College context are offered.
- Only OPEN/PARTIALLY_CLEARED Fee Demands in the scheme's Academic Session and program/offering scope are scanned.
- Existing PENDING/APPROVED assignment of the same scheme to the same Fee Demand is excluded.
- Covered Fee Heads and remaining eligible base are calculated with the same `FeeStudentBenefitService` logic used by Individual Assignment.
- Every selected Fee Demand is revalidated server-side at submission. A stale/ineligible row is skipped rather than incorrectly receiving a benefit.
- MANUAL schemes create PENDING assignments; AUTOMATIC schemes auto-sanction only after the same final validation.
- Original Fee Demand and Fee Demand Item gross amounts remain immutable. Approved benefit continues to post through the ADR 130 adjustment mechanism.

## UI compactness
All hierarchy groups are collapsed initially. Search works across student/application/admission/demand, Degree Level, Degree, Discipline and candidate category. Summary cards show scanned, eligible-to-consider, selected, already-assigned and selected calculated-benefit total.

## Scope
This ADR adds bulk operational assignment only. It does not introduce a separate merit-ranking engine, beneficiary quota engine, payment, reversal/refund or Fee Clearance workflow.
