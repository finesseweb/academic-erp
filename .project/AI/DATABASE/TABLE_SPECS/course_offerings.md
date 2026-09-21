# Table Spec — `course_offerings`

Status: IMPLEMENTED — OWNER QA REQUIRED
Date: 2026-09-20
Decision: ADR 209

## Purpose
Operational batch-level delivery record for one existing Curriculum Course Mapping. It consumes existing academic structure and does not redefine it.

## Columns
- `id` BIGINT PK
- `batch_id` BIGINT NOT NULL FK -> `batches.id` RESTRICT
- `curriculum_course_mapping_id` BIGINT NOT NULL FK -> `curriculum_course_mappings.id` RESTRICT
- `status` ENUM(`ACTIVE`,`INACTIVE`) default `INACTIVE`
- `notes` TEXT NULL
- `created_by` BIGINT NULL FK -> `users.id` NULL ON DELETE
- `updated_by` BIGINT NULL FK -> `users.id` NULL ON DELETE
- timestamps

## Constraints / indexes
- UNIQUE (`batch_id`, `curriculum_course_mapping_id`)
- INDEX (`batch_id`, `status`)

## Ownership / scope
College ownership is inherited only through:
`course_offerings.batch_id -> batches.college_program_offering_id -> college_program_offerings.college_id`.

Do not add duplicate `college_id`, `program_template_id`, `curriculum_id`, `academic_session_id`, `curriculum_term_id`, `course_id`, or `section_id` columns. Those authoritative relationships already exist upstream.

## Integrity rule
The referenced Curriculum Course Mapping must resolve through Slot -> Term to the same Curriculum referenced by the Batch's parent Program Offering. This is server-enforced during creation and activation.

## Downstream
Phase 13 Faculty Allocation should consume `course_offerings.id` rather than bypassing Course Offering and allocating faculty directly to Course Master/Curriculum Mapping.
