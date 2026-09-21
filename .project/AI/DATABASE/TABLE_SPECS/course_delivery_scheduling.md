# Course Delivery Scheduling Tables

- `college_rooms`: College-owned physical teaching-space master.
- `timetable_entries`: recurring rule linked to `faculty_allocations`, optional `college_rooms`, weekday/time and effective range.
- `class_schedules`: dated occurrence linked to `timetable_entries`, with time/room snapshot and SCHEDULED/COMPLETED/CANCELLED status.

All parent foreign keys restrict deletion; Test Data Cleanup implements child-first removal.
