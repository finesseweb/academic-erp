# ADR 203 — Student Identity and Roll Number Rules — QA Addendum

ENR-3 owns Student UID, University Roll Number, and Enrollment Class Roll Number. Exam Roll remains outside ENR-3.

## Canonical placement UI addendum — 2026-09-22
Student Identity is the single UI boundary for assigning existing canonical `student_enrollments.batch_id` and `section_id`. Bulk placement is atomic, restricted to enrollments from one Programme Offering, and validates an ACTIVE same-Offering Batch plus ACTIVE child Section. Student Enrollment does not duplicate this action. The addendum introduces no placement table, Section capacity or financial side effect.

## Test Data Cleanup addendum — 2026-09-17
System Maintenance → Test Data Cleanup exposes a **Student Management → Students** group. Cleaning an individual test Student removes Student-owned profile/enrollment/identity data and restores Applicant lifecycle linkage when applicable, while preserving source Application, Admission and Fee records. Individual cleanup never rewinds identity sequences; assigned numbers remain non-reusable.

## ENR-3.3 — Configurable Class Roll Scope (2026-09-17)
- Class Roll sequencing is College-configurable: `PROGRAMME_OFFERING` (default/backward-compatible) or `DISCIPLINE`.
- `PROGRAMME_OFFERING`: one Class Roll sequence for all enrolled students in the offering.
- `DISCIPLINE`: an independent Class Roll sequence per authoritative Discipline inside the Programme Offering.
- Discipline is resolved from the existing Admission → Application → Academic Preference relationship; it is not duplicated as a new Student domain field.
- Already assigned Class Roll numbers are immutable. Changing scope affects only future assignments.
- `student_enrollments.class_roll_scope_key` records the scope used at assignment and supports database uniqueness when the same Class Roll can legitimately exist in two disciplines.
- Existing Class Rolls are backfilled with their historical `OFFERING:<id>` scope; no existing roll is renumbered.

### ENR-3.4 — issuance boundary and QA cleanup
Institutional identity issuance is an explicit post-enrollment action. Student Enrollment creates the authoritative Student and Enrollment but MUST NOT allocate Student UID, University Roll No. or Class Roll No. The Student Identity page is the single issuance boundary and shows newly enrolled records as PENDING until an authorized user selects Assign.

Test Data Cleanup exposes a separate Student Identity Assignments cleanup. It clears only `students.student_uid`, `students.university_roll_no`, and the selected enrollment's `student_enrollments.class_roll_no`. It MUST preserve the Student, Enrollment, Student Profile, Application, Admission and Fee records. `student_identity_sequences` MUST NOT be rewound; issued numbers are non-reusable even when QA identity assignments are cleared.

## ENR-3.6 RBAC alignment and audit hardening — 2026-09-17
- `Student Management` is the RBAC parent module for both Student Enrollment and Student Identity, matching the sidebar/business workflow.
- Student Identity separates read access (`college_student_identity.view`) from mutation access (`college_student_identity.manage`).
- Existing roles holding Identity Manage are migration-backfilled with Identity View so the permission split does not unexpectedly hide the page.
- Student Identity page authorization and sidebar visibility use Identity View; Save Rules and Assign continue to require Identity Manage.
- Identity assignment remains audited by `student.identity.assigned` with before/after values, actor, College scope and IP.
- Identity rule changes (including Class Roll Scope) are audited by `student.identity.rules.updated` with before/after settings, actor, College scope and IP.
- This migration is permission/reference-data only: no new domain table and no Student/Enrollment domain column.
