# Patch — Attendance Calculation Level Consumption — 2026-09-23

Status: **IMPLEMENTED — OWNER QA REQUIRED**

Attendance Operations now consumes the resolved Academic Policy Attendance Rule `calculation_level` instead of always projecting the exact Course Offering.

- `COURSE`: finalized attendance for the exact Course Offering.
- `TERM`: finalized attendance for the same student across Course Offerings in the exact Curriculum Term and same College Programme Offering.
- `OVERALL`: finalized attendance for the same student across the same College Programme Offering.
- Policy `rounding_rule` continues to apply after aggregation.
- Only `FINALIZED` Attendance Registers are included.
- Raw Attendance records, historical registers, Class Schedules, Student placement and Fee Demand are not rewritten.
- TERM fails closed if the current Course Offering cannot resolve a Curriculum Term; it never silently widens to OVERALL.

No schema migration is required. Owner QA should verify the same student with finalized attendance in multiple courses and terms, then switch policy calculation level between COURSE / TERM / OVERALL and confirm held, attended and percentage totals change at the intended boundary.
