# Table: `college_admission_interview_panel_breaks`

## Purpose
Stores data related to the college admission interview panel breaks domain in Academic ERP.

## Database Table
`college_admission_interview_panel_breaks`

## Columns

- `session_duration_minutes` (unsignedInteger)
- `college_admission_interview_panel_id` (unsignedBigInteger)
- `label` (string)
- `starts_at` (dateTime)
- `ends_at` (dateTime)
- `college_admission_interview_panel_id` (foreign)
- `session_duration_minutes` (dropColumn)
- `id`
- `timestamps`

## Relationships

Relationships should follow foreign key definitions from Laravel migrations and Eloquent models.

## Business Usage

Used by the related ERP module workflows.

## Documentation Status

Generated during documentation synchronization on 2026-09-11. Review and enrich with business rules when module documentation is finalized.
