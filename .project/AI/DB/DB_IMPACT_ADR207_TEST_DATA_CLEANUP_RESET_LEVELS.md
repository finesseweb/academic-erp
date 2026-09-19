# DB Impact — ADR 207 Test Data Cleanup Reset Levels

Classification: **NO SCHEMA CHANGE / NO MIGRATION**.

This change modifies controlled deletion behavior only. No table, column, FK, index or constraint is added/changed.

Canonical dependency order added/confirmed:
- `student_enrollment_course_choices.student_enrollment_id` → delete before `student_enrollments`.
- `student_profile_values.student_id` → delete before `students`.
- `student_enrollments.student_id` → delete before `students`.
- Admission module reset removes admission-created Student/Enrollment data before `admissions`.
- Merit entries and seat allocations are removed before Selection/Reservation test setup is reset.

Full Academic Test Reset now includes all University-scoped Students, including IMPORT-source Students, instead of relying only on Admission/Application linkage.

Migration recovery/restart safety: N/A — no migration. Runtime reset remains transactional and FK checks stay enabled.


## Follow-up — Academic Calendar Period cleanup (2026-09-18)

- Schema change: **NO**.
- Migration: **NONE**.
- Data affected by targeted cleanup: `academic_calendar_term_periods` selected assignment row.
- Parent `academic_calendars` row: preserved.
- Curriculum/curriculum term master rows: preserved.
- Academic calendar events: preserved. Optional period-assignment references are detached safely when required by the existing schema.
- Fee structures/items: not modified. Existing fee/calendar validation remains authoritative.
- Restart/recovery: no DDL is executed; operation remains scoped to the selected QA cleanup target.
