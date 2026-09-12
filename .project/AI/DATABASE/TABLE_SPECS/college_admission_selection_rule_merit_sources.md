# Table: `college_admission_selection_rule_merit_sources`

## Purpose
Stores data related to the college admission selection rule merit sources domain in Academic ERP.

## Database Table
`college_admission_selection_rule_merit_sources`

## Columns

- `college_admission_selection_rule_id` (foreignId)
- `label` (string)
- `source_type` (string)
- `obtained_field_id` (unsignedBigInteger)
- `maximum_field_id` (unsignedBigInteger)
- `weight_percent` (decimal)
- `display_order` (unsignedSmallInteger)
- `obtained_field_id` (foreign)
- `maximum_field_id` (foreign)
- `merit_source_snapshot` (json)
- `merit_source_snapshot` (dropColumn)
- `id`
- `timestamps`

## Relationships

Relationships should follow foreign key definitions from Laravel migrations and Eloquent models.

## Business Usage

Used by the related ERP module workflows.

## Documentation Status

Generated during documentation synchronization on 2026-09-11. Review and enrich with business rules when module documentation is finalized.
