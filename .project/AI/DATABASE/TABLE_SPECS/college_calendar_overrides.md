# Table: `college_calendar_overrides`

## Purpose
Stores data related to the college calendar overrides domain in Academic ERP.

## Database Table
`college_calendar_overrides`

## Columns

- `college_academic_calendar_id` (foreignId)
- `academic_calendar_id` (unsignedBigInteger)
- `academic_calendar_event_id` (foreignId)
- `title` (string)
- `start_date` (date)
- `end_date` (date)
- `description` (text)
- `reason` (text)
- `status` (enum)
- `created_by` (foreignId)
- `updated_by` (foreignId)
- `academic_calendar_id` (foreign)
- `id`
- `timestamps`

## Relationships

Relationships should follow foreign key definitions from Laravel migrations and Eloquent models.

## Business Usage

Used by the related ERP module workflows.

## Documentation Status

Generated during documentation synchronization on 2026-09-11. Review and enrich with business rules when module documentation is finalized.
