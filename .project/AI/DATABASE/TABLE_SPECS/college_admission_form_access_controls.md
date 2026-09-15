# Table: `college_admission_form_access_controls`

## Purpose
Stores data related to the college admission form access controls domain in Academic ERP.

## Database Table
`college_admission_form_access_controls`

## Columns

- `university_id` (unsignedBigInteger)
- `college_id` (unsignedBigInteger)
- `is_enabled` (boolean)
- `governance_mode` (enum)
- `allow_college_fee_override` (boolean)
- `enabled_by` (unsignedBigInteger)
- `enabled_at` (timestamp)
- `updated_by` (unsignedBigInteger)
- `university_id` (foreign)
- `college_id` (foreign)
- `enabled_by` (foreign)
- `updated_by` (foreign)
- `college_id` (unique)
- `id`
- `timestamps`

## Relationships

Relationships should follow foreign key definitions from Laravel migrations and Eloquent models.

## Business Usage

Used by the related ERP module workflows.

## Documentation Status

Generated during documentation synchronization on 2026-09-11. Review and enrich with business rules when module documentation is finalized.
