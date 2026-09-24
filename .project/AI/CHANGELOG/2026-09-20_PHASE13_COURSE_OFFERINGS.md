# 2026-09-20 — Phase 13 Course Delivery / Course Offerings

- Owner confirmed ENR-6 / ADR 208 Student Profile QA PASS / CLOSED.
- Owner confirmed ADR 204C Student account credential correction QA PASS / CLOSED.
- Started Phase 13 exactly at the next hierarchy milestone: Course Offerings.
- Added `course_offerings` as Batch + existing Curriculum Course Mapping; no existing Program Offering/Batch/Section/Curriculum hierarchy was redesigned.
- Added College-scoped Course Offerings page, create and activate/deactivate actions.
- Added server-side integrity checks for College ownership, Batch/Program Offering active state, exact linked Curriculum, and active Term/Slot/Mapping on activation.
- Added Course Delivery permissions and default SUPER_ADMIN/COLLEGE_ADMIN grants.
- Added audit events for create/activate/deactivate.
- Added PAGE_SPEC, TABLE_SPEC and ADR 209.
- Faculty Allocation, Timetable, Rooms, Class Scheduling and Attendance remain unimplemented future milestones.
- Status: IMPLEMENTED — OWNER QA REQUIRED.
- Completion-contract sync: Course Offerings added to individual Test Data Cleanup and Full Academic Test Reset child-first order before Batch deletion; Batch cleanup now blocks while Course Offerings exist.
- Database catalog/relationship map and Security permission/role/audit documentation synchronized.
