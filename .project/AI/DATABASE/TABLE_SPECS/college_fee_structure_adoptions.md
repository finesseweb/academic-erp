# Table: `college_fee_structure_adoptions`

## Purpose
Stores data related to the college fee structure adoptions domain in Academic ERP.

## Database Table
`college_fee_structure_adoptions`

## Columns

- `college_applicability` (string)
- `university_fee_structure_id` (unsignedBigInteger)
- `college_id` (unsignedBigInteger)
- `status` (string)
- `created_by` (unsignedBigInteger)
- `updated_by` (unsignedBigInteger)
- `university_fee_structure_id` (foreign)
- `college_id` (foreign)
- `created_by` (foreign)
- `updated_by` (foreign)
- `college_applicability` (dropColumn)
- `id`
- `timestamps`

## Relationships

Relationships should follow foreign key definitions from Laravel migrations and Eloquent models.

## Business Usage

Used by the related ERP module workflows.

## Documentation Status

Generated during documentation synchronization on 2026-09-11. Review and enrich with business rules when module documentation is finalized.
