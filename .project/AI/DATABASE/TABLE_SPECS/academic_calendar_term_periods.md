# Table: `academic_calendar_term_periods`

## Purpose
Stores data related to the academic calendar term periods domain in Academic ERP.

## Database Table
`academic_calendar_term_periods`

## Columns

- `academic_calendar_id` (foreignId)
- `curriculum_term_id` (foreignId)
- `start_date` (date)
- `end_date` (date)
- `allow_college_override` (boolean)
- `status` (enum)
- `created_by` (foreignId)
- `updated_by` (foreignId)
- `academic_calendar_term_period_id` (foreignId)
- `ace_calendar_period_idx` (dropIndex)
- `academic_calendar_term_period_id` (dropColumn)
- `id`
- `timestamps`

## Relationships

Relationships should follow foreign key definitions from Laravel migrations and Eloquent models.

## Business Usage

Used by the related ERP module workflows.

## Documentation Status

Generated during documentation synchronization on 2026-09-11. Review and enrich with business rules when module documentation is finalized.

## Implemented Contract - reviewed 2026-09-12

- `academic_calendar_id` references `academic_calendars.id` with cascade delete.
- `curriculum_term_id` references `curriculum_terms.id` with restrict delete.
- (`academic_calendar_id`, `curriculum_term_id`) is unique.
- (`academic_calendar_id`, `status`) is indexed.
- Only ACTIVE Terms from a current approved Curriculum in the Calendar's University and Academic Session are accepted.
- Dates must fit the Academic Session and Curriculum effective window; ACTIVE periods in one Curriculum cannot overlap.
- `academic_calendar_events.academic_calendar_term_period_id` optionally links an event to a period with null-on-delete behavior.
- Period-linked event dates must fit the selected period.

The generated column list above incorrectly includes rollback operations
(`dropIndex`/`dropColumn`) as if they were columns. Those are migration-down
operations, not fields in `academic_calendar_term_periods`.
