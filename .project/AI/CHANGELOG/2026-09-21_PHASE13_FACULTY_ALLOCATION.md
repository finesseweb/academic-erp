# 2026-09-21 — Phase 13 Faculty Allocation

- Owner confirmed Course Offerings QA PASS / CLOSED.
- Implemented Faculty Allocation using Course Offering, existing Faculty user, and Batch/optional Section scope.
- Added lifecycle, College RBAC, audit, workload metadata, parent guards and dependency-safe cleanup.
- Refined selection to Current Session → Program Offering → Discipline → Semester/Term → searchable Course Offering, with searchable Faculty and direct College Users/Roles guidance.
- Added the shared SearchableSelect and its project-wide adoption/scalability contract.
- Corrected both Faculty Allocation Semester/Term selectors to follow Curriculum `sequence_no` rather than Course Offering creation/query order.
- Added and executed an idempotent repair migration for `college_faculty_allocation.eligible` because the table migration had already run in the owner environment before eligibility was added.
- Timetable, Rooms, Class Scheduling and Attendance remain unimplemented.
- Status: IMPLEMENTED — OWNER QA REQUIRED.
