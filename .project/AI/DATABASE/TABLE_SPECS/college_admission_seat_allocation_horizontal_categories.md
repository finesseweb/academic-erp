# Table: `college_admission_seat_allocation_horizontal_categories`

## Purpose
Stores data related to the college admission seat allocation horizontal categories domain in Academic ERP.

## Database Table
`college_admission_seat_allocation_horizontal_categories`

## Columns

- `college_id` (unsignedBigInteger)
- `college_program_intake_id` (unsignedBigInteger)
- `bucket_type` (enum)
- `bucket_key` (string)
- `college_program_reservation_plan_id` (unsignedBigInteger)
- `college_admission_merit_entry_id` (unsignedBigInteger)
- `college_admission_application_id` (unsignedBigInteger)
- `college_admission_application_choice_id` (unsignedBigInteger)
- `college_admission_document_verification_id` (unsignedBigInteger)
- `college_admission_score_id` (unsignedBigInteger)
- `college_admission_selection_rule_id` (unsignedBigInteger)
- `merit_rank` (unsignedInteger)
- `final_weighted_score` (decimal)
- `physical_seat_type` (enum)
- `physical_reservation_category_id` (unsignedBigInteger)
- `physical_category_code` (string)
- `physical_category_name` (string)
- `allocation_round` (unsignedSmallInteger)
- `status` (enum)
- `decision_note` (text)
- `allocated_at` (timestamp)
- `allocated_by` (unsignedBigInteger)
- `cancelled_at` (timestamp)
- `cancelled_by` (unsignedBigInteger)
- `cancellation_reason` (text)
- `college_id` (foreign)
- `college_program_intake_id` (foreign)
- `college_program_reservation_plan_id` (foreign)
- `college_admission_merit_entry_id` (foreign)
- `college_admission_application_id` (foreign)
- `college_admission_application_choice_id` (foreign)
- `college_admission_document_verification_id` (foreign)
- `college_admission_score_id` (foreign)
- `college_admission_selection_rule_id` (foreign)
- `physical_reservation_category_id` (foreign)
- `allocated_by` (foreign)
- `cancelled_by` (foreign)
- `college_admission_merit_entry_id` (unique)
- `college_admission_application_choice_id` (unique)
- `college_admission_seat_allocation_id` (unsignedBigInteger)
- `reservation_category_id` (unsignedBigInteger)
- `category_code` (string)
- `category_name` (string)
- `fulfills_target` (boolean)
- `created_by` (unsignedBigInteger)
- `college_admission_seat_allocation_id` (foreign)
- `reservation_category_id` (foreign)
- `created_by` (foreign)
- `id`
- `timestamps`

## Relationships

Relationships should follow foreign key definitions from Laravel migrations and Eloquent models.

## Business Usage

Used by the related ERP module workflows.

## Documentation Status

Generated during documentation synchronization on 2026-09-11. Review and enrich with business rules when module documentation is finalized.
