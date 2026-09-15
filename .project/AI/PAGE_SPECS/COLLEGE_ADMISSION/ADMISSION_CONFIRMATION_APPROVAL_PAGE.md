# College Admission Confirmation / Approval Page

## Purpose
Finalize admission for a candidate who already owns an ACTIVE Seat Allocation. This page consumes the existing seat decision; it never allocates or recalculates capacity.

## Route
`GET /college/{college}/admission-confirmations`

Actions:
- `POST /college/{college}/admission-confirmations/seat-allocation/{allocation}`
- `PATCH /college/{college}/admission-confirmations/{admission}/revoke`

## Required permissions
- view: `college_admission_confirmation.view`
- confirm/re-confirm: `college_admission_confirmation.confirm`
- revoke before Student Enrollment: `college_admission_confirmation.revoke`

## Upstream gate
A candidate appears only from an ACTIVE `college_admission_seat_allocations` row.

Backend Confirmation requires:
- Seat Allocation status = `ALLOCATED`
- exact linked Document Verification status = `VERIFIED`
- Application status = `SUBMITTED`
- no other active Admission Confirmation for the same Application

UI disabling is informational. Backend transaction checks are authoritative.

## Page behavior
- Summary shows Active Seat Allocations, Ready to Confirm, Confirmed and Revoked counts.
- Optional Selection Rule filter narrows the exact Merit/Program context without changing it.
- Candidate rows show Merit rank, final normalized score, Application, Program/Session, VERIFIED state, physical seat category, allocation round, Horizontal quota snapshots and Admission state.
- Confirmation dialog allows only an optional approval/committee/counselling note.
- Revoke requires a mandatory reason.

## Safety
- Admission cannot exist without the exact Seat Allocation FK.
- Admission does not change physical seat category, Reservation Plan, Merit rank or Score.
- Confirmed Admission blocks Seat Allocation cancellation.
- Revoking Admission does not automatically release the seat.
- Admission revocation is blocked after future Student Enrollment consumes the Admission/Application.
- No destructive normal delete action is exposed.

## Downstream contract
Student Enrollment must consume a `CONFIRMED` Admission and create the Student master before enabling the same Applicant user identity through `ApplicantStudentPromotionService::enableStudentAccess()`.
