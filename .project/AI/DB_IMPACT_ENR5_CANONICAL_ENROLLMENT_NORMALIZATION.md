# DB Impact — ENR-5 Canonical Enrollment Academic Normalization

## Classification
**DATA NORMALIZATION / NO SCHEMA CHANGE / NO MIGRATION**

## Existing tables written
- `student_enrollments`: fills missing `curriculum_id`, `discipline_id`, `specialization_id` for safe legacy ADMISSION enrollments only.
- `student_enrollment_course_choices`: inserts missing canonical rows copied from the same Admission's saved Application course choices; existing conflicting/extra rows are not auto-rewritten.
- `audit_logs`: records `student.enrollment.academic_normalized` for applied legacy normalization.

## Authoritative derivation
`student_enrollments.admission_id → admissions.college_admission_application_id → college_admission_applications → college_admission_application_academic_preferences` supplies Curriculum/Discipline/Specialization.

`college_admission_application_course_choices` supplies the already-resolved Term/Slot/Curriculum Course Mapping/Course/selection source rows.

No Curriculum, Discipline, Course, Application, Admission or Student master is created by normalization.

## Restart / partial failure safety
Apply runs per Enrollment inside a DB transaction with `student_enrollments` row locking. Canonical course choices are unique by `(student_enrollment_id, curriculum_course_mapping_id)` and the shared writer checks existing rows before insert. Re-running after a successful row is idempotent. Conflicts are reported as `NEEDS_REVIEW` instead of being overwritten.
