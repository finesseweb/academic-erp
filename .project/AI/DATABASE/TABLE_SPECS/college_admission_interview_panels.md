# Table: `college_admission_interview_panels`

## Purpose
Stores data related to the college admission interview panels domain in Academic ERP.

## Database Table
`college_admission_interview_panels`

## Columns

- `college_id` (unsignedBigInteger)
- `name` (string)
- `starts_at` (dateTime)
- `slot_duration_minutes` (unsignedSmallInteger)
- `venue` (string)
- `status` (enum)
- `created_by` (unsignedBigInteger)
- `updated_by` (unsignedBigInteger)
- `college_id` (foreign)
- `created_by` (foreign)
- `updated_by` (foreign)
- `college_admission_interview_panel_id` (unsignedBigInteger)
- `evaluator_user_id` (unsignedBigInteger)
- `evaluator_name_snapshot` (string)
- `college_admission_interview_panel_id` (foreign)
- `evaluator_user_id` (foreign)
- `slot_sequence` (unsignedInteger)
- `cai_panel_fk` (dropForeign)
- `cai_panel_slot_idx` (dropIndex)
- `id`
- `timestamps`

## Relationships

Relationships should follow foreign key definitions from Laravel migrations and Eloquent models.

## Business Usage

Used by the related ERP module workflows.

## Documentation Status

Generated during documentation synchronization on 2026-09-11. Review and enrich with business rules when module documentation is finalized.
