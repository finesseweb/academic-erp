# ADR 150 — Direct Admission downstream gates

**Date:** 2026-09-09  
**Status:** IMPLEMENTED — QA PENDING

## Context

The application layer already supported `admission_mode = DIRECT` and correctly bypassed Eligibility / Score / Interview / Merit / Selection Rule processing. However, Document Verification and Seat Allocation were implemented only from generated Merit / Roster rows. A SUBMITTED Direct Admission application therefore had no route into Document Verification or Seat Allocation even though the Admission schema had already reserved nullable Score/Merit/Selection Rule references for the Direct path.

This was a workflow gap. Direct Admission is a **selection method**, not a bypass of admission-compliance or physical-seat controls.

## Decision

The canonical Direct Admission flow is:

`Application SUBMITTED (DIRECT)` → **Document Verification** → **Seat Allocation / Reservation Consumption** → **Admission Confirmation** → Fee Demand → Student Benefits → later Enrollment.

Direct Admission bypasses only:

- Eligibility / Selection Rule qualification processing
- Score calculation
- Interview scoring used by the Selection Rule
- Merit / Roster generation and rank priority

Direct Admission does **not** bypass:

- uploaded document review and final `VERIFIED` status
- Program / Discipline / Specialization academic context
- ACTIVE Intake and seat-bucket context
- candidate Reservation Category identity
- physical Open/Reserved seat capacity
- Vertical reservation compatibility
- Horizontal quota recording/target controls
- Admission Confirmation
- downstream Fee Demand / Student Benefit rules

## Processing context

A Direct application still requires one unambiguous Intake + seat-bucket context. On submit the saved Admission Academic Preference is resolved against the ACTIVE Program Offering/Intake structure. A `college_admission_application_choice` is created as the downstream processing context with:

- `college_admission_selection_rule_id = NULL`
- `eligibility_status = ELIGIBLE` only as a technical Direct-bypass marker
- `eligibility_reason = DIRECT_ADMISSION_SELECTION_BYPASS`

This does **not** mean the candidate passed a Selection Rule; it records that Selection Rule eligibility is intentionally not applicable.

For Direct applications submitted before ADR 150, finalizing Document Verification as `VERIFIED` repairs/locks the missing Direct processing choice before Seat Allocation.

## Document Verification

Document Verification now has a separate **Direct Admission** context in addition to Merit/Selection Rule contexts. SUBMITTED Direct applications appear here without requiring a Merit row. All FILE/IMAGE review, VERIFIED/WAIVED item rules, DEFICIENT handling and audit behavior remain unchanged.

## Seat Allocation

Direct Seat Allocation is a separate backend action against the locked Direct application choice. It requires:

- SUBMITTED Direct application
- VERIFIED Document Verification
- matching College/Program Intake/bucket
- ACTIVE Reservation Plan when configured
- candidate Reservation Category resolution from the system Reservation Category field (ADR 149), legacy fallback, or authorized manual confirmation
- physical capacity and reservation compatibility

Merit-priority enforcement is intentionally not run for Direct Admission because Direct Admission bypasses the Merit/Selection route. Capacity and reservation rules are never bypassed.

## Schema

`college_admission_seat_allocations` now permits NULL for fields that are not applicable to Direct Admission:

- `college_admission_merit_entry_id`
- `college_admission_score_id`
- `college_admission_selection_rule_id`
- `merit_rank`
- `final_weighted_score`

Regular Admission continues to populate all five fields.

The existing `admissions` table already permits nullable Merit/Score/Selection Rule references for the documented Direct path.

## Admission Confirmation

Admission Confirmation accepts both Regular and Direct Seat Allocations. Direct rows are explicitly labelled `DIRECT ADMISSION`; no fake rank or fake weighted score is generated. VERIFIED documents and the exact allocated physical seat remain mandatory.

## Test Data Cleanup

Direct Admission QA data uses the same cleanup coverage as the existing Admission pipeline:

1. downstream Fee Demand / Benefit transactional test data where present
2. Admission Confirmation test record
3. Seat Allocation test record and horizontal allocation rows
4. Document Verification + item reviews
5. Direct processing choice / application test data

Dependency ordering must remain enforced; bulk module cleanup from ADR 148 must not bypass these protections.

## QA gate

Verify at minimum:

1. Existing SUBMITTED Direct application appears in Document Verification without Merit generation.
2. It cannot allocate a seat before Document Verification is VERIFIED.
3. Final VERIFIED status locks/repairs exactly one Direct Intake/bucket choice.
4. The candidate appears in Direct Seat Allocation.
5. Candidate Reservation Category from ADR 149 is auto-consumed when present.
6. Open/Reserved capacity and category compatibility are enforced.
7. Direct allocation stores NULL Merit/Score/Selection Rule/rank/weighted score.
8. Direct allocation appears in Admission Confirmation as `DIRECT`, not rank `#0`.
9. Admission can be confirmed and then enters the same Fee Demand / Student Benefit workflow as other confirmed admissions.
10. Regular Merit/Selection admission behavior is unchanged.
