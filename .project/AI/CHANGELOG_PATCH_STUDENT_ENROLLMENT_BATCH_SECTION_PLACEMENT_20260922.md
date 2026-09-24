# Patch — Student Enrollment Batch / Section Placement — 2026-09-22

## Reason
Attendance QA showed `No enrolled students are mapped to this Course Offering and delivery scope` for a valid Section A class because enrolled students could still have NULL canonical Batch / Section placement.

## Implementation
- Added audited Enrollment Academic Placement mutation.
- Validates College scope, ENROLLED lifecycle, same Programme Offering, ACTIVE Batch, ACTIVE child Section.
- Student Enrollment queue exposes Assign/Change Placement for enrolled Admission-origin rows.
- Student Identity exposes Placement for all canonical enrollments, preserving Admission/Import parity.
- Attendance roster query is intentionally unchanged; it continues to consume exact canonical placement and course-choice context.
- No migration and no new mapping table.

## Owner QA
1. Open Student Identity and locate an enrolled student showing Placement = Pending.
2. Choose the Batch used by the Course Offering and the same Section used by Faculty Allocation (for example Section A).
3. Save and verify Placement becomes Assigned.
4. Open the dated class in Attendance and verify the student appears in Student Roster when the enrollment also contains the relevant canonical course choice.
5. Verify a Batch from another Programme Offering and a Section outside the selected Batch are rejected server-side.
6. Change placement and verify `student.enrollment.placement_assigned` is audited.
