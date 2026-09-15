# academic_calendars

## Identity
- Business entity: official University Academic Calendar header for one Academic Session.
- Physical table / Eloquent model: `academic_calendars` / `AcademicCalendar`.
- Scope/grain: University-owned; exactly one row per University + Academic Session.

## Columns
- `id` — unsigned BIGINT primary key.
- `university_id` — required FK to `universities.id`; explicit University ownership.
- `academic_session_id` — required FK to `academic_sessions.id`.
- `name` — required calendar display name, max 150.
- `code` — required stable University-local code, max 50.
- `notes` — optional general calendar note.
- `status` — `ACTIVE | INACTIVE`; non-destructive lifecycle.
- `created_by`, `updated_by` — optional actor FKs to `users.id`.
- timestamps.

## Keys / Indexes
- Unique (`university_id`, `academic_session_id`) — one official University calendar per Academic Session.
- Unique (`university_id`, `code`).
- Index (`university_id`, `status`).

## Relationships
- University 1 -> N Academic Calendars.
- Academic Session 1 -> 0..1 Academic Calendar within the same University.
- Academic Calendar 1 -> N `academic_calendar_events`.

## Rules
- Referenced Academic Session must belong to the same University.
- Calendar is governance/configuration, not Routine/Timetable.
- Calendar is never hard-deleted by normal UI; use status.
- Future College Academic Calendars adopt this University calendar and may override only events explicitly marked `allow_college_override = true`.
