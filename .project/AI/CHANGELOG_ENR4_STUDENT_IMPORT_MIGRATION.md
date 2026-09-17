# Changelog — ENR-4 Student Import / Migration — 2026-09-17

- ENR-3 / ADR 203 closed after Owner QA including RBAC View/Manage alignment and Audit Log verification.
- Implemented Student Management → Student Import / Migration.
- Added CSV template, private upload, arbitrary header mapping, full server validation, bounded preview, explicit import confirmation and transactional Student + Enrollment creation.
- Added `college_student_import.view` / `.manage` under Student Management.
- Added `student_enrollments.discipline_id` for authoritative imported-student academic context; Admission-origin enrollments now snapshot existing Application preference Discipline and Identity keeps a legacy fallback.
- Imported existing identities are validated/preserved; blank identities remain Pending for Student Identity.
- Added `student.import.completed` audit event.
- Database: one nullable FK/indexed Enrollment column + RBAC reference rows; no new domain table.
