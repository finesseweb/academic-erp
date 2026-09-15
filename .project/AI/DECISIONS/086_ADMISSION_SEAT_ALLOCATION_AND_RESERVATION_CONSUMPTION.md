# ADR 086 — Admission Seat Allocation / Reservation Consumption

Status: IMPLEMENTED — OWNER QA REQUIRED
Date: 2026-09-03

## Context
ADR 085 produces an immutable Merit / Roster from the exact locked Selection Rule version. The next Admission transaction step must convert that ranked Application Choice into one physical seat decision without bypassing Intake or Reservation configuration.

## Decision
Seat Allocation consumes only a generated `college_admission_merit_entries` row and preserves its exact College, Intake, effective bucket, Application, Application Choice, Score and Selection Rule references.

For REGULAR Admission the implemented chain is:

`Program Offering -> Intake -> optional Reservation Plan -> locked Selection Rule -> Application Choice -> Score/Interview -> Merit Entry -> Seat Allocation -> Admission Confirmation -> Student Lifecycle`

One Application may have only one ACTIVE physical seat allocation at a time, even when it has multiple ranked Program Choices.

## Physical seat rule
- Every ACTIVE allocation consumes exactly one physical seat from the exact Intake bucket.
- `OPEN` consumes the derived Open / Unreserved remainder.
- `RESERVED` consumes one configured Vertical reservation category.
- Physical consumption is counted across the Intake + bucket, not only one Selection Rule version, so a later rule version cannot create extra capacity.
- Backend transaction locks protect the candidate/application and Reservation rows before capacity is checked.

## Horizontal quota rule
Horizontal categories are stored separately from the physical seat.
- They do not create or consume an additional physical seat.
- An allocation can record zero or more applicable Horizontal categories.
- `fulfills_target = true` records whether this candidate counts toward the required target.
- Once a Horizontal target is fulfilled, additional applicable candidates may still be recorded but cannot be counted as another required fulfilment.
- Actual Horizontal candidates and target fulfilment remain separately reportable.

## Policy boundary
The ERP does not hard-code category priority, conversion, carry-forward, interchange or counselling preference policy. Authorized admission staff choose the physical category and verified Horizontal applicability against the institution's approved policy while the ERP enforces structural legality and capacity. Optional decision/counselling notes provide traceability.

## Cancellation / correction
Seat allocations are not destructively deleted in normal operations. Before Admission Confirmation, an ACTIVE allocation may be cancelled with a mandatory reason, releasing the physical seat. The same immutable Merit row may then be re-allocated; the audit trail records both decisions.

After a future Admission Confirmation references an allocation, this Seat Allocation page must reject cancellation.

## Reservation immutability dependency
Once a generated Merit / Roster depends on a Reservation Plan, that plan may not be deactivated or edited. Seat Allocation must consume the same locked Reservation structure that governed the submitted Application Choice / Selection Rule context.

## Permissions
- `college_admission_seat_allocation.view`
- `college_admission_seat_allocation.allocate`
- `college_admission_seat_allocation.cancel`

Allocate and cancel are sensitive College-delegable actions. `SUPER_ADMIN` and `COLLEGE_ADMIN` receive them by the implementation migration, consistent with the existing Admission milestone defaults.

## Audit events
- `COLLEGE_ADMISSION_SEAT_ALLOCATED`
- `COLLEGE_ADMISSION_SEAT_REALLOCATED`
- `COLLEGE_ADMISSION_SEAT_ALLOCATION_CANCELLED`

## Next frozen step
Seat Allocation / Consumption QA -> Admission Confirmation / Approval -> Student Enrollment / Lifecycle.

College Academic Setup completeness remains separately tracked: Batches -> Sections -> College Academic Calendar are still mandatory and must be implemented before downstream teaching operations depend on them.

## 2026-09-03 hierarchy correction — mandatory Document Verification gate
Before Seat Allocation may create/re-activate an allocation, the ranked Application must have an application-level `college_admission_document_verifications` row finalized as `VERIFIED`. The allocation stores that verification ID as a required RESTRICT foreign key. This closes the previously missing main-hierarchy step `Merit -> Document Verification -> Seat Allocation`. See ADR 089.

## Downstream implementation note — 2026-09-04
Admission Confirmation is now implemented by ADR 090. A `CONFIRMED` `admissions` row blocks Seat Allocation cancellation. A properly `REVOKED` Admission preserves history but no longer blocks an explicit allocation cancellation; the seat is never released automatically by Admission revocation.
