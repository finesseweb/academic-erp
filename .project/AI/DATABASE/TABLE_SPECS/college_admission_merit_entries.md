# Table: `college_admission_merit_entries`

## Purpose
Stores data related to the college admission merit entries domain in Academic ERP.

## Database Table
`college_admission_merit_entries`

## Columns

- `college_id` (unsignedBigInteger)
- `college_program_intake_id` (unsignedBigInteger)
- `bucket_key` (string)
- `college_admission_application_id` (unsignedBigInteger)
- `college_admission_application_choice_id` (unsignedBigInteger)
- `college_admission_score_id` (unsignedBigInteger)
- `college_admission_selection_rule_id` (unsignedBigInteger)
- `generation_batch` (uuid)
- `rank` (unsignedInteger)
- `final_weighted_score` (decimal)
- `tie_break_snapshot` (json)
- `generated_at` (timestamp)
- `generated_by` (unsignedBigInteger)
- `college_id` (foreign)
- `college_program_intake_id` (foreign)
- `college_admission_application_id` (foreign)
- `college_admission_application_choice_id` (foreign)
- `college_admission_score_id` (foreign)
- `college_admission_selection_rule_id` (foreign)
- `generated_by` (foreign)
- `college_admission_application_choice_id` (unique)
- `id`
- `timestamps`

## Relationships

Relationships should follow foreign key definitions from Laravel migrations and Eloquent models.

## Business Usage

Used by the related ERP module workflows.

## Documentation Status

Generated during documentation synchronization on 2026-09-11. Review and enrich with business rules when module documentation is finalized.
