# academic_calendar_events

## Identity
- Business entity: dated University Academic Calendar event/range.
- Physical table / Eloquent model: `academic_calendar_events` / `AcademicCalendarEvent`.
- Scope/grain: one row per event/range on one University Academic Calendar.

## Columns
- `id` — unsigned BIGINT primary key.
- `academic_calendar_id` — required FK to `academic_calendars.id`.
- `event_type` — bounded classification string; current UI values: `ACADEMIC`, `REGISTRATION`, `INSTRUCTION`, `EXAMINATION_WINDOW`, `HOLIDAY`, `VACATION`, `OTHER`.
- `title` — required event title, max 180.
- `start_date`, `end_date` — required DATE range; end cannot precede start.
- `description` — optional event detail.
- `allow_college_override` — whether a future College Academic Calendar may override this University event.
- `status` — `ACTIVE | INACTIVE`.
- `display_order` — optional ordering hint; chronological date remains primary presentation order.
- `created_by`, `updated_by` — optional actor FKs.
- timestamps.

## Keys / Indexes
- Index (`academic_calendar_id`, `start_date`, `status`).
- Index (`academic_calendar_id`, `event_type`).

## Relationships / Rules
- Event belongs to exactly one Academic Calendar.
- Event date range must remain inside the parent Academic Session date range.
- Academic Session date updates are business-rule restricted when an existing event would become out-of-range; events are not auto-disabled or auto-deleted.
- Inactivation preserves history; normal UI does not hard-delete events.
- `EXAMINATION_WINDOW` records a University calendar window only; it is not an Examination Timetable and does not assign papers, rooms, invigilators or students.
- `INSTRUCTION` records University teaching-period milestones only; it is not a Routine/Class Timetable.
