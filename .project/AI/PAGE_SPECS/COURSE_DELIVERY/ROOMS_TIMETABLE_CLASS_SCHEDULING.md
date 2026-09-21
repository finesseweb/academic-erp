# Rooms, Timetable and Class Scheduling

Status: IMPLEMENTED — OWNER QA REQUIRED

- Rooms: `/college/{college}/rooms`; College-owned code/name/location/type/capacity/status.
- Timetable: `/college/{college}/timetables`; consumes Faculty Allocation and optional Room; recurring weekday/time/effective period; inactive-first lifecycle.
- Class Scheduling: `/college/{college}/class-schedules`; consumes active Timetable and creates dated SCHEDULED occurrences; supports COMPLETED/CANCELLED lifecycle.

Conflict rules reject overlapping active Faculty, Room, and Course Offering delivery-scope entries. Room deactivation is blocked while active Timetable entries use it. Cleanup order is Class Schedule → Timetable → Room/Faculty Allocation → Course Offering.

Timetable creation exposes only ACTIVE Faculty Allocations and rejects inactive allocation IDs server-side. Faculty Allocation deactivation is blocked while an ACTIVE Timetable entry depends on it. Navigation follows setup order: Course Offerings → Faculty Allocation → Rooms → Timetable → Class Scheduling.

When a Faculty Allocation has Maximum Teaching Hours / Week, Timetable activation sums active recurring slot duration for that allocation across overlapping effective periods and blocks any excess. Class Schedule occurrences inherit the approved Timetable duration and do not consume the limit a second time.

Next after owner QA: Attendance Operations consuming Class Schedule.
