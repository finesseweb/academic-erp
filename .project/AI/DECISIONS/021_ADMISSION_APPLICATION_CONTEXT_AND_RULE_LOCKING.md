# ADR 021 — Admission Application Program Choices and Selection Rule Locking

Status: IMPLEMENTED — OWNER QA REQUIRED
Date: 2026-08-26

## Context
A candidate application belongs to one Program-Offering-scoped Admission Cycle and may contain more than one ordered seat-bucket/specialization choice within that offering. Different buckets can use different Reservation contexts and Selection Rule versions. Later Score, Interview, Merit and Seat Allocation must know exactly which policy applied without re-deriving history from current configuration.

## Decision
Use an Application header plus ordered seat-bucket/specialization choice child rows under the Program Offering already fixed by the Admission Cycle.

`college_admission_applications`
→ `college_admission_application_choices`
→ exact Intake seat bucket
→ optional Reservation Plan
→ exact Selection Rule version

A DRAFT may be edited. On SUBMIT, every choice is revalidated against the exact Program Offering fixed by the Admission Cycle + ACTIVE Intake + effective bucket + optional ACTIVE Reservation + ACTIVE Selection Rule. The resulting Selection Rule ID is persisted as the historical rule version for future processing.

`submitted_at` is persisted on the Application header and is the executable value for the `APPLICATION_SUBMITTED_AT` tie-break criterion.

## Eligibility Boundary
Candidate eligibility in this milestone is preliminary/basic eligibility per Program Choice. It does not run selection score thresholds. Future Score Capture / Normalization and Merit / Roster Generation must consume the exact locked Selection Rule and execute Merit/Entrance/Interview thresholds, weighting and tie-breaks there.

## Future Consumption
- Score Capture stores raw + normalized Merit/Entrance score data against the Application Choice and its locked Selection Rule.
- Interview Scheduling/Evaluation is created only when the locked rule has Interview weight > 0.
- Merit/Roster generation ranks the same Application Choice using the locked rule and its structured tie-breakers.
- Seat Allocation consumes the ranked Application Choice and its physical bucket/Reservation context.
- Admission Confirmation produces the admitted result.
- Student Lifecycle begins only after Admission Confirmation.

## Historical Safety
A submitted choice must never be automatically relinked when a newer Selection Rule version becomes ACTIVE. Drafts may be refreshed before submission because they are not yet operative admission records.
