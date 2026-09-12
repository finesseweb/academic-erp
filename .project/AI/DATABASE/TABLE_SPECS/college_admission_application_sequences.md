# Table: `college_admission_application_sequences`

## Purpose
Stores data related to the college admission application sequences domain in Academic ERP.

## Database Table
`college_admission_application_sequences`

## Columns

- `college_id` (unsignedBigInteger)
- `college_admission_cycle_id` (unsignedBigInteger)
- `next_number` (unsignedBigInteger)
- `college_id` (foreign)
- `college_admission_cycle_id` (foreign)
- `id`
- `timestamps`

## Relationships

Relationships should follow foreign key definitions from Laravel migrations and Eloquent models.

## Business Usage

Used by the related ERP module workflows.

## Documentation Status

Generated during documentation synchronization on 2026-09-11. Review and enrich with business rules when module documentation is finalized.
