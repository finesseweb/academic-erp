# ADR 161 — Curriculum Term Academic Periods + Fee Due Date Boundary

Date: 2026-09-09
Status: IMPLEMENTED — QA PENDING

## Decision
Do not create a second Semester/Year master in Academic Calendar. `curriculum_terms` remains the authoritative identity for Semester 1, Semester 2, Year 1, etc.

Academic Calendar stores only the session-specific date boundary for an existing Curriculum Term through `academic_calendar_term_periods`:
- Academic Calendar
- Curriculum Term
- Start Date
- End Date
- College Override governance flag
- Status

This solves different UG/PG/programme calendars naturally because BA Semester 1 and MA Semester 1 are different Curriculum Term records and can have different dates in the same Academic Session.

## Event linkage
Calendar Events may optionally reference a Curriculum Academic Period. Period-scoped events (for example Mid Semester Examination) must remain inside that period. University-wide events/holidays may remain unscoped and continue to use Academic Session boundaries.

Existing events are NOT guessed/backfilled to a Semester. They remain University-wide until explicitly mapped.

## Fee linkage
ADR 160 Standard Due Date is now validated against the applicable Curriculum Academic Period:
- PER_TERM / SPECIFIC_TERM: due date must be inside the exact Curriculum Term period.
- PER_ACADEMIC_YEAR / SPECIFIC_ACADEMIC_YEAR: due date must be inside the combined boundary of the Curriculum Terms covered by that academic year.
- ONE_TIME / Admission Initial: not forced into a Curriculum Term boundary.
- Fee Structure activation re-validates existing due dates against current Academic Period boundaries.
- Fee Demand continues to snapshot the due date; historical generated demand is not silently changed.

## Installment / Late Fine
Installment due dates remain authoritative when an installment schedule exists. Otherwise the Fee Demand Item due-date snapshot is authoritative. ADR 158 Late Fine QA remains deferred until aligned to this hierarchy.

## Governance
Existing University Academic Calendar and College adoption/event-override architecture is preserved. Curriculum Academic Periods are University-defined. `allow_college_override` is stored as governance metadata for the later effective College period-override path; no College may silently change University period dates.

## Cleanup
Curriculum cleanup treats Academic Calendar Term Period mappings as downstream references. Full Academic Reset removes these mappings before Curriculum Terms. Calendar deletion cascades its term-period mappings. Real/master calendar configuration is preserved by ordinary transactional cleanup.

## QA gate
1. Existing Curriculum already contains Semester/Year terms.
2. Academic Calendar shows those terms as selectable; no duplicate Semester creation.
3. Assign BA Semester 1 start/end and MA Semester 1 different start/end in same session.
4. Add an Examination Window mapped to BA Semester 1; date inside boundary succeeds.
5. Event outside boundary is blocked.
6. General University holiday without period remains allowed inside Session.
7. Fee Setup Semester 1 Standard Due Date inside boundary succeeds.
8. Due Date outside boundary is blocked server-side.
9. Fee Structure activation revalidates boundary.
10. Demand generation snapshots Due Date unchanged.
11. Curriculum test cleanup is blocked while period mapping exists; full reset deletes safely.
