# Rooms, Timetable and Class Scheduling

Status: IMPLEMENTED — OWNER QA REQUIRED

- Rooms: `/college/{college}/rooms`; College-owned code/name/location/type/capacity/status.
- Timetable: `/college/{college}/timetables`; consumes Faculty Allocation and optional Room; recurring weekday/time/effective period; inactive-first lifecycle.
- Class Scheduling: `/college/{college}/class-schedules`; consumes active Timetable and creates dated SCHEDULED occurrences; supports COMPLETED/CANCELLED lifecycle.

Timetable create/edit time fields use the shared project `TimePicker` (hour, minute, AM/PM; 5-minute step) used by Interview Scheduling; browser-native time inputs are not used. Submitted values remain `HH:mm` for the existing backend contract.

Conflict rules reject overlapping active Faculty, Room, and Course Offering delivery-scope entries. Room deactivation is blocked while active Timetable entries use it. Cleanup order is Class Schedule → Timetable → Room/Faculty Allocation → Course Offering.

Timetable creation exposes only ACTIVE Faculty Allocations and rejects inactive allocation IDs server-side. Faculty Allocation deactivation is blocked while an ACTIVE Timetable entry depends on it. Navigation follows setup order: Course Offerings → Faculty Allocation → Rooms → Timetable → Class Scheduling.

Timetable rows expose Edit only while `INACTIVE` and the actor has `college_timetable.manage`. The edit dialog is prefilled and uses the same selectors/date controls as creation. Laravel rechecks exact College ownership, active Faculty Allocation, Room ownership, time/effective-period validity and overlap rules. Active entries must be deactivated before editing.

When a Faculty Allocation has Maximum Teaching Hours / Week, Timetable activation sums active recurring slot duration for that allocation across overlapping effective periods and blocks any excess. Class Schedule occurrences inherit the approved Timetable duration and do not consume the limit a second time.

Next after owner QA: Attendance Operations consuming Class Schedule.

## Scheduled class edit lifecycle
A dated Class Schedule remains editable only while its status is `SCHEDULED`. Authorized users may change the active Timetable entry, class date, and note; the server re-runs Timetable weekday/effective-period/active-allocation validation, prevents duplicate Timetable-entry/date occurrences, and refreshes the snapshotted start time, end time, and Room from the selected Timetable entry. `COMPLETED` and `CANCELLED` are terminal locked states: the occurrence cannot be edited or moved back to another status.

## Academic Calendar boundary
Class Schedule create and edit require an ACTIVE College Academic Calendar adopting the ACTIVE University calendar for the Programme Offering Academic Session. The selected date must be inside the exact course Curriculum Term's ACTIVE Academic Calendar period. Effective ACTIVE `HOLIDAY` and `VACATION` dates block scheduling; an ACTIVE allowed College override supplies the effective replacement title/date range. Timetable remains a recurring rule and is not invalidated by individual holidays. Attendance requires the valid dated Class Schedule and therefore inherits this calendar boundary without a second roster or manual-student workflow.
