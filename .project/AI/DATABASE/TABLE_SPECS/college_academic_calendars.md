# Table: `college_academic_calendars`

## Purpose
Stores data related to the college academic calendars domain in Academic ERP.

## Database Table
`college_academic_calendars`

## Columns

- `college_id` (foreignId)
- `university_academic_calendar_id` (unsignedBigInteger)
- `status` (enum)
- `notes` (text)
- `created_by` (foreignId)
- `updated_by` (foreignId)
- `university_academic_calendar_id` (foreign)
- `id`
- `timestamps`

## Relationships

Relationships should follow foreign key definitions from Laravel migrations and Eloquent models.

## Business Usage

Used by the related ERP module workflows.

## Documentation Status

Generated during documentation synchronization on 2026-09-11. Review and enrich with business rules when module documentation is finalized.
