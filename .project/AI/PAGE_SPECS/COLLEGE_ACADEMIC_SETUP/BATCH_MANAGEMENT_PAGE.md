# College Academic Setup — Batch Management

Status: IMPLEMENTED — OWNER QA REQUIRED
Date: 2026-09-04

## Purpose
Create operational student cohorts after Program Offering -> Intake / Seat Capacity -> Reservation / Seat Distribution. A Batch is not a new seat-capacity authority. It groups the future enrolled students under one exact College Program Offering.

## Route
- GET `/college/{college}/batches`
- POST `/college/{college}/batches`
- PATCH `/college/{college}/batches/{batch}`
- PATCH `/college/{college}/batches/{batch}/status`

## Hierarchy
`College -> Program Offering -> Intake/Reservation context -> Batch -> Section -> Student Enrollment`

Batch must never select an arbitrary University Program, Curriculum or Academic Session. Those are inherited from `college_program_offerings`.

## Fields
- Program Offering — required; must belong to the current College
- Batch Code — required, max 50, unique within Program Offering
- Batch Name — required, max 120
- Notes — optional
- Status — `INACTIVE` / `ACTIVE`

New Batches start `INACTIVE`.

## Activation gate
Activation requires:
1. College is ACTIVE.
2. linked College Program Offering is ACTIVE.
3. linked Program Offering has an ACTIVE Intake / Seat Capacity.

Reservation remains optional where no reservation plan is configured. Batch activation never recalculates or copies seat allocation counts.

## Edit rules
- Batch metadata can be edited with `college_batch.update`.
- An ACTIVE Batch cannot be moved to a different Program Offering.
- A Batch with downstream Section/Student records cannot be moved to another Program Offering.

## Deactivation gate
Future active Sections or Student Enrollment records must block deactivation. Backend checks remain authoritative even if the UI hides/disables an action.

## RBAC
- `college_batch.view`
- `college_batch.create`
- `college_batch.update`
- `college_batch.enable` — sensitive
- `college_batch.disable` — sensitive

All are College-delegable and server-enforced with exact College scope. Default protected grants: `SUPER_ADMIN`, `COLLEGE_ADMIN`.

## Audit
- `COLLEGE_BATCH_CREATED`
- `COLLEGE_BATCH_UPDATED`
- `COLLEGE_BATCH_ACTIVATED`
- `COLLEGE_BATCH_DEACTIVATED`

## Cleanup
Test Data Cleanup lists Batches as dependency-aware records. A Batch cannot be individually cleaned while Section/Student dependencies exist. Full Academic Reset removes Batches before Intake/Program Offering parents.

## QA
1. Create an INACTIVE Batch from an ACTIVE Program Offering.
2. Confirm duplicate Batch Code within the same Offering is blocked.
3. Confirm activation is blocked if Intake is absent/inactive.
4. Activate after Intake is ACTIVE.
5. Verify Program/Curriculum/Session shown are inherited from Offering.
6. Verify no Intake/Reservation/Seat Allocation numbers change when Batch is created/activated.
7. Test RBAC view/create/update/enable/disable independently.
8. Test cross-College URL/API access returns 403/404 as appropriate.
9. Test Cleanup Center sees the Batch and can clean it when no dependencies exist.


### Parent deactivation UX (Owner QA refinement — 2026-09-04)

- An ACTIVE Batch is a hard downstream dependency of its Intake / Seat Capacity and Program Offering.
- When at least one ACTIVE Batch exists, the parent Intake deactivate control and Program Offering deactivate control must be disabled in the UI with guidance to deactivate the Batch first.
- Backend guards remain mandatory and must return a visible validation message if a direct or stale request attempts the blocked deactivation.
- UI disabling is convenience only; it never replaces the backend dependency check.

