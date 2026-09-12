# ADR 090 — Admission Confirmation / Approval

Status: IMPLEMENTED — OWNER QA ACCEPTED
Date: 2026-09-04

## Context
The Admission chain is frozen as:

`Generated Merit / Roster -> Document Verification -> Seat Allocation / Consumption -> Admission Confirmation / Approval -> Student Enrollment / Lifecycle`

Seat Allocation already owns physical capacity and Reservation consumption. Admission Confirmation must therefore finalize the admission decision without recalculating Intake capacity, changing Reservation category, re-ranking Merit, or replacing the exact VERIFIED document context.

## Decision
Admission Confirmation is implemented through the canonical `admissions` table.

A Confirmation may be created only from an existing `college_admission_seat_allocations` row whose status is `ALLOCATED`. The confirmation copies/references the exact upstream transaction chain:
- College
- Intake
- optional locked Reservation Plan
- effective Curriculum snapshot/reference
- Application
- Application Choice
- Document Verification
- Seat Allocation
- Merit Entry
- normalized Score
- locked Selection Rule version

The linked Document Verification must still be `VERIFIED`, and the Application must remain `SUBMITTED`.

## No seat recalculation
Admission Confirmation does not call Seat Allocation capacity logic and does not choose a Reservation category. The physical seat decision remains authoritative in `college_admission_seat_allocations`.

## Admission number
A stable Admission Number is generated on first confirmation using the College code, confirmation year, and immutable Application ID. Re-confirming a previously revoked record preserves the same Admission Number.

## Status lifecycle
`CONFIRMED -> REVOKED -> CONFIRMED` is allowed only before Student Enrollment consumes the Admission.

Normal product flow never hard-deletes Admission history. Revocation requires a reason and writes an audit event. Revocation does not silently release the physical seat; the operator must explicitly cancel the Seat Allocation after revocation if the seat is to be released.

While an Admission is `CONFIRMED`, Seat Allocation cancellation is blocked. A `REVOKED` Admission no longer blocks explicit Seat Allocation cancellation.

## Student boundary
This milestone does not create a Student record and does not change the Applicant login to STUDENT. Student master creation and `ApplicantStudentPromotionService::enableStudentAccess()` remain the responsibility of the next Student Enrollment / Lifecycle milestone.

Once a future Student record consumes an Admission/Application, Admission revocation must be blocked.

## Permissions
- `college_admission_confirmation.view`
- `college_admission_confirmation.confirm` — sensitive, College-delegable
- `college_admission_confirmation.revoke` — sensitive, College-delegable

The implementation migration grants these to `SUPER_ADMIN` and `COLLEGE_ADMIN` by default, following the established Admission milestone pattern.

## Audit events
- `COLLEGE_ADMISSION_CONFIRMED`
- `COLLEGE_ADMISSION_RECONFIRMED`
- `COLLEGE_ADMISSION_CONFIRMATION_REVOKED`

## Cleanup contract
System Maintenance -> Test Data Cleanup includes Admission Confirmation as its own dependency-safe target. Admission records must be removed before their Seat Allocation, Merit, Score, Application or Verification parents. Future Student records block Admission cleanup.

## Next implementation order
Owner QA for this milestone -> Batch Management -> Section Management -> College Academic Calendar -> Student Enrollment / Lifecycle.

The pending College Academic Setup layers are mandatory before Student Enrollment/teaching operations depend on them.
