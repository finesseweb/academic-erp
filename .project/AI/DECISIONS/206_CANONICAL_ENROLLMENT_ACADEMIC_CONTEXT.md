# ADR 206 — Canonical Enrollment Academic Context

**Status:** IMPLEMENTED / OWNER QA REQUIRED  
**Date:** 2026-09-18

## Decision
Once a person becomes a Student, all downstream academic modules consume one canonical academic structure regardless of entry provenance (`ADMISSION` or `IMPORT`).

Canonical post-entry ownership is:
- `students` — stable Student identity/profile owner.
- `student_enrollments` — Programme Offering, Curriculum, Discipline, Specialization and enrollment lifecycle.
- `student_enrollment_course_choices` — resolved Curriculum Term/Slot/Course Mapping/Course facts for the Enrollment.

Application and Admission records remain immutable process/provenance evidence. They are authoritative inputs when an Admission-origin Enrollment is first created or a legacy pre-canonical Enrollment is normalized, but downstream modules must not branch back to Application/Admission to discover current Enrollment academic context.

## Shared write contract
`StudentEnrollmentAcademicContextService` is the single persistence boundary for Enrollment academic context. Both `StudentEnrollmentService` (ADMISSION) and `StudentImportService` (IMPORT) write through it. It is conflict-safe and idempotent for already-persisted mapping IDs.

Future Attendance, Examination, Result, Marksheet, Promotion, Registration and Student Profile implementations must consume the canonical Enrollment structure and must not implement `if source_type = ADMISSION ... else IMPORT ...` academic-resolution branches.

## Legacy normalization
Pre-ENR-4.4 Admission enrollments may have NULL Curriculum/Discipline/Specialization and no canonical Enrollment course-choice rows. `StudentEnrollmentAcademicNormalizationService` resolves those values only from the Enrollment's own Admission → Application → Academic Preference + saved Application Course Choices.

Safety rules:
- default command mode is dry-run;
- conflicting non-null Enrollment values are never overwritten automatically;
- extra/conflicting saved Enrollment course choices are never deleted/rewritten automatically;
- missing/ambiguous provenance is `NEEDS_REVIEW`, never guessed;
- apply mode is transaction/row-lock based and restart-safe/idempotent;
- each applied normalization writes `student.enrollment.academic_normalized` to Audit Log.

Command:
`php artisan students:normalize-enrollment-academics [--college=ID] [--enrollment=ID] [--apply]`

## DB classification
No schema migration. This is a data-normalization + shared-domain-service change over existing documented tables. Database ownership/relationship documentation is updated in the same patch per ADR 205.
