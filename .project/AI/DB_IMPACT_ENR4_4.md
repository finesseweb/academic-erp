# DB Impact — ENR-4.4 / ENR-4.4.1

## Migration
`2026_09_17_150000_link_student_enrollment_academic_context.php`

## Classification
Student Enrollment lifecycle extension. This is a domain-schema migration because it extends the authoritative Enrollment academic context and adds an enrollment-owned resolved-course choice table. It does **not** create a competing Curriculum, Course, Discipline, Specialization, Application, or Admission model.

## Authoritative ownership and relationships
### `student_enrollments`
- `curriculum_id` nullable FK → `curricula.id`.
- `specialization_id` nullable FK → `academic_disciplines.id`; the existing academic-discipline hierarchy remains authoritative for specialization.
- Existing `college_program_offering_id` and `discipline_id` remain authoritative context links.
- Admission and CSV Import must converge on this same Enrollment context.

### `student_enrollment_course_choices`
Enrollment-owned resolved academic choices. One row records the course mapping resolved from the same Curriculum structure used by the Admission academic-preference flow.

Columns/links:
- `student_enrollment_id` FK → `student_enrollments.id`, cascade delete.
- `curriculum_term_id` FK → `curriculum_terms.id`, restrict delete.
- `curriculum_slot_id` FK → `curriculum_slots.id`, restrict delete.
- `curriculum_course_mapping_id` FK → `curriculum_course_mappings.id`, restrict delete.
- `course_id` FK → `courses.id`, restrict delete.
- `selection_source` records how the resolved choice entered Enrollment.

Indexes/constraints:
- unique `student_enrollment_course_mapping_uq` on (`student_enrollment_id`, `curriculum_course_mapping_id`).
- index `student_enrollment_course_term_idx` on (`student_enrollment_id`, `curriculum_term_id`).
- explicit short FK names: `sec_enrollment_fk`, `sec_term_fk`, `sec_slot_fk`, `sec_mapping_fk`, `sec_course_fk`.
- Enrollment context FKs use `se_curriculum_fk` and `se_specialization_fk` when created by this migration.

## ENR-4.4.1 migration recovery
Owner QA found MySQL error 1059 because Laravel auto-generated the FK name for `curriculum_course_mapping_id` exceeded MySQL's identifier-length limit. MySQL may already have committed earlier DDL in the migration before the failure.

Corrective rule:
- migration uses explicit short constraint names;
- migration checks existing columns, foreign keys, and indexes before creating them;
- rerunning `php artisan migrate` after the failed first attempt is supported;
- do not manually delete the already-added Enrollment columns before rerunning;
- migration is treated as restart-safe for the known partial-DDL failure path.

QA status: migration recovery pending Owner rerun after ENR-4.4.1 patch.

## ENR-4.5 validation contract — DB impact (2026-09-17)
**Classification: NO SCHEMA CHANGE.** No migration is introduced by ENR-4.5.

The change reads existing Admission Form metadata (`student_data_policy`, `student_profile_key`, `is_required`, active mapping/applicability) and existing Curriculum academic configuration to enforce mapping-level and row-level import validation. Successful rows continue to persist only through the existing authoritative `students`, `student_enrollments`, `student_profile_values`, and `student_enrollment_course_choices` structures documented above.

No academic master is created from CSV values. Invalid/missing Discipline, conditional Specialization, or curriculum choice values are validation failures. Missing Admission Form Template is not a database error and does not create a fallback template; the import proceeds without template-driven profile fields after explicit UI disclosure.

## ENR-4.6 DB impact — 2026-09-17
**Classification: NO SCHEMA CHANGE / NO MIGRATION.**

Academic-choice parity changes only import schema generation and validation/resolution. It reuses the existing Curriculum/Curriculum Course Mapping read model and persists resolved choices through the existing `student_enrollment_course_choices` table. Offered From/Common-Interdisciplinary is derived from the referenced curriculum mapping rather than duplicated as free-text enrollment data. No table, column, foreign key, index, sequence, permission row, or reference-data migration is introduced by ENR-4.6.

## ENR-4.8 DB impact — 2026-09-17
**Classification: NO SCHEMA CHANGE / NO MIGRATION.**

Offered From remains derived Curriculum configuration and is not duplicated on Student/Enrollment. Admission and Import resolve the applicant-facing Offered From option to the authoritative `curriculum_course_mappings.id`; persistence continues through existing Application course-choice and `student_enrollment_course_choices` rows. No table, column, FK, index, permission/reference row, or sequence changes are introduced.

## ENR-4.9 DB impact
**NO SCHEMA CHANGE / NO MIGRATION.** Candidate/Application UI continues persisting existing curriculum-course-mapping identifiers through existing applicant academic-preference/course-choice structures. No table, column, FK, index, sequence, permission/reference-data migration, or alternate academic persistence model is introduced.
