# 2026-09-19 — Direct Admission Seat Allocation 404 Fix

- Root cause: frontend Direct Admission allocation posts to `/college/{college}/admission-seat-allocations/direct/{choice}` and the controller already implements `allocateDirect()`, but the route was absent from `routes/web.php`.
- Fix: registered the missing POST route and named it `college-admission-seat-allocations.allocate-direct`.
- Existing `CollegeAdmissionSeatAllocationService::allocateDirect()` remains authoritative; no business-rule duplication was added.
- Merit/Roster allocation and cancellation routes are unchanged.
- DB impact: none. No migration required.
- Status: implementation complete; owner regression QA required.
