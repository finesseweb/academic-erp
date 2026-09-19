# Direct Admission Seat Allocation 404 — Regression QA

Status: OWNER QA REQUIRED

## Root-cause verification
- Direct Seat Allocation UI posts to `/college/{college}/admission-seat-allocations/direct/{choice}`.
- `CollegeAdmissionSeatAllocationController::allocateDirect()` exists and delegates to the canonical Seat Allocation service.
- The corresponding POST route was missing; this produced the 404 before controller/service execution.

## Owner QA
1. Use a VERIFIED Direct Admission application with available physical capacity.
2. Open Allocate Seat and confirm the expected Candidate Reservation Category.
3. Select/confirm the permitted physical seat category and click Confirm Seat Allocation.
4. PASS: no 404; canonical success toast appears and the row reflects the allocation.
5. Verify physical capacity changes exactly once and reservation consumption matches the selected seat.
6. Open Admission Confirmation; PASS when the Direct allocation is available for the normal confirmation flow.
7. Regression: allocate a normal Merit/Roster candidate and verify that endpoint still works.
8. Negative guard: a Direct candidate that is not document-VERIFIED or lacks valid capacity must still be rejected by existing backend rules, not bypassed by this route fix.

## DB impact
None. This correction only restores the missing HTTP route to the already-implemented controller/service path.
