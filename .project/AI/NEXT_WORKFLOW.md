# Next Workflow — Academic Calendar QA

Academic Approval QA is complete. Validate the new University Academic Calendar milestone:

1. Run migrations and confirm `academic_calendars` and `academic_calendar_events` exist.
2. Confirm `academic_calendar.*` permissions are registered and Super Admin can open `/admin/academic-calendars`.
3. Create one calendar for an existing Academic Session.
4. Attempt a second calendar for the same session and confirm it is rejected.
5. Add an event whose dates fall inside the Academic Session and confirm it saves.
6. Attempt an event before the session start or after the session end and confirm backend validation rejects it.
7. Edit an event and verify `allow_college_override` persists correctly.
8. Disable/re-enable a calendar and event; verify records are preserved.
9. Confirm audit log rows are written for create/update/status operations.
10. Confirm a user without the relevant permission cannot perform the protected action.
11. Run the frontend production build and verify responsive page behavior.
12. Owner reviews the page before advancing to the next hierarchy milestone.

Important: Academic Calendar is not Routine/Timetable and not detailed Examination scheduling.
