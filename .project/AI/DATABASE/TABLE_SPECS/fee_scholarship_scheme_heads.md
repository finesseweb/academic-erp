# Table: `fee_scholarship_scheme_heads`

## Purpose
Stores data related to the fee scholarship scheme heads domain in Academic ERP.

## Database Table
`fee_scholarship_scheme_heads`

## Columns

- `university_id` (unsignedBigInteger)
- `college_id` (unsignedBigInteger)
- `academic_session_id` (unsignedBigInteger)
- `program_template_id` (unsignedBigInteger)
- `college_program_offering_id` (unsignedBigInteger)
- `name` (string)
- `code` (string)
- `benefit_type` (enum)
- `calculation_type` (enum)
- `benefit_value` (decimal)
- `maximum_benefit_amount` (decimal)
- `eligibility_mode` (enum)
- `approval_mode` (enum)
- `description` (text)
- `status` (enum)
- `created_by` (unsignedBigInteger)
- `updated_by` (unsignedBigInteger)
- `university_id` (foreign)
- `college_id` (foreign)
- `academic_session_id` (foreign)
- `program_template_id` (foreign)
- `college_program_offering_id` (foreign)
- `fee_scholarship_scheme_id` (unsignedBigInteger)
- `fee_head_id` (unsignedBigInteger)
- `fee_scholarship_scheme_id` (foreign)
- `fee_head_id` (foreign)
- `reservation_category_id` (unsignedBigInteger)
- `reservation_category_id` (foreign)
- `id`
- `timestamps`

## Relationships

Relationships should follow foreign key definitions from Laravel migrations and Eloquent models.

## Business Usage

Used by the related ERP module workflows.

## Documentation Status

Generated during documentation synchronization on 2026-09-11. Review and enrich with business rules when module documentation is finalized.
