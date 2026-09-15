# Table: `college_admission_form_field_comparisons`

## Purpose
Stores data related to the college admission form field comparisons domain in Academic ERP.

## Database Table
`college_admission_form_field_comparisons`

## Columns

- `target_field_id` (unsignedBigInteger)
- `source_field_id` (unsignedBigInteger)
- `operator` (enum)
- `is_active` (boolean)
- `target_field_id` (foreign)
- `source_field_id` (foreign)
- `trigger_field_id` (unsignedBigInteger)
- `trigger_values` (json)
- `is_read_only_when_active` (boolean)
- `trigger_field_id` (foreign)
- `id`
- `timestamps`

## Relationships

Relationships should follow foreign key definitions from Laravel migrations and Eloquent models.

## Business Usage

Used by the related ERP module workflows.

## Documentation Status

Generated during documentation synchronization on 2026-09-11. Review and enrich with business rules when module documentation is finalized.
