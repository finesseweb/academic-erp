# Table: `college_admission_application_academic_preferences`

## Purpose
Stores data related to the college admission application academic preferences domain in Academic ERP.

## Database Table
`college_admission_application_academic_preferences`

## Columns

- `college_admission_application_id` (unsignedBigInteger)
- `college_admission_application_id` (unique)
- `college_program_offering_id` (unsignedBigInteger)
- `curriculum_id` (unsignedBigInteger)
- `discipline_id` (unsignedBigInteger)
- `specialization_id` (unsignedBigInteger)
- `curriculum_snapshot` (json)
- `college_admission_application_id` (foreign)
- `college_program_offering_id` (foreign)
- `curriculum_id` (foreign)
- `discipline_id` (foreign)
- `specialization_id` (foreign)
- `curriculum_term_id` (unsignedBigInteger)
- `curriculum_slot_id` (unsignedBigInteger)
- `curriculum_course_mapping_id` (unsignedBigInteger)
- `course_id` (unsignedBigInteger)
- `selection_source` (enum)
- `curriculum_term_id` (foreign)
- `curriculum_slot_id` (foreign)
- `curriculum_course_mapping_id` (foreign)
- `course_id` (foreign)
- `id`
- `timestamps`

## Relationships

Relationships should follow foreign key definitions from Laravel migrations and Eloquent models.

## Business Usage

Used by the related ERP module workflows.

## Documentation Status

Generated during documentation synchronization on 2026-09-11. Review and enrich with business rules when module documentation is finalized.
