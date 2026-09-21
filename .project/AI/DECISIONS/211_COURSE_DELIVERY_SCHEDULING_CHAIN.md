# ADR 211 — Course Delivery Scheduling Chain

Status: IMPLEMENTED — OWNER QA REQUIRED
Date: 2026-09-21

The linked operational chain is `Course Offering → Faculty Allocation → Timetable Entry → Class Schedule`. College Rooms are reusable physical resources referenced by Timetable entries and snapshotted onto dated Class Schedules.

Timetable entries are recurring weekly rules with weekday, time and effective date range. Activation requires an active Faculty Allocation and active Room when supplied. Active overlap is rejected for faculty, room and the same Course Offering delivery scope. Class Scheduling creates dated occurrences only from active Timetable entries, requires the matching weekday/effective range, and snapshots time/room so history does not drift.

`faculty_allocations.weekly_load` means maximum teaching hours per week for that exact allocation. Timetable activation sums the durations of its active recurring entries whose effective periods overlap and rejects activation above the configured limit. A null load remains unlimited. It is not a College-wide Faculty workload cap across separate allocations.

Attendance remains downstream and must reference Class Schedule rather than recreate course/faculty/time context.
