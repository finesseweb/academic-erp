# curriculum_terms

## Purpose
Stores the ordered Terms / Semesters that belong to one versioned Curriculum Header.

This is the first child table in Curriculum Manage Structure.

## Columns
- `id`
- `curriculum_id` — required parent Curriculum Header; RESTRICT on delete
- `sequence_no` — required positive academic order within the curriculum
- `name` — curriculum-specific display label such as `Semester I` or `Term 1`
- `status` — `ACTIVE|INACTIVE`
- `created_by`, `updated_by` — nullable User actor references
- timestamps

## Constraints
- unique `(curriculum_id, sequence_no)`
- indexed `(curriculum_id, status, sequence_no)`

## Rules
- No hard delete.
- Structure mutations are allowed only while the parent Curriculum Header is `DRAFT`.
- `ACTIVE` and `RETIRED` Curriculum versions are read-only to preserve historical structures.
- No start/end dates are stored here; calendar dates remain an Academic Calendar concern.
- No credits, Course Categories, Course Types, Slots or Course Mapping are stored here.

## Audit Events
- `CURRICULUM_TERM_CREATED`
- `CURRICULUM_TERM_UPDATED`
- `CURRICULUM_TERM_STATUS_CHANGED`

## Change History
- 2026-08-22: Implemented as the first Curriculum Manage Structure milestone.
