# Academic Calendar → Class Schedule linkage — 2026-09-22

- Added server-side effective College Academic Calendar validation to dated Class Schedule create/edit.
- Requires the exact course Curriculum Term's ACTIVE calendar period to contain the class date.
- Blocks effective ACTIVE University/College-overridden `HOLIDAY` and `VACATION` dates with a clear validation message.
- Keeps recurring Timetable rules independent from individual holiday dates.
- Attendance inherits the governed date through its existing Class Schedule boundary.
- No placement, Fee Demand or schema changes.
