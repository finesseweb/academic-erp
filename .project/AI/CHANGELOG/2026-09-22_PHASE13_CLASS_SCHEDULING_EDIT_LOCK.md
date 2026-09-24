# 2026-09-22 — Phase 13 Class Scheduling edit lifecycle

- Added Edit action for `SCHEDULED` dated classes.
- Editable fields: active Timetable entry, class date and note.
- Edit reuses server-side weekday/effective-period/active-allocation and duplicate occurrence validation.
- Timetable-derived start/end time and Room snapshot refresh when the occurrence is edited.
- `COMPLETED` and `CANCELLED` are now terminal locked states: no edit and no status reopening.
- Added `CLASS_SCHEDULE_UPDATED` audit event.
- No database schema migration.
- Status: IMPLEMENTED — OWNER QA REQUIRED.


## UI consistency follow-up
- The Scheduled Class edit action uses the existing project table-action convention: a compact ghost icon button with the Pencil icon, matching editable rows such as Rooms.
- No standalone outlined text `Edit` button is introduced into the Class Scheduling table.
