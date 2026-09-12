# ADR 169 — Academic Period effective-year + occupied-date guard

Date: 2026-09-09
Status: Implemented; QA pending

## Problem
Academic Period dates are bounded by Academic Session + Curriculum Effective From/To (ADR 164), but two UX/integrity gaps remained:

1. Curriculum effective values can arrive as ISO datetimes (for example `2026-08-31T00:00:00.000000Z`). The shared DatePicker was parsing those directly as `YYYY-MM-DD`, producing an invalid day component and preventing the Year selector from reliably deriving its allowed years.
2. When one Curriculum Term (for example Semester 1) already owns a date range, another Term of the same Curriculum could still select dates from that occupied range in the picker. Backend also needed a hard no-overlap guard.

## Decision
The authoritative date chain remains:

`Academic Session ∩ Curriculum Effective From/To → Curriculum Term Academic Period → Fee Billing Period → Standard Due Date → Fee Demand snapshot`

No duplicate Semester/Year master is introduced.

### DatePicker normalization
The shared project DatePicker must normalize `min` and `max` to date-only `YYYY-MM-DD` before parsing. Therefore the year list is derived strictly from the effective boundary supplied by the module.

Example:
- Curriculum effective window: 31-Aug-2026 → 02-Mar-2030
- Year selector: 2026, 2027, 2028, 2029, 2030 only
- dates outside that window remain disabled

This is a shared DatePicker correction and therefore also benefits Fee Setup and any other bounded project DatePicker.

### Same-Curriculum period occupancy
Within one Academic Calendar + one Curriculum, ACTIVE Curriculum Term Academic Periods must not overlap.

Example:
- BA Curriculum / Semester 1 = 01-Sep-2026 → 30-Sep-2026
- BA Curriculum / Semester 2 cannot use any date from 01-Sep-2026 → 30-Sep-2026

The Add/Edit Academic Period DatePickers receive existing ranges for the same Curriculum as disabled ranges. The modal also shows the already allocated Term/date ranges so the operator understands why dates are unavailable.

Backend validation is authoritative. A direct request whose Start/End interval overlaps another ACTIVE Term Period of the same Curriculum is rejected even if the UI is bypassed.

Periods belonging to a different Curriculum are not blocked by this rule because different programmes/curricula may legitimately run in parallel.

When editing an existing Academic Period, its own stored range is excluded from the overlap check; other Term periods of the same Curriculum remain protected.

## Fee linkage
ADR 164 remains unchanged: Fee Setup consumes the exact Academic Period boundary. ADR 169 strengthens the upstream Academic Period integrity, so Fee Setup cannot inherit overlapping Semester/Year boundaries from the same Curriculum.

Late Fine remains QA-deferred until Academic Calendar + Fee Setup due-date linkage passes.

## Migration
No migration required.

## QA gate
1. Select a Curriculum with Effective From/To spanning multiple years.
2. Open Start Date and confirm only effective-window years are listed.
3. Confirm dates before Effective From and after Effective To are disabled.
4. Configure Semester 1 and save.
5. Add Semester 2 for the same Curriculum; Semester 1 dates must be disabled and shown as already allocated.
6. Attempt direct/normal save with an overlapping Semester 2 range; backend must block it.
7. Save a non-overlapping Semester 2 range; it must succeed.
8. Edit Semester 1; its own range remains editable while Semester 2 range is protected.
9. Confirm a different Curriculum may use overlapping calendar dates.
10. Continue to Fee Setup only after PASS.
