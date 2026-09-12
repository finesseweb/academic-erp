# ADR 112 — Applicable Fee Demand Snapshot

## Decision
After Admission Confirmation and before Payment/Fee Clearance, the ERP generates an admission-level **Applicable Fee Demand** for one exact billing period.

The demand resolver combines only effective Fee Foundation rules: ACTIVE College structures for the admission's exact Program Offering plus ACTIVE matching University structures that are MANDATORY or OPTIONAL+ADOPTED. Period 1 also includes applicable ONE_TIME charges. Recurring/specific structures resolve the exact requested period.

Each demand item snapshots source structure/head, owner, amount, Mandatory, Enrollment Clearance Required, Installment Allowed, purpose and charge basis. Later Fee Structure edits never rewrite an existing demand.

Mandatory and Enrollment Clearance are distinct. Mandatory contributes to the mandatory total. Enrollment Clearance Required contributes to the amount that must be cleared before Student Enrollment. Installment Allowed is retained per demand item for the future Payment/Installment workflow; it does not itself mark a demand cleared.

Only CONFIRMED admissions can receive demands. One non-cancelled demand per Admission + billing period is allowed. Mixed currency and duplicate effective Fee Heads are rejected. Unpaid/unadjusted demands may be cancelled with reason while retaining history. Payment, scholarship/waiver/adjustment, installment schedule, Fee Clearance and Enrollment remain downstream milestones.

## Status
IMPLEMENTED — OWNER QA REQUIRED.
