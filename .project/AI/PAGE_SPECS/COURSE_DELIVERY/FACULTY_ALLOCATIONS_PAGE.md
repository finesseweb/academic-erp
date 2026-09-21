# Course Delivery — Faculty Allocation

Status: IMPLEMENTED — OWNER QA REQUIRED
Decision: ADR 210

Routes: `GET/POST /college/{college}/faculty-allocations`, `PATCH /college/{college}/faculty-allocations/{facultyAllocation}`, and its `/status` action.

The parent is an existing Course Offering. Faculty is active College Staff of the route College with `college_faculty_allocation.eligible` through an effective College-scoped role. Scope is the entire Batch or one Section belonging to the Course Offering Batch. New records start INACTIVE, active records are immutable until deactivated, and duplicate Faculty + Offering + delivery-scope records are rejected.

## Selection workflow
Allocation uses searchable dependent selection: Current Academic Session by default → Program Offering → Discipline → Semester/Term → Course Offering. Changing a parent clears downstream values. Course search includes course code/name, Batch, specialization and status. Faculty search includes name, email and active roles.

Semester/Term options are ordered by the authoritative numeric `curriculum_terms.sequence_no` ascending, with name used only as a deterministic tie-breaker.

Faculty onboarding remains College Users → create College Staff; College Roles → grant `college_faculty_allocation.eligible`; user role assignment → assign that role. Faculty Allocation never creates users or roles. See `UI_SEARCHABLE_SELECT_STANDARD.md`.

Management permissions are `college_faculty_allocation.view`, `.create`, `.update`, `.enable`, and `.disable`. Enable/disable are sensitive. Default management grants are SUPER_ADMIN and COLLEGE_ADMIN. Faculty eligibility is independently granted to a College role through `college_faculty_allocation.eligible`; it is not granted to administrators by default.

## QA
1. Only eligible active Faculty users appear.
2. Batch-wide and Section-scoped allocations start INACTIVE.
3. Cross-Batch Section, cross-College Faculty, and duplicate scope are rejected.
4. Activation enforces all active parents and active Section where applicable.
5. Active allocation cannot be edited; deactivate, edit and reactivate.
6. Verify five permissions independently, audit events, College isolation, targeted cleanup and Full Academic Reset.
7. Confirm upstream Course Offering, Curriculum, Batch, Section, user and role records remain unchanged.

Next after owner QA pass: Timetable.
