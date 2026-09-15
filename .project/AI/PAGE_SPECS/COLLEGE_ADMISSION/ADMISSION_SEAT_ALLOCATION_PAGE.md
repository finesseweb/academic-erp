# College Admission Seat Allocation / Consumption Page

## Purpose
Convert the immutable generated Merit / Roster into auditable physical seat decisions against the exact Intake bucket and locked Reservation Plan.

## Route
`GET /college/{college}/admission-seat-allocations`

## Required permission
`college_admission_seat_allocation.view`

Actions:
- allocate / re-allocate: `college_admission_seat_allocation.allocate`
- cancel before Admission Confirmation: `college_admission_seat_allocation.cancel`

## Upstream dependency
A Selection Rule appears only after it has generated Merit entries. The page must not generate ranking, recalculate Score/Interview, resolve a newer Selection Rule, or select an arbitrary Program/Intake.

## Page behavior
- Selection Rule cards show Program, Session, effective bucket, capacity and exact rule version.
- Capacity summary shows total physical capacity, ACTIVE allocations and remaining seats.
- Open / Unreserved and each Vertical reserved category show capacity / used / remaining.
- Horizontal categories show required target, fulfilled target, remaining target and actual admitted/allotted candidate count separately.
- Candidate table follows generated Merit rank order and shows Application, preference, final score and current seat decision.

## Allocation dialog
The operator chooses exactly one physical seat:
- Open / Unreserved; or
- one configured Vertical category with remaining capacity.

The operator may also select zero or more configured Horizontal categories. A Horizontal category may optionally be marked `Count toward target`; this is rejected when its target is already fulfilled.

Allocation round and a decision/counselling note are stored for traceability.

## Safety
- One Application may hold only one ACTIVE physical seat allocation across its choices.
- Capacity is enforced in a DB transaction with row locks.
- Horizontal quota never increases physical capacity.
- Reservation Plan must be the exact plan locked by the Selection Rule and remain ACTIVE.
- Cancellation requires a reason and is blocked after future Admission Confirmation consumes the allocation.
- No destructive normal delete action is exposed.

## Next page dependency
Admission Confirmation / Approval must consume an ACTIVE `college_admission_seat_allocations` record; it must not allocate seats again.

## Mandatory Document Verification gate — 2026-09-03
The candidate table exposes overall document-verification status. Allocate/Re-allocate is available only when the application is `VERIFIED`, and the backend independently enforces the same rule under transaction lock. Every allocation persists the exact verification row it consumed. See ADR 089 and the Document Verification page spec.

## Admission Confirmation downstream integration — 2026-09-04
Admission Confirmation is now implemented through `admissions`. Seat Allocation cancellation is blocked while a linked Admission status is `CONFIRMED`. If that Admission is validly `REVOKED` before Student Enrollment, the allocation still remains ACTIVE until an authorized user explicitly cancels it here; revocation never releases capacity automatically.

## Confirmed-admission action guard — 2026-09-04 QA refinement
- When an ACTIVE Seat Allocation is linked to an Admission with status `CONFIRMED`, the `Cancel Allocation` action is rendered disabled in the UI.
- The disabled action exposes guidance that Admission must be revoked before the allocation can be cancelled.
- This is a usability guard only; the backend cancellation service continues to reject the request independently so direct/API requests cannot bypass the rule.
- A linked `REVOKED` Admission does not disable cancellation; the operator may then explicitly cancel/release the allocation.

## Candidate Reservation Category authority — 2026-09-07 (ADR 132)
- Candidate Reservation Category is distinct from the physical seat category consumed by allocation.
- When Admission Form Mapping resolves the candidate category, Seat Allocation auto-fills it as the candidate-category source.
- When mapping does not resolve a category, the operator must confirm Candidate Reservation Category manually before allocation.
- A candidate may therefore remain `SC` while consuming an `OPEN / Unreserved` physical seat when OPEN merit rules permit it.
- Student Benefit eligibility consumes the candidate-category snapshot, not the physical seat category.

## Mutation feedback consistency — 2026-09-07 (ADR 133)
- Successful allocation/cancellation uses server `flash.toast` and the global Sonner presenter.
- Validation/protection failures are surfaced by the shared `useFlashToast()` error fallback.
- Do not add page-specific `window.alert()` success/error feedback.
- Confirmed-admission cancellation protection remains both UI-visible and backend-enforced.
