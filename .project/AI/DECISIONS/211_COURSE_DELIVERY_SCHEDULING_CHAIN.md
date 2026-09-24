# ADR 211 — Course Delivery Scheduling Chain

Status: IMPLEMENTED — OWNER QA REQUIRED
Date: 2026-09-21

The linked operational chain is `Course Offering → Faculty Allocation → Timetable Entry → Class Schedule`. College Rooms are reusable physical resources referenced by Timetable entries and snapshotted onto dated Class Schedules.

Timetable entries are recurring weekly rules with weekday, time and effective date range. Activation requires an active Faculty Allocation and active Room when supplied. Active overlap is rejected for faculty, room and the same Course Offering delivery scope. Class Scheduling creates dated occurrences only from active Timetable entries, requires the matching weekday/effective range, and snapshots time/room so history does not drift.

Room `capacity` is physical capacity. The applicable delivery strength is derived from ACTIVE, ENROLLED Student Enrollment placements that selected the exact Curriculum Course Mapping: Batch-wide allocations count the Batch roster and Section allocations count only that Section. Timetable create/edit/activation and Class Schedule create/edit reject a selected room when derived strength exceeds capacity. Section stores no capacity or maximum-strength field.

Dated Class Schedule create/edit resolves the ACTIVE College Academic Calendar adopted for the Programme Offering Academic Session. The class date must fall inside the exact course Curriculum Term's ACTIVE Academic Calendar period and must not fall inside an effective ACTIVE `HOLIDAY` or `VACATION`. An ACTIVE governed College event override replaces the University event dates for this check. Recurring Timetable rules are not rejected merely because one recurrence falls on a holiday; validation occurs when a dated occurrence is created or edited. Attendance continues to consume only the resulting legitimate Class Schedule.

`faculty_allocations.weekly_load` means maximum teaching hours per week for that exact allocation. Timetable activation sums the durations of its active recurring entries whose effective periods overlap and rejects activation above the configured limit. A null load remains unlimited. It is not a College-wide Faculty workload cap across separate allocations.

Attendance remains downstream and must reference Class Schedule rather than recreate course/faculty/time context.

## 2026-09-22 — Dated class edit boundary
A Class Schedule may be corrected while it remains `SCHEDULED`. Editing revalidates the selected active Timetable entry and occurrence date and refreshes the Timetable-derived time/Room snapshot. `COMPLETED` and `CANCELLED` are terminal operational-history states and cannot be edited or reopened through normal Class Scheduling. This preserves downstream Attendance history from post-finalization drift.

Inactive Timetable entries are editable by users with `college_timetable.manage`. Editing may change Faculty Allocation, Room, weekday/time, effective period and notes, re-runs College scope/conflict validation, and preserves existing dated Class Schedule snapshots. Active Timetable entries must be deactivated before editing.
