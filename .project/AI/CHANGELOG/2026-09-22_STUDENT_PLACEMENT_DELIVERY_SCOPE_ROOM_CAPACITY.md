# Student placement, delivery scope and room capacity correction — 2026-09-22

## Outcome
- Student Identity is the only placement UI and supports atomic multi-student Batch/Section assignment.
- Student Enrollment no longer duplicates placement controls.
- Faculty Allocation requires explicit Batch-wide or Section-specific scope; no Section A/first-Section fallback exists.
- Section capacity remains deliberately absent. Strength is derived from canonical active enrolled placements and exact course choices.
- Timetable create/edit/activation and Class Schedule create/edit enforce physical Room capacity against the applicable derived course roster.
- Attendance keeps strict Batch/Section and exact-course roster resolution.
- Placement has no Fee Demand side effect.

## Data impact
No migration or new table/column. Existing Enrollment, Faculty Allocation and Room fields remain canonical.

## QA focus
Verify mixed-offering bulk placement rejection and atomicity; active/inactive and cross-parent validation; explicit Faculty delivery scope; Batch versus Section roster counts; room under-capacity rejection after roster growth; inactive Timetable edit permission; strict Attendance roster; unchanged Fee Demand.
